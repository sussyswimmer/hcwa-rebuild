<?php
/**
 * Reports library (/tac-dong/bao-cao/, /en/impact/reports/): filters by type and year, a
 * language badge on every PDF (06-PAGES "Impact & Reports" 5).
 */

defined( 'ABSPATH' ) || exit;

get_header();
$hcwa_type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$hcwa_year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$hcwa_base = hcwa_reports_url();

// Years that have at least one report in this language, newest first.
$hcwa_years = array();
foreach ( get_posts( array( 'post_type' => 'hcwa_report', 'posts_per_page' => -1, 'fields' => 'ids', 'lang' => hcwa_lang() ) ) as $hcwa_id ) {
	$hcwa_y = (int) get_post_meta( $hcwa_id, 'hcwa_year', true );
	if ( $hcwa_y ) {
		$hcwa_years[ $hcwa_y ] = true;
	}
}
krsort( $hcwa_years );

echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'trail' => array( array( hcwa_t( 'impact' ), hcwa_url( 'impact' ) ), array( hcwa_t( 'reportsTitle' ), '' ) ),
		'title' => hcwa_t( 'reportsTitle' ),
	)
);

$hcwa_chip = function ( $label, $args, $current ) use ( $hcwa_base ) {
	return '<li><a class="chip" href="' . esc_url( add_query_arg( $args, $hcwa_base ) ) . '"' . ( $current ? ' aria-current="true"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
};
?>
<div class="container filters-stack">
	<nav class="filters" aria-label="<?php echo esc_attr( hcwa_t( 'filterType' ) ); ?>">
		<ul>
			<?php
			echo $hcwa_chip( hcwa_t( 'all' ), array_filter( array( 'year' => $hcwa_year ) ), ! $hcwa_type ); // phpcs:ignore WordPress.Security.EscapeOutput
			foreach ( array_keys( hcwa_report_types() ) as $hcwa_type_key ) {
				echo $hcwa_chip( hcwa_report_type_label( $hcwa_type_key ), array_filter( array( 'type' => $hcwa_type_key, 'year' => $hcwa_year ) ), $hcwa_type === $hcwa_type_key ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</ul>
	</nav>
	<?php if ( count( $hcwa_years ) > 1 ) : ?>
		<nav class="filters" aria-label="<?php echo esc_attr( hcwa_t( 'filterYear' ) ); ?>">
			<ul>
				<?php
				echo $hcwa_chip( hcwa_t( 'allYears' ), array_filter( array( 'type' => $hcwa_type ) ), ! $hcwa_year ); // phpcs:ignore WordPress.Security.EscapeOutput
				foreach ( array_keys( $hcwa_years ) as $hcwa_y ) {
					echo $hcwa_chip( (string) $hcwa_y, array_filter( array( 'type' => $hcwa_type, 'year' => $hcwa_y ) ), $hcwa_year === $hcwa_y ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</ul>
		</nav>
	<?php endif; ?>
</div>

<section class="section section--tight" aria-label="<?php echo esc_attr( hcwa_t( 'reportsTitle' ) ); ?>">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<ul class="report-list">
				<?php
				while ( have_posts() ) {
					the_post();
					echo hcwa_report_row( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</ul>
			<?php echo hcwa_pagination(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>
			<p class="empty-state"><?php hcwa_e( 'noReports' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
