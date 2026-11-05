<?php
/**
 * hcwa/center-grid: center cards (prototype centerCard() in .center-grid), in the order
 * set by "Order" on each center, then by name. Photos go through hcwa_image() with the
 * center as context (contract gating rule 2). data-areas feeds the Our Work ?area= filter.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_limit      = hcwa_blocks_int( $attributes, 'limit', 3, 1, 24 );
$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();

$hcwa_centers = get_posts(
	array_merge(
		hcwa_blocks_query_args( 'hcwa_center', $hcwa_limit ),
		array(
			'orderby' => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	)
);

if ( ! $hcwa_centers ) {
	echo hcwa_blocks_empty_note( 'blk_noCenters' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'center-grid', 'data-filter-group' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php
	foreach ( $hcwa_centers as $hcwa_center ) {
		if ( $hcwa_center instanceof WP_Post ) {
			echo hcwa_blocks_center_card( hcwa_blocks_center_data( $hcwa_center ), $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
	}
	?>
</div>
