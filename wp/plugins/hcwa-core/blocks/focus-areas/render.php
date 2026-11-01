<?php
/**
 * hcwa/focus-areas: list, not cards (06-PAGES "What we do").
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_terms = get_terms(
	array(
		'taxonomy'   => 'hcwa_area',
		'hide_empty' => false,
		'lang'       => hcwa_lang(),
	)
);
if ( is_wp_error( $hcwa_terms ) || ! $hcwa_terms ) {
	return;
}
// Order by the "hcwa_order" term meta; terms without one go last, by name.
usort(
	$hcwa_terms,
	function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'hcwa_order', true );
		$ob = (int) get_term_meta( $b->term_id, 'hcwa_order', true );
		$oa = $oa ? $oa : 99;
		$ob = $ob ? $ob : 99;
		return $oa === $ob ? strcmp( $a->name, $b->name ) : $oa - $ob;
	}
);
$hcwa_work = hcwa_url( 'ourWork' );
echo '<ul ' . hcwa_block_wrapper( 'focus-list' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
foreach ( $hcwa_terms as $hcwa_term ) {
	echo '<li class="focus-item">';
	echo hcwa_image_slot( (int) get_term_meta( $hcwa_term->term_id, 'hcwa_icon', true ), array( 'code' => 'A6', 'label' => array( 'vi' => $hcwa_term->name, 'en' => $hcwa_term->name ), 'ratio' => '1:1', 'class' => 'ph--icon focus-item__icon', 'sizes' => '48px', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div><h3><a href="' . esc_url( add_query_arg( 'area', $hcwa_term->slug, $hcwa_work ) ) . '">' . esc_html( $hcwa_term->name ) . '</a></h3>';
	$hcwa_draft = (bool) get_term_meta( $hcwa_term->term_id, 'hcwa_draft', true );
	if ( $hcwa_term->description && ! ( $hcwa_draft && hcwa_is_production() ) ) {
		echo '<p' . hcwa_draft_attr( $hcwa_draft ) . '>' . esc_html( $hcwa_term->description ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div></li>';
}
echo '</ul>';
