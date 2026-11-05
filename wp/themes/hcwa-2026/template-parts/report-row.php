<?php
/**
 * Report row: cover, title, year · type, language badge ("PDF · Tiếng Việt"), file size.
 * The badge shows the document language in that language, with a lang attribute.
 *
 * @package hcwa-2026
 *
 * @var array $args { @type WP_Post $post Report. @type string $heading h2 | h3. }
 */

defined( 'ABSPATH' ) || exit;

$hcwa_post = $args['post'] ?? null;
if ( ! $hcwa_post instanceof WP_Post ) {
	return;
}
$hcwa_heading = ( $args['heading'] ?? 'h3' ) === 'h2' ? 'h2' : 'h3';
$hcwa_info    = hcwa_theme_report_info( $hcwa_post );
$hcwa_cover   = hcwa_theme_image(
	(int) get_post_thumbnail_id( $hcwa_post ),
	'thumbnail',
	array(
		'class'   => 'report-row__cover',
		'loading' => 'lazy',
		'alt'     => '',
	),
	$hcwa_post
);
$hcwa_title = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title( $hcwa_post ) ) );
$hcwa_href  = '' !== $hcwa_info['file_url'] ? $hcwa_info['file_url'] : get_permalink( $hcwa_post );
?>
<li class="report-row">
	<?php if ( '' !== $hcwa_cover ) : ?>
		<?php echo $hcwa_cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?>
	<?php else : ?>
		<span class="report-row__cover report-row__cover--empty" aria-hidden="true"><?php echo esc_html( strtoupper( $hcwa_info['format'] ) ); ?></span>
	<?php endif; ?>
	<div>
		<<?php echo esc_html( $hcwa_heading ); ?> class="report-row__title"><a href="<?php echo esc_url( $hcwa_href ); ?>"><?php echo esc_html( $hcwa_title ); ?></a></<?php echo esc_html( $hcwa_heading ); ?>>
		<?php if ( '' !== $hcwa_info['meta'] ) : ?>
			<p class="report-row__meta"><?php echo esc_html( $hcwa_info['meta'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php hcwa_theme_report_badge( $hcwa_info ); ?>
	<span class="report-row__size"><?php echo esc_html( $hcwa_info['size'] ); ?></span>
</li>
