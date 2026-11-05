<?php
/**
 * Production refuses to publish draft text (contract gating rule 4).
 *
 * On the live site, saving a post as published or scheduled while its title, content,
 * excerpt or one of our text fields still contains a draft marker ([DRAFT: …] or a
 * placeholder token such as [N], [year], [năm]) puts it back to draft and explains why.
 * Covers the classic editor, quick edit, REST (block editor) and the block editor's
 * separate meta box save. Staging is not affected. `wp hcwa draft-scan` lists offenders.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder tokens used by the draft content (content/draft/*.json, patterns).
 *
 * @return string[]
 */
function hcwa_draft_tokens(): array {
	return array(
		'N',
		'year',
		'năm',
		'amount',
		'số tiền',
		'start',
		'end',
		'năm bắt đầu',
		'năm kết thúc',
		'age range',
		'độ tuổi',
		'ward',
		'phường',
		'date',
		'ngày',
		'month/year',
		'tháng/năm',
	);
}

/**
 * Whether text contains a draft marker: "[DRAFT" (any case), a placeholder token from
 * hcwa_draft_tokens(), or a bracketed note that mentions HCWA such as
 * "[Answer supplied by HCWA]". Shortcodes ([gallery ids="1,2"]) and footnotes ([1]) pass.
 *
 * @param string $s Text or HTML.
 */
function hcwa_contains_draft( string $s ): bool {
	if ( '' === $s ) {
		return false;
	}
	if ( false !== strpos( $s, '&#91;' ) || false !== strpos( $s, '&lbrack;' ) ) {
		$s = html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
	if ( false === strpos( $s, '[' ) ) {
		return false;
	}
	if ( class_exists( 'Normalizer' ) ) {
		$normalized = Normalizer::normalize( $s, Normalizer::FORM_C );
		$s          = false === $normalized ? $s : $normalized;
	}

	if ( preg_match( '/\[\s*DRAFT\b/i', $s ) ) {
		return true;
	}

	$tokens = implode( '|', array_map( static fn( $t ) => preg_quote( $t, '/' ), hcwa_draft_tokens() ) );
	if ( preg_match( '/\[\s*(?:' . $tokens . ')\s*\]/iu', $s ) ) {
		return true;
	}

	return (bool) preg_match( '/\[[^\[\]=]*\bHCWA\b[^\[\]=]*\]/u', $s );
}

/**
 * Post types the guard covers: public types, our admin-only types and synced patterns.
 *
 * @return string[]
 */
function hcwa_guarded_post_types(): array {
	$types = array_merge( array_values( get_post_types( array( 'public' => true ) ) ), hcwa_post_types(), array( 'wp_block' ) );
	return array_values( array_diff( array_unique( $types ), array( 'attachment' ) ) );
}

/**
 * Labels of the fields of a post that contain draft markers.
 *
 * @param array $fields label => text.
 * @return string[]
 */
function hcwa_draft_fields( array $fields ): array {
	$found = array();
	foreach ( $fields as $label => $text ) {
		if ( is_string( $text ) && hcwa_contains_draft( $text ) ) {
			$found[] = (string) $label;
		}
	}
	return $found;
}

/**
 * Request-level memory between the insert filter and the after-insert action, keyed by
 * post ID ('new' for a post that has no ID yet).
 *
 * @param int|string $key   Post ID or 'new'.
 * @param array|null $found Fields to remember; null reads and forgets the entry.
 * @return string[]
 */
function hcwa_publish_guard_pending( $key, ?array $found = null ): array {
	static $pending = array();

	if ( null !== $found ) {
		if ( $found ) {
			$pending[ $key ] = $found;
		} else {
			unset( $pending[ $key ] );
		}
		return $found;
	}

	$out = $pending[ $key ] ?? array();
	unset( $pending[ $key ] );
	return $out;
}

/**
 * wp_insert_post_data: switches publish/future to draft when title, content or excerpt
 * contain draft markers.
 *
 * @param array $data    Slashed, sanitized post data.
 * @param array $postarr Slashed post array.
 * @return array
 */
function hcwa_publish_guard_data( $data, $postarr ) {
	if ( ! hcwa_is_production() || ! is_array( $data ) || ! in_array( $data['post_type'] ?? '', hcwa_guarded_post_types(), true ) ) {
		return $data;
	}

	$key = ! empty( $postarr['ID'] ) ? (int) $postarr['ID'] : 'new';

	if ( ! in_array( $data['post_status'] ?? '', array( 'publish', 'future' ), true ) ) {
		hcwa_publish_guard_pending( $key, array() );
		return $data;
	}

	$found = hcwa_draft_fields(
		array(
			__( 'Title', 'hcwa' )   => wp_unslash( (string) ( $data['post_title'] ?? '' ) ),
			__( 'Content', 'hcwa' ) => wp_unslash( (string) ( $data['post_content'] ?? '' ) ),
			__( 'Excerpt', 'hcwa' ) => wp_unslash( (string) ( $data['post_excerpt'] ?? '' ) ),
		)
	);

	if ( $found ) {
		$data['post_status'] = 'draft';
	}
	hcwa_publish_guard_pending( $key, $found );

	return $data;
}
add_filter( 'wp_insert_post_data', 'hcwa_publish_guard_data', 99, 2 );

/**
 * wp_after_insert_post (fires after REST meta and after meta box saves): also checks our
 * registered text fields, reverts to draft if needed and records the reason.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 * @param bool    $update  Whether an existing post was updated.
 */
function hcwa_publish_guard_after( $post_id, $post, $update = true ): void {
	static $running = false;

	if ( $running || ! hcwa_is_production() || ! $post instanceof WP_Post || ! in_array( $post->post_type, hcwa_guarded_post_types(), true ) ) {
		return;
	}

	$found = hcwa_publish_guard_pending( (int) $post_id );
	if ( ! $update ) {
		$found = array_merge( $found, hcwa_publish_guard_pending( 'new' ) );
	}

	$live = in_array( $post->post_status, array( 'publish', 'future' ), true );

	if ( $live ) {
		$texts = array();
		foreach ( get_registered_meta_keys( 'post', $post->post_type ) as $key => $args ) {
			if ( 0 === strpos( $key, 'hcwa_' ) && 'string' === ( $args['type'] ?? '' ) ) {
				$texts[ (string) ( $args['description'] ?? $key ) ] = (string) get_post_meta( $post->ID, $key, true );
			}
		}
		$found = array_merge( $found, hcwa_draft_fields( $texts ) );

		if ( $found ) {
			$running = true;
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'draft',
				)
			);
			$running = false;
		}
	}

	if ( $found ) {
		update_post_meta( $post->ID, '_hcwa_publish_blocked', implode( ', ', array_unique( $found ) ) );
		set_transient( 'hcwa_publish_blocked_' . get_current_user_id(), (int) $post->ID, 5 * MINUTE_IN_SECONDS );
	} elseif ( $live ) {
		delete_post_meta( $post->ID, '_hcwa_publish_blocked' );
	}
}
add_action( 'wp_after_insert_post', 'hcwa_publish_guard_after', 20, 3 );

/**
 * The explanation shown to editors.
 *
 * @param string $fields Comma-separated field names.
 */
function hcwa_publish_guard_message( string $fields ): string {
	return sprintf(
		/* translators: %s: list of fields */
		__( 'Not published. This is the live site, and the text still contains draft markers such as [DRAFT: …], [N] or [year] (in: %s). The post was saved as a draft. Replace the markers with approved text, then publish again.', 'hcwa' ),
		$fields
	);
}

/**
 * Admin notice after a blocked save (classic editor, quick edit after reload, lists).
 */
function hcwa_publish_guard_notice(): void {
	if ( ! hcwa_is_production() ) {
		return;
	}

	$post_id = 0;
	$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( $screen && 'post' === $screen->base && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		$post_id = (int) get_transient( 'hcwa_publish_blocked_' . get_current_user_id() );
		delete_transient( 'hcwa_publish_blocked_' . get_current_user_id() );
	}

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = (string) get_post_meta( $post_id, '_hcwa_publish_blocked', true );
	if ( '' === $fields || 'draft' !== get_post_status( $post_id ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html( get_the_title( $post_id ) ),
		esc_html( hcwa_publish_guard_message( $fields ) )
	);
}
add_action( 'admin_notices', 'hcwa_publish_guard_notice' );

/**
 * Exposes the reason to the block editor (edit context only).
 */
function hcwa_publish_guard_rest_field(): void {
	register_rest_field(
		hcwa_guarded_post_types(),
		'hcwa_publish_blocked',
		array(
			'get_callback' => static function ( $item ) {
				$id = (int) ( $item['id'] ?? 0 );
				return $id && current_user_can( 'edit_post', $id ) ? (string) get_post_meta( $id, '_hcwa_publish_blocked', true ) : '';
			},
			'schema'       => array(
				'description' => __( 'Fields that kept this post from being published on the live site.', 'hcwa' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			),
		)
	);
}
add_action( 'rest_api_init', 'hcwa_publish_guard_rest_field' );

/**
 * Block editor: after each save, asks the REST API whether the guard stepped in and
 * shows a warning (assets/editor-guard.js). Production only.
 */
function hcwa_publish_guard_editor_assets(): void {
	if ( ! hcwa_is_production() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	$rest_bases = array();
	foreach ( hcwa_guarded_post_types() as $post_type ) {
		$object = get_post_type_object( $post_type );
		if ( $object && $object->show_in_rest ) {
			$rest_bases[ $post_type ] = $object->rest_base ? $object->rest_base : $post_type;
		}
	}

	wp_enqueue_script( 'hcwa-editor-guard', HCWA_CORE_URL . 'assets/editor-guard.js', array( 'wp-data', 'wp-notices', 'wp-api-fetch', 'wp-editor' ), HCWA_CORE_VERSION, true );
	wp_add_inline_script(
		'hcwa-editor-guard',
		'window.hcwaPublishGuard = ' . wp_json_encode(
			array(
				'restBases' => $rest_bases,
				'message'   => hcwa_publish_guard_message( '%s' ),
				'reload'    => __( 'Reload the editor', 'hcwa' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'hcwa_publish_guard_editor_assets' );
