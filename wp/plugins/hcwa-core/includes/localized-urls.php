<?php
/**
 * Per-language URL bases for centers, programs, reports and post categories.
 *
 * Polylang free cannot give one post type a different URL base per language, so the
 * post types register with 'rewrite' => false and this file adds explicit rules with
 * add_rewrite_rule() plus post_type_link / term_link filters.
 *
 * Checked in polylang/src/links-directory.php (Polylang 3.9.1):
 * - Rules added with add_rewrite_rule() only pass through the 'rewrite_rules_array'
 *   filter. There Polylang changes a rule only if it matches '#post_type=(...)#' for a
 *   translated custom post type and has no 'name='. Ours use the post type query var
 *   ('hcwa_center=') and carry their own 'lang=', so they survive unchanged.
 * - Polylang prepares its rule filters on 'wp_loaded' priority 9, just before
 *   WordPress's deferred flush (priority 10), so flush_rewrite_rules() must not be
 *   forced earlier than that; WP_Rewrite::flush_rules() defers itself until wp_loaded.
 * - Polylang's own link filters run at priority 20 ('post_type_link', 'term_link'),
 *   so ours run at 30 and build the whole URL from get_option( 'home' ), which
 *   Polylang does not filter, to avoid a doubled /en/.
 * - Posts and pages keep Polylang's own URLs (/en/{slug}/ for English).
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL bases per object and language (wp/CONTRACT.md "URLs").
 *
 * @return array<string, array{vi: string, en: string}>
 */
function hcwa_url_bases(): array {
	/**
	 * Filters the per-language URL bases. Changing them needs a rewrite flush
	 * (Settings → Permalinks → Save) and 301 redirects for the old URLs.
	 *
	 * @param array $bases object => ['vi' => base, 'en' => base], no leading or trailing slash.
	 */
	return (array) apply_filters(
		'hcwa_url_bases',
		array(
			'hcwa_center'  => array( 'vi' => 'hoat-dong/co-so', 'en' => 'en/our-work/centers' ),
			'hcwa_program' => array( 'vi' => 'hoat-dong/du-an', 'en' => 'en/our-work/programs' ),
			'hcwa_report'  => array( 'vi' => 'tac-dong/bao-cao', 'en' => 'en/impact/reports' ),
			'category'     => array( 'vi' => 'tin-tuc', 'en' => 'en/news' ),
			'post'         => array( 'vi' => '', 'en' => 'en' ),
		)
	);
}

/**
 * Relative path (no leading slash, trailing slash) for an object in a language.
 * Shared by the link filters and the tests. '' for an unknown object.
 *
 * @param string $object hcwa_center | hcwa_program | hcwa_report | category | post.
 * @param string $lang   'vi' or 'en'.
 * @param string $slug   Post name or term slug (already URL-encoded), or a %placeholder%.
 */
function hcwa_localized_path( string $object, string $lang, string $slug ): string {
	$bases = hcwa_url_bases();
	if ( ! isset( $bases[ $object ] ) ) {
		return '';
	}

	$base = trim( (string) ( $bases[ $object ][ hcwa_normalize_lang( $lang ) ] ?? '' ), '/' );
	$slug = trim( $slug, '/' );
	$path = implode( '/', array_filter( array( $base, $slug ), 'strlen' ) );

	return '' === $path ? '' : $path . '/';
}

/**
 * The rewrite rules, regex => query, in the order they must be tried.
 *
 * Category rules skip 'page', 'feed' and 'embed' right after the base, so
 * /en/news/page/2/ still paginates the posts page instead of looking for a category
 * named "page".
 *
 * Centers, programs and reports get the sub-rules WordPress would generate for them:
 * attachment pages (WordPress links an attachment as its parent's permalink plus the
 * attachment slug, wp-includes/link-template.php get_attachment_link()) and paged content
 * (/x/2/). There is no embed rule: embeds are switched off (media-approval.php), since
 * the embed view prints the featured image past the photo approval gate.
 *
 * @return array<string, string>
 */
function hcwa_localized_rewrite_rules(): array {
	$rules    = array();
	$bases    = hcwa_url_bases();
	$reserved = '(?!(?:page|feed|embed)(?:/|$))';
	$sub      = '(?!(?:embed|feed|trackback)/?$)';

	foreach ( hcwa_languages() as $lang ) {
		foreach ( array( 'hcwa_center', 'hcwa_program', 'hcwa_report' ) as $post_type ) {
			if ( empty( $bases[ $post_type ][ $lang ] ) ) {
				continue;
			}
			$base = trim( $bases[ $post_type ][ $lang ], '/' );

			$rules[ $base . '/[^/]+/attachment/([^/]+)/?$' ]  = 'index.php?attachment=$matches[1]&lang=' . $lang;
			$rules[ $base . '/([^/]+)/([0-9]+)/?$' ]          = 'index.php?' . $post_type . '=$matches[1]&page=$matches[2]&lang=' . $lang;
			$rules[ $base . '/[^/]+/' . $sub . '([^/]+)/?$' ] = 'index.php?attachment=$matches[1]&lang=' . $lang;
			$rules[ $base . '/([^/]+)/?$' ]                   = 'index.php?' . $post_type . '=$matches[1]&lang=' . $lang;
		}

		if ( ! empty( $bases['category'][ $lang ] ) ) {
			$base = trim( $bases['category'][ $lang ], '/' );

			$rules[ $base . '/' . $reserved . '([^/]+)/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]&lang=' . $lang;
			$rules[ $base . '/' . $reserved . '([^/]+)/page/?([0-9]{1,})/?$' ]                  = 'index.php?category_name=$matches[1]&paged=$matches[2]&lang=' . $lang;
			$rules[ $base . '/' . $reserved . '([^/]+)/?$' ]                                     = 'index.php?category_name=$matches[1]&lang=' . $lang;
		}
	}

	return $rules;
}

/**
 * Adds the rules at the top, before WordPress's page rules.
 */
function hcwa_add_localized_rewrite_rules(): void {
	foreach ( hcwa_localized_rewrite_rules() as $regex => $query ) {
		add_rewrite_rule( $regex, $query, 'top' );
	}
}
add_action( 'init', 'hcwa_add_localized_rewrite_rules', 10 );

/**
 * Re-flushes once after a plugin update, since the rules may have changed.
 * flush_rewrite_rules() waits for wp_loaded, after Polylang has prepared its filters.
 */
function hcwa_maybe_flush_rewrite_rules(): void {
	if ( get_option( 'hcwa_core_rules_version' ) !== HCWA_CORE_VERSION ) {
		update_option( 'hcwa_core_rules_version', HCWA_CORE_VERSION );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'hcwa_maybe_flush_rewrite_rules', 99 );

/**
 * Language of a post: 'vi' or 'en' (Vietnamese without Polylang or before one is set).
 *
 * @param int $post_id Post ID.
 */
function hcwa_post_language( int $post_id ): string {
	$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post_id ) : '';

	// A new translation has no language until its first save; Polylang passes it in the URL.
	if ( ! $lang && is_admin() && isset( $_GET['new_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lang = sanitize_key( wp_unslash( $_GET['new_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return hcwa_normalize_lang( $lang );
}

/**
 * Permalinks for centers, programs and reports.
 *
 * Drafts keep WordPress's plain ?post_type=…&p=… link (previews rely on it); the editor's
 * sample permalink gets the %post_type% placeholder so the slug stays editable.
 *
 * @param string  $link      Link built by WordPress (and Polylang).
 * @param WP_Post $post      Post.
 * @param bool    $leavename Keep the %post_type% placeholder.
 * @param bool    $sample    Sample permalink for the editor.
 */
function hcwa_filter_post_type_link( $link, $post, $leavename = false, $sample = false ) {
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'hcwa_center', 'hcwa_program', 'hcwa_report' ), true ) ) {
		return $link;
	}
	if ( ! get_option( 'permalink_structure' ) ) {
		return $link;
	}
	if ( ! $sample && wp_force_plain_post_permalink( $post ) ) {
		return $link;
	}

	$slug = $leavename ? '%' . $post->post_type . '%' : $post->post_name;
	if ( '' === $slug ) {
		return $link;
	}

	return hcwa_home_base() . hcwa_localized_path( $post->post_type, hcwa_post_language( $post->ID ), $slug );
}
add_filter( 'post_type_link', 'hcwa_filter_post_type_link', 30, 4 );

/**
 * Category archive links: /tin-tuc/{slug}/ and /en/news/{slug}/.
 *
 * @param string  $link     Link.
 * @param WP_Term $term     Term.
 * @param string  $taxonomy Taxonomy.
 */
function hcwa_filter_term_link( $link, $term, $taxonomy ) {
	if ( 'category' !== $taxonomy || ! $term instanceof WP_Term || ! get_option( 'permalink_structure' ) ) {
		return $link;
	}

	$lang = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $term->term_id ) : '';

	return hcwa_home_base() . hcwa_localized_path( 'category', hcwa_normalize_lang( $lang ), $term->slug );
}
add_filter( 'term_link', 'hcwa_filter_term_link', 30, 3 );
