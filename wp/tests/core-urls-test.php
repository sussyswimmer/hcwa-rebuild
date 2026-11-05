<?php
/**
 * Localized URL paths, rewrite rules, logical page URLs.
 *
 * @package hcwa-core
 */

// Paths per object and language (wp/CONTRACT.md "URLs").
hcwa_assert_same( 'hoat-dong/co-so/tre-xanh/', hcwa_localized_path( 'hcwa_center', 'vi', 'tre-xanh' ), 'vi center' );
hcwa_assert_same( 'en/our-work/centers/green-bamboo/', hcwa_localized_path( 'hcwa_center', 'en', 'green-bamboo' ), 'en center' );
hcwa_assert_same( 'hoat-dong/du-an/cung-vuon-len/', hcwa_localized_path( 'hcwa_program', 'vi', 'cung-vuon-len' ), 'vi program' );
hcwa_assert_same( 'en/our-work/programs/thrive-together/', hcwa_localized_path( 'hcwa_program', 'en', 'thrive-together' ), 'en program' );
hcwa_assert_same( 'tac-dong/bao-cao/bao-cao-2025/', hcwa_localized_path( 'hcwa_report', 'vi', 'bao-cao-2025' ), 'vi report' );
hcwa_assert_same( 'en/impact/reports/annual-2025/', hcwa_localized_path( 'hcwa_report', 'en', 'annual-2025' ), 'en report' );
hcwa_assert_same( 'tin-tuc/cau-chuyen/', hcwa_localized_path( 'category', 'vi', 'cau-chuyen' ), 'vi category' );
hcwa_assert_same( 'en/news/stories/', hcwa_localized_path( 'category', 'en', 'stories' ), 'en category' );
hcwa_assert_same( 'mot-bai-viet/', hcwa_localized_path( 'post', 'vi', 'mot-bai-viet' ), 'vi post keeps its slug' );
hcwa_assert_same( 'en/a-post/', hcwa_localized_path( 'post', 'en', 'a-post' ), 'en post gets /en/' );
hcwa_assert_same( 'en/our-work/centers/%hcwa_center%/', hcwa_localized_path( 'hcwa_center', 'en', '%hcwa_center%' ), 'editor placeholder' );
hcwa_assert_same( 'hoat-dong/co-so/', hcwa_localized_path( 'hcwa_center', 'vi', '' ), 'empty slug gives the base' );
hcwa_assert_same( '', hcwa_localized_path( 'unknown', 'vi', 'x' ), 'unknown object' );

// Rewrite rules, tried in order the way WP::parse_request() does.
$match = static function ( string $path ) {
	foreach ( hcwa_localized_rewrite_rules() as $regex => $query ) {
		if ( preg_match( "#^$regex#", $path, $m ) ) {
			return preg_replace_callback( '/\$matches\[(\d+)\]/', static fn( $x ) => $m[ (int) $x[1] ] ?? '', $query );
		}
	}
	return null;
};

hcwa_assert_same( 'index.php?hcwa_center=tre-xanh&lang=vi', $match( 'hoat-dong/co-so/tre-xanh' ), 'vi center rule' );
hcwa_assert_same( 'index.php?hcwa_center=green-bamboo&lang=en', $match( 'en/our-work/centers/green-bamboo/' ), 'en center rule' );
hcwa_assert_same( 'index.php?hcwa_program=thrive-together&lang=en', $match( 'en/our-work/programs/thrive-together' ), 'en program rule' );
hcwa_assert_same( 'index.php?hcwa_report=annual-2025&lang=en', $match( 'en/impact/reports/annual-2025' ), 'en report rule' );
hcwa_assert_same( null, $match( 'hoat-dong/co-so/tre-xanh/embed' ), 'no embed rule (embeds are switched off)' );
hcwa_assert_same( null, $match( 'en/our-work/centers/green-bamboo/feed' ), 'feed is not taken for an attachment slug' );
hcwa_assert_same( 'index.php?attachment=anh-1&lang=vi', $match( 'hoat-dong/co-so/tre-xanh/anh-1/' ), 'attachment page under a center (parent permalink + slug)' );
hcwa_assert_same( 'index.php?attachment=photo&lang=en', $match( 'en/our-work/programs/thrive-together/attachment/photo' ), 'attachment page, /attachment/ form' );
hcwa_assert_same( 'index.php?hcwa_report=bao-cao-2025&page=2&lang=vi', $match( 'tac-dong/bao-cao/bao-cao-2025/2/' ), 'paged report' );
hcwa_assert_same( 'index.php?category_name=stories&lang=en', $match( 'en/news/stories' ), 'en category rule' );
hcwa_assert_same( 'index.php?category_name=stories&paged=2&lang=en', $match( 'en/news/stories/page/2' ), 'en category pagination' );
hcwa_assert_same( 'index.php?category_name=cau-chuyen&paged=3&lang=vi', $match( 'tin-tuc/cau-chuyen/page/3/' ), 'vi category pagination' );
hcwa_assert_same( 'index.php?category_name=cau-chuyen&feed=feed&lang=vi', $match( 'tin-tuc/cau-chuyen/feed' ), 'vi category feed' );
hcwa_assert_same( null, $match( 'en/news/page/2' ), '/en/news/page/2/ is left to the posts page' );
hcwa_assert_same( null, $match( 'tin-tuc/page/2' ), '/tin-tuc/page/2/ is left to the posts page' );
hcwa_assert_same( null, $match( 'en/news/page' ), 'a category named "page" is never matched' );
hcwa_assert_same( null, $match( 'en/news/feed' ), 'the posts page feed is left alone' );
hcwa_assert_same( null, $match( 'hoat-dong' ), 'the Our Work page is left to WordPress' );
hcwa_assert_same( null, $match( 'en/our-work' ), 'the EN Our Work page is left to WordPress' );
hcwa_assert_same( null, $match( 'mot-bai-viet' ), 'posts are left to WordPress' );

foreach ( hcwa_localized_rewrite_rules() as $query ) {
	if ( false !== strpos( $query, 'post_type=' ) ) {
		hcwa_test_record( false, "rule query uses post_type=, which Polylang rewrites: $query" );
	}
}
hcwa_assert_true( true, 'no rule uses post_type= (Polylang leaves them alone)' );

// Link filters.
$GLOBALS['hcwa_test']['options']['home'] = 'https://hcwa.test/';
$center = hcwa_test_post(
	array(
		'ID'          => 30,
		'post_type'   => 'hcwa_center',
		'post_name'   => 'tre-xanh',
		'post_status' => 'publish',
	)
);
hcwa_assert_same( 'https://hcwa.test/hoat-dong/co-so/tre-xanh/', hcwa_filter_post_type_link( 'https://hcwa.test/?hcwa_center=tre-xanh', $center ), 'published center link (no double slash)' );
hcwa_assert_same( 'https://hcwa.test/hoat-dong/co-so/%hcwa_center%/', hcwa_filter_post_type_link( 'x', $center, true, true ), 'sample permalink keeps the placeholder' );

$draft = hcwa_test_post(
	array(
		'ID'          => 31,
		'post_type'   => 'hcwa_center',
		'post_name'   => 'nhap',
		'post_status' => 'draft',
	)
);
hcwa_assert_same( 'https://hcwa.test/?post_type=hcwa_center&p=31', hcwa_filter_post_type_link( 'https://hcwa.test/?post_type=hcwa_center&p=31', $draft ), 'draft keeps the plain link' );

$page = hcwa_test_post( array( 'ID' => 32, 'post_type' => 'page', 'post_name' => 'x' ) );
hcwa_assert_same( 'unchanged', hcwa_filter_post_type_link( 'unchanged', $page ), 'other types untouched' );

$term = new WP_Term( array( 'term_id' => 5, 'slug' => 'cau-chuyen', 'taxonomy' => 'category' ) );
hcwa_assert_same( 'https://hcwa.test/tin-tuc/cau-chuyen/', hcwa_filter_term_link( 'https://hcwa.test/category/cau-chuyen/', $term, 'category' ), 'category link' );
hcwa_assert_same( 'tag-link', hcwa_filter_term_link( 'tag-link', $term, 'post_tag' ), 'tags untouched' );

$GLOBALS['hcwa_test']['options']['permalink_structure'] = '';
hcwa_assert_same( 'plain', hcwa_filter_post_type_link( 'plain', $center ), 'plain permalinks untouched' );
$GLOBALS['hcwa_test']['options']['permalink_structure'] = '/%postname%/';

// Logical pages: default paths mirror prototype/src/routes.mjs.
hcwa_assert_same( '', hcwa_page_path( 'home', 'vi' ), 'vi home' );
hcwa_assert_same( 'en/', hcwa_page_path( 'home', 'en' ), 'en home' );
hcwa_assert_same( 'chung-tay/ung-ho/', hcwa_page_path( 'donate', 'vi' ), 'vi donate' );
hcwa_assert_same( 'en/get-involved/donate/', hcwa_page_path( 'donate', 'en' ), 'en donate' );
hcwa_assert_same( 'tac-dong/#bao-cao', hcwa_page_path( 'reports', 'vi' ), 'anchor key vi' );
hcwa_assert_same( 'en/about/policies/#privacy', hcwa_page_path( 'privacy', 'en' ), 'anchor key en' );
hcwa_assert_same( null, hcwa_page_path( 'nope', 'vi' ), 'unknown key' );

foreach ( hcwa_page_keys() as $key ) {
	if ( null === hcwa_page_path( $key, 'vi' ) || null === hcwa_page_path( $key, 'en' ) ) {
		hcwa_test_record( false, "page key $key has no default path" );
	}
}
hcwa_assert_same( 15, count( hcwa_page_keys() ), 'fifteen mappable page keys (contract)' );

hcwa_assert_same( 'https://hcwa.test/lien-he/', hcwa_page_url( 'contact', 'vi' ), 'page URL from the path table' );
hcwa_assert_same( 'https://hcwa.test/en/contact/', hcwa_page_url( 'contact', 'en' ), 'en page URL' );
hcwa_assert_same( 'https://hcwa.test/en/impact/#reports', hcwa_page_url( 'reports', 'en' ), 'anchor page URL' );
hcwa_assert_same( 'https://hcwa.test/', hcwa_page_url( 'nope', 'vi' ), 'unknown key falls back to home' );

// Page map: a mapped VI page is used (Polylang inactive here, so the same page).
hcwa_test_post( array( 'ID' => 40, 'post_type' => 'page', 'post_name' => 'lien-he-moi', 'post_status' => 'publish' ) );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array( 'page_map' => array( 'contact' => 40 ) );
hcwa_assert_same( 'https://hcwa.test/lien-he-moi/', hcwa_page_url( 'contact', 'vi' ), 'page map wins over the path table' );

// Canonical redirect for localized objects (hcwa_pll_check_canonical_url): the permalink's
// path on the requested host, the visitor's own query kept, object query vars dropped.
hcwa_assert_same(
	'https://hcwa.test/hoat-dong/co-so/cau-han/?utm_source=x',
	hcwa_canonical_with_request( 'https://hcwa.test/hoat-dong/co-so/cau-han/', 'https://hcwa.test/our-work/centers/cau-han/?utm_source=x&lang=vi' ),
	'wrong-language base: permalink path, query kept'
);
hcwa_assert_same( 'http://hcwa.test:8080/en/news/news/', hcwa_canonical_with_request( 'https://hcwa.test/en/news/news/', 'http://hcwa.test:8080/en/category/news/' ), 'scheme, host and port come from the request' );
hcwa_assert_same( 'https://hcwa.test/en/x/', hcwa_pll_check_canonical_url( 'https://hcwa.test/en/x/' ), 'other objects: Polylang decides' );
hcwa_assert_same( false, hcwa_pll_check_canonical_url( false ), 'no redirect stays no redirect' );
$GLOBALS['hcwa_test']['is']['is_feed'] = true;
hcwa_assert_same( 'https://hcwa.test/a/', hcwa_pll_check_canonical_url( 'https://hcwa.test/a/' ), 'feeds untouched' );
