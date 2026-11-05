<?php
/**
 * Polylang integration: which types are translated, which fields are shared between
 * translations, and the data for the VI / EN switch. Everything is guarded with
 * function_exists() so the site keeps working (in Vietnamese) without Polylang.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Forces translation on for our post types. When Polylang builds its settings screen
 * ($is_settings true) the types are removed so staff cannot switch translation off,
 * the same pattern Polylang uses for wpml-config.xml (modules/wpml/wpml-config.php).
 *
 * @param string[] $types       Post types.
 * @param bool     $is_settings True when listing types on Polylang's settings screen.
 * @return string[]
 */
function hcwa_pll_post_types( $types, $is_settings = false ) {
	$types = (array) $types;
	foreach ( hcwa_post_types() as $post_type ) {
		if ( $is_settings ) {
			unset( $types[ $post_type ] );
		} else {
			$types[ $post_type ] = $post_type;
		}
	}
	return $types;
}
add_filter( 'pll_get_post_types', 'hcwa_pll_post_types', 10, 2 );

/**
 * Same for the focus area taxonomy.
 *
 * @param string[] $taxonomies  Taxonomies.
 * @param bool     $is_settings True on Polylang's settings screen.
 * @return string[]
 */
function hcwa_pll_taxonomies( $taxonomies, $is_settings = false ) {
	$taxonomies = (array) $taxonomies;
	if ( $is_settings ) {
		unset( $taxonomies['hcwa_focus_area'] );
	} else {
		$taxonomies['hcwa_focus_area'] = 'hcwa_focus_area';
	}
	return $taxonomies;
}
add_filter( 'pll_get_taxonomies', 'hcwa_pll_taxonomies', 10, 2 );

/**
 * Shared fields are copied and kept in sync between translations; per-language text
 * fields never are (even if "Custom fields" sync is switched on in Polylang).
 *
 * @param string[] $keys Meta keys Polylang will copy.
 * @param bool     $sync True when synchronizing, false when creating a translation.
 * @param int      $from Source post ID.
 * @param int      $to   Target post ID.
 * @param string   $lang Target language.
 * @return string[]
 */
function hcwa_pll_copy_post_metas( $keys, $sync = false, $from = 0, $to = 0, $lang = '' ) {
	$keys = array_diff( (array) $keys, hcwa_meta_per_language_keys() );
	return array_values( array_unique( array_merge( $keys, hcwa_meta_sync_keys() ) ) );
}
add_filter( 'pll_copy_post_metas', 'hcwa_pll_copy_post_metas', 10, 5 );

/**
 * hreflang x-default = the Vietnamese URL on every template (07-BILINGUAL). Polylang 3.9.1
 * only adds x-default on the front page and only when the default language keeps its
 * directory (src/frontend/frontend-filters-links.php, wp_head()); our VI has no directory.
 * Polylang prints the list only when a translation exists, so 'vi' is there whenever
 * this runs with two entries.
 *
 * @param array $hreflangs hreflang code => URL.
 * @return array
 */
function hcwa_hreflang_x_default( $hreflangs ) {
	$hreflangs = (array) $hreflangs;
	if ( isset( $hreflangs['x-default'] ) ) {
		return $hreflangs;
	}
	foreach ( $hreflangs as $code => $url ) {
		if ( 'vi' === strtolower( (string) $code ) || 0 === strpos( strtolower( (string) $code ), 'vi-' ) ) {
			$hreflangs['x-default'] = $url;
			break;
		}
	}
	return $hreflangs;
}
add_filter( 'pll_rel_hreflang_attributes', 'hcwa_hreflang_x_default' );

/**
 * ID of a post's translation in a language. Without Polylang the post itself.
 * 0 when Polylang knows the post has no translation in that language.
 *
 * @param int    $post_id Post ID.
 * @param string $lang    'vi' or 'en'.
 */
function hcwa_translation_id( int $post_id, string $lang ): int {
	if ( ! $post_id ) {
		return 0;
	}
	if ( ! function_exists( 'pll_get_post' ) ) {
		return $post_id;
	}
	return (int) pll_get_post( $post_id, hcwa_normalize_lang( $lang ) );
}

/**
 * The Vietnamese original of a post (shared fields store these IDs). The post itself
 * without Polylang or when it has no Vietnamese version.
 *
 * @param int $post_id Post ID.
 */
function hcwa_original_id( int $post_id ): int {
	if ( ! $post_id || ! function_exists( 'pll_get_post' ) ) {
		return $post_id;
	}
	$vi = (int) pll_get_post( $post_id, 'vi' );
	return $vi ? $vi : $post_id;
}

/**
 * Current URL on our own host, without the hcwa_lang_notice argument.
 */
function hcwa_current_url(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed and rebuilt below.
	$path = (string) wp_parse_url( (string) $uri, PHP_URL_PATH );
	$qs   = (string) wp_parse_url( (string) $uri, PHP_URL_QUERY );
	$home = wp_parse_url( hcwa_home_base() );

	$url = ( $home['scheme'] ?? 'https' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . ( '' !== $path ? $path : '/' );
	if ( '' !== $qs ) {
		$url .= '?' . $qs;
	}

	return esc_url_raw( remove_query_arg( 'hcwa_lang_notice', $url ) );
}

/**
 * Data for the language switch (07-BILINGUAL "The switch").
 *
 * - Translated content: link to the translation.
 * - Untranslated news post: stay on the post with ?hcwa_lang_notice=<lang>, which makes
 *   the language notice explain that the article exists in one language only.
 * - Untranslated page or center/program/report: that language's home page.
 *
 * @return array<string, array{url: string, name: string, current: bool, has_translation: bool}>
 */
function hcwa_language_links(): array {
	$current = hcwa_lang();
	$pll     = array();

	if ( function_exists( 'pll_the_languages' ) && did_action( 'wp' ) ) {
		$raw = pll_the_languages(
			array(
				'raw'                    => 1,
				'echo'                   => 0,
				'hide_if_empty'          => 0,
				'hide_if_no_translation' => 0,
			)
		);
		$pll = is_array( $raw ) ? $raw : array();
	}

	$links = array();
	foreach ( hcwa_languages() as $lang ) {
		if ( $lang === $current ) {
			$url = is_singular() ? (string) get_permalink( get_queried_object_id() ) : (string) ( $pll[ $lang ]['url'] ?? '' );
			$links[ $lang ] = array(
				'url'             => '' !== $url ? $url : hcwa_current_url(),
				'name'            => hcwa_language_name( $lang ),
				'current'         => true,
				'has_translation' => true,
			);
			continue;
		}

		list( $url, $has ) = hcwa_translation_target( $lang, $pll[ $lang ] ?? array() );

		$links[ $lang ] = array(
			'url'             => $url,
			'name'            => hcwa_language_name( $lang ),
			'current'         => false,
			'has_translation' => $has,
		);
	}

	/**
	 * Filters the language switch data.
	 *
	 * @param array $links lang => [url, name, current, has_translation].
	 */
	return (array) apply_filters( 'hcwa_language_links', $links );
}

/**
 * Where the switch should send a visitor who picks $lang.
 *
 * @param string $lang    Target language.
 * @param array  $element Polylang's raw switcher element for $lang (may be empty).
 * @return array{0: string, 1: bool} URL and whether a real translation exists.
 */
function hcwa_translation_target( string $lang, array $element ): array {
	$polylang = function_exists( 'pll_get_post' );

	if ( is_front_page() ) {
		return array( hcwa_page_url( 'home', $lang ), $polylang );
	}

	if ( is_home() ) {
		return array( hcwa_page_url( 'news', $lang ), $polylang );
	}

	if ( is_singular() ) {
		$post_id = (int) get_queried_object_id();
		$tr_id   = $polylang ? (int) pll_get_post( $post_id, $lang ) : 0;

		if ( $tr_id && 'publish' === get_post_status( $tr_id ) ) {
			return array( (string) get_permalink( $tr_id ), true );
		}

		if ( 'post' === get_post_type( $post_id ) ) {
			return array( add_query_arg( 'hcwa_lang_notice', $lang, (string) get_permalink( $post_id ) ), false );
		}

		return array( hcwa_page_url( 'home', $lang ), false );
	}

	if ( ! empty( $element['url'] ) ) {
		return array( (string) $element['url'], empty( $element['no_translation'] ) );
	}

	return array( hcwa_page_url( 'home', $lang ), false );
}
