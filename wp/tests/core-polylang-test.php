<?php
/**
 * Polylang filters and the field table (no Polylang loaded: filters called directly).
 *
 * @package hcwa-core
 */

$types = hcwa_pll_post_types( array( 'post' => 'post', 'page' => 'page' ), false );
foreach ( hcwa_post_types() as $post_type ) {
	hcwa_assert_true( isset( $types[ $post_type ] ), "$post_type translated" );
}

$settings_list = hcwa_pll_post_types( array( 'hcwa_center' => 'hcwa_center', 'book' => 'book' ), true );
hcwa_assert_same( array( 'book' => 'book' ), $settings_list, 'our types are hidden from the Polylang settings list' );

hcwa_assert_true( isset( hcwa_pll_taxonomies( array(), false )['hcwa_focus_area'] ), 'focus area translated' );
hcwa_assert_same( array(), hcwa_pll_taxonomies( array( 'hcwa_focus_area' => 'hcwa_focus_area' ), true ), 'focus area hidden from settings list' );

// Meta sync lists follow the contract.
$sync     = hcwa_meta_sync_keys();
$per_lang = hcwa_meta_per_language_keys();

foreach ( array( 'hcwa_since_year', 'hcwa_accent', 'hcwa_logo_id', 'hcwa_gallery_ids', 'hcwa_contact_approved', 'hcwa_phone', 'hcwa_email', 'hcwa_news_tag', 'hcwa_status', 'hcwa_start_year', 'hcwa_end_year', 'hcwa_partner_ids', 'hcwa_center_id', 'hcwa_value', 'hcwa_suffix', 'hcwa_year', 'hcwa_verified', 'hcwa_verified_by', 'hcwa_verified_date', 'hcwa_order', 'hcwa_scope_type', 'hcwa_scope_id', 'hcwa_report_type', 'hcwa_doc_language', 'hcwa_file_id', 'hcwa_website', 'hcwa_partner_type', 'hcwa_active_years', 'hcwa_show_on_home', 'hcwa_logo_permission', 'hcwa_approved_for_web' ) as $key ) {
	hcwa_assert_true( in_array( $key, $sync, true ), "$key is shared" );
}
foreach ( array( 'hcwa_summary', 'hcwa_who_we_serve', 'hcwa_what_we_do', 'hcwa_location', 'hcwa_help_text', 'hcwa_contact_name', 'hcwa_goals', 'hcwa_activities', 'hcwa_source_note' ) as $key ) {
	hcwa_assert_true( in_array( $key, $per_lang, true ), "$key is per language" );
}
hcwa_assert_same( array(), array_values( array_intersect( $sync, $per_lang ) ), 'no key is both shared and per language' );

// Protective flags are never copied between translations (linking or saving one language
// version could otherwise clear them); they count when any version has them.
foreach ( array( 'hcwa_no_identifiable_children', 'hcwa_identifiable_child' ) as $key ) {
	hcwa_assert_false( in_array( $key, $sync, true ), "$key is not shared" );
	hcwa_assert_true( in_array( $key, $per_lang, true ), "$key is never copied" );
	hcwa_assert_false( in_array( $key, hcwa_pll_copy_post_metas( array( $key ), true, 1, 2, 'en' ), true ), "$key is removed from Polylang's copy list" );
}

// Copy into a new translation ($sync false): other plugins' keys stay as Polylang chose them.
$copied = hcwa_pll_copy_post_metas( array( 'hcwa_summary', 'other_plugin_key', '_thumbnail_id', 'footnotes' ), false, 1, 2, 'en' );
hcwa_assert_false( in_array( 'hcwa_summary', $copied, true ), 'copy: per-language text is never copied' );
hcwa_assert_true( in_array( 'other_plugin_key', $copied, true ), 'copy: other plugins\' keys untouched' );
hcwa_assert_false( in_array( 'footnotes', $copied, true ), 'copy: core footnotes (per-language text) are never copied' );
hcwa_assert_true( in_array( 'hcwa_since_year', $copied, true ), 'copy: shared keys added' );

// Synchronizing ($sync true), as when Polylang's "Custom fields" sync is on: only our
// shared keys and protected keys Polylang or a plugin added on purpose survive.
$synced = hcwa_pll_copy_post_metas( array( 'hcwa_summary', 'other_plugin_key', 'footnotes', '_thumbnail_id', '_wp_page_template', '_wp_attachment_metadata', '_yoast_wpseo_primary_category' ), true, 1, 2, 'en' );
hcwa_assert_false( in_array( 'hcwa_summary', $synced, true ), 'sync: per-language text is never synced' );
hcwa_assert_false( in_array( 'other_plugin_key', $synced, true ), 'sync: public keys from "Custom fields" sync are dropped' );
hcwa_assert_false( in_array( 'footnotes', $synced, true ), 'sync: core footnotes are dropped' );
foreach ( array( '_thumbnail_id', '_wp_page_template', '_wp_attachment_metadata', '_yoast_wpseo_primary_category' ) as $key ) {
	hcwa_assert_true( in_array( $key, $synced, true ), "sync: protected $key kept" );
}
hcwa_assert_true( in_array( 'hcwa_since_year', $synced, true ), 'sync: shared keys added' );
hcwa_assert_true( in_array( 'hcwa_approved_for_web', $synced, true ), 'sync: attachment approval shared' );
hcwa_assert_false( in_array( 'hcwa_no_identifiable_children', $synced, true ), 'sync: protective flag never synced' );
hcwa_assert_same( count( $synced ), count( array_unique( $synced ) ), 'sync: no duplicate keys' );

// Empty translated term archives keep their hreflang and switch link (07-BILINGUAL).
hcwa_assert_false( hcwa_pll_show_empty_term_archive( true, 'en', array( 'taxonomy' => 'category' ) ), 'empty translated category archive is linked' );
hcwa_assert_true( hcwa_pll_show_empty_term_archive( true, 'en', array( 'post_type' => 'hcwa_report' ) ), 'empty post type archive keeps Polylang\'s rule' );
hcwa_assert_true( hcwa_pll_show_empty_term_archive( true, 'en', array( 'year' => '2026' ) ), 'empty date archive keeps Polylang\'s rule' );
hcwa_assert_false( hcwa_pll_show_empty_term_archive( false, 'en', array( 'year' => '2026' ) ), 'non-empty archive stays linked' );
$archive_hook = array_values( array_filter( $GLOBALS['hcwa_test']['hooks']['pll_hide_archive_translation_url'] ?? array(), static fn( $h ) => 'hcwa_pll_show_empty_term_archive' === $h[0] ) );
hcwa_assert_true( 1 === count( $archive_hook ) && 3 === $archive_hook[0][2], 'archive filter registered with its three arguments' );

// Field sanitizing.
$fields = hcwa_meta_fields();
hcwa_assert_same( 2020, hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_since_year'], '2020' ), 'year kept' );
hcwa_assert_same( 0, hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_since_year'], '20' ), 'implausible year dropped' );
hcwa_assert_same( '', hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_accent'], 'purple' ), 'unknown accent falls back to the default (no unit)' );
hcwa_assert_same( '', hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_accent'], 'blue' ), 'blue is no longer a unit (11-WP-PORT.md 3.2)' );
hcwa_assert_same( 'little-rose', hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_accent'], 'little-rose' ), 'known accent kept' );
hcwa_assert_same( "Một\nHai", hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_what_we_do'], "Một\r\n\r\n  Hai  \n" ), 'lines trimmed, blanks dropped' );
hcwa_assert_same( 1.5, hcwa_sanitize_meta_value( $fields['hcwa_impact']['hcwa_value'], '1.5' ), 'number' );
hcwa_assert_same( '', hcwa_sanitize_meta_value( $fields['hcwa_impact']['hcwa_value'], 'many' ), 'non-number dropped' );
hcwa_assert_same( '', hcwa_sanitize_meta_value( $fields['hcwa_impact']['hcwa_verified_date'], '08/02/2026' ), 'date must be Y-m-d' );
hcwa_assert_true( hcwa_sanitize_meta_value( $fields['hcwa_impact']['hcwa_verified'], '1' ), 'checkbox on' );
hcwa_assert_same( array( 3, 5 ), hcwa_id_list( '3, 5,0,3' ), 'ID list' );

// Every select default is one of its options (REST validates defaults against the enum).
foreach ( $fields as $post_type => $list ) {
	foreach ( $list as $key => $spec ) {
		if ( 'select' === $spec['input'] && ! array_key_exists( $spec['default'], $spec['options'] ) ) {
			hcwa_test_record( false, "$post_type.$key default is not an option" );
		}
	}
}
hcwa_assert_true( true, 'select defaults are valid options' );

// Translations and protective flags.
hcwa_assert_same( array( 50 ), hcwa_post_translation_ids( 50 ), 'no translations: the post itself' );
$GLOBALS['hcwa_test']['translations'][50] = array( 'vi' => 50, 'en' => 51 );
$GLOBALS['hcwa_test']['translations'][51] = array( 'vi' => 50, 'en' => 51 );
hcwa_assert_same( array( 50, 51 ), hcwa_post_translation_ids( 50 ), 'post and its translations' );
hcwa_test_post( array( 'ID' => 50, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 51, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '' ) );
hcwa_assert_true( hcwa_meta_flag_any( 51, 'hcwa_no_identifiable_children' ), 'English center unticked, Vietnamese ticked: still protected' );
hcwa_assert_true( hcwa_image_context_no_identifiable( 51 ), 'and the English center is a no-identifiable context' );

// A translation takes the original's shared fields before Polylang copies them back
// (linking a separately created English center must not wipe the Vietnamese one).
$vi = hcwa_test_post( array( 'ID' => 60, 'post_type' => 'hcwa_center' ), array( 'hcwa_phone' => '028 1234', 'hcwa_gallery_ids' => array( 7, 8 ), 'hcwa_no_identifiable_children' => '1' ) );
$en = hcwa_test_post( array( 'ID' => 61, 'post_type' => 'hcwa_center' ), array( 'hcwa_phone' => '', 'hcwa_since_year' => 0, 'hcwa_summary' => 'English summary' ) );
hcwa_pll_seed_shared_meta( 61, $en, array( 'vi' => 60, 'en' => 61 ) );
hcwa_assert_same( '028 1234', get_post_meta( 61, 'hcwa_phone', true ), 'shared phone taken from the original' );
hcwa_assert_same( array( 7, 8 ), get_post_meta( 61, 'hcwa_gallery_ids', true ), 'shared gallery taken from the original' );
hcwa_assert_false( metadata_exists( 'post', 61, 'hcwa_since_year' ), 'a shared value the original lacks is removed, not copied back over it' );
hcwa_assert_same( 'English summary', get_post_meta( 61, 'hcwa_summary', true ), 'per-language text untouched' );
hcwa_assert_false( metadata_exists( 'post', 61, 'hcwa_no_identifiable_children' ), 'protective flag is not copied' );
hcwa_pll_seed_shared_meta( 60, $vi, array( 'vi' => 60, 'en' => 61 ) );
hcwa_assert_same( '028 1234', get_post_meta( 60, 'hcwa_phone', true ), 'saving the original changes nothing on it' );

// Our keys are protected, so the core Custom Fields panel cannot edit or delete them.
hcwa_assert_true( hcwa_protect_meta( false, 'hcwa_no_identifiable_children', 'post' ), 'hcwa_ post meta is protected' );
hcwa_assert_false( hcwa_protect_meta( false, 'hcwa_phone', '' ), 'only for post meta (Polylang asks without a type)' );
hcwa_assert_false( hcwa_protect_meta( false, 'other_key', 'post' ), 'other keys keep their state' );
hcwa_assert_true( hcwa_protect_meta( true, 'other_key', 'post' ), 'already protected stays protected' );

// A center ticked "No identifiable children" without a news tag is flagged for staff.
hcwa_assert_true( hcwa_center_news_unprotected( 50 ), 'protected center without a news tag' );
$GLOBALS['hcwa_test']['post_meta'][50]['hcwa_news_tag'] = 'hoa-hong-nho';
hcwa_assert_false( hcwa_center_news_unprotected( 50 ), 'with a news tag it is fine' );
