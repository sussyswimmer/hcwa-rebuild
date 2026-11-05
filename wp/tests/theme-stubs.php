<?php
/**
 * Stubs the hcwa-2026 theme tests need on top of bootstrap.php: conditional tags read by
 * hcwa_language_links(). Outside a request they are all false. Guarded like bootstrap.php.
 *
 * @package hcwa-2026
 */

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $post_types = '' ) {
		return false;
	}
}
if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() {
		return false;
	}
}
if ( ! function_exists( 'is_home' ) ) {
	function is_home() {
		return false;
	}
}
