<?php
/**
 * hcwa_number, hcwa_date, hcwa_strip_leading_emoji, language helpers.
 *
 * @package hcwa-core
 */

// Numbers: deterministic per language, independent of the server locale.
hcwa_assert_same( '300.000', hcwa_number_in( 300000, 'vi' ), 'vi thousands separator is a dot' );
hcwa_assert_same( '300,000', hcwa_number_in( 300000, 'en' ), 'en thousands separator is a comma' );
hcwa_assert_same( '1.234,5', hcwa_number_in( 1234.5, 'vi', 1 ), 'vi decimal comma' );
hcwa_assert_same( '1,234.50', hcwa_number_in( 1234.5, 'en', 2 ), 'en decimal point' );
hcwa_assert_same( '12', hcwa_number_in( 12, 'vi' ), 'small number unchanged' );
hcwa_assert_same( '-1.500', hcwa_number_in( -1500, 'vi' ), 'negative vi' );
hcwa_assert_same( '1,000,000', hcwa_number_in( 1000000, 'en_GB' ), 'locale-like language code maps to en' );
hcwa_assert_same( '300.000', hcwa_number( 300000 ), 'current language is vi without Polylang' );

$old_locale = setlocale( LC_NUMERIC, '0' );
setlocale( LC_NUMERIC, 'de_DE.UTF-8', 'de_DE', 'fr_FR.UTF-8' );
hcwa_assert_same( '1,234.50', hcwa_number_in( 1234.5, 'en', 2 ), 'server locale does not change en output' );
setlocale( LC_NUMERIC, $old_locale );

// Dates.
hcwa_assert_same( '08/02/2026', hcwa_date_in( '2026-02-08', 'vi' ), 'vi date dd/mm/yyyy' );
hcwa_assert_same( '8 Feb 2026', hcwa_date_in( '2026-02-08', 'en' ), 'en date d Mon yyyy' );
hcwa_assert_same( '31 Dec 2025', hcwa_date_in( '2025-12-31 23:59:00', 'en' ), 'MySQL datetime keeps the date part' );
hcwa_assert_same( '01/01/2026', hcwa_date_in( '2026-01-01', 'vi' ), 'leading zeros in vi' );
hcwa_assert_same( 'not a date', hcwa_date_in( 'not a date', 'vi' ), 'invalid input returned unchanged' );
hcwa_assert_same( '2026-02-30', hcwa_date_in( '2026-02-30', 'en' ), 'impossible date returned unchanged' );
hcwa_assert_same( '08/02/2026', hcwa_date( '2026-02-08' ), 'hcwa_date uses the current language (vi)' );

// Language helpers.
hcwa_assert_same( 'vi', hcwa_lang(), 'vi without Polylang' );
hcwa_assert_same( 'en', hcwa_normalize_lang( 'EN' ), 'normalize EN' );
hcwa_assert_same( 'vi', hcwa_normalize_lang( false ), 'normalize false' );
hcwa_assert_same( 'vi', hcwa_other_lang( 'en' ), 'other of en' );
hcwa_assert_same( 'Tiếng Việt', hcwa_language_name( 'vi' ), 'vi endonym' );
hcwa_assert_same( 'English', hcwa_language_name( 'en' ), 'en endonym' );

// Leading emoji.
hcwa_assert_same( 'Tết 2027', hcwa_strip_leading_emoji( '🎉 Tết 2027' ), 'strips emoji and space' );
hcwa_assert_same( 'Hội chợ', hcwa_strip_leading_emoji( '🎉🎈  Hội chợ' ), 'strips several emoji' );
hcwa_assert_same( 'Cảm ơn', hcwa_strip_leading_emoji( '❤️ Cảm ơn' ), 'strips emoji with variation selector' );
hcwa_assert_same( 'Gia đình', hcwa_strip_leading_emoji( '👨‍👩‍👧 Gia đình' ), 'strips ZWJ sequence' );
hcwa_assert_same( 'Việt Nam', hcwa_strip_leading_emoji( '🇻🇳 Việt Nam' ), 'strips flag' );
hcwa_assert_same( 'Ba bước', hcwa_strip_leading_emoji( '3️⃣ Ba bước' ), 'strips keycap' );
hcwa_assert_same( '2026: một năm nhìn lại', hcwa_strip_leading_emoji( '2026: một năm nhìn lại' ), 'keeps a leading number' );
hcwa_assert_same( 'Lớp học 🎒 mới', hcwa_strip_leading_emoji( 'Lớp học 🎒 mới' ), 'keeps emoji later in the title' );
hcwa_assert_same( 'Ông Đỗ', hcwa_strip_leading_emoji( 'Ông Đỗ' ), 'keeps Vietnamese capitals' );
hcwa_assert_same( '', hcwa_strip_leading_emoji( '🎉' ), 'emoji-only title becomes empty' );
