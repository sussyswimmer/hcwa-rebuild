<?php
/**
 * hcwa/donation-methods: "How to give" accordion (prototype donate page): bank transfer
 * in Vietnam with copy buttons and the VietQR image, then giving from abroad with SWIFT
 * details and PayPal (contract gating rule 3).
 *
 * Details come only from hcwa_donation_details(), which is null until HCWA finance has
 * confirmed them (name and date) in Settings → HCWA:
 * - live site, not confirmed: nothing (the core plugin shows staff an admin notice);
 * - staging, not confirmed: the prototype's labelled placeholder panel, every value
 *   marked data-donation-placeholder;
 * - confirmed: the real rows. Only one copy of this block per page (ids are anchors).
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes (none).
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();
$hcwa_details    = hcwa_donation_details();

if ( null === $hcwa_details && $hcwa_production ) {
	return;
}

$hcwa_qr = '';
if ( null !== $hcwa_details && ! empty( $hcwa_details['vietqr_id'] ) ) {
	$hcwa_qr = (string) wp_get_attachment_image(
		(int) $hcwa_details['vietqr_id'],
		'medium',
		false,
		array(
			'class'   => 'pay__qr',
			'alt'     => hcwa_t( 'pay_qr' ),
			'loading' => 'lazy',
		)
	);
}

$hcwa_methods = hcwa_blocks_donation_methods( $hcwa_details, $hcwa_lang, $hcwa_qr );

if ( '' === $hcwa_methods ) {
	echo hcwa_blocks_empty_note( 'blk_noDonation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'methods' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php echo $hcwa_methods; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
	<p class="sr-only" aria-live="polite" data-live></p>
</div>
