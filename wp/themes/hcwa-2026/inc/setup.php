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

	// Editor styles (the bundle's CSS and editor.css) are added by inc/assets.php (11-WP-PORT.md 1.5).

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

add_filter( 'body_class', 'hcwa_theme_body_class', 99 );

/**
 * The body classes are an allow list (11-WP-PORT.md 2.1 rule 8): WordPress adds `search` on
 * search results, and `.search` is a prototype component. So only the page class
 * (hcwa_theme_page_context()), `hcwa-staging` off production (draft outlines, pending chips),
 * the language, and `logged-in admin-bar` when present.
 *
 * @param string[] $classes Body classes from WordPress.
 * @return string[]
 */
function hcwa_theme_body_class( array $classes ): array {
	$context = function_exists( 'hcwa_theme_page_context' ) ? hcwa_theme_page_context() : array( 'body_class' => 'page-wp' );
	$out     = array( (string) $context['body_class'] );
	if ( ! hcwa_theme_is_production() ) {
		$out[] = 'hcwa-staging';
	}
	$out[] = 'hcwa-lang-' . hcwa_theme_lang();
	foreach ( array( 'logged-in', 'admin-bar' ) as $keep ) {
		if ( in_array( $keep, $classes, true ) ) {
			$out[] = $keep;
		}
	}
	return $out;
}

add_filter( 'document_title_parts', 'hcwa_theme_title_parts', 20 );
add_filter( 'document_title_separator', 'hcwa_theme_title_separator' );

/**
 * The page title (1.2 #4, prototype layout()): the home page is "{orgName} (HCWA)", every
 * other page "{title} | {shortName}". Draft text never reaches the live site's title.
 *
 * @param array $parts title, page, tagline, site.
 * @return array
 */
function hcwa_theme_title_parts( $parts ): array {
	$parts = is_array( $parts ) ? $parts : array();
	$names = hcwa_theme_names();
	if ( is_front_page() ) {
		return array( 'title' => $names['org'] . ' (HCWA)' );
	}
	$title = hcwa_theme_draft_text( (string) ( $parts['title'] ?? '' ) );
	if ( is_404() ) {
		$title['text'] = hcwa_theme_t( 'nf_title' );
	}
	return array_filter(
		array(
			'title' => $title['text'],
			'page'  => $parts['page'] ?? '',
			'site'  => $names['short'],
		),
		'strlen'
	);
}

/**
 * The prototype's title separator.
 *
 * @return string
 */
function hcwa_theme_title_separator(): string {
	return '|';
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
	if ( hcwa_theme_queried_attachment_id() > 0 ) {
		$robots['noindex'] = true;
	}
	return $robots;
}

// On 'wp' (class-wp.php:839), like hcwa-core's hcwa_refuse_attachment_page(): before every
// template_redirect callback, so neither redirect_canonical (priority 10) nor an SEO
// plugin's attachment redirect (Yoast: priority 1) can answer 301 to the raw file first.
// set_404() clears is_attachment (class-wp-query.php:1846, :525), so they then do nothing.
add_action( 'wp', 'hcwa_theme_refuse_attachment_page', 5, 0 );

/**
 * Attachment page of a photo the approval gate refuses in its context (or of any photo
 * without hcwa-core): a plain 404, so neither the photo nor its title or caption, which
 * often carry a child's name, is printed. Same steps as core's WP::handle_404(). With
 * hcwa-core active the plugin's copy has already run (same hook and priority, loaded
 * first), so this one finds no attachment left and does nothing. The attachment is read
 * from the queried object, not from is_attachment(): ?post_type=attachment&p=N shows it
 * as a single post (is_single), and single.php would print it.
 */
function hcwa_theme_refuse_attachment_page(): void {
	if ( ! hcwa_theme_attachment_refused( hcwa_theme_queried_attachment_id() ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

/**
 * ID of the attachment the main query shows (an attachment page or a post_type=attachment
 * query shown as a single post), else 0. Same rule as hcwa-core's hcwa_queried_attachment_id().
 */
function hcwa_theme_queried_attachment_id(): int {
	$object = get_queried_object();
	if ( $object instanceof WP_Post && 'attachment' === $object->post_type ) {
		return (int) $object->ID;
	}
	return is_attachment() ? (int) get_queried_object_id() : 0;
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
 * News archives show 12 posts per page (06-PAGES); the reports archive can filter by type;
 * search never lists attachments (hcwa_theme_search_post_types()).
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
	if ( $query->is_search() ) {
		$query->set( 'post_type', hcwa_theme_search_post_types( $query->get( 'post_type' ), get_post_types( array( 'exclude_from_search' => false ) ) ) );
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
 * Post types the site search may list: those asked for (?post_type=) or every searchable
 * type, never attachments. WordPress searches attachments by default (registered public,
 * so not excluded from search: wp-includes/post.php:92-104, class-wp-post-type.php:606;
 * those with a published parent: class-wp-query.php:2330-2332 and 2707-2710), and their
 * title and caption are often the file name or text read from the file
 * (hcwa_theme_attachment_text()). Pure helper (tested).
 *
 * @param mixed    $asked      The query's post_type: '', 'any', a name or a list.
 * @param string[] $searchable Post types not excluded from search.
 * @return string[]
 */
function hcwa_theme_search_post_types( $asked, array $searchable ): array {
	$searchable = array_values( array_diff( array_map( 'strval', $searchable ), array( 'attachment' ) ) );
	$asked      = array_values( array_intersect( array_map( 'strval', (array) $asked ), $searchable ) );
	if ( $asked ) {
		return $asked;
	}
	// Never an empty list: WordPress reads it as "any", attachments included.
	return $searchable ? $searchable : array( 'post' );
}

add_filter( 'document_title_parts', 'hcwa_theme_attachment_document_title' );

/**
 * Browser title of an attachment page: the page's own heading, never the file name or text
 * read from the file, which wp_get_document_title() would use (general-template.php:1462,
 * filtered at :1520). An SEO plugin builds its own title instead.
 *
 * @param mixed $parts Title parts.
 * @return mixed
 */
function hcwa_theme_attachment_document_title( $parts ) {
	$attachment_id = is_array( $parts ) ? hcwa_theme_queried_attachment_id() : 0;
	if ( is_array( $parts ) && ( $attachment_id > 0 || is_attachment() ) ) {
		$parts['title'] = hcwa_theme_attachment_heading( $attachment_id );
	}
	return $parts;
}

add_filter( 'single_template_hierarchy', 'hcwa_theme_single_attachment_template' );

/**
 * An attachment shown as a single post (?post_type=attachment&p=N, is_single rather than
 * is_attachment) uses attachment.php like an attachment page, so its heading is never the
 * file name and the gate applies; single.php would print the raw title. Refused photos
 * never get here (hcwa_theme_refuse_attachment_page() makes them a 404 first).
 *
 * @param mixed $templates Candidate template file names, most specific first.
 * @return mixed
 */
function hcwa_theme_single_attachment_template( $templates ) {
	if ( is_array( $templates ) && hcwa_theme_queried_attachment_id() > 0 ) {
		array_unshift( $templates, 'attachment.php' );
	}
	return $templates;
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
