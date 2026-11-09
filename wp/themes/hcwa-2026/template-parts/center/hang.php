<?php
/**
 * section.hang: where approved photos will hang, off production only, never for a protected center.
 * The markup is built by hcwa-core (hcwa_center_hang_html()); without the plugin nothing prints.
 *
 * @package hcwa-2026
 *
 * @var array $args ['post' => WP_Post].
 */

defined( 'ABSPATH' ) || exit;

$hcwa_part_post = $args['post'] ?? null;
if ( $hcwa_part_post instanceof WP_Post && function_exists( 'hcwa_center_hang_html' ) ) {
	echo "\n  " . hcwa_center_hang_html( $hcwa_part_post ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
}
