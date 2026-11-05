<?php
/**
 * Writes wp/plugins/hcwa-core/blocks/blocks-manifest.php: every block folder's block.json
 * as one PHP array, keyed by folder name (the format of wp_register_block_metadata_collection()).
 * Run after changing a block.json:
 *
 *   php wp/tests/blocks-manifest-build.php
 *
 * blocks-files-test.php fails while the manifest and the block.json files differ.
 *
 * @package hcwa-core
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$hcwa_dir    = dirname( __DIR__ ) . '/plugins/hcwa-core/blocks';
$hcwa_blocks = array();
foreach ( glob( $hcwa_dir . '/*/block.json' ) as $hcwa_file ) {
	$hcwa_meta = json_decode( (string) file_get_contents( $hcwa_file ), true );
	if ( ! is_array( $hcwa_meta ) ) {
		fwrite( STDERR, "Not valid JSON: $hcwa_file\n" );
		exit( 1 );
	}
	$hcwa_blocks[ basename( dirname( $hcwa_file ) ) ] = $hcwa_meta;
}
ksort( $hcwa_blocks );

/**
 * PHP source for a value, WordPress style: array(), tabs, aligned arrows.
 *
 * @param mixed $value Decoded JSON value.
 * @param int   $depth Indentation level of the line the value starts on.
 */
$hcwa_export = static function ( $value, int $depth ) use ( &$hcwa_export ): string {
	if ( ! is_array( $value ) ) {
		return null === $value ? 'null' : var_export( $value, true );
	}
	if ( ! $value ) {
		return 'array()';
	}
	$indent = str_repeat( "\t", $depth + 1 );
	$list   = array_is_list( $value );
	$width  = $list ? 0 : max( array_map( static fn( $k ) => strlen( var_export( (string) $k, true ) ), array_keys( $value ) ) );
	$lines  = array();
	foreach ( $value as $key => $item ) {
		$prefix  = $list ? '' : str_pad( var_export( (string) $key, true ), $width ) . ' => ';
		$lines[] = $indent . $prefix . $hcwa_export( $item, $depth + 1 ) . ',';
	}
	return "array(\n" . implode( "\n", $lines ) . "\n" . str_repeat( "\t", $depth ) . ')';
};

$hcwa_source = <<<'PHP'
<?php
/**
 * Every block folder's block.json as PHP, keyed by folder name. WordPress reads this one
 * file (kept by opcache) instead of reading and decoding each block.json on every request
 * (wp_register_block_metadata_collection(), includes/blocks.php).
 *
 * Generated: do not edit. Change a block.json, then run
 * `php wp/tests/blocks-manifest-build.php`; wp/tests/blocks-files-test.php fails while
 * the two differ.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

return
PHP;

file_put_contents( $hcwa_dir . '/blocks-manifest.php', $hcwa_source . ' ' . $hcwa_export( $hcwa_blocks, 0 ) . ";\n" );
echo 'Wrote ' . count( $hcwa_blocks ) . " blocks to blocks/blocks-manifest.php\n";
