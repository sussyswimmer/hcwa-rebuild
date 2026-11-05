<?php
/**
 * Bridge to the hcwa-core plugin.
 *
 * Templates call these hcwa_theme_* wrappers instead of the core functions directly.
 * With hcwa-core active they pass straight through (see wp/CONTRACT.md). Without it the
 * theme still renders a reduced page instead of a fatal error, refuses every image and
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
 * Raw HCWA setting (Settings → HCWA).
 *
 * @param string $key     Setting key.
 * @param mixed  $default Value when unset.
 * @return mixed
 */
function hcwa_theme_setting( string $key, $default = '' ) {
	if ( function_exists( 'hcwa_setting' ) ) {
		$value = hcwa_setting( $key, $default );
		return null === $value ? $default : $value;
	}
	return $default;
}

/**
 * Per-language HCWA setting (key_vi / key_en) in the current language.
 *
 * @param string $key Setting key without the language suffix.
 */
function hcwa_theme_setting_l( string $key ): string {
	return function_exists( 'hcwa_setting_l' ) ? (string) hcwa_setting_l( $key ) : '';
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
