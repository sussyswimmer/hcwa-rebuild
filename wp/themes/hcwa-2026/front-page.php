<?php
/**
 * Home page: the static front page's blocks (hero, impact strip, centers, … from the
 * page-home pattern). If the site is set to show latest posts on the front, the news hub.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

if ( is_home() ) {
	get_template_part( 'home' );
	return;
}

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_content = str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content() ) );
	// One H1 per page: the hero pattern carries it; if the editor removed the hero, keep a hidden one.
	if ( false === stripos( $hcwa_content, '<h1' ) ) {
		echo '<h1 class="sr-only">' . esc_html( wp_strip_all_tags( get_bloginfo( 'name' ) ) ) . '</h1>';
	}
	?>
	<div class="entry-content entry-content--page entry-content--home">
		<?php echo $hcwa_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
	</div>
	<?php
endwhile;

get_footer();
