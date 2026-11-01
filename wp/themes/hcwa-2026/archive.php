<?php
/**
 * Category, tag and date archives: same layout as the news hub.
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part(
	'template-parts/news-archive',
	null,
	array(
		'title' => wp_strip_all_tags( get_the_archive_title() ),
		'intro' => wp_strip_all_tags( get_the_archive_description() ),
	)
);
get_footer();
