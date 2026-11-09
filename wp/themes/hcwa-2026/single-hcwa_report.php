<?php
/**
 * One report (11-WP-PORT.md 2.7): the sun head with its summary, then in the day's gradient
 * the document row (the file, its year, type, language and size) and the body through
 * the_content.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post    = get_post();
	$hcwa_title   = hcwa_theme_draft_text( hcwa_theme_clean_title( wp_strip_all_tags( (string) get_the_title() ) ) );
	$hcwa_summary = hcwa_theme_draft_text( wp_strip_all_tags( (string) get_post_meta( $hcwa_post->ID, 'hcwa_summary', true ) ) );
	$hcwa_archive = (string) get_post_type_archive_link( 'hcwa_report' );

	echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		array(
			'title'       => $hcwa_title['text'],
			'trail'       => array(
				array( hcwa_theme_t( 'impact' ), hcwa_theme_url( 'impact' ) ),
				array( hcwa_theme_t( 'reportsTitle' ), '' !== $hcwa_archive ? $hcwa_archive : hcwa_theme_url( 'reports' ) ),
				array( $hcwa_title['text'] ),
			),
			'intro'       => $hcwa_summary['text'],
			'intro_draft' => $hcwa_summary['draft'],
		)
	);
	$hcwa_row = hcwa_theme_doc_row( $hcwa_post );
	?>
<div class="day day--donate">
  <div class="container wp-body">
	<?php if ( '' !== $hcwa_row ) : ?>
	<ul class="docs__list"><?php echo $hcwa_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></ul>
	<?php endif; ?>
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
  </div>
</div>
	<?php
endwhile;

get_footer();
