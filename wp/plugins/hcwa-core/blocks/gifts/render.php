<?php
/**
 * hcwa/gifts: What a gift does. Staging only until HCWA sets the amounts. Prints the prototype's markup with no wrapper element
 * (docs/plan/11-WP-PORT.md 2.1 rule 3); the markup is built in includes/blocks-sections.php.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

echo hcwa_render_gifts( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
