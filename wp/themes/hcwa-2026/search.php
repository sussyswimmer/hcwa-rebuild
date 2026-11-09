<?php
/**
 * Search results in the page language (Polylang filters the query; 11-WP-PORT.md 2.7): the
 * sun head, the prototype's search form, the results as a dated list, pagination or the
 * empty state.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hcwa_query = trim( get_search_query( false ) );
$hcwa_title = '' !== $hcwa_query ? hcwa_theme_t( 'searchResults', array( 'q' => $hcwa_query ) ) : hcwa_theme_t( 'searchTitle' );

echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	array(
		'title' => $hcwa_title,
		'trail' => array( array( hcwa_theme_t( 'searchTitle' ) ) ),
	)
);
?>
<div class="day day--donate">
  <div class="container wp-list">
	<?php get_search_form(); ?>
	<?php if ( have_posts() ) : ?>
	<ol class="dated-list wp-results">
		<?php
		while ( have_posts() ) {
			the_post();
			echo hcwa_theme_dated_post( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>
	</ol>
		<?php get_template_part( 'template-parts/pagination' ); ?>
	<?php else : ?>
	<p class="wp-empty"><?php hcwa_theme_e( 'searchEmpty' ); ?></p>
	<?php endif; ?>
  </div>
</div>
<?php
get_footer();
