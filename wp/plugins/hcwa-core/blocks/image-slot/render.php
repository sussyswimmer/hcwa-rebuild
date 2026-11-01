<?php
/**
 * hcwa/image-slot: approved image or labeled placeholder (see hcwa_image_slot()).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_args = array(
	'code'         => $attributes['code'],
	'label'        => array( 'vi' => $attributes['labelVi'], 'en' => $attributes['labelEn'] ),
	'ratio'        => $attributes['ratio'],
	'illustration' => $attributes['illustration'],
	'eager'        => $attributes['eager'],
	'strict'       => hcwa_strict_context(),
	'class'        => isset( $attributes['className'] ) ? $attributes['className'] : '',
);
if ( $attributes['sizes'] ) {
	$hcwa_args['sizes'] = $attributes['sizes'];
}
echo hcwa_image_slot( (int) $attributes['id'], $hcwa_args ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in hcwa_image_slot()
