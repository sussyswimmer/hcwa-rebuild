<?php
/**
 * REST routes closed to the public (post-types.php hcwa_rest_protect_routes()).
 *
 * @package hcwa-core
 */

$request = static fn( string $route ) => new WP_REST_Request( 'GET', $route );

foreach ( array( '/wp/v2/media', '/wp/v2/media/12', '/wp/v2/MEDIA', '/WP/V2/Media/12', '/wp/v2/hcwa_impact', '/wp/v2/HCWA_PARTNER/5', '/wp/v2/Hcwa_impact/3/revisions', '/yoast/v1/get_head', '/Yoast/V1/Get_Head' ) as $route ) {
	hcwa_assert_true( hcwa_rest_route_is_protected( $route ), "protected: $route" );
}
// Fix round 1: their text prints raw in content.rendered and term descriptions.
foreach ( array( '/wp/v2/hcwa_center', '/wp/v2/hcwa_center/34', '/wp/v2/HCWA_PROGRAM', '/wp/v2/hcwa_program/12/autosaves', '/wp/v2/hcwa_report', '/wp/v2/hcwa_focus_area', '/wp/v2/hcwa_focus_area/7' ) as $route ) {
	hcwa_assert_true( hcwa_rest_route_is_protected( $route ), "protected (draft text prints raw there): $route" );
}
foreach ( array( '/wp/v2/posts', '/wp/v2/pages', '/wp/v2/hcwa_centers', '/wp/v2/media-other', '/oembed/1.0/proxy', '/yoast/v1/get_headings' ) as $route ) {
	hcwa_assert_false( hcwa_rest_route_is_protected( $route ), "not protected: $route" );
}

// Anonymous: refused, whatever the letter case (WordPress matches routes case-insensitively).
$GLOBALS['hcwa_test']['can'] = false;
$denied = hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/Media' ) );
hcwa_assert_true( $denied instanceof WP_Error, 'anonymous /wp/v2/Media refused' );
hcwa_assert_true( hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/media' ) ) instanceof WP_Error, 'anonymous media list refused (?parent=N is a query parameter of this route)' );
hcwa_assert_true( hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/HCWA_IMPACT' ) ) instanceof WP_Error, 'anonymous impact figures refused in any case' );
hcwa_assert_same( null, hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/posts' ) ), 'public routes untouched' );
hcwa_assert_true( hcwa_rest_protect_routes( null, array(), $request( '/yoast/v1/get_head' ) ) instanceof WP_Error, 'anonymous /yoast/v1/get_head refused (any page\'s share image and schema)' );

// Staff: allowed. An earlier error is kept.
$GLOBALS['hcwa_test']['can'] = true;
hcwa_assert_same( null, hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/media' ) ), 'editors read media' );
$earlier = new WP_Error( 'rest_invalid_param', 'x' );
hcwa_assert_same( $earlier, hcwa_rest_protect_routes( $earlier, array(), $request( '/wp/v2/media' ) ), 'an earlier error is kept' );

$hooked = array_filter( $GLOBALS['hcwa_test']['hooks']['rest_request_before_callbacks'] ?? array(), static fn( $h ) => 'hcwa_rest_protect_routes' === $h[0] );
hcwa_assert_true( (bool) $hooked, 'checked on the matched request (rest_request_before_callbacks)' );
$GLOBALS['hcwa_test']['can'] = false;
hcwa_assert_true( hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/hcwa_center' ) ) instanceof WP_Error, 'anonymous centers refused (content.rendered prints draft bodies raw)' );
hcwa_assert_true( hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/hcwa_focus_area' ) ) instanceof WP_Error, 'anonymous focus areas refused (description prints raw)' );
$GLOBALS['hcwa_test']['can'] = true;
hcwa_assert_same( null, hcwa_rest_protect_routes( null, array(), $request( '/wp/v2/hcwa_program' ) ), 'editors read programs (the editor needs them)' );

// ---------- Feeds of our post types (fix round 1) ----------
$hcwa_saved_is  = $GLOBALS['hcwa_test']['is'] ?? array();
$hcwa_feed_case = static function ( array $is, array $vars, $queried = null ): bool {
	$GLOBALS['hcwa_test']['is']         = $is;
	$GLOBALS['hcwa_test']['query_vars'] = $vars;
	$GLOBALS['hcwa_test']['queried']    = $queried;
	return hcwa_feed_shows_our_types();
};
foreach ( array( 'hcwa_center', 'hcwa_program', 'hcwa_report', 'hcwa_impact', 'hcwa_partner', 'any' ) as $hcwa_type ) {
	hcwa_assert_true( $hcwa_feed_case( array( 'is_feed' => true ), array( 'post_type' => $hcwa_type ) ), "feed: ?feed=rss2&post_type=$hcwa_type is refused" );
	hcwa_assert_true( $hcwa_feed_case( array( 'is_feed' => true ), array( 'post_type' => array( 'post', $hcwa_type ) ) ), "feed: post_type[]=$hcwa_type is refused" );
}
hcwa_assert_true( $hcwa_feed_case( array( 'is_feed' => true, 'is_tax' => true ), array( 'hcwa_focus_area' => 'education' ) ), 'feed: a focus area feed is refused' );
hcwa_assert_true( $hcwa_feed_case( array( 'is_feed' => true, 'is_search' => true ), array( 's' => 'x' ) ), 'feed: a search feed is refused (it lists centers and programs)' );
hcwa_assert_true( $hcwa_feed_case( array( 'is_feed' => true ), array(), hcwa_test_post( array( 'ID' => 34, 'post_type' => 'hcwa_center' ) ) ), 'feed: the comments feed of a center is refused' );
hcwa_assert_false( $hcwa_feed_case( array( 'is_feed' => true ), array() ), 'feed: the news feed stays' );
hcwa_assert_false( $hcwa_feed_case( array( 'is_feed' => true ), array( 'category_name' => 'tin-tuc' ) ), 'feed: a category feed stays' );
hcwa_assert_false( $hcwa_feed_case( array(), array( 'post_type' => 'hcwa_center' ) ), 'feed: the center archive page itself is not a feed' );

$GLOBALS['wp_query'] = new class() {
	public $is_404 = false;

	public function set_404() {
		$this->is_404 = true;
	}
};
unset( $GLOBALS['hcwa_test']['status'] );
$hcwa_feed_case( array( 'is_feed' => true ), array( 'post_type' => 'hcwa_program' ) );
hcwa_refuse_our_feeds();
hcwa_assert_true( $GLOBALS['wp_query']->is_404, 'feed: a refused feed becomes a 404' );
hcwa_assert_same( 404, $GLOBALS['hcwa_test']['status'] ?? null, 'feed: status 404' );
$GLOBALS['wp_query'] = new class() {
	public $is_404 = false;

	public function set_404() {
		$this->is_404 = true;
	}
};
$hcwa_feed_case( array( 'is_feed' => true ), array() );
hcwa_refuse_our_feeds();
hcwa_assert_false( $GLOBALS['wp_query']->is_404, 'feed: the news feed is served' );
$hcwa_feed_hooked = array_filter( $GLOBALS['hcwa_test']['hooks']['wp'] ?? array(), static fn( $h ) => 'hcwa_refuse_our_feeds' === $h[0] );
hcwa_assert_true( (bool) $hcwa_feed_hooked, 'feed: refused on "wp", before template-loader serves it' );
$GLOBALS['hcwa_test']['is']         = $hcwa_saved_is;
$GLOBALS['hcwa_test']['query_vars'] = array();
unset( $GLOBALS['wp_query'], $GLOBALS['hcwa_test']['queried'], $GLOBALS['hcwa_test']['status'] );

// ---------- Draft text in feeds and the REST index on the live site ----------
$hcwa_saved_env              = $GLOBALS['hcwa_test']['env'];
$hcwa_tagline                = '[DRAFT: Tổ chức xã hội tự nguyện]';
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_feed_drop_draft( '<p>[DRAFT: An introduction]</p>' ), 'production: a draft feed body prints nothing' );
hcwa_assert_same( '<p>Ready text [1]</p>', hcwa_feed_drop_draft( '<p>Ready text [1]</p>' ), 'production: a footnote is not draft' );
hcwa_assert_same( '', hcwa_feed_tagline( $hcwa_tagline, 'description' ), 'production: a draft tagline is left out of the feed' );
hcwa_assert_same( $hcwa_tagline, hcwa_feed_tagline( $hcwa_tagline, 'name' ), 'production: only the tagline field is touched' );
$hcwa_index = hcwa_rest_index_tagline( new WP_REST_Response( array( 'name' => 'HCWA', 'description' => $hcwa_tagline ) ) );
hcwa_assert_same( array( 'name' => 'HCWA', 'description' => '' ), $hcwa_index->get_data(), 'production: the REST index tagline is empty while draft' );
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( $hcwa_tagline, hcwa_feed_tagline( $hcwa_tagline, 'description' ), 'staging: the marker stays visible' );
hcwa_assert_same( $hcwa_tagline, hcwa_rest_index_tagline( new WP_REST_Response( array( 'description' => $hcwa_tagline ) ) )->get_data()['description'], 'staging: REST index unchanged' );
$GLOBALS['hcwa_test']['env'] = $hcwa_saved_env;
foreach ( array( 'the_title_rss', 'the_content_feed', 'the_excerpt_rss' ) as $hcwa_hook ) {
	$hcwa_hooked = array_filter( $GLOBALS['hcwa_test']['hooks'][ $hcwa_hook ] ?? array(), static fn( $h ) => 'hcwa_feed_drop_draft' === $h[0] );
	hcwa_assert_true( (bool) $hcwa_hooked, "feed text guard on $hcwa_hook" );
}
hcwa_assert_true( (bool) array_filter( $GLOBALS['hcwa_test']['hooks']['get_bloginfo_rss'] ?? array(), static fn( $h ) => 'hcwa_feed_tagline' === $h[0] ), 'tagline guard on get_bloginfo_rss' );
hcwa_assert_true( (bool) array_filter( $GLOBALS['hcwa_test']['hooks']['rest_index'] ?? array(), static fn( $h ) => 'hcwa_rest_index_tagline' === $h[0] ), 'tagline guard on rest_index' );
