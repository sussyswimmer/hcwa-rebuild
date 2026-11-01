<?php
/**
 * hcwa/contact-details. "band": phone + email inline (blue contact band). "card": office card
 * with address, phone, email and a map link (no embedded Google Map, 06-PAGES).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_c = hcwa_contact();
if ( ! $hcwa_c ) {
	return;
}
$hcwa_draft = hcwa_draft_attr( $hcwa_c['draft'] );
$hcwa_lines = array();
if ( 'card' === $attributes['variant'] && $hcwa_c['address'] ) {
	$hcwa_lines[] = '<li>' . hcwa_icon( 'pin' ) . '<span>' . esc_html( $hcwa_c['address'] ) . '</span></li>';
}
if ( $hcwa_c['phone'] ) {
	$hcwa_lines[] = '<li>' . hcwa_icon( 'phone' ) . '<a href="tel:' . esc_attr( $hcwa_c['tel'] ) . '">' . esc_html( $hcwa_c['phone'] ) . '</a></li>';
}
if ( $hcwa_c['email'] ) {
	$hcwa_lines[] = '<li>' . hcwa_icon( 'mail' ) . '<a href="mailto:' . esc_attr( $hcwa_c['email'] ) . '">' . esc_html( $hcwa_c['email'] ) . '</a></li>';
}
if ( ! $hcwa_lines ) {
	return;
}

if ( 'band' === $attributes['variant'] ) {
	echo '<ul ' . hcwa_block_wrapper( 'cta-band__contact' ) . $hcwa_draft . '>' . implode( '', $hcwa_lines ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

echo '<article ' . hcwa_block_wrapper( 'office' ) . $hcwa_draft . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
echo '<h3 class="h4">' . esc_html( hcwa_t( 'hcwaOffice' ) ) . '</h3>';
echo '<ul class="contact-lines">' . implode( '', $hcwa_lines ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
if ( $hcwa_c['map_url'] ) {
	echo '<a class="map-link" href="' . esc_url( $hcwa_c['map_url'] ) . '">';
	echo hcwa_image_slot( 0, array( 'code' => 'C7', 'label' => array( 'vi' => 'Bản đồ tĩnh', 'en' => 'Static map' ), 'ratio' => '4:3' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<span class="text-link"><span>' . esc_html( hcwa_t( 'openMap' ) ) . '</span>' . hcwa_icon( 'external' ) . '</span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
if ( $hcwa_c['zalo_url'] || hcwa_setting( 'facebook_url' ) ) {
	$hcwa_chat = array();
	if ( $hcwa_c['zalo_url'] ) {
		$hcwa_chat[] = '<a href="' . esc_url( $hcwa_c['zalo_url'] ) . '">Zalo</a>';
	}
	if ( hcwa_setting( 'facebook_url' ) ) {
		$hcwa_chat[] = '<a href="' . esc_url( hcwa_setting( 'facebook_url' ) ) . '">Facebook Messenger</a>';
	}
	echo '<p class="offices__chat">' . esc_html( hcwa_t( 'messageUsOn' ) ) . ' ' . implode( ' · ', $hcwa_chat ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
echo '</article>';
