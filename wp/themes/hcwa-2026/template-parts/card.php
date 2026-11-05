<?php
/**
 * Post card. Variants:
 * - story: image 3:2, category label, title, date, excerpt (news archive grid)
 * - item: category label + date, title (compact rows: related news, search results)
 *
 * The whole card is clickable through the title link's ::after; the title has no emoji.
 *
 * @package hcwa-2026
 *
 * @var array $args {
 *     @type WP_Post $post    The post.
 *     @type string  $variant story | item.
 *     @type string  $heading h2 | h3.
 *     @type bool    $excerpt Show the excerpt (item variant).
 * }
 */

defined( 'ABSPATH' ) || exit;

$hcwa_post = $args['post'] ?? null;
if ( ! $hcwa_post instanceof WP_Post ) {
	return;
}
$hcwa_variant = ( $args['variant'] ?? 'story' ) === 'item' ? 'item' : 'story';
$hcwa_heading = ( $args['heading'] ?? 'h3' ) === 'h2' ? 'h2' : 'h3';
$hcwa_title   = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title( $hcwa_post ) ) );
$hcwa_url     = get_permalink( $hcwa_post );
$hcwa_ymd     = get_the_date( 'Y-m-d', $hcwa_post );

// Label: category for posts, content type for everything else (search results).
$hcwa_label = '';
if ( 'post' === $hcwa_post->post_type ) {
	$hcwa_label = hcwa_theme_post_category( $hcwa_post )['label'];
} else {
	$hcwa_types = array(
		'page'         => 'type_page',
		'hcwa_center'  => 'type_center',
		'hcwa_program' => 'type_program',
		'hcwa_report'  => 'type_report',
	);
	$hcwa_label = isset( $hcwa_types[ $hcwa_post->post_type ] ) ? hcwa_theme_t( $hcwa_types[ $hcwa_post->post_type ] ) : '';
}
$hcwa_show_date = 'post' === $hcwa_post->post_type;
$hcwa_excerpt   = has_excerpt( $hcwa_post ) ? get_the_excerpt( $hcwa_post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $hcwa_post->post_content ) ), 28, '…' );
$hcwa_summary   = (string) get_post_meta( $hcwa_post->ID, 'hcwa_summary', true );
if ( '' !== $hcwa_summary ) {
	$hcwa_excerpt = $hcwa_summary;
}

if ( 'item' === $hcwa_variant ) :
	?>
	<li class="news-item">
		<p class="news-item__meta">
			<?php if ( '' !== $hcwa_label ) : ?>
				<span class="label"><?php echo esc_html( $hcwa_label ); ?></span>
			<?php endif; ?>
			<?php if ( $hcwa_show_date ) : ?>
				<time datetime="<?php echo esc_attr( $hcwa_ymd ); ?>"><?php echo esc_html( hcwa_theme_date( $hcwa_ymd ) ); ?></time>
			<?php endif; ?>
		</p>
		<<?php echo esc_html( $hcwa_heading ); ?> class="news-item__title"><a href="<?php echo esc_url( $hcwa_url ); ?>"><?php echo esc_html( $hcwa_title ); ?></a></<?php echo esc_html( $hcwa_heading ); ?>>
		<?php if ( ! empty( $args['excerpt'] ) && '' !== trim( $hcwa_excerpt ) ) : ?>
			<p class="news-item__excerpt"><?php echo esc_html( wp_strip_all_tags( $hcwa_excerpt ) ); ?></p>
		<?php endif; ?>
	</li>
	<?php
	return;
endif;

$hcwa_image = hcwa_theme_image(
	(int) get_post_thumbnail_id( $hcwa_post ),
	'medium_large',
	array(
		'loading' => 'lazy',
		'sizes'   => '(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw',
	),
	$hcwa_post
);
$hcwa_is_story = hcwa_theme_is_story( $hcwa_post );
?>
<li class="story-card">
	<div class="story-card__media<?php echo '' === $hcwa_image ? ' story-card__media--empty' : ''; ?>"<?php echo '' === $hcwa_image ? ' aria-hidden="true"' : ''; ?>>
		<?php echo $hcwa_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image() / wp_get_attachment_image(). ?>
	</div>
	<p class="story-card__meta">
		<?php if ( '' !== $hcwa_label ) : ?>
			<span class="label"><?php echo esc_html( $hcwa_label ); ?></span>
		<?php endif; ?>
		<time datetime="<?php echo esc_attr( $hcwa_ymd ); ?>"><?php echo esc_html( hcwa_theme_date( $hcwa_ymd ) ); ?></time>
	</p>
	<<?php echo esc_html( $hcwa_heading ); ?> class="story-card__title"><a href="<?php echo esc_url( $hcwa_url ); ?>"><?php echo esc_html( $hcwa_title ); ?></a></<?php echo esc_html( $hcwa_heading ); ?>>
	<?php if ( '' !== trim( $hcwa_excerpt ) ) : ?>
		<p class="story-card__excerpt"><?php echo esc_html( wp_strip_all_tags( $hcwa_excerpt ) ); ?></p>
	<?php endif; ?>
	<?php if ( $hcwa_is_story ) : ?>
		<p class="story-card__note"><?php hcwa_theme_e( 'namesChanged' ); ?></p>
	<?php endif; ?>
</li>
