<?php
/**
 * Styles, script, font preloads, favicon fallback and head clean-up.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asset version: the file's modification time, so caches refresh after every deploy.
 *
 * @param string $relative Path inside the theme.
 */
function hcwa_theme_asset_version( string $relative ): string {
	$path = get_theme_file_path( $relative );
	return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
}

// Priority 20: after core's global styles, so equal-specificity rules from site.css win.
add_action( 'wp_enqueue_scripts', 'hcwa_theme_enqueue_assets', 20 );

function hcwa_theme_enqueue_assets(): void {
	wp_enqueue_style( 'hcwa-site', get_theme_file_uri( 'assets/css/site.css' ), array(), hcwa_theme_asset_version( 'assets/css/site.css' ) );

	if ( file_exists( get_theme_file_path( 'assets/css/patterns.css' ) ) ) {
		wp_enqueue_style( 'hcwa-patterns', get_theme_file_uri( 'assets/css/patterns.css' ), array( 'hcwa-site' ), hcwa_theme_asset_version( 'assets/css/patterns.css' ) );
	}

	// No dependencies: the front end never needs jQuery for the theme.
	wp_enqueue_script(
		'hcwa-site',
		get_theme_file_uri( 'assets/js/site.js' ),
		array(),
		hcwa_theme_asset_version( 'assets/js/site.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => false,
		)
	);
	// Inline in <head> before site.js: the strings, and bars.js, which header.php calls right
	// after each top bar so they are shown or hidden before first paint (no layout shift).
	wp_add_inline_script( 'hcwa-site', 'window.hcwaI18n = ' . wp_json_encode( hcwa_theme_js_strings() ) . ";\n" . hcwa_theme_bars_js(), 'before' );
}

/**
 * assets/js/bars.js, for printing inline ('' if the file is missing).
 */
function hcwa_theme_bars_js(): string {
	$path = get_theme_file_path( 'assets/js/bars.js' );
	return file_exists( $path ) ? trim( (string) file_get_contents( $path ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
}

/**
 * Strings site.js needs, in the page language.
 *
 * @return array<string, string>
 */
function hcwa_theme_js_strings(): array {
	$keys = array( 'copy', 'copied', 'copyFailed', 'close', 'noResults', 'showAll' );
	$out  = array( 'lang' => hcwa_theme_lang() );
	foreach ( $keys as $key ) {
		$out[ $key ] = hcwa_theme_t( $key );
	}
	return $out;
}

add_filter( 'wp_preload_resources', 'hcwa_theme_preload_fonts' );

/**
 * Preload the body regular and the display face used by the hero H1 (05-DESIGN), as the
 * prototype does. The @font-face rules come from theme.json.
 *
 * @param array $resources Preload resources.
 * @return array
 */
function hcwa_theme_preload_fonts( array $resources ): array {
	foreach ( array( 'be-vietnam-pro-400.woff2', 'fraunces-vf.woff2' ) as $file ) {
		$resources[] = array(
			'href'        => get_theme_file_uri( 'assets/fonts/' . $file ),
			'as'          => 'font',
			'type'        => 'font/woff2',
			'crossorigin' => 'anonymous',
		);
	}
	return $resources;
}

add_action( 'wp_head', 'hcwa_theme_favicon_fallback', 99 );

/**
 * The theme's favicons, unless staff set a Site Icon in the Customizer.
 */
function hcwa_theme_favicon_fallback(): void {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" sizes="32x32" type="image/png">' . "\n", esc_url( get_theme_file_uri( 'assets/img/favicon-32.png' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/img/apple-touch-icon.png' ) ) );
}

add_action( 'wp_head', 'hcwa_theme_meta_description', 1 );

// Yoast prints its own descriptions and never reads hcwa_summary; where it has none for a
// page, the page's own description fills in (meta, Open Graph and X). Priority 100: after
// hcwa-core drops a Yoast text with a draft marker on production (seo.php, priority 99),
// so such a page gets its own description instead of none.
add_filter( 'wpseo_metadesc', 'hcwa_theme_seo_description_fallback', 100 );
add_filter( 'wpseo_opengraph_desc', 'hcwa_theme_seo_description_fallback', 100 );
add_filter( 'wpseo_twitter_description', 'hcwa_theme_seo_description_fallback', 100 );

/**
 * Whether an SEO plugin prints the meta description (Yoast, Rank Math, SEOPress).
 */
function hcwa_theme_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Description of the current page from its own fields, at most 160 characters: the summary
 * (centers, programs, reports), else a hand-written excerpt; a photo's caption only when
 * staff wrote it (hcwa_theme_attachment_text()); a category or term description. '' when
 * the page has none.
 */
function hcwa_theme_page_description(): string {
	$description = '';
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && 'attachment' === $post->post_type ) {
			$description = hcwa_theme_attachment_text( (int) $post->ID, 'caption' );
		} elseif ( $post instanceof WP_Post ) {
			$description = (string) get_post_meta( $post->ID, 'hcwa_summary', true );
			if ( '' === trim( $description ) && has_excerpt( $post ) ) {
				$description = get_the_excerpt( $post );
			}
		}
	} elseif ( is_category() || is_tax() ) {
		$description = term_description();
	}
	// Meta, captions, term descriptions and the tagline skip the publish guard: no draft
	// text on the live site.
	$description = hcwa_theme_public_text( trim( wp_strip_all_tags( (string) $description ) ) );
	return wp_html_excerpt( $description, 160, '…' );
}

/**
 * Basic meta description when no SEO plugin is active: the page's own description, else
 * the tagline.
 */
function hcwa_theme_meta_description(): void {
	if ( hcwa_theme_seo_plugin_active() ) {
		return;
	}
	$description = hcwa_theme_page_description();
	if ( '' === $description ) {
		$description = wp_html_excerpt( hcwa_theme_public_text( trim( wp_strip_all_tags( get_bloginfo( 'description' ) ) ) ), 160, '…' );
	}
	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
}

/**
 * Yoast's meta, Open Graph and X descriptions: Yoast's own text wins; when it has none for
 * this page, the page's own description (hcwa_theme_page_description(), draft-safe). Never
 * the tagline, which would repeat on every page. Yoast is not in the reference source:
 * its presenters filter the value before dropping an empty tag (Yoast 14+, from memory),
 * so check once on the installed version that a center without a Yoast description gets
 * its summary.
 *
 * @param mixed $description Yoast's description.
 * @return mixed
 */
function hcwa_theme_seo_description_fallback( $description ) {
	if ( is_string( $description ) && '' !== trim( $description ) ) {
		return $description;
	}
	$own = hcwa_theme_page_description();
	return '' !== $own ? $own : $description;
}

add_action( 'init', 'hcwa_theme_clean_head' );

/**
 * Head clutter off: emoji script and styles, oEmbed discovery, RSD, generator, shortlink,
 * REST discovery and per-comment/category feed links. The main posts feed stays.
 */
function hcwa_theme_clean_head(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );

	// Core hooks oEmbed discovery at priority 4 and keeps a priority-10 copy for back-compat.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}
