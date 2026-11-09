<?php
/**
 * The prototype's bundle in WordPress (docs/plan/11-WP-PORT.md 1.2 and 1.5).
 *
 * `npm --prefix prototype run export:wp` builds prototype/src/main.js into assets/dist/ with a
 * manifest. This file enqueues it: the draft CSS (staging only) before the main CSS, the module
 * script and its modulepreload links in <head>, the font preloads and favicons. No URL under
 * assets/dist/ ever carries a query string: the hashed file names bust the cache, and a ?ver on
 * the module would evaluate main.js twice (the motion and world chunks import "./main-….js"), a
 * ?ver on the CSS would make Vite's preload helper fetch it again.
 *
 * On every page that holds only prototype markup (sections pages, centers, programs, the 404)
 * core's block CSS, global styles, the classic-theme styles, the image auto-sizes fix and the
 * theme.json font faces are left out: the bundle carries all of it. WordPress-only pages
 * (body class page-wp: news, reports, About and the rest) keep them for their core blocks.
 *
 * Also here: editor styles, the WordPress-only share script, the meta description and the head
 * clean-up.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * The bundle's manifest (assets/dist/manifest.json), read once per request; empty when the
 * export has not run.
 *
 * @param array|null $replace Tests only: use this manifest instead ([] reads the file again).
 * @return array<string, array>
 */
function hcwa_theme_manifest( ?array $replace = null ): array {
	static $manifest = null;
	if ( null !== $replace ) {
		$manifest = $replace ? $replace : null;
	}
	if ( null === $manifest ) {
		$file     = get_theme_file_path( 'assets/dist/manifest.json' );
		$data     = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$manifest = is_array( $data ) ? $data : array();
	}
	return $manifest;
}

/**
 * Absolute URL of a file in the bundle, exactly as the bundle resolves it: no query string.
 *
 * @param string $path Path inside assets/dist/ ('assets/main-….css', 'img/hcwa-logo.webp', '' for the folder).
 */
function hcwa_theme_dist_url( string $path ): string {
	return get_theme_file_uri( 'assets/dist/' . ltrim( $path, '/' ) );
}

/**
 * One manifest entry ('src/main.js', 'src/styles/draft.css'), or an empty array.
 *
 * @param string $key Manifest key.
 */
function hcwa_theme_dist_entry( string $key ): array {
	$entry = hcwa_theme_manifest()[ $key ] ?? null;
	return is_array( $entry ) && ! empty( $entry['file'] ) ? $entry : array();
}

/**
 * The files an entry imports statically, depth first, each once (its modulepreload links).
 *
 * @param string $key Manifest key.
 * @return string[] Paths inside assets/dist/.
 */
function hcwa_theme_dist_imports( string $key ): array {
	$manifest = hcwa_theme_manifest();
	$out      = array();
	$walk     = static function ( string $k ) use ( &$walk, &$out, $manifest ): void {
		foreach ( (array) ( $manifest[ $k ]['imports'] ?? array() ) as $dep ) {
			$file = $manifest[ $dep ]['file'] ?? '';
			if ( '' === $file || in_array( $file, $out, true ) ) {
				continue;
			}
			$out[] = $file;
			$walk( $dep );
		}
	};
	$walk( $key );
	return $out;
}

/**
 * The CSS an entry needs: its own and that of its static imports, in order.
 *
 * @param string $key Manifest key.
 * @return string[] Paths inside assets/dist/.
 */
function hcwa_theme_dist_css( string $key ): array {
	$manifest = hcwa_theme_manifest();
	$css      = (array) ( $manifest[ $key ]['css'] ?? array() );
	foreach ( (array) ( $manifest[ $key ]['imports'] ?? array() ) as $dep ) {
		$css = array_merge( $css, (array) ( $manifest[ $dep ]['css'] ?? array() ) );
	}
	return array_values( array_unique( array_map( 'strval', $css ) ) );
}

/**
 * What the current view is, for the head and the asset choices: the templates' page context
 * (11-WP-PORT.md 6.4) when it exists, else the front page as home and everything else as a
 * WordPress-only page.
 *
 * @param array|null $replace Tests only: use this context ([] goes back to the real one).
 * @return array{lit: bool, world: bool, header_over: bool, body_class: string}
 */
function hcwa_theme_assets_context( ?array $replace = null ): array {
	static $fixed = null;
	if ( null !== $replace ) {
		$fixed = $replace ? $replace : null;
		if ( ! $replace ) {
			return array(
				'lit'         => false,
				'world'       => false,
				'header_over' => false,
				'body_class'  => 'page-wp',
			);
		}
	}
	$context = $fixed ?? ( function_exists( 'hcwa_theme_page_context' ) ? hcwa_theme_page_context() : null );
	if ( ! is_array( $context ) ) {
		$home    = is_front_page();
		$context = array(
			'lit'         => $home,
			'world'       => $home,
			'header_over' => $home,
			'body_class'  => $home ? 'page-home' : 'page-wp',
		);
	}
	return array(
		'lit'         => ! empty( $context['lit'] ),
		'world'       => ! empty( $context['world'] ),
		'header_over' => ! empty( $context['header_over'] ),
		'body_class'  => (string) ( $context['body_class'] ?? 'page-wp' ),
	);
}

/**
 * Whether this view prints only prototype markup, so core's block and global styles stay out.
 */
function hcwa_theme_prototype_view(): bool {
	return ! is_admin() && 'page-wp' !== hcwa_theme_assets_context()['body_class'];
}

// Priority 20: after core's own styles are queued, so the bundle's order is the last word.
add_action( 'wp_enqueue_scripts', 'hcwa_theme_enqueue_assets', 20 );

function hcwa_theme_enqueue_assets(): void {
	$main = hcwa_theme_dist_entry( 'src/main.js' );
	$deps = array();

	// Staging only: draft outlines, the staging note and pending chips, before the main CSS as the
	// prototype imports it first. Production never loads it.
	if ( ! hcwa_theme_is_production() ) {
		$draft = hcwa_theme_dist_entry( 'src/styles/draft.css' );
		if ( $draft && str_ends_with( (string) $draft['file'], '.css' ) ) {
			wp_enqueue_style( 'hcwa-draft', hcwa_theme_dist_url( (string) $draft['file'] ), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- hashed file name, no query string (11-WP-PORT.md 1.2).
			$deps = array( 'hcwa-draft' );
		}
	}
	if ( $main ) {
		foreach ( hcwa_theme_dist_css( 'src/main.js' ) as $i => $css ) {
			$handle = 0 === $i ? 'hcwa-main' : 'hcwa-main-' . $i;
			wp_enqueue_style( $handle, hcwa_theme_dist_url( $css ), $deps, null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- hashed file name, no query string.
			$deps = array( $handle );
		}
	}

	// WordPress-only pages, the announcement bar and the admin bar get their own small stylesheet
	// (templates stream, 2.7); the parity routes never load it.
	$wp_css = 'assets/css/wp.css';
	if ( file_exists( get_theme_file_path( $wp_css ) ) && ( ! hcwa_theme_prototype_view() || hcwa_theme_announcement_on() || is_admin_bar_showing() ) ) {
		wp_enqueue_style( 'hcwa-wp', get_theme_file_uri( $wp_css ), $deps, hcwa_theme_file_version( $wp_css ) );
	}

	// The phone's share sheet on single posts (2.8): no prototype counterpart.
	if ( is_singular( 'post' ) ) {
		wp_enqueue_script(
			'hcwa-share',
			get_theme_file_uri( 'assets/js/wp-share.js' ),
			array(),
			hcwa_theme_file_version( 'assets/js/wp-share.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => false,
			)
		);
	}
}

/**
 * A theme file's modification time as its version (theme files outside the bundle only).
 *
 * @param string $relative Path inside the theme.
 */
function hcwa_theme_file_version( string $relative ): string {
	$path = get_theme_file_path( $relative );
	return file_exists( $path ) ? (string) filemtime( $path ) : (string) wp_get_theme()->get( 'Version' );
}

/**
 * Whether the announcement bar prints on this view (Settings → HCWA switches it on).
 */
function hcwa_theme_announcement_on(): bool {
	if ( function_exists( 'hcwa_announcement' ) ) {
		return null !== hcwa_announcement();
	}
	return ! empty( hcwa_theme_setting( 'announcement_enabled' ) );
}

// Priority 9, after core's head scripts: the prototype loads its module in <head>. Printed by
// hand, not as a script module, because a classic theme prints script modules in the footer.
add_action( 'wp_head', 'hcwa_theme_print_module', 9 );

function hcwa_theme_print_module(): void {
	$main = hcwa_theme_dist_entry( 'src/main.js' );
	if ( ! $main ) {
		return;
	}
	printf( '<script type="module" crossorigin src="%s"></script>' . "\n", esc_url( hcwa_theme_dist_url( (string) $main['file'] ) ) );
	foreach ( hcwa_theme_dist_imports( 'src/main.js' ) as $file ) {
		printf( '<link rel="modulepreload" crossorigin href="%s">' . "\n", esc_url( hcwa_theme_dist_url( $file ) ) );
	}
}

/**
 * Strings the bundle reads from <script type="application/json" id="i18n"> (inc/head.php), in
 * the page language: the prototype's 11 keys.
 *
 * @return array<string, string>
 */
function hcwa_theme_js_strings(): array {
	$keys = array( 'copy', 'copied', 'copyFailed', 'f_sending', 'f_send', 'e_summary', 'close', 'motionPause', 'motionPlay', 'draftHide', 'draftShow' );
	$out  = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = hcwa_theme_t( $key );
	}
	return $out;
}

add_filter( 'wp_preload_resources', 'hcwa_theme_preload_fonts' );

/**
 * Preload the display face and the body regular, as the prototype does, at the URLs the bundle
 * CSS uses (so the preload is the request the @font-face reuses).
 *
 * @param array $resources Preload resources.
 * @return array
 */
function hcwa_theme_preload_fonts( array $resources ): array {
	if ( ! hcwa_theme_manifest() ) {
		return $resources;
	}
	foreach ( array( 'archivo-w78-600-700.woff2', 'be-vietnam-pro-400.woff2' ) as $file ) {
		$resources[] = array(
			'href'        => hcwa_theme_dist_url( 'fonts/' . $file ),
			'as'          => 'font',
			'type'        => 'font/woff2',
			'crossorigin' => 'anonymous',
		);
	}
	return $resources;
}

add_action( 'wp_head', 'hcwa_theme_favicon_fallback', 99 );

/**
 * The prototype's favicons, unless staff set a Site Icon in the Customizer.
 */
function hcwa_theme_favicon_fallback(): void {
	if ( has_site_icon() || ! hcwa_theme_manifest() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" sizes="32x32" type="image/png">' . "\n", esc_url( hcwa_theme_dist_url( 'img/favicon-32.png' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( hcwa_theme_dist_url( 'img/apple-touch-icon.png' ) ) );
}

// Late on wp_enqueue_scripts, and again before the footer prints late styles.
add_action( 'wp_enqueue_scripts', 'hcwa_theme_dequeue_core_styles', 100 );
add_action( 'wp_footer', 'hcwa_theme_dequeue_core_styles', 0 );

/**
 * Core styles a prototype view does not use (11-WP-PORT.md 1.2 "Dequeued on sections pages").
 *
 * @return string[]
 */
function hcwa_theme_core_style_handles(): array {
	return array( 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'block-style-variation-styles', 'core-block-supports', 'core-block-supports-duotone', 'wp-img-auto-sizes-contain' );
}

function hcwa_theme_dequeue_core_styles(): void {
	if ( ! hcwa_theme_prototype_view() ) {
		return;
	}
	foreach ( hcwa_theme_core_style_handles() as $handle ) {
		wp_dequeue_style( $handle );
	}
	// Per-block core styles, should a core block reach a sections page (2.1 rule 4).
	foreach ( (array) wp_styles()->queue as $handle ) {
		if ( str_starts_with( (string) $handle, 'wp-block-' ) ) {
			wp_dequeue_style( $handle );
		}
	}
}

// After redirect_canonical (10) and hcwa-core's refusals, once the view is known.
add_action( 'template_redirect', 'hcwa_theme_trim_core_head', 100 );

/**
 * On a prototype view: no inline image auto-sizes fix, no theme.json font faces (the bundle has
 * its own @font-face), no global styles or block-support styles queued late in the footer.
 */
function hcwa_theme_trim_core_head(): void {
	if ( ! hcwa_theme_prototype_view() ) {
		return;
	}
	remove_action( 'wp_head', 'wp_enqueue_img_auto_sizes_contain_css_fix', 0 );
	remove_action( 'wp_head', 'wp_print_auto_sizes_contain_css_fix', 1 );
	remove_action( 'wp_head', 'wp_print_font_faces', 50 );
	remove_action( 'wp_head', 'wp_print_font_faces_from_style_variations', 50 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );
}

// After hcwa_theme_setup (priority 10), which registers the editor-styles support.
add_action( 'after_setup_theme', 'hcwa_theme_editor_styles', 11 );

/**
 * Editor styles (1.5): the bundle's draft and main CSS, so block previews look like the front
 * end, then assets/css/editor.css. The world and motion chunks never load in the editor.
 */
function hcwa_theme_editor_styles(): void {
	$styles = array();
	$draft  = hcwa_theme_dist_entry( 'src/styles/draft.css' );
	if ( $draft ) {
		$styles[] = 'assets/dist/' . $draft['file'];
	}
	foreach ( hcwa_theme_dist_css( 'src/main.js' ) as $css ) {
		$styles[] = 'assets/dist/' . $css;
	}
	$styles[] = 'assets/css/editor.css';
	add_editor_style( $styles );
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
 * Description of the current page, at most 160 characters. First the description the
 * prototype prints for the page (hcwa_theme_own_description(): a sections page's intro, a
 * center's "who we serve", a program's summary, the 404 text), which comes without draft
 * brackets on staging and is '' on production while draft. Otherwise the page's own fields:
 * the summary (centers, programs, reports), else a hand-written excerpt; a photo's caption
 * only when staff wrote it (hcwa_theme_attachment_text()); a category or term description.
 * '' when the page has none.
 */
function hcwa_theme_page_description(): string {
	$own = function_exists( 'hcwa_theme_own_description' ) ? trim( wp_strip_all_tags( hcwa_theme_own_description() ) ) : '';
	if ( '' !== $own ) {
		return wp_html_excerpt( $own, 160, '…' );
	}
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
 * The description and Open Graph tags when no SEO plugin is active, as the prototype prints
 * them (prototype/src/partials/layout.mjs): <meta name=description> with the page's own
 * description, else the tagline; og:title with the browser title; og:description with the
 * same description; og:locale vi_VN or en_GB. The tagline goes through
 * hcwa_theme_draft_text(), so staging prints it without the [DRAFT: …] brackets and
 * production prints nothing while it is draft. With no description, neither description
 * tag is printed.
 */
function hcwa_theme_meta_description(): void {
	if ( hcwa_theme_seo_plugin_active() ) {
		return;
	}
	$description = hcwa_theme_page_description();
	if ( '' === $description ) {
		// get_bloginfo( 'description' ) is this same option (Polylang translates it).
		$tagline     = hcwa_theme_draft_text( trim( wp_strip_all_tags( (string) get_option( 'blogdescription', '' ) ) ) );
		$description = wp_html_excerpt( (string) $tagline['text'], 160, '…' );
	}
	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
	// wp_get_document_title() returns the title HTML-escaped; decode it once for the attribute.
	$title = trim( html_entity_decode( wp_strip_all_tags( (string) wp_get_document_title() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' !== $title ) {
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	}
	if ( '' !== $description ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	}
	printf( '<meta property="og:locale" content="%s">' . "\n", 'en' === hcwa_theme_lang() ? 'en_GB' : 'vi_VN' );
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
 * Head clutter off: emoji script and styles, oEmbed discovery and the embed host script, RSD,
 * generator, shortlink, REST discovery and per-comment/category feed links. The main posts
 * feed stays.
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
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );

	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}
