<?php
/**
 * Shared helpers: environment, language, UI strings, number/date formats, page URLs, icons.
 * Templates in the hcwa-2026 theme call these directly.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- environment ---------- */

/**
 * Production = the live site. WordPress treats a site with no WP_ENVIRONMENT_TYPE as
 * production, so the safe behavior (unverified figures hidden) is the default.
 */
function hcwa_is_production() {
	return 'production' === wp_get_environment_type();
}

/** Review mode: local and staging show drafts, pending chips and labeled image slots. */
function hcwa_review_mode() {
	return ! hcwa_is_production();
}

/* ---------- language ---------- */

/**
 * Current content language: 'vi' or 'en'. Falls back to the language of the post being
 * rendered (block previews in the editor run outside Polylang's front-end context).
 */
function hcwa_lang() {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
	if ( ! $lang && function_exists( 'pll_get_post_language' ) ) {
		$id = get_the_ID();
		if ( ! $id && isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- block-renderer preview
			$id = absint( $_GET['post_id'] );
		}
		$lang = $id ? pll_get_post_language( $id, 'slug' ) : '';
	}
	if ( ! $lang && function_exists( 'pll_default_language' ) ) {
		$lang = pll_default_language( 'slug' );
	}
	return in_array( $lang, array( 'vi', 'en' ), true ) ? $lang : 'vi';
}

function hcwa_other_lang( $lang = null ) {
	return 'en' === ( $lang ? $lang : hcwa_lang() ) ? 'vi' : 'en';
}

/** Picks the current-language value from a [ 'vi' => …, 'en' => … ] pair. */
function hcwa_tx( $pair, $lang = null ) {
	if ( ! is_array( $pair ) ) {
		return (string) $pair;
	}
	$lang = $lang ? $lang : hcwa_lang();
	return isset( $pair[ $lang ] ) ? (string) $pair[ $lang ] : '';
}

/* ---------- UI strings ---------- */

/**
 * Bundled UI strings (strings/ui.json, synced from content/draft/ui.json so the prototype and
 * WordPress use the same wording). Editors can override any of them in
 * Languages → Translations; the Vietnamese text is the registered source string.
 */
function hcwa_strings() {
	static $strings = null;
	if ( null === $strings ) {
		$json    = file_get_contents( HCWA_CORE_DIR . 'strings/ui.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$strings = json_decode( $json, true );
		unset( $strings['_note'] );
	}
	return $strings;
}

/**
 * Translated UI string. `{x}` placeholders are replaced from $vars. Never echo a raw key on
 * the front end: a missing key is a bug, so it warns in debug mode and shows nothing.
 */
function hcwa_t( $key, $vars = array(), $lang = null ) {
	$lang    = $lang ? $lang : hcwa_lang();
	$strings = hcwa_strings();
	if ( ! isset( $strings[ $key ][ $lang ] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error( esc_html( "HCWA: missing UI string {$key} ({$lang})" ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
		return '';
	}
	$text   = $strings[ $key ][ $lang ];
	$source = $strings[ $key ]['vi'];
	if ( function_exists( 'pll_translate_string' ) ) {
		$override = pll_translate_string( $source, $lang );
		// Polylang returns the source when nothing was entered; only then keep the bundled text.
		if ( '' !== $override && ( 'vi' === $lang || $override !== $source ) ) {
			$text = $override;
		}
	}
	foreach ( $vars as $name => $value ) {
		$text = str_replace( '{' . $name . '}', (string) $value, $text );
	}
	return $text;
}

/** Escaped hcwa_t() for HTML text. */
function hcwa_e( $key, $vars = array() ) {
	echo esc_html( hcwa_t( $key, $vars ) );
}

/* ---------- numbers and dates (07-BILINGUAL: VI 300.000 and 08/02/2026, EN 300,000 and 8 Feb 2026) ---------- */

function hcwa_number( $value, $lang = null ) {
	$lang     = $lang ? $lang : hcwa_lang();
	$value    = (float) $value;
	$decimals = floor( $value ) === $value ? 0 : 1;
	return 'vi' === $lang ? number_format( $value, $decimals, ',', '.' ) : number_format( $value, $decimals, '.', ',' );
}

function hcwa_date( $timestamp_or_post = null, $lang = null ) {
	$lang      = $lang ? $lang : hcwa_lang();
	$timestamp = is_numeric( $timestamp_or_post ) ? (int) $timestamp_or_post : get_post_timestamp( $timestamp_or_post );
	if ( ! $timestamp ) {
		return '';
	}
	if ( 'vi' === $lang ) {
		return wp_date( 'd/m/Y', $timestamp );
	}
	// Month names come from a fixed list so the result never depends on the admin locale.
	$months = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
	return wp_date( 'j', $timestamp ) . ' ' . $months[ (int) wp_date( 'n', $timestamp ) - 1 ] . ' ' . wp_date( 'Y', $timestamp );
}

/* ---------- key pages and URLs ---------- */

/**
 * Pages the theme links to by role (header Donate button, footer, CTAs). Settings → HCWA
 * stores the Vietnamese page for each role; the English one is its Polylang translation.
 */
function hcwa_page_roles() {
	return array(
		'about'        => __( 'About Us', 'hcwa-core' ),
		'ourStory'     => __( 'Our story', 'hcwa-core' ),
		'team'         => __( 'Team', 'hcwa-core' ),
		'partners'     => __( 'Partners & sponsors', 'hcwa-core' ),
		'policies'     => __( 'Policies', 'hcwa-core' ),
		'ourWork'      => __( 'Our Work', 'hcwa-core' ),
		'impact'       => __( 'Impact & Reports', 'hcwa-core' ),
		'getInvolved'  => __( 'Get Involved', 'hcwa-core' ),
		'donate'       => __( 'Donate', 'hcwa-core' ),
		'volunteer'    => __( 'Volunteer', 'hcwa-core' ),
		'partner'      => __( 'Partner with us', 'hcwa-core' ),
		'inKind'       => __( 'In-kind gifts', 'hcwa-core' ),
		'contact'      => __( 'Contact', 'hcwa-core' ),
	);
}

/** Anchors inside role pages, per language (04-SITEMAP). */
function hcwa_anchor_routes() {
	return array(
		'safeguarding' => array( 'policies', array( 'vi' => 'bao-ve-tre-em', 'en' => 'safeguarding' ) ),
		'privacy'      => array( 'policies', array( 'vi' => 'quyen-rieng-tu', 'en' => 'privacy' ) ),
		'bakery'       => array( 'inKind', array( 'vi' => 'tiem-banh', 'en' => 'bakery' ) ),
		'centers'      => array( 'ourWork', array( 'vi' => 'co-so', 'en' => 'centers' ) ),
		'programs'     => array( 'ourWork', array( 'vi' => 'du-an', 'en' => 'programs' ) ),
	);
}

/** Post ID of a role page in a language, or 0. */
function hcwa_page_id( $role, $lang = null ) {
	$lang  = $lang ? $lang : hcwa_lang();
	$pages = (array) get_option( 'hcwa_pages', array() );
	$id    = isset( $pages[ $role ] ) ? (int) $pages[ $role ] : 0;
	if ( ! $id ) {
		return 0;
	}
	if ( function_exists( 'pll_get_post' ) ) {
		$translated = pll_get_post( $id, $lang );
		return $translated ? (int) $translated : 0;
	}
	return $id;
}

/** URL for a role ('donate'), an anchor route ('privacy'), 'home' or 'news'. */
function hcwa_url( $key, $lang = null ) {
	$lang = $lang ? $lang : hcwa_lang();
	if ( 'home' === $key ) {
		return function_exists( 'pll_home_url' ) ? pll_home_url( $lang ) : home_url( '/' );
	}
	if ( 'news' === $key ) {
		$id = (int) get_option( 'page_for_posts' );
		if ( $id && function_exists( 'pll_get_post' ) ) {
			$id = (int) pll_get_post( $id, $lang );
		}
		return $id ? get_permalink( $id ) : hcwa_url( 'home', $lang );
	}
	if ( 'reports' === $key ) {
		return hcwa_reports_url( $lang );
	}
	$anchors = hcwa_anchor_routes();
	if ( isset( $anchors[ $key ] ) ) {
		return hcwa_url( $anchors[ $key ][0], $lang ) . '#' . $anchors[ $key ][1][ $lang ];
	}
	$id = hcwa_page_id( $key, $lang );
	return $id ? get_permalink( $id ) : hcwa_url( 'home', $lang );
}

/* ---------- small HTML helpers ---------- */

/** Lucide outline icons (ISC), 24px grid, 1.5px stroke (05-DESIGN). Same set as the prototype. */
function hcwa_icon( $name, $class = 'icon' ) {
	$paths = array(
		'arrow'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'menu'     => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
		'close'    => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'copy'     => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'alert'    => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
		'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
		'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'     => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'pin'      => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
		'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
		'prev'     => '<path d="m15 18-6-6 6-6"/>',
		'next'     => '<path d="m9 18 6-6-6-6"/>',
		'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
		'link'     => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/** ` data-draft` in review mode when $draft is true (dashed outline, 05-DESIGN). */
function hcwa_draft_attr( $draft ) {
	return $draft && hcwa_review_mode() ? ' data-draft' : '';
}

/**
 * Card titles: no emoji (05-DESIGN), and decorative Unicode letters such as "𝕊𝔸ℕ𝕋𝔸" folded to
 * plain ones when the intl extension is there.
 */
function hcwa_clean_title( $title ) {
	$title = wp_strip_all_tags( (string) $title );
	if ( class_exists( 'Normalizer' ) ) {
		$normalized = Normalizer::normalize( $title, Normalizer::FORM_KC );
		$title      = false === $normalized ? $title : $normalized;
	}
	$title = preg_replace( '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]/u', '', $title );
	return trim( preg_replace( '/\s{2,}/u', ' ', $title ) );
}

/** Text area meta stored one item per line → array of non-empty lines. */
function hcwa_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $text ) ) ) );
}
