<?php
/**
 * Reports library (11-WP-PORT.md 2.7): the sun head, the type chips (plain links,
 * ?report_type=), the reports as the prototype's li.doc rows, pagination or the empty state.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hcwa_title  = hcwa_theme_t( 'reportsTitle' );
$hcwa_active = hcwa_theme_report_type_filter();
$hcwa_base   = (string) get_post_type_archive_link( 'hcwa_report' );
$hcwa_base   = '' !== $hcwa_base ? $hcwa_base : hcwa_theme_url( 'reports' );

echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	array(
		'title' => $hcwa_title,
		'trail' => array(
			array( hcwa_theme_t( 'impact' ), hcwa_theme_url( 'impact' ) ),
			array( $hcwa_title ),
		),
	)
);
?>
<div class="day day--donate">
  <div class="container wp-list">
	<nav class="wp-chips" aria-label="<?php echo esc_attr( hcwa_theme_t( 'filterType' ) ); ?>">
	  <ul class="filters__list">
		<li><a class="chip chip--all" href="<?php echo esc_url( $hcwa_base ); ?>"<?php echo '' === $hcwa_active ? ' aria-current="true"' : ''; ?>><?php hcwa_theme_e( 'all' ); ?></a></li>
		<?php foreach ( hcwa_theme_report_types() as $hcwa_type ) : ?>
		<li><a class="chip chip--all" href="<?php echo esc_url( add_query_arg( 'report_type', $hcwa_type, $hcwa_base ) ); ?>"<?php echo $hcwa_type === $hcwa_active ? ' aria-current="true"' : ''; ?>><?php hcwa_theme_e( 'rt_' . $hcwa_type ); ?></a></li>
		<?php endforeach; ?>
	  </ul>
	</nav>
	<?php if ( have_posts() ) : ?>
	<ul class="docs__list">
		<?php
		// Rows link the file and print its size: load the files before the loop.
		hcwa_theme_prime_attachments( (array) ( $GLOBALS['wp_query']->posts ?? array() ), array( 'hcwa_file_id' ) );
		while ( have_posts() ) {
			the_post();
			echo hcwa_theme_doc_row( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>
	</ul>
		<?php get_template_part( 'template-parts/pagination' ); ?>
	<?php else : ?>
	<p class="wp-empty"><?php hcwa_theme_e( 'noReports' ); ?></p>
	<?php endif; ?>
  </div>
</div>
<?php
get_footer();
