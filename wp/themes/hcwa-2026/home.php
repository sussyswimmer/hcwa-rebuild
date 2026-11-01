<?php
/**
 * News hub (the posts page, /tin-tuc/ and /en/news/): category chips, 12 cards per page,
 * numbered pagination. ?category=slug filters without leaving the hub.
 */

defined( 'ABSPATH' ) || exit;

get_header();
$hcwa_page = get_queried_object();
get_template_part(
	'template-parts/news-archive',
	null,
	array(
		'title'       => $hcwa_page instanceof WP_Post ? get_the_title( $hcwa_page ) : hcwa_t( 'news' ),
		'intro'       => $hcwa_page instanceof WP_Post ? hcwa_page_intro( $hcwa_page ) : '',
		'intro_draft' => $hcwa_page instanceof WP_Post && hcwa_intro_is_draft( $hcwa_page ),
	)
);
get_footer();
