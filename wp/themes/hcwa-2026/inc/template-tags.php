<?php
/**
 * Small markup helpers shared by templates (ports of prototype/src/partials/components.mjs).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lucide outline icon (ISC), 24px grid, 1.5px stroke. Static markup, safe to echo.
 *
 * @param string $name  Icon name.
 * @param string $class CSS class.
 */
function hcwa_theme_icon( string $name, string $class = 'icon' ): string {
	$paths = array(
		'arrow'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'menu'     => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
		'close'    => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'copy'     => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
		'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'     => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'pin'      => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
		'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
		'prev'     => '<path d="m15 18-6-6 6-6"/>',
		'next'     => '<path d="m9 18 6-6-6-6"/>',
		'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'message'  => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
		'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
		'share'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.59 13.51 6.83 3.98"/><path d="m15.41 6.51-6.82 3.98"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
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
 * VI / EN switch: text not flags, aria-current on the current one, lang + hreflang on each.
 * The visible "VI" stays inside the accessible name (WCAG 2.5.3): "VI (Tiếng Việt)".
 *
 * @param string $class Extra class.
 */
function hcwa_theme_lang_switch( string $class = '' ): string {
	$parts = array();
	foreach ( hcwa_theme_language_links() as $code => $link ) {
		$parts[] = sprintf(
			'<a href="%1$s" lang="%2$s" hreflang="%2$s"%3$s>%4$s<span class="sr-only"> (%5$s)</span></a>',
			esc_url( $link['url'] ),
			esc_attr( $code ),
			$link['current'] ? ' aria-current="true"' : '',
			esc_html( strtoupper( $code ) ),
			esc_html( $link['name'] )
		);
	}
	return sprintf(
		'<div class="%s" role="group" aria-label="%s">%s</div>',
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
 * Category slugs per logical key, VI and EN (contract: seeded categories).
 *
 * @return array<string, string[]>
 */
function hcwa_theme_category_slugs(): array {
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
	foreach ( hcwa_theme_category_slugs() as $key => $known ) {
		if ( array_intersect( $slugs, $known ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * Seeded category in the current language, or null.
 *
 * @param string $key news | stories | events | announcements.
 */
function hcwa_theme_category( string $key ): ?WP_Term {
	$slugs = hcwa_theme_category_slugs()[ $key ] ?? array();
	if ( 'en' !== hcwa_theme_lang() ) {
		$slugs = array_reverse( $slugs );
	}
	foreach ( $slugs as $slug ) {
		$term = hcwa_theme_term_in_lang( 'category', $slug );
		if ( $term ) {
			return $term;
		}
	}
	return null;
}

/**
 * A term looked up by slug in any language, returned in the current language.
 *
 * @param string $taxonomy Taxonomy.
 * @param string $slug     Term slug (sync meta stores the VI one).
 */
function hcwa_theme_term_in_lang( string $taxonomy, string $slug ): ?WP_Term {
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
	$term = $terms[0];
	if ( function_exists( 'pll_get_term' ) ) {
		$translated = (int) pll_get_term( $term->term_id, hcwa_theme_lang() );
		if ( $translated > 0 && $translated !== $term->term_id ) {
			$other = get_term( $translated, $taxonomy );
			if ( $other instanceof WP_Term ) {
				return $other;
			}
		}
	}
	return $term;
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
 * Whether a post is in the stories category (adds the "names changed" note).
 *
 * @param WP_Post $post Post.
 */
function hcwa_theme_is_story( WP_Post $post ): bool {
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
	$name = wp_strip_all_tags( get_the_title( $vi_id ) );
	return $name === wp_strip_all_tags( get_the_title( $post_id ) ) ? '' : $name;
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
	$path     = $file_id > 0 ? get_attached_file( $file_id ) : false;
	$size     = ( $path && file_exists( $path ) ) ? (string) size_format( (int) filesize( $path ), 1 ) : '';
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
 * "tel:" target from a display phone number.
 *
 * @param string $display Phone as shown, e.g. "+84 28 3840 1406".
 */
function hcwa_theme_tel( string $display ): string {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $display );
}
