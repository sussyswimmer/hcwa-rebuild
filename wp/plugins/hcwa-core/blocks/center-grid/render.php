<?php
/**
 * hcwa/center-grid. With followFilter on (Our Work hub), ?area= filters it on the server, so
 * the filter chips work without JavaScript.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_area = $attributes['followFilter'] && hcwa_request_area() ? hcwa_request_area() : $attributes['area'];
$hcwa_args = array( 'posts_per_page' => $attributes['count'] > 0 ? (int) $attributes['count'] : 20 );
if ( $hcwa_area ) {
	$hcwa_args['tax_query'] = array( array( 'taxonomy' => 'hcwa_area', 'field' => 'slug', 'terms' => $hcwa_area ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
}
$hcwa_centers = hcwa_get_centers( $hcwa_args );
if ( ! $hcwa_centers ) {
	return;
}
echo '<div ' . hcwa_block_wrapper( 'center-grid' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
foreach ( $hcwa_centers as $hcwa_center ) {
	echo hcwa_center_card( $hcwa_center ); // phpcs:ignore WordPress.Security.EscapeOutput
}
echo '</div>';
