<?php
/**
 * Plugin Name:       HCWA Core
 * Plugin URI:        https://hcwa.org.vn
 * Description:       Content types, fields, bilingual UI strings, localized URLs and the safety gates (photo approval, verified figures, finance-confirmed donation details, no draft text on the live site) for the HCWA website.
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            HCWA website volunteers
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hcwa
 * Domain Path:       /languages
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

define( 'HCWA_CORE_VERSION', '0.1.1' ); // A version change re-flushes the rewrite rules (localized-urls.php).
define( 'HCWA_CORE_FILE', __FILE__ );
define( 'HCWA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'HCWA_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once HCWA_CORE_DIR . 'includes/helpers.php';
require_once HCWA_CORE_DIR . 'includes/strings.php';
require_once HCWA_CORE_DIR . 'includes/post-types.php';
require_once HCWA_CORE_DIR . 'includes/meta.php';
require_once HCWA_CORE_DIR . 'includes/media-approval.php';
require_once HCWA_CORE_DIR . 'includes/localized-urls.php';
require_once HCWA_CORE_DIR . 'includes/polylang.php';
require_once HCWA_CORE_DIR . 'includes/settings.php';
require_once HCWA_CORE_DIR . 'includes/impact.php';
require_once HCWA_CORE_DIR . 'includes/publish-guard.php';
require_once HCWA_CORE_DIR . 'includes/blocks.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once HCWA_CORE_DIR . 'includes/cli.php';
}

/**
 * Admin strings (text domain "hcwa"): languages/hcwa-vi.l10n.php. WordPress loads
 * the file just in time, the first time an "hcwa" string is translated.
 */
function hcwa_core_load_textdomain(): void {
	load_plugin_textdomain( 'hcwa', false, dirname( plugin_basename( HCWA_CORE_FILE ) ) . '/languages' );
}
add_action( 'init', 'hcwa_core_load_textdomain', 0 );

/**
 * Activation: register the types and URL rules in this request, then rebuild the
 * rewrite rules once.
 */
function hcwa_core_activate(): void {
	hcwa_register_post_types();
	hcwa_add_localized_rewrite_rules();
	flush_rewrite_rules();
	update_option( 'hcwa_core_rules_version', HCWA_CORE_VERSION );
}
register_activation_hook( __FILE__, 'hcwa_core_activate' );

/**
 * Deactivation: drop the stored rules so WordPress rebuilds them without ours.
 */
function hcwa_core_deactivate(): void {
	delete_option( 'rewrite_rules' );
	delete_option( 'hcwa_core_rules_version' );
}
register_deactivation_hook( __FILE__, 'hcwa_core_deactivate' );

/**
 * Without Polylang the site works in Vietnamese only; say so to administrators.
 */
function hcwa_core_polylang_notice(): void {
	if ( function_exists( 'pll_current_language' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'HCWA Core: Polylang is not active, so the website runs in Vietnamese only. English pages, the language switch and translated menus come back when Polylang is activated.', 'hcwa' )
	);
}
add_action( 'admin_notices', 'hcwa_core_polylang_notice' );
