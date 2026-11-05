<?php
/**
 * hcwa/* blocks: markup builders. Prototype class names, escaping, and the staging-only
 * parts (pending chips, placeholder slots) that must never reach the live site.
 *
 * @package hcwa-core
 */

$hcwa_evil = '<script>alert(1)</script>';

// Impact figure: verified vs pending (contract gating rule 1).
$hcwa_html = hcwa_blocks_figure_html( array( 'value' => 300000.0, 'suffix' => '+', 'label' => 'trẻ em được hỗ trợ', 'year' => 2025, 'verified' => true, 'id' => 1 ), 'vi', true );
hcwa_assert_contains( '<p class="figure__value">300.000+</p>', $hcwa_html, 'verified figure value' );
hcwa_assert_contains( '<span class="figure__year">(2025)</span>', $hcwa_html, 'figure year' );
hcwa_assert_false( str_contains( $hcwa_html, 'chip-pending' ), 'verified figure has no pending chip' );
$hcwa_html = hcwa_blocks_figure_html( array( 'value' => null, 'suffix' => '', 'label' => 'children supported in [year]', 'year' => null, 'verified' => false, 'id' => 2 ), 'en', false );
hcwa_assert_contains( '<span class="chip-pending" data-pending>Pending verification</span>', $hcwa_html, 'unverified figure carries the pending chip' );
hcwa_assert_contains( '<div class="figure" data-draft>', $hcwa_html, 'draft label outlined on staging' );
hcwa_assert_contains( '[N]', $hcwa_html, 'missing value shows the token on staging' );
$hcwa_html = hcwa_blocks_figure_html( array( 'value' => 5.0, 'label' => '🎉 ' . $hcwa_evil, 'verified' => true ), 'vi', true );
hcwa_assert_false( str_contains( $hcwa_html, '<script>' ), 'figure label is escaped' );
hcwa_assert_false( str_contains( $hcwa_html, '🎉' ), 'figure label loses leading emoji' );

// Center card (prototype centerCard()).
$hcwa_center = array(
	'title'  => 'Cơ sở Bảo trợ Xã hội Tre Xanh',
	'url'    => 'https://hcwa.test/hoat-dong/co-so/tre-xanh/',
	'since'  => 1993,
	'serves' => 'Trẻ em và gia đình khó khăn',
	'accent' => 'green-bamboo',
	'areas'  => array( 'education', 'family' ),
	'image'  => '<img src="x.jpg" alt="Lớp học">',
);
$hcwa_html = hcwa_blocks_center_card( $hcwa_center, 'vi', true );
hcwa_assert_contains( '<article class="center-card" style="--unit:var(--color-unit-green-bamboo)" data-areas="education family">', $hcwa_html, 'card: unit band and areas' );
hcwa_assert_contains( '<div class="center-card__media"><img src="x.jpg" alt="Lớp học"></div>', $hcwa_html, 'card: approved photo' );
hcwa_assert_contains( '<h3 class="center-card__name"><a href="https://hcwa.test/hoat-dong/co-so/tre-xanh/">Cơ sở Bảo trợ Xã hội Tre Xanh</a></h3>', $hcwa_html, 'card: name links to the center' );
hcwa_assert_contains( '<p class="center-card__since">Từ năm 1993</p>', $hcwa_html, 'card: since year in vi' );
hcwa_assert_contains( '<span class="text-link" aria-hidden="true"><span>Xem cơ sở</span>', $hcwa_html, 'card: visit line in vi' );
$hcwa_no_photo = array_merge( $hcwa_center, array( 'image' => '', 'since' => 0, 'accent' => 'pink' ) );
$hcwa_html     = hcwa_blocks_center_card( $hcwa_no_photo, 'en', true );
hcwa_assert_false( str_contains( $hcwa_html, 'class="ph"' ) || str_contains( $hcwa_html, 'center-card__media' ), 'card: no photo slot on production' );
hcwa_assert_false( str_contains( $hcwa_html, 'center-card__since' ), 'card: unknown year left out' );
hcwa_assert_contains( '--color-unit-blue', $hcwa_html, 'card: unknown accent falls back to blue' );
hcwa_assert_contains( 'Visit center', $hcwa_html, 'card: visit line in en' );
$hcwa_html = hcwa_blocks_center_card( $hcwa_no_photo, 'en', false );
hcwa_assert_contains( '<div class="ph" role="img" aria-label="blk_noPhoto"', $hcwa_html, 'card: labelled photo slot on staging' );
$hcwa_html = hcwa_blocks_center_card( array_merge( $hcwa_center, array( 'title' => $hcwa_evil, 'url' => 'javascript:alert(1)' ) ), 'vi', true );
hcwa_assert_false( str_contains( $hcwa_html, '<script>' ), 'card: title escaped' );
hcwa_assert_contains( '<a href="">', $hcwa_html, 'card: javascript: URL dropped' );

// Program row (prototype programRow()).
$hcwa_program = array(
	'title'    => 'Chương trình Cùng Vươn Lên',
	'url'      => 'https://hcwa.test/hoat-dong/du-an/cung-vuon-len/',
	'status'   => 'active',
	'years'    => '2023–2026',
	'summary'  => 'Hỗ trợ trẻ em dễ bị tổn thương.',
	'partners' => array( 'Đối tác A', 'Đối tác B' ),
	'areas'    => array( 'protection' ),
);
$hcwa_html = hcwa_blocks_program_row( $hcwa_program, 'vi', true );
hcwa_assert_contains( '<li class="program-row" data-areas="protection" data-status="active">', $hcwa_html, 'program: row with areas and status' );
hcwa_assert_contains( '<span class="status status--active">Đang hoạt động</span><span class="program-row__years">2023–2026</span>', $hcwa_html, 'program: status pill and years' );
hcwa_assert_contains( '<p class="program-row__partners">Đối tác A, Đối tác B</p>', $hcwa_html, 'program: partner names' );
hcwa_assert_contains( 'class="icon program-row__arrow"', $hcwa_html, 'program: arrow' );
$hcwa_html = hcwa_blocks_program_row( array_merge( $hcwa_program, array( 'status' => 'completed', 'years' => '', 'partners' => array(), 'summary' => '' ) ), 'en', true );
hcwa_assert_contains( '<span class="status status--completed">Completed</span></div>', $hcwa_html, 'program: completed pill, no years' );
hcwa_assert_false( str_contains( $hcwa_html, 'program-row__partners' ), 'program: no partner line without partners' );
$hcwa_html = hcwa_blocks_program_row( array_merge( $hcwa_program, array( 'status' => '"><script>' ) ), 'en', true );
hcwa_assert_contains( 'status--active', $hcwa_html, 'program: unknown status becomes active' );

// Report row (prototype reportRow()).
$hcwa_report = array(
	'title'    => 'Báo cáo thường niên 2025',
	'url'      => 'https://hcwa.test/wp-content/uploads/bao-cao-2025.pdf',
	'year'     => 2025,
	'type'     => 'annual',
	'doc_lang' => 'vi',
	'format'   => 'pdf',
	'bytes'    => 820 * 1024,
	'cover'    => '',
);
$hcwa_html = hcwa_blocks_report_row( $hcwa_report, 'en', true );
hcwa_assert_contains( '<span class="report-row__cover report-row__cover--empty" aria-hidden="true">PDF</span>', $hcwa_html, 'report: empty cover' );
hcwa_assert_contains( '<p class="report-row__meta">2025 · blk_type_annual</p>', $hcwa_html, 'report: year and type label key (the dictionary filter is not applied in tests)' );
hcwa_assert_contains( '<span class="badge">PDF · <span lang="vi">Tiếng Việt</span></span>', $hcwa_html, 'report: language badge on the EN site shows the document language' );
hcwa_assert_contains( '<span class="report-row__size">820 KB</span>', $hcwa_html, 'report: size' );
hcwa_assert_contains( 'href="https://hcwa.test/wp-content/uploads/bao-cao-2025.pdf"', $hcwa_html, 'report: links to the file' );
$hcwa_html = hcwa_blocks_report_row( array_merge( $hcwa_report, array( 'type' => 'secret', 'year' => 0 ) ), 'vi', true );
hcwa_assert_false( str_contains( $hcwa_html, 'report-row__meta' ), 'report: no meta line without year or known type' );

// Partner logos (contract gating rule 5).
$hcwa_html = hcwa_blocks_partner_item( array( 'name' => 'Partner Co', 'url' => 'https://partner.example', 'logo' => '<img class="partner-logo" alt="Partner Co">', 'permitted' => true ), 'vi' );
hcwa_assert_same( '<li><a href="https://partner.example"><img class="partner-logo" alt="Partner Co"></a></li>', $hcwa_html, 'partner: linked logo' );
$hcwa_html = hcwa_blocks_partner_item( array( 'name' => 'Partner Co', 'url' => '', 'logo' => '', 'permitted' => false ), 'en' );
hcwa_assert_contains( '<span class="ph ph--logo" role="img" aria-label="Partner Co">', $hcwa_html, 'partner: named slot without a logo (staging)' );
hcwa_assert_contains( 'class="chip-pending" data-pending>blk_logoPermission</span>', $hcwa_html, 'partner: permission chip (staging)' );

$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_partner = hcwa_test_post( array( 'ID' => 70, 'post_type' => 'hcwa_partner', 'post_title' => 'No Permission Ltd' ), array( '_thumbnail_id' => 71 ) );
hcwa_assert_same( null, hcwa_blocks_partner_data( $hcwa_partner, true ), 'partner: no permission, no logo on production' );
$hcwa_partner = hcwa_test_post( array( 'ID' => 72, 'post_type' => 'hcwa_partner', 'post_title' => 'Allowed & Co' ), array( '_thumbnail_id' => 73, 'hcwa_logo_permission' => true, 'hcwa_website' => 'https://allowed.example' ) );
$hcwa_data    = hcwa_blocks_partner_data( $hcwa_partner, true );
hcwa_assert_true( is_array( $hcwa_data ) && $hcwa_data['permitted'], 'partner: permitted logo on production' );
hcwa_assert_contains( 'alt="Allowed &amp; Co"', (string) ( $hcwa_data['logo'] ?? '' ), 'partner: logo alt is the partner name' );
$hcwa_partner = hcwa_test_post( array( 'ID' => 74, 'post_type' => 'hcwa_partner', 'post_title' => 'No Logo' ), array( 'hcwa_logo_permission' => true ) );
hcwa_assert_same( null, hcwa_blocks_partner_data( $hcwa_partner, true ), 'partner: permitted but no logo stays out on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_partner = hcwa_test_post( array( 'ID' => 75, 'post_type' => 'hcwa_partner', 'post_title' => 'Waiting' ), array() );
$hcwa_data    = hcwa_blocks_partner_data( $hcwa_partner, false );
hcwa_assert_true( is_array( $hcwa_data ) && false === $hcwa_data['permitted'], 'partner: staging shows it, marked as not permitted' );

// News: story card and rows (prototype storyCard(), newsItem()).
$hcwa_story = array(
	'title'    => 'Một ngày ở lớp học tình thương',
	'url'      => 'https://hcwa.test/mot-ngay/',
	'date'     => '2026-02-08',
	'label'    => 'Câu chuyện',
	'category' => 'stories',
	'excerpt'  => 'Đoạn trích.',
	'image'    => '',
);
$hcwa_html = hcwa_blocks_story_card( $hcwa_story, 'vi', true );
hcwa_assert_contains( '<p class="story-card__meta"><span class="label">Câu chuyện</span><time datetime="2026-02-08">08/02/2026</time></p>', $hcwa_html, 'story: label and vi date' );
hcwa_assert_contains( '<p class="story-card__note">Tên nhân vật đã được thay đổi để bảo vệ trẻ.</p>', $hcwa_html, 'story: names changed note' );
hcwa_assert_false( str_contains( $hcwa_html, 'story-card__media' ), 'story: no empty photo slot on production' );
$hcwa_html = hcwa_blocks_story_card( array_merge( $hcwa_story, array( 'category' => 'news', 'label' => 'News' ) ), 'en', false );
hcwa_assert_contains( '<time datetime="2026-02-08">8 Feb 2026</time>', $hcwa_html, 'story: en date' );
hcwa_assert_false( str_contains( $hcwa_html, 'story-card__note' ), 'story: no names note on a news post' );
hcwa_assert_contains( 'story-card__media--empty', $hcwa_html, 'story: empty slot on staging' );
$hcwa_html = hcwa_blocks_news_item( array( 'title' => $hcwa_evil, 'url' => 'https://hcwa.test/x/', 'date' => '2026-01-05', 'label' => '' ), 'vi', true );
hcwa_assert_contains( '<li class="news-item"><p class="news-item__meta"><time datetime="2026-01-05">05/01/2026</time></p>', $hcwa_html, 'news row: date without label' );
hcwa_assert_false( str_contains( $hcwa_html, '<script>' ), 'news row: title escaped' );

// Donation methods (contract gating rule 3).
$hcwa_html = hcwa_blocks_donation_methods( null, 'vi' );
hcwa_assert_true( substr_count( $hcwa_html, 'data-donation-placeholder' ) >= 10, 'placeholder: every value, the QR and PayPal are marked' );
hcwa_assert_contains( '<details class="method" id="trong-nuoc" open>', $hcwa_html, 'placeholder: first method open' );
hcwa_assert_contains( '<dt>Số tài khoản</dt><dd><span class="pending-value" data-donation-placeholder>Chờ HCWA xác nhận</span></dd>', $hcwa_html, 'placeholder: labelled pending value' );
hcwa_assert_contains( 'data-copy="" disabled', $hcwa_html, 'placeholder: copy buttons disabled' );
hcwa_assert_false( (bool) preg_match( '/\d{6,}/', $hcwa_html ), 'placeholder: no account-like numbers' );
hcwa_assert_contains( '<details class="method" id="quoc-te">', $hcwa_html, 'placeholder: abroad method' );

$hcwa_details = hcwa_donation_details_from(
	array(
		'donation_bank_en'        => 'Example Bank',
		'donation_account'        => '0123 456 789',
		'donation_holder'         => 'HCWA',
		'donation_note_en'        => 'GIFT + phone',
		'donation_paypal_url'     => 'https://paypal.example/hcwa',
		'donation_confirmed_by'   => 'Finance',
		'donation_confirmed_date' => '2026-11-01',
	),
	'en'
);
$hcwa_html = hcwa_blocks_donation_methods( $hcwa_details, 'en', '<img class="pay__qr" alt="QR">' );
hcwa_assert_contains( '<details class="method" id="in-vietnam" open>', $hcwa_html, 'confirmed: in Vietnam method' );
hcwa_assert_contains( '<dd><code>0123 456 789</code></dd><button class="btn btn--small btn--secondary" type="button" data-copy="0123 456 789" aria-label="Copy: Account number">', $hcwa_html, 'confirmed: account with copy button' );
hcwa_assert_contains( '<dt>Bank</dt><dd>Example Bank</dd><span></span>', $hcwa_html, 'confirmed: bank without copy button' );
hcwa_assert_contains( '<span class="pay-details__hint">So HCWA can match your gift and send a receipt.</span>', $hcwa_html, 'confirmed: transfer note hint' );
hcwa_assert_contains( '<img class="pay__qr" alt="QR"></div>', $hcwa_html, 'confirmed: QR next to the rows' );
hcwa_assert_contains( '<details class="method" id="from-abroad"><summary>', $hcwa_html, 'confirmed: abroad method for PayPal only' );
hcwa_assert_contains( '<a class="btn btn--secondary btn--small" href="https://paypal.example/hcwa">Give through PayPal</a>', $hcwa_html, 'confirmed: PayPal button' );
hcwa_assert_false( str_contains( $hcwa_html, 'data-donation-placeholder' ), 'confirmed: no placeholders' );
hcwa_assert_same( '', hcwa_blocks_donation_methods( array( 'in_vietnam' => array(), 'abroad' => array(), 'paypal_url' => '' ), 'vi' ), 'confirmed but empty: nothing' );
$hcwa_html = hcwa_blocks_donation_methods( array( 'in_vietnam' => array(), 'abroad' => array( array( 'id' => 'swift', 'label' => 'SWIFT', 'value' => '"><script>', 'copy' => true, 'hint' => '' ) ), 'paypal_url' => 'javascript:alert(1)' ), 'en' );
hcwa_assert_false( str_contains( $hcwa_html, '<script>' ), 'confirmed: values escaped' );
hcwa_assert_contains( '<details class="method" id="from-abroad" open>', $hcwa_html, 'only method is open' );
hcwa_assert_contains( 'href=""', $hcwa_html, 'PayPal javascript: URL dropped' );

// Data collectors with stubbed posts: photos go through hcwa_image() (rule 2).
$hcwa_center_post = hcwa_test_post(
	array( 'ID' => 80, 'post_type' => 'hcwa_center', 'post_title' => '🌱 Little Rose', 'post_name' => 'hoa-hong-nho' ),
	array( '_thumbnail_id' => 81, 'hcwa_no_identifiable_children' => true, 'hcwa_since_year' => 1992, 'hcwa_accent' => 'little-rose', 'hcwa_summary' => 'Girls aged 12 to 18.' )
);
hcwa_test_post( array( 'ID' => 81, 'post_type' => 'attachment' ), array( 'hcwa_approved_for_web' => true, 'hcwa_identifiable_child' => true ) );
$GLOBALS['hcwa_test']['terms'][80]['hcwa_focus_area'] = array( new WP_Term( array( 'term_id' => 5, 'slug' => 'protection-vi', 'taxonomy' => 'hcwa_focus_area' ) ) );
$hcwa_data = hcwa_blocks_center_data( $hcwa_center_post );
hcwa_assert_same( '', $hcwa_data['image'], 'center data: identifiable child refused on a no-identifiable center' );
hcwa_assert_same( 'Little Rose', $hcwa_data['title'], 'center data: leading emoji removed' );
hcwa_assert_same( array( 'protection' ), $hcwa_data['areas'], 'center data: focus area keys' );
hcwa_assert_same( 1992, $hcwa_data['since'], 'center data: since year' );
update_post_meta( 81, 'hcwa_identifiable_child', false );
$hcwa_data = hcwa_blocks_center_data( $hcwa_center_post );
hcwa_assert_contains( '<img src="https://hcwa.test/wp-content/uploads/81-medium_large.jpg"', $hcwa_data['image'], 'center data: approved, non-identifiable photo shown' );

$hcwa_program_post = hcwa_test_post(
	array( 'ID' => 90, 'post_type' => 'hcwa_program', 'post_title' => 'Thrive Together', 'post_excerpt' => 'Short excerpt.' ),
	array( 'hcwa_start_year' => 2023, 'hcwa_partner_ids' => array( 91, 92 ) )
);
hcwa_test_post( array( 'ID' => 91, 'post_type' => 'hcwa_partner', 'post_title' => 'Funder One' ) );
hcwa_test_post( array( 'ID' => 92, 'post_type' => 'hcwa_partner', 'post_title' => 'Draft Funder', 'post_status' => 'draft' ) );
$hcwa_data = hcwa_blocks_program_data( $hcwa_program_post );
hcwa_assert_same( 'active', $hcwa_data['status'], 'program data: missing status counts as active' );
hcwa_assert_same( '2023–', $hcwa_data['years'], 'program data: running since 2023' );
hcwa_assert_same( array( 'Funder One' ), $hcwa_data['partners'], 'program data: published partners only' );
hcwa_assert_same( 'Short excerpt.', $hcwa_data['summary'], 'program data: excerpt when no summary' );

$hcwa_news_post = hcwa_test_post( array( 'ID' => 100, 'post_type' => 'post', 'post_title' => '📣 Tết fair', 'post_date' => '2026-01-20 09:00:00', 'post_content' => 'One two three' ), array() );
$GLOBALS['hcwa_test']['terms'][100]['category'] = array( new WP_Term( array( 'term_id' => 1, 'slug' => 'su-kien', 'name' => 'Sự kiện', 'taxonomy' => 'category' ) ) );
$hcwa_data = hcwa_blocks_news_data( $hcwa_news_post );
hcwa_assert_same( 'Tết fair', $hcwa_data['title'], 'news data: leading emoji removed' );
hcwa_assert_same( '2026-01-20', $hcwa_data['date'], 'news data: date' );
hcwa_assert_same( 'events', $hcwa_data['category'], 'news data: category key' );
hcwa_assert_same( 'Sự kiện', $hcwa_data['label'], 'news data: category label in the page language' );
hcwa_assert_same( '', $hcwa_data['image'], 'news data: rows build no image' );
