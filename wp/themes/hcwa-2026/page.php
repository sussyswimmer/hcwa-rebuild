<?php
/**
 * Inner pages: breadcrumb + H1 + intro (the page excerpt), then the block body.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) {
	the_post();
	$hcwa_trail = array();
	foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $hcwa_ancestor ) {
		$hcwa_trail[] = array( get_the_title( $hcwa_ancestor ), get_permalink( $hcwa_ancestor ) );
	}
	$hcwa_trail[] = array( get_the_title(), '' );
	echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
		array(
			'trail'       => $hcwa_trail,
			'title'       => get_the_title(),
			'intro'       => hcwa_page_intro(),
			'intro_draft' => hcwa_intro_is_draft(),
		)
	);
	echo '<div class="page-body">';
	the_content();
	echo '</div>';
}
get_footer();
