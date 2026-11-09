<?php
/**
 * section.plate: the center at a glance, who it serves, what it does (2.5 P2).
 * The markup is built by hcwa-core (hcwa_center_plate_html()); without the plugin nothing prints.
 *
 * @package hcwa-2026
 *
 * @var array $args ['post' => WP_Post].
 */

defined( 'ABSPATH' ) || exit;

$hcwa_part_post = $args['post'] ?? null;
if ( $hcwa_part_post instanceof WP_Post && function_exists( 'hcwa_center_plate_html' ) ) {
	echo "\n  " . hcwa_center_plate_html( $hcwa_part_post ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
}
