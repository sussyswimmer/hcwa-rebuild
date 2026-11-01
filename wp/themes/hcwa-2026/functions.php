<?php
/**
 * HCWA 2026 theme. Hybrid by decision 2.1 (03-STRATEGY-AND-DECISIONS): Polylang free can't
 * translate block-theme template parts, so header, footer, menus and archives are PHP.
 *
 * Content logic (fields, figures, approval rules, components) lives in the HCWA Core plugin;
 * this theme only lays things out.
 */

defined( 'ABSPATH' ) || exit;

define( 'HCWA_THEME_VERSION', '0.1.0' );

// Without HCWA Core nothing here can render safely (figures, images, strings): show a notice
// in the admin and a plain maintenance page to visitors instead of half a site.
if ( ! function_exists( 'hcwa_t' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The HCWA 2026 theme needs the HCWA Core plugin. Activate it under Plugins.', 'hcwa-2026' ) . '</p></div>';
		}
	);
	add_filter(
		'template_include',
		function () {
			return get_template_directory() . '/inc/no-core.php';
		}
	);
	return;
}

require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/nav.php';
require get_template_directory() . '/inc/patterns.php';
