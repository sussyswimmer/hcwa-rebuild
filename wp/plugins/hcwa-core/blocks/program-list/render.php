<?php
/**
 * hcwa/program-list.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_programs = hcwa_get_programs(
	array(
		'status' => $attributes['status'],
		'center' => $attributes['center'],
		'area'   => $attributes['followFilter'] && hcwa_request_area() ? hcwa_request_area() : $attributes['area'],
		'count'  => $attributes['count'] > 0 ? (int) $attributes['count'] : 50,
	)
);
if ( ! $hcwa_programs ) {
	return;
}
echo '<ul ' . hcwa_block_wrapper( 'program-list' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
foreach ( $hcwa_programs as $hcwa_program ) {
	echo hcwa_program_row( $hcwa_program ); // phpcs:ignore WordPress.Security.EscapeOutput
}
echo '</ul>';
