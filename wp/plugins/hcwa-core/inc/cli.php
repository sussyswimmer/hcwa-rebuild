<?php
/**
 * WP-CLI: `wp hcwa coverage` and `wp hcwa audit`. Both exit with status 1 when a check fails,
 * so they can gate a deploy script.
 */

defined( 'ABSPATH' ) || exit;

class HCWA_CLI_Command {

	/**
	 * Lists pages, centers, programs, reports, figures, terms, menus and UI strings missing a
	 * Vietnamese or English version (07-BILINGUAL).
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, json or csv.
	 * ---
	 * default: table
	 * ---
	 */
	public function coverage( $args, $assoc ) {
		$this->output( hcwa_coverage_report(), $assoc );
	}

	/**
	 * Content and safety checks before launch: drafts, placeholders, social feeds, unapproved
	 * images, unverified figures, donation and contact confirmation (09-QA-CHECKLIST).
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, json or csv.
	 * ---
	 * default: table
	 * ---
	 */
	public function audit( $args, $assoc ) {
		$this->output( hcwa_audit_report(), $assoc );
	}

	private function output( $rows, $assoc ) {
		WP_CLI\Utils\format_items( isset( $assoc['format'] ) ? $assoc['format'] : 'table', $rows, array( 'status', 'check', 'detail' ) );
		if ( hcwa_report_failed( $rows ) ) {
			WP_CLI::halt( 1 );
		}
	}
}

WP_CLI::add_command( 'hcwa', 'HCWA_CLI_Command' );
