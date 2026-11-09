<?php
/**
 * hcwa/* blocks: the shared markup builders (docs/plan/11-WP-PORT.md 2.6): prototype class
 * names, escaping, and the staging-only parts (pending chips, draft outlines, unverified
 * figures) that must never reach the live site.
 *
 * @package hcwa-core
 */

$hcwa_evil = '<script>alert(1)</script>';

// Counts (impact figures, contract gating rule 1): verified figures as they are, unverified
// ones with the token and the pending chip off production only. Never animated.
$hcwa_figures = array(
	array( 'value' => 300000.0, 'suffix' => '+', 'label' => 'trẻ em được hỗ trợ', 'verified' => true ),
	array( 'value' => null, 'label' => 'gia đình [năm]', 'verified' => false ),
	array( 'value' => 5.0, 'label' => $hcwa_evil, 'verified' => true ),
);
$hcwa_html = hcwa_counts_html( $hcwa_figures, 'Tác động', 'impact-title' );
hcwa_assert_same( 0, strpos( $hcwa_html, '<section class="counts container" aria-labelledby="impact-title">' ), 'counts: section labelled by its heading' );
hcwa_assert_contains( '<h2 class="counts__title" id="impact-title">Tác động</h2>', $hcwa_html, 'counts: heading' );
hcwa_assert_contains( '<li class="count">' . "\n    " . '<p class="count__value num">300.000+</p>', $hcwa_html, 'counts: a verified figure, Vietnamese separators' );
hcwa_assert_contains( '<li class="count" data-draft>' . "\n    " . '<p class="count__value num">[N]</p>', $hcwa_html, 'counts: an unverified figure shows the token, outlined' );
hcwa_assert_contains( 'data-pending', $hcwa_html, 'counts: the pending chip on staging' );
hcwa_assert_false( str_contains( $hcwa_html, '<script>' ), 'counts: labels escaped' );
hcwa_assert_false( (bool) preg_match( '/data-count|data-countup|countUp/i', $hcwa_html ), 'counts: no count-up hooks' );
hcwa_assert_contains( '<h3 class="counts__title"', hcwa_counts_html( $hcwa_figures, 'Tác động', 'x', true ), 'counts: nested in a story, an h3' );
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_html                   = hcwa_counts_html( $hcwa_figures, 'Tác động', 'impact-title' );
hcwa_assert_false( str_contains( $hcwa_html, '[N]' ) || str_contains( $hcwa_html, 'data-pending' ) || str_contains( $hcwa_html, 'data-draft' ), 'counts: no unverified figure, chip or outline on production' );
hcwa_assert_same( 2, substr_count( $hcwa_html, '<li class="count"' ), 'counts: only the verified figures on production' );
hcwa_assert_same( '', hcwa_counts_html( array( $hcwa_figures[1] ), 'Tác động', 'impact-title' ), 'counts: nothing verified, nothing printed on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( '', hcwa_counts_html( array(), 'Tác động', 'impact-title' ), 'counts: no figures, nothing printed' );

// Program row (prototype programRow()): a row, never a card; door chips for its centers.
$hcwa_center  = array(
	'key'       => 'green-bamboo',
	'protected' => false,
	'color'     => 'var(--color-unit-green-bamboo)',
	'short'     => array( 'text' => 'Tre Xanh', 'draft' => false ),
);
$hcwa_lr      = array(
	'key'       => 'little-rose',
	'protected' => true,
	'color'     => 'var(--color-unit-little-rose)',
	'short'     => array( 'text' => 'Hoa Hồng Nhỏ', 'draft' => false ),
);
$hcwa_program = array(
	'name'     => array( 'text' => 'Cùng Vươn Lên', 'draft' => false ),
	'url'      => 'https://hcwa.test/hoat-dong/du-an/cung-vuon-len/',
	'status'   => 'active',
	'years'    => array( 'text' => '[năm]–[năm]', 'draft' => true ),
	'summary'  => array( 'text' => 'Hỗ trợ trẻ em & gia đình.', 'draft' => false ),
	'partners' => array( 'Đối tác A', $hcwa_evil ),
	'centers'  => array( $hcwa_center, $hcwa_lr ),
	'areas'    => array(),
);
$hcwa_html = hcwa_program_row_html( $hcwa_program );
hcwa_assert_same( 0, strpos( $hcwa_html, '<li class="program-row" data-areas="" data-status="active">' ), 'row: li with areas and status' );
hcwa_assert_contains( '<span class="status status--active">', $hcwa_html, 'row: status pill' );
hcwa_assert_contains( '<span class="program-row__years" data-draft>[năm]–[năm]</span>', $hcwa_html, 'row: draft years outlined' );
hcwa_assert_contains( '<h3 class="program-row__name"><a href="https://hcwa.test/hoat-dong/du-an/cung-vuon-len/">', $hcwa_html, 'row: name links to the program' );
hcwa_assert_contains( '<p class="program-row__summary">Hỗ trợ trẻ em &amp; gia đình.</p>', $hcwa_html, 'row: summary escaped' );
hcwa_assert_contains( '<ul class="door-chips"><li class="door-chip" data-unit="green-bamboo"', $hcwa_html, 'row: door chips' );
hcwa_assert_contains( 'data-unit="little-rose"', $hcwa_html, 'row: the Little Rose chip carries data-unit' );
hcwa_assert_false( str_contains( $hcwa_html, '<script>' ), 'row: partner names escaped' );
hcwa_assert_false( str_contains( $hcwa_html, 'program-row__floor' ), 'row: no tile strip without areas' );
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_html                   = hcwa_program_row_html( $hcwa_program );
hcwa_assert_contains( '<li class="door-chip" data-unit="little-rose">Hoa Hồng Nhỏ</li>', $hcwa_html, 'row: the Little Rose chip has no door and no --unit on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_bare = hcwa_program_row_html( array_merge( $hcwa_program, array( 'partners' => array(), 'centers' => array(), 'summary' => array( 'text' => '', 'draft' => false ), 'years' => array( 'text' => '', 'draft' => false ) ) ) );
hcwa_assert_false( str_contains( $hcwa_bare, 'program-row__facts' ) || str_contains( $hcwa_bare, 'program-row__summary' ) || str_contains( $hcwa_bare, 'program-row__years' ), 'row: empty parts are left out' );

// Dated post (prototype datedPost(), 2.8): local permalink, no external label or icon.
$hcwa_post = hcwa_test_post(
	array(
		'ID'         => 600,
		'post_type'  => 'post',
		'post_title' => 'Ngày hội & trẻ em',
		'post_name'  => 'ngay-hoi',
		'post_date'  => '2026-05-25 09:00:00',
	)
);
$hcwa_html = hcwa_dated_post_html( $hcwa_post );
hcwa_assert_same( 0, strpos( $hcwa_html, '<li class="dated">' ), 'dated: li' );
hcwa_assert_contains( '<time class="dated__date" datetime="2026-05-25"><span class="dated__dm">25/05</span><span class="sr-only">/</span><span class="dated__y">2026</span></time>', $hcwa_html, 'dated: the date cut as the prototype cuts it' );
hcwa_assert_contains( '<h3 class="dated__title"><a href="https://hcwa.test/ngay-hoi/">Ngày hội &amp; trẻ em</a></h3>', $hcwa_html, 'dated: local permalink, escaped title' );
hcwa_assert_false( str_contains( $hcwa_html, 'dated__ext' ) || str_contains( $hcwa_html, 'hcwa.org.vn' ), 'dated: no external-link icon or live-site link' );
$hcwa_post->post_title = '[DRAFT: Bài viết]';
hcwa_assert_contains( '<h3 class="dated__title" data-draft><a', hcwa_dated_post_html( $hcwa_post ), 'dated: a draft title is outlined' );

// Louver row (prototype louver()).
$hcwa_html = hcwa_louver_html( array( 'id' => 'volunteer', 'title' => 'Tình nguyện', 'text' => '[DRAFT: Dạy học]' ) );
hcwa_assert_same( 0, strpos( $hcwa_html, '<li class="louver" data-way="volunteer">' ), 'louver: row' );
hcwa_assert_contains( '<h3 class="louver__title"><a href="', $hcwa_html, 'louver: the title is the link' );
hcwa_assert_contains( '<p class="louver__text" data-draft>Dạy học</p>', $hcwa_html, 'louver: draft text unwrapped and outlined' );
$hcwa_html = hcwa_louver_html( array( 'id' => 'donate', 'title' => 'Ủng hộ', 'text' => 'Mỗi khoản giúp.' ), 2 );
hcwa_assert_contains( '<li class="louver louver--donate" data-way="donate">', $hcwa_html, 'louver: the donate row' );
hcwa_assert_contains( '<h2 class="louver__title"><span class="louver__label">', $hcwa_html, 'louver: the donate title is not a link; heading level from the page' );
hcwa_assert_contains( 'btn btn--primary louver__btn', $hcwa_html, 'louver: the one sun button' );
hcwa_assert_false( str_contains( $hcwa_html, 'louver__go' ), 'louver: no arrow on the donate row' );

// Contact lines (2.1 rule 12): only lines that may print; data-draft while unconfirmed.
update_option( 'hcwa_settings', array( 'phone' => '028 3930 0000', 'emails' => 'info@hcwa.test' ) );
$hcwa_html = hcwa_contact_lines_html( array( 'phone', 'email', 'sponsorship' ), 'dusk__lines' );
hcwa_assert_same( 0, strpos( $hcwa_html, '<dl class="dusk__lines" data-draft>' ), 'contact lines: outlined while unconfirmed' );
hcwa_assert_contains( 'href="tel:02839300000"', $hcwa_html, 'contact lines: tel link' );
hcwa_assert_contains( 'href="mailto:info@hcwa.test"', $hcwa_html, 'contact lines: mailto link' );
hcwa_assert_same( 2, substr_count( $hcwa_html, '<dt>' ), 'contact lines: no sponsorship line without a second email' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_contact_lines_html( array( 'phone', 'email' ), 'dusk__lines' ), 'contact lines: nothing on production until confirmed' );
update_option( 'hcwa_settings', array( 'phone' => '028 3930 0000', 'emails' => 'info@hcwa.test', 'contact_confirmed' => '1' ) );
$hcwa_html = hcwa_contact_lines_html( array( 'phone', 'email' ), 'dusk__lines' );
hcwa_assert_same( 0, strpos( $hcwa_html, '<dl class="dusk__lines">' ), 'contact lines: confirmed lines on production, no outline' );
$GLOBALS['hcwa_test']['env'] = 'staging';
update_option( 'hcwa_settings', array() );
hcwa_assert_same( '', hcwa_contact_lines_html( array( 'phone', 'email' ), 'office__lines' ), 'contact lines: none set, no empty list' );

// Lit header (2.5) and sun head: the one H1, breadcrumb, escaped text.
$hcwa_html = hcwa_lit_header_html(
	array(
		'still'       => '',
		'trail'       => array( array( 'Hoạt động', 'https://hcwa.test/hoat-dong/' ), array( 'Tre Xanh' ) ),
		'title'       => 'Cơ sở <Tre Xanh>',
		'intro'       => 'Lời giới thiệu',
		'intro_draft' => true,
		'unit_color'  => 'var(--color-unit-green-bamboo)',
	)
);
hcwa_assert_same( 0, strpos( $hcwa_html, '<header class="lit-head lit-head--unit" style="--unit:var(--color-unit-green-bamboo)">' ), 'lit header: unit colour' );
hcwa_assert_same( 1, substr_count( $hcwa_html, '<h1' ), 'lit header: one H1' );
hcwa_assert_true( str_contains( $hcwa_html, '&lt;Tre' ) && ! str_contains( $hcwa_html, '<Tre' ), 'lit header: title escaped' );
hcwa_assert_contains( '<p class="lit-head__intro" data-draft>Lời giới thiệu</p>', $hcwa_html, 'lit header: draft intro outlined' );
hcwa_assert_contains( '<nav class="breadcrumb"', $hcwa_html, 'lit header: breadcrumb' );
$hcwa_html = hcwa_sun_head_html( array( 'title' => 'Ủng hộ', 'jumps' => array( array( '#tu-nuoc-ngoai', 'Từ nước ngoài' ) ) ) );
hcwa_assert_same( 1, substr_count( $hcwa_html, '<h1 class="sun-head__title">' ), 'sun head: one H1' );
hcwa_assert_contains( '<ul class="sun-head__jump">', $hcwa_html, 'sun head: jump links for printed panels' );
hcwa_assert_false( str_contains( hcwa_sun_head_html( array( 'title' => 'Ủng hộ' ) ), 'sun-head__side' ), 'sun head: no side column when empty' );

// Text helpers used by every builder never print markup from stored text.
hcwa_assert_same( 'Trẻ em &amp; gia đình', esc_html( hcwa_attr_text( 'Trẻ em &amp; gia đình' ) ), 'stored &amp; prints as one &amp;' );
