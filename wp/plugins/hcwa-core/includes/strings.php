<?php
/**
 * Front-end UI strings: the dictionary, Polylang registration and lookup.
 *
 * The dictionary is content/draft/ui.json (generated into strings-data.php) plus the
 * few keys below. Each key is registered with Polylang using the Vietnamese text as the
 * source string, so staff can edit wording in Languages → Translations. English falls
 * back to our dictionary until someone saves an English translation there.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keys the core plugin adds on top of ui.json. Donation labels are copied from
 * content/draft/involve.json (bankFields, abroadFields) so blocks can label rows.
 *
 * @return array<string, array{vi: string, en: string}>
 */
function hcwa_core_extra_strings(): array {
	return array(
		'pay_bank'           => array( 'vi' => 'Ngân hàng', 'en' => 'Bank' ),
		'pay_account'        => array( 'vi' => 'Số tài khoản', 'en' => 'Account number' ),
		'pay_holder'         => array( 'vi' => 'Chủ tài khoản', 'en' => 'Account holder' ),
		'pay_note'           => array( 'vi' => 'Nội dung chuyển khoản', 'en' => 'Transfer note' ),
		'pay_noteHint'       => array( 'vi' => 'Để Hội đối chiếu và gửi biên nhận cho bạn.', 'en' => 'So HCWA can match your gift and send a receipt.' ),
		'pay_swift'          => array( 'vi' => 'Mã SWIFT', 'en' => 'SWIFT code' ),
		'pay_accountAbroad'  => array( 'vi' => 'Số tài khoản (USD/EUR)', 'en' => 'Account number (USD/EUR)' ),
		'pay_paypal'         => array( 'vi' => 'Ủng hộ qua PayPal', 'en' => 'Give through PayPal' ),
		'pay_qr'             => array( 'vi' => 'Mã VietQR để chuyển khoản', 'en' => 'VietQR code for bank transfer' ),
	);
}

/**
 * The full dictionary: key => ['vi' => ..., 'en' => ...].
 *
 * @return array<string, array{vi: string, en: string}>
 */
function hcwa_strings(): array {
	static $data = null;
	static $filtered = null;

	if ( null === $data ) {
		$data = require __DIR__ . '/strings-data.php';
		$data = array_merge( is_array( $data ) ? $data : array(), hcwa_core_extra_strings() );
	}

	if ( null !== $filtered ) {
		return $filtered;
	}

	/**
	 * Lets the theme and blocks add their own keys. Add the filter before 'init'
	 * (functions.php or plugin load) so the keys are registered with Polylang too.
	 *
	 * @param array $strings key => ['vi' => string, 'en' => string].
	 */
	$strings = (array) apply_filters( 'hcwa_strings', $data );

	// Cache only once every plugin and the theme had the chance to add keys.
	if ( did_action( 'init' ) ) {
		$filtered = $strings;
	}

	return $strings;
}

/**
 * Registers every key with Polylang (group "HCWA UI"). Polylang only stores the list
 * on admin requests, so this is cheap elsewhere.
 */
function hcwa_register_strings(): void {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	foreach ( hcwa_strings() as $key => $entry ) {
		if ( ! empty( $entry['vi'] ) ) {
			pll_register_string( $key, $entry['vi'], 'HCWA UI', mb_strlen( $entry['vi'] ) > 70 );
		}
	}
}
add_action( 'init', 'hcwa_register_strings', 20 );

/**
 * Picks the text for one dictionary entry.
 *
 * Polylang returns the Vietnamese source unchanged when no English translation is
 * saved; in that case our English text is used, so the site is bilingual out of the box.
 *
 * Polylang keeps one translation per source string. When several keys share the same
 * Vietnamese text but need different English ('Hoạt động' is "Our Work", "Activities" and
 * "What we do"), a saved English translation would land on all of them, so for those
 * sources ($ambiguous) English always comes from the dictionary.
 *
 * @param array         $entry      ['vi' => string, 'en' => string].
 * @param string        $lang       'vi' or 'en'.
 * @param callable|null $translator fn( string $source, string $lang ): ?string. Defaults to
 *                                  Polylang; null means "no saved translation".
 * @param bool          $ambiguous  The Vietnamese source is shared by keys whose English differs.
 */
function hcwa_translate_entry( array $entry, string $lang, ?callable $translator = null, bool $ambiguous = false ): string {
	$source = (string) ( $entry['vi'] ?? '' );
	$ours   = (string) ( $entry[ $lang ] ?? '' );

	if ( '' === $source ) {
		return $ours;
	}

	if ( $ambiguous && 'vi' !== $lang && '' !== $ours ) {
		return $ours;
	}

	$translator = $translator ?? 'hcwa_pll_translate';
	$saved      = $translator( $source, $lang );

	if ( null === $saved || '' === $saved ) {
		return '' !== $ours ? $ours : $source;
	}

	if ( 'vi' !== $lang && $saved === $source && '' !== $ours ) {
		return $ours;
	}

	return (string) $saved;
}

/**
 * Vietnamese source texts used by more than one key with different English text
 * (see hcwa_translate_entry()). wp-static-check.py lists them as warnings.
 *
 * @param array|null $strings Dictionary; defaults to hcwa_strings().
 * @return array<string, true> Vietnamese text => true.
 */
function hcwa_ambiguous_sources( ?array $strings = null ): array {
	$english = array();
	foreach ( $strings ?? hcwa_strings() as $entry ) {
		$vi = (string) ( $entry['vi'] ?? '' );
		if ( '' !== $vi ) {
			$english[ $vi ][ (string) ( $entry['en'] ?? '' ) ] = true;
		}
	}
	return array_map( static fn() => true, array_filter( $english, static fn( $ens ) => count( $ens ) > 1 ) );
}

/**
 * Polylang's saved translation of a registered string, or null without Polylang.
 * Memoized because pll_translate_string() is called many times per page.
 *
 * @param string $source Vietnamese source text.
 * @param string $lang   'vi' or 'en'.
 */
function hcwa_pll_translate( string $source, string $lang ): ?string {
	static $memo = array();

	if ( ! function_exists( 'pll_translate_string' ) ) {
		return null;
	}

	if ( ! isset( $memo[ $lang ][ $source ] ) ) {
		$memo[ $lang ][ $source ] = (string) pll_translate_string( $source, $lang );
	}

	return $memo[ $lang ][ $source ];
}
