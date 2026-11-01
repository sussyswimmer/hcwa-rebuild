<?php
/**
 * hcwa/link. A role target ("donate", "ourWork", "privacy"…) resolves to the page in the
 * current language; a custom URL is used as typed.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_label = trim( wp_strip_all_tags( (string) $attributes['label'] ) );
if ( '' === $hcwa_label ) {
	return;
}
$hcwa_url = $attributes['target'] ? hcwa_url( $attributes['target'] ) : (string) $attributes['url'];
if ( ! $hcwa_url ) {
	return;
}
$hcwa_extra = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';

if ( 'text' === $attributes['kind'] ) {
	printf(
		'<a class="text-link%s" href="%s"><span>%s</span>%s</a>',
		esc_attr( $hcwa_extra ),
		esc_url( $hcwa_url ),
		esc_html( $hcwa_label ),
		hcwa_icon( 'arrow' ) // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG
	);
} else {
	printf(
		'<a class="btn btn--%s%s" href="%s">%s</a>',
		esc_attr( $attributes['kind'] ),
		esc_attr( $hcwa_extra ),
		esc_url( $hcwa_url ),
		esc_html( $hcwa_label )
	);
}
