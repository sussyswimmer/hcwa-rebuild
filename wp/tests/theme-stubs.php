<?php
/**
 * Stubs the hcwa-2026 theme tests need on top of bootstrap.php: conditional tags read by
 * hcwa_language_links() (outside a request they are all false), wp_attachment_is_image() and
 * core's lazy-loading counter. Guarded like bootstrap.php.
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
if ( ! function_exists( 'wp_attachment_is_image' ) ) {
	/** True for an attachment post whose post_mime_type starts with image/. */
	function wp_attachment_is_image( $post = null ) {
		$post = get_post( $post );
		return $post instanceof WP_Post && 'attachment' === $post->post_type && isset( $post->post_mime_type ) && 0 === strpos( (string) $post->post_mime_type, 'image/' );
	}
}
// Core's lazy-loading counter and threshold (wp-includes/media.php), same behaviour.
if ( ! function_exists( 'wp_omit_loading_attr_threshold' ) ) {
	function wp_omit_loading_attr_threshold( $force = false ) {
		return 3;
	}
}
if ( ! function_exists( 'wp_increase_content_media_count' ) ) {
	function wp_increase_content_media_count( $amount = 1 ) {
		static $count = 0;
		$count += $amount;
		return $count;
	}
}
