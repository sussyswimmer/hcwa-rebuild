<?php
/**
 * Center page (11-WP-PORT.md 2.5 P2; prototype pages/work.mjs center()): the lit header,
 * then the day in the unit's colour (plate, story, photos or the hang, programs here, how to
 * help, related news), then the evening band. Every section is built by hcwa-core; a
 * protected center (Little Rose) never prints a location, a gallery or "visit".
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post = get_post();
	if ( ! $hcwa_post instanceof WP_Post || ! function_exists( 'hcwa_center_view' ) || ! function_exists( 'hcwa_center_header_html' ) || ! function_exists( 'hcwa_center_photos' ) || ! function_exists( 'hcwa_related_news_html' ) || ! function_exists( 'hcwa_center_dusk_html' ) || ! function_exists( 'hcwa_unit_style' ) ) {
		echo hcwa_theme_sun_head( array( 'title' => wp_strip_all_tags( (string) get_the_title() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		continue;
	}
	$hcwa_view   = hcwa_center_view( $hcwa_post );
	$hcwa_photos = hcwa_center_photos( $hcwa_post );
	$hcwa_args   = array(
		'post'   => $hcwa_post,
		'photos' => $hcwa_photos,
	);

	echo "\n" . hcwa_center_header_html( $hcwa_post ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	echo '<div class="day day--center" style="' . esc_attr( hcwa_unit_style( $hcwa_view ) ) . '">';
	get_template_part( 'template-parts/center/plate', null, $hcwa_args );
	get_template_part( 'template-parts/center/story', null, $hcwa_args );
	get_template_part( 'template-parts/center/photos', null, $hcwa_args );
	get_template_part( 'template-parts/center/here', null, $hcwa_args );
	get_template_part( 'template-parts/center/give', null, $hcwa_args );
	echo "\n  " . hcwa_related_news_html( $hcwa_post->ID, 'center-news' ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	echo "</div>\n";
	echo hcwa_center_dusk_html( $hcwa_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	echo $hcwa_photos['lightbox']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
endwhile;

get_footer();
