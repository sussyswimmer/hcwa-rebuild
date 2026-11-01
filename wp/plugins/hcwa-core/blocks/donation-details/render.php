<?php
/**
 * hcwa/donation-details. Hard rule (CLAUDE.md): no account number, QR code, account holder or
 * PayPal address renders unless HCWA finance confirmed it (Settings → HCWA, "confirmed by" +
 * "confirmed on"). Before that: placeholders on staging, a "contact us" line on production.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_ok     = hcwa_donation_confirmed();
$hcwa_method = $attributes['method'];

if ( ! $hcwa_ok && ! hcwa_review_mode() ) {
	// PayPal sits next to the transfer details from abroad; one "contact us" line is enough.
	if ( 'paypal' !== $hcwa_method ) {
		echo '<p ' . hcwa_block_wrapper( 'pay-ask' ) . '>' . esc_html( hcwa_t( 'd_askUs' ) ) . ' ' . hcwa_text_link( hcwa_url( 'contact' ), hcwa_t( 'contactUs' ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	return;
}

$hcwa_rows = array(
	'vietnam' => array(
		array( 'd_bank', 'bank_name', false ),
		array( 'd_account', 'bank_account', true ),
		array( 'd_holder', 'bank_holder', false ),
		array( 'd_note', 'transfer_note', true ),
	),
	'abroad'  => array(
		array( 'd_bank', 'abroad_bank', false ),
		array( 'd_swift', 'abroad_swift', true ),
		array( 'd_accountAbroad', 'abroad_account', true ),
		array( 'd_holder', 'abroad_holder', false ),
	),
	'paypal'  => array(),
);

$hcwa_value = function ( $setting ) {
	return 'transfer_note' === $setting ? hcwa_setting_tx( $setting ) : (string) hcwa_setting( $setting );
};

echo '<div ' . hcwa_block_wrapper( 'vietnam' === $hcwa_method ? 'pay' : 'pay pay--plain' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput

if ( $hcwa_rows[ $hcwa_method ] ) {
	echo '<dl class="pay-details">';
	foreach ( $hcwa_rows[ $hcwa_method ] as $hcwa_row ) {
		list( $hcwa_label, $hcwa_setting, $hcwa_copy ) = $hcwa_row;
		$hcwa_v = $hcwa_ok ? $hcwa_value( $hcwa_setting ) : '';
		echo '<div class="pay-details__row"><dt>' . esc_html( hcwa_t( $hcwa_label ) ) . '</dt><dd>';
		echo $hcwa_ok ? '<span class="pay-details__value">' . esc_html( $hcwa_v ) . '</span>' : '<span class="pending-value" data-donation-placeholder>' . esc_html( hcwa_t( 'pendingFinance' ) ) . '</span>';
		if ( 'd_note' === $hcwa_label ) {
			echo '<span class="pay-details__hint">' . esc_html( hcwa_t( 'd_noteHint' ) ) . '</span>';
		}
		echo '</dd>';
		if ( $hcwa_copy ) {
			printf(
				'<button class="btn btn--small btn--secondary" type="button" data-copy="%s"%s aria-label="%s">%s<span>%s</span></button>',
				esc_attr( $hcwa_v ),
				$hcwa_ok && $hcwa_v ? '' : ' disabled',
				esc_attr( hcwa_t( 'copy' ) . ': ' . hcwa_t( $hcwa_label ) ),
				hcwa_icon( 'copy' ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( hcwa_t( 'copy' ) )
			);
		} else {
			echo '<span></span>';
		}
		echo '</div>';
	}
	echo '</dl>';
}

if ( 'vietnam' === $hcwa_method ) {
	$hcwa_qr = $hcwa_ok ? (int) hcwa_setting( 'vietqr' ) : 0;
	// The QR code is a payment detail, not a photo: confirmed by finance, so no photo approval needed.
	echo $hcwa_qr ? wp_get_attachment_image( $hcwa_qr, 'medium', false, array( 'class' => 'pay__qr', 'alt' => hcwa_t( 'd_qr' ) ) ) : hcwa_image_slot( 0, array( 'code' => 'C6', 'label' => array( 'vi' => 'Mã VietQR', 'en' => 'VietQR code' ), 'ratio' => '1:1', 'class' => 'pay__qr' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
}

if ( 'paypal' === $hcwa_method ) {
	$hcwa_paypal = $hcwa_ok ? hcwa_setting( 'paypal_url' ) : '';
	echo '<p class="method__alt">' . esc_html( hcwa_t( 'd_paypal' ) ) . ' ';
	echo $hcwa_paypal
		? '<a class="btn btn--secondary btn--small" href="' . esc_url( $hcwa_paypal ) . '" rel="noopener">PayPal</a>'
		: '<button class="btn btn--secondary btn--small" type="button" disabled data-donation-placeholder>PayPal</button>';
	echo '</p>';
}
echo '</div>';
