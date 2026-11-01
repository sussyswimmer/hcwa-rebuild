<?php
/**
 * Polylang (free) integration, 07-BILINGUAL:
 * - centers, programs, impact figures, reports and focus areas are translatable;
 *   partners are not (a logo and a name are the same in both languages)
 * - fields marked `sync` are copied between translations; text fields are not
 * - UI strings are registered so editors can change them in Languages → Translations
 * - language switch data, including the "only available in …" notice for untranslated posts
 */

defined( 'ABSPATH' ) || exit;

function hcwa_translated_post_types() {
	return array( 'hcwa_center', 'hcwa_program', 'hcwa_impact', 'hcwa_report' );
}

add_filter(
	'pll_get_post_types',
	function ( $types, $is_settings ) {
		foreach ( hcwa_translated_post_types() as $type ) {
			$types[ $type ] = $type;
		}
		unset( $types['hcwa_partner'] );
		return $types;
	},
	10,
	2
);

add_filter(
	'pll_get_taxonomies',
	function ( $taxonomies ) {
		$taxonomies['hcwa_area'] = 'hcwa_area';
		return $taxonomies;
	}
);

/**
 * When Polylang synchronizes (sync = true) it must only touch language-independent fields.
 * When it copies a new translation (sync = false) everything is copied as a starting point.
 */
add_filter(
	'pll_copy_post_metas',
	function ( $keys, $sync ) {
		if ( ! $sync ) {
			return $keys;
		}
		$synced = hcwa_synced_meta_keys();
		return array_values(
			array_filter(
				$keys,
				function ( $key ) use ( $synced ) {
					return 0 !== strpos( $key, 'hcwa_' ) || in_array( $key, $synced, true );
				}
			)
		);
	},
	10,
	2
);

/* ---------- UI strings ---------- */

add_action(
	'init',
	function () {
		if ( ! function_exists( 'pll_register_string' ) || ! is_admin() ) {
			return;
		}
		foreach ( hcwa_strings() as $key => $pair ) {
			if ( isset( $pair['vi'] ) ) {
				pll_register_string( $key, $pair['vi'], 'HCWA UI', strlen( $pair['vi'] ) > 80 );
			}
		}
	},
	20
);

/* ---------- language switch ---------- */

/**
 * Both languages for the VI / EN switch. Each item: url, current, lang, name, missing.
 * A post or page without a translation links to the other language's news hub (posts) or
 * homepage (anything else) with ?untranslated=ID, which shows the language notice there.
 * The visitor never lands on a dead end or silently on the wrong homepage (07-BILINGUAL).
 */
function hcwa_language_links() {
	$current = hcwa_lang();
	$names   = array( 'vi' => 'Tiếng Việt', 'en' => 'English' );
	$links   = array();
	$raw     = function_exists( 'pll_the_languages' ) ? pll_the_languages( array( 'raw' => 1, 'hide_if_no_translation' => 0, 'hide_if_empty' => 0 ) ) : array();

	foreach ( array( 'vi', 'en' ) as $lang ) {
		$url     = isset( $raw[ $lang ]['url'] ) ? $raw[ $lang ]['url'] : hcwa_url( 'home', $lang );
		$missing = false;
		if ( $lang !== $current && is_singular() && function_exists( 'pll_get_post' ) && ! pll_get_post( get_queried_object_id(), $lang ) ) {
			$missing = true;
			$hub     = 'post' === get_post_type() ? hcwa_url( 'news', $lang ) : hcwa_url( 'home', $lang );
			$url     = add_query_arg( 'untranslated', get_queried_object_id(), $hub );
		}
		// Keep the Our Work filter when switching language (?area= uses the other language's term).
		if ( $lang !== $current && ! $missing && isset( $_GET['area'] ) && function_exists( 'pll_get_term' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$term = get_term_by( 'slug', sanitize_title( wp_unslash( $_GET['area'] ) ), 'hcwa_area' ); // phpcs:ignore WordPress.Security.NonceVerification
			$tr   = $term ? pll_get_term( $term->term_id, $lang ) : 0;
			$tr   = $tr ? get_term( $tr, 'hcwa_area' ) : null;
			if ( $tr && ! is_wp_error( $tr ) ) {
				$url = add_query_arg( 'area', $tr->slug, $url );
			}
		}
		$links[ $lang ] = array(
			'lang'    => $lang,
			'name'    => $names[ $lang ],
			'url'     => $url,
			'current' => $lang === $current,
			'missing' => $missing,
		);
	}
	return $links;
}

/**
 * The post behind ?untranslated=ID, if it is a published post in the other language.
 * Used by the theme to show the language notice at the top of the page.
 */
function hcwa_untranslated_post() {
	if ( empty( $_GET['untranslated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return null;
	}
	$post = get_post( absint( $_GET['untranslated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $post || 'publish' !== $post->post_status || ! is_post_type_viewable( $post->post_type ) ) {
		return null;
	}
	$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post->ID, 'slug' ) : '';
	return $lang && $lang !== hcwa_lang() ? $post : null;
}
