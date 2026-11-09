<?php
/**
 * hcwa/louvers: The four ways to help as louver slats. Prints the prototype's markup with no wrapper element
 * (docs/plan/11-WP-PORT.md 2.1 rule 3); the markup is built in includes/blocks-sections.php.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

echo hcwa_render_louvers( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
