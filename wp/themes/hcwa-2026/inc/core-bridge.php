<?php
/**
 * Bridge to the hcwa-core plugin.
 *
 * Templates call these hcwa_theme_* wrappers instead of the core functions directly.
 * With hcwa-core active they pass straight through (see wp/CONTRACT.md). Without it the
 * theme still renders a reduced page instead of a fatal error, shows no photo from the
 * Media Library (templates, content and featured images; see the end of this file) and
 * shows an admin notice (inc/admin.php).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether hcwa-core is loaded. Plugins load before the theme, so this is reliable here.
 */
function hcwa_theme_core_active(): bool {
	return function_exists( 'hcwa_t' );
}

function hcwa_theme_is_production(): bool {
	if ( function_exists( 'hcwa_is_production' ) ) {
		return (bool) hcwa_is_production();
	}
	return 'production' === wp_get_environment_type();
}

/**
 * Current page language: 'vi' or 'en'.
 */
function hcwa_theme_lang(): string {
	if ( function_exists( 'hcwa_lang' ) ) {
		return 'en' === hcwa_lang() ? 'en' : 'vi';
	}
	if ( function_exists( 'pll_current_language' ) ) {
		return 'en' === pll_current_language() ? 'en' : 'vi';
	}
	return 'vi';
}

/**
 * Interface string in the current language (unescaped: escape on output).
 *
 * With hcwa-core active the theme's keys (inc/strings.php) are part of core's dictionary
 * through the 'hcwa_strings' filter, so they are registered with Polylang like the rest.
 * The local lookup below only runs without hcwa-core.
 *
 * @param string $key  Dictionary key.
 * @param array  $vars Replacements for {name} placeholders.
 */
function hcwa_theme_t( string $key, array $vars = array() ): string {
	return hcwa_theme_t_in( $key, hcwa_theme_lang(), $vars );
}

/**
 * Interface string in a given language.
 *
 * @param string $key  Dictionary key.
 * @param string $lang 'vi' or 'en'.
 * @param array  $vars Replacements for {name} placeholders.
 */
function hcwa_theme_t_in( string $key, string $lang, array $vars = array() ): string {
	$lang = 'en' === $lang ? 'en' : 'vi';
	$text = '';
	if ( function_exists( 'hcwa_t_in' ) ) {
		$text = (string) hcwa_t_in( $key, $lang, $vars );
	} elseif ( function_exists( 'hcwa_t' ) && hcwa_theme_lang() === $lang ) {
		$text = (string) hcwa_t( $key, $vars );
	}
	if ( '' !== $text && $text !== $key ) {
		return $text;
	}

	$strings = hcwa_theme_strings();
	if ( isset( $strings[ $key ] ) ) {
		return hcwa_theme_fill( $strings[ $key ][ $lang ] ?? $strings[ $key ]['vi'], $vars );
	}
	return '' !== $text ? $text : $key;
}

/**
 * Replaces {name} placeholders. Pure helper (tested in wp/tests/theme-helpers-test.php).
 *
 * @param string $text Text with placeholders.
 * @param array  $vars name => value.
 */
function hcwa_theme_fill( string $text, array $vars ): string {
	foreach ( $vars as $name => $value ) {
		$text = str_replace( '{' . $name . '}', (string) $value, $text );
	}
	return $text;
}

/**
 * Prints an escaped interface string.
 *
 * @param string $key  Dictionary key.
 * @param array  $vars Replacements for {name} placeholders.
 */
function hcwa_theme_e( string $key, array $vars = array() ): void {
	echo esc_html( hcwa_theme_t( $key, $vars ) );
}

/**
 * Default page paths, mirroring prototype/src/routes.mjs (and core's hcwa_page_paths()).
 * Only used when hcwa-core is off.
 *
 * @return array<string, array{0: string, 1: string}> key => [vi path, en path]
 */
function hcwa_theme_default_paths(): array {
	return array(
		'home'        => array( '/', '/en/' ),
		'about'       => array( '/ve-chung-toi/', '/en/about/' ),
		'ourStory'    => array( '/ve-chung-toi/cau-chuyen/', '/en/about/our-story/' ),
		'team'        => array( '/ve-chung-toi/doi-ngu/', '/en/about/team/' ),
		'partners'    => array( '/ve-chung-toi/doi-tac/', '/en/about/partners/' ),
		'policies'    => array( '/ve-chung-toi/chinh-sach/', '/en/about/policies/' ),
		'ourWork'     => array( '/hoat-dong/', '/en/our-work/' ),
		'impact'      => array( '/tac-dong/', '/en/impact/' ),
		'news'        => array( '/tin-tuc/', '/en/news/' ),
		'getInvolved' => array( '/chung-tay/', '/en/get-involved/' ),
		'donate'      => array( '/chung-tay/ung-ho/', '/en/get-involved/donate/' ),
		'volunteer'   => array( '/chung-tay/tinh-nguyen/', '/en/get-involved/volunteer/' ),
		'partner'     => array( '/chung-tay/doi-tac-doanh-nghiep/', '/en/get-involved/partner/' ),
		'inKind'      => array( '/chung-tay/hien-vat/', '/en/get-involved/in-kind/' ),
		'contact'     => array( '/lien-he/', '/en/contact/' ),
	);
}

/**
 * Links that point at a section of a page (prototype routes.mjs). The page patterns
 * must carry these ids. Only used when hcwa-core is off; core has the same table.
 *
 * @return array<string, array{0: string, 1: string, 2: string}> key => [page key, vi anchor, en anchor]
 */
function hcwa_theme_anchor_routes(): array {
	return array(
		'reports'      => array( 'impact', 'bao-cao', 'reports' ),
		'safeguarding' => array( 'policies', 'bao-ve-tre-em', 'safeguarding' ),
		'privacy'      => array( 'policies', 'quyen-rieng-tu', 'privacy' ),
		'bakery'       => array( 'inKind', 'tiem-banh', 'bakery' ),
		'centers'      => array( 'ourWork', 'co-so', 'centers' ),
		'programs'     => array( 'ourWork', 'du-an', 'programs' ),
	);
}

/**
 * URL of a logical page (contract page keys) or of a page section (anchor routes above).
 *
 * @param string      $key  Page key.
 * @param string|null $lang 'vi', 'en' or null for the current language.
 */
function hcwa_theme_url( string $key, ?string $lang = null ): string {
	$lang = 'en' === ( $lang ?? hcwa_theme_lang() ) ? 'en' : 'vi';
	if ( function_exists( 'hcwa_page_url' ) ) {
		// Core knows the page map (Settings → HCWA) and the anchor keys too.
		return (string) hcwa_page_url( $key, $lang );
	}
	$anchors = hcwa_theme_anchor_routes();
	if ( isset( $anchors[ $key ] ) ) {
		return hcwa_theme_url( $anchors[ $key ][0], $lang ) . '#' . ( 'en' === $lang ? $anchors[ $key ][2] : $anchors[ $key ][1] );
	}
	$paths = hcwa_theme_default_paths();
	$path  = $paths[ $key ][ 'en' === $lang ? 1 : 0 ] ?? $paths['home'][ 'en' === $lang ? 1 : 0 ];
	return untrailingslashit( (string) get_option( 'home' ) ) . $path;
}

/**
 * Language links for the VI / EN switch, always both languages, always the same shape.
 *
 * @return array<string, array{url: string, name: string, current: bool, has_translation: bool}>
 */
function hcwa_theme_language_links(): array {
	$names   = array(
		'vi' => 'Tiếng Việt',
		'en' => 'English',
	);
	$current = hcwa_theme_lang();
	$raw     = array();

	if ( function_exists( 'hcwa_language_links' ) ) {
		$raw = (array) hcwa_language_links();
	} elseif ( function_exists( 'pll_the_languages' ) ) {
		foreach ( (array) pll_the_languages( array( 'raw' => 1 ) ) as $slug => $lang ) {
			$raw[ $slug ] = array(
				'url'             => $lang['url'] ?? '',
				'name'            => $lang['name'] ?? '',
				'current'         => ! empty( $lang['current_lang'] ),
				'has_translation' => empty( $lang['no_translation'] ),
			);
		}
	}

	$links = array();
	foreach ( $names as $code => $name ) {
		$item           = isset( $raw[ $code ] ) ? (array) $raw[ $code ] : array();
		$links[ $code ] = array(
			'url'             => (string) ( $item['url'] ?? $item[0] ?? hcwa_theme_url( 'home', $code ) ),
			'name'            => (string) ( $item['name'] ?? $item[1] ?? $name ),
			'current'         => (bool) ( $item['current'] ?? $item[2] ?? ( $code === $current ) ),
			'has_translation' => (bool) ( $item['has_translation'] ?? $item[3] ?? true ),
		);
		if ( '' === $links[ $code ]['url'] ) {
			$links[ $code ]['url'] = hcwa_theme_url( 'home', $code );
		}
	}
	return $links;
}

/**
 * Whether text still holds a draft marker ("[DRAFT: …]" and the other markers core knows).
 *
 * @param string $text Text.
 */
function hcwa_theme_contains_draft( string $text ): bool {
	if ( function_exists( 'hcwa_contains_draft' ) ) {
		return (bool) hcwa_contains_draft( $text );
	}
	return false !== stripos( $text, '[DRAFT' );
}

/**
 * Text that did not pass the publish guard (settings, captions, the tagline, a translation's
 * title) as it may be printed: '' on the live site while it holds a draft marker (CLAUDE.md,
 * contract rule 4), unchanged on staging, where hcwa_theme_draft_attr() outlines it.
 *
 * @param string $text Text.
 */
function hcwa_theme_public_text( string $text ): string {
	return hcwa_theme_is_production() && hcwa_theme_contains_draft( $text ) ? '' : $text;
}

/**
 * The current page's own description for <meta name=description> and og:description, as
 * the prototype prints it: the intro of a sections page (the front page included), a
 * center's "who we serve", a program's summary (hcwa-core hcwa_page_description()), and the
 * 404 text on the not-found page. Plain text, without draft brackets off production and ''
 * on production while draft. '' for other pages and without hcwa-core.
 */
function hcwa_theme_own_description(): string {
	if ( is_404() ) {
		return hcwa_theme_t( 'nf_text' );
	}
	if ( ! function_exists( 'hcwa_page_description' ) ) {
		return '';
	}
	$post = is_singular() || is_front_page() ? get_queried_object() : null;
	if ( ! $post instanceof WP_Post && is_front_page() ) {
		$post = get_post( (int) get_option( 'page_on_front' ) ); // Polylang gives the page in the current language.
	}
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	$description = hcwa_page_description( $post );
	return trim( (string) ( $description['text'] ?? '' ) );
}

/**
 * ' data-draft' for an element whose text holds a draft marker (staging outline), else ''.
 *
 * @param string $text Text the element prints.
 */
function hcwa_theme_draft_attr( string $text ): string {
	return hcwa_theme_contains_draft( $text ) ? ' data-draft' : '';
}

/**
 * HCWA setting (Settings → HCWA). Text holding a draft marker counts as unset on the live site.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Value when unset.
 * @return mixed
 */
function hcwa_theme_setting( string $key, $default = '' ) {
	if ( function_exists( 'hcwa_setting' ) ) {
		$value = hcwa_setting( $key, $default );
		if ( is_string( $value ) && '' === hcwa_theme_public_text( $value ) ) {
			return $default;
		}
		return null === $value ? $default : $value;
	}
	return $default;
}

/**
 * Per-language HCWA setting (key_vi / key_en) in the current language; '' on the live site
 * while it holds a draft marker.
 *
 * @param string $key Setting key without the language suffix.
 */
function hcwa_theme_setting_l( string $key ): string {
	return function_exists( 'hcwa_setting_l' ) ? hcwa_theme_public_text( (string) hcwa_setting_l( $key ) ) : '';
}

/**
 * Image through the core approval gate. Returns '' when refused or when hcwa-core is off:
 * without the gate the theme cannot know whether a photo is approved, so it shows none.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Image size.
 * @param array  $attr          Extra attributes for the img tag.
 * @param mixed  $context       The post being rendered (center, program or story post).
 */
function hcwa_theme_image( int $attachment_id, string $size, array $attr = array(), $context = null ): string {
	if ( $attachment_id <= 0 || ! function_exists( 'hcwa_image' ) ) {
		return '';
	}
	return (string) hcwa_image( $attachment_id, $size, $attr, $context );
}

/**
 * Library IDs a center shows: featured image, logo and gallery (contract meta).
 *
 * @param int $center_id Center ID.
 * @return int[]
 */
function hcwa_theme_center_media_ids( int $center_id ): array {
	return hcwa_theme_parse_ids(
		array_merge(
			array( get_post_meta( $center_id, '_thumbnail_id', true ), get_post_meta( $center_id, 'hcwa_logo_id', true ) ),
			hcwa_theme_parse_ids( get_post_meta( $center_id, 'hcwa_gallery_ids', true ) )
		)
	);
}

/**
 * Whether an attachment page must treat its photo as being in a no-identifiable context.
 * The parent alone is not enough: photos are picked from the library, so a Little Rose
 * gallery photo can be unattached or attached to another post. It counts when its parent is
 * such a context, when a center marked "No identifiable children" shows it (featured image,
 * logo or gallery), or when it is the featured image of a post, center or program that is
 * such a context. Without hcwa-core: true (the strictest answer).
 *
 * @param int $attachment_id Attachment ID.
 */
function hcwa_theme_attachment_no_identifiable( int $attachment_id ): bool {
	if ( ! function_exists( 'hcwa_image_context_no_identifiable' ) ) {
		return true;
	}
	// hcwa-core 0.1.1+ answers this itself (and refuses such pages without the theme).
	if ( function_exists( 'hcwa_attachment_no_identifiable' ) ) {
		return (bool) hcwa_attachment_no_identifiable( $attachment_id );
	}

	$attachment = get_post( $attachment_id );
	$parent     = $attachment instanceof WP_Post ? (int) $attachment->post_parent : 0;
	if ( $parent > 0 && hcwa_image_context_no_identifiable( $parent ) ) {
		return true;
	}

	// The photo and its Polylang media translations: shared meta stores the VI original's ID.
	$ids = array( $attachment_id );
	if ( function_exists( 'pll_get_post_translations' ) ) {
		$ids = hcwa_theme_parse_ids( array_merge( $ids, array_values( (array) pll_get_post_translations( $attachment_id ) ) ) );
	}

	$query = array(
		'post_status'      => 'any',
		'numberposts'      => 50,
		'suppress_filters' => false,
		'lang'             => '', // Every language (Polylang).
	);

	$centers = get_posts(
		$query + array(
			'post_type'  => 'hcwa_center',
			'fields'     => 'ids',
			'meta_key'   => 'hcwa_no_identifiable_children', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $centers as $center_id ) {
		if ( array_intersect( $ids, hcwa_theme_center_media_ids( (int) $center_id ) ) ) {
			return true;
		}
	}

	$users = get_posts(
		$query + array(
			'post_type'  => array( 'post', 'hcwa_center', 'hcwa_program' ),
			'fields'     => 'ids',
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
 * Whether an attachment page must not exist: an image the approval gate refuses in its
 * context (hcwa_theme_attachment_no_identifiable()), or any image without hcwa-core.
 * inc/setup.php turns such a page into a 404 that says nothing about the file.
 *
 * @param int $attachment_id Attachment ID.
 */
function hcwa_theme_attachment_refused( int $attachment_id ): bool {
	if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}
	if ( ! function_exists( 'hcwa_image_allowed' ) ) {
		return true;
	}
	return ! hcwa_image_allowed( $attachment_id, hcwa_theme_attachment_no_identifiable( $attachment_id ) );
}

/**
 * ID of a post's translation in $lang when it is published, else 0 (the post itself, no
 * translation, or one that is still a draft). Same rule as core's language switch.
 *
 * @param int    $post_id Post ID.
 * @param string $lang    'vi' or 'en'.
 */
function hcwa_theme_published_translation( int $post_id, string $lang ): int {
	$translation = function_exists( 'hcwa_translation_id' ) ? (int) hcwa_translation_id( $post_id, $lang ) : 0;
	if ( $translation <= 0 || $translation === $post_id ) {
		return 0;
	}
	return 'publish' === get_post_status( $translation ) ? $translation : 0;
}

/**
 * Localized date: 08/02/2026 (vi) or 8 Feb 2026 (en).
 *
 * @param string $ymd Date as Y-m-d.
 */
function hcwa_theme_date( string $ymd ): string {
	if ( function_exists( 'hcwa_date' ) ) {
		return (string) hcwa_date( $ymd );
	}
	$time = strtotime( $ymd . ' 00:00:00' );
	if ( false === $time ) {
		return $ymd;
	}
	return 'en' === hcwa_theme_lang() ? gmdate( 'j M Y', $time ) : gmdate( 'd/m/Y', $time );
}

/**
 * Title for cards and headings with any leading emoji removed (05-DESIGN: no emoji in UI).
 *
 * @param string $title Title text.
 */
function hcwa_theme_clean_title( string $title ): string {
	if ( function_exists( 'hcwa_strip_leading_emoji' ) ) {
		return (string) hcwa_strip_leading_emoji( $title );
	}
	return hcwa_theme_strip_leading_emoji( $title );
}

/**
 * Local copy of the emoji strip, used only without hcwa-core. Pure helper (tested).
 *
 * @param string $text Text that may start with emoji.
 */
function hcwa_theme_strip_leading_emoji( string $text ): string {
	$stripped = preg_replace( '/^(?:[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]|\s)+/u', '', $text );
	return null === $stripped ? $text : $stripped;
}

/**
 * Renders one of the hcwa/* dynamic blocks from PHP. Returns '' if the block is not registered.
 *
 * @param string $name  Block name, e.g. 'hcwa/impact-strip'.
 * @param array  $attrs Block attributes (contract names only).
 */
function hcwa_theme_block( string $name, array $attrs = array() ): string {
	if ( ! class_exists( 'WP_Block_Type_Registry' ) || ! WP_Block_Type_Registry::get_instance()->is_registered( $name ) ) {
		return '';
	}
	return trim(
		render_block(
			array(
				'blockName'    => $name,
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		)
	);
}

/**
 * Translation of a post (sync meta stores the VI original) in the current language.
 * Falls back to the original when there is no translation.
 *
 * @param int $post_id Post ID.
 */
function hcwa_theme_translated_id( int $post_id ): int {
	if ( $post_id > 0 && function_exists( 'pll_get_post' ) ) {
		$translated = (int) pll_get_post( $post_id, hcwa_theme_lang() );
		if ( $translated > 0 ) {
			return $translated;
		}
	}
	return $post_id;
}

/*
 * Without hcwa-core there is no approval gate, so no photo from the Media Library may render.
 * Templates already get '' from hcwa_theme_image(); these filters do the same for photos
 * placed in content (core/image blocks and gallery blocks, classic HTML, cover and media-text
 * images: core finds their library ID in the wp-image-N class), classic [gallery] shortcodes
 * and featured images printed by any other code. Plugins load before the theme, so the
 * check is reliable here.
 */
if ( ! hcwa_theme_core_active() ) {
	add_filter( 'render_block_core/image', 'hcwa_theme_drop_library_image_block', 10, 2 );
	add_filter( 'wp_content_img_tag', 'hcwa_theme_drop_library_img_tag', 10, 3 );
	add_filter( 'post_gallery', 'hcwa_theme_drop_gallery' );
	add_filter( 'post_thumbnail_html', '__return_empty_string' );
}

/**
 * core/image block without hcwa-core: the whole figure goes when it shows a library photo.
 *
 * @param string $block_content Rendered block.
 * @param array  $block         Parsed block.
 * @return string
 */
function hcwa_theme_drop_library_image_block( $block_content, $block ) {
	return (int) ( $block['attrs']['id'] ?? 0 ) > 0 ? '' : $block_content;
}

/**
 * img tag in content without hcwa-core: removed when it is a library photo.
 *
 * @param string $image         img tag.
 * @param string $context       Where it is printed.
 * @param int    $attachment_id Library ID or 0 (external image).
 * @return string
 */
function hcwa_theme_drop_library_img_tag( $image, $context, $attachment_id ) {
	return (int) $attachment_id > 0 ? '' : $image;
}

/**
 * Classic [gallery] shortcode without hcwa-core: its images carry no wp-image-N class, so
 * the img filter above cannot see them. A non-empty return replaces the gallery.
 *
 * @param string $output Gallery HTML ('' lets core build it).
 * @return string
 */
function hcwa_theme_drop_gallery( $output ) {
	return ' ';
}

/* -------------------------------------------------------------------------
 * Bridge to the port's core builders (11-WP-PORT.md 2.6). Each degrades to plain markup or
 * nothing when hcwa-core is inactive.
 * ---------------------------------------------------------------------- */

/**
 * A text as it prints (2.1 rule 7): ['text' => …, 'draft' => bool]. Without hcwa-core a
 * text with a draft marker prints nowhere on the live site.
 *
 * @param string $text Stored text.
 * @return array{text: string, draft: bool}
 */
function hcwa_theme_draft_text( string $text ): array {
	if ( function_exists( 'hcwa_draft_text' ) && function_exists( 'hcwa_attr_text' ) ) {
		return hcwa_draft_text( hcwa_attr_text( $text ) );
	}
	$draft = hcwa_theme_contains_draft( $text );
	return array(
		'text'  => $draft && hcwa_theme_is_production() ? '' : trim( wp_strip_all_tags( $text ) ),
		'draft' => $draft,
	);
}

/**
 * Heading text with the no-break glossary (plain text; escape at output).
 *
 * @param string $text Plain text.
 */
function hcwa_theme_vi_keep( string $text ): string {
	return function_exists( 'hcwa_vi_keep' ) ? hcwa_vi_keep( $text ) : $text;
}

/**
 * The Hoa thị quatrefoil SVG ('' without hcwa-core).
 *
 * @param string $cls Class.
 */
function hcwa_theme_quatrefoil( string $cls ): string {
	return function_exists( 'hcwa_quatrefoil' ) ? hcwa_quatrefoil( $cls ) : '';
}

/**
 * One contact line through hcwa_contact_line(); empty without hcwa-core.
 *
 * @param string $which phone | email | sponsorship.
 * @return array{text: string, href: string, draft: bool}
 */
function hcwa_theme_contact_line( string $which ): array {
	if ( function_exists( 'hcwa_contact_line' ) ) {
		return hcwa_contact_line( $which );
	}
	return array(
		'text'  => '',
		'href'  => '',
		'draft' => true,
	);
}

/**
 * The pause toggle (WCAG 2.2.2).
 *
 * @param string $cls       Extra class.
 * @param string $text_safe data-text-safe value.
 */
function hcwa_theme_pause_toggle( string $cls = '', string $text_safe = '' ): string {
	return function_exists( 'hcwa_pause_toggle_html' ) ? hcwa_pause_toggle_html( $cls, $text_safe ) : '';
}

/**
 * Breadcrumb: Home, then the trail.
 *
 * @param array $trail [label, url] pairs, the last without url.
 */
function hcwa_theme_breadcrumb( array $trail ): string {
	return function_exists( 'hcwa_breadcrumb_html' ) ? hcwa_breadcrumb_html( $trail ) : '';
}

/**
 * The sun head of a WordPress-only page (2.7): breadcrumb and H1.
 *
 * @param array $args title, trail, intro.
 */
function hcwa_theme_sun_head( array $args ): string {
	if ( function_exists( 'hcwa_sun_head_html' ) ) {
		return hcwa_sun_head_html( $args );
	}
	return '<header class="sun-head"><div class="container sun-head__inner"><h1 class="sun-head__title">' . esc_html( (string) ( $args['title'] ?? '' ) ) . '</h1></div></header>';
}

/**
 * A named still from the art provider, '' when not ready or without hcwa-core.
 *
 * @param string $name Still name.
 * @param array  $o    cls, sizes, eager.
 */
function hcwa_theme_still( string $name, array $o = array() ): string {
	return function_exists( 'hcwa_art' ) ? hcwa_art()->still( $name, $o ) : '';
}

/**
 * A page built from hcwa/sections (2.1 rule 4); '' without hcwa-core or without the block.
 *
 * @param WP_Post $post Page.
 */
function hcwa_theme_render_sections( WP_Post $post ): string {
	return function_exists( 'hcwa_render_sections' ) ? hcwa_render_sections( $post ) : '';
}

/**
 * Whether a page is a sections page.
 *
 * @param WP_Post|null $post Page.
 */
function hcwa_theme_is_sections_page( ?WP_Post $post ): bool {
	return function_exists( 'hcwa_is_sections_page' ) && hcwa_is_sections_page( $post );
}

/**
 * A post as a dated list row (2.6).
 *
 * @param WP_Post $post Post.
 */
function hcwa_theme_dated_post( WP_Post $post ): string {
	return function_exists( 'hcwa_dated_post_html' ) ? hcwa_dated_post_html( $post ) : '';
}

/**
 * A report as an li.doc row.
 *
 * @param WP_Post $report Report.
 */
function hcwa_theme_doc_row( WP_Post $report ): string {
	if ( function_exists( 'hcwa_doc_row_html' ) && function_exists( 'hcwa_report_view' ) ) {
		return hcwa_doc_row_html( hcwa_report_view( $report ) );
	}
	return '';
}

/**
 * The orgName and shortName of the site in the current language (2.3, 1.2 #4).
 *
 * @return array{org: string, short: string}
 */
function hcwa_theme_names(): array {
	$org   = trim( wp_strip_all_tags( (string) get_bloginfo( 'name' ) ) );
	$short = hcwa_theme_setting_l( 'short_name' );
	return array(
		'org'   => $org,
		'short' => '' !== $short ? $short : $org,
	);
}

/**
 * The page context (11-WP-PORT.md 6.4), decided once per request before <html> prints:
 * body class, whether the page opens on a lit header, carries the world, starts with the
 * header over the hero.
 *
 * @return array{lit: bool, world: bool, header_over: bool, body_class: string}
 */
function hcwa_theme_page_context(): array {
	static $context = null;
	if ( null !== $context && ! defined( 'HCWA_TESTS' ) ) {
		return $context;
	}
	$context = array(
		'lit'         => false,
		'world'       => false,
		'header_over' => false,
		'body_class'  => 'page-wp',
	);
	if ( is_404() ) {
		$context['body_class'] = 'page-gap';
	} elseif ( is_front_page() && ! is_home() ) {
		$context = array(
			'lit'         => true,
			'world'       => true,
			'header_over' => true,
			'body_class'  => 'page-home',
		);
	} elseif ( is_singular( 'hcwa_center' ) || is_singular( 'hcwa_program' ) ) {
		$context['lit']        = true;
		$context['body_class'] = is_singular( 'hcwa_center' ) ? 'page-center' : 'page-program';
	} elseif ( is_page() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && hcwa_theme_is_sections_page( $post ) ) {
			$context = array_merge( $context, hcwa_theme_sections_context( (string) $post->post_content ) );
		}
	}
	return $context;
}

/**
 * Page class and lit flag of a sections page, from its first blocks: hcwa/lit-header first
 * means lit, hcwa/sun-head first means not lit, the first hcwa/day's variant gives the class.
 *
 * @param string $content Post content.
 * @return array{lit: bool, body_class: string}
 */
function hcwa_theme_sections_context( string $content ): array {
	$classes = array(
		'hub'     => 'page-work',
		'involve' => 'page-involve',
		'donate'  => 'page-donate',
		'contact' => 'page-contact',
		'gap'     => 'page-gap',
	);
	$out     = array(
		'lit'        => false,
		'body_class' => 'page-wp',
	);
	$blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
	foreach ( $blocks as $block ) {
		if ( 'hcwa/sections' !== ( $block['blockName'] ?? '' ) ) {
			continue;
		}
		$first = true;
		foreach ( (array) $block['innerBlocks'] as $inner ) {
			$name = (string) ( $inner['blockName'] ?? '' );
			if ( $first ) {
				$out['lit'] = 'hcwa/lit-header' === $name;
				$first      = false;
			}
			if ( 'hcwa/day' === $name ) {
				$variant           = (string) ( $inner['attrs']['variant'] ?? 'involve' );
				$out['body_class'] = $classes[ $variant ] ?? 'page-wp';
				break;
			}
		}
		break;
	}
	return $out;
}
