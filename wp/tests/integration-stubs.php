<?php
/**
 * Stubs for wp/tests/integration-test.php: theme file helpers, so inc/assets.php loads.
 *
 * @package hcwa-core
 */

if ( ! function_exists( 'get_theme_file_path' ) ) {
	function get_theme_file_path( $file = '' ) {
		return dirname( __DIR__ ) . '/themes/hcwa-2026/' . ltrim( (string) $file, '/' );
	}
}
if ( ! function_exists( 'get_theme_file_uri' ) ) {
	function get_theme_file_uri( $file = '' ) {
		return 'https://hcwa.test/wp-content/themes/hcwa-2026/' . ltrim( (string) $file, '/' );
	}
}
