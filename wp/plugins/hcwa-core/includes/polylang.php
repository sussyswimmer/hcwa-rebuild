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
 * The rule: Polylang's "Custom fields" sync stays off, and the only custom fields kept in
 * sync are hcwa_meta_sync_keys(). With that option on, Polylang 3.9.1 puts every public
 * (unprotected) meta key of both posts in the sync list (src/modules/sync/
 * sync-post-metas.php:51-61), which would overwrite per-language text from other plugins
 * and core's block `footnotes`. So when synchronizing ($sync true), public keys other than
 * ours are dropped; protected keys stay, because they only reach the list when Polylang
 * or a plugin adds them on purpose (_thumbnail_id, _wp_page_template, the attachment
 * file keys, Yoast's primary category; :64-75 and integrations/wpseo/wpseo.php:431-461).
 * Keys from wpml-config.xml are added after this filter (priority 20,
 * modules/wpml/wpml-config.php:133) and are not affected. Copying into a new translation
 * ($sync false) keeps other plugins' keys as Polylang chose them, except `footnotes`,
 * whose text belongs to the source language's content. Yoast SEO's texts cannot be
 * dropped here: Polylang adds them in its own filter, hooked later on pll_init at the same
 * priority (polylang src/integrations/wpseo/load.php:12-19, wpseo.php:432-435), so
 * hcwa_pll_drop_seo_text() (seo.php) removes them at priority 15.
 *
 * @param string[] $keys Meta keys Polylang will copy.
 * @param bool     $sync True when synchronizing, false when creating a translation.
 * @param int      $from Source post ID.
 * @param int      $to   Target post ID.
 * @param string   $lang Target language.
 * @return string[]
 */
function hcwa_pll_copy_post_metas( $keys, $sync = false, $from = 0, $to = 0, $lang = '' ) {
	$shared = hcwa_meta_sync_keys();
	$keys   = array_diff( array_map( 'strval', (array) $keys ), hcwa_meta_per_language_keys(), array( 'footnotes' ) );
	if ( $sync ) {
		$keys = array_filter(
			$keys,
			static function ( string $key ) use ( $shared ): bool {
				if ( in_array( $key, $shared, true ) ) {
					return true;
				}
				return function_exists( 'is_protected_meta' ) ? is_protected_meta( $key ) : ( '' !== $key && '_' === $key[0] );
			}
		);
	}
	return array_values( array_unique( array_merge( $keys, $shared ) ) );
}
add_filter( 'pll_copy_post_metas', 'hcwa_pll_copy_post_metas', 10, 5 );

/**
 * Translated term archives link to their translation even while it has no posts yet.
 * Polylang hides the link to an empty translated archive by default (src/frontend/
 * frontend-links.php:117-129, `$hide = ! $count`), so the seeded Announcements category,
 * and any new category until its first post, would print no hreflang (frontend-filters-
 * links.php:205-216 needs two URLs) and the VI / EN switch would fall back to the home
 * page. 07-BILINGUAL asks for vi, en and x-default on every archive. Only the term branch
 * passes `taxonomy` in $args; date, author and post type archives keep Polylang's rule.
 *
 * @param bool   $hide True to hide the translation URL (default: the archive is empty).
 * @param string $lang Language slug of the translation.
 * @param array  $args Arguments Polylang used to count the posts.
 * @return bool
 */
function hcwa_pll_show_empty_term_archive( $hide, $lang = '', $args = array() ) {
	return ( is_array( $args ) && ! empty( $args['taxonomy'] ) ) ? false : (bool) $hide;
}
add_filter( 'pll_hide_archive_translation_url', 'hcwa_pll_show_empty_term_archive', 10, 3 );

/**
 * The Vietnamese original is the source of shared fields. On every save of a translation,
 * Polylang copies the translation's shared fields to the original (pll_save_post, priority
 * 10, src/modules/sync/sync-metas.php save_object() and copy(), which also deletes values
 * the translation lacks). A translation created separately and then linked in the Languages
 * box has none or only defaults, so that copy would wipe the original's gallery, contact
 * details and so on. Running first, this gives the translation the original's values, so
 * Polylang's copy changes nothing. The details box shows shared fields read-only on
 * translations (meta.php), so nothing typed there is lost.
 *
 * @param int     $post_id      Post being saved.
 * @param WP_Post $post         Post.
 * @param int[]   $translations Language => post ID, as Polylang passes them.
 */
function hcwa_pll_seed_shared_meta( $post_id, $post, $translations = array() ): void {
	$post_id  = (int) $post_id;
	$original = (int) ( ( (array) $translations )['vi'] ?? 0 );
	if ( ! $post instanceof WP_Post || ! $original || $original === $post_id || ! in_array( $post->post_type, hcwa_post_types(), true ) ) {
		return;
	}

	foreach ( hcwa_meta_fields()[ $post->post_type ] ?? array() as $key => $spec ) {
		if ( ! $spec['sync'] ) {
			continue;
		}
		if ( metadata_exists( 'post', $original, $key ) ) {
			$value = get_post_meta( $original, $key, true );
			if ( get_post_meta( $post_id, $key, true ) !== $value || ! metadata_exists( 'post', $post_id, $key ) ) {
				update_post_meta( $post_id, $key, wp_slash( $value ) );
			}
		} elseif ( metadata_exists( 'post', $post_id, $key ) ) {
			delete_post_meta( $post_id, $key );
		}
	}
}
add_action( 'pll_save_post', 'hcwa_pll_seed_shared_meta', 5, 3 );

/**
 * Polylang's canonical redirect (template_redirect priority 4, src/frontend/canonical.php)
 * takes the language from the post or term but only swaps the /en/ prefix. A center,
 * program or report requested under the other language's base (an old redirect target,
 * or a hand-written link reusing the Vietnamese slug) would be sent to a URL no rule
 * matches, and an old /category/x/ URL would never reach /tin-tuc/x/ or /en/news/x/.
 * For these objects the canonical URL is their own permalink, so Polylang issues one 301
 * to the right base. Its chained-redirect check calls this filter again with the same
 * query and gets the same answer, so it never loops.
 *
 * @param string|false $redirect_url URL Polylang would redirect to, or false.
 * @param mixed        $language     Language Polylang detected (unused).
 * @return string|false
 */
function hcwa_pll_check_canonical_url( $redirect_url, $language = null ) {
	if ( ! $redirect_url || is_feed() || is_embed() || is_preview() ) {
		return $redirect_url;
	}
	$target = hcwa_localized_canonical_target();
	return '' === $target ? $redirect_url : hcwa_canonical_with_request( $target, (string) $redirect_url );
}
add_filter( 'pll_check_canonical_url', 'hcwa_pll_check_canonical_url', 10, 2 );

/**
 * Permalink of the queried center, program, report (with its page number), attachment
 * of one of those, or category (with its page number); '' for anything else.
 */
function hcwa_localized_canonical_target(): string {
	$types = array( 'hcwa_center', 'hcwa_program', 'hcwa_report' );
	$id    = (int) get_queried_object_id();

	if ( is_singular( $types ) || ( is_attachment() && in_array( get_post_type( (int) wp_get_post_parent_id( $id ) ), $types, true ) ) ) {
		$url  = (string) get_permalink( $id );
		$page = (int) get_query_var( 'page' );
		return ( '' !== $url && $page > 1 && ! is_attachment() ) ? user_trailingslashit( trailingslashit( $url ) . $page, 'single_paged' ) : $url;
	}

	if ( is_category() ) {
		$url = get_term_link( get_queried_object() );
		if ( ! is_string( $url ) ) {
			return '';
		}
		$paged = (int) get_query_var( 'paged' );
		return $paged > 1 ? user_trailingslashit( trailingslashit( $url ) . 'page/' . $paged, 'paged' ) : $url;
	}

	return '';
}

/**
 * The target's path on the scheme and host of the URL Polylang computed from the request,
 * keeping the visitor's own query arguments but not the ones that name the object.
 *
 * @param string $target       Canonical URL (permalink).
 * @param string $redirect_url URL Polylang computed.
 */
function hcwa_canonical_with_request( string $target, string $redirect_url ): string {
	$to   = wp_parse_url( $target );
	$from = wp_parse_url( $redirect_url );
	if ( ! is_array( $to ) || ! is_array( $from ) || empty( $from['host'] ) ) {
		return $target;
	}

	$url = ( $from['scheme'] ?? 'https' ) . '://' . $from['host'] . ( isset( $from['port'] ) ? ':' . $from['port'] : '' ) . ( $to['path'] ?? '/' );
	if ( ! empty( $from['query'] ) ) {
		$url = remove_query_arg(
			array( 'p', 'page_id', 'post_type', 'name', 'pagename', 'attachment', 'attachment_id', 'cat', 'category_name', 'paged', 'page', 'lang', 'hcwa_center', 'hcwa_program', 'hcwa_report' ),
			$url . '?' . $from['query']
		);
	}
	return $url;
}

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
 * A post and all its translations (the post first). Without Polylang just the post.
 *
 * @param int $post_id Post ID.
 * @return int[]
 */
function hcwa_post_translation_ids( int $post_id ): array {
	$ids = array( $post_id );
	if ( $post_id > 0 && function_exists( 'pll_get_post_translations' ) ) {
		$ids = array_merge( $ids, array_map( 'intval', array_values( (array) pll_get_post_translations( $post_id ) ) ) );
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * A protective flag (hcwa_meta_fields() 'protective': "No identifiable children",
 * "Identifiable child") is never copied between translations and counts when any
 * translation has it, so saving or linking one language version can never clear it.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 */
function hcwa_meta_flag_any( int $post_id, string $key ): bool {
	foreach ( hcwa_post_translation_ids( $post_id ) as $id ) {
		if ( in_array( get_post_meta( $id, $key, true ), array( true, 1, '1', 'true' ), true ) ) {
			return true;
		}
	}
	return false;
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
