<?php
/**
 * Runs every wp/tests/*-test.php file against the stubs in bootstrap.php.
 *
 *   php wp/tests/run.php            all tests
 *   php wp/tests/run.php core-      only files whose name contains "core-"
 *
 * A test file is plain PHP that calls the assert helpers below. Warnings and notices
 * count as failures. Exit code 1 when anything fails.
 *
 * @package hcwa-core
 */

require __DIR__ . '/bootstrap.php';

$GLOBALS['hcwa_test_results'] = array();

/**
 * Records one result for the current file.
 *
 * @param bool   $ok    Passed.
 * @param string $label Description.
 */
function hcwa_test_record( bool $ok, string $label ): void {
	$file = $GLOBALS['hcwa_test_file'] ?? '?';
	if ( ! isset( $GLOBALS['hcwa_test_results'][ $file ] ) ) {
		$GLOBALS['hcwa_test_results'][ $file ] = array(
			'pass'     => 0,
			'fail'     => 0,
			'failures' => array(),
		);
	}
	if ( $ok ) {
		++$GLOBALS['hcwa_test_results'][ $file ]['pass'];
	} else {
		++$GLOBALS['hcwa_test_results'][ $file ]['fail'];
		$GLOBALS['hcwa_test_results'][ $file ]['failures'][] = $label;
	}
}

/**
 * Strict equality.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $label    Description.
 */
function hcwa_assert_same( $expected, $actual, string $label = '' ): void {
	$ok = $expected === $actual;
	hcwa_test_record( $ok, $ok ? $label : sprintf( "%s\n      expected: %s\n      actual:   %s", $label, var_export( $expected, true ), var_export( $actual, true ) ) );
}

/**
 * @param mixed  $value Value expected to be true.
 * @param string $label Description.
 */
function hcwa_assert_true( $value, string $label = '' ): void {
	hcwa_test_record( true === $value, $label . ( true === $value ? '' : ' (got ' . var_export( $value, true ) . ')' ) );
}

/**
 * @param mixed  $value Value expected to be false.
 * @param string $label Description.
 */
function hcwa_assert_false( $value, string $label = '' ): void {
	hcwa_test_record( false === $value, $label . ( false === $value ? '' : ' (got ' . var_export( $value, true ) . ')' ) );
}

/**
 * Substring check.
 *
 * @param string $needle   Expected substring.
 * @param string $haystack Text.
 * @param string $label    Description.
 */
function hcwa_assert_contains( string $needle, string $haystack, string $label = '' ): void {
	$ok = false !== strpos( $haystack, $needle );
	hcwa_test_record( $ok, $ok ? $label : sprintf( "%s\n      missing: %s\n      in:      %s", $label, $needle, $haystack ) );
}

$hcwa_filter = $argv[1] ?? '';
$hcwa_files  = glob( __DIR__ . '/*-test.php' );
sort( $hcwa_files );

foreach ( $hcwa_files as $hcwa_file ) {
	if ( '' !== $hcwa_filter && false === strpos( basename( $hcwa_file ), $hcwa_filter ) ) {
		continue;
	}

	hcwa_test_reset();
	$GLOBALS['hcwa_test_file'] = basename( $hcwa_file );
	hcwa_test_record( true, 'loaded' );

	set_error_handler(
		static function ( $severity, $message, $file, $line ) {
			throw new ErrorException( $message, 0, $severity, $file, $line );
		}
	);

	try {
		( static function ( $path ) {
			require $path;
		} )( $hcwa_file );
	} catch ( Throwable $e ) {
		hcwa_test_record( false, sprintf( 'uncaught %s: %s (%s:%d)', get_class( $e ), $e->getMessage(), basename( $e->getFile() ), $e->getLine() ) );
	}

	restore_error_handler();
}

$hcwa_pass = 0;
$hcwa_fail = 0;
foreach ( $GLOBALS['hcwa_test_results'] as $hcwa_name => $hcwa_result ) {
	// The "loaded" marker is bookkeeping, not an assertion.
	$hcwa_count = $hcwa_result['pass'] - 1;
	printf( "%s %s: %d passed, %d failed\n", $hcwa_result['fail'] ? 'FAIL' : 'ok  ', $hcwa_name, $hcwa_count, $hcwa_result['fail'] );
	foreach ( $hcwa_result['failures'] as $hcwa_message ) {
		echo '    - ' . $hcwa_message . "\n";
	}
	$hcwa_pass += $hcwa_count;
	$hcwa_fail += $hcwa_result['fail'];
}

printf( "\n%d passed, %d failed\n", $hcwa_pass, $hcwa_fail );
exit( $hcwa_fail > 0 ? 1 : 0 );
