<?php
/**
 * Attachment page. Older installs still have these pages; core would print the full image
 * through the_content (prepend_attachment), past the photo approval gate. Here the image
 * goes through hcwa_image() with the parent post as context, or is not shown at all.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_parent = wp_get_post_parent_id( get_the_ID() );
	$hcwa_title  = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
	$hcwa_image  = wp_attachment_is_image( get_the_ID() ) ? hcwa_theme_image(
		get_the_ID(),
		'hcwa-1600',
		array(
			'class'   => 'post-cover__img',
			'loading' => 'eager',
			'sizes'   => '(min-width: 1024px) 960px, 100vw',
		),
		$hcwa_parent ? get_post( $hcwa_parent ) : null
	) : '';

	$hcwa_trail = array();
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
	?>
	<div class="section section--tight">
		<?php if ( '' !== $hcwa_image ) : ?>
			<figure class="container post-cover">
				<?php echo $hcwa_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?>
				<?php if ( has_excerpt() ) : ?>
					<figcaption><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></figcaption>
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
