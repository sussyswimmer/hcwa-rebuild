<?php
/**
 * Theme setup: supports, menus, assets, <head>, and a few clean-ups that keep pages light
 * (08 task 2.8: home under 1 MB and 15 requests without images, no jQuery on the front end).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'align-wide' ); // sections are full-width groups
		remove_theme_support( 'core-block-patterns' );
		add_editor_style( array( 'assets/css/tokens.css', 'assets/css/site.css', 'assets/css/wp.css', 'assets/css/editor.css' ) );
		add_post_type_support( 'page', 'excerpt' ); // the page header intro

		register_nav_menus(
			array(
				'primary' => __( 'Main menu (header and phone menu)', 'hcwa-2026' ),
			)
		);

		// srcset widths from 05-DESIGN (480/768/1200/1600/2400).
		add_image_size( 'hcwa-480', 480, 0 );
		add_image_size( 'hcwa-1600', 1600, 0 );
		add_image_size( 'hcwa-2400', 2400, 0 );
		update_option( 'medium_large_size_w', 768 );
	}
);

/* ---------- assets ---------- */

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_template_directory_uri() . '/assets';
		wp_enqueue_style( 'hcwa-tokens', $dir . '/css/tokens.css', array(), HCWA_THEME_VERSION );
		wp_enqueue_style( 'hcwa-site', $dir . '/css/site.css', array( 'hcwa-tokens' ), HCWA_THEME_VERSION );
		wp_enqueue_style( 'hcwa-wp', $dir . '/css/wp.css', array( 'hcwa-site' ), HCWA_THEME_VERSION );
		wp_enqueue_script( 'hcwa-site', $dir . '/js/site.js', array(), HCWA_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => false ) );

		// Not used by this theme; each one is a request or inline CSS on every page.
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'wp-block-library-theme' );
		if ( ! is_admin_bar_showing() ) {
			wp_deregister_script( 'wp-embed' );
		}
	},
	20
);

// Only the CSS of blocks actually on the page.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

// Emoji polyfill script + styles: not needed (05-DESIGN: no emoji in the UI).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

/* ---------- <head> ---------- */

add_action(
	'wp_head',
	function () {
		$fonts = get_template_directory_uri() . '/assets/fonts/';
		// 05-DESIGN: preload only the body regular and the heading face used in the hero.
		echo '<link rel="preload" href="' . esc_url( $fonts . 'be-vietnam-pro-400.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		echo '<link rel="preload" href="' . esc_url( $fonts . 'fraunces-vf.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		echo '<link rel="icon" href="' . esc_url( get_template_directory_uri() . '/assets/img/favicon-32.png' ) . '" sizes="32x32" type="image/png">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( get_template_directory_uri() . '/assets/img/apple-touch-icon.png' ) . '">' . "\n";
	},
	2
);

// Staging and local copies stay out of search engines, whatever the Reading setting says.
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( hcwa_review_mode() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['max-image-preview'] );
		}
		return $robots;
	}
);

// Titles: home = full name + (HCWA) (task 0.4), other pages "Title | short name".
add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( is_front_page() ) {
			return array( 'title' => hcwa_t( 'orgName' ) . ' (HCWA)' );
		}
		if ( isset( $_GET['untranslated'] ) && hcwa_untranslated_post() ) { // phpcs:ignore WordPress.Security.NonceVerification
			$parts['title'] = hcwa_t( 'news' );
		}
		$parts['site'] = hcwa_t( 'shortName' );
		unset( $parts['tagline'] );
		return $parts;
	}
);
add_filter(
	'document_title_separator',
	function () {
		return '|';
	}
);

/** One-line description for meta tags: excerpt, then the org description. */
function hcwa_meta_description() {
	if ( is_singular() && has_excerpt() ) {
		return wp_strip_all_tags( get_the_excerpt() );
	}
	if ( is_singular( 'hcwa_center' ) && hcwa_meta( get_queried_object(), 'hcwa_serves' ) ) {
		return hcwa_meta( get_queried_object(), 'hcwa_serves' );
	}
	return hcwa_safe_text( hcwa_setting_tx( 'description' ) );
}

// Without an SEO plugin, print description and Open Graph basics; Yoast takes over if active.
add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return;
		}
		$description = hcwa_meta_description();
		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
		echo '<meta property="og:locale" content="' . ( 'vi' === hcwa_lang() ? 'vi_VN' : 'en_GB' ) . '">' . "\n";
		$image = is_singular() && has_post_thumbnail() && hcwa_image_allowed( get_post_thumbnail_id() ) ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' ) : '';
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}
	},
	5
);

// x-default = Vietnamese (07-BILINGUAL). Polylang prints vi/en alternates but no x-default
// when the default language has no URL prefix, so add it here when missing.
add_filter(
	'pll_rel_hreflang_attributes',
	function ( $hreflangs ) {
		if ( ! isset( $hreflangs['x-default'] ) && isset( $hreflangs['vi'] ) ) {
			$hreflangs['x-default'] = $hreflangs['vi'];
		}
		return $hreflangs;
	}
);

// schema.org NGO on the homepage (09 SEO): names in both languages, logo, verified address only.
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		$data = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'NGO',
			'name'          => hcwa_t( 'orgName', array(), 'vi' ),
			'alternateName' => array( hcwa_t( 'orgName', array(), 'en' ), 'HCWA' ),
			'url'           => hcwa_url( 'home', 'vi' ),
			'logo'          => get_template_directory_uri() . '/assets/img/hcwa-logo.png',
		);
		$social = array_values( hcwa_social_links() );
		if ( $social ) {
			$data['sameAs'] = $social;
		}
		if ( hcwa_contact_verified() ) {
			$data['address']   = array( '@type' => 'PostalAddress', 'streetAddress' => hcwa_setting( 'address', 'vi' ), 'addressLocality' => 'Thành phố Hồ Chí Minh', 'addressCountry' => 'VN' );
			$data['telephone'] = hcwa_setting( 'phone_tel' );
			$data['email']     = hcwa_setting( 'email' );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
);

/* ---------- body and blocks ---------- */

add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'lang-' . hcwa_lang();
		if ( hcwa_review_mode() ) {
			$classes[] = 'is-review';
		}
		return $classes;
	}
);

// One gentle fade-up per section (05-DESIGN motion): sections from patterns get data-reveal.
add_filter(
	'render_block_core/group',
	function ( $content, $block ) {
		$class = isset( $block['attrs']['className'] ) ? ' ' . $block['attrs']['className'] . ' ' : '';
		if ( false === strpos( $class, ' section ' ) ) {
			return $content;
		}
		$tags = new WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'data-reveal', true );
		}
		return $tags->get_updated_html();
	},
	10,
	2
);

// "Category: Stories" comes from WordPress core in the admin language; the page H1 is the name alone.
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

/* ---------- archive queries ---------- */

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		// News: 12 per page (06-PAGES); ?category= filters the hub without leaving it.
		if ( $query->is_home() || $query->is_category() || $query->is_tag() ) {
			$query->set( 'posts_per_page', 12 );
			$query->set( 'ignore_sticky_posts', true );
			if ( $query->is_home() && ! empty( $_GET['category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$query->set( 'category_name', sanitize_title( wp_unslash( $_GET['category'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		}
		// Reports library filters.
		if ( $query->get( 'hcwa_reports' ) ) {
			$meta = array();
			if ( ! empty( $_GET['type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$meta[] = array( 'key' => 'hcwa_type', 'value' => sanitize_key( wp_unslash( $_GET['type'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( ! empty( $_GET['year'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$meta[] = array( 'key' => 'hcwa_year', 'value' => absint( $_GET['year'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			$order = hcwa_report_order_args( array_merge( (array) $query->get( 'meta_query' ), $meta ) );
			$query->set( 'meta_query', $order['meta_query'] );
			$query->set( 'orderby', $order['orderby'] );
			$query->set( 'posts_per_page', 20 );
		}
	}
);

/* ---------- helpers for templates ---------- */

/**
 * Text from settings that may still hold placeholders: hidden on production when it contains
 * a [bracket] token, outlined in review mode.
 */
function hcwa_safe_text( $text ) {
	if ( hcwa_is_production() && preg_match( '/\[[^\]]{1,80}\]/u', (string) $text ) ) {
		return '';
	}
	return (string) $text;
}

function hcwa_has_token( $text ) {
	return (bool) preg_match( '/\[[^\]]{1,80}\]/u', (string) $text );
}

/** Strings the front-end script needs, in the page language (same keys as the prototype). */
function hcwa_client_strings() {
	$keys = array( 'copy', 'copied', 'copyFailed', 'f_sending', 'f_send', 'e_summary', 'close' );
	$out  = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = hcwa_t( $key );
	}
	return '<script type="application/json" id="i18n">' . str_replace( '<', '\u003c', wp_json_encode( $out, JSON_UNESCAPED_UNICODE ) ) . '</script>';
}

/** Numbered pagination with localized labels (07-BILINGUAL). */
function hcwa_pagination( $query = null ) {
	$query = $query ? $query : $GLOBALS['wp_query'];
	if ( $query->max_num_pages < 2 ) {
		return '';
	}
	$links = paginate_links(
		array(
			'total'     => $query->max_num_pages,
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'type'      => 'array',
			'prev_text' => hcwa_icon( 'prev' ) . '<span>' . esc_html( hcwa_t( 'prevPage' ) ) . '</span>',
			'next_text' => '<span>' . esc_html( hcwa_t( 'nextPage' ) ) . '</span>' . hcwa_icon( 'next' ),
			'mid_size'  => 1,
		)
	);
	if ( ! $links ) {
		return '';
	}
	$out = '<nav class="pagination" aria-label="' . esc_attr( hcwa_t( 'pagination' ) ) . '"><ul>';
	foreach ( $links as $link ) {
		$out .= '<li>' . $link . '</li>';
	}
	return $out . '</ul></nav>';
}
