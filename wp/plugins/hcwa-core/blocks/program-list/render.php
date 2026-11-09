<?php
/**
 * hcwa/program-list: ul.program-list of program rows (prototype programRow()), active first. On a
 * center page, centerId 0 means that center.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

echo hcwa_render_program_list( $attributes, $block ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
