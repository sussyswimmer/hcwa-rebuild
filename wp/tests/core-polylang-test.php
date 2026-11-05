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

foreach ( array( 'hcwa_since_year', 'hcwa_accent', 'hcwa_logo_id', 'hcwa_gallery_ids', 'hcwa_contact_approved', 'hcwa_phone', 'hcwa_email', 'hcwa_news_tag', 'hcwa_no_identifiable_children', 'hcwa_status', 'hcwa_start_year', 'hcwa_end_year', 'hcwa_partner_ids', 'hcwa_center_id', 'hcwa_value', 'hcwa_suffix', 'hcwa_year', 'hcwa_verified', 'hcwa_verified_by', 'hcwa_verified_date', 'hcwa_order', 'hcwa_scope_type', 'hcwa_scope_id', 'hcwa_report_type', 'hcwa_doc_language', 'hcwa_file_id', 'hcwa_website', 'hcwa_partner_type', 'hcwa_active_years', 'hcwa_show_on_home', 'hcwa_logo_permission', 'hcwa_approved_for_web', 'hcwa_identifiable_child' ) as $key ) {
	hcwa_assert_true( in_array( $key, $sync, true ), "$key is shared" );
}
foreach ( array( 'hcwa_summary', 'hcwa_who_we_serve', 'hcwa_what_we_do', 'hcwa_location', 'hcwa_help_text', 'hcwa_contact_name', 'hcwa_goals', 'hcwa_activities', 'hcwa_source_note' ) as $key ) {
	hcwa_assert_true( in_array( $key, $per_lang, true ), "$key is per language" );
}
hcwa_assert_same( array(), array_values( array_intersect( $sync, $per_lang ) ), 'no key is both shared and per language' );

$copied = hcwa_pll_copy_post_metas( array( 'hcwa_summary', 'other_plugin_key', '_thumbnail_id' ), true, 1, 2, 'en' );
hcwa_assert_false( in_array( 'hcwa_summary', $copied, true ), 'per-language text is never copied' );
hcwa_assert_true( in_array( 'other_plugin_key', $copied, true ), 'other keys untouched' );
hcwa_assert_true( in_array( '_thumbnail_id', $copied, true ), 'featured image still synced' );
hcwa_assert_true( in_array( 'hcwa_since_year', $copied, true ), 'shared keys added' );

// Field sanitizing.
$fields = hcwa_meta_fields();
hcwa_assert_same( 2020, hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_since_year'], '2020' ), 'year kept' );
hcwa_assert_same( 0, hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_since_year'], '20' ), 'implausible year dropped' );
hcwa_assert_same( 'blue', hcwa_sanitize_meta_value( $fields['hcwa_center']['hcwa_accent'], 'purple' ), 'unknown accent falls back to the default' );
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
