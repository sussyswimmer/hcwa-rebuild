<?php
/**
 * Fallback template: any listing WordPress has no more specific template for.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/news-archive' );
get_footer();
