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
foreach ( array( '/wp/v2/posts', '/wp/v2/hcwa_center', '/wp/v2/media-other', '/oembed/1.0/proxy', '/yoast/v1/get_headings' ) as $route ) {
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
