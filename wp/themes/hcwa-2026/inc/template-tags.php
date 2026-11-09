<?php
/**
 * Small markup helpers shared by templates (ports of prototype/src/partials/components.mjs).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lucide outline icon (ISC) from the prototype's exported paths in hcwa-core
 * (prototype-data.php 'icons'), so theme and blocks print the same SVG. '' without hcwa-core.
 *
 * @param string $name  Icon name.
 * @param string $class CSS class.
 */
function hcwa_theme_icon( string $name, string $class = 'icon' ): string {
	return function_exists( 'hcwa_icon' ) ? hcwa_icon( $name, $class ) : '';
}

/**
 * Button link: primary (sun), secondary (blue outline), on-blue (for blue bands).
 *
 * @param string $url   Link target.
 * @param string $label Visible label (plain text).
 * @param string $kind  primary | secondary | on-blue.
 * @param string $extra Extra class names.
 */
function hcwa_theme_button( string $url, string $label, string $kind = 'primary', string $extra = '' ): string {
	$kind = in_array( $kind, array( 'primary', 'secondary', 'on-blue' ), true ) ? $kind : 'primary';
	return sprintf(
		'<a class="%s" href="%s">%s</a>',
		esc_attr( trim( 'btn btn--' . $kind . ' ' . $extra ) ),
		esc_url( $url ),
		esc_html( $label )
	);
}

/**
 * Text link with arrow.
 *
 * @param string $url   Link target.
 * @param string $label Visible label (plain text).
 */
function hcwa_theme_text_link( string $url, string $label ): string {
	return sprintf(
		'<a class="text-link" href="%s"><span>%s</span>%s</a>',
		esc_url( $url ),
		esc_html( $label ),
		hcwa_theme_icon( 'arrow' )
	);
}

/**
 * VI / EN switch: text not flags, aria-current on the current one, lang on each, and
 * hreflang only where the target is this page in that language: a link to a post without a
 * translation leads to the same post in the page's language (with the language notice), or
 * to that language's home page, so it promises no hreflang.
 * The visible "VI" stays inside the accessible name (WCAG 2.5.3): "VI (Tiếng Việt)".
 *
 * @param string $class Extra class.
 */
function hcwa_theme_lang_switch( string $class = '' ): string {
	$parts = array();
	foreach ( hcwa_theme_language_links() as $code => $link ) {
		$parts[] = sprintf(
			'<a href="%1$s" lang="%2$s"%6$s%3$s>%4$s<span class="sr-only"> (%5$s)</span></a>',
			esc_url( $link['url'] ),
			esc_attr( $code ),
			$link['current'] ? ' aria-current="true"' : '',
			esc_html( strtoupper( $code ) ),
			esc_html( $link['name'] ),
			( $link['current'] || $link['has_translation'] ) ? ' hreflang="' . esc_attr( $code ) . '"' : ''
		);
	}
	return sprintf(
		'<div class="%s" role="group" aria-label="%s" data-lang-switch>%s</div>',
		esc_attr( trim( 'lang-switch ' . $class ) ),
		esc_attr( hcwa_theme_t( 'language' ) ),
		implode( '<span aria-hidden="true">/</span>', $parts )
	);
}

/**
 * Unit accent from post meta, limited to the contract values.
 *
 * @param int $post_id Post ID.
 */
function hcwa_theme_accent( int $post_id ): string {
	$accent = (string) get_post_meta( $post_id, 'hcwa_accent', true );
	return in_array( $accent, array( 'green-bamboo', 'little-rose', 'cau-han', 'blue' ), true ) ? $accent : '';
}

/**
 * Whether a center must not say where it is or invite visits: Little Rose, or any center
 * flagged "No identifiable children" (a shelter for girls who survived or are at risk of
 * sexual abuse). Read across translations through hcwa-core when it is active.
 *
 * @param int $post_id Center ID.
 */
function hcwa_theme_center_is_protected( int $post_id ): bool {
	if ( function_exists( 'hcwa_meta_flag_any' ) ) {
		return hcwa_meta_flag_any( $post_id, 'hcwa_no_identifiable_children' );
	}
	return in_array( get_post_meta( $post_id, 'hcwa_no_identifiable_children', true ), array( true, 1, '1', 'true' ), true );
}

/**
 * One-item-per-line meta as a clean list. Pure parsing in hcwa_theme_split_lines().
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return string[]
 */
function hcwa_theme_meta_lines( int $post_id, string $key ): array {
	return hcwa_theme_split_lines( (string) get_post_meta( $post_id, $key, true ) );
}

/**
 * Splits text on line breaks, trims, drops empty lines and leading list markers.
 *
 * @param string $text Text.
 * @return string[]
 */
function hcwa_theme_split_lines( string $text ): array {
	$lines = preg_split( '/\r\n|\r|\n/', $text );
	$out   = array();
	foreach ( (array) $lines as $line ) {
		$line = trim( (string) preg_replace( '/^\s*(?:[-*•]\s+)/u', '', (string) $line ) );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}
	return $out;
}

/**
 * Integer list meta (int[]), stored as an array or a comma-separated string.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return int[]
 */
function hcwa_theme_meta_ids( int $post_id, string $key ): array {
	return hcwa_theme_parse_ids( get_post_meta( $post_id, $key, true ) );
}

/**
 * Loads some posts (attachments, partners) with their meta and terms in three queries in
 * all, before a loop reads them one by one: hcwa_image() reads the approval flags and, for
 * the identifiable-child check, the attachment's Polylang translations (kept in terms);
 * wp_get_attachment_image() reads the metadata for srcset. _prime_post_caches() is core's
 * (wp-includes/post.php:8502) and skips posts already cached.
 *
 * @param int[] $ids Post IDs.
 */
function hcwa_theme_prime_ids( array $ids ): void {
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ), static fn( int $id ): bool => $id > 0 ) ) );
	if ( $ids && function_exists( '_prime_post_caches' ) ) {
		_prime_post_caches( $ids, true, true );
	}
}

/**
 * Attachment IDs that some posts point to through meta keys (the featured image by
 * default), each once. Reads the posts' meta, which their query has already loaded.
 * Pure apart from get_post_meta() (tested).
 *
 * @param array    $posts WP_Post objects or IDs.
 * @param string[] $keys  Meta keys that hold one attachment ID.
 * @return int[]
 */
function hcwa_theme_attachment_ids( array $posts, array $keys = array( '_thumbnail_id' ) ): array {
	$ids = array();
	foreach ( $posts as $post ) {
		$post_id = $post instanceof WP_Post ? (int) $post->ID : ( is_int( $post ) ? $post : 0 );
		if ( $post_id <= 0 ) {
			continue;
		}
		foreach ( $keys as $key ) {
			$id = (int) get_post_meta( $post_id, $key, true );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Primes the attachments a list of posts is about to print (cards, report rows).
 *
 * @param array    $posts WP_Post objects or IDs, usually $wp_query->posts.
 * @param string[] $keys  Meta keys that hold one attachment ID.
 */
function hcwa_theme_prime_attachments( array $posts, array $keys = array( '_thumbnail_id' ) ): void {
	hcwa_theme_prime_ids( hcwa_theme_attachment_ids( $posts, $keys ) );
}

/**
 * An attachment's title, caption or alt text as the theme may print it, or '' when nobody
 * wrote it. At upload WordPress fills these in by itself: the title from the file name, and
 * title, caption and alt from text a camera, phone app or photographer put in the file
 * (IPTC, EXIF, XMP: wp-admin/includes/media.php:313-320 and 392-407, image.php:886-997).
 * Nobody reviewed that text; it can hold a child's name and school (CLAUDE.md) or just
 * "OLYMPUS DIGITAL CAMERA". WordPress keeps what it read in the attachment metadata
 * ('image_meta', image.php:258-261), so text still equal to it, or a title still equal to
 * the file name, counts as unwritten. Like other text that skips the publish guard, draft
 * text is dropped on the live site. hcwa-core's hcwa_attachment_text() applies the same
 * rule when the plugin is active (it also gates the alt and caption WordPress prints).
 * Pure apart from post and meta reads (tested).
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $field         'title', 'caption' or 'alt'.
 */
function hcwa_theme_attachment_text( int $attachment_id, string $field ): string {
	if ( function_exists( 'hcwa_attachment_text' ) ) {
		return hcwa_attachment_text( $attachment_id, $field );
	}
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
	if ( '' !== $from_file && hcwa_theme_text_key( $from_file ) === hcwa_theme_text_key( $text ) ) {
		return '';
	}
	if ( 'title' === $field ) {
		$files = array( (string) get_post_meta( $attachment_id, '_wp_attached_file', true ), (string) ( $meta['original_image'] ?? '' ) );
		foreach ( $files as $file ) {
			$name = (string) preg_replace( '/\.[^.]*$/', '', wp_basename( $file ) );
			if ( '' !== $name && hcwa_theme_file_name_key( $name ) === hcwa_theme_file_name_key( $text ) ) {
				return '';
			}
		}
	}
	return hcwa_theme_public_text( $text );
}

/**
 * Comparison key for attachment text: its slug, so case, spacing, accents and HTML entities
 * do not matter. Pure helper (tested).
 *
 * @param string $text Text.
 */
function hcwa_theme_text_key( string $text ): string {
	$key = sanitize_title( $text );
	return '' !== $key ? $key : trim( $text );
}

/**
 * Comparison key for a file name and a title made from one: the slug without the endings
 * WordPress adds to stored files (-1, -2 … from wp_unique_filename(), -scaled for big
 * images, -rotated, -e{timestamp} after an edit). Pure helper (tested).
 *
 * @param string $name File name without extension, or a title.
 */
function hcwa_theme_file_name_key( string $name ): string {
	return (string) preg_replace( '/(?:-(?:scaled|rotated|e?\d+))+$/', '', hcwa_theme_text_key( $name ) );
}

/**
 * Heading of an attachment page (H1, breadcrumb, browser title): the title staff wrote
 * (hcwa_theme_attachment_text()), else "Ảnh" / "Photo", or "Tệp đính kèm" / "Attachment"
 * for other files. Never the file name or text read from the file.
 *
 * @param int $attachment_id Attachment ID.
 */
function hcwa_theme_attachment_heading( int $attachment_id ): string {
	$title = hcwa_theme_clean_title( hcwa_theme_attachment_text( $attachment_id, 'title' ) );
	if ( '' !== $title ) {
		return $title;
	}
	return wp_attachment_is_image( $attachment_id ) ? hcwa_theme_t( 'photo' ) : hcwa_theme_t( 'attachedFile' );
}

/**
 * Normalizes an ID list. Pure helper (tested).
 *
 * @param mixed $value Array, comma-separated string or single number.
 * @return int[]
 */
function hcwa_theme_parse_ids( $value ): array {
	if ( is_string( $value ) ) {
		$value = preg_split( '/[\s,]+/', $value );
	}
	$ids = array_map( 'intval', (array) $value );
	return array_values( array_unique( array_filter( $ids, static fn( $id ) => $id > 0 ) ) );
}

/**
 * Year range for a program: "2023–2026", "2023–" (no end yet) or "".
 *
 * @param int $start Start year.
 * @param int $end   End year.
 */
function hcwa_theme_year_range( int $start, int $end ): string {
	if ( $start <= 0 ) {
		return $end > 0 ? (string) $end : '';
	}
	if ( $end <= 0 ) {
		return $start . '–';
	}
	return $start === $end ? (string) $start : $start . '–' . $end;
}

/**
 * Category slugs per logical key, EN and VI (contract: seeded categories). hcwa-core's
 * hcwa_category_slugs() when the plugin is active, so chips, card labels and `wp hcwa
 * coverage` read one map; the same four sections otherwise.
 *
 * @return array<string, string[]>
 */
function hcwa_theme_category_slugs(): array {
	if ( function_exists( 'hcwa_category_slugs' ) ) {
		return hcwa_category_slugs();
	}
	return array(
		'news'          => array( 'news', 'tin-tuc' ),
		'stories'       => array( 'stories', 'cau-chuyen' ),
		'events'        => array( 'events', 'su-kien' ),
		'announcements' => array( 'announcements', 'thong-bao' ),
	);
}

/**
 * Logical key of a category term ('news', 'stories', …) or '' if it is not a seeded one.
 * Checks the term's translations too, in case Polylang gave a language its own slug.
 *
 * @param WP_Term $term Category.
 */
function hcwa_theme_category_key( WP_Term $term ): string {
	$slugs = array( $term->slug );
	if ( function_exists( 'pll_get_term' ) ) {
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$other = (int) pll_get_term( $term->term_id, $lang );
			if ( $other > 0 && $other !== $term->term_id ) {
				$translated = get_term( $other, 'category' );
				if ( $translated instanceof WP_Term ) {
					$slugs[] = $translated->slug;
				}
			}
		}
	}
	if ( function_exists( 'hcwa_category_key' ) ) {
		return hcwa_category_key( $slugs ); // Also maps legacy story categories to 'stories'.
	}
	foreach ( hcwa_theme_category_slugs() as $key => $known ) {
		if ( array_intersect( $slugs, $known ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * Seeded category in the current language, or null when it has no version in this
 * language (never the other language's category: its chip would mix languages).
 *
 * @param string $key news | stories | events | announcements.
 */
function hcwa_theme_category( string $key ): ?WP_Term {
	$slugs = hcwa_theme_category_slugs()[ $key ] ?? array();
	if ( 'en' !== hcwa_theme_lang() ) {
		$slugs = array_reverse( $slugs );
	}
	foreach ( $slugs as $slug ) {
		$term = hcwa_theme_term_in_lang( 'category', $slug, true );
		if ( $term ) {
			return $term;
		}
	}
	return null;
}

/**
 * Which term to use for the current language. Pure part of hcwa_theme_term_in_lang() (tested).
 *
 * @param WP_Term      $term       Term found by slug, in any language.
 * @param int|null     $translated pll_get_term() for the current language: the term itself
 *                                 when it is in that language, its translation, or 0 when it
 *                                 has none; null without Polylang.
 * @param WP_Term|null $other      The translation, when it is another term.
 * @param bool         $strict     Null instead of a term in the other language.
 */
function hcwa_theme_pick_term( WP_Term $term, ?int $translated, ?WP_Term $other, bool $strict ): ?WP_Term {
	if ( null === $translated || $translated === (int) $term->term_id ) {
		return $term;
	}
	if ( $translated > 0 && $other instanceof WP_Term ) {
		return $other;
	}
	return $strict ? null : $term;
}

/**
 * A term looked up by slug in any language, returned in the current language. Without a
 * version in the current language: the term as found, or null when $strict.
 *
 * @param string $taxonomy Taxonomy.
 * @param string $slug     Term slug (sync meta stores the VI one).
 * @param bool   $strict   Never return a term in the other language.
 */
function hcwa_theme_term_in_lang( string $taxonomy, string $slug, bool $strict = false ): ?WP_Term {
	if ( '' === $slug ) {
		return null;
	}
	// 'lang' => '' asks Polylang not to filter this lookup by the current language.
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'slug'       => $slug,
			'hide_empty' => false,
			'number'     => 1,
			'lang'       => '',
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}
	$term       = $terms[0];
	$translated = function_exists( 'pll_get_term' ) ? (int) pll_get_term( $term->term_id, hcwa_theme_lang() ) : null;
	$other      = ( $translated > 0 && $translated !== (int) $term->term_id ) ? get_term( $translated, $taxonomy ) : null;
	return hcwa_theme_pick_term( $term, $translated, $other instanceof WP_Term ? $other : null, $strict );
}

/**
 * The category shown on a post's card: the first seeded one, else the first real one.
 *
 * @param WP_Post $post Post.
 * @return array{term: ?WP_Term, key: string, label: string}
 */
function hcwa_theme_post_category( WP_Post $post ): array {
	$terms    = get_the_category( $post->ID );
	$fallback = null;
	foreach ( $terms as $term ) {
		if ( (int) get_option( 'default_category' ) === $term->term_id && 'uncategorized' === $term->slug ) {
			continue;
		}
		$key = hcwa_theme_category_key( $term );
		if ( '' !== $key ) {
			return array(
				'term'  => $term,
				'key'   => $key,
				'label' => hcwa_theme_category_label( $key, $term ),
			);
		}
		$fallback = $fallback ?? $term;
	}
	return array(
		'term'  => $fallback,
		'key'   => '',
		'label' => $fallback ? $fallback->name : '',
	);
}

/**
 * Singular label for a category on cards (prototype: Tin tức / Câu chuyện / Sự kiện).
 *
 * @param string       $key  Logical key.
 * @param WP_Term|null $term Term, for the name of non-seeded categories.
 */
function hcwa_theme_category_label( string $key, ?WP_Term $term = null ): string {
	$map = array(
		'news'          => 'cat_news',
		'stories'       => 'cat_story',
		'events'        => 'cat_event',
		'announcements' => 'cat_announcement',
	);
	if ( isset( $map[ $key ] ) ) {
		return hcwa_theme_t( $map[ $key ] );
	}
	return $term ? $term->name : '';
}

/**
 * Whether a post is a story (adds the "names changed" note). With hcwa-core this is
 * hcwa_post_is_story(), the rule its photo gate uses: the seeded and the legacy story
 * categories (our-stories, chuyen-cua-chung-toi), in any language version of the post, so
 * the note and the gate always agree.
 *
 * @param WP_Post $post Post.
 */
function hcwa_theme_is_story( WP_Post $post ): bool {
	if ( function_exists( 'hcwa_post_is_story' ) ) {
		return hcwa_post_is_story( $post );
	}
	foreach ( get_the_category( $post->ID ) as $term ) {
		if ( 'stories' === hcwa_theme_category_key( $term ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Renders post content once and splits it at the "more" block: the part before is the
 * short story, the part after goes into "Read the full history" (06-PAGES center page).
 *
 * @return array{main: string, extended: string}
 */
function hcwa_theme_split_content(): array {
	$html  = apply_filters( 'the_content', get_the_content() );
	$html  = str_replace( ']]>', ']]&gt;', (string) $html );
	$parts = preg_split( '#(?:<p>\s*)?<span id="more-\d+"></span>(?:\s*</p>)?#', $html, 2 );
	return array(
		'main'     => trim( (string) ( $parts[0] ?? '' ) ),
		'extended' => trim( (string) ( $parts[1] ?? '' ) ),
	);
}

/**
 * Posts tagged with a center's or program's news tag, in the current language.
 *
 * @param string $tag_slug Tag slug from hcwa_news_tag (the VI tag).
 * @param int    $limit    How many.
 * @param int    $exclude  Post to leave out.
 * @return WP_Post[]
 */
function hcwa_theme_tagged_posts( string $tag_slug, int $limit = 3, int $exclude = 0 ): array {
	$tag = hcwa_theme_term_in_lang( 'post_tag', $tag_slug );
	if ( ! $tag ) {
		return array();
	}
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'tag_id'              => $tag->term_id,
			'posts_per_page'      => $limit,
			'post__not_in'        => $exclude ? array( $exclude ) : array(),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	return $query->posts;
}

/**
 * The VI name in brackets on EN pages (07-BILINGUAL: proper names keep their diacritics).
 *
 * @param int $post_id Post ID.
 */
function hcwa_theme_vi_name( int $post_id ): string {
	if ( 'en' !== hcwa_theme_lang() || ! function_exists( 'pll_get_post' ) ) {
		return '';
	}
	$vi_id = (int) pll_get_post( $post_id, 'vi' );
	if ( $vi_id <= 0 || $vi_id === $post_id ) {
		return '';
	}
	$post = get_post( $post_id );
	return hcwa_theme_vi_name_of( get_post( $vi_id ), $post instanceof WP_Post ? $post->post_title : '' );
}

/**
 * Pure part of hcwa_theme_vi_name() (tested): the Vietnamese translation's raw title, only
 * when that translation is public (published, no password: an unreviewed draft title never
 * reaches the live English page), differs from the English title, and holds no draft
 * marker on the live site.
 *
 * @param WP_Post|null $vi       Vietnamese translation.
 * @param string       $en_title Raw title of the English post.
 */
function hcwa_theme_vi_name_of( ?WP_Post $vi, string $en_title ): string {
	if ( ! $vi instanceof WP_Post || 'publish' !== $vi->post_status || ! empty( $vi->post_password ) ) {
		return '';
	}
	$name = trim( wp_strip_all_tags( (string) $vi->post_title ) );
	if ( '' === $name || trim( wp_strip_all_tags( $en_title ) ) === $name ) {
		return '';
	}
	return hcwa_theme_public_text( $name );
}

/**
 * Everything a report row or report page shows, from the contract meta.
 *
 * @param WP_Post $post Report.
 * @return array{file_url: string, size: string, format: string, year: int, type: string, type_label: string, doc_lang: string, meta: string}
 */
function hcwa_theme_report_info( WP_Post $post ): array {
	$file_id  = (int) get_post_meta( $post->ID, 'hcwa_file_id', true );
	$file_url = $file_id > 0 ? (string) wp_get_attachment_url( $file_id ) : '';
	// WordPress stores the size in the attachment metadata at upload, PDFs included
	// (wp-admin/includes/image.php:735-736, 'filesize'), which the row's priming already
	// loaded; stat the file only for older uploads without it. Same rule as hcwa-core's
	// report rows (includes/blocks.php).
	$meta     = $file_id > 0 ? wp_get_attachment_metadata( $file_id ) : false;
	$bytes    = is_array( $meta ) && ! empty( $meta['filesize'] ) ? (int) $meta['filesize'] : 0;
	if ( ! $bytes && $file_id > 0 ) {
		$path  = get_attached_file( $file_id );
		$bytes = ( $path && is_readable( $path ) ) ? (int) filesize( $path ) : 0;
	}
	$size     = $bytes > 0 ? (string) size_format( $bytes, 1 ) : '';
	$format   = '' !== $file_url ? strtolower( pathinfo( (string) wp_parse_url( $file_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ) : '';
	$year     = (int) get_post_meta( $post->ID, 'hcwa_year', true );
	$type     = (string) get_post_meta( $post->ID, 'hcwa_report_type', true );
	$type     = in_array( $type, hcwa_theme_report_types(), true ) ? $type : '';
	$doc_lang = (string) get_post_meta( $post->ID, 'hcwa_doc_language', true );
	$doc_lang = in_array( $doc_lang, array( 'vi', 'en', 'both' ), true ) ? $doc_lang : '';
	$label    = '' !== $type ? hcwa_theme_t( 'rt_' . $type ) : '';

	return array(
		'file_url'   => $file_url,
		'size'       => $size,
		'format'     => '' !== $format ? $format : 'pdf',
		'year'       => $year,
		'type'       => $type,
		'type_label' => $label,
		'doc_lang'   => $doc_lang,
		'meta'       => implode( ' · ', array_filter( array( $year > 0 ? (string) $year : '', $label ) ) ),
	);
}

/**
 * Prints the document badge: "PDF · Tiếng Việt", each language name in its own language.
 *
 * @param array $info From hcwa_theme_report_info().
 */
function hcwa_theme_report_badge( array $info ): void {
	$names = array(
		'vi' => 'Tiếng Việt',
		'en' => 'English',
	);
	$langs = 'both' === $info['doc_lang'] ? array( 'vi', 'en' ) : array_filter( array( $info['doc_lang'] ) );
	$parts = array();
	foreach ( $langs as $lang ) {
		$parts[] = '<span lang="' . esc_attr( $lang ) . '">' . esc_html( $names[ $lang ] ) . '</span>';
	}
	echo '<span class="badge">' . esc_html( strtoupper( $info['format'] ) ) . ( $parts ? ' · ' . implode( ', ', $parts ) : '' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts escaped above.
}

/**
 * Language the visitor asked for when the switch led to a post without a translation
 * (?hcwa_lang_notice=en). '' when the parameter is absent. Read-only display flag.
 */
function hcwa_theme_lang_notice_request(): string {
	if ( function_exists( 'hcwa_lang_notice_request' ) ) {
		return hcwa_lang_notice_request();
	}
	if ( ! isset( $_GET['hcwa_lang_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$lang = sanitize_key( wp_unslash( $_GET['hcwa_lang_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( in_array( $lang, array( 'vi', 'en' ), true ) ) {
		return $lang;
	}
	return 'vi' === hcwa_theme_lang() ? 'en' : 'vi';
}

/**
 * Whether $current is inside the section that starts at $path. The two language home
 * pages ('/' and '/en/') are never a section of everything below them. Pure helper (tested).
 *
 * @param string $path    Menu item path, with trailing slash.
 * @param string $current Current request path, with leading and trailing slash.
 */
function hcwa_theme_is_section_path( string $path, string $current ): bool {
	if ( in_array( $path, array( '/', '/en/' ), true ) || $path === $current ) {
		return false;
	}
	return str_starts_with( $current, $path );
}

/**
 * Call right after printing a cover image with loading="eager" and fetchpriority="high"
 * (news, center and program pages). An explicit loading attribute keeps core from counting
 * the cover as content media (wp-includes/media.php, wp_get_loading_optimization_attributes()),
 * so the first three images of the body would load eagerly and compete with the cover.
 * Counting the cover as the whole above-the-fold allowance makes every body image lazy.
 * Not called when the gate refused the cover: then the first body image may be the largest
 * one on screen and must stay eager.
 */
function hcwa_theme_cover_printed(): void {
	// Core's counter (media.php, @access private, stable since 5.9); guarded in case it goes.
	if ( function_exists( 'wp_increase_content_media_count' ) && function_exists( 'wp_omit_loading_attr_threshold' ) ) {
		wp_increase_content_media_count( wp_omit_loading_attr_threshold() );
	}
}

/**
 * "tel:" target from a display phone number.
 *
 * @param string $display Phone as shown, e.g. "+84 28 3840 1406".
 */
function hcwa_theme_tel( string $display ): string {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $display );
}
