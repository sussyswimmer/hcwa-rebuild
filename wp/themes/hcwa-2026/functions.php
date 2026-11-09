<?php
/**
 * HCWA 2026 theme bootstrap.
 *
 * Content types, settings, strings and the publishing gates live in the hcwa-core plugin
 * (see wp/CONTRACT.md); this theme only presents them. Its CSS and JS are the prototype's own
 * bundle, built into assets/dist/ by `npm --prefix prototype run export:wp`
 * (docs/plan/11-WP-PORT.md 1.1); inc/assets.php, inc/head.php and inc/art.php print it.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_theme_includes = array(
	'inc/strings.php',
	'inc/core-bridge.php',
	'inc/template-tags.php',
	'inc/setup.php',
	'inc/assets.php',
	'inc/head.php',
	'inc/art.php',
	'inc/navigation.php',
	'inc/admin.php',
);

foreach ( $hcwa_theme_includes as $hcwa_theme_file ) {
	require_once get_theme_file_path( $hcwa_theme_file );
}

unset( $hcwa_theme_includes, $hcwa_theme_file );
