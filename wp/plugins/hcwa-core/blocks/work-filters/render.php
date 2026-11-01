<?php
/**
 * hcwa/work-filters.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_terms = get_terms( array( 'taxonomy' => 'hcwa_area', 'hide_empty' => false, 'lang' => hcwa_lang() ) );
if ( is_wp_error( $hcwa_terms ) || ! $hcwa_terms ) {
	return;
}
usort(
	$hcwa_terms,
	function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'hcwa_order', true );
		$ob = (int) get_term_meta( $b->term_id, 'hcwa_order', true );
		return ( $oa ? $oa : 99 ) - ( $ob ? $ob : 99 );
	}
);
$hcwa_current = hcwa_request_area();
$hcwa_base    = get_permalink() ? get_permalink() : hcwa_url( 'ourWork' );
// Anything left to show under the chosen filter? Otherwise say so (the blocks below hide).
$hcwa_empty = false;
if ( $hcwa_current ) {
	$hcwa_tax   = array( array( 'taxonomy' => 'hcwa_area', 'field' => 'slug', 'terms' => $hcwa_current ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$hcwa_empty = ! hcwa_get_centers( array( 'tax_query' => $hcwa_tax, 'posts_per_page' => 1 ) ) && ! hcwa_get_programs( array( 'area' => $hcwa_current, 'count' => 1 ) );
}

echo '<nav ' . hcwa_block_wrapper( 'filters', array( 'aria-label' => hcwa_t( 'filterArea' ) ) ) . '><ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
printf( '<li><a class="chip" href="%s"%s>%s</a></li>', esc_url( $hcwa_base ), $hcwa_current ? '' : ' aria-current="true"', esc_html( hcwa_t( 'all' ) ) );
foreach ( $hcwa_terms as $hcwa_term ) {
	printf(
		'<li><a class="chip" href="%s"%s>%s</a></li>',
		esc_url( add_query_arg( 'area', $hcwa_term->slug, $hcwa_base ) ),
		$hcwa_current === $hcwa_term->slug ? ' aria-current="true"' : '',
		esc_html( $hcwa_term->name )
	);
}
echo '</ul>';
if ( $hcwa_empty ) {
	echo '<p class="filters__empty">' . esc_html( hcwa_t( 'noResults' ) ) . ' <a href="' . esc_url( $hcwa_base ) . '">' . esc_html( hcwa_t( 'showAll' ) ) . '</a></p>';
}
echo '</nav>';
