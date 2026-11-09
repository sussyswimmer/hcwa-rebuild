<?php
/**
 * hcwa-2026 theme: pure helpers, string coverage of every template, theme.json sanity.
 * Loads only the theme's helper files (no templates, no WordPress).
 *
 * @package hcwa-2026
 */

$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';

// Placeholders.
hcwa_assert_same( 'Ủng hộ Tre Xanh', hcwa_theme_fill( 'Ủng hộ {name}', array( 'name' => 'Tre Xanh' ) ), 'fill replaces {name}' );
hcwa_assert_same( 'Kết quả tại {name}', hcwa_theme_fill( 'Kết quả tại {name}', array() ), 'fill leaves unknown placeholders' );

// Emoji: the theme's own copy (used without hcwa-core) and the core-backed wrapper agree.
hcwa_assert_same( 'Tin vui', hcwa_theme_strip_leading_emoji( '🎉 Tin vui' ), 'leading emoji removed' );
hcwa_assert_same( 'Hội Bảo trợ Trẻ em', hcwa_theme_strip_leading_emoji( 'Hội Bảo trợ Trẻ em' ), 'Vietnamese letters untouched' );
hcwa_assert_same( 'Tết 🎉 vui', hcwa_theme_strip_leading_emoji( '✨🎉 Tết 🎉 vui' ), 'only leading emoji removed' );
hcwa_assert_same( 'Tin vui', hcwa_theme_clean_title( '🎉 Tin vui' ), 'clean title through core' );

// One item per line meta.
hcwa_assert_same( array( 'Lớp học', 'Câu lạc bộ', 'Tư vấn' ), hcwa_theme_split_lines( "Lớp học\n\n- Câu lạc bộ\r\n• Tư vấn  " ), 'lines trimmed, markers and blanks dropped' );
hcwa_assert_same( array(), hcwa_theme_split_lines( '' ), 'empty text gives no lines' );

// ID lists.
hcwa_assert_same( array( 3, 4 ), hcwa_theme_parse_ids( array( 3, '4', 0, -1, 3 ) ), 'ids: ints, positive, unique' );
hcwa_assert_same( array( 5, 6, 7 ), hcwa_theme_parse_ids( '5, 6 7' ), 'ids from a string' );
hcwa_assert_same( array(), hcwa_theme_parse_ids( null ), 'no ids' );

// Attachments a loop prints, collected once before it runs (priming).
hcwa_test_post( array( 'ID' => 930, 'post_type' => 'hcwa_report' ), array( '_thumbnail_id' => 41, 'hcwa_file_id' => 42 ) );
hcwa_test_post( array( 'ID' => 931, 'post_type' => 'hcwa_report' ), array( '_thumbnail_id' => '41' ) );
hcwa_assert_same( array( 41 ), hcwa_theme_attachment_ids( array( get_post( 930 ), 931, 'x', 0 ) ), 'attachment ids: featured images from posts or IDs, each once' );
hcwa_assert_same( array( 41, 42 ), hcwa_theme_attachment_ids( array( 930, 931 ), array( '_thumbnail_id', 'hcwa_file_id' ) ), 'attachment ids: covers and files' );
hcwa_assert_same( array(), hcwa_theme_attachment_ids( array() ), 'attachment ids: none' );

// Program years.
hcwa_assert_same( '2023–2026', hcwa_theme_year_range( 2023, 2026 ), 'start–end' );
hcwa_assert_same( '2023–', hcwa_theme_year_range( 2023, 0 ), 'open-ended' );
hcwa_assert_same( '2024', hcwa_theme_year_range( 2024, 2024 ), 'single year' );
hcwa_assert_same( '', hcwa_theme_year_range( 0, 0 ), 'no years' );

// Current-section matching for the menu.
hcwa_assert_true( hcwa_theme_is_section_path( '/hoat-dong/', '/hoat-dong/co-so/tre-xanh/' ), 'center is inside Our Work (vi)' );
hcwa_assert_true( hcwa_theme_is_section_path( '/en/our-work/', '/en/our-work/programs/thrive-together/' ), 'program is inside Our Work (en)' );
hcwa_assert_false( hcwa_theme_is_section_path( '/', '/lien-he/' ), 'vi home is not a section of everything' );
hcwa_assert_false( hcwa_theme_is_section_path( '/en/', '/en/contact/' ), 'en home is not a section of everything' );
hcwa_assert_false( hcwa_theme_is_section_path( '/tin-tuc/', '/tin-tuc-cu/' ), 'prefix must end at a slash' );
hcwa_assert_false( hcwa_theme_is_section_path( '/chung-tay/', '/chung-tay/' ), 'the page itself is current, not a section' );

// Strings: theme keys reach the page in both languages, core keys are not replaced.
hcwa_assert_same( 'Chia sẻ', hcwa_theme_t( 'share' ), 'theme key in vi' );
hcwa_assert_same( 'Share', hcwa_theme_t_in( 'share', 'en' ), 'theme key in en' );
hcwa_assert_same( 'Ủng hộ', hcwa_theme_t( 'donate' ), 'core key in vi' );
hcwa_assert_same( 'Green Bamboo in numbers', hcwa_theme_t_in( 'centerFigures', 'en', array( 'name' => 'Green Bamboo' ) ), 'theme key with a placeholder' );
hcwa_assert_same( 'missingKey', hcwa_theme_t( 'missingKey' ), 'unknown key shows itself on staging' );

$hcwa_core_keys  = function_exists( 'hcwa_strings' ) ? hcwa_strings() : array();
$hcwa_theme_keys = hcwa_theme_strings();
hcwa_assert_same( array(), array_keys( array_intersect_key( $hcwa_theme_keys, $hcwa_core_keys ) ), 'theme keys do not clash with core keys' );
$hcwa_merged = hcwa_theme_add_strings( array( 'skip' => array( 'vi' => 'X', 'en' => 'Y' ) ) );
hcwa_assert_same( 'X', $hcwa_merged['skip']['vi'], 'hcwa_strings filter keeps existing keys' );
hcwa_assert_true( isset( $hcwa_merged['share'] ), 'hcwa_strings filter adds theme keys' );

foreach ( $hcwa_theme_keys as $hcwa_key => $hcwa_entry ) {
	$hcwa_ok = '' !== trim( $hcwa_entry['vi'] ?? '' ) && '' !== trim( $hcwa_entry['en'] ?? '' );
	hcwa_assert_true( $hcwa_ok, "string $hcwa_key has vi and en" );
	$hcwa_vi = $hcwa_entry['vi'] ?? '';
	// Vietnamese is never set in capitals: no word of two or more capital letters (VI, EN, HCWA, PDF are names).
	$hcwa_caps = preg_match( '/\b(?!(?:VI|EN|HCWA|PDF|TP)\b)\p{Lu}{2,}\b/u', $hcwa_vi );
	hcwa_assert_same( 0, $hcwa_caps, "string $hcwa_key has no capitalised Vietnamese words" );
	hcwa_assert_false( str_contains( $hcwa_vi . $hcwa_entry['en'], '[DRAFT' ), "string $hcwa_key has no draft marker" );
	hcwa_assert_same( 0, preg_match( '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $hcwa_vi . $hcwa_entry['en'] ), "string $hcwa_key has no emoji" );
}

// Every literal key the templates use exists in core's dictionary or the theme's.
$hcwa_all_keys = $hcwa_core_keys + $hcwa_theme_keys;
$hcwa_used     = array();
$hcwa_iter     = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $hcwa_theme_dir, FilesystemIterator::SKIP_DOTS ) );
foreach ( $hcwa_iter as $hcwa_file ) {
	if ( 'php' !== $hcwa_file->getExtension() || str_contains( $hcwa_file->getPathname(), '/patterns/' ) ) {
		continue;
	}
	preg_match_all( "/hcwa_theme_(?:t|e|t_in)\(\s*'([A-Za-z_]+)'\s*[,)]/", (string) file_get_contents( $hcwa_file->getPathname() ), $hcwa_m );
	foreach ( $hcwa_m[1] as $hcwa_key ) {
		$hcwa_used[ $hcwa_key ] = basename( $hcwa_file->getPathname() );
	}
}
// Keys built at runtime: status pills, report types, category labels, labels in navigation.php and site.js.
foreach ( array( 'status_active', 'status_completed', 'status_planned', 'rt_annual', 'rt_financial', 'rt_program', 'rt_policy', 'cat_news', 'cat_story', 'cat_event', 'cat_announcement', 'type_page', 'type_center', 'type_program', 'type_report', 'goals', 'activities', 'copy', 'copied', 'copyFailed', 'close', 'whoWeAre', 'allWork', 'annualReports', 'ourStory', 'team', 'policies', 'partners', 'volunteer', 'partnerWithUs', 'inKind', 'bakery', 'about', 'impact', 'news', 'getInvolved', 'contact', 'centers', 'programs', 'safeguarding', 'privacy', 'donate' ) as $hcwa_key ) {
	$hcwa_used[ $hcwa_key ] = 'runtime';
}
hcwa_assert_true( count( $hcwa_used ) > 40, 'found the template string keys' );
foreach ( $hcwa_used as $hcwa_key => $hcwa_where ) {
	hcwa_assert_true( isset( $hcwa_all_keys[ $hcwa_key ] ), "key $hcwa_key ($hcwa_where) exists in the dictionary" );
}

// Page URLs come from core, anchors included.
hcwa_assert_same( 'https://hcwa.test/en/get-involved/donate/', hcwa_theme_url( 'donate', 'en' ), 'donate url (en)' );
hcwa_assert_same( 'https://hcwa.test/hoat-dong/#co-so', hcwa_theme_url( 'centers', 'vi' ), 'centers anchor (vi)' );
hcwa_assert_same( 'https://hcwa.test/en/about/policies/#safeguarding', hcwa_theme_url( 'safeguarding', 'en' ), 'safeguarding anchor (en)' );
$hcwa_defaults = hcwa_theme_default_paths();
foreach ( hcwa_page_paths() as $hcwa_key => $hcwa_path ) {
	if ( isset( $hcwa_path['base'] ) ) {
		$hcwa_anchor = hcwa_theme_anchor_routes()[ $hcwa_key ] ?? null;
		hcwa_assert_same( array( $hcwa_path['base'], ltrim( $hcwa_path['vi'], '#' ), ltrim( $hcwa_path['en'], '#' ) ), $hcwa_anchor, "fallback anchor $hcwa_key matches core" );
		continue;
	}
	hcwa_assert_same( array( '/' . $hcwa_path['vi'], '/' . $hcwa_path['en'] ), $hcwa_defaults[ $hcwa_key ] ?? null, "fallback path $hcwa_key matches core" );
}

// Images without an attachment are refused (nothing printed).
hcwa_assert_same( '', hcwa_theme_image( 0, 'large' ), 'no attachment, no image' );

// Language links always have both languages in the same shape.
$hcwa_links = hcwa_theme_language_links();
hcwa_assert_same( array( 'vi', 'en' ), array_keys( $hcwa_links ), 'vi and en links' );
hcwa_assert_same( array( 'url', 'name', 'current', 'has_translation' ), array_keys( $hcwa_links['en'] ), 'link shape' );
hcwa_assert_same( 'English', $hcwa_links['en']['name'], 'en endonym' );

