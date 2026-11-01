<?php
/**
 * Fallback for anything without its own template: the news list layout.
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/news-archive', null, array( 'title' => wp_strip_all_tags( wp_get_document_title() ) ) );
get_footer();
