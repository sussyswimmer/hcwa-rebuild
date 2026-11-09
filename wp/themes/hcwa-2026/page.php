<?php
/**
 * Page (11-WP-PORT.md 4.3). A page whose content holds an hcwa/sections block (Our Work, Get
 * Involved, Donate, Contact) prints through hcwa_render_sections(): the prototype's markup,
 * no the_content filters, the photo gate on every block staff could add (2.1 rule 4). Any
 * other page (About, Team, Policies and the rest, 2.7) prints the sun head with its breadcrumb
 * and H1, then its blocks through the_content inside the day's gradient.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post = get_post();
	$hcwa_html = $hcwa_post instanceof WP_Post && hcwa_theme_is_sections_page( $hcwa_post ) ? hcwa_theme_render_sections( $hcwa_post ) : '';

	if ( '' !== $hcwa_html ) {
		echo $hcwa_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
		continue;
	}

	$hcwa_title = hcwa_theme_draft_text( (string) get_the_title() );
	$hcwa_trail = array();
	foreach ( array_reverse( get_post_ancestors( $hcwa_post ) ) as $hcwa_ancestor ) {
		$hcwa_trail[] = array( wp_strip_all_tags( (string) get_the_title( $hcwa_ancestor ) ), (string) get_permalink( $hcwa_ancestor ) );
	}
	$hcwa_trail[] = array( $hcwa_title['text'] );
	$hcwa_intro   = has_excerpt() ? hcwa_theme_draft_text( (string) get_the_excerpt() ) : array(
		'text'  => '',
		'draft' => false,
	);

	echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		array(
			'title'       => $hcwa_title['text'],
		'title_draft' => $hcwa_title['draft'],
			'trail'       => $hcwa_trail,
			'intro'       => $hcwa_intro['text'],
			'intro_draft' => $hcwa_intro['draft'],
		)
	);
	?>
<div class="day day--donate">
  <div class="entry-content container">
	<?php echo str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
  </div>
</div>
	<?php
endwhile;

get_footer();
