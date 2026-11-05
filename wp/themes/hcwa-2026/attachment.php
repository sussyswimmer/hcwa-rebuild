<?php
/**
 * Attachment page. Older installs still have these pages; core would print the full image
 * through the_content (prepend_attachment), past the photo approval gate. Here the image
 * goes through hcwa_image() in its context (the parent, or a no-identifiable context when a
 * protected center or post uses it: hcwa_theme_attachment_no_identifiable()). A refused
 * image never gets here: inc/setup.php turns its page into a 404 that says nothing about
 * the file, since titles and captions of child photos often carry names. Attachment pages
 * are noindex (inc/setup.php).
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
			'class'   => 'post-cover__img',
			'loading' => 'eager',
			'sizes'   => '(min-width: 1024px) 960px, 100vw',
		),
		hcwa_theme_attachment_no_identifiable( get_the_ID() )
	) : '';
	if ( $hcwa_is_image && '' === $hcwa_image ) {
		continue; // Refused (normally a 404 already): print nothing about the file.
	}

	$hcwa_parent = wp_get_post_parent_id( get_the_ID() );
	$hcwa_title  = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
	$hcwa_trail  = array();
	if ( $hcwa_parent ) {
		$hcwa_trail[] = array( wp_strip_all_tags( get_the_title( $hcwa_parent ) ), get_permalink( $hcwa_parent ) );
	}
	$hcwa_trail[] = array( $hcwa_title );

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'trail' => $hcwa_trail,
			'title' => $hcwa_title,
		)
	);
	// Captions skip the publish guard: no draft text on the live site.
	$hcwa_caption = has_excerpt() ? hcwa_theme_public_text( wp_strip_all_tags( get_the_excerpt() ) ) : '';
	?>
	<div class="section section--tight">
		<?php if ( '' !== $hcwa_image ) : ?>
			<figure class="container post-cover">
				<?php echo $hcwa_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?>
				<?php if ( '' !== $hcwa_caption ) : ?>
					<figcaption<?php echo hcwa_theme_draft_attr( $hcwa_caption ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute. ?>><?php echo esc_html( $hcwa_caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>
		<?php if ( $hcwa_parent ) : ?>
			<p class="container">
				<?php echo hcwa_theme_text_link( (string) get_permalink( $hcwa_parent ), wp_strip_all_tags( get_the_title( $hcwa_parent ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
endwhile;

get_footer();
