<?php
/**
 * hcwa/program-list: program rows (prototype programRow() in .program-list), active
 * ones first (06-PAGES). centerId 0 lists every program, except on a center page where
 * it means "programs run by this center".
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_status     = hcwa_blocks_enum( $attributes, 'status', array( 'any', 'active', 'completed' ), 'any' );
$hcwa_limit      = hcwa_blocks_int( $attributes, 'limit', 4, 1, 50 );
$hcwa_center_id  = hcwa_blocks_int( $attributes, 'centerId', 0, 0, PHP_INT_MAX );
$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();

if ( 0 === $hcwa_center_id ) {
	$hcwa_context = hcwa_blocks_context_post_id( $block ?? null );
	if ( $hcwa_context && 'hcwa_center' === get_post_type( $hcwa_context ) ) {
		$hcwa_center_id = $hcwa_context;
	}
}

$hcwa_args = array_merge(
	hcwa_blocks_query_args( 'hcwa_program', 100 ),
	array(
		'orderby' => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
);
if ( $hcwa_center_id > 0 ) {
	// hcwa_center_id is a shared field: it stores the Vietnamese original's ID.
	$hcwa_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'   => 'hcwa_center_id',
			'value' => hcwa_original_id( $hcwa_center_id ),
			'type'  => 'NUMERIC',
		),
	);
}

$hcwa_rows = array();
foreach ( get_posts( $hcwa_args ) as $hcwa_program ) {
	if ( ! $hcwa_program instanceof WP_Post ) {
		continue;
	}
	$hcwa_row = hcwa_blocks_program_data( $hcwa_program );
	// Status is filtered here, not in SQL: a program saved without a status row is "active".
	if ( 'any' === $hcwa_status || $hcwa_status === $hcwa_row['status'] ) {
		$hcwa_rows[] = $hcwa_row;
	}
}
$hcwa_rows = array_slice( hcwa_blocks_sort_programs( $hcwa_rows ), 0, $hcwa_limit );

if ( ! $hcwa_rows ) {
	echo hcwa_blocks_empty_note( 'blk_noPrograms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'program-list', 'data-filter-group' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php
	foreach ( $hcwa_rows as $hcwa_row ) {
		echo hcwa_blocks_program_row( $hcwa_row, $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	}
	?>
</ul>
