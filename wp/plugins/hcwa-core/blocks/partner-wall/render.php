<?php
/**
 * hcwa/partner-wall: one-tone logo wall (prototype .logo-wall, 06-PAGES: max 12,
 * "show on homepage"). On the live site a partner appears only with "Permission to show
 * the logo" and a logo (contract gating rule 5). Staging shows every partner and marks
 * the ones still waiting for permission.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_limit      = hcwa_blocks_int( $attributes, 'limit', 12, 1, 48 );
$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();

$hcwa_args = array_merge(
	hcwa_blocks_query_args( 'hcwa_partner', 200 ),
	array(
		'orderby' => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
);
if ( hcwa_blocks_bool( $attributes, 'homeOnly', true ) ) {
	$hcwa_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'   => 'hcwa_show_on_home',
			'value' => '1',
		),
	);
}

$hcwa_items = array();
foreach ( get_posts( $hcwa_args ) as $hcwa_partner ) {
	if ( count( $hcwa_items ) >= $hcwa_limit ) {
		break;
	}
	if ( ! $hcwa_partner instanceof WP_Post ) {
		continue;
	}
	$hcwa_data = hcwa_blocks_partner_data( $hcwa_partner, $hcwa_production );
	if ( null !== $hcwa_data ) {
		$hcwa_items[] = hcwa_blocks_partner_item( $hcwa_data, $hcwa_lang );
	}
}

if ( ! $hcwa_items ) {
	echo hcwa_blocks_empty_note( 'blk_noPartners' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'logo-wall', 'aria-label' => hcwa_t( 'partnerLogo' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php echo implode( '', $hcwa_items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
</ul>
