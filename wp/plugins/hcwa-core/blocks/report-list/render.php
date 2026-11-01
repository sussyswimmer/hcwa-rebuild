<?php
/**
 * hcwa/report-list.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_reports = hcwa_get_reports(
	array(
		'type'    => $attributes['type'],
		'year'    => (int) $attributes['year'],
		'program' => $attributes['program'],
		'count'   => (int) $attributes['count'],
	)
);
if ( ! $hcwa_reports->have_posts() ) {
	return;
}
echo '<div ' . hcwa_block_wrapper( 'report-block' ) . '><ul class="report-list">'; // phpcs:ignore WordPress.Security.EscapeOutput
foreach ( $hcwa_reports->posts as $hcwa_report ) {
	echo hcwa_report_row( $hcwa_report ); // phpcs:ignore WordPress.Security.EscapeOutput
}
echo '</ul>';
if ( $attributes['showAllLink'] && $hcwa_reports->found_posts > count( $hcwa_reports->posts ) ) {
	echo '<p class="report-block__more">' . hcwa_text_link( hcwa_reports_url(), hcwa_t( 'allReports' ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
echo '</div>';
