<?php
/**
 * Yoast SEO under the photo gate and the draft rule (seo.php). Yoast is not loaded: its
 * presentation and schema objects are stood in for by plain objects and arrays.
 *
 * @package hcwa-core
 */

hcwa_test_post( array( 'ID' => 100, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '1', 'hcwa_identifiable_child' => '1' ) );
hcwa_test_post( array( 'ID' => 101, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '' ) );
hcwa_test_post( array( 'ID' => 102, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '1' ) );
hcwa_test_post( array( 'ID' => 10, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 90, 'post_type' => 'post' ) );
$up = 'https://hcwa.test/wp-content/uploads/2026/11/';
$GLOBALS['hcwa_test']['upload_urls'] = array(
	$up . 'kid.webp'   => 101,
	$up . 'ok.webp'    => 102,
	$up . 'face.webp'  => 100,
);
$theme_logo = 'https://hcwa.test/wp-content/themes/hcwa-2026/assets/img/share-vi.png';

$model = static fn( string $type, int $id ) => (object) array(
	'object_type' => $type,
	'object_id'   => $id,
);
$presentation = static function ( $model, array $images, string $twitter ) {
	$p                    = new stdClass();
	$p->model             = $model;
	$p->open_graph_images = $images;
	$p->twitter_image     = $twitter;
	return $p;
};
$og = static fn( string $url ) => array( $url => array( 'url' => $url, 'width' => 1200, 'height' => 630 ) );

// og:image and twitter:image.
$out = hcwa_seo_presentation( $presentation( $model( 'post', 90 ), $og( $up . 'kid-1200x630.webp' ), $up . 'kid.webp' ) );
hcwa_assert_same( array(), $out->open_graph_images, 'news post: an unapproved featured photo is not shared (no approved fallback set)' );
hcwa_assert_same( '', $out->twitter_image, 'and not as twitter:image' );
$out = hcwa_seo_presentation( $presentation( $model( 'post', 90 ), $og( $up . 'ok.webp' ) + $og( $theme_logo ), $up . 'ok-1024x538.webp' ) );
hcwa_assert_same( array( $up . 'ok.webp', $theme_logo ), array_keys( $out->open_graph_images ), 'approved photo and the theme\'s share image kept' );
hcwa_assert_same( $up . 'ok-1024x538.webp', $out->twitter_image, 'approved twitter:image kept' );
$out = hcwa_seo_presentation( $presentation( $model( 'post', 10 ), $og( $up . 'face.webp' ), '' ) );
hcwa_assert_same( array(), $out->open_graph_images, 'Little Rose center: an approved identifiable child is not shared' );
$out = hcwa_seo_presentation( $presentation( $model( 'post', 90 ), $og( $up . 'face.webp' ), '' ) );
hcwa_assert_same( array( $up . 'face.webp' ), array_keys( $out->open_graph_images ), 'ordinary news: an approved photo of a child HCWA approved is shared' );
$out = hcwa_seo_presentation( $presentation( $model( 'term', 5 ), $og( $up . 'face.webp' ), '' ) );
hcwa_assert_same( array(), $out->open_graph_images, 'archives use the strict context' );
$out = hcwa_seo_presentation( $presentation( $model( 'post', 90 ), $og( 'https://cdn.example.org/kid.jpg' ) + $og( $up . 'unknown.jpg' ), '' ) );
hcwa_assert_same( array(), $out->open_graph_images, 'another site\'s image or an unknown upload is not shared' );
$out = hcwa_seo_presentation( $presentation( $model( 'post', 90 ), array( array( 'url' => $theme_logo, 'id' => 101 ) ), '' ) );
hcwa_assert_same( array(), $out->open_graph_images, 'an attachment ID Yoast gives is checked too' );

$broken = new #[AllowDynamicProperties] class() {
	public $model;

	public function __get( $name ) {
		throw new Exception( "Property $name has no generator." );
	}
};
$broken = hcwa_seo_presentation( $broken );
hcwa_assert_same( array( array(), '' ), array( $broken->open_graph_images, $broken->twitter_image ), 'fails closed when Yoast\'s fields cannot be read' );
hcwa_assert_same( 'x', hcwa_seo_presentation( 'x' ), 'not an object: left alone' );

hcwa_assert_same( '', hcwa_seo_filter_image_url( $up . 'kid.webp', $presentation( $model( 'post', 90 ), array(), '' ) ), 'wpseo_opengraph_image: refused URL emptied' );
hcwa_assert_same( $up . 'ok.webp', hcwa_seo_filter_image_url( $up . 'ok.webp' ), 'approved URL kept' );

// Schema: the ImageObject (with its caption) and every reference to it go; the logo stays.
$graph = array(
	array(
		'@type'              => 'WebPage',
		'@id'                => 'https://hcwa.test/tin/',
		'primaryImageOfPage' => array( '@id' => 'https://hcwa.test/tin/#primaryimage' ),
		'image'              => array( '@id' => 'https://hcwa.test/tin/#primaryimage' ),
		'thumbnailUrl'       => $up . 'kid.webp',
	),
	array(
		'@type'      => 'ImageObject',
		'@id'        => 'https://hcwa.test/tin/#primaryimage',
		'url'        => $up . 'kid.webp',
		'contentUrl' => $up . 'kid.webp',
		'caption'    => 'Lan, 9, at school',
	),
	array(
		'@type' => 'Organization',
		'logo'  => array( '@id' => 'https://hcwa.test/#/schema/logo/image/' ),
		'image' => array( '@id' => 'https://hcwa.test/#/schema/logo/image/' ),
	),
	array(
		'@type'      => 'ImageObject',
		'@id'        => 'https://hcwa.test/#/schema/logo/image/',
		'contentUrl' => $up . 'logo.png',
	),
	array(
		'@type'        => 'Article',
		'image'        => array( array( '@id' => 'https://hcwa.test/tin/#primaryimage' ), $up . 'ok.webp' ),
		'thumbnailUrl' => $up . 'ok.webp',
	),
);
$schema = hcwa_seo_schema_graph( $graph, (object) array( 'indexable' => $model( 'post', 90 ) ) );
hcwa_assert_same( 4, count( $schema ), 'the refused ImageObject node is removed' );
hcwa_assert_false( strpos( (string) wp_json_encode( $schema ), 'Lan, 9' ), 'its caption is gone' );
hcwa_assert_same( array( '@type' => 'WebPage', '@id' => 'https://hcwa.test/tin/' ), $schema[0], 'primaryImageOfPage, image and thumbnailUrl pointing at it removed' );
hcwa_assert_same( $graph[2], $schema[1], 'Organization logo kept' );
hcwa_assert_same( $graph[3], $schema[2], 'logo ImageObject kept' );
hcwa_assert_same( array( $up . 'ok.webp' ), $schema[3]['image'], 'a list keeps its approved entries' );
hcwa_assert_same( $up . 'ok.webp', $schema[3]['thumbnailUrl'], 'approved thumbnailUrl kept' );
hcwa_assert_same( 'x', hcwa_seo_schema_graph( 'x' ), 'not a graph: left alone' );

// Sitemaps: no image entries, no attachment sitemap.
hcwa_assert_true( has_filter( 'wpseo_xml_sitemap_include_images' ), 'image sitemaps switched off' );
hcwa_assert_true( hcwa_seo_sitemap_exclude_attachments( false, 'attachment' ), 'no attachment sitemap' );
hcwa_assert_false( hcwa_seo_sitemap_exclude_attachments( false, 'post' ), 'post sitemap kept' );

// REST: no Yoast head for visitors.
$response = new WP_REST_Response( array( 'id' => 90, 'link' => 'x', 'yoast_head' => '<meta>', 'yoast_head_json' => array( 'og_image' => array() ) ) );
$GLOBALS['hcwa_test']['can'] = false;
hcwa_assert_same( array( 'id' => 90, 'link' => 'x' ), hcwa_seo_rest_hide_head( $response )->get_data(), 'visitor: yoast_head and yoast_head_json removed' );
$GLOBALS['hcwa_test']['can'] = true;
$response = new WP_REST_Response( array( 'id' => 90, 'yoast_head' => '<meta>' ) );
hcwa_assert_same( array( 'id' => 90, 'yoast_head' => '<meta>' ), hcwa_seo_rest_hide_head( $response )->get_data(), 'editor: kept' );
foreach ( array( 'post', 'page', 'hcwa_center', 'hcwa_program', 'hcwa_report', 'category', 'post_tag' ) as $type ) {
	hcwa_assert_true( has_filter( "rest_prepare_$type" ), "rest_prepare_$type filtered" );
}
hcwa_assert_same( array( 'enable_headless_rest_endpoints' => false, 'x' => 1 ), hcwa_seo_no_headless_endpoints( array( 'enable_headless_rest_endpoints' => true, 'x' => 1 ) ), 'headless head endpoint stays off' );
hcwa_assert_same( array( 'x' => 1 ), hcwa_seo_no_headless_endpoints( array( 'x' => 1 ) ), 'no key invented on other Yoast versions' );

// Draft text in Yoast's fields: printed on staging, dropped on the live site.
hcwa_assert_same( '[DRAFT: Tiêu đề] - HCWA', hcwa_seo_drop_draft_text( '[DRAFT: Tiêu đề] - HCWA' ), 'staging prints draft SEO text' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_seo_drop_draft_text( '[DRAFT: Tiêu đề] - HCWA' ), 'production drops a draft title' );
hcwa_assert_same( 'Hội Bảo trợ Trẻ em', hcwa_seo_drop_draft_text( 'Hội Bảo trợ Trẻ em' ), 'clean text kept' );

// The publish guard checks Yoast's texts when Yoast is active.
$seo_post = hcwa_test_post( array( 'ID' => 95, 'post_type' => 'post', 'post_title' => 'Tết', 'post_content' => 'ok' ), array( '_yoast_wpseo_metadesc' => '[DRAFT: mô tả]' ) );
hcwa_assert_same( array( 'Meta description' ), hcwa_draft_fields( hcwa_publish_guard_texts( $seo_post, true ) ), 'guard: a draft meta description is found' );
hcwa_assert_same( array(), hcwa_draft_fields( hcwa_publish_guard_texts( $seo_post, false ) ), 'guard: ignored without Yoast' );
hcwa_publish_guard_after( 95, $seo_post, true );
hcwa_assert_same( defined( 'WPSEO_VERSION' ) ? 'draft' : 'publish', $seo_post->post_status, 'guard: follows whether Yoast is loaded' );
$GLOBALS['hcwa_test']['env'] = 'staging';

// A new translation does not start with the other language's SEO texts.
$keys = array( '_thumbnail_id', '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_opengraph-image', 'hcwa_since_year' );
hcwa_assert_same( array( '_thumbnail_id', '_yoast_wpseo_opengraph-image', 'hcwa_since_year' ), hcwa_pll_drop_seo_text( $keys, false ), 'copy: Yoast texts dropped' );
hcwa_assert_same( $keys, hcwa_pll_drop_seo_text( $keys, true ), 'sync: left alone' );
$copy_hook = array_values( array_filter( $GLOBALS['hcwa_test']['hooks']['pll_copy_post_metas'] ?? array(), static fn( $h ) => 'hcwa_pll_drop_seo_text' === $h[0] ) );
hcwa_assert_same( 15, $copy_hook[0][1] ?? null, 'runs after Polylang\'s Yoast filter (priority 10, added later)' );
hcwa_assert_same( 8, count( hcwa_seo_text_meta_keys() ), 'the eight texts Polylang copies' );

// Organization schema: no unverified figures, no one-language description, settings merged.
$hcwa_saved_settings = $GLOBALS['hcwa_test']['options']['hcwa_settings'] ?? null;
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array(
	'address_vi' => "14 Đường Ví Dụ\nQuận 3, TP. Hồ Chí Minh",
	'address_en' => '[DRAFT: English address]',
	'facebook'   => 'https://www.facebook.com/hcwa',
	'zalo'       => 'https://zalo.me/02838401406',
);
$org = array(
	'@type'             => 'Organization',
	'name'              => 'Hội Bảo trợ Trẻ em TP. Hồ Chí Minh',
	'foundingDate'      => '1991',
	'numberOfEmployees' => array( '@type' => 'QuantitativeValue', 'value' => 50 ),
	'description'       => 'Hơn 30 năm giúp 10.000 trẻ em',
	'legalName'         => '[DRAFT: tên pháp lý]',
	'sameAs'            => array( 'https://www.facebook.com/hcwa' ),
);
$GLOBALS['hcwa_test']['env'] = 'production';
$out = hcwa_seo_schema_organization( $org );
hcwa_assert_false( isset( $out['foundingDate'] ) || isset( $out['numberOfEmployees'] ), 'organization: no founding date or staff count (unverified figures)' );
hcwa_assert_false( isset( $out['description'] ), 'organization: no free-text, one-language description' );
hcwa_assert_false( isset( $out['legalName'] ), 'production: draft text left out' );
hcwa_assert_same( 'HCWA', $out['alternateName'], 'alternate name HCWA added' );
hcwa_assert_same( array( 'https://www.facebook.com/hcwa', 'https://zalo.me/02838401406' ), $out['sameAs'], 'sameAs: Facebook once, Zalo added' );
hcwa_assert_same( array( '@type' => 'PostalAddress', 'streetAddress' => '14 Đường Ví Dụ, Quận 3, TP. Hồ Chí Minh', 'addressCountry' => 'VN' ), $out['address'], 'address in the page language (vi), one line' );
$GLOBALS['hcwa_test']['options']['hcwa_settings']['address_vi'] = '[DRAFT: địa chỉ]';
hcwa_assert_false( isset( hcwa_seo_schema_organization( $org )['address'] ), 'production: an address with a draft marker is left out' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$out = hcwa_seo_schema_organization( $org + array( 'alternateName' => 'Ho Chi Minh City Child Welfare Association' ) );
hcwa_assert_same( '[DRAFT: tên pháp lý]', $out['legalName'], 'staging: draft text shown for review' );
hcwa_assert_same( array( 'Ho Chi Minh City Child Welfare Association', 'HCWA' ), $out['alternateName'], 'staff alternate name kept, HCWA added' );
hcwa_assert_same( 'x', hcwa_seo_schema_organization( 'x' ), 'not an array: left alone' );
hcwa_assert_true( has_filter( 'wpseo_schema_organization' ), 'organization filter added' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = $hcwa_saved_settings;
if ( null === $hcwa_saved_settings ) {
	unset( $GLOBALS['hcwa_test']['options']['hcwa_settings'] );
}
