<?php
/**
 * section.aims: goals and activities as slats.
 * The markup is built by hcwa-core (hcwa_program_aims_html()); without the plugin nothing prints.
 *
 * @package hcwa-2026
 *
 * @var array $args ['post' => WP_Post].
 */

defined( 'ABSPATH' ) || exit;

$hcwa_part_post = $args['post'] ?? null;
if ( $hcwa_part_post instanceof WP_Post && function_exists( 'hcwa_program_aims_html' ) ) {
	echo "\n  " . hcwa_program_aims_html( $hcwa_part_post ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
}
