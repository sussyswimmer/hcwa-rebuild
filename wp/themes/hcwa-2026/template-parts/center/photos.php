<?php
/**
 * section.photos: the approved photos of an unprotected center (2.5 "P2 gallery"), or, off
 * production when there is none, the hang. The lightbox is printed after the dusk band by
 * single-hcwa_center.php.
 *
 * @package hcwa-2026
 *
 * @var array $args ['post' => WP_Post, 'photos' => ['section' => string]].
 */

defined( 'ABSPATH' ) || exit;

$hcwa_part_post = $args['post'] ?? null;
$hcwa_photos    = (string) ( $args['photos']['section'] ?? '' );
if ( '' !== $hcwa_photos ) {
	echo "\n  " . $hcwa_photos . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts in hcwa-core.
} elseif ( $hcwa_part_post instanceof WP_Post ) {
	get_template_part( 'template-parts/center/hang', null, array( 'post' => $hcwa_part_post ) );
}
