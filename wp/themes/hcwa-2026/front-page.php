<?php
/**
 * Homepage (06-PAGES): the body is block patterns (hero, impact strip, focus areas, centers,
 * programs, news, ways to help, partners, contact band), edited in the block editor.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) {
	the_post();
	echo '<div class="page-body page-body--home">';
	the_content();
	echo '</div>';
}
get_footer();
