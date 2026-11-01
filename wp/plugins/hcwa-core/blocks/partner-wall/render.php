<?php
/**
 * hcwa/partner-wall.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_meta = array();
if ( $attributes['homepageOnly'] ) {
	$hcwa_meta[] = array( 'key' => 'hcwa_homepage', 'value' => '1' );
}
if ( $attributes['type'] ) {
	$hcwa_meta[] = array( 'key' => 'hcwa_type', 'value' => $attributes['type'] );
}
if ( ! hcwa_review_mode() ) {
	$hcwa_meta[] = array( 'key' => 'hcwa_logo_permission', 'value' => '1' );
}
$hcwa_partners = get_posts(
	array(
		'post_type'      => 'hcwa_partner',
		'posts_per_page' => max( 1, min( 48, (int) $attributes['count'] ) ),
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'meta_query'     => $hcwa_meta, // phpcs:ignore WordPress.DB.SlowDBQuery
	)
);
if ( ! $hcwa_partners ) {
	return;
}
echo '<ul ' . hcwa_block_wrapper( 'logo-wall', array( 'aria-label' => hcwa_t( 'partners' ) ) ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
foreach ( $hcwa_partners as $hcwa_partner ) {
	echo hcwa_partner_logo( $hcwa_partner ); // phpcs:ignore WordPress.Security.EscapeOutput
}
echo '</ul>';
