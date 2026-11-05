<?php
/**
 * HCWA 2026 theme bootstrap.
 *
 * Content types, settings, strings and the publishing gates live in the hcwa-core plugin
 * (see wp/CONTRACT.md); this theme only presents them.
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
	'inc/navigation.php',
	'inc/admin.php',
);

foreach ( $hcwa_theme_includes as $hcwa_theme_file ) {
	require_once get_theme_file_path( $hcwa_theme_file );
}

unset( $hcwa_theme_includes, $hcwa_theme_file );
