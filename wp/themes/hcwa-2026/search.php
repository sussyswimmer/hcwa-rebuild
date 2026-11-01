<?php
/**
 * Search results in the current language.
 */

defined( 'ABSPATH' ) || exit;

get_header();
$hcwa_q = get_search_query();
echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'trail' => array( array( hcwa_t( 'nf_searchBtn' ), '' ) ),
		'title' => $hcwa_q ? hcwa_t( 'searchResults', array( 'q' => $hcwa_q ) ) : hcwa_t( 'nf_search' ),
	)
);
?>
<section class="section section--tight" aria-label="<?php echo esc_attr( hcwa_t( 'nf_searchBtn' ) ); ?>">
	<div class="container">
		<?php get_template_part( 'template-parts/search-form' ); ?>
		<?php if ( have_posts() ) : ?>
			<ul class="news-list search-results">
				<?php
				while ( have_posts() ) {
					the_post();
					echo hcwa_news_item( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</ul>
			<?php echo hcwa_pagination(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>
			<p class="empty-state"><?php hcwa_e( 'noSearchResults' ); ?></p>
			<ul class="link-list">
				<li><?php echo hcwa_text_link( hcwa_url( 'home' ), hcwa_t( 'home' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<li><?php echo hcwa_text_link( hcwa_url( 'ourWork' ), hcwa_t( 'ourWork' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<li><?php echo hcwa_text_link( hcwa_url( 'contact' ), hcwa_t( 'contact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
			</ul>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
