<?php
/**
 * Small helpers the theme and blocks rely on: environment, language, UI strings,
 * number and date formatting, logical page URLs.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * True on the live site. wp_get_environment_type() defaults to 'production' when
 * nothing is configured, so an unconfigured install is treated as live (fail safe).
 */
function hcwa_is_production(): bool {
	return 'production' === wp_get_environment_type();
}

/**
 * The two site languages, Vietnamese first (it is the default language).
 *
 * @return string[]
 */
function hcwa_languages(): array {
	return array( 'vi', 'en' );
}

/**
 * Maps anything language-like ('en', 'en_GB', 'EN', false) to 'vi' or 'en'.
 *
 * @param mixed $lang Language slug or locale.
 */
function hcwa_normalize_lang( $lang ): string {
	return ( is_string( $lang ) && 0 === strpos( strtolower( $lang ), 'en' ) ) ? 'en' : 'vi';
}

/**
 * Current language: 'vi' or 'en'. 'vi' when Polylang is inactive or has no current
 * language (for example on admin screens set to "all languages").
 */
function hcwa_lang(): string {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language() : '';

	/**
	 * Filters the language HCWA helpers use for the current request.
	 *
	 * @param string|false $lang Polylang's current language slug, or '' / false.
	 */
	$lang = apply_filters( 'hcwa_lang', $lang );

	return hcwa_normalize_lang( $lang );
}

/**
 * The other site language.
 *
 * @param string|null $lang Defaults to the current language.
 */
function hcwa_other_lang( ?string $lang = null ): string {
	return 'en' === hcwa_normalize_lang( $lang ?? hcwa_lang() ) ? 'vi' : 'en';
}

/**
 * Endonym for a language, used as the accessible name in the language switch.
 * Language names are never translated (07-BILINGUAL).
 *
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_language_name( string $lang ): string {
	return 'en' === hcwa_normalize_lang( $lang ) ? 'English' : 'Tiếng Việt';
}

/**
 * Writes to the PHP error log and fires 'hcwa_log' (tests and debug tools listen to it).
 *
 * @param string $message Message without a prefix.
 */
function hcwa_log( string $message ): void {
	do_action( 'hcwa_log', $message );
	error_log( 'hcwa-core: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * UI string in the current language. Unescaped: escape at output.
 *
 * @param string $key  Dictionary key (content/draft/ui.json plus additions).
 * @param array  $vars Values for {name} placeholders.
 */
function hcwa_t( string $key, array $vars = array() ): string {
	return hcwa_t_in( $key, hcwa_lang(), $vars );
}

/**
 * UI string in a given language. Unescaped: escape at output.
 *
 * @param string $key  Dictionary key.
 * @param string $lang 'vi' or 'en'.
 * @param array  $vars Values for {name} placeholders.
 */
function hcwa_t_in( string $key, string $lang, array $vars = array() ): string {
	$strings = hcwa_strings();

	if ( ! isset( $strings[ $key ] ) ) {
		static $logged = array();
		if ( ! isset( $logged[ $key ] ) ) {
			$logged[ $key ] = true;
			hcwa_log( sprintf( 'Missing UI string "%s".', $key ) );
		}
		// Visitors never see key names; staging shows them so the gap is obvious.
		return hcwa_is_production() ? '' : $key;
	}

	// Recomputed only when the dictionary grows (filters added before init).
	static $ambiguous = array();
	static $size      = -1;
	if ( count( $strings ) !== $size ) {
		$size      = count( $strings );
		$ambiguous = hcwa_ambiguous_sources( $strings );
	}

	$source = (string) ( $strings[ $key ]['vi'] ?? '' );
	$text   = hcwa_translate_entry( $strings[ $key ], hcwa_normalize_lang( $lang ), null, isset( $ambiguous[ $source ] ) );

	return hcwa_t_vars( $text, $vars );
}

/**
 * Replaces {name} placeholders. Unknown placeholders stay as they are.
 *
 * @param string $text Text with placeholders.
 * @param array  $vars name => value.
 */
function hcwa_t_vars( string $text, array $vars ): string {
	if ( ! $vars || false === strpos( $text, '{' ) ) {
		return $text;
	}

	return (string) preg_replace_callback(
		'/\{(\w+)\}/',
		static function ( $m ) use ( $vars ) {
			return array_key_exists( $m[1], $vars ) ? (string) $vars[ $m[1] ] : $m[0];
		},
		$text
	);
}

/**
 * Number in the current language: 300.000 (vi) or 300,000 (en).
 *
 * @param float|int $n        Number.
 * @param int       $decimals Decimal places.
 */
function hcwa_number( float|int $n, int $decimals = 0 ): string {
	return hcwa_number_in( $n, hcwa_lang(), $decimals );
}

/**
 * Number in a given language. Deterministic: does not depend on the server locale.
 *
 * @param float|int $n        Number.
 * @param string    $lang     'vi' or 'en'.
 * @param int       $decimals Decimal places.
 */
function hcwa_number_in( float|int $n, string $lang, int $decimals = 0 ): string {
	$decimals = max( 0, $decimals );

	if ( 'en' === hcwa_normalize_lang( $lang ) ) {
		return number_format( (float) $n, $decimals, '.', ',' );
	}

	return number_format( (float) $n, $decimals, ',', '.' );
}

/**
 * Date in the current language: 08/02/2026 (vi) or 8 Feb 2026 (en).
 *
 * @param string $ymd 'Y-m-d', or a MySQL datetime (the time part is ignored).
 */
function hcwa_date( string $ymd ): string {
	return hcwa_date_in( $ymd, hcwa_lang() );
}

/**
 * Date in a given language. Returns the input unchanged if it is not a valid date.
 *
 * @param string $ymd  'Y-m-d' or 'Y-m-d H:i:s'.
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_date_in( string $ymd, string $lang ): string {
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', trim( $ymd ), $m ) ) {
		return $ymd;
	}

	$year  = (int) $m[1];
	$month = (int) $m[2];
	$day   = (int) $m[3];

	if ( ! checkdate( $month, $day, $year ) ) {
		return $ymd;
	}

	if ( 'en' === hcwa_normalize_lang( $lang ) ) {
		$months = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
		return $day . ' ' . $months[ $month - 1 ] . ' ' . $year;
	}

	return sprintf( '%02d/%02d/%04d', $day, $month, $year );
}

/**
 * Site root from the 'home' option, with one trailing slash. Links are built from the
 * raw option rather than home_url() so Polylang never adds a second /en/.
 */
function hcwa_home_base(): string {
	return rtrim( (string) get_option( 'home' ), '/' ) . '/';
}

/**
 * Logical page keys staff can map to real pages in Settings → HCWA.
 *
 * @return string[]
 */
function hcwa_page_keys(): array {
	return array( 'home', 'about', 'ourStory', 'team', 'partners', 'policies', 'ourWork', 'impact', 'news', 'getInvolved', 'donate', 'volunteer', 'partner', 'inKind', 'contact' );
}

/**
 * Default paths per logical page, mirrored from prototype/src/routes.mjs (04-SITEMAP).
 * Anchor keys point at a section of another page: 'base' names that page.
 *
 * @return array<string, array{vi: string, en: string, base?: string}>
 */
function hcwa_page_paths(): array {
	return array(
		'home'         => array( 'vi' => '', 'en' => 'en/' ),
		'about'        => array( 'vi' => 've-chung-toi/', 'en' => 'en/about/' ),
		'ourStory'     => array( 'vi' => 've-chung-toi/cau-chuyen/', 'en' => 'en/about/our-story/' ),
		'team'         => array( 'vi' => 've-chung-toi/doi-ngu/', 'en' => 'en/about/team/' ),
		'partners'     => array( 'vi' => 've-chung-toi/doi-tac/', 'en' => 'en/about/partners/' ),
		'policies'     => array( 'vi' => 've-chung-toi/chinh-sach/', 'en' => 'en/about/policies/' ),
		'ourWork'      => array( 'vi' => 'hoat-dong/', 'en' => 'en/our-work/' ),
		'impact'       => array( 'vi' => 'tac-dong/', 'en' => 'en/impact/' ),
		'news'         => array( 'vi' => 'tin-tuc/', 'en' => 'en/news/' ),
		'getInvolved'  => array( 'vi' => 'chung-tay/', 'en' => 'en/get-involved/' ),
		'donate'       => array( 'vi' => 'chung-tay/ung-ho/', 'en' => 'en/get-involved/donate/' ),
		'volunteer'    => array( 'vi' => 'chung-tay/tinh-nguyen/', 'en' => 'en/get-involved/volunteer/' ),
		'partner'      => array( 'vi' => 'chung-tay/doi-tac-doanh-nghiep/', 'en' => 'en/get-involved/partner/' ),
		'inKind'       => array( 'vi' => 'chung-tay/hien-vat/', 'en' => 'en/get-involved/in-kind/' ),
		'contact'      => array( 'vi' => 'lien-he/', 'en' => 'en/contact/' ),
		'centers'      => array( 'vi' => '#co-so', 'en' => '#centers', 'base' => 'ourWork' ),
		'programs'     => array( 'vi' => '#du-an', 'en' => '#programs', 'base' => 'ourWork' ),
		'reports'      => array( 'vi' => '#bao-cao', 'en' => '#reports', 'base' => 'impact' ),
		'bakery'       => array( 'vi' => '#tiem-banh', 'en' => '#bakery', 'base' => 'inKind' ),
		'safeguarding' => array( 'vi' => '#bao-ve-tre-em', 'en' => '#safeguarding', 'base' => 'policies' ),
		'privacy'      => array( 'vi' => '#quyen-rieng-tu', 'en' => '#privacy', 'base' => 'policies' ),
	);
}

/**
 * Default relative path (no leading slash) for a logical page, including any anchor.
 * Null for an unknown key.
 *
 * @param string $key  Logical page key.
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_page_path( string $key, string $lang ): ?string {
	$paths = hcwa_page_paths();
	$lang  = hcwa_normalize_lang( $lang );

	if ( ! isset( $paths[ $key ] ) ) {
		return null;
	}

	if ( isset( $paths[ $key ]['base'] ) ) {
		return $paths[ $paths[ $key ]['base'] ][ $lang ] . $paths[ $key ][ $lang ];
	}

	return $paths[ $key ][ $lang ];
}

/**
 * Absolute URL of a logical page in a language. Uses the page chosen in Settings → HCWA
 * (Vietnamese page, followed to its translation) and falls back to the default path.
 *
 * @param string      $key  Logical page key (see hcwa_page_paths()).
 * @param string|null $lang 'vi' or 'en'; defaults to the current language.
 */
function hcwa_page_url( string $key, ?string $lang = null ): string {
	$lang  = hcwa_normalize_lang( $lang ?? hcwa_lang() );
	$paths = hcwa_page_paths();

	if ( ! isset( $paths[ $key ] ) ) {
		hcwa_log( sprintf( 'Unknown page key "%s"; linking to the home page.', $key ) );
		return hcwa_page_url( 'home', $lang );
	}

	$base_key = $paths[ $key ]['base'] ?? $key;
	$anchor   = isset( $paths[ $key ]['base'] ) ? $paths[ $key ][ $lang ] : '';

	if ( 'home' === $base_key && function_exists( 'pll_home_url' ) ) {
		return pll_home_url( $lang );
	}

	$map     = hcwa_setting( 'page_map', array() );
	$page_id = is_array( $map ) ? absint( $map[ $base_key ] ?? 0 ) : 0;

	if ( ! $page_id && 'news' === $base_key ) {
		$page_id = (int) get_option( 'page_for_posts' );
	}

	if ( $page_id ) {
		$target = hcwa_translation_id( $page_id, $lang );
		if ( $target && 'publish' === get_post_status( $target ) ) {
			$url = get_permalink( $target );
			if ( $url ) {
				return $url . $anchor;
			}
		}
	}

	return hcwa_home_base() . hcwa_page_path( $key, $lang );
}

/**
 * The 'hcwa_lang_notice' query argument set by the language switch on an untranslated
 * news post: the language the visitor asked for ('vi' or 'en'), or ''.
 */
function hcwa_lang_notice_request(): string {
	if ( ! isset( $_GET['hcwa_lang_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}

	$value = sanitize_key( wp_unslash( $_GET['hcwa_lang_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return in_array( $value, hcwa_languages(), true ) ? $value : '';
}

/**
 * Removes emoji (and the spaces after them) from the start of a string, for titles
 * rendered in cards (05-DESIGN: no emoji in UI). Emoji later in the text are kept.
 *
 * @param string $s Text, usually a post title.
 */
function hcwa_strip_leading_emoji( string $s ): string {
	$pictographs = '\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{25A0}-\x{25FF}'
		. '\x{2190}-\x{21FF}\x{2934}\x{2935}\x{3030}\x{303D}\x{3297}\x{3299}\x{203C}\x{2049}\x{2122}\x{2139}\x{24C2}\x{00A9}\x{00AE}';
	$joiners     = '\x{FE0E}\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}';
	$spaces      = '\s\x{00A0}\x{2000}-\x{200B}\x{3000}';

	// A keycap emoji starts with a digit, # or *, so it is matched as a whole sequence.
	$pattern = '/^(?:[0-9#*]\x{FE0F}?\x{20E3}|[' . $pictographs . $joiners . ']|[' . $spaces . '])+/u';

	$out = preg_replace( $pattern, '', $s );

	return null === $out ? $s : $out;
}
