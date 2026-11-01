<?php
/**
 * hcwa/language-notice.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_post = $attributes['postId'] ? get_post( (int) $attributes['postId'] ) : null;
if ( ! $hcwa_post || 'publish' !== $hcwa_post->post_status ) {
	return;
}
echo '<div ' . hcwa_block_wrapper( 'lang-notice-block' ) . '>' . hcwa_language_notice( $hcwa_post ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
