<?php
/**
 * Donation details gate (contract gating rule 3) and settings helpers.
 *
 * @package hcwa-core
 */

$details = array(
	'donation_bank_vi'        => 'Ngân hàng A',
	'donation_bank_en'        => 'Bank A',
	'donation_account'        => '0123456789',
	'donation_holder'         => 'HOI BAO TRO TRE EM',
	'donation_note_vi'        => 'UNGHO + số điện thoại',
	'donation_note_en'        => 'UNGHO + phone number',
	'donation_abroad_swift'   => 'ABCDVNVX',
	'donation_paypal_url'     => 'https://paypal.example/hcwa',
	'donation_vietqr_id'      => 55,
	'donation_confirmed_by'   => 'Finance officer',
	'donation_confirmed_date' => '2026-11-01',
);

hcwa_assert_same( null, hcwa_donation_details_from( array(), 'vi' ), 'nothing set: null' );
hcwa_assert_same( null, hcwa_donation_details_from( array_merge( $details, array( 'donation_confirmed_by' => '' ) ), 'vi' ), 'no confirmer: null' );
hcwa_assert_same( null, hcwa_donation_details_from( array_merge( $details, array( 'donation_confirmed_date' => '' ) ), 'vi' ), 'no date: null' );
hcwa_assert_same( null, hcwa_donation_details_from( array_merge( $details, array( 'donation_confirmed_by' => '   ' ) ), 'vi' ), 'blank confirmer: null' );

$vi = hcwa_donation_details_from( $details, 'vi' );
hcwa_assert_true( is_array( $vi ), 'confirmed: details returned' );
hcwa_assert_same( array( 'bank', 'account', 'holder', 'note' ), array_column( $vi['in_vietnam'], 'id' ), 'in-Vietnam rows in prototype order' );
hcwa_assert_same( 'Ngân hàng A', $vi['in_vietnam'][0]['value'], 'vi bank name' );
hcwa_assert_same( 'Số tài khoản', $vi['in_vietnam'][1]['label'], 'vi label from the dictionary' );
hcwa_assert_true( $vi['in_vietnam'][1]['copy'], 'account number has a copy button' );
hcwa_assert_false( $vi['in_vietnam'][2]['copy'], 'holder has no copy button' );
hcwa_assert_same( 'UNGHO + số điện thoại', $vi['in_vietnam'][3]['value'], 'vi transfer note' );
hcwa_assert_true( '' !== $vi['in_vietnam'][3]['hint'], 'transfer note has a hint' );
hcwa_assert_same( array( 'swift' ), array_column( $vi['abroad'], 'id' ), 'empty abroad rows left out' );
hcwa_assert_same( 55, $vi['vietqr_id'], 'VietQR attachment ID' );
hcwa_assert_same( 'https://paypal.example/hcwa', $vi['paypal_url'], 'PayPal URL' );

$en = hcwa_donation_details_from( $details, 'en' );
hcwa_assert_same( 'Bank A', $en['in_vietnam'][0]['value'], 'en bank name' );
hcwa_assert_same( 'Account number', $en['in_vietnam'][1]['label'], 'en label' );
hcwa_assert_same( 'UNGHO + phone number', $en['in_vietnam'][3]['value'], 'en transfer note' );

$GLOBALS['hcwa_test']['options']['hcwa_settings'] = $details;
hcwa_assert_same( 'Ngân hàng A', hcwa_donation_details()['in_vietnam'][0]['value'], 'hcwa_donation_details reads the option' );

// Changing a detail without re-confirming clears the confirmation.
$changed = hcwa_guard_donation_confirmation( $details, array_merge( $details, array( 'donation_account' => '9999' ) ) );
hcwa_assert_same( '', $changed['donation_confirmed_by'], 'changed account clears "confirmed by"' );
hcwa_assert_same( '', $changed['donation_confirmed_date'], 'changed account clears the date' );
hcwa_assert_same( null, hcwa_donation_details_from( $changed, 'vi' ), 'and the details stop rendering' );

$reconfirmed = hcwa_guard_donation_confirmation( $details, array_merge( $details, array( 'donation_account' => '9999', 'donation_confirmed_date' => '2026-11-05' ) ) );
hcwa_assert_same( 'Finance officer', $reconfirmed['donation_confirmed_by'], 'new date with the change keeps the confirmation' );

$same = hcwa_guard_donation_confirmation( $details, $details );
hcwa_assert_same( '2026-11-01', $same['donation_confirmed_date'], 'unchanged details keep the confirmation' );

// Settings helpers.
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array(
	'announcement_text_vi' => 'Hội chợ Tết',
	'announcement_text_en' => '',
	'emails'               => "hcwa.vn@gmail.com\nnot-an-email\nsponsorshiprelations@hcwa.org.vn",
);
hcwa_assert_same( 'Hội chợ Tết', hcwa_setting_l( 'announcement_text', 'vi' ), 'per-language setting' );
hcwa_assert_same( '', hcwa_setting_l( 'announcement_text', 'en' ), 'no fallback to the other language' );
hcwa_assert_same( 'fallback', hcwa_setting( 'announcement_text_en', 'fallback' ), 'empty value returns the default' );
hcwa_assert_same( array( 'hcwa.vn@gmail.com', 'sponsorshiprelations@hcwa.org.vn' ), hcwa_contact_emails(), 'valid emails only' );

hcwa_assert_same( '+842838401406', hcwa_sanitize_setting_value( 'tel', '+84 28 3840 1406' ), 'tel keeps + and digits' );
hcwa_assert_same( 'https://zalo.me/0901234567', hcwa_sanitize_setting_value( 'zalo', '090 123 4567' ), 'zalo phone becomes a zalo.me link' );
hcwa_assert_same( '', hcwa_sanitize_setting_value( 'date', '2026-02-30' ), 'invalid date dropped' );
hcwa_assert_same( '2026-02-08', hcwa_sanitize_setting_value( 'date', '2026-02-08' ), 'valid date kept' );
hcwa_assert_true( hcwa_sanitize_setting_value( 'checkbox', '1' ), 'checkbox on' );
hcwa_assert_false( hcwa_sanitize_setting_value( 'checkbox', '0' ), 'checkbox off' );
hcwa_assert_same( '', hcwa_sanitize_setting_value( 'url', 'javascript:alert(1)' ), 'javascript URL dropped' );

// Announcement bar.
$bar = array(
	'announcement_enabled' => true,
	'announcement_text_vi' => 'Hội chợ Tết: gửi đồ trước 15/01',
	'announcement_text_en' => '',
	'announcement_link_vi' => 'https://hcwa.test/su-kien/',
);
hcwa_assert_same( array( 'text' => 'Hội chợ Tết: gửi đồ trước 15/01', 'url' => 'https://hcwa.test/su-kien/' ), hcwa_announcement_from( $bar, 'vi', true ), 'enabled bar with text' );
hcwa_assert_same( null, hcwa_announcement_from( $bar, 'en', false ), 'no English text: no bar on English pages' );
hcwa_assert_same( null, hcwa_announcement_from( array_merge( $bar, array( 'announcement_enabled' => false ) ), 'vi', false ), 'switched off' );
$draft_bar = array_merge( $bar, array( 'announcement_text_vi' => '[DRAFT: Tết 2027]' ) );
hcwa_assert_same( null, hcwa_announcement_from( $draft_bar, 'vi', true ), 'draft text hidden on production' );
hcwa_assert_same( '[DRAFT: Tết 2027]', hcwa_announcement_from( $draft_bar, 'vi', false )['text'], 'draft text shown on staging' );
