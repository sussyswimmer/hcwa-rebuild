<?php
/**
 * Share links: Facebook (plain link, no SDK), the phone's share sheet (where Zalo lives
 * on most phones; shown by site.js only when the browser supports it) and copy link.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_url   = (string) wp_get_canonical_url();
$hcwa_url   = '' !== $hcwa_url ? $hcwa_url : (string) get_permalink();
$hcwa_title = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
$hcwa_fb    = add_query_arg( 'u', rawurlencode( $hcwa_url ), 'https://www.facebook.com/sharer/sharer.php' );
?>
<div class="share" role="group" aria-labelledby="share-title">
	<p class="share__title" id="share-title"><?php hcwa_theme_e( 'share' ); ?></p>
	<ul class="share__list">
		<li>
			<a class="btn btn--secondary btn--small" href="<?php echo esc_url( $hcwa_fb ); ?>" target="_blank" rel="noopener">Facebook<span class="sr-only"> <?php hcwa_theme_e( 'newTab' ); ?></span></a>
		</li>
		<li data-share-native hidden>
			<button class="btn btn--secondary btn--small" type="button" data-share-url="<?php echo esc_url( $hcwa_url ); ?>" data-share-title="<?php echo esc_attr( html_entity_decode( $hcwa_title, ENT_QUOTES, 'UTF-8' ) ); ?>"><?php echo hcwa_theme_icon( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php hcwa_theme_e( 'shareApp' ); ?></span></button>
		</li>
		<li>
			<button class="btn btn--secondary btn--small" type="button" data-copy="<?php echo esc_url( $hcwa_url ); ?>"><?php echo hcwa_theme_icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php hcwa_theme_e( 'copyLink' ); ?></span></button>
		</li>
	</ul>
</div>
