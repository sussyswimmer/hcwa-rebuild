<?php
/**
 * hcwa/impact-strip. "strip" is the homepage panel under the hero (2×2 on phones, 4 across on
 * desktop, link to Impact & Reports); "grid" is the plain row used on center and program pages.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_strip = 'strip' === $attributes['variant'];
$hcwa_html  = hcwa_figure_strip(
	$attributes['scope'],
	array(
		'min'   => $hcwa_strip ? max( 2, (int) $attributes['min'] ) : (int) $attributes['min'],
		'class' => $hcwa_strip ? 'figures--strip' : '',
	)
);
if ( '' === $hcwa_html ) {
	return; // 06-PAGES: too few verified figures → the section hides
}
if ( $hcwa_strip ) {
	printf( '<div %s>%s%s</div>', hcwa_block_wrapper( 'container impact-strip' ), $hcwa_html, hcwa_text_link( hcwa_url( 'impact' ), hcwa_t( 'seeImpact' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the helpers
} else {
	printf( '<div %s>%s</div>', hcwa_block_wrapper( 'impact-block' ), $hcwa_html ); // phpcs:ignore WordPress.Security.EscapeOutput
}
