<?php
/**
 * Not found, in the language of the URL (/en/… is English): a friendly line, search,
 * and links to Home, Our Work and Contact.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="section not-found">
	<div class="container">
		<div class="not-found__body">
			<h1><?php hcwa_theme_e( 'nf_title' ); ?></h1>
			<p class="lead"><?php hcwa_theme_e( 'nf_text' ); ?></p>
			<?php get_search_form(); ?>
			<ul class="link-list">
				<li><?php echo hcwa_theme_text_link( hcwa_theme_url( 'home' ), hcwa_theme_t( 'home' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></li>
				<li><?php echo hcwa_theme_text_link( hcwa_theme_url( 'ourWork' ), hcwa_theme_t( 'ourWork' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></li>
				<li><?php echo hcwa_theme_text_link( hcwa_theme_url( 'contact' ), hcwa_theme_t( 'contact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></li>
			</ul>
		</div>
	</div>
</section>
<?php
get_footer();
