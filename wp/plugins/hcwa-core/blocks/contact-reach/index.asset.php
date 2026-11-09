<?php
/**
 * Script dependencies and version of index.js, read by register_block_script_handle().
 * hcwa-blocks-editor is the shared helper script registered in includes/blocks.php.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array( 'hcwa-blocks-editor', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
	'version'      => defined( 'HCWA_CORE_VERSION' ) ? HCWA_CORE_VERSION : false,
);
