<?php
/**
 * 404 (06-PAGES): a friendly line in the current language, search, links home.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="section not-found">
	<div class="container not-found__grid">
		<div>
			<h1><?php hcwa_e( 'nf_title' ); ?></h1>
			<p class="lead"><?php hcwa_e( 'nf_text' ); ?></p>
			<?php get_template_part( 'template-parts/search-form' ); ?>
			<ul class="link-list">
				<li><?php echo hcwa_text_link( hcwa_url( 'home' ), hcwa_t( 'home' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<li><?php echo hcwa_text_link( hcwa_url( 'ourWork' ), hcwa_t( 'ourWork' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
				<li><?php echo hcwa_text_link( hcwa_url( 'contact' ), hcwa_t( 'contact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
			</ul>
		</div>
		<?php echo hcwa_image_slot( 0, array( 'code' => 'C8', 'label' => array( 'vi' => 'Minh họa trang 404', 'en' => '404 illustration' ), 'ratio' => '1:1', 'illustration' => true, 'class' => 'not-found__art' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</section>
<?php
get_footer();
