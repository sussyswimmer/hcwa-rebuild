<?php
/**
 * hcwa/impact-strip: verified figures for the organization, a center or a program as section.counts
 * (contract gating rule 1: production needs 2 verified; staging shows from 1 with the pending
 * chip). Markup in includes/blocks.php (hcwa_counts_html()).
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

echo hcwa_render_impact_strip( $attributes, $block ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
