<?php
/**
 * Theme supports, menus, pattern category, image sizes.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'hcwa_theme_setup' );

function hcwa_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	// Editors build pages from the HCWA patterns only; core's generic patterns are off-brand.
	remove_theme_support( 'core-block-patterns' );

	$editor_styles = array( 'assets/css/site.css' );
	if ( file_exists( get_theme_file_path( 'assets/css/patterns.css' ) ) ) {
		$editor_styles[] = 'assets/css/patterns.css';
	}
	$editor_styles[] = 'assets/css/editor.css';
	add_editor_style( $editor_styles );

	// Menu location names are admin UI; Polylang adds a per-language assignment for each.
	register_nav_menus(
		array(
			'primary'        => __( 'Main menu', 'hcwa' ),
			'footer-explore' => __( 'Footer: Explore', 'hcwa' ),
			'footer-trust'   => __( 'Footer: Reports & policies', 'hcwa' ),
		)
	);

	// Widths from 05-DESIGN (480 / 768 / 1200 / 1600 / 2400); 768 and 1536/2048 come from core.
	add_image_size( 'hcwa-480', 480, 0 );
	add_image_size( 'hcwa-1200', 1200, 0 );
	add_image_size( 'hcwa-1600', 1600, 0 );

	// The page excerpt is the intro line under the H1 in the page header.
	add_post_type_support( 'page', 'excerpt' );
}

add_action( 'init', 'hcwa_theme_register_pattern_category' );

function hcwa_theme_register_pattern_category(): void {
	register_block_pattern_category(
		'hcwa',
		array(
			'label'       => 'HCWA',
			'description' => __( 'Hội Bảo trợ Trẻ em TP. Hồ Chí Minh / HCWA page sections', 'hcwa' ),
		)
	);
}

// Attachment pages show the image only through hcwa_image() (attachment.php), never core's
// ungated copy in the_content.
remove_filter( 'the_content', 'prepend_attachment' );

// No remote patterns from wordpress.org in the inserter either.
add_filter( 'should_load_remote_block_patterns', '__return_false' );

// Only the CSS of blocks actually on the page.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

add_filter( 'body_class', 'hcwa_theme_body_class' );

/**
 * Staging gets `hcwa-staging`, which turns on draft outlines and pending chips (CONTRACT rule 7).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function hcwa_theme_body_class( array $classes ): array {
	if ( ! hcwa_theme_is_production() ) {
		$classes[] = 'hcwa-staging';
	}
	$classes[] = 'hcwa-lang-' . hcwa_theme_lang();
	return $classes;
}

add_filter( 'wp_robots', 'hcwa_theme_robots', 99 );

/**
 * Non-production: <meta name='robots' content='noindex, nofollow'>. Uses core's wp_robots
 * so there is only ever one robots tag on the page.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function hcwa_theme_robots( array $robots ): array {
	if ( ! hcwa_theme_is_production() ) {
		return array(
			'noindex'  => true,
			'nofollow' => true,
		);
	}
	// Attachment pages are leftovers of the old site, not content: keep them out of search.
	if ( is_attachment() ) {
		$robots['noindex'] = true;
	}
	return $robots;
}

// Priority 5: before core's redirect_canonical (10), which would send a disabled attachment
// page to the raw file.
add_action( 'template_redirect', 'hcwa_theme_refuse_attachment_page', 5 );

/**
 * Attachment page of a photo the approval gate refuses in its context (or of any photo
 * without hcwa-core): a plain 404, so neither the photo nor its title or caption, which
 * often carry a child's name, is printed. Same steps as core's WP::handle_404().
 */
function hcwa_theme_refuse_attachment_page(): void {
	if ( ! is_attachment() || ! hcwa_theme_attachment_refused( (int) get_queried_object_id() ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

add_action( 'template_redirect', 'hcwa_theme_lang_notice_redirect' );

/**
 * ?hcwa_lang_notice=<lang> on a post that has a published translation in that language
 * by now (a bookmarked or shared switch link): go to the translation instead of saying the
 * article only exists in one language. 302, because the translation may be unpublished again.
 */
function hcwa_theme_lang_notice_redirect(): void {
	$lang = hcwa_theme_lang_notice_request();
	if ( '' === $lang || ! is_singular() ) {
		return;
	}
	$translation = hcwa_theme_published_translation( (int) get_queried_object_id(), $lang );
	if ( $translation > 0 && wp_safe_redirect( (string) get_permalink( $translation ), 302 ) ) {
		exit;
	}
}

add_action( 'pre_get_posts', 'hcwa_theme_main_query' );

/**
 * News archives show 12 posts per page (06-PAGES); the reports archive can filter by type.
 *
 * @param WP_Query $query Query being prepared.
 */
function hcwa_theme_main_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_search() || $query->is_date() || $query->is_tax( 'hcwa_focus_area' ) ) {
		$query->set( 'posts_per_page', 12 );
	}
	if ( $query->is_post_type_archive( 'hcwa_report' ) ) {
		$query->set( 'posts_per_page', 24 );
		$type = hcwa_theme_report_type_filter();
		if ( '' !== $type ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'   => 'hcwa_report_type',
						'value' => $type,
					),
				)
			);
		}
	}
}

/**
 * Report types (contract meta values).
 *
 * @return string[]
 */
function hcwa_theme_report_types(): array {
	return array( 'annual', 'financial', 'program', 'policy' );
}

/**
 * The ?report_type= filter on the reports archive, validated against the known types.
 */
function hcwa_theme_report_type_filter(): string {
	// Read-only filter link, no state change: no nonce needed.
	$type = isset( $_GET['report_type'] ) ? sanitize_key( wp_unslash( $_GET['report_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return in_array( $type, hcwa_theme_report_types(), true ) ? $type : '';
}

add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
