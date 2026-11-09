<?php
/**
 * Runs every wp/tests/*-test.php file against the stubs in bootstrap.php.
 *
 *   php wp/tests/run.php            all tests
 *   php wp/tests/run.php core-      only files whose name contains "core-"
 *
 * A test file is plain PHP that calls the assert helpers below. Warnings and notices
 * count as failures. Exit code 1 when anything fails. hcwa_test_skip() records a check
 * that cannot run yet (for example a golden case before the export has written its
 * fixtures); skips are listed but do not fail the run. hcwa_test_isolated() runs PHP in a
 * separate process with the stubs, optionally without hcwa-core.
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

/**
 * Records a check that cannot run yet, with the reason.
 *
 * @param string $label What was skipped and why.
 */
function hcwa_test_skip( string $label ): void {
	$file = $GLOBALS['hcwa_test_file'] ?? '?';
	$GLOBALS['hcwa_test_skipped'][ $file ][] = $label;
}

/**
 * Runs PHP code in a separate process after bootstrap.php, e.g. to load the theme without
 * hcwa-core. Returns [exit code, output (stdout and stderr)].
 *
 * @param string $code   PHP code without the opening tag.
 * @param bool   $plugin Load hcwa-core (false sets HCWA_TEST_NO_PLUGIN=1).
 */
function hcwa_test_isolated( string $code, bool $plugin = true ): array {
	$file = tempnam( sys_get_temp_dir(), 'hcwa-iso-' );
	file_put_contents( $file, "<?php\nrequire " . var_export( __DIR__ . '/bootstrap.php', true ) . ";\n" . $code . "\n" );
	$env = getenv();
	if ( ! $plugin ) {
		$env['HCWA_TEST_NO_PLUGIN'] = '1';
	}
	$proc = proc_open( array( PHP_BINARY, '-d', 'display_errors=stderr', $file ), array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, null, $env );
	$out  = '';
	$exit = -1;
	if ( is_resource( $proc ) ) {
		$out  = (string) stream_get_contents( $pipes[1] ) . (string) stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		$exit = proc_close( $proc );
	}
	unlink( $file );
	return array( $exit, $out );
}

$GLOBALS['hcwa_test_skipped'] = array();
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
$hcwa_skip = 0;
foreach ( $GLOBALS['hcwa_test_results'] as $hcwa_name => $hcwa_result ) {
	// The "loaded" marker is bookkeeping, not an assertion.
	$hcwa_count   = $hcwa_result['pass'] - 1;
	$hcwa_skipped = $GLOBALS['hcwa_test_skipped'][ $hcwa_name ] ?? array();
	printf( "%s %s: %d passed, %d failed%s\n", $hcwa_result['fail'] ? 'FAIL' : 'ok  ', $hcwa_name, $hcwa_count, $hcwa_result['fail'], $hcwa_skipped ? ', ' . count( $hcwa_skipped ) . ' skipped' : '' );
	foreach ( $hcwa_result['failures'] as $hcwa_message ) {
		echo '    - ' . $hcwa_message . "\n";
	}
	foreach ( $hcwa_skipped as $hcwa_message ) {
		echo '    ~ skipped: ' . $hcwa_message . "\n";
	}
	$hcwa_pass += $hcwa_count;
	$hcwa_fail += $hcwa_result['fail'];
	$hcwa_skip += count( $hcwa_skipped );
}

printf( "\n%d passed, %d failed%s\n", $hcwa_pass, $hcwa_fail, $hcwa_skip ? ", $hcwa_skip skipped" : '' );
exit( $hcwa_fail > 0 ? 1 : 0 );
