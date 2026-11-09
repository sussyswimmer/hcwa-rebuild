<?php
/**
 * Stubs for wp/tests/theme-assets-test.php (the assets stream of docs/plan/11-WP-PORT.md): the
 * style and script queues, editor styles, inline script tags and the conditional tags the
 * theme's inc/assets.php, inc/head.php and inc/art.php call. Every stub is guarded, and each
 * records what it was given in $GLOBALS['hcwa_test'] so a test can read it back:
 *   enqueued_styles   [] => [handle, src, deps, ver]
 *   enqueued_scripts  [] => [handle, src, deps, ver, args]
 *   dequeued_styles   [] => handle
 *   style_queue       the handles queued now (wp_styles()->queue)
 *   editor_styles     [] => the argument of add_editor_style()
 *
 * @package hcwa-2026
 */

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		$GLOBALS['hcwa_test']['enqueued_styles'][] = array( $handle, $src, $deps, $ver );
		$GLOBALS['hcwa_test']['style_queue'][]     = $handle;
	}
}
if ( ! function_exists( 'wp_dequeue_style' ) ) {
	function wp_dequeue_style( $handle ) {
		$GLOBALS['hcwa_test']['dequeued_styles'][] = $handle;
		$GLOBALS['hcwa_test']['style_queue']       = array_values( array_diff( $GLOBALS['hcwa_test']['style_queue'] ?? array(), array( $handle ) ) );
	}
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {
		$GLOBALS['hcwa_test']['enqueued_scripts'][] = array( $handle, $src, $deps, $ver, $args );
	}
}
if ( ! class_exists( 'HCWA_Test_Styles' ) ) {
	/** wp_styles() stand-in: only the queue. */
	class HCWA_Test_Styles {
		/**
		 * @param string $name Property.
		 * @return mixed
		 */
		public function __get( $name ) {
			return 'queue' === $name ? ( $GLOBALS['hcwa_test']['style_queue'] ?? array() ) : null;
		}
	}
}
if ( ! function_exists( 'wp_styles' ) ) {
	function wp_styles() {
		return new HCWA_Test_Styles();
	}
}
if ( ! function_exists( 'add_editor_style' ) ) {
	function add_editor_style( $stylesheet = 'editor-style.css' ) {
		$GLOBALS['hcwa_test']['editor_styles'][] = $stylesheet;
		return true;
	}
}
if ( ! function_exists( 'wp_get_inline_script_tag' ) ) {
	/** As WordPress 7.1 prints it: the data trimmed and wrapped in newlines. */
	function wp_get_inline_script_tag( $data, $attributes = array() ) {
		$attrs = '';
		foreach ( $attributes as $name => $value ) {
			$attrs .= true === $value ? ' ' . $name : sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) );
		}
		return sprintf( "<script%s>\n%s\n</script>\n", $attrs, trim( (string) $data, "\n\r " ) );
	}
}
if ( ! function_exists( 'is_admin_bar_showing' ) ) {
	function is_admin_bar_showing() {
		return hcwa_test_is( 'is_admin_bar_showing' );
	}
}
if ( ! function_exists( 'has_site_icon' ) ) {
	function has_site_icon( $blog_id = 0 ) {
		return hcwa_test_is( 'has_site_icon' );
	}
}
if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() {
		return hcwa_test_is( 'is_front_page' );
	}
}
if ( ! function_exists( 'wp_get_theme' ) ) {
	function wp_get_theme( $stylesheet = '' ) {
		return new class() {
			/**
			 * @param string $header Theme header.
			 */
			public function get( $header ) {
				return 'Version' === $header ? '0.2.0' : '';
			}
		};
	}
}
if ( ! class_exists( 'Walker_Nav_Menu' ) ) {
	/** Empty stand-in so the whole theme (inc/navigation.php's walker) loads in a test. */
	class Walker_Nav_Menu {}
}
if ( ! function_exists( 'is_404' ) ) {
	function is_404() {
		return hcwa_test_is( 'is_404' );
	}
}
if ( ! function_exists( 'is_page' ) ) {
	function is_page( $page = '' ) {
		return hcwa_test_is( 'is_page' );
	}
}
if ( ! function_exists( 'wp_get_document_title' ) ) {
	/** The browser title as core returns it, HTML-escaped; a test sets $GLOBALS['hcwa_test']['document_title']. */
	function wp_get_document_title() {
		return esc_html( (string) ( $GLOBALS['hcwa_test']['document_title'] ?? '' ) );
	}
}
