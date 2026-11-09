<?php
/**
 * Content types (04-SITEMAP): centers, programs, reports (public singles), impact
 * figures and partners (admin only), and the shared "focus area" taxonomy.
 *
 * Public types use 'rewrite' => false: their per-language URLs come from
 * localized-urls.php (see wp/CONTRACT.md for why).
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types the core plugin registers.
 *
 * @return string[]
 */
function hcwa_post_types(): array {
	return array( 'hcwa_center', 'hcwa_program', 'hcwa_report', 'hcwa_impact', 'hcwa_partner' );
}

/**
 * The four post sections (contract "Names": seeded categories) and their slugs, English
 * first, Vietnamese second. The theme's category chips and card labels, the blocks' card
 * labels and `wp hcwa coverage` all read this one map. Legacy story categories are listed
 * by hcwa_story_category_slugs(), not here, so a chip never links an old category.
 *
 * @return array<string, string[]> news | stories | events | announcements => [en, vi].
 */
function hcwa_category_slugs(): array {
	return array(
		'news'          => array( 'news', 'tin-tuc' ),
		'stories'       => array( 'stories', 'cau-chuyen' ),
		'events'        => array( 'events', 'su-kien' ),
		'announcements' => array( 'announcements', 'thong-bao' ),
	);
}

/**
 * Section key of a category from the slugs of the category and its translations (any
 * language, a "-vi" / "-en" suffix ignored): one of hcwa_category_slugs(), 'stories' for a
 * legacy story category (hcwa_story_category_slugs()), or '' for any other category.
 *
 * @param string[] $slugs Slugs of one category and its translations.
 */
function hcwa_category_key( array $slugs ): string {
	$slugs = array_map( static fn( $slug ): string => (string) preg_replace( '/-(vi|en)$/', '', (string) $slug ), $slugs );
	foreach ( hcwa_category_slugs() as $key => $known ) {
		if ( array_intersect( $slugs, $known ) ) {
			return $key;
		}
	}
	return array_intersect( $slugs, hcwa_story_category_slugs() ) ? 'stories' : '';
}

/**
 * Registers the five post types and the focus area taxonomy.
 */
function hcwa_register_post_types(): void {
	$public_single = array(
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_rest'       => true,
		'has_archive'        => false,
		'rewrite'            => false,
		'map_meta_cap'       => true,
		'capability_type'    => 'post',
	);

	register_post_type(
		'hcwa_center',
		array_merge(
			$public_single,
			array(
				'labels'        => array(
					'name'                  => __( 'Centers', 'hcwa' ),
					'singular_name'         => __( 'Center', 'hcwa' ),
					'menu_name'             => __( 'Centers', 'hcwa' ),
					'all_items'             => __( 'All centers', 'hcwa' ),
					'add_new'               => __( 'Add center', 'hcwa' ),
					'add_new_item'          => __( 'Add new center', 'hcwa' ),
					'edit_item'             => __( 'Edit center', 'hcwa' ),
					'new_item'              => __( 'New center', 'hcwa' ),
					'view_item'             => __( 'View center', 'hcwa' ),
					'view_items'            => __( 'View centers', 'hcwa' ),
					'search_items'          => __( 'Search centers', 'hcwa' ),
					'not_found'             => __( 'No centers found.', 'hcwa' ),
					'not_found_in_trash'    => __( 'No centers found in Trash.', 'hcwa' ),
					'featured_image'        => __( 'Cover photo', 'hcwa' ),
					'set_featured_image'    => __( 'Set cover photo', 'hcwa' ),
					'remove_featured_image' => __( 'Remove cover photo', 'hcwa' ),
					'use_featured_image'    => __( 'Use as cover photo', 'hcwa' ),
					'item_published'        => __( 'Center published.', 'hcwa' ),
					'item_updated'          => __( 'Center updated.', 'hcwa' ),
				),
				'description'   => __( 'Permanent units that HCWA runs, such as Green Bamboo or Little Rose.', 'hcwa' ),
				'menu_icon'     => 'dashicons-building',
				'menu_position' => 20,
				'query_var'     => 'hcwa_center',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
			)
		)
	);

	register_post_type(
		'hcwa_program',
		array_merge(
			$public_single,
			array(
				'labels'        => array(
					'name'                  => __( 'Programs', 'hcwa' ),
					'singular_name'         => __( 'Program', 'hcwa' ),
					'menu_name'             => __( 'Programs', 'hcwa' ),
					'all_items'             => __( 'All programs', 'hcwa' ),
					'add_new'               => __( 'Add program', 'hcwa' ),
					'add_new_item'          => __( 'Add new program', 'hcwa' ),
					'edit_item'             => __( 'Edit program', 'hcwa' ),
					'new_item'              => __( 'New program', 'hcwa' ),
					'view_item'             => __( 'View program', 'hcwa' ),
					'view_items'            => __( 'View programs', 'hcwa' ),
					'search_items'          => __( 'Search programs', 'hcwa' ),
					'not_found'             => __( 'No programs found.', 'hcwa' ),
					'not_found_in_trash'    => __( 'No programs found in Trash.', 'hcwa' ),
					'featured_image'        => __( 'Cover photo', 'hcwa' ),
					'set_featured_image'    => __( 'Set cover photo', 'hcwa' ),
					'remove_featured_image' => __( 'Remove cover photo', 'hcwa' ),
					'use_featured_image'    => __( 'Use as cover photo', 'hcwa' ),
					'item_published'        => __( 'Program published.', 'hcwa' ),
					'item_updated'          => __( 'Program updated.', 'hcwa' ),
				),
				'description'   => __( 'Time-bound or funded projects and programs.', 'hcwa' ),
				'menu_icon'     => 'dashicons-portfolio',
				'menu_position' => 21,
				'query_var'     => 'hcwa_program',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
			)
		)
	);

	register_post_type(
		'hcwa_report',
		array_merge(
			$public_single,
			array(
				'labels'        => array(
					'name'                  => __( 'Reports', 'hcwa' ),
					'singular_name'         => __( 'Report', 'hcwa' ),
					'menu_name'             => __( 'Reports', 'hcwa' ),
					'all_items'             => __( 'All reports', 'hcwa' ),
					'add_new'               => __( 'Add report', 'hcwa' ),
					'add_new_item'          => __( 'Add new report', 'hcwa' ),
					'edit_item'             => __( 'Edit report', 'hcwa' ),
					'new_item'              => __( 'New report', 'hcwa' ),
					'view_item'             => __( 'View report', 'hcwa' ),
					'view_items'            => __( 'View reports', 'hcwa' ),
					'search_items'          => __( 'Search reports', 'hcwa' ),
					'not_found'             => __( 'No reports found.', 'hcwa' ),
					'not_found_in_trash'    => __( 'No reports found in Trash.', 'hcwa' ),
					'featured_image'        => __( 'Cover thumbnail', 'hcwa' ),
					'set_featured_image'    => __( 'Set cover thumbnail', 'hcwa' ),
					'remove_featured_image' => __( 'Remove cover thumbnail', 'hcwa' ),
					'use_featured_image'    => __( 'Use as cover thumbnail', 'hcwa' ),
					'item_published'        => __( 'Report published.', 'hcwa' ),
					'item_updated'          => __( 'Report updated.', 'hcwa' ),
				),
				'description'   => __( 'Annual reports, financial statements, program reports and policies (PDF).', 'hcwa' ),
				'menu_icon'     => 'dashicons-media-document',
				'menu_position' => 22,
				'query_var'     => 'hcwa_report',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
			)
		)
	);

	$admin_only = array(
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_rest'        => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'map_meta_cap'        => true,
		'capability_type'     => 'post',
	);

	register_post_type(
		'hcwa_impact',
		array_merge(
			$admin_only,
			array(
				'labels'        => array(
					'name'               => __( 'Impact figures', 'hcwa' ),
					'singular_name'      => __( 'Impact figure', 'hcwa' ),
					'menu_name'          => __( 'Impact figures', 'hcwa' ),
					'all_items'          => __( 'All impact figures', 'hcwa' ),
					'add_new'            => __( 'Add figure', 'hcwa' ),
					'add_new_item'       => __( 'Add new impact figure', 'hcwa' ),
					'edit_item'          => __( 'Edit impact figure', 'hcwa' ),
					'new_item'           => __( 'New impact figure', 'hcwa' ),
					'view_item'          => __( 'View impact figure', 'hcwa' ),
					'search_items'       => __( 'Search impact figures', 'hcwa' ),
					'not_found'          => __( 'No impact figures found.', 'hcwa' ),
					'not_found_in_trash' => __( 'No impact figures found in Trash.', 'hcwa' ),
					'item_published'     => __( 'Impact figure published.', 'hcwa' ),
					'item_updated'       => __( 'Impact figure updated.', 'hcwa' ),
				),
				'description'   => __( 'One number per entry. The title is the label shown under the number.', 'hcwa' ),
				'menu_icon'     => 'dashicons-chart-bar',
				'menu_position' => 23,
				'supports'      => array( 'title', 'custom-fields' ),
			)
		)
	);

	register_post_type(
		'hcwa_partner',
		array_merge(
			$admin_only,
			array(
				'labels'        => array(
					'name'                  => __( 'Partners', 'hcwa' ),
					'singular_name'         => __( 'Partner', 'hcwa' ),
					'menu_name'             => __( 'Partners', 'hcwa' ),
					'all_items'             => __( 'All partners', 'hcwa' ),
					'add_new'               => __( 'Add partner', 'hcwa' ),
					'add_new_item'          => __( 'Add new partner', 'hcwa' ),
					'edit_item'             => __( 'Edit partner', 'hcwa' ),
					'new_item'              => __( 'New partner', 'hcwa' ),
					'view_item'             => __( 'View partner', 'hcwa' ),
					'search_items'          => __( 'Search partners', 'hcwa' ),
					'not_found'             => __( 'No partners found.', 'hcwa' ),
					'not_found_in_trash'    => __( 'No partners found in Trash.', 'hcwa' ),
					'featured_image'        => __( 'Logo', 'hcwa' ),
					'set_featured_image'    => __( 'Set logo', 'hcwa' ),
					'remove_featured_image' => __( 'Remove logo', 'hcwa' ),
					'use_featured_image'    => __( 'Use as logo', 'hcwa' ),
					'item_published'        => __( 'Partner published.', 'hcwa' ),
					'item_updated'          => __( 'Partner updated.', 'hcwa' ),
				),
				'description'   => __( 'Partners and sponsors. The featured image is the logo.', 'hcwa' ),
				'menu_icon'     => 'dashicons-groups',
				'menu_position' => 24,
				'supports'      => array( 'title', 'thumbnail', 'custom-fields', 'page-attributes' ),
			)
		)
	);

	register_taxonomy(
		'hcwa_focus_area',
		array( 'hcwa_center', 'hcwa_program', 'post' ),
		array(
			'labels'             => array(
				'name'          => __( 'Focus areas', 'hcwa' ),
				'singular_name' => __( 'Focus area', 'hcwa' ),
				'menu_name'     => __( 'Focus areas', 'hcwa' ),
				'all_items'     => __( 'All focus areas', 'hcwa' ),
				'edit_item'     => __( 'Edit focus area', 'hcwa' ),
				'view_item'     => __( 'View focus area', 'hcwa' ),
				'update_item'   => __( 'Update focus area', 'hcwa' ),
				'add_new_item'  => __( 'Add new focus area', 'hcwa' ),
				'new_item_name' => __( 'New focus area name', 'hcwa' ),
				'search_items'  => __( 'Search focus areas', 'hcwa' ),
				'not_found'     => __( 'No focus areas found.', 'hcwa' ),
				'back_to_items' => __( 'Back to focus areas', 'hcwa' ),
			),
			'description'        => __( 'Education, child protection, family and community support, policy and advocacy.', 'hcwa' ),
			'public'             => true,
			'publicly_queryable' => false,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'query_var'          => false,
			'rewrite'            => false,
			// The areas keep the order they are given in: a program's tile strip and data-areas
			// follow it, as in the prototype (work.json lists each center's and program's areas).
			'sort'               => true,
			'args'               => array( 'orderby' => 'term_order' ),
		)
	);
}
add_action( 'init', 'hcwa_register_post_types', 5 );

/**
 * Impact figures and partners are a title plus fields, so they keep the classic screen.
 *
 * @param bool   $use_block_editor Whether the post type uses the block editor.
 * @param string $post_type        Post type.
 */
function hcwa_use_block_editor( $use_block_editor, $post_type ) {
	return in_array( $post_type, array( 'hcwa_impact', 'hcwa_partner' ), true ) ? false : $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', 'hcwa_use_block_editor', 10, 2 );

/**
 * "Label" instead of "Add title" on impact figures, "Name" on partners.
 *
 * @param string  $text Placeholder.
 * @param WP_Post $post Post being edited.
 */
function hcwa_title_placeholder( $text, $post ) {
	if ( 'hcwa_impact' === $post->post_type ) {
		return __( 'Label shown under the number, e.g. "children supported in 2025"', 'hcwa' );
	}
	if ( 'hcwa_partner' === $post->post_type ) {
		return __( 'Partner name', 'hcwa' );
	}
	return $text;
}
add_filter( 'enter_title_here', 'hcwa_title_placeholder', 10, 2 );

/**
 * REST routes limited to people who can edit posts (the site renders on the server and
 * never needs them in public):
 * - impact figures and partners are admin-only, but show_in_rest (needed for the editor)
 *   would let anyone read published ones, including unverified figures;
 * - media (/wp/v2/media, also reached by ?_embed through wp:featuredmedia) would list every
 *   upload with its file URLs, captions and parent, approved for web or not, including
 *   photos of identifiable children and a VietQR image finance has not confirmed;
 * - Yoast SEO's /yoast/v1/get_head?url= returns any page's head (share image, schema with
 *   image captions) without rendering it; seo.php also takes yoast_head and yoast_head_json
 *   out of public post and term responses;
 * - centers, programs, reports and focus areas: the theme prints their text only through
 *   hcwa_draft_text() (center bodies through hcwa_center_body()), but content.rendered and
 *   a term's description print it raw, so draft text would reach the live site through
 *   REST (found on a production instance, fix round 1). The editor reads them as staff.
 *
 * Checked on the matched request (rest_request_before_callbacks, which also runs for
 * embedded and batch requests), case-insensitively, because WordPress matches routes
 * case-insensitively (wp-includes/rest-api/class-wp-rest-server.php
 * match_request_to_handler()).
 *
 * @param mixed           $response Response so far (a WP_Error from validation is kept).
 * @param array           $handler  Matched route handler.
 * @param WP_REST_Request $request  Request.
 * @return mixed
 */
function hcwa_rest_protect_routes( $response, $handler, $request ) {
	if ( is_wp_error( $response ) || ! $request instanceof WP_REST_Request ) {
		return $response;
	}
	if ( hcwa_rest_route_is_protected( (string) $request->get_route() ) && ! current_user_can( 'edit_posts' ) ) {
		return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to do that.', 'hcwa' ), array( 'status' => rest_authorization_required_code() ) );
	}
	return $response;
}
add_filter( 'rest_request_before_callbacks', 'hcwa_rest_protect_routes', 10, 3 );

/**
 * Whether a REST route is one of hcwa_rest_protect_routes()'s (any letter case).
 *
 * @param string $route Route, e.g. /wp/v2/media/12.
 */
function hcwa_rest_route_is_protected( string $route ): bool {
	return (bool) preg_match( '#^/+(?:wp/v2/(?:hcwa_impact|hcwa_partner|hcwa_center|hcwa_program|hcwa_report|hcwa_focus_area|media)|yoast/v1/get_head)(?:/|$)#i', $route );
}

/**
 * Feeds that would print our post types: centers, programs, reports, impact figures and
 * partners (?feed=rss2&post_type=…, post_type=any), focus area archives and search results
 * (which list centers, programs and reports). Their bodies and term descriptions print
 * through hcwa_draft_text() in the theme, but a feed prints them raw through
 * the_content_feed. Nobody needs these feeds: the news feed (posts) stays.
 */
function hcwa_feed_shows_our_types(): bool {
	if ( ! is_feed() ) {
		return false;
	}
	$ours  = array( 'hcwa_center', 'hcwa_program', 'hcwa_report', 'hcwa_impact', 'hcwa_partner' );
	$asked = array_map( 'strval', (array) get_query_var( 'post_type' ) );
	if ( array_intersect( $ours, $asked ) || in_array( 'any', $asked, true ) ) {
		return true;
	}
	if ( is_search() || is_tax( 'hcwa_focus_area' ) ) {
		return true;
	}
	$object = get_queried_object();
	return $object instanceof WP_Post && in_array( $object->post_type, $ours, true );
}

/**
 * Those feeds answer a plain 404 and print the 404 template. On 'wp', before
 * template-loader.php serves the feed. WP_Query::set_404() keeps is_feed
 * (class-wp-query.php:1843-1849) and the found posts, so a bare set_404() only changed the
 * status and the feed still printed every item (found on real WordPress, fix round 2);
 * hcwa_end_as_404() clears the feed flags and the posts too.
 */
function hcwa_refuse_our_feeds(): void {
	if ( ! hcwa_feed_shows_our_types() ) {
		return;
	}
	hcwa_end_as_404();
}
add_action( 'wp', 'hcwa_refuse_our_feeds', 5, 0 );

/**
 * On the live site, feed text that still holds a draft marker prints nothing: titles,
 * bodies and excerpts of the news feed (raw draft posts are unpublished before launch,
 * 11-WP-PORT.md 5.9; this is the second lock). Staging keeps the marker visible.
 *
 * @param mixed $text Feed text.
 * @return mixed
 */
function hcwa_feed_drop_draft( $text ) {
	if ( ! is_string( $text ) || '' === $text || ! hcwa_is_production() ) {
		return $text;
	}
	return hcwa_contains_draft( $text ) ? '' : $text;
}
foreach ( array( 'the_title_rss', 'the_content_feed', 'the_excerpt_rss' ) as $hcwa_feed_hook ) {
	add_filter( $hcwa_feed_hook, 'hcwa_feed_drop_draft', 99 );
}
unset( $hcwa_feed_hook );

/**
 * The tagline (blogdescription, translated by Polylang) as feeds print it: on the live site
 * nothing while it holds a draft marker. The tagline skips the publish guard (it is an
 * option), and the seeded one is draft.
 *
 * @param mixed  $info Value.
 * @param string $show Which bloginfo field.
 * @return mixed
 */
function hcwa_feed_tagline( $info, $show = '' ) {
	return 'description' === $show ? hcwa_feed_drop_draft( $info ) : $info;
}
add_filter( 'get_bloginfo_rss', 'hcwa_feed_tagline', 99, 2 );

/**
 * The REST index (/wp-json/) prints the site name and tagline to anyone: on the live site
 * the tagline is left empty while it holds a draft marker.
 *
 * @param mixed $response WP_REST_Response of the index.
 * @return mixed
 */
function hcwa_rest_index_tagline( $response ) {
	if ( ! $response instanceof WP_REST_Response ) {
		return $response;
	}
	$data = $response->get_data();
	if ( is_array( $data ) && isset( $data['description'] ) && is_string( $data['description'] ) ) {
		$data['description'] = (string) hcwa_feed_drop_draft( $data['description'] );
		$response->set_data( $data );
	}
	return $response;
}
add_filter( 'rest_index', 'hcwa_rest_index_tagline', 99 );
