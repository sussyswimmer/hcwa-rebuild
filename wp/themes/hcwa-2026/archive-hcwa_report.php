<?php
/**
 * Reports library: type filter (plain links, ?report_type=), report rows with the
 * document language badge on every file, pagination.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hcwa_title  = hcwa_theme_t( 'reportsTitle' );
$hcwa_active = hcwa_theme_report_type_filter();
$hcwa_base   = (string) get_post_type_archive_link( 'hcwa_report' );
$hcwa_base   = '' !== $hcwa_base ? $hcwa_base : hcwa_theme_url( 'reports' );

get_template_part(
	'template-parts/page-header',
	null,
	array(
		'trail' => array(
			array( hcwa_theme_t( 'impact' ), hcwa_theme_url( 'impact' ) ),
			array( $hcwa_title ),
		),
		'title' => $hcwa_title,
	)
);
?>
<div class="section section--tight">
	<div class="container">
		<nav class="filters filters--flush" aria-label="<?php echo esc_attr( hcwa_theme_t( 'filterType' ) ); ?>">
			<ul>
				<li><a class="chip" href="<?php echo esc_url( $hcwa_base ); ?>"<?php echo '' === $hcwa_active ? ' aria-current="true"' : ''; ?>><?php hcwa_theme_e( 'all' ); ?></a></li>
				<?php foreach ( hcwa_theme_report_types() as $hcwa_type ) : ?>
					<li><a class="chip" href="<?php echo esc_url( add_query_arg( 'report_type', $hcwa_type, $hcwa_base ) ); ?>"<?php echo $hcwa_type === $hcwa_active ? ' aria-current="true"' : ''; ?>><?php hcwa_theme_e( 'rt_' . $hcwa_type ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<?php if ( have_posts() ) : ?>
			<ul class="report-list">
				<?php
				// Rows print the cover and link the file (URL and size): load both before the loop.
				hcwa_theme_prime_attachments( (array) ( $GLOBALS['wp_query']->posts ?? array() ), array( '_thumbnail_id', 'hcwa_file_id' ) );
				while ( have_posts() ) {
					the_post();
					get_template_part(
						'template-parts/report-row',
						null,
						array(
							'post'    => get_post(),
							'heading' => 'h2',
						)
					);
				}
				?>
			</ul>
			<?php get_template_part( 'template-parts/pagination' ); ?>
		<?php else : ?>
			<p class="empty-state"><?php hcwa_theme_e( 'noReports' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
