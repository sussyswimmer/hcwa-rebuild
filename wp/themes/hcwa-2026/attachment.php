<?php
/**
 * Attachment page (11-WP-PORT.md 2.7). Older installs still have these pages; core would print
 * the full image through the_content (prepend_attachment), past the photo approval gate. Here
 * the image goes through hcwa_image() in its context (the parent, or a no-identifiable
 * context when a protected center or post uses it: hcwa_theme_attachment_no_identifiable()).
 * A refused image never gets here: hcwa-core turns its page into a 404 that says nothing about
 * the file and stops the canonical redirect that would name it. Heading and caption are only
 * text staff wrote, never the file name or text read from the file. Attachment pages are
 * noindex (inc/setup.php).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_is_image = wp_attachment_is_image( get_the_ID() );
	$hcwa_image    = $hcwa_is_image ? hcwa_theme_image(
		get_the_ID(),
		'hcwa-1600',
		array(
			'class'   => 'wp-cover__img',
			'loading' => 'eager',
			'sizes'   => '(min-width: 1024px) 960px, 100vw',
		),
		hcwa_theme_attachment_no_identifiable( get_the_ID() )
	) : '';
	if ( $hcwa_is_image && '' === $hcwa_image ) {
		continue; // Refused (normally a 404 already): print nothing about the file.
	}

	$hcwa_parent = wp_get_post_parent_id( get_the_ID() );
	$hcwa_title  = hcwa_theme_attachment_heading( (int) get_the_ID() );
	$hcwa_trail  = array();
	if ( $hcwa_parent ) {
		$hcwa_trail[] = array( wp_strip_all_tags( (string) get_the_title( $hcwa_parent ) ), (string) get_permalink( $hcwa_parent ) );
	}
	$hcwa_trail[] = array( $hcwa_title );

	echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		array(
			'title' => $hcwa_title,
			'trail' => $hcwa_trail,
		)
	);
	$hcwa_caption = hcwa_theme_draft_text( hcwa_theme_attachment_text( (int) get_the_ID(), 'caption' ) );
	?>
<div class="day day--donate">
  <div class="container wp-body">
	<?php if ( '' !== $hcwa_image ) : ?>
	<figure class="wp-cover">
		<?php echo $hcwa_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?>
		<?php if ( '' !== $hcwa_caption['text'] ) : ?>
		<figcaption<?php echo $hcwa_caption['draft'] ? ' data-draft' : ''; ?>><?php echo esc_html( $hcwa_caption['text'] ); ?></figcaption>
		<?php endif; ?>
	</figure>
	<?php endif; ?>
	<?php if ( $hcwa_parent ) : ?>
	<p><?php echo hcwa_theme_text_link( (string) get_permalink( $hcwa_parent ), wp_strip_all_tags( (string) get_the_title( $hcwa_parent ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></p>
	<?php endif; ?>
  </div>
</div>
	<?php
endwhile;

get_footer();
