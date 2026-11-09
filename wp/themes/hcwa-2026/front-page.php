<?php
/**
 * Home (11-WP-PORT.md 2.4): the front page's hcwa/sections block printed by
 * hcwa_render_sections() (H2 to H10, no wrapper between them). The hero carries the one H1;
 * an sr-only H1 is added only when none was printed. If the site shows the latest posts on
 * the front, the news hub.
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
	$hcwa_post = get_post();
	$hcwa_html = $hcwa_post instanceof WP_Post ? hcwa_theme_render_sections( $hcwa_post ) : '';
	if ( '' === $hcwa_html && $hcwa_post instanceof WP_Post ) {
		// A front page not built from the sections: its blocks through the_content (2.7).
		$hcwa_html = '<div class="day day--donate"><div class="entry-content container">' . str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content() ) ) . '</div></div>';
	}
	if ( false === stripos( $hcwa_html, '<h1' ) ) {
		echo '<h1 class="sr-only">' . esc_html( hcwa_theme_names()['org'] ) . '</h1>';
	}
	echo $hcwa_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
endwhile;

get_footer();
