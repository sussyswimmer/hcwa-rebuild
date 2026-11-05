<?php
/**
 * "Related news" section: three posts as compact rows. Prints nothing when there are none.
 *
 * @package hcwa-2026
 *
 * @var array $args { @type WP_Post[] $posts Posts. @type string $id Heading id. }
 */

defined( 'ABSPATH' ) || exit;

$hcwa_posts = isset( $args['posts'] ) && is_array( $args['posts'] ) ? $args['posts'] : array();
if ( ! $hcwa_posts ) {
	return;
}
$hcwa_id = (string) ( $args['id'] ?? 'related-news' );
?>
<section class="section section--tight" aria-labelledby="<?php echo esc_attr( $hcwa_id ); ?>">
	<div class="container">
		<div class="section-head">
			<h2 id="<?php echo esc_attr( $hcwa_id ); ?>"><?php hcwa_theme_e( 'relatedNews' ); ?></h2>
			<?php echo hcwa_theme_text_link( hcwa_theme_url( 'news' ), hcwa_theme_t( 'allNews' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
		</div>
		<ul class="news-list news-list--row">
			<?php
			foreach ( $hcwa_posts as $hcwa_post ) {
				get_template_part(
					'template-parts/card',
					null,
					array(
						'post'    => $hcwa_post,
						'variant' => 'item',
						'heading' => 'h3',
					)
				);
			}
			?>
		</ul>
	</div>
</section>
