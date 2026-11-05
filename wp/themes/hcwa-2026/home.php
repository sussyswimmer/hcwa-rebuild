<?php
/**
 * News hub: the posts page (/tin-tuc/, /en/news/).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/news-archive' );
get_footer();
