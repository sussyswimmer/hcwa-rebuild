<?php
/**
 * Page: page header (breadcrumb from the page's parents, H1, excerpt as intro) and the
 * page's blocks.
 *
 * Pages built from a pattern that already has its own H1 (page-header or hero pattern) get
 * no second header; the breadcrumb is still added if the pattern has none.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post    = get_post();
	$hcwa_content = str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content() ) );
	$hcwa_title   = wp_strip_all_tags( get_the_title() );

	$hcwa_trail = array();
	foreach ( array_reverse( get_post_ancestors( $hcwa_post ) ) as $hcwa_ancestor ) {
		$hcwa_trail[] = array( wp_strip_all_tags( get_the_title( $hcwa_ancestor ) ), get_permalink( $hcwa_ancestor ) );
	}
	$hcwa_trail[] = array( $hcwa_title );

	if ( false === stripos( $hcwa_content, '<h1' ) ) {
		get_template_part(
			'template-parts/page-header',
			null,
			array(
				'trail' => $hcwa_trail,
				'title' => $hcwa_title,
				'intro' => has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : '',
			)
		);
	} elseif ( ! preg_match( '/class="[^"]*\bbreadcrumb\b/', $hcwa_content ) ) {
		echo '<div class="container page-crumbs">';
		get_template_part( 'template-parts/breadcrumb', null, array( 'trail' => $hcwa_trail ) );
		echo '</div>';
	}
	?>
	<div class="entry-content entry-content--page">
		<?php echo $hcwa_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
	</div>
	<?php
endwhile;

get_footer();
