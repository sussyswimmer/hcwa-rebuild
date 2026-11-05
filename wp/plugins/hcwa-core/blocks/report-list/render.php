<?php
/**
 * hcwa/report-list: report rows (prototype reportRow() in .report-list): cover, title,
 * year and type, language badge ("PDF · Tiếng Việt"), file size. Newest year first;
 * reports without a year come last.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_type       = hcwa_blocks_enum( $attributes, 'reportType', array_merge( array( 'any' ), hcwa_blocks_report_types() ), 'any' );
$hcwa_limit      = hcwa_blocks_int( $attributes, 'limit', 6, 1, 50 );
$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();

$hcwa_reports = get_posts(
	array_merge(
		hcwa_blocks_query_args( 'hcwa_report', 200 ),
		array(
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	)
);

// Filter and sort on the cheap fields first; files and covers only for the rows shown.
$hcwa_rows = array();
foreach ( $hcwa_reports as $hcwa_report ) {
	if ( ! $hcwa_report instanceof WP_Post ) {
		continue;
	}
	if ( 'any' !== $hcwa_type && hcwa_meta( $hcwa_report->ID, 'hcwa_report_type' ) !== $hcwa_type ) {
		continue;
	}
	$hcwa_rows[] = array(
		'post' => $hcwa_report,
		'year' => (int) get_post_meta( $hcwa_report->ID, 'hcwa_year', true ),
	);
}
$hcwa_rows = array_slice( hcwa_blocks_sort_by_year( $hcwa_rows ), 0, $hcwa_limit );

if ( ! $hcwa_rows ) {
	echo hcwa_blocks_empty_note( 'blk_noReports' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}

// Covers and PDF files of the rows shown, loaded together (URL, metadata with file size).
hcwa_blocks_prime_attachments( array_column( $hcwa_rows, 'post' ), array( '_thumbnail_id', 'hcwa_file_id' ) );
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'report-list' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php
	foreach ( $hcwa_rows as $hcwa_row ) {
		echo hcwa_blocks_report_row( hcwa_blocks_report_data( $hcwa_row['post'] ), $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	}
	?>
</ul>
