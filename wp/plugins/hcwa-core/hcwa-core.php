<?php
/**
 * Plugin Name:       HCWA Core
 * Description:       Centers, programs, impact figures, reports and partners for hcwa.org.vn, with their fields, blocks and launch checks. Content lives here, not in the theme, so a theme change never loses it.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            HCWA volunteers
 * License:           GPL-2.0-or-later
 * Text Domain:       hcwa-core
 *
 * PHP 7.4 on purpose: the live host runs PHP 7.4.33 (checked 1 Nov 2026). No match, enums,
 * readonly, named arguments or nullsafe operators until HCWA's host is upgraded.
 */

defined( 'ABSPATH' ) || exit;

define( 'HCWA_CORE_VERSION', '0.1.0' );
define( 'HCWA_CORE_DIR', __DIR__ . '/' );
define( 'HCWA_CORE_URL', plugin_dir_url( __FILE__ ) );

require HCWA_CORE_DIR . 'inc/helpers.php';
require HCWA_CORE_DIR . 'inc/content-types.php';
require HCWA_CORE_DIR . 'inc/fields.php';
require HCWA_CORE_DIR . 'inc/media.php';
require HCWA_CORE_DIR . 'inc/polylang.php';
require HCWA_CORE_DIR . 'inc/settings.php';
require HCWA_CORE_DIR . 'inc/figures.php';
require HCWA_CORE_DIR . 'inc/components.php';
require HCWA_CORE_DIR . 'inc/blocks.php';
require HCWA_CORE_DIR . 'inc/checks.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require HCWA_CORE_DIR . 'inc/cli.php';
}

register_activation_hook( __FILE__, 'hcwa_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

function hcwa_activate() {
	hcwa_register_content_types();
	flush_rewrite_rules();
}
