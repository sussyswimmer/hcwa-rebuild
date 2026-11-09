<?php
/**
 * section.story: the story and history from the post content (hcwa_center_body()) and the nested figures.
 * The markup is built by hcwa-core (hcwa_center_story_html()); without the plugin nothing prints.
 *
 * @package hcwa-2026
 *
 * @var array $args ['post' => WP_Post].
 */

defined( 'ABSPATH' ) || exit;

$hcwa_part_post = $args['post'] ?? null;
if ( $hcwa_part_post instanceof WP_Post && function_exists( 'hcwa_center_story_html' ) ) {
	echo "\n  " . hcwa_center_story_html( $hcwa_part_post ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
}
