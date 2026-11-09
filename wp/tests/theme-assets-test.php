<?php
/**
 * hcwa-2026 theme assets: everything the "assets" stream of docs/plan/11-WP-PORT.md owns
 * (inc/assets.php, theme.json, the theme's CSS and JS files). Moved here from
 * theme-gates-test.php, theme-helpers-test.php and integration-test.php on 9 Nov 2026 so each
 * stream owns its tests; the assertions are unchanged and describe the theme as it is before
 * the port (site.css, site.js, bars.js, the Fraunces theme.json). The assets stream rewrites
 * them for the port: the manifest enqueue without query strings, the head-inline and
 * pageReveal emission, #i18n, HCWA_Theme_Art and the theme loading without hcwa-core.
 *
 * @package hcwa-2026
 */

$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';
require_once $hcwa_theme_dir . '/inc/assets.php'; // Theme file helpers: integration-stubs.php.
require_once $hcwa_theme_dir . '/inc/setup.php';

$hcwa_read = static fn( string $file ): string => (string) file_get_contents( $hcwa_theme_dir . '/' . $file );

// ---------- Meta description (from theme-gates-test.php) ----------
hcwa_assert_contains( 'hcwa_theme_public_text( trim( wp_strip_all_tags( (string) $description ) ) )', $hcwa_read( 'inc/assets.php' ), 'meta description goes through the draft check' );

// ---------- Top bars decided before first paint (from theme-gates-test.php) ----------
$hcwa_header = $hcwa_read( 'header.php' );
$hcwa_bars = hcwa_theme_bars_js();
hcwa_assert_contains( 'window.hcwaBars = bars;', $hcwa_bars, 'bars.js is inlined in the head' );
hcwa_assert_contains( 'hcwaBars.enBar(document.currentScript.previousElementSibling)', $hcwa_header, 'EN bar decided right after it is printed' );
hcwa_assert_contains( 'hcwaBars.announce(document.currentScript.previousElementSibling)', $hcwa_header, 'announcement decided right after it is printed' );
$hcwa_site_js = $hcwa_read( 'assets/js/site.js' );
hcwa_assert_false( str_contains( $hcwa_site_js, 'navigator.language' ), 'site.js no longer shows the EN bar after paint' );
hcwa_assert_contains( 'bars.announceKey(box)', $hcwa_site_js, 'site.js uses the same announcement key as bars.js' );
hcwa_assert_contains( '"hcwa-en-bar"', $hcwa_site_js, 'site.js remembers the EN bar dismissal' );
hcwa_assert_contains( '"hcwa-en-bar"', $hcwa_bars, 'bars.js reads the same EN bar key' );
hcwa_assert_contains( 's.tabIndex = desktop.matches ? -1 : 0;', $hcwa_site_js, 'inert desktop footer summaries leave the tab order' );

// ---------- CSS hard rules and layout (from theme-gates-test.php) ----------
$hcwa_css = $hcwa_read( 'assets/css/site.css' );
hcwa_assert_true( (bool) preg_match( '/\.entry-content blockquote, \.entry-content \.wp-block-quote \{[^}]*font: 500 var\(--fs-lg\) \/ var\(--lh-body\)/', $hcwa_css ), 'quotes use the body line-height' );
hcwa_assert_true( (bool) preg_match( '/\.entry-content blockquote cite \{[^}]*\/ 1\.6 /', $hcwa_css ), 'quote citations at 1.6' );
hcwa_assert_false( str_contains( $hcwa_css, '.entry-content :where(li) + li' ), 'list spacing no longer leaks into block lists' );
hcwa_assert_contains( '.entry-content :where(ul:not([class]), ol:not([class]), .wp-block-list) > li + li', $hcwa_css, 'list spacing for prose lists' );
hcwa_assert_contains( '.entry-content--page > :where([class*="wp-block-hcwa-"]):not(.alignwide):not(.alignfull) { width: min(100% - 2 * var(--gutter), var(--container)); margin-inline: auto; }', $hcwa_css, 'hcwa blocks in a page body keep to the container' );
hcwa_assert_true( (bool) preg_match( '/\.lang-switch a \{[^}]*min-width: 44px;[^}]*min-height: 44px;/', $hcwa_css ), 'VI / EN links are 44 x 44' );
hcwa_assert_true( (bool) preg_match( '/\.nav__toggle \{ width: 44px; height: 44px;/', $hcwa_css ), 'submenu toggle is 44 x 44' );
hcwa_assert_false( (bool) preg_match( '/\.nav__toggle \{[^}]*margin-left: -/', $hcwa_css ), 'submenu toggle does not overlap its link' );
hcwa_assert_true( (bool) preg_match( '/\.report-row__title a \{[^}]*min-height: 44px;/', $hcwa_css ), 'report links are 44 px tall' );

// ---------- theme.json and site.css (from theme-helpers-test.php) ----------
// theme.json: schema version, tokens, fonts that exist, no uppercase, editor restrictions.
$hcwa_json = json_decode( (string) file_get_contents( $hcwa_theme_dir . '/theme.json' ), true );
hcwa_assert_same( 3, $hcwa_json['version'] ?? null, 'theme.json version 3' );
hcwa_assert_false( $hcwa_json['settings']['color']['defaultPalette'] ?? null, 'default palette off' );
hcwa_assert_false( $hcwa_json['settings']['color']['custom'] ?? null, 'custom colors off' );
hcwa_assert_same( array(), $hcwa_json['settings']['color']['gradients'] ?? null, 'no gradients' );
hcwa_assert_false( $hcwa_json['settings']['typography']['customFontSize'] ?? null, 'custom font sizes off' );
hcwa_assert_same( '720px', $hcwa_json['settings']['layout']['contentSize'] ?? null, 'content size' );
hcwa_assert_same( '1200px', $hcwa_json['settings']['layout']['wideSize'] ?? null, 'wide size' );
hcwa_assert_same( '1.65', $hcwa_json['styles']['typography']['lineHeight'] ?? null, 'body line-height 1.65' );
hcwa_assert_same( '1.2', $hcwa_json['styles']['elements']['heading']['typography']['lineHeight'] ?? null, 'heading line-height 1.2' );
hcwa_assert_same( 'none', $hcwa_json['styles']['elements']['heading']['typography']['textTransform'] ?? null, 'headings never uppercase' );
foreach ( $hcwa_json['settings']['typography']['fontFamilies'] as $hcwa_family ) {
	foreach ( $hcwa_family['fontFace'] as $hcwa_face ) {
		foreach ( $hcwa_face['src'] as $hcwa_src ) {
			hcwa_assert_true( str_starts_with( $hcwa_src, 'file:./assets/fonts/' ) && is_file( $hcwa_theme_dir . '/' . substr( $hcwa_src, 7 ) ), "font file exists: $hcwa_src" );
		}
	}
}
$hcwa_palette = array_column( $hcwa_json['settings']['color']['palette'], 'color', 'slug' );
$hcwa_css     = (string) file_get_contents( $hcwa_theme_dir . '/assets/css/site.css' );
foreach ( array( 'paper', 'ink', 'blue-700', 'sun-400' ) as $hcwa_slug ) {
	hcwa_assert_contains( '--color-' . $hcwa_slug . ': ' . $hcwa_palette[ $hcwa_slug ] . ';', $hcwa_css, "palette $hcwa_slug matches the CSS token" );
}

// Donation rows: the copy button sits inside the <dd> (a <dl> group holds only dt and dd),
// so the dd is the grid and the button its second column.
hcwa_assert_contains( '.pay-details dd > .btn { grid-column: 2; grid-row: 1 / span 2;', $hcwa_css, 'copy button is placed inside its dd' );
hcwa_assert_same( 0, preg_match( '/\.pay-details__row\s*>\s*:last-child/', $hcwa_css ), 'no rule still targets the old row-level button slot' );

// CSS: never uppercase, drafts outlined on staging only.
hcwa_assert_same( 0, preg_match( '/text-transform:\s*uppercase/i', $hcwa_css ), 'no uppercase in site.css' );
hcwa_assert_contains( '.hcwa-staging [data-draft]', $hcwa_css, 'draft outlines need the staging class' );

// ---------- Strings for site.js and the Our Work chips (from integration-test.php) ----------
$hcwa_js = hcwa_theme_js_strings();
foreach ( array( 'copy', 'copied', 'copyFailed', 'close', 'noResults', 'showAll' ) as $hcwa_key ) {
	hcwa_assert_true( isset( $hcwa_js[ $hcwa_key ] ) && '' !== $hcwa_js[ $hcwa_key ] && $hcwa_key !== $hcwa_js[ $hcwa_key ], "site.js string $hcwa_key is exported" );
}

// site.js reads the Our Work chips from the pattern's plain ?area= links and adds the
// empty message itself; the blocks print the hooks it needs.
$hcwa_site_js = (string) file_get_contents( $hcwa_theme_dir . '/assets/js/site.js' );
hcwa_assert_contains( 'searchParams.get("area")', $hcwa_site_js, 'site.js reads ?area= from chip links' );
hcwa_assert_contains( 'data-filter-empty', $hcwa_site_js, 'site.js creates the empty-filter message' );
