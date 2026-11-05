<?php
/**
 * Every admin string of hcwa-core (text domain "hcwa") has a Vietnamese translation in
 * languages/hcwa-vi.l10n.php, with the same placeholders.
 *
 * @package hcwa-core
 */

$plugin_dir = dirname( __DIR__ ) . '/plugins/hcwa-core';
$l10n       = include $plugin_dir . '/languages/hcwa-vi.l10n.php';

hcwa_assert_true( is_array( $l10n ) && isset( $l10n['messages'] ) && is_array( $l10n['messages'] ), 'l10n file returns headers and messages' );
hcwa_assert_same( 'vi', $l10n['language'] ?? '', 'language header' );

$functions = array( '__', 'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e', '_e' );
$same      = array( 'Zalo', 'HCWA', 'Email', 'Website', 'Logo', 'YYYY' );
$msgids    = array();

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin_dir . '/includes', FilesystemIterator::SKIP_DOTS ) );
foreach ( array_merge( iterator_to_array( $files ), array( new SplFileInfo( $plugin_dir . '/hcwa-core.php' ) ) ) as $file ) {
	if ( 'php' !== $file->getExtension() || in_array( $file->getFilename(), array( 'blocks.php', 'strings-data.php' ), true ) ) {
		continue;
	}
	$tokens = token_get_all( (string) file_get_contents( $file->getPathname() ) );
	$count  = count( $tokens );
	for ( $i = 0; $i < $count; $i++ ) {
		if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0] || ! in_array( $tokens[ $i ][1], $functions, true ) ) {
			continue;
		}
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			++$j;
		}
		if ( '(' !== $tokens[ $j ] ) {
			continue;
		}
		++$j;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			++$j;
		}
		if ( ! is_array( $tokens[ $j ] ) || T_CONSTANT_ENCAPSED_STRING !== $tokens[ $j ][0] ) {
			continue;
		}
		$literal = $tokens[ $j ][1];
		$msgids[] = "'" === $literal[0]
			? str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), substr( $literal, 1, -1 ) )
			: stripcslashes( substr( $literal, 1, -1 ) );
	}
}

$msgids = array_values( array_unique( $msgids ) );
hcwa_assert_true( count( $msgids ) > 250, 'admin strings found in the plugin' );

$missing = array_values( array_diff( $msgids, array_keys( $l10n['messages'] ), $same ) );
hcwa_assert_same( array(), $missing, 'every admin string has a Vietnamese translation' );

foreach ( $l10n['messages'] as $original => $translation ) {
	preg_match_all( '/%(?:\d\$)?[sd]/', (string) $original, $a );
	preg_match_all( '/%(?:\d\$)?[sd]/', (string) $translation, $b );
	sort( $a[0] );
	sort( $b[0] );
	if ( $a[0] !== $b[0] ) {
		hcwa_test_record( false, "placeholders differ: $original" );
	}
}
hcwa_assert_true( true, 'placeholders checked' );
