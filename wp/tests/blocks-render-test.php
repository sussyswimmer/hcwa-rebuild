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

// Lists with nothing to show print nothing, on staging and production alike (the prototype
// prints no empty state); the partner wall shows its pending note on staging only.
$hcwa_empty = array(
	'impact-strip' => array( 'scopeType' => 'organization' ),
	'program-list' => array( 'status' => 'any' ),
	'report-list'  => array( 'reportType' => 'any' ),
	'news-latest'  => array( 'featured' => true ),
);
foreach ( $hcwa_empty as $hcwa_name => $hcwa_attrs ) {
	foreach ( array( 'production', 'staging' ) as $hcwa_env ) {
		$GLOBALS['hcwa_test']['env'] = $hcwa_env;
		hcwa_assert_same( '', $hcwa_render( $hcwa_name, $hcwa_attrs ), "$hcwa_name: nothing on $hcwa_env when empty" );
	}
}
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', $hcwa_render( 'partner-wall', array( 'homeOnly' => true ) ), 'partner wall: nothing on production below the minimum' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_html = $hcwa_render( 'partner-wall', array( 'homeOnly' => true, 'title' => 'Đối tác' ) );
hcwa_assert_contains( 'data-partners-pending', $hcwa_html, 'partner wall: pending wall on staging' );
hcwa_assert_contains( 'data-draft', $hcwa_html, 'partner wall: the pending note is outlined' );
// Rendering twice must work: render.php is required on every render (no function declarations).
hcwa_assert_same( '', $hcwa_render( 'program-list' ), 'program list renders a second time' );

// No wrapper markup: each block prints the prototype's own element first.
$hcwa_first = array(
	'hero'      => array( array( 'title' => 'Xin chào', 'intro' => 'Lời mở đầu' ), '<section class="hero' ),
	'passage'   => array( array( 'title' => 'Lối sáng', 'statement' => 'Một câu' ), '<section class="passage"' ),
	'corridor'  => array( array( 'intro' => 'Các cơ sở' ), '<section class="bays-section"' ),
	'shutters'  => array( array(), '<section class="shutters"' ),
	'help'      => array( array( 'title' => 'Cách giúp' ), '<section class="wall wall--light help"' ),
	'dusk-band' => array( array( 'title' => 'Liên hệ', 'titleId' => 'contact-title', 'actions' => array( 'contact' ) ), '<section class="dusk"' ),
	'work-hub'  => array( array( 'centersLead' => 'Cơ sở', 'programsLead' => 'Dự án' ), '<nav class="filters container"' ),
	'gifts'     => array( array( 'title' => 'Ví dụ', 'gifts' => array( array( 'amount' => '[DRAFT: 200.000 đ]', 'text' => '[DRAFT: Sách vở]' ) ) ), '<section class="gifts container"' ),
);
foreach ( $hcwa_first as $hcwa_name => $hcwa_case ) {
	$hcwa_html = $hcwa_render( $hcwa_name, $hcwa_case[0] );
	hcwa_assert_same( 0, strpos( $hcwa_html, $hcwa_case[1] ), "$hcwa_name: starts with the prototype's element" );
	hcwa_assert_false( str_contains( $hcwa_html, 'wp-block-hcwa' ), "$hcwa_name: no block wrapper class" );
}
$hcwa_html = $hcwa_render( 'dusk-band', array( 'title' => 'Liên hệ', 'titleId' => 'contact-title', 'anchor' => 'contact', 'actions' => array( 'contact' ) ) );
hcwa_assert_contains( '<section class="dusk" id="contact" aria-labelledby="contact-title">', $hcwa_html, 'dusk band: anchor and labelled by its title' );
hcwa_assert_contains( '<h2 class="dusk__title" id="contact-title">Liên hệ</h2>', $hcwa_html, 'dusk band: title' );
hcwa_assert_false( str_contains( $hcwa_render( 'dusk-band', array( 'titleId' => 'x' ) ), 'aria-labelledby' ), 'dusk band: no title, no aria-labelledby' );

// Gift examples (P5): staging only, the amounts are HCWA's to confirm.
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', $hcwa_render( 'gifts', $hcwa_first['gifts'][0] ), 'gifts: never on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_contains( 'Sách vở', $hcwa_render( 'gifts', $hcwa_first['gifts'][0] ), 'gifts: shown on staging with the draft unwrapped' );

// Draft text attributes: unwrapped and outlined on staging, left out on production.
$hcwa_html = $hcwa_render( 'passage', array( 'title' => '[DRAFT: Lối sáng]', 'statement' => 'Một câu' ) );
hcwa_assert_contains( 'Lối sáng', $hcwa_html, 'draft attr: the inner text on staging' );
hcwa_assert_false( str_contains( $hcwa_html, '[DRAFT' ), 'draft attr: no marker printed' );
hcwa_assert_contains( 'data-draft', $hcwa_html, 'draft attr: outlined on staging' );
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_html = $hcwa_render( 'passage', array( 'title' => '[DRAFT: Lối sáng]', 'statement' => 'Một câu' ) );
hcwa_assert_false( str_contains( $hcwa_html, 'Lối sáng' ) || str_contains( $hcwa_html, 'data-draft' ), 'draft attr: neither the text nor data-draft on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';

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
hcwa_assert_contains( '<div class="announce" data-announce="Hội chợ Tết: gửi hiện vật trước ngày 15/1 ngay">', $hcwa_html, 'announcement: keyed by its plain text' );
hcwa_assert_contains( '<a href="https://hcwa.test/chung-tay/hien-vat/">Đọc thêm</a>', $hcwa_html, 'announcement: read more link in vi' );
hcwa_assert_contains( 'data-dismiss-announce aria-label="Ẩn thông báo"', $hcwa_html, 'announcement: labelled close button' );
hcwa_assert_false( str_contains( $hcwa_html, '<b>' ), 'announcement: no markup from the setting' );
update_option(
	'hcwa_settings',
	array(
		'announcement_enabled' => true,
		'announcement_text_vi' => '[DRAFT: Hội chợ Tết]',
	)
);
hcwa_assert_contains( '<p data-draft>Hội chợ Tết', $hcwa_render( 'announcement-bar' ), 'announcement: draft text outlined on staging' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', $hcwa_render( 'announcement-bar' ), 'announcement: draft text never on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';

// Donation methods (contract gating rule 3).
$hcwa_pay = array(
	'howTitle'      => 'Cách ủng hộ',
	'tabsLabel'     => 'Cách chuyển',
	'inVietnam'     => 'Trong nước',
	'fromAbroad'    => 'Từ nước ngoài',
	'qr'            => 'Mã QR',
	'pendingNote'   => '[DRAFT: Chờ bộ phận tài chính xác nhận]',
	'toCenterTitle' => 'Ủng hộ một cơ sở',
	'tellTitle'     => 'Báo cho Hội',
	'bankFields'    => array(
		array( 'id' => 'bank', 'label' => 'Ngân hàng', 'copy' => false ),
		array( 'id' => 'account', 'label' => 'Số tài khoản', 'copy' => true ),
	),
);
update_option( 'hcwa_settings', array( 'donation_account' => '0123456789', 'donation_bank_vi' => 'Ngân hàng X' ) );
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_html                   = $hcwa_render( 'donation-methods', $hcwa_pay );
hcwa_assert_false( str_contains( $hcwa_html, 'class="slip"' ) || str_contains( $hcwa_html, '0123456789' ), 'donation: no slip and no unconfirmed details on production' );
hcwa_assert_false( str_contains( $hcwa_html, 'data-form' ), 'donation: no form on production without a handler' );
hcwa_assert_contains( '<nav class="trust container"', $hcwa_html, 'donation: the trust list stays on production (3.5)' );
hcwa_assert_false( str_contains( substr( $hcwa_html, (int) strpos( $hcwa_html, '<nav class="trust' ) ), 'href' ), 'donation: no trust link while none of its pages is published (fix round 1): words only' );
hcwa_assert_contains( '<li><span>', $hcwa_html, 'donation: an unpublished trust item keeps its words' );
hcwa_test_post( array( 'ID' => 610, 'post_type' => 'page', 'post_name' => 'chinh-sach', 'post_status' => 'publish' ) );
$GLOBALS['hcwa_test']['page_paths'] = array( 've-chung-toi/chinh-sach' => 610 );
$hcwa_html                          = $hcwa_render( 'donation-methods', $hcwa_pay );
hcwa_assert_contains( '<nav class="trust container"', $hcwa_html, 'donation: the trust list with Policies published' );
hcwa_assert_false( str_contains( $hcwa_html, '#bao-cao' ), 'donation: no link to the unpublished Impact page' );
hcwa_assert_contains( '#bao-ve-tre-em', $hcwa_html, 'donation: the safeguarding link stays' );
unset( $GLOBALS['hcwa_test']['page_paths'] );
$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_html = $hcwa_render( 'donation-methods', $hcwa_pay );
hcwa_assert_same( 0, strpos( $hcwa_html, '<div class="give-grid container">' ), 'donation: the give grid' );
hcwa_assert_contains( 'data-donation-placeholder', $hcwa_html, 'donation: staging shows the labelled placeholder' );
hcwa_assert_false( str_contains( $hcwa_html, '0123456789' ), 'donation: unconfirmed account number never printed, even on staging' );
hcwa_assert_contains( 'aria-describedby="pay-note"', $hcwa_html, 'donation: copy buttons described by the pending note' );
hcwa_assert_contains( '<p class="slip__note" id="pay-note" data-draft>', $hcwa_html, 'donation: the pending note' );
hcwa_assert_contains( 'data-form', $hcwa_html, 'donation: the tell form on staging' );
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
$hcwa_html                   = $hcwa_render( 'donation-methods', $hcwa_pay );
hcwa_assert_contains( 'data-copy="0123456789"', $hcwa_html, 'donation: confirmed account with copy button on production' );
hcwa_assert_contains( 'alt="Mã QR"', $hcwa_html, 'donation: VietQR image with alt text' );
hcwa_assert_false( str_contains( $hcwa_html, 'data-donation-placeholder' ) || str_contains( $hcwa_html, 'data-draft' ), 'donation: no placeholder and no draft outline once confirmed' );
hcwa_assert_false( str_contains( $hcwa_html, 'pay-note' ), 'donation: a draft note is left out on production, and nothing points at it' );
$GLOBALS['hcwa_test']['env'] = 'staging';

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
