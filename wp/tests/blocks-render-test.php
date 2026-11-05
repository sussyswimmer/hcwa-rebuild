<?php
/**
 * hcwa/* blocks: the render.php templates, run the way WordPress runs them (require with
 * $attributes, $content and $block in scope). get_posts() returns nothing in the stubs,
 * so the list blocks are tested for their empty states; settings-driven blocks are
 * tested with real option values.
 *
 * @package hcwa-core
 */

$hcwa_render = static function ( string $name, array $attributes = array() ): string {
	$file = dirname( __DIR__ ) . '/plugins/hcwa-core/blocks/' . $name . '/render.php';
	return ( static function () use ( $file, $attributes ) {
		$content = '';
		$block   = null;
		ob_start();
		require $file;
		return trim( (string) ob_get_clean() );
	} )();
};

// Empty states: nothing on production, a short note on staging (data-draft outline).
$hcwa_empty = array(
	'impact-strip' => array( 'scopeType' => 'organization' ),
	'center-grid'  => array( 'limit' => 3 ),
	'program-list' => array( 'status' => 'any' ),
	'report-list'  => array( 'reportType' => 'any' ),
	'partner-wall' => array( 'homeOnly' => true ),
	'news-latest'  => array( 'featured' => true ),
);
foreach ( $hcwa_empty as $hcwa_name => $hcwa_attrs ) {
	$GLOBALS['hcwa_test']['env'] = 'production';
	hcwa_assert_same( '', $hcwa_render( $hcwa_name, $hcwa_attrs ), "$hcwa_name: nothing on production when empty" );
	$GLOBALS['hcwa_test']['env'] = 'staging';
	$hcwa_html = $hcwa_render( $hcwa_name, $hcwa_attrs );
	hcwa_assert_contains( '<p class="wp-block-hcwa-test empty-state" data-draft="">', $hcwa_html, "$hcwa_name: staging note" );
}
// Same for the center and program scopes of the impact strip outside a center page.
hcwa_assert_contains( 'empty-state', $hcwa_render( 'impact-strip', array( 'scopeType' => 'center', 'scopeId' => 0 ) ), 'impact strip: center scope without a page' );
// Rendering twice must work: render.php is required on every render (no function declarations).
hcwa_assert_contains( 'empty-state', $hcwa_render( 'center-grid' ), 'center grid renders a second time' );

// Announcement bar: off, on, per language, draft text on production.
hcwa_assert_same( '', $hcwa_render( 'announcement-bar' ), 'announcement: off by default' );
update_option(
	'hcwa_settings',
	array(
		'announcement_enabled' => true,
		'announcement_text_vi' => 'Hội chợ Tết: gửi hiện vật trước ngày 15/1 <b>ngay</b>',
		'announcement_text_en' => '',
		'announcement_link_vi' => 'https://hcwa.test/chung-tay/hien-vat/',
	)
);
$hcwa_html = $hcwa_render( 'announcement-bar' );
hcwa_assert_contains( 'class="wp-block-hcwa-test announce" data-announce="Hội chợ Tết: gửi hiện vật trước ngày 15/1 &lt;b&gt;ngay&lt;/b&gt;"', $hcwa_html, 'announcement: wrapper keyed by its text' );
hcwa_assert_contains( '<a href="https://hcwa.test/chung-tay/hien-vat/">Đọc thêm</a>', $hcwa_html, 'announcement: read more link in vi' );
hcwa_assert_contains( 'data-dismiss-announce aria-label="Ẩn thông báo"', $hcwa_html, 'announcement: labelled close button' );
hcwa_assert_false( str_contains( $hcwa_html, '<b>' ), 'announcement: text escaped' );
update_option(
	'hcwa_settings',
	array(
		'announcement_enabled' => true,
		'announcement_text_vi' => '[DRAFT: Hội chợ Tết]',
	)
);
hcwa_assert_contains( '<p data-draft>', $hcwa_render( 'announcement-bar' ), 'announcement: draft text outlined on staging' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', $hcwa_render( 'announcement-bar' ), 'announcement: draft text never on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';

// Donation methods (contract gating rule 3).
update_option( 'hcwa_settings', array( 'donation_account' => '0123456789', 'donation_bank_vi' => 'Ngân hàng X' ) );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', $hcwa_render( 'donation-methods' ), 'donation: unconfirmed details never on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_html = $hcwa_render( 'donation-methods' );
hcwa_assert_contains( '<div class="wp-block-hcwa-test methods">', $hcwa_html, 'donation: methods wrapper' );
hcwa_assert_contains( 'data-donation-placeholder', $hcwa_html, 'donation: staging shows the labelled placeholder' );
hcwa_assert_false( str_contains( $hcwa_html, '0123456789' ), 'donation: unconfirmed account number never printed, even on staging' );
hcwa_assert_contains( 'aria-live="polite" data-live', $hcwa_html, 'donation: live region for the copy buttons' );
update_option(
	'hcwa_settings',
	array(
		'donation_account'        => '0123456789',
		'donation_bank_vi'        => 'Ngân hàng X',
		'donation_vietqr_id'      => 55,
		'donation_confirmed_by'   => 'Kế toán',
		'donation_confirmed_date' => '2026-11-01',
	)
);
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_html                   = $hcwa_render( 'donation-methods' );
hcwa_assert_contains( 'data-copy="0123456789"', $hcwa_html, 'donation: confirmed account with copy button on production' );
hcwa_assert_contains( 'class="pay__qr" alt="Mã VietQR để chuyển khoản"', $hcwa_html, 'donation: VietQR image with alt text' );
hcwa_assert_false( str_contains( $hcwa_html, 'data-donation-placeholder' ), 'donation: no placeholder once confirmed' );
update_option( 'hcwa_settings', array( 'donation_confirmed_by' => 'Kế toán', 'donation_confirmed_date' => '2026-11-01' ) );
hcwa_assert_same( '', $hcwa_render( 'donation-methods' ), 'donation: confirmed but empty prints nothing on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_contains( 'empty-state', $hcwa_render( 'donation-methods' ), 'donation: confirmed but empty shows a note on staging' );

// Language notice (07-BILINGUAL): only when asked for, in the language asked for, once
// per page, and only while the post has no published translation in that language.
// $hcwa_notice renders it as the first notice of a new page.
$hcwa_notice = static function ( array $attributes = array() ) use ( $hcwa_render ): string {
	hcwa_blocks_notice_claim( true );
	return $hcwa_render( 'language-notice', $attributes );
};
$_SERVER['REQUEST_URI'] = '/mot-bai-viet/?hcwa_lang_notice=en';
hcwa_assert_same( '', $hcwa_notice(), 'notice: nothing without a request' );
$_GET['hcwa_lang_notice'] = 'en';
$hcwa_html                = $hcwa_notice();
hcwa_assert_contains( '<aside class="wp-block-hcwa-test lang-notice" lang="en">', $hcwa_html, 'notice: speaks the requested language' );
hcwa_assert_contains( '<p>This article is only available in Vietnamese.</p>', $hcwa_html, 'notice: text' );
hcwa_assert_contains( 'href="https://hcwa.test/mot-bai-viet/" hreflang="vi"><span>Read it in Vietnamese</span>', $hcwa_html, 'notice: link back to the article without the argument' );
hcwa_assert_contains( 'href="https://hcwa.test/en/news/" hreflang="en"><span>See English news</span>', $hcwa_html, 'notice: link to English news' );
$_GET['hcwa_lang_notice'] = 'vi';
hcwa_assert_same( '', $hcwa_notice(), 'notice: not in the page language' );
$_GET['hcwa_lang_notice'] = '<script>';
hcwa_assert_same( '', $hcwa_notice(), 'notice: junk request ignored' );
unset( $_GET['hcwa_lang_notice'] );
hcwa_assert_contains( 'lang="en"', $hcwa_notice( array( 'forceLang' => 'en' ) ), 'notice: forceLang (single.php passes the request through it)' );
hcwa_assert_same( '', $hcwa_notice( array( 'forceLang' => 'xx' ) ), 'notice: invalid forceLang ignored' );

// One panel per page: single.php prints it above the article (forceLang), so the same
// block placed inside the article prints nothing. A render that prints nothing does not
// use up the page's notice.
hcwa_blocks_notice_claim( true );
$_GET['hcwa_lang_notice'] = 'en';
hcwa_assert_contains( 'lang-notice', $hcwa_render( 'language-notice', array( 'forceLang' => 'en' ) ), 'notice: the template prints the first one' );
hcwa_assert_same( '', $hcwa_render( 'language-notice' ), 'notice: a block inside the article adds no second panel' );
hcwa_blocks_notice_claim( true );
unset( $_GET['hcwa_lang_notice'] );
hcwa_assert_same( '', $hcwa_render( 'language-notice' ), 'notice: nothing to show (no request)' );
$_GET['hcwa_lang_notice'] = 'en';
hcwa_assert_contains( 'lang-notice', $hcwa_render( 'language-notice' ), 'notice: an empty render left the notice for the page' );

// The post on screen: its own link, and no notice once an English version is published
// (a bookmarked switch link, or a forceLang saved on a post translated later).
$GLOBALS['hcwa_test']['current_post'] = hcwa_test_post( array( 'ID' => 300, 'post_type' => 'post', 'post_name' => 'bai-viet-goc' ) );
hcwa_test_post( array( 'ID' => 301, 'post_type' => 'post', 'post_name' => 'the-article', 'post_status' => 'draft' ) );
$GLOBALS['hcwa_test']['translations'][300] = array( 'vi' => 300, 'en' => 301 );
hcwa_assert_contains( 'href="https://hcwa.test/bai-viet-goc/" hreflang="vi"', $hcwa_notice(), 'notice: draft translation, still shown, read link to the post itself' );
$GLOBALS['hcwa_test']['posts'][301]->post_status = 'publish';
hcwa_assert_same( '', $hcwa_notice(), 'notice: none once the translation is published' );
hcwa_assert_same( '', $hcwa_notice( array( 'forceLang' => 'en' ) ), 'notice: a saved forceLang stops once the translation is published' );

hcwa_blocks_notice_claim( true );
unset( $_GET['hcwa_lang_notice'], $_SERVER['REQUEST_URI'], $GLOBALS['hcwa_test']['current_post'], $GLOBALS['hcwa_test']['translations'] );
