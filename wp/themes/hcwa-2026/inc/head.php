<?php
/**
 * What the prototype prints in <head> besides the bundle (docs/plan/11-WP-PORT.md 1.2):
 *
 * - hcwa_theme_print_head_first(): header.php calls it before wp_head(), so the head-inline
 *   script (data-tier, data-motion, sun-pending, stages) runs before any stylesheet, then the
 *   View Transitions pageReveal script. Both are the prototype's own, exported to
 *   assets/generated/ (head-inline.production.js on production: the ?tier, ?motion=off and
 *   ?drafts=1 flags are compiled out there).
 * - <script type="application/json" id="i18n">: the strings the bundle reads, in the page
 *   language (it replaces window.hcwaI18n).
 * - data-lit on <html> for a page that opens on a lit header (the head-inline script reads it).
 * - hcwa_theme_bar_script(): the WordPress-only script header.php prints right after the EN bar
 *   and after the announcement slot, so both are decided before first paint.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * A generated file from assets/generated/ (written by the export), '' when missing.
 *
 * @param string $file Path inside assets/generated/.
 */
function hcwa_theme_generated( string $file ): string {
	static $cache = array();
	if ( ! isset( $cache[ $file ] ) ) {
		$path           = get_theme_file_path( 'assets/generated/' . ltrim( $file, '/' ) );
		$cache[ $file ] = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	}
	return $cache[ $file ];
}

/**
 * The head-inline script, then pageReveal: the first two scripts of every page.
 */
function hcwa_theme_print_head_first(): void {
	$inline = hcwa_theme_generated( hcwa_theme_is_production() ? 'head-inline.production.js' : 'head-inline.staging.js' );
	foreach ( array( $inline, hcwa_theme_generated( 'page-reveal.js' ) ) as $js ) {
		if ( '' !== $js ) {
			echo wp_get_inline_script_tag( $js ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated theme file, no request data.
		}
	}
}

add_action( 'wp_head', 'hcwa_theme_print_i18n', 99 );

/**
 * The bundle's strings (ui/dom.js reads #i18n). JSON_HEX_TAG keeps a "<" in a translation from
 * closing the script.
 */
function hcwa_theme_print_i18n(): void {
	$json = wp_json_encode( hcwa_theme_js_strings(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( is_string( $json ) ) {
		echo wp_get_inline_script_tag( $json, array( 'type' => 'application/json', 'id' => 'i18n' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with tags escaped.
	}
}

add_filter( 'language_attributes', 'hcwa_theme_language_attributes', 20, 2 );

/**
 * data-lit on a page that opens on a lit header (2.5), so the head-inline script can schedule
 * the sun-in. The front end only.
 *
 * @param string $output  Attributes so far (lang="…").
 * @param string $doctype 'html' or 'xhtml'.
 */
function hcwa_theme_language_attributes( string $output, string $doctype = 'html' ): string {
	if ( 'html' !== $doctype || is_admin() || is_feed() || str_contains( $output, 'data-lit' ) ) {
		return $output;
	}
	return hcwa_theme_assets_context()['lit'] ? $output . ' data-lit' : $output;
}

/**
 * The inline script that decides a top bar before first paint (assets/js/wp-bars.js), for
 * header.php to print right after that bar: 'en' after the EN bar (shows it for an English
 * browser that has not dismissed it), 'announce' after the announcement slot (hides an
 * announcement the visitor dismissed, and remembers a dismissal per announcement text).
 *
 * @param string $which 'en' or 'announce'.
 */
function hcwa_theme_bar_script( string $which ): string {
	if ( ! in_array( $which, array( 'en', 'announce' ), true ) ) {
		return '';
	}
	$path = get_theme_file_path( 'assets/js/wp-bars.js' );
	$js   = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	// The file is one function expression after its comment: call it with the element just printed.
	$js = rtrim( trim( (string) preg_replace( '#^\s*/\*.*?\*/#s', '', $js ) ), ';' );
	if ( '' === $js ) {
		return '';
	}
	return wp_get_inline_script_tag( $js . '(document.currentScript.previousElementSibling, "' . $which . '");' );
}
