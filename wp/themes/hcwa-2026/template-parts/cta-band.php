<?php
/**
 * CTA band: blue background, one sentence, at most two buttons (05-DESIGN).
 *
 * @package hcwa-2026
 *
 * @var array $args {
 *     @type string $title   One sentence (plain).
 *     @type array  $actions Up to two [url, label, kind] buttons; kind primary | on-blue.
 *     @type array  $contact Optional [icon, url, text] lines (approved contact details only).
 *     @type string $id      Heading id.
 * }
 */

defined( 'ABSPATH' ) || exit;

$hcwa_args = wp_parse_args(
	$args ?? array(),
	array(
		'title'   => '',
		'actions' => array(),
		'contact' => array(),
		'id'      => 'cta-title',
	)
);
if ( '' === $hcwa_args['title'] ) {
	return;
}
?>
<section class="cta-band" aria-labelledby="<?php echo esc_attr( $hcwa_args['id'] ); ?>">
	<div class="container cta-band__inner">
		<h2 id="<?php echo esc_attr( $hcwa_args['id'] ); ?>"><?php echo esc_html( $hcwa_args['title'] ); ?></h2>
		<?php if ( $hcwa_args['actions'] ) : ?>
			<div class="cta-band__actions">
				<?php
				foreach ( array_slice( $hcwa_args['actions'], 0, 2 ) as $hcwa_action ) {
					echo hcwa_theme_button( (string) $hcwa_action[0], (string) $hcwa_action[1], (string) ( $hcwa_action[2] ?? 'on-blue' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
				}
				?>
			</div>
		<?php endif; ?>
		<?php if ( $hcwa_args['contact'] ) : ?>
			<ul class="cta-band__contact">
				<?php foreach ( $hcwa_args['contact'] as $hcwa_line ) : ?>
					<li><?php echo hcwa_theme_icon( (string) $hcwa_line[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><a href="<?php echo esc_url( (string) $hcwa_line[1], array( 'tel', 'mailto', 'http', 'https' ) ); ?>"><?php echo esc_html( (string) $hcwa_line[2] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
