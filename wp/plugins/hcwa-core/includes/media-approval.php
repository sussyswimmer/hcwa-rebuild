<?php
/**
 * Photo approval (CLAUDE.md child safeguarding, contract gating rule 2).
 *
 * Two flags on every image: "Approved for web" and "Identifiable child". hcwa_image()
 * is the gated way to print a photo: it refuses anything not approved, and in a
 * no-identifiable context (Little Rose) also refuses identifiable children. A refusal
 * prints nothing and is listed in the Media library for staff.
 *
 * The other ways WordPress prints photos are gated here too: images placed in content
 * (blocks, classic HTML, [gallery], captions, cover backgrounds, images inserted by URL),
 * featured images printed by core blocks, and embeds (switched off). The REST media
 * routes are closed to the public in post-types.php.
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
 * approved only if every translation of it is, and identifiable if any translation says so
 * ("Identifiable child" is a protective flag, never copied between translations).
 *
 * @param int $attachment_id Attachment ID.
 * @return array{approved_for_web: bool, identifiable_child: bool, exists: bool}
 */
function hcwa_image_flags( int $attachment_id ): array {
	$ids = hcwa_post_translation_ids( $attachment_id );

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
 * - a post ID or WP_Post: a center with "No identifiable children" (in any language), a
 *   program run by such a center, or a news post that carries, in any of its language
 *   versions, such a center's news tag;
 * - a bool, or an array with 'no_identifiable_children' => bool, to say it directly.
 *
 * Translations are read too: Polylang drops a tag that has no linked translation when it
 * creates the English version of a post, and the flag is never copied between versions.
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
		$result = hcwa_meta_flag_any( $post->ID, 'hcwa_no_identifiable_children' );
	} elseif ( 'hcwa_program' === $post->post_type ) {
		foreach ( hcwa_post_translation_ids( $post->ID ) as $program_id ) {
			$center = (int) get_post_meta( $program_id, 'hcwa_center_id', true );
			if ( $center && hcwa_meta_flag_any( $center, 'hcwa_no_identifiable_children' ) ) {
				$result = true;
				break;
			}
		}
	} elseif ( 'post' === $post->post_type ) {
		$result = hcwa_post_has_protected_tag( $post, hcwa_no_identifiable_news_tags() );
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
		foreach ( hcwa_post_translation_ids( (int) $center_id ) as $id ) {
			$slug = (string) get_post_meta( $id, 'hcwa_news_tag', true );
			if ( '' !== $slug ) {
				$tags[] = $slug;
			}
		}
	}
	return $tags = array_values( array_unique( $tags ) );
}

/**
 * Whether a news post, or any of its translations, carries one of the protected news tags
 * (Vietnamese slugs; English tags are mapped to their Vietnamese translation).
 *
 * @param WP_Post  $post           News post.
 * @param string[] $protected_tags hcwa_no_identifiable_news_tags().
 */
function hcwa_post_has_protected_tag( WP_Post $post, array $protected_tags ): bool {
	if ( ! $protected_tags ) {
		return false;
	}
	foreach ( hcwa_post_translation_ids( $post->ID ) as $id ) {
		$terms = get_the_terms( $id, 'post_tag' ); // Cached with the post; wp_get_post_terms() always queries.
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( $term instanceof WP_Term && in_array( hcwa_original_term_slug( $term ), $protected_tags, true ) ) {
				return true;
			}
		}
	}
	return false;
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
 * Pure decision for one image placed in content: in a gated context a media library image
 * needs hcwa_image_allowed(), and an image that cannot be checked is refused.
 *
 * @param int  $attachment_id Library ID (from the block, the wp-image-N class or the URL);
 *                            0 = not a library photo (printed by our own gated code, or a
 *                            theme or plugin file); -1 = cannot be checked (another site,
 *                            an upload that matches no attachment).
 * @param bool $gated         hcwa_content_images_gated() for the post.
 * @param bool $allowed       hcwa_image_allowed() for the image in that post (ignored when not needed).
 */
function hcwa_content_image_decision( int $attachment_id, bool $gated, bool $allowed ): bool {
	return 0 === $attachment_id || ! $gated || ( $attachment_id > 0 && $allowed );
}

/**
 * Library ID of an image placed in content by URL (Insert from URL, classic HTML without
 * the wp-image-N class), for hcwa_content_image_decision():
 * - 0 when it was printed by code in this request through wp_get_attachment_image()
 *   (hcwa_image() output, partner logos, the VietQR image: each gated where it is printed),
 *   or when it is a file of the site itself outside the uploads folder (theme, plugin);
 * - the attachment ID when the URL is one of our uploads (sizes and -scaled included);
 * - -1 otherwise: another site, a data: URI, or an upload that matches no attachment.
 *
 * @param string $src Image URL as printed (HTML entities allowed).
 */
function hcwa_content_url_attachment_id( string $src ): int {
	$src = trim( html_entity_decode( $src, ENT_QUOTES, 'UTF-8' ) );
	if ( '' === $src ) {
		return 0;
	}
	if ( isset( hcwa_printed_image_srcs()[ $src ] ) ) {
		return 0;
	}

	$url  = wp_parse_url( $src );
	$home = wp_parse_url( (string) get_option( 'home' ) );
	if ( ! is_array( $url ) || ( isset( $url['scheme'] ) && ! in_array( strtolower( $url['scheme'] ), array( 'http', 'https' ), true ) ) ) {
		return -1;
	}
	if ( isset( $url['host'] ) && strtolower( $url['host'] ) !== strtolower( (string) ( $home['host'] ?? '' ) ) ) {
		return -1;
	}

	$baseurl = untrailingslashit( (string) ( wp_get_upload_dir()['baseurl'] ?? '' ) );
	$uploads = (string) wp_parse_url( $baseurl, PHP_URL_PATH );
	$path    = (string) ( $url['path'] ?? '' );
	if ( '' === $uploads || 0 !== strpos( $path, $uploads . '/' ) ) {
		return 0;
	}

	$file       = $baseurl . substr( $path, strlen( $uploads ) );
	$bare       = (string) preg_replace( '/-\d+x\d+(?=\.\w+$)/', '', $file );
	$candidates = array( $file, $bare, (string) preg_replace( '/(\.\w+)$/', '-scaled$1', $bare ) );
	// A WebP size whose attachment still has a JPEG main file (sizes regenerated after
	// hcwa_image_output_format() was added, without converting the main file).
	if ( preg_match( '/\.webp$/i', $bare ) ) {
		foreach ( array( 'jpg', 'jpeg' ) as $ext ) {
			$candidates[] = (string) preg_replace( '/\.webp$/i', '.' . $ext, $bare );
		}
	}
	foreach ( array_unique( $candidates ) as $candidate ) {
		$id = (int) attachment_url_to_postid( $candidate );
		if ( $id > 0 ) {
			return $id;
		}
	}
	return -1;
}

/**
 * URLs printed through wp_get_attachment_image() in this request (see
 * hcwa_content_url_attachment_id()). Code paths that print them gate them themselves.
 *
 * @param string $src URL to remember, or '' to only read.
 * @return array<string, true>
 */
function hcwa_printed_image_srcs( string $src = '' ): array {
	static $srcs = array();
	if ( '' !== $src ) {
		$srcs[ $src ] = true;
	}
	return $srcs;
}

/**
 * wp_get_attachment_image_attributes: remembers the printed URL.
 *
 * @param array $attr Image attributes.
 * @return array
 */
function hcwa_note_printed_image( $attr ) {
	if ( is_array( $attr ) && ! empty( $attr['src'] ) && is_string( $attr['src'] ) ) {
		hcwa_printed_image_srcs( $attr['src'] );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'hcwa_note_printed_image', 99 );

/**
 * Whether a block's own image (library ID and/or URL) may show in the post being rendered.
 * Refusals of images that are not in the library are logged too.
 *
 * @param int          $id   Library ID from the block attributes, or 0.
 * @param string       $url  Image URL from the block attributes, or ''.
 * @param WP_Post|null $post Post being rendered.
 */
function hcwa_content_block_image_ok( int $id, string $url, $post ): bool {
	$gated = hcwa_content_images_gated( $post );
	if ( ! $gated ) {
		return true;
	}
	if ( $id <= 0 ) {
		$id = hcwa_content_url_attachment_id( $url );
	}
	$ok = hcwa_content_image_decision( $id, $gated, $id > 0 && hcwa_image_allowed( $id, $post ) );
	if ( ! $ok && $id < 0 ) {
		hcwa_image_log_refusal( 0, 'not_in_library', $post instanceof WP_Post ? $post->ID : 0 );
	}
	return $ok;
}

/**
 * Post a block renders for: the block context's postId (Query Loop), else the current post.
 *
 * @param WP_Block|null $instance Block instance.
 * @return WP_Post|null
 */
function hcwa_block_context_post( $instance ) {
	return ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) ? get_post( (int) $instance->context['postId'] ) : get_post();
}

/**
 * core/image blocks: the whole figure (link and caption included) goes when the photo is
 * refused. An image inserted by URL (Insert from URL in a pattern slot) has no library ID,
 * so in a gated context it must be one of our approved uploads.
 *
 * @param string        $block_content Rendered block.
 * @param array         $block         Parsed block.
 * @param WP_Block|null $instance      Block instance.
 * @return string
 */
function hcwa_gate_image_block( $block_content, $block, $instance = null ) {
	$id  = (int) ( $block['attrs']['id'] ?? 0 );
	$url = (string) ( $block['attrs']['url'] ?? '' );
	if ( '' === trim( (string) $block_content ) ) {
		return $block_content;
	}
	if ( $id <= 0 && '' === $url && preg_match( '/<img\b[^>]*\bsrc=(["\'])(.*?)\1/i', (string) $block_content, $m ) ) {
		$url = $m[2];
	}
	if ( $id <= 0 && '' === $url ) {
		return $block_content;
	}
	return hcwa_content_block_image_ok( $id, $url, hcwa_block_context_post( $instance ) ) ? $block_content : '';
}
add_filter( 'render_block_core/image', 'hcwa_gate_image_block', 10, 3 );

/**
 * core/cover: a fixed or repeated background is a div with an inline background-image, which
 * no img filter sees; the featured image variant is checked always (as hcwa_image() does for
 * featured images). The background goes, the cover's text stays.
 *
 * @param string        $block_content Rendered block.
 * @param array         $block         Parsed block.
 * @param WP_Block|null $instance      Block instance.
 * @return string
 */
function hcwa_gate_cover_block( $block_content, $block, $instance = null ) {
	$attrs = (array) ( $block['attrs'] ?? array() );
	if ( '' === trim( (string) $block_content ) || ( isset( $attrs['backgroundType'] ) && 'image' !== $attrs['backgroundType'] ) ) {
		return $block_content;
	}

	$post = hcwa_block_context_post( $instance );
	if ( ! empty( $attrs['useFeaturedImage'] ) ) {
		$thumbnail = $post instanceof WP_Post ? (int) get_post_thumbnail_id( $post ) : 0;
		$ok        = $thumbnail <= 0 || hcwa_image_allowed( $thumbnail, $post );
	} else {
		$id  = (int) ( $attrs['id'] ?? 0 );
		$url = (string) ( $attrs['url'] ?? '' );
		$ok  = ( $id <= 0 && '' === $url ) || hcwa_content_block_image_ok( $id, $url, $post );
	}

	if ( $ok ) {
		return $block_content;
	}
	return (string) preg_replace(
		array( '#<img\b[^>]*\bwp-block-cover__image-background\b[^>]*>#i', '#<div\b[^>]*\bwp-block-cover__image-background\b[^>]*>\s*</div>#i' ),
		'',
		(string) $block_content
	);
}
add_filter( 'render_block_core/cover', 'hcwa_gate_cover_block', 10, 3 );

/**
 * core/media-text with "Use featured image": checked always, like other featured images.
 * Its library and URL images are img tags in content (hcwa_gate_content_img_tag()).
 *
 * @param string        $block_content Rendered block.
 * @param array         $block         Parsed block.
 * @param WP_Block|null $instance      Block instance.
 * @return string
 */
function hcwa_gate_media_text_block( $block_content, $block, $instance = null ) {
	if ( empty( $block['attrs']['useFeaturedImage'] ) ) {
		return $block_content;
	}
	$post      = hcwa_block_context_post( $instance );
	$thumbnail = $post instanceof WP_Post ? (int) get_post_thumbnail_id( $post ) : 0;
	if ( $thumbnail <= 0 || hcwa_image_allowed( $thumbnail, $post ) ) {
		return $block_content;
	}
	return (string) preg_replace( '#<img\b[^>]*\bwp-image-' . $thumbnail . '\b[^>]*>#i', '', (string) $block_content );
}
add_filter( 'render_block_core/media-text', 'hcwa_gate_media_text_block', 10, 3 );

/**
 * Featured images printed by any code through get_the_post_thumbnail() (core/post-featured-image
 * in a Query Loop, core/latest-posts, cover with "Use featured image"): the same check the
 * theme's templates make with hcwa_image(). A partner's featured image is its logo, which
 * needs the partner's permission instead (rule 5). Admin screens are left alone.
 *
 * @param string $html         Thumbnail HTML.
 * @param int    $post_id      Post ID.
 * @param int    $thumbnail_id Attachment ID.
 * @return string
 */
function hcwa_gate_post_thumbnail_html( $html, $post_id = 0, $thumbnail_id = 0 ) {
	if ( '' === (string) $html || is_admin() ) {
		return $html;
	}
	if ( 'hcwa_partner' === get_post_type( (int) $post_id ) ) {
		return hcwa_partner_logo_allowed( (int) $post_id ) ? $html : '';
	}
	return hcwa_image_allowed( (int) $thumbnail_id, (int) $post_id ) ? $html : '';
}
add_filter( 'post_thumbnail_html', 'hcwa_gate_post_thumbnail_html', 10, 3 );

/**
 * Marker left where hcwa_gate_content_img_tag() removed an image, so the next pass can also
 * remove the link around it (to the full file) and a classic caption (often a name).
 */
function hcwa_refused_marker(): string {
	return '<!--hcwa-refused-image-->';
}

/**
 * Every other image in content (classic HTML, cover and media-text images, classic galleries
 * and captions): the img tag goes when refused. WordPress finds the library ID in the
 * wp-image-N class; without one, the URL decides (hcwa_content_url_attachment_id()).
 *
 * @param string $image         img tag.
 * @param string $context       'the_content', 'the_excerpt', ...
 * @param int    $attachment_id Library ID or 0.
 * @return string
 */
function hcwa_gate_content_img_tag( $image, $context, $attachment_id ) {
	if ( ! in_array( $context, array( 'the_content', 'the_excerpt' ), true ) ) {
		return $image;
	}
	$post  = get_post();
	$gated = hcwa_content_images_gated( $post );
	if ( ! $gated ) {
		return $image;
	}

	$id = (int) $attachment_id;
	if ( $id <= 0 ) {
		$id = preg_match( '/\bsrc=(["\'])(.*?)\1/i', (string) $image, $m ) ? hcwa_content_url_attachment_id( $m[2] ) : 0;
	}
	if ( hcwa_content_image_decision( $id, $gated, $id > 0 && hcwa_image_allowed( $id, $post ) ) ) {
		return $image;
	}
	if ( $id < 0 ) {
		hcwa_image_log_refusal( 0, 'not_in_library', $post instanceof WP_Post ? $post->ID : 0 );
	}
	return hcwa_refused_marker();
}
add_filter( 'wp_content_img_tag', 'hcwa_gate_content_img_tag', 10, 3 );

/**
 * After the img pass (wp_filter_content_tags, priority 12): removes a link that only held a
 * refused image and a classic caption ([caption] output) around one, then the markers.
 *
 * @param string $content Content.
 * @return string
 */
function hcwa_content_strip_refused( $content ) {
	$marker = hcwa_refused_marker();
	if ( ! is_string( $content ) || false === strpos( $content, $marker ) ) {
		return $content;
	}
	$m       = preg_quote( $marker, '#' );
	$content = (string) preg_replace( '#<a\b[^>]*>\s*' . $m . '\s*</a>#i', $marker, $content );
	$content = (string) preg_replace( '#<(figure|div)\b[^>]*\bclass=("|\')[^"\']*\bwp-caption\b[^"\']*\2[^>]*>(?:(?!</\1>).)*?' . $m . '(?:(?!</\1>).)*</\1>#is', '', $content );
	return str_replace( $marker, '', $content );
}
add_filter( 'the_content', 'hcwa_content_strip_refused', 13 );
add_filter( 'the_excerpt', 'hcwa_content_strip_refused', 13 );

/**
 * Classic [gallery] shortcodes print through wp_get_attachment_image() with no wp-image-N
 * class, so the img pass cannot see their IDs. In a gated context only allowed images are
 * passed to WordPress's own gallery; none allowed prints nothing.
 *
 * @param string $output   Gallery output so far ('' = WordPress builds it).
 * @param array  $attr     Shortcode attributes.
 * @param int    $instance Gallery instance number.
 * @return string
 */
function hcwa_gate_gallery_shortcode( $output, $attr, $instance = 0 ) {
	static $rendering = false;

	$post = get_post();
	if ( '' !== (string) $output || $rendering || ! hcwa_content_images_gated( $post ) ) {
		return $output;
	}

	$attr    = is_array( $attr ) ? $attr : array();
	$ids     = hcwa_gallery_ids( $attr, $post );
	$allowed = array_values( array_filter( $ids, static fn( $id ) => hcwa_image_allowed( (int) $id, $post ) ) );
	if ( ! $allowed ) {
		return hcwa_refused_marker();
	}
	if ( $allowed === $ids ) {
		return $output;
	}

	if ( empty( $attr['ids'] ) && empty( $attr['include'] ) && empty( $attr['orderby'] ) ) {
		$attr['orderby'] = 'menu_order ID'; // Keep the order of attached images.
	}
	$attr['ids'] = implode( ',', $allowed );
	unset( $attr['include'], $attr['exclude'] );

	$rendering = true;
	$html      = gallery_shortcode( $attr );
	$rendering = false;

	return '' !== $html ? $html : hcwa_refused_marker();
}
add_filter( 'post_gallery', 'hcwa_gate_gallery_shortcode', 10, 3 );

/**
 * Image IDs a [gallery] shortcode would show: its ids or include list, else the images
 * attached to its post (minus exclude), in WordPress's order.
 *
 * @param array        $attr Shortcode attributes.
 * @param WP_Post|null $post Current post.
 * @return int[]
 */
function hcwa_gallery_ids( array $attr, $post ): array {
	$list = ! empty( $attr['ids'] ) ? $attr['ids'] : ( $attr['include'] ?? '' );
	if ( '' !== $list && array() !== $list ) {
		return hcwa_id_list( $list );
	}

	$parent = isset( $attr['id'] ) ? (int) $attr['id'] : ( $post instanceof WP_Post ? $post->ID : 0 );
	if ( $parent <= 0 ) {
		return array();
	}
	$children = get_children(
		array(
			'post_parent'    => $parent,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'orderby'        => 'menu_order ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	return array_values( array_diff( hcwa_id_list( array_values( (array) $children ) ), hcwa_id_list( $attr['exclude'] ?? '' ) ) );
}

/**
 * Warms the post and meta caches for the library images a post's content uses, so the gate
 * above does not load them one by one (do_blocks runs at priority 9, before core's own
 * priming in wp_filter_content_tags at 12).
 *
 * @param string $content Content.
 * @return string
 */
function hcwa_prime_content_images( $content ) {
	if ( is_string( $content ) && function_exists( '_prime_post_caches' ) && preg_match_all( '/(?:\bwp-image-|"id":)(\d+)/', $content, $m ) ) {
		$ids = array_values( array_unique( array_map( 'intval', $m[1] ) ) );
		if ( count( $ids ) > 1 ) {
			_prime_post_caches( $ids, true, true );
		}
	}
	return $content;
}
add_filter( 'the_content', 'hcwa_prime_content_images', 8 );

/**
 * Embeds are switched off. The embed view (<permalink>/embed/, ?embed=true) prints the
 * featured image, or an attachment's own image, with wp_get_attachment_image() past the
 * approval gate (wp-includes/theme-compat/embed-content.php), and the oEmbed endpoint
 * returns its thumbnail_url (wp-includes/embed.php get_oembed_response_data_rich()). The
 * site removes oEmbed discovery and never needs either. set_404() keeps is_embed
 * (class-wp-query.php init_query_flags()), and template-loader.php tries is_embed first,
 * so it is cleared too.
 */
function hcwa_disable_embed_view(): void {
	if ( ! is_embed() ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	$wp_query->is_embed = false;
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'hcwa_disable_embed_view', 0 );

// /wp-json/oembed/1.0/embed and local URLs pasted in the editor find no post. The
// /oembed/1.0/proxy route for YouTube and other sites keeps working.
add_filter( 'oembed_request_post_id', '__return_zero' );

/**
 * JPEG uploads are served as WebP (05-DESIGN "Formats", 09-QA images). WordPress converts
 * the main file and every size, keeps the uploaded JPEG as the attachment's original_image
 * (wp-admin/includes/image.php:297-345, _wp_image_meta_replace_original()), and applies
 * the mapping only when the server's image editor can write WebP, otherwise keeping JPEG
 * (wp-includes/class-wp-image-editor.php:370-377). WordPress prints a plain <img>, so no
 * JPEG fallback is served: 05-DESIGN's "JPEG fallback" is covered only by every current
 * browser reading WebP (CONTRACT Status records this). PNG (logos, illustrations, the
 * VietQR code) and the HEIC-to-JPEG default are left alone. AVIF is not used for the same
 * reason (no <picture>). Media already in the library stays JPEG until `wp media
 * regenerate` (Phase 3 migration). Removing this filter restores JPEG for new uploads.
 *
 * @param array<string, string> $formats   Source mime type => output mime type.
 * @param string                $filename  Path to the image (unused).
 * @param string                $mime_type Source mime type (unused).
 * @return array<string, string>
 */
function hcwa_image_output_format( $formats, $filename = '', $mime_type = '' ) {
	$formats = is_array( $formats ) ? $formats : array();
	if ( ! isset( $formats['image/jpeg'] ) ) {
		$formats['image/jpeg'] = 'image/webp';
	}
	return $formats;
}
add_filter( 'image_editor_output_format', 'hcwa_image_output_format', 10, 3 );

/**
 * Library IDs a center shows in one language version: featured image, logo and gallery.
 *
 * @param int $center_id Center ID.
 * @return int[]
 */
function hcwa_center_media_ids( int $center_id ): array {
	return hcwa_id_list(
		array_merge(
			array( get_post_meta( $center_id, '_thumbnail_id', true ), get_post_meta( $center_id, 'hcwa_logo_id', true ) ),
			hcwa_id_list( get_post_meta( $center_id, 'hcwa_gallery_ids', true ) )
		)
	);
}

/**
 * Whether a photo seen on its own (its attachment page, outside any post) is in a
 * no-identifiable context. The parent alone is not enough: photos are picked from the
 * library, so a Little Rose gallery photo can be unattached or attached to another post.
 * It counts when its parent is such a context, when a center marked "No identifiable
 * children" (in any language) shows it as featured image, logo or gallery photo, or when
 * it is the featured image of a post, center or program that is such a context. The
 * photo's Polylang media translations count too (shared meta stores the VI original's ID).
 *
 * @param int $attachment_id Attachment ID.
 */
function hcwa_attachment_no_identifiable( int $attachment_id ): bool {
	$attachment = get_post( $attachment_id );
	if ( ! $attachment instanceof WP_Post ) {
		return false;
	}
	$parent = (int) $attachment->post_parent;
	if ( $parent > 0 && hcwa_image_context_no_identifiable( $parent ) ) {
		return true;
	}

	$ids   = hcwa_post_translation_ids( $attachment_id );
	$query = array(
		'post_status'      => 'any',
		'numberposts'      => 50,
		'fields'           => 'ids',
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$query['lang'] = ''; // Every language.
	}

	$centers = get_posts(
		$query + array(
			'post_type'  => 'hcwa_center',
			'meta_key'   => 'hcwa_no_identifiable_children', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $centers as $center_id ) {
		foreach ( hcwa_post_translation_ids( (int) $center_id ) as $version ) {
			if ( array_intersect( $ids, hcwa_center_media_ids( $version ) ) ) {
				return true;
			}
		}
	}

	$users = get_posts(
		$query + array(
			'post_type'  => array( 'post', 'hcwa_center', 'hcwa_program' ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'value'   => array_map( 'strval', $ids ),
					'compare' => 'IN',
				),
			),
		)
	);
	foreach ( $users as $user_id ) {
		if ( hcwa_image_context_no_identifiable( (int) $user_id ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether a photo's attachment page must not exist: the approval gate refuses the photo in
 * its own context (hcwa_attachment_no_identifiable()). Other files (report PDFs) keep
 * their pages.
 *
 * @param int $attachment_id Attachment ID.
 */
function hcwa_attachment_page_refused( int $attachment_id ): bool {
	if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}
	return ! hcwa_image_allowed( $attachment_id, hcwa_attachment_no_identifiable( $attachment_id ) );
}

/**
 * The attachment page of a refused photo is a plain 404, whatever the theme: the default
 * attachment template prints the image, its title and caption (which often carry a
 * child's name) with wp_get_attachment_image(), past the gate. Same steps as core's
 * WP::handle_404(). Priority 5: before redirect_canonical (default-filters.php:687,
 * priority 10), which would send the page to the raw file when attachment pages are off
 * (canonical.php:551-552). hcwa-2026 does the same (inc/setup.php); whichever runs second
 * finds is_attachment() false after set_404() and does nothing.
 */
function hcwa_refuse_attachment_page(): void {
	if ( ! is_attachment() || ! hcwa_attachment_page_refused( (int) get_queried_object_id() ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'hcwa_refuse_attachment_page', 5 );

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
 * Remembers a refusal so the Media library can show it: only when the viewer is staff who
 * can act on it (editing, previewing or browsing while logged in) or WP-CLI, never for
 * anonymous visitors, so a crawler cannot make the site write to the database. Entries
 * are collected and written once per request (hcwa_image_flush_refusals()). Also logged
 * on non-production.
 *
 * @param int    $attachment_id Attachment ID (0 for an image that is not in the library).
 * @param string $reason        not_approved | identifiable_child | not_in_library.
 * @param int    $context_id    Context post ID or 0.
 */
function hcwa_image_log_refusal( int $attachment_id, string $reason, int $context_id ): void {
	if ( ! current_user_can( 'edit_posts' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	hcwa_image_refusal_queue(
		array(
			'id'      => $attachment_id,
			'reason'  => $reason,
			'context' => $context_id,
			'time'    => time(),
		)
	);

	if ( ! hcwa_is_production() ) {
		hcwa_log( sprintf( 'Image %d refused (%s) in context %d.', $attachment_id, $reason, $context_id ) );
	}
}

/**
 * Refusals collected in this request.
 *
 * @param array|null $entry Entry to add, or null to take (and clear) the queue.
 * @return array<string, array>
 */
function hcwa_image_refusal_queue( ?array $entry = null ): array {
	static $queue = array();
	if ( null === $entry ) {
		$out   = $queue;
		$queue = array();
		return $out;
	}
	$queue[ $entry['id'] . ':' . $entry['reason'] ] = $entry;
	return $queue;
}

/**
 * Writes this request's refusals, at most once a day per image and reason: one option
 * write per request at most, keeping the 50 newest entries plus every one added now.
 */
function hcwa_image_flush_refusals(): void {
	$new = hcwa_image_refusal_queue();
	if ( ! $new ) {
		return;
	}

	$log   = get_option( 'hcwa_image_refusals', array() );
	$log   = is_array( $log ) ? $log : array();
	$added = array();
	foreach ( $new as $key => $entry ) {
		if ( isset( $log[ $key ]['time'] ) && ( $entry['time'] - (int) $log[ $key ]['time'] ) < DAY_IN_SECONDS ) {
			continue;
		}
		$added[ $key ] = $entry;
	}
	if ( ! $added ) {
		return;
	}

	$rest = array_diff_key( $log, $added );
	uasort( $rest, static fn( $a, $b ) => (int) ( $b['time'] ?? 0 ) <=> (int) ( $a['time'] ?? 0 ) );
	update_option( 'hcwa_image_refusals', $added + array_slice( $rest, 0, max( 0, 50 - count( $added ) ), true ), false );
}
add_action( 'shutdown', 'hcwa_image_flush_refusals' );

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
			'help'  => __( 'HCWA has approved this image for the website. Pages, centers, programs, reports, stories and Little Rose news show only approved images, icons and illustrations included.', 'hcwa' ),
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

/**
 * Block editor: on a post whose content images are gated, warn while editing about images
 * the live site will leave out (assets/editor-images.js), instead of letting them vanish.
 */
function hcwa_image_approval_editor_assets(): void {
	$post = get_post();
	if ( ! $post instanceof WP_Post || ! hcwa_content_images_gated( $post ) ) {
		return;
	}

	wp_enqueue_script( 'hcwa-editor-images', HCWA_CORE_URL . 'assets/editor-images.js', array( 'wp-data', 'wp-notices', 'wp-core-data', 'wp-block-editor' ), HCWA_CORE_VERSION, true );
	wp_add_inline_script(
		'hcwa-editor-images',
		'window.hcwaImageApproval = ' . wp_json_encode(
			array(
				'noIdentifiable' => hcwa_image_context_no_identifiable( $post ),
				/* translators: %s: image titles */
				'notApproved'    => __( 'Not approved for web, so the live site leaves them out: %s. An editor can approve them in the Media Library.', 'hcwa' ),
				/* translators: %s: image titles */
				'identifiable'   => __( 'Marked "Identifiable child", which this page does not allow, so the live site leaves them out: %s.', 'hcwa' ),
				/* translators: %d: number of images */
				'fromUrl'        => __( 'Images inserted from a URL do not appear on the live site here (%d). Choose them from the Media Library instead.', 'hcwa' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'hcwa_image_approval_editor_assets' );
