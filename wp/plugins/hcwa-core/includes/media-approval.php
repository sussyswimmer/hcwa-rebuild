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
 * featured images printed by core blocks or read by ID on the front end, attachment pages
 * and embeds (switched off). Yoast SEO's share images, schema and image sitemaps are gated
 * in seo.php, camera metadata (GPS) is removed from the files in media-privacy.php, and
 * the REST media routes are closed to the public in post-types.php.
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
 *   program run by such a center, or a news post that is marked "No identifiable children"
 *   itself (in any language; `wp hcwa protect-little-rose` marks the imported Little Rose
 *   posts) or carries, in any of its language versions, a protected news tag
 *   (hcwa_no_identifiable_news_tags());
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
		$result = hcwa_center_no_identifiable( $post->ID );
	} elseif ( 'hcwa_program' === $post->post_type ) {
		// Every center the program runs at (hcwa_center_ids, else the old hcwa_center_id), in
		// any language and whatever its status: one protected center makes it protected.
		foreach ( hcwa_post_translation_ids( $post->ID ) as $program_id ) {
			$centers   = hcwa_id_list( get_post_meta( $program_id, 'hcwa_center_ids', true ) );
			$centers[] = (int) get_post_meta( $program_id, 'hcwa_center_id', true );
			foreach ( array_filter( $centers ) as $center ) {
				if ( hcwa_center_no_identifiable( (int) $center ) ) {
					$result = true;
					break 2;
				}
			}
		}
	} elseif ( 'post' === $post->post_type ) {
		$result = hcwa_meta_flag_any( $post->ID, 'hcwa_no_identifiable_children' ) || hcwa_post_has_protected_tag( $post, hcwa_no_identifiable_news_tags() );
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
 * Whether a center is a no-identifiable-children context: its "No identifiable children"
 * flag on any translation, or its unit key is little-rose (hcwa_center_is_protected(), which
 * fails closed when the flag was never ticked).
 *
 * @param int $center_id Center ID (any language).
 */
function hcwa_center_no_identifiable( int $center_id ): bool {
	if ( $center_id <= 0 ) {
		return false;
	}
	if ( function_exists( 'hcwa_center_is_protected' ) ) {
		return hcwa_center_is_protected( $center_id );
	}
	return hcwa_meta_flag_any( $center_id, 'hcwa_no_identifiable_children' );
}

/**
 * Term IDs of the protected news tags, every language version included: the news tag of
 * each center marked "No identifiable children" and of each program such a center runs,
 * plus hcwa_protected_tag_slugs(). A stored slug is resolved to its term in any language
 * and then to the term's whole Polylang translation group, so it does not matter which
 * language's slug was typed (on the imported site the Vietnamese Little Rose tag is
 * hoa-hong-nho-vi and hoa-hong-nho is the English one).
 *
 * @param bool $refresh Rebuild the cached list (tests, WP-CLI after changes).
 * @return int[]
 */
function hcwa_no_identifiable_news_tags( bool $refresh = false ): array {
	static $ids = null;
	if ( null !== $ids && ! $refresh ) {
		return $ids;
	}

	$args = array(
		'post_status'      => 'any',
		'numberposts'      => -1,
		'fields'           => 'ids',
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$args['lang'] = '';
	}
	$owners = get_posts(
		$args + array(
			'post_type'  => 'hcwa_center',
			'meta_key'   => 'hcwa_no_identifiable_children', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( get_posts( $args + array( 'post_type' => 'hcwa_program' ) ) as $program_id ) {
		if ( hcwa_image_context_no_identifiable( (int) $program_id ) ) {
			$owners[] = $program_id;
		}
	}

	$slugs = hcwa_protected_tag_slugs();
	foreach ( $owners as $owner_id ) {
		foreach ( hcwa_post_translation_ids( (int) $owner_id ) as $id ) {
			$slugs[] = trim( (string) get_post_meta( $id, 'hcwa_news_tag', true ) );
		}
	}

	return $ids = hcwa_tag_group_ids( $slugs );
}

/**
 * Tag slugs that are always protected, whichever center they belong to: the Little Rose
 * tags of the live site (scripts/urls-live.csv: hoa-hong-nho and lrws in English,
 * hoa-hong-nho-vi in Vietnamese; two English tags cannot share one translation group) and
 * the seeded English slug.
 *
 * @return string[]
 */
function hcwa_protected_tag_slugs(): array {
	/**
	 * Filters the post tag slugs whose news is always a no-identifiable-children context.
	 *
	 * @param string[] $slugs Tag slugs, any language.
	 */
	return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'hcwa_protected_tag_slugs', array( 'hoa-hong-nho', 'hoa-hong-nho-vi', 'little-rose', 'lrws' ) ) ) ) );
}

/**
 * IDs of the post tags with these slugs (any language) and of their Polylang translations.
 *
 * @param string[] $slugs Tag slugs.
 * @return int[]
 */
function hcwa_tag_group_ids( array $slugs ): array {
	$slugs = array_values( array_unique( array_filter( array_map( 'strval', $slugs ) ) ) );
	if ( ! $slugs ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'post_tag',
			'slug'       => $slugs,
			'hide_empty' => false,
			'fields'     => 'ids',
			'lang'       => '', // Every language (polylang src/crud-terms.php:192-195).
		)
	);

	$ids = array();
	foreach ( is_array( $terms ) ? $terms : array() as $term_id ) {
		$ids[] = (int) $term_id;
		if ( function_exists( 'pll_get_term_translations' ) ) {
			foreach ( (array) pll_get_term_translations( (int) $term_id ) as $translation ) {
				$ids[] = (int) $translation;
			}
		}
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Whether a news post, or any of its translations, carries one of the protected tags.
 *
 * @param WP_Post $post           News post.
 * @param int[]   $protected_tags Term IDs, hcwa_no_identifiable_news_tags().
 */
function hcwa_post_has_protected_tag( WP_Post $post, array $protected_tags ): bool {
	if ( ! $protected_tags ) {
		return false;
	}
	$protected_tags = array_map( 'intval', $protected_tags );
	foreach ( hcwa_post_translation_ids( $post->ID ) as $id ) {
		$terms = get_the_terms( $id, 'post_tag' ); // Cached with the post; wp_get_post_terms() always queries.
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( $term instanceof WP_Term && in_array( (int) $term->term_id, $protected_tags, true ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Slug of the Vietnamese version of a term (shared fields store VI slugs).
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
 * Category slugs of story posts (photo gate, "names changed" note): the seeded stories /
 * cau-chuyen and the live site's our-stories / chuyen-cua-chung-toi (scripts/urls-live.csv),
 * until Phase 3 moves the old posts. A "-vi" or "-en" suffix Polylang adds is ignored.
 *
 * @return string[]
 */
function hcwa_story_category_slugs(): array {
	/**
	 * Filters the category slugs whose posts are stories.
	 *
	 * @param string[] $slugs Category slugs, any language.
	 */
	return array_values( array_map( 'strval', (array) apply_filters( 'hcwa_story_category_slugs', array( 'stories', 'cau-chuyen', 'our-stories', 'chuyen-cua-chung-toi' ) ) ) );
}

/**
 * Whether a post, or any of its translations, is in a story category.
 *
 * @param WP_Post $post Post.
 */
function hcwa_post_is_story( WP_Post $post ): bool {
	$stories = hcwa_story_category_slugs();
	foreach ( hcwa_post_translation_ids( $post->ID ) as $id ) {
		foreach ( (array) get_the_category( $id ) as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			foreach ( array_unique( array( $term->slug, hcwa_original_term_slug( $term ) ) ) as $slug ) {
				if ( in_array( (string) preg_replace( '/-(vi|en)$/', '', $slug ), $stories, true ) ) {
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Whether the live site still waits for `wp hcwa protect-little-rose --apply`, which marks
 * the imported posts about Little Rose that carry no protected tag. Until then every news
 * post's photos need "Approved for web" on production (hcwa_content_images_gated()), and
 * `wp hcwa coverage` blocks.
 */
function hcwa_little_rose_review_pending(): bool {
	return hcwa_is_production() && '' === (string) get_option( 'hcwa_little_rose_review', '' );
}

/**
 * Words that mark a post as being about a protected center: hcwa_little_rose_aliases()
 * compared with hcwa_mentions().
 *
 * @return string[] Normalised aliases (lower case, no accents, single spaces).
 */
function hcwa_little_rose_aliases(): array {
	$aliases = array( 'hoa hong nho', 'little rose', 'litte rose', 'lrws', 'mai am hoa hong' );

	$args = array(
		'post_type'        => 'hcwa_center',
		'post_status'      => 'any',
		'numberposts'      => -1,
		'fields'           => 'ids',
		'suppress_filters' => false,
		'meta_key'         => 'hcwa_no_identifiable_children', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$args['lang'] = '';
	}
	foreach ( get_posts( $args ) as $center_id ) {
		foreach ( hcwa_post_translation_ids( (int) $center_id ) as $id ) {
			$aliases[] = get_the_title( $id );
		}
	}
	foreach ( hcwa_no_identifiable_news_tags() as $term_id ) {
		$term = get_term( (int) $term_id, 'post_tag' );
		if ( $term instanceof WP_Term ) {
			$aliases[] = $term->name;
		}
	}

	/**
	 * Filters the words that mark a post as being about Little Rose (or another protected center).
	 *
	 * @param string[] $aliases Words or phrases, any case, accents allowed.
	 */
	$aliases = (array) apply_filters( 'hcwa_little_rose_aliases', $aliases );
	$aliases = array_map( static fn( $alias ) => trim( hcwa_normalize_words( (string) $alias ) ), $aliases );
	return array_values( array_unique( array_filter( $aliases, static fn( string $alias ): bool => strlen( $alias ) >= 3 ) ) );
}

/**
 * Text as words for alias matching: no tags, no accents (đ = d), lower case, one space
 * between words and one at each end.
 *
 * @param string $text Text, HTML or a slug.
 */
function hcwa_normalize_words( string $text ): string {
	$text = strtolower( remove_accents( wp_strip_all_tags( $text ) ) );
	return ' ' . trim( (string) preg_replace( '/[^a-z0-9]+/', ' ', $text ) ) . ' ';
}

/**
 * The first alias that appears in a text as whole words, or ''.
 *
 * @param string   $text    Text, HTML or a slug.
 * @param string[] $aliases Normalised aliases (hcwa_little_rose_aliases()).
 */
function hcwa_mentions( string $text, array $aliases ): string {
	$words = hcwa_normalize_words( $text );
	foreach ( $aliases as $alias ) {
		if ( '' !== $alias && false !== strpos( $words, ' ' . $alias . ' ' ) ) {
			return $alias;
		}
	}
	return '';
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
 * An attachment's title, caption or alt text as the site may print it, or '' when nobody
 * wrote it. WordPress fills these in by itself at upload: the title from the file name,
 * and title, caption and alt from text a camera, phone app or photographer put in the file
 * (IPTC, EXIF, XMP: wp-admin/includes/media.php:392-407, image.php:886-997). Nobody
 * reviewed that text; it can hold a child's name and school. WordPress keeps what it read
 * in the attachment metadata ('image_meta', image.php:258-261), so text still equal to it,
 * or a title still equal to the file name, counts as unwritten. On the live site, text
 * with a draft marker is dropped too (it never passes the publish guard). hcwa-2026's
 * hcwa_theme_attachment_text() uses this when the plugin is active.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $field         'title', 'caption' or 'alt'.
 */
function hcwa_attachment_text( int $attachment_id, string $field ): string {
	$post = $attachment_id > 0 ? get_post( $attachment_id ) : null;
	if ( ! $post instanceof WP_Post || 'attachment' !== $post->post_type ) {
		return '';
	}
	$texts = array(
		'title'   => (string) $post->post_title,
		'caption' => (string) $post->post_excerpt,
		'alt'     => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
	);
	$text = trim( wp_strip_all_tags( $texts[ $field ] ?? '' ) );
	if ( '' === $text ) {
		return '';
	}

	$meta      = wp_get_attachment_metadata( $attachment_id );
	$meta      = is_array( $meta ) ? $meta : array();
	$from_file = is_array( $meta['image_meta'] ?? null ) ? trim( (string) ( $meta['image_meta'][ $field ] ?? '' ) ) : '';
	if ( '' !== $from_file && hcwa_attachment_text_key( $from_file ) === hcwa_attachment_text_key( $text ) ) {
		return '';
	}
	if ( 'title' === $field ) {
		$files = array( (string) get_post_meta( $attachment_id, '_wp_attached_file', true ), (string) ( $meta['original_image'] ?? '' ) );
		foreach ( $files as $file ) {
			$name = (string) preg_replace( '/\.[^.]*$/', '', wp_basename( $file ) );
			if ( '' !== $name && hcwa_attachment_file_name_key( $name ) === hcwa_attachment_file_name_key( $text ) ) {
				return '';
			}
		}
	}
	return ( hcwa_is_production() && hcwa_contains_draft( $text ) ) ? '' : $text;
}

/**
 * Comparison key for attachment text: its slug, so case, spacing, accents and HTML entities
 * do not matter.
 *
 * @param string $text Text.
 */
function hcwa_attachment_text_key( string $text ): string {
	$key = sanitize_title( $text );
	return '' !== $key ? $key : trim( $text );
}

/**
 * Comparison key for a file name and a title made from one: the slug without the endings
 * WordPress adds to stored files (-1, -2 … from wp_unique_filename(), -scaled, -rotated,
 * -e{timestamp} after an edit).
 *
 * @param string $name File name without extension, or a title.
 */
function hcwa_attachment_file_name_key( string $name ): string {
	return (string) preg_replace( '/(?:-(?:scaled|rotated|e?\d+))+$/', '', hcwa_attachment_text_key( $name ) );
}

/**
 * wp_get_attachment_image_attributes on the front end: the default alt (the stored alt,
 * media.php:1186) is printed only when staff wrote it (hcwa_attachment_text()), for
 * hcwa_image(), the theme, the blocks and core's own image functions alike. An alt the
 * caller passed is kept.
 *
 * @param array   $attr       Attributes.
 * @param WP_Post $attachment Attachment.
 * @return array
 */
function hcwa_attachment_alt_attr( $attr, $attachment = null ) {
	if ( ! is_array( $attr ) || ! isset( $attr['alt'] ) || '' === $attr['alt'] || ! $attachment instanceof WP_Post || ! hcwa_is_front_end_view() ) {
		return $attr;
	}
	$stored = trim( strip_tags( (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- the same steps as core's default alt.
	if ( (string) $attr['alt'] === $stored ) {
		$attr['alt'] = hcwa_attachment_text( (int) $attachment->ID, 'alt' );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'hcwa_attachment_alt_attr', 10, 2 );

/**
 * wp_get_attachment_caption (post.php:7295) on the front end: '' unless staff wrote the
 * caption (hcwa_attachment_text()). Covers get_the_post_thumbnail_caption() and plugins
 * that read captions through it (SEO image schema). The classic [gallery] reads
 * post_excerpt itself (media.php:3063-3067), and block captions are saved in the content.
 *
 * @param string $caption Caption.
 * @param int    $post_id Attachment ID.
 * @return string
 */
function hcwa_attachment_caption( $caption, $post_id = 0 ) {
	if ( ! is_string( $caption ) || '' === trim( $caption ) || ! hcwa_is_front_end_view() ) {
		return $caption;
	}
	return '' === hcwa_attachment_text( (int) $post_id, 'caption' ) ? '' : $caption;
}
add_filter( 'wp_get_attachment_caption', 'hcwa_attachment_caption', 10, 2 );

/**
 * Whether photos placed in a post's own content must pass hcwa_image_allowed() (contract
 * rule 2): pages (the HCWA patterns' image slots), centers, programs and reports, story
 * posts (hcwa_post_is_story(), any language version), and any no-identifiable-children
 * context (Little Rose news). Other news posts keep their inline photos as published on the
 * old site, except on the live site before `wp hcwa protect-little-rose --apply` has run
 * (hcwa_little_rose_review_pending()): until then an imported Little Rose post may carry no
 * protected tag, so every news photo needs "Approved for web".
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
			$gated = hcwa_little_rose_review_pending() || hcwa_image_context_no_identifiable( $post ) || hcwa_post_is_story( $post );
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
 * Backstop for code that reads the featured image ID itself and prints its URL (share
 * buttons, feeds, other plugins; core get_post_thumbnail_id(), post-thumbnail-template.php:70):
 * on a front-end view a refused featured image counts as none. The editor (REST), admin
 * screens, AJAX, cron and WP-CLI still see it, so staff can replace it. Yoast SEO is gated
 * in seo.php, because it stores the image at save time.
 *
 * @param int|false        $thumbnail_id Attachment ID.
 * @param int|WP_Post|null $post         Post.
 * @return int
 */
function hcwa_gate_post_thumbnail_id( $thumbnail_id, $post = null ) {
	$thumbnail_id = (int) $thumbnail_id;
	if ( $thumbnail_id <= 0 || ! hcwa_is_front_end_view() ) {
		return $thumbnail_id;
	}
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return $thumbnail_id;
	}

	static $seen = array();
	$key = $post->ID . ':' . $thumbnail_id;
	if ( ! isset( $seen[ $key ] ) ) {
		$seen[ $key ] = 'hcwa_partner' === $post->post_type ? hcwa_partner_logo_allowed( $post->ID ) : hcwa_image_allowed( $thumbnail_id, $post );
	}
	return $seen[ $key ] ? $thumbnail_id : 0;
}
add_filter( 'post_thumbnail_id', 'hcwa_gate_post_thumbnail_id', 10, 2 );

/**
 * Whether this request renders the public site (not admin, AJAX, REST, cron or WP-CLI).
 */
function hcwa_is_front_end_view(): bool {
	return ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron()
		&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! ( defined( 'WP_CLI' ) && WP_CLI );
}

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
 * JPEG uploads are served as WebP (05-DESIGN "Formats", 09-QA images). On the server path
 * WordPress converts the main file and the sizes it makes and keeps the uploaded JPEG as the
 * attachment's original_image (wp-admin/includes/image.php:297-345,
 * _wp_image_meta_replace_original()); in the block editor's client-side path the browser
 * makes the sizes in the format the REST API reports as image_output_format, which it
 * works out by calling this filter with the identity mapping
 * array( 'image/jpeg' => 'image/jpeg' ) (class-wp-rest-attachments-controller.php:1586-1595),
 * so an identity JPEG mapping counts as unset. A mapping to another format (AVIF) set
 * earlier is kept. WordPress applies the mapping only when the server's image editor can
 * write WebP, otherwise keeping JPEG (wp-includes/class-wp-image-editor.php:370-377), and
 * prints a plain <img>, so no JPEG fallback is served: 05-DESIGN's "JPEG fallback" is
 * covered only by every current browser reading WebP (CONTRACT Status records this). PNG
 * (logos, illustrations, the VietQR code) and the HEIC-to-JPEG default are left alone.
 * AVIF is not used for the same reason (no <picture>). Media already in the library stays
 * JPEG until its sizes are regenerated; its location data is removed by `wp hcwa
 * media-scrub` (media-privacy.php), not by regenerating. Removing this filter restores
 * JPEG for new uploads.
 *
 * @param array<string, string> $formats   Source mime type => output mime type.
 * @param string                $filename  Path to the image (unused).
 * @param string                $mime_type Source mime type (unused).
 * @return array<string, string>
 */
function hcwa_image_output_format( $formats, $filename = '', $mime_type = '' ) {
	$formats = is_array( $formats ) ? $formats : array();
	if ( 'image/jpeg' === ( $formats['image/jpeg'] ?? 'image/jpeg' ) ) {
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
 * ID of the attachment the main query shows, whatever the query looks like, else 0.
 * is_attachment() is not enough: ?post_type=attachment&p=N (also with page_id, name or
 * post_type[]) makes WP_Query set is_single, not is_attachment, and single.php then printed
 * the attachment's title, description and a language switch naming its parent page (found
 * on real WordPress, fix round 1). So the check reads the queried object itself.
 */
function hcwa_queried_attachment_id(): int {
	$object = get_queried_object();
	if ( $object instanceof WP_Post && 'attachment' === $object->post_type ) {
		return (int) $object->ID;
	}
	return is_attachment() ? (int) get_queried_object_id() : 0;
}

/**
 * Attachments a request names: the attachment the main query shows
 * (hcwa_queried_attachment_id()), the IDs in its p, page_id and attachment_id query vars,
 * and its attachment slug. ?p=40&feed=rss2 finds no post (p means a post there), yet it is
 * the comment feed of attachment 40 and answered 200 (found on real WordPress, fix round 2),
 * so a refused photo is refused however the request names it.
 *
 * @return int[]
 */
function hcwa_request_attachment_ids(): array {
	$ids = array( hcwa_queried_attachment_id() );
	foreach ( array( 'p', 'page_id', 'attachment_id' ) as $var ) {
		$value = get_query_var( $var );
		$ids[] = is_scalar( $value ) ? absint( $value ) : 0;
	}
	$slug = get_query_var( 'attachment' );
	if ( is_string( $slug ) && '' !== $slug ) {
		$found = get_page_by_path( sanitize_title( $slug ), OBJECT, 'attachment' );
		$ids[] = $found instanceof WP_Post ? (int) $found->ID : 0;
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * The attachment page of a refused photo is a plain 404, whatever the theme: the default
 * attachment template prints the image, its title and caption (which often carry a
 * child's name) with wp_get_attachment_image(), past the gate. It runs on 'wp'
 * (class-wp.php:839), before every template_redirect callback: redirect_canonical
 * (default-filters.php:687, priority 10) and Yoast SEO's attachment redirect (priority 1)
 * would otherwise answer 301 to the raw file when attachment pages are off
 * (canonical.php:551-552), and ?attachment_id=N is a public query var (class-wp.php:18).
 * set_404() clears is_attachment (class-wp-query.php:1846 and :525), so those redirects,
 * and hcwa-2026's own copy of this check (inc/setup.php), do nothing. set_404() keeps
 * is_feed, though, and the photo's feeds (&feed=rss2, atom, rdf, comments-rss2) printed it as
 * an item under the 404 (fix round 2), so hcwa_end_as_404() also clears the feed flags and
 * the found posts. The attachment is found through hcwa_request_attachment_ids(), so a
 * post_type query that shows it as a single post, and its comment feed by ?p=N, are
 * refused too.
 */
function hcwa_refuse_attachment_page(): void {
	foreach ( hcwa_request_attachment_ids() as $id ) {
		if ( hcwa_attachment_page_refused( $id ) ) {
			hcwa_end_as_404();
			return;
		}
	}
}
add_action( 'wp', 'hcwa_refuse_attachment_page', 5, 0 );

/**
 * Stops core's canonical redirect from turning a refused photo's 404 into a 301 to its
 * pretty attachment path (found on real WordPress, 9 Nov 2026). hcwa_refuse_attachment_page()
 * sets the 404 on 'wp'; redirect_canonical() (template_redirect, priority 10) then takes its
 * is_404() branch (canonical.php: the p, page_id and attachment_id query vars become
 * get_permalink( N )), and the Location header names the Little Rose page path. The redirect
 * is cancelled whenever one of those query vars, the attachment slug or the guessed target
 * names an attachment the gate refuses; the request then ends as a plain 404.
 *
 * @param string|false $redirect_url  Where core would redirect.
 * @param string       $requested_url The requested URL.
 * @return string|false
 */
function hcwa_refused_attachment_canonical( $redirect_url, $requested_url = '' ) {
	if ( false === $redirect_url || '' === (string) $redirect_url ) {
		return $redirect_url;
	}
	foreach ( hcwa_canonical_attachment_candidates( (string) $redirect_url ) as $id ) {
		if ( 'attachment' === get_post_type( $id ) && hcwa_attachment_page_refused( $id ) ) {
			return false;
		}
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'hcwa_refused_attachment_canonical', 10, 2 );

/**
 * Post IDs a canonical redirect may be about: the p, page_id and attachment_id query vars,
 * the attachment slug, and the post the redirect target resolves to.
 *
 * @param string $redirect_url Where core would redirect.
 * @return int[]
 */
function hcwa_canonical_attachment_candidates( string $redirect_url ): array {
	$ids = array();
	foreach ( array( 'p', 'page_id', 'attachment_id' ) as $var ) {
		$ids[] = absint( get_query_var( $var ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only.
		$ids[] = isset( $_GET[ $var ] ) && is_scalar( $_GET[ $var ] ) ? absint( $_GET[ $var ] ) : 0;
	}
	$slug = (string) get_query_var( 'attachment' );
	if ( '' !== $slug ) {
		$found = get_page_by_path( sanitize_title( $slug ), OBJECT, 'attachment' );
		$ids[] = $found instanceof WP_Post ? (int) $found->ID : 0;
	}
	if ( function_exists( 'url_to_postid' ) ) {
		$ids[] = (int) url_to_postid( $redirect_url );
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

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
			'help'  => __( 'HCWA has approved this image for the website. Pages, centers, programs, reports, stories and Little Rose news show only approved images, icons and illustrations included. Check the title, caption and alt text before approving: they can appear on the site. Write the alt text for each language and never name a child.', 'hcwa' ),
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
