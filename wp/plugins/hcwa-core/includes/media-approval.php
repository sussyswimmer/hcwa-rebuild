<?php
/**
 * Photo approval (CLAUDE.md child safeguarding, contract gating rule 2).
 *
 * Two flags on every image: "Approved for web" and "Identifiable child". hcwa_image()
 * is the gated way to print a photo: it refuses anything not approved, and in a
 * no-identifiable context (Little Rose) also refuses identifiable children. A refusal
 * prints nothing and is listed in the Media library for staff.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The decision itself, kept pure so it is unit-tested.
 *
 * @param array $flags                   ['approved_for_web' => bool, 'identifiable_child' => bool].
 * @param bool  $no_identifiable_context True on Little Rose and other no-identifiable contexts.
 */
function hcwa_image_decision( array $flags, bool $no_identifiable_context ): bool {
	if ( empty( $flags['approved_for_web'] ) ) {
		return false;
	}
	if ( $no_identifiable_context && ! empty( $flags['identifiable_child'] ) ) {
		return false;
	}
	return true;
}

/**
 * Approval flags of an attachment. With Polylang media translation, a photo counts as
 * approved only if every translation of it is, and identifiable if any translation says so.
 *
 * @param int $attachment_id Attachment ID.
 * @return array{approved_for_web: bool, identifiable_child: bool, exists: bool}
 */
function hcwa_image_flags( int $attachment_id ): array {
	$ids = array( $attachment_id );
	if ( function_exists( 'pll_get_post_translations' ) ) {
		$ids = array_unique( array_merge( $ids, array_map( 'intval', (array) pll_get_post_translations( $attachment_id ) ) ) );
	}

	$flags = array(
		'approved_for_web'   => true,
		'identifiable_child' => false,
		'exists'             => 'attachment' === get_post_type( $attachment_id ),
	);

	foreach ( $ids as $id ) {
		$flags['approved_for_web']   = $flags['approved_for_web'] && (bool) get_post_meta( $id, 'hcwa_approved_for_web', true );
		$flags['identifiable_child'] = $flags['identifiable_child'] || (bool) get_post_meta( $id, 'hcwa_identifiable_child', true );
	}

	return $flags;
}

/**
 * Whether a context forbids identifiable children.
 *
 * $context may be:
 * - null: the current post (inside the loop or on a single page);
 * - a post ID or WP_Post: a center with "No identifiable children", a program run by such
 *   a center, or a news post carrying such a center's news tag;
 * - a bool, or an array with 'no_identifiable_children' => bool, to say it directly.
 *
 * @param mixed $context See above.
 */
function hcwa_image_context_no_identifiable( $context ): bool {
	if ( is_bool( $context ) ) {
		return $context;
	}
	if ( is_array( $context ) ) {
		return ! empty( $context['no_identifiable_children'] );
	}

	$post = null === $context ? get_post() : get_post( $context );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$result = false;

	if ( 'hcwa_center' === $post->post_type ) {
		$result = (bool) get_post_meta( $post->ID, 'hcwa_no_identifiable_children', true );
	} elseif ( 'hcwa_program' === $post->post_type ) {
		$center = (int) get_post_meta( $post->ID, 'hcwa_center_id', true );
		$result = $center && (bool) get_post_meta( $center, 'hcwa_no_identifiable_children', true );
	} elseif ( 'post' === $post->post_type ) {
		$protected_tags = hcwa_no_identifiable_news_tags();
		if ( $protected_tags ) {
			foreach ( (array) wp_get_post_terms( $post->ID, 'post_tag' ) as $term ) {
				if ( $term instanceof WP_Term && in_array( hcwa_original_term_slug( $term ), $protected_tags, true ) ) {
					$result = true;
					break;
				}
			}
		}
	}

	/**
	 * Filters whether a post is a no-identifiable-children context.
	 *
	 * @param bool    $result Decision so far.
	 * @param WP_Post $post   Context post.
	 */
	return (bool) apply_filters( 'hcwa_no_identifiable_context', $result, $post );
}

/**
 * News tag slugs (Vietnamese) of centers marked "No identifiable children".
 *
 * @return string[]
 */
function hcwa_no_identifiable_news_tags(): array {
	static $tags = null;
	if ( null !== $tags ) {
		return $tags;
	}

	$args = array(
		'post_type'        => 'hcwa_center',
		'post_status'      => 'any',
		'numberposts'      => 50,
		'fields'           => 'ids',
		'meta_key'         => 'hcwa_no_identifiable_children', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$args['lang'] = '';
	}

	$tags = array();
	foreach ( get_posts( $args ) as $center_id ) {
		$slug = (string) get_post_meta( (int) $center_id, 'hcwa_news_tag', true );
		if ( '' !== $slug ) {
			$tags[] = $slug;
		}
	}
	return $tags = array_values( array_unique( $tags ) );
}

/**
 * Slug of the Vietnamese version of a term (news tags are stored as VI slugs).
 *
 * @param WP_Term $term Term.
 */
function hcwa_original_term_slug( WP_Term $term ): string {
	if ( function_exists( 'pll_get_term' ) ) {
		$vi_id = (int) pll_get_term( $term->term_id, 'vi' );
		if ( $vi_id && $vi_id !== $term->term_id ) {
			$vi = get_term( $vi_id, $term->taxonomy );
			if ( $vi instanceof WP_Term ) {
				return $vi->slug;
			}
		}
	}
	return $term->slug;
}

/**
 * Whether an image may be shown in a context (see hcwa_image_context_no_identifiable()).
 * Refusals are logged for staff.
 *
 * @param int   $attachment_id Attachment ID.
 * @param mixed $context       Context post, ID, bool or array; null = current post.
 */
function hcwa_image_allowed( int $attachment_id, $context = null ): bool {
	if ( $attachment_id <= 0 ) {
		return false;
	}

	$flags = hcwa_image_flags( $attachment_id );
	if ( ! $flags['exists'] ) {
		return false;
	}

	$no_identifiable = hcwa_image_context_no_identifiable( $context );

	if ( hcwa_image_decision( $flags, $no_identifiable ) ) {
		return true;
	}

	$reason       = empty( $flags['approved_for_web'] ) ? 'not_approved' : 'identifiable_child';
	$context_post = ( is_int( $context ) || $context instanceof WP_Post || null === $context ) ? get_post( $context ) : null;
	hcwa_image_log_refusal( $attachment_id, $reason, $context_post instanceof WP_Post ? $context_post->ID : 0 );

	return false;
}

/**
 * Gated image markup. Returns '' when refused, so nothing broken is printed.
 * The current-language translation of the attachment is used for alt text.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Image size.
 * @param array  $attr          Attributes for wp_get_attachment_image().
 * @param mixed  $context       Context post, ID, bool or array; null = current post.
 */
function hcwa_image( int $attachment_id, string $size, array $attr = array(), $context = null ): string {
	if ( ! hcwa_image_allowed( $attachment_id, $context ) ) {
		return '';
	}

	$display_id = $attachment_id;
	if ( function_exists( 'pll_get_post' ) ) {
		$translated = (int) pll_get_post( $attachment_id, hcwa_lang() );
		if ( $translated ) {
			$display_id = $translated;
		}
	}

	return (string) wp_get_attachment_image( $display_id, $size, false, $attr );
}

/**
 * Whether photos placed in a post's own content must pass hcwa_image_allowed() (contract
 * rule 2): pages (the HCWA patterns' image slots), centers, programs and reports, story
 * posts, and any no-identifiable-children context (news tagged for Little Rose). Other
 * news posts keep their inline photos as published on the old site.
 *
 * @param WP_Post|int|null $post Post whose content is rendered; null = current post.
 */
function hcwa_content_images_gated( $post = null ): bool {
	$post  = get_post( $post );
	$gated = false;

	if ( $post instanceof WP_Post ) {
		if ( in_array( $post->post_type, array( 'page', 'hcwa_center', 'hcwa_program', 'hcwa_report' ), true ) ) {
			$gated = true;
		} elseif ( 'post' === $post->post_type ) {
			$gated = hcwa_image_context_no_identifiable( $post );
			foreach ( $gated ? array() : (array) get_the_category( $post->ID ) as $term ) {
				$slug = $term instanceof WP_Term ? (string) preg_replace( '/-(vi|en)$/', '', hcwa_original_term_slug( $term ) ) : '';
				if ( in_array( $slug, array( 'cau-chuyen', 'stories' ), true ) ) {
					$gated = true;
					break;
				}
			}
		}
	}

	/**
	 * Filters whether photos in this post's content need "Approved for web".
	 *
	 * @param bool         $gated Decision so far.
	 * @param WP_Post|null $post  Post being rendered.
	 */
	return (bool) apply_filters( 'hcwa_content_images_gated', $gated, $post instanceof WP_Post ? $post : null );
}

/**
 * Pure decision for one image placed in content: media library images in a gated context
 * need hcwa_image_allowed(). Images without a library ID (hcwa_image() and block output,
 * which are gated already, and external URLs) are left alone.
 *
 * @param int  $attachment_id Library ID from the block or the wp-image-N class, or 0.
 * @param bool $gated         hcwa_content_images_gated() for the post.
 * @param bool $allowed       hcwa_image_allowed() for the image in that post (ignored when not needed).
 */
function hcwa_content_image_decision( int $attachment_id, bool $gated, bool $allowed ): bool {
	return $attachment_id <= 0 || ! $gated || $allowed;
}

/**
 * core/image blocks: the whole figure (caption included) goes when the photo is refused.
 *
 * @param string        $block_content Rendered block.
 * @param array         $block         Parsed block.
 * @param WP_Block|null $instance      Block instance.
 * @return string
 */
function hcwa_gate_image_block( $block_content, $block, $instance = null ) {
	$id   = (int) ( $block['attrs']['id'] ?? 0 );
	$post = ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) ? get_post( (int) $instance->context['postId'] ) : get_post();
	if ( $id <= 0 || '' === trim( (string) $block_content ) ) {
		return $block_content;
	}
	$gated = hcwa_content_images_gated( $post );
	return hcwa_content_image_decision( $id, $gated, $gated && hcwa_image_allowed( $id, $post ) ) ? $block_content : '';
}
add_filter( 'render_block_core/image', 'hcwa_gate_image_block', 10, 3 );

/**
 * Every other library image in content (classic HTML, galleries, cover and media-text
 * blocks): the img tag goes when refused. WordPress finds the ID in the wp-image-N class.
 *
 * @param string $image         img tag.
 * @param string $context       'the_content', 'the_excerpt', ...
 * @param int    $attachment_id Library ID or 0.
 * @return string
 */
function hcwa_gate_content_img_tag( $image, $context, $attachment_id ) {
	if ( ! in_array( $context, array( 'the_content', 'the_excerpt' ), true ) || (int) $attachment_id <= 0 ) {
		return $image;
	}
	$post  = get_post();
	$gated = hcwa_content_images_gated( $post );
	return hcwa_content_image_decision( (int) $attachment_id, $gated, $gated && hcwa_image_allowed( (int) $attachment_id, $post ) ) ? $image : '';
}
add_filter( 'wp_content_img_tag', 'hcwa_gate_content_img_tag', 10, 3 );

/**
 * Partner logos (contract gating rule 5): on the live site only with the partner's
 * written permission ("Permission to show the logo"). Staging shows every logo.
 *
 * @param int       $partner_id Partner post ID (any language; the flag is shared).
 * @param bool|null $production Defaults to hcwa_is_production().
 */
function hcwa_partner_logo_allowed( int $partner_id, ?bool $production = null ): bool {
	if ( ! ( $production ?? hcwa_is_production() ) ) {
		return true;
	}
	return (bool) get_post_meta( $partner_id, 'hcwa_logo_permission', true );
}

/**
 * Remembers a refusal (at most once a day per image and reason, 50 entries) so the
 * Media library can show it. Also logged on non-production.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $reason        not_approved | identifiable_child.
 * @param int    $context_id    Context post ID or 0.
 */
function hcwa_image_log_refusal( int $attachment_id, string $reason, int $context_id ): void {
	$log = get_option( 'hcwa_image_refusals', array() );
	$log = is_array( $log ) ? $log : array();
	$key = $attachment_id . ':' . $reason;
	$now = time();

	if ( isset( $log[ $key ] ) && ( $now - (int) $log[ $key ]['time'] ) < DAY_IN_SECONDS ) {
		return;
	}

	$log[ $key ] = array(
		'id'      => $attachment_id,
		'reason'  => $reason,
		'context' => $context_id,
		'time'    => $now,
	);

	uasort( $log, static fn( $a, $b ) => $b['time'] <=> $a['time'] );
	update_option( 'hcwa_image_refusals', array_slice( $log, 0, 50, true ), false );

	if ( ! hcwa_is_production() ) {
		hcwa_log( sprintf( 'Image %d refused (%s) in context %d.', $attachment_id, $reason, $context_id ) );
	}
}

/**
 * Logged refusals for one attachment, newest first.
 *
 * @param int $attachment_id Attachment ID.
 * @return array[]
 */
function hcwa_image_refusals_for( int $attachment_id ): array {
	return array_values( array_filter( hcwa_image_recent_refusals(), static fn( $entry ) => (int) $entry['id'] === $attachment_id ) );
}

/**
 * Refusals from the last 30 days, newest first.
 *
 * @return array[]
 */
function hcwa_image_recent_refusals(): array {
	$log   = get_option( 'hcwa_image_refusals', array() );
	$since = time() - 30 * DAY_IN_SECONDS;
	return array_values( array_filter( is_array( $log ) ? $log : array(), static fn( $entry ) => is_array( $entry ) && (int) ( $entry['time'] ?? 0 ) >= $since ) );
}

/* -------------------------------------------------------------------------
 * Admin: fields on the attachment screen and in the media modal
 * ---------------------------------------------------------------------- */

/**
 * Approval checkboxes for images. Only editors and administrators can change them.
 *
 * @param array   $form_fields Fields.
 * @param WP_Post $post        Attachment.
 * @return array
 */
function hcwa_attachment_fields_to_edit( $form_fields, $post ) {
	if ( ! $post instanceof WP_Post || ! wp_attachment_is_image( $post->ID ) ) {
		return $form_fields;
	}

	$can_approve = current_user_can( 'edit_post', $post->ID ) && current_user_can( 'edit_others_posts' );
	$fields      = array(
		'hcwa_approved_for_web'   => array(
			'label' => __( 'Approved for web', 'hcwa' ),
			'help'  => __( 'HCWA has approved this photo for the website. Center, program and story pages show only approved photos.', 'hcwa' ),
		),
		'hcwa_identifiable_child' => array(
			'label' => __( 'Identifiable child', 'hcwa' ),
			'help'  => __( 'A child can be recognised (face, name, school or home). Never shown on Little Rose pages.', 'hcwa' ),
		),
	);

	foreach ( $fields as $key => $field ) {
		$name    = 'attachments[' . $post->ID . '][' . $key . ']';
		$id      = 'attachments-' . $post->ID . '-' . $key;
		$checked = (bool) get_post_meta( $post->ID, $key, true );

		if ( $can_approve ) {
			// The hidden 0 comes first so an unticked box still saves "no".
			$html = sprintf(
				'<input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1"%3$s>',
				esc_attr( $name ),
				esc_attr( $id ),
				checked( $checked, true, false )
			);
		} else {
			$html = '<span>' . ( $checked ? esc_html__( 'Yes', 'hcwa' ) : esc_html__( 'No', 'hcwa' ) ) . '</span> <span class="description">' . esc_html__( 'Only editors can change this.', 'hcwa' ) . '</span>';
		}

		$form_fields[ $key ] = array(
			'label' => esc_html( $field['label'] ),
			'input' => 'html',
			'html'  => $html,
			'helps' => esc_html( $field['help'] ),
		);
	}

	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'hcwa_attachment_fields_to_edit', 10, 2 );

/**
 * Saves the approval checkboxes (attachment screen and media modal).
 *
 * @param array $post       Attachment data.
 * @param array $attachment Submitted fields for this attachment.
 * @return array
 */
function hcwa_attachment_fields_to_save( $post, $attachment ) {
	$id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
	if ( ! $id || ! is_array( $attachment ) || ! current_user_can( 'edit_post', $id ) || ! current_user_can( 'edit_others_posts' ) ) {
		return $post;
	}

	foreach ( array( 'hcwa_approved_for_web', 'hcwa_identifiable_child' ) as $key ) {
		if ( array_key_exists( $key, $attachment ) && is_scalar( $attachment[ $key ] ) ) {
			update_post_meta( $id, $key, '1' === (string) $attachment[ $key ] );
		}
	}

	return $post;
}
add_filter( 'attachment_fields_to_save', 'hcwa_attachment_fields_to_save', 10, 2 );

/**
 * "Web approval" column in the Media library list view.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function hcwa_media_columns( $columns ) {
	$columns['hcwa_web'] = __( 'Web approval', 'hcwa' );
	return $columns;
}
add_filter( 'manage_media_columns', 'hcwa_media_columns' );

/**
 * Prints the "Web approval" cell.
 *
 * @param string $column  Column key.
 * @param int    $post_id Attachment ID.
 */
function hcwa_media_column_cell( $column, $post_id ): void {
	if ( 'hcwa_web' !== $column || ! wp_attachment_is_image( (int) $post_id ) ) {
		return;
	}

	$lines = array(
		get_post_meta( (int) $post_id, 'hcwa_approved_for_web', true ) ? __( 'Approved', 'hcwa' ) : __( 'Not approved', 'hcwa' ),
	);
	if ( get_post_meta( (int) $post_id, 'hcwa_identifiable_child', true ) ) {
		$lines[] = __( 'Identifiable child', 'hcwa' );
	}

	$refusals = hcwa_image_refusals_for( (int) $post_id );
	if ( $refusals ) {
		/* translators: %s: date */
		$lines[] = sprintf( __( 'Refused on the site (%s)', 'hcwa' ), wp_date( 'Y-m-d', (int) $refusals[0]['time'] ) );
	}

	echo esc_html( implode( ' · ', $lines ) );
}
add_action( 'manage_media_custom_column', 'hcwa_media_column_cell', 10, 2 );

/**
 * One notice on the Media library when the site recently refused photos.
 */
function hcwa_media_refusals_notice(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'upload' !== $screen->base || ! current_user_can( 'upload_files' ) ) {
		return;
	}

	$log = hcwa_image_recent_refusals();
	if ( ! $log ) {
		return;
	}

	$count = count( array_unique( array_map( static fn( $entry ) => (int) $entry['id'], $log ) ) );
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: %d: number of images */
				__( 'The website recently left out %d image(s) because they are not approved for web, or show an identifiable child on a page where that is not allowed. The "Web approval" column in list view marks them.', 'hcwa' ),
				$count
			)
		)
	);
}
add_action( 'admin_notices', 'hcwa_media_refusals_notice' );
