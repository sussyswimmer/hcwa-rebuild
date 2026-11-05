<?php
/**
 * Stubs the hcwa-2026 theme tests need on top of bootstrap.php: conditional tags read by
 * hcwa_language_links() and the meta description (false unless a test sets
 * $GLOBALS['hcwa_test']['is']), the queried object ($GLOBALS['hcwa_test']['queried']),
 * wp_attachment_is_image(), attachment metadata (the _wp_attachment_metadata post meta),
 * wp_html_excerpt() and core's lazy-loading counter. Guarded like bootstrap.php.
 *
 * @package hcwa-2026
 */

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $post_types = '' ) {
		return hcwa_test_is( 'is_singular' );
	}
}
if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $taxonomy = '', $term = '' ) {
		return hcwa_test_is( 'is_tax' );
	}
}
if ( ! function_exists( 'term_description' ) ) {
	function term_description( $term = 0, $deprecated = null ) {
		return '';
	}
}
if ( ! function_exists( 'get_queried_object' ) ) {
	function get_queried_object() {
		return $GLOBALS['hcwa_test']['queried'] ?? null;
	}
}
if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
	function wp_get_attachment_metadata( $attachment_id = 0, $unfiltered = false ) {
		$meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		return is_array( $meta ) ? $meta : false;
	}
}
if ( ! function_exists( 'wp_html_excerpt' ) ) {
	/** Same steps as core (wp-includes/formatting.php:5468). */
	function wp_html_excerpt( $str, $count, $more = null ) {
		$str     = wp_strip_all_tags( $str );
		$excerpt = mb_substr( $str, 0, $count );
		$excerpt = preg_replace( '/&[^;\s]{0,6}$/', '', $excerpt );
		return $str !== $excerpt ? trim( $excerpt ) . ( null === $more ? '' : $more ) : $excerpt;
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
