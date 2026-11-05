<?php
/**
 * Draft marker detection (contract gating rule 4).
 *
 * @package hcwa-core
 */

$drafts = array(
	'[DRAFT: Cùng trẻ em xây dựng tương lai]',
	'Intro <p>[DRAFT, HCWA to write]</p>',
	'[draft: lower case]',
	'[ DRAFT ]',
	'[N]+ trẻ em',
	'từ năm [năm]',
	'since [year]',
	'[số tiền] giúp một em',
	'[amount] provides a scholarship',
	'[năm bắt đầu] – [năm kết thúc]',
	'Ages [age range]',
	'[phường], TP. Hồ Chí Minh',
	'[Answer supplied by HCWA]',
	'[Câu trả lời do HCWA cung cấp]',
	'[HCWA decides whether each center shows its own account.]',
	'Encoded &#91;DRAFT: x&#93;',
);
foreach ( $drafts as $text ) {
	hcwa_assert_true( hcwa_contains_draft( $text ), 'draft: ' . $text );
}

$clean = array(
	'',
	'Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn.',
	'[gallery ids="1,2,3"]',
	'[contact-form-7 id="12" title="HCWA contact"]',
	'See note [1] and [2].',
	'<!-- wp:paragraph --><p>Năm 2025</p><!-- /wp:paragraph -->',
	'Dự án [Cùng Vươn Lên] kết thúc',
	'Drafting a plan',
	'data-draft attribute only',
);
foreach ( $clean as $text ) {
	hcwa_assert_false( hcwa_contains_draft( $text ), 'clean: ' . $text );
}

// Decomposed Vietnamese (NFD) still matches the token.
$nfd = class_exists( 'Normalizer' ) ? Normalizer::normalize( 'từ năm [năm]', Normalizer::FORM_D ) : 'từ năm [năm]';
hcwa_assert_true( hcwa_contains_draft( $nfd ), 'NFD text matches [năm]' );

hcwa_assert_same( array( 'Title', 'Excerpt' ), hcwa_draft_fields( array( 'Title' => '[DRAFT: x]', 'Content' => 'ok', 'Excerpt' => '[N]' ) ), 'field labels with markers' );

// The insert filter only acts on production.
$data = array(
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Liên hệ',
	'post_content' => '[DRAFT: nội dung]',
	'post_excerpt' => '',
);
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( 'publish', hcwa_publish_guard_data( $data, array( 'ID' => 7 ) )['post_status'], 'staging publishes drafts' );

// Cron publishes scheduled posts with wp_publish_post(), which skips wp_insert_post_data:
// the after-insert check reads title, content and excerpt itself.
$GLOBALS['hcwa_test']['env'] = 'production';
$scheduled = hcwa_test_post(
	array(
		'ID'           => 70,
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'Tết 2027',
		'post_content' => '<p>[DRAFT: lịch sự kiện]</p>',
	)
);
hcwa_publish_guard_after( 70, $scheduled, true );
hcwa_assert_same( 'draft', $scheduled->post_status, 'scheduled post with draft text goes back to draft' );
hcwa_assert_same( 'Content', get_post_meta( 70, '_hcwa_publish_blocked', true ), 'and the reason names the field' );

$clean_post = hcwa_test_post(
	array(
		'ID'           => 71,
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'Tết 2027',
		'post_content' => '<p>Lịch sự kiện</p>',
	)
);
hcwa_publish_guard_after( 71, $clean_post, true );
hcwa_assert_same( 'publish', $clean_post->post_status, 'clean scheduled post stays published' );

$GLOBALS['hcwa_test']['env'] = 'staging';
$staging_post = hcwa_test_post( array( 'ID' => 72, 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => '[DRAFT: x]' ) );
hcwa_publish_guard_after( 72, $staging_post, true );
hcwa_assert_same( 'publish', $staging_post->post_status, 'staging is not affected' );
