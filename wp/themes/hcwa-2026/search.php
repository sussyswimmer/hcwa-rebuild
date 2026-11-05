<?php
/**
 * Search results in the page language (Polylang filters the query), with the search form
 * on top, compact result rows and pagination.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hcwa_query = trim( get_search_query( false ) );
$hcwa_title = '' !== $hcwa_query ? hcwa_theme_t( 'searchResults', array( 'q' => $hcwa_query ) ) : hcwa_theme_t( 'searchTitle' );

get_template_part(
	'template-parts/page-header',
	null,
	array(
		'trail' => array( array( hcwa_theme_t( 'searchTitle' ) ) ),
		'title' => $hcwa_title,
	)
);
?>
<div class="section section--tight">
	<div class="container search-page">
		<?php get_search_form(); ?>

		<?php if ( have_posts() ) : ?>
			<ul class="news-list search-results">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part(
						'template-parts/card',
						null,
						array(
							'post'    => get_post(),
							'variant' => 'item',
							'heading' => 'h2',
							'excerpt' => true,
						)
					);
				}
				?>
			</ul>
			<?php get_template_part( 'template-parts/pagination' ); ?>
		<?php else : ?>
			<p class="empty-state"><?php hcwa_theme_e( 'searchEmpty' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
