<?php
/**
 * Stubs the hcwa/* block tests need on top of bootstrap.php. Guarded like bootstrap.php.
 *
 * Extra test state (cleared by hcwa_test_reset() with the rest):
 *   $GLOBALS['hcwa_test']['terms'][post_id][taxonomy] => WP_Term[] for get_the_terms()
 *   post meta '_thumbnail_id' for get_post_thumbnail_id()
 *
 * @package hcwa-core
 */

if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
	/**
	 * Like WordPress: block class first, extra class merged, other attributes appended.
	 *
	 * @param array $extra Extra attributes.
	 */
	function get_block_wrapper_attributes( $extra = array() ) {
		$class = trim( 'wp-block-hcwa-test ' . (string) ( $extra['class'] ?? '' ) );
		$out   = array( 'class="' . esc_attr( $class ) . '"' );
		foreach ( (array) $extra as $name => $value ) {
			if ( 'class' !== $name && is_scalar( $value ) && ! is_bool( $value ) ) {
				$out[] = $name . '="' . esc_attr( (string) $value ) . '"';
			}
		}
		return implode( ' ', $out );
	}
}
if ( ! function_exists( 'wp_get_attachment_image' ) ) {
	function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {
		if ( (int) $attachment_id <= 0 ) {
			return '';
		}
		$attr = is_array( $attr ) ? $attr : array();
		$html = '<img src="https://hcwa.test/wp-content/uploads/' . (int) $attachment_id . '-' . esc_attr( $size ) . '.jpg"';
		foreach ( $attr as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
		}
		return $html . '>';
	}
}
if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $post = null ) {
		$post = get_post( $post );
		return $post ? (int) get_post_meta( $post->ID, '_thumbnail_id', true ) : 0;
	}
}
if ( ! function_exists( 'get_the_terms' ) ) {
	function get_the_terms( $post, $taxonomy ) {
		$post = get_post( $post );
		$list = $post ? ( $GLOBALS['hcwa_test']['terms'][ $post->ID ][ $taxonomy ] ?? array() ) : array();
		return $list ? $list : false;
	}
}
if ( ! function_exists( 'get_the_category' ) ) {
	function get_the_category( $post_id = false ) {
		$terms = get_the_terms( $post_id, 'category' );
		return is_array( $terms ) ? $terms : array();
	}
}
if ( ! function_exists( 'has_excerpt' ) ) {
	function has_excerpt( $post = 0 ) {
		$post = get_post( $post );
		return $post && '' !== (string) $post->post_excerpt;
	}
}
if ( ! function_exists( 'get_the_excerpt' ) ) {
	function get_the_excerpt( $post = null ) {
		$post = get_post( $post );
		return $post ? (string) $post->post_excerpt : '';
	}
}
if ( ! function_exists( 'strip_shortcodes' ) ) {
	function strip_shortcodes( $content ) {
		return (string) preg_replace( '/\[[^\]]+\]/', '', (string) $content );
	}
}
if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = null ) {
		$words = preg_split( '/\s+/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $words ) <= $num_words ) {
			return implode( ' ', $words );
		}
		return implode( ' ', array_slice( $words, 0, $num_words ) ) . ( $more ?? '…' );
	}
}
