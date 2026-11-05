<?php
/**
 * hcwa_t / hcwa_t_in: dictionary, {var} substitution, Polylang fallback, missing keys.
 *
 * @package hcwa-core
 */

$strings = hcwa_strings();

hcwa_assert_true( count( $strings ) > 100, 'dictionary loaded from strings-data.php' );
hcwa_assert_false( isset( $strings['_note'] ), '_note dropped' );

$complete = true;
foreach ( $strings as $key => $entry ) {
	if ( '' === trim( (string) ( $entry['vi'] ?? '' ) ) || '' === trim( (string) ( $entry['en'] ?? '' ) ) ) {
		$complete = false;
		hcwa_test_record( false, "string $key is missing a language" );
	}
}
hcwa_assert_true( $complete, 'every string has vi and en' );

// strings-data.php matches ui.json (regenerate with scripts/build-wp-strings.py).
$ui = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/content/draft/ui.json' ), true );
unset( $ui['_note'] );
$generated = require dirname( __DIR__ ) . '/plugins/hcwa-core/includes/strings-data.php';
hcwa_assert_same( array_keys( $ui ), array_keys( $generated ), 'strings-data.php has the ui.json keys in order' );
hcwa_assert_same( $ui['submenu'], $generated['submenu'], 'strings-data.php text equals ui.json' );

// Current language (vi without Polylang) and explicit languages.
hcwa_assert_same( 'Ủng hộ', hcwa_t( 'donate' ), 'hcwa_t uses vi by default' );
hcwa_assert_same( 'Donate', hcwa_t_in( 'donate', 'en' ), 'hcwa_t_in en' );
hcwa_assert_same( 'Mở mục con: Hoạt động', hcwa_t( 'submenu', array( 'x' => 'Hoạt động' ) ), '{x} replaced in vi' );
hcwa_assert_same( 'Show Our Work pages', hcwa_t_in( 'submenu', 'en', array( 'x' => 'Our Work' ) ), '{x} replaced in en' );
hcwa_assert_same( 'Show {x} pages', hcwa_t_in( 'submenu', 'en' ), 'placeholder kept without vars' );
hcwa_assert_same( 'a {b} c', hcwa_t_vars( 'a {b} c', array( 'x' => 1 ) ), 'unknown placeholder kept' );
hcwa_assert_same( 'Ngân hàng', hcwa_t_in( 'pay_bank', 'vi' ), 'core extra keys are in the dictionary' );

// Polylang fallback, through an injected translator.
$entry      = array( 'vi' => 'Đọc thêm', 'en' => 'Read more' );
$unchanged  = static fn( $source, $lang ) => $source;
$saved_en   = static fn( $source, $lang ) => 'en' === $lang ? 'Keep reading' : $source;
$edited_vi  = static fn( $source, $lang ) => 'vi' === $lang ? 'Xem thêm' : $source;
$no_polylang = static fn( $source, $lang ) => null;

hcwa_assert_same( 'Read more', hcwa_translate_entry( $entry, 'en', $unchanged ), 'en falls back to our text when Polylang returns the vi source' );
hcwa_assert_same( 'Keep reading', hcwa_translate_entry( $entry, 'en', $saved_en ), 'saved en translation wins' );
hcwa_assert_same( 'Xem thêm', hcwa_translate_entry( $entry, 'vi', $edited_vi ), 'edited vi wording wins' );
hcwa_assert_same( 'Đọc thêm', hcwa_translate_entry( $entry, 'vi', $unchanged ), 'vi source when nothing saved' );
hcwa_assert_same( 'Read more', hcwa_translate_entry( $entry, 'en', $no_polylang ), 'our en without Polylang' );
hcwa_assert_same( 'Menu', hcwa_translate_entry( array( 'vi' => 'Menu', 'en' => 'Menu' ), 'en', $unchanged ), 'identical vi/en text is fine' );

// Missing keys: the key on staging, nothing on production, logged once.
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( 'noSuchKey', hcwa_t( 'noSuchKey' ), 'missing key shows the key on staging' );
hcwa_t( 'noSuchKey' );
$logged = array_filter( $GLOBALS['hcwa_test']['fired'], static fn( $call ) => 'hcwa_log' === $call[0] && false !== strpos( $call[1][0], 'noSuchKey' ) );
hcwa_assert_same( 1, count( $logged ), 'missing key logged once' );

$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_t( 'otherMissingKey' ), 'missing key renders nothing on production' );

// Rule 6: Vietnamese is never set in capitals.
$caps = array_filter( $strings, static fn( $entry ) => preg_match( '/\p{Ll}/u', $entry['vi'] ) === 0 && preg_match( '/\p{Lu}{2,}/u', $entry['vi'] ) === 1 && mb_strlen( $entry['vi'] ) > 3 );
hcwa_assert_same( array(), array_keys( $caps ), 'no all-caps Vietnamese strings' );
