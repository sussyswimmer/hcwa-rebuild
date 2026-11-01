<?php
/**
 * Parses every PHP file of the theme and plugin with the PHP version it runs under (7.4 via
 * scripts/lint-php.mjs), so syntax newer than the live host supports fails here, not live.
 * token_get_all() with TOKEN_PARSE throws ParseError exactly like `php -l`.
 */

$root   = isset( $argv[1] ) ? $argv[1] : '/src';
$failed = 0;
$count  = 0;
foreach ( array( 'plugins', 'themes', 'dev' ) as $dir ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		if ( '.php' !== substr( $file, -4 ) ) {
			continue;
		}
		++$count;
		try {
			token_get_all( file_get_contents( $file ), TOKEN_PARSE );
		} catch ( ParseError $e ) {
			++$failed;
			echo 'FAIL ', substr( $file, strlen( $root ) + 1 ), ':', $e->getLine(), ' ', $e->getMessage(), "\n";
		}
	}
}
echo 'PHP ', PHP_VERSION, ': ', $count, ' files, ', $failed, " with syntax errors\n";
exit( $failed ? 1 : 0 );
