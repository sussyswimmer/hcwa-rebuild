<?php
/**
 * Stubs for wp/tests/runtime-scripts-test.php (scripts/wp-seed.php).
 *
 * @package hcwa-core
 */

if ( ! function_exists( 'wp_cache_flush' ) ) {
	/** Counts the calls in $GLOBALS['hcwa_test']['cache_flushes']. */
	function wp_cache_flush() {
		$GLOBALS['hcwa_test']['cache_flushes'] = ( $GLOBALS['hcwa_test']['cache_flushes'] ?? 0 ) + 1;
		return true;
	}
}
