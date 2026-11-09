<?php
/**
 * Program page (11-WP-PORT.md 2.5 P3; prototype pages/work.mjs program()): the lit header
 * with the full name, status, years and funders; the day (brief, span, aims, results, where
 * it runs, documents, related news); the evening band. The program's post content is not
 * printed: every field is meta, and the edit screen says so.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post = get_post();
	if ( ! $hcwa_post instanceof WP_Post || ! function_exists( 'hcwa_program_header_html' ) || ! function_exists( 'hcwa_get_impact_figures' ) || ! function_exists( 'hcwa_impact_strip_visible' ) || ! function_exists( 'hcwa_counts_html' ) || ! function_exists( 'hcwa_original_id' ) || ! function_exists( 'hcwa_related_news_html' ) || ! function_exists( 'hcwa_program_dusk_html' ) ) {
		echo hcwa_theme_sun_head( array( 'title' => wp_strip_all_tags( (string) get_the_title() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		continue;
	}
	$hcwa_args    = array( 'post' => $hcwa_post );
	$hcwa_figures = hcwa_get_impact_figures(
		array(
			'scope_type' => 'program',
			'scope_id'   => hcwa_original_id( $hcwa_post->ID ),
			'limit'      => 12,
		)
	);

	echo "\n" . hcwa_program_header_html( $hcwa_post ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	echo '<div class="day day--program">';
	get_template_part( 'template-parts/program/brief', null, $hcwa_args );
	get_template_part( 'template-parts/program/span', null, $hcwa_args );
	get_template_part( 'template-parts/program/aims', null, $hcwa_args );
	if ( hcwa_impact_strip_visible( $hcwa_figures, hcwa_theme_is_production() ) ) {
		echo "\n  " . hcwa_counts_html( $hcwa_figures, hcwa_theme_t( 'results' ), 'program-results' ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	}
	get_template_part( 'template-parts/program/where', null, $hcwa_args );
	get_template_part( 'template-parts/program/docs', null, $hcwa_args );
	echo "\n  " . hcwa_related_news_html( $hcwa_post->ID, 'program-news' ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	echo "</div>\n";
	echo hcwa_program_dusk_html( $hcwa_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
endwhile;

get_footer();
