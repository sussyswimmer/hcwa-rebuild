<?php
/**
 * Stubs the markup tests need on top of bootstrap.php (sections pages, center bodies).
 * Guarded like bootstrap.php.
 *
 * parse_blocks() is a small block-comment parser with the shape WordPress returns
 * (blockName, attrs, innerBlocks, innerHTML, innerContent; freeform HTML between top-level
 * blocks is a block named null). render_block() prints innerContent with each inner block
 * rendered in its place, wraps an hcwa/* block's output in <name>…</name> so a test can see
 * which blocks printed, and runs the render_block filter like WordPress does.
 *
 * Extra test state (cleared by hcwa_test_reset() with the rest):
 *   $GLOBALS['hcwa_test']['password'][post_id] => true for post_password_required()
 *
 * @package hcwa-core
 */

/**
 * Runs the callbacks hooked on a filter, in priority order, with their accepted argument count.
 *
 * @param string $hook  Filter name.
 * @param mixed  $value Value.
 * @param mixed  ...$args Extra arguments.
 * @return mixed
 */
function hcwa_test_run_filters( string $hook, $value, ...$args ) {
	$entries = $GLOBALS['hcwa_test']['hooks'][ $hook ] ?? array();
	usort( $entries, static fn( $a, $b ) => (int) $a[1] <=> (int) $b[1] );
	foreach ( $entries as $entry ) {
		if ( is_callable( $entry[0] ) ) {
			$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $args ), 0, max( 1, (int) $entry[2] ) ) );
		}
	}
	return $value;
}

if ( ! function_exists( 'parse_blocks' ) ) {
	/**
	 * Parses serialized blocks.
	 *
	 * @param string $content Post content.
	 * @return array[]
	 */
	function parse_blocks( $content ) {
		$content = (string) $content;
		preg_match_all( '/<!--\s+(\/)?wp:([a-z][a-z0-9_-]*(?:\/[a-z][a-z0-9_-]*)?)\s+(\{.*?\}\s+)?(\/)?-->/s', $content, $tokens, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
		$root  = array(
			'blockName'    => '#root',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
		$stack = array( $root );
		$at    = 0;
		$text  = static function ( array &$frame, string $html ): void {
			if ( '' === $html ) {
				return;
			}
			if ( '#root' === $frame['blockName'] ) {
				$frame['innerBlocks'][]  = array(
					'blockName'    => null,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => $html,
					'innerContent' => array( $html ),
				);
				$frame['innerContent'][] = null;
				return;
			}
			$frame['innerHTML']     .= $html;
			$frame['innerContent'][] = $html;
		};
		foreach ( $tokens as $t ) {
			$top = count( $stack ) - 1;
			$text( $stack[ $top ], substr( $content, $at, $t[0][1] - $at ) );
			$at   = $t[0][1] + strlen( $t[0][0] );
			$name = $t[2][0];
			$name = false === strpos( $name, '/' ) ? 'core/' . $name : $name;
			if ( '/' === ( $t[1][0] ?? '' ) ) {
				$block = array_pop( $stack );
				$top   = count( $stack ) - 1;
				$stack[ $top ]['innerBlocks'][]  = $block;
				$stack[ $top ]['innerContent'][] = null;
				continue;
			}
			$attrs = isset( $t[3] ) && '' !== trim( (string) $t[3][0] ) ? json_decode( trim( $t[3][0] ), true ) : array();
			$block = array(
				'blockName'    => $name,
				'attrs'        => is_array( $attrs ) ? $attrs : array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			);
			if ( '/' === ( $t[4][0] ?? '' ) ) {
				$stack[ $top ]['innerBlocks'][]  = $block;
				$stack[ $top ]['innerContent'][] = null;
			} else {
				$stack[] = $block;
			}
		}
		$text( $stack[ count( $stack ) - 1 ], substr( $content, $at ) );
		return $stack[0]['innerBlocks'];
	}
}

if ( ! function_exists( 'render_block' ) ) {
	/**
	 * Renders a parsed block (see the file comment).
	 *
	 * @param array $parsed Parsed block.
	 */
	function render_block( $parsed ) {
		$html  = '';
		$index = 0;
		foreach ( (array) ( $parsed['innerContent'] ?? array() ) as $chunk ) {
			$html .= is_string( $chunk ) ? $chunk : render_block( $parsed['innerBlocks'][ $index++ ] ?? array() );
		}
		$name = (string) ( $parsed['blockName'] ?? '' );
		if ( 0 === strpos( $name, 'hcwa/' ) ) {
			$tag  = substr( $name, 5 );
			$html = "<$tag>" . $html . "</$tag>";
		}
		return hcwa_test_run_filters( 'render_block', $html, $parsed );
	}
}

if ( ! function_exists( 'wp_filter_content_tags' ) ) {
	/**
	 * The img pass of WordPress's content filter: each <img> through wp_content_img_tag
	 * with the id of its wp-image-N class.
	 *
	 * @param string $content Content.
	 * @param string $context Context.
	 */
	function wp_filter_content_tags( $content, $context = null ) {
		return (string) preg_replace_callback(
			'/<img\b[^>]*>/i',
			static function ( array $m ) use ( $context ) {
				$id = preg_match( '/\bwp-image-(\d+)/', $m[0], $c ) ? (int) $c[1] : 0;
				return (string) hcwa_test_run_filters( 'wp_content_img_tag', $m[0], $context, $id );
			},
			(string) $content
		);
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '', $filter = 'raw' ) {
		return (string) ( array(
			'name'        => 'Hội Bảo trợ Trẻ em TP.HCM',
			'description' => '',
			'charset'     => 'UTF-8',
		)[ $show ] ?? '' );
	}
}

if ( ! function_exists( 'post_password_required' ) ) {
	function post_password_required( $post = null ) {
		$post = get_post( $post );
		return $post instanceof WP_Post && ! empty( $GLOBALS['hcwa_test']['password'][ $post->ID ] );
	}
}

if ( ! function_exists( 'get_the_password_form' ) ) {
	function get_the_password_form( $post = 0 ) {
		return '<form class="post-password-form"></form>';
	}
}
