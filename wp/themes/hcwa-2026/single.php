<?php
/**
 * News post (11-WP-PORT.md 2.7): the sun head with breadcrumb, H1 and the date and section,
 * the language notice when the visitor came from the language switch and the post has no
 * translation, then in the day's gradient the lead photo (gated), the body through
 * the_content, the "names changed" note on stories, share links and related news.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post     = get_post();
	$hcwa_is_post  = 'post' === $hcwa_post->post_type;
	$hcwa_category = $hcwa_is_post ? hcwa_theme_post_category( $hcwa_post ) : array(
		'term'  => null,
		'key'   => '',
		'label' => '',
	);
	$hcwa_is_story = $hcwa_is_post && hcwa_theme_is_story( $hcwa_post );
	$hcwa_title    = hcwa_theme_draft_text( hcwa_theme_clean_title( wp_strip_all_tags( (string) get_the_title() ) ) );
	$hcwa_ymd      = (string) get_the_date( 'Y-m-d' );
	$hcwa_meta     = trim( ( '' !== $hcwa_category['label'] ? $hcwa_category['label'] . ' · ' : '' ) . hcwa_theme_date( $hcwa_ymd ) );

	$hcwa_trail = array();
	if ( $hcwa_is_post ) {
		$hcwa_trail[] = array( hcwa_theme_t( 'news' ), hcwa_theme_url( 'news' ) );
		if ( $hcwa_category['term'] instanceof WP_Term ) {
			$hcwa_cat_link = get_term_link( $hcwa_category['term'] );
			$hcwa_trail[]  = array( $hcwa_category['term']->name, is_wp_error( $hcwa_cat_link ) ? '' : $hcwa_cat_link );
		}
	}
	$hcwa_trail[] = array( $hcwa_title['text'] );

	echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		array(
			'title' => $hcwa_title['text'],
			'title_draft' => $hcwa_title['draft'],
			'trail' => $hcwa_trail,
			'intro' => $hcwa_meta,
		)
	);

	// Only while the post really has no published version in the language asked for: a bookmarked
	// ?hcwa_lang_notice= link to a post translated since is redirected (inc/setup.php).
	$hcwa_notice      = '';
	$hcwa_notice_lang = hcwa_theme_lang_notice_request();
	if ( '' !== $hcwa_notice_lang && 0 === hcwa_theme_published_translation( (int) get_the_ID(), $hcwa_notice_lang ) ) {
		$hcwa_notice = hcwa_theme_block( 'hcwa/language-notice', array( 'forceLang' => $hcwa_notice_lang ) );
	}

	$hcwa_thumb_id = (int) get_post_thumbnail_id();
	$hcwa_cover    = hcwa_theme_image(
		$hcwa_thumb_id,
		'hcwa-1200',
		array(
			'class'         => 'wp-cover__img',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'sizes'         => '(min-width: 800px) 760px, 100vw',
		),
		$hcwa_post
	);
	?>
<div class="day day--donate">
  <article class="container wp-body">
	<?php if ( '' !== $hcwa_notice ) : ?>
		<?php echo $hcwa_notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
	<?php endif; ?>
	<?php if ( '' !== $hcwa_cover ) : ?>
	<figure class="wp-cover">
		<?php
		echo $hcwa_cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image().
		hcwa_theme_cover_printed();
		// Only a caption staff wrote, never one read from the file; no draft text on the live site.
		$hcwa_caption = hcwa_theme_draft_text( hcwa_theme_attachment_text( $hcwa_thumb_id, 'caption' ) );
		if ( '' !== $hcwa_caption['text'] ) :
			?>
		<figcaption<?php echo $hcwa_caption['draft'] ? ' data-draft' : ''; ?>><?php echo esc_html( $hcwa_caption['text'] ); ?></figcaption>
		<?php endif; ?>
	</figure>
	<?php endif; ?>
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
	<?php if ( $hcwa_is_story ) : ?>
	<p class="note wp-note"><?php echo hcwa_theme_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php hcwa_theme_e( 'namesChanged' ); ?></span></p>
	<?php endif; ?>
	<?php get_template_part( 'template-parts/share' ); ?>
  </article>
	<?php
	if ( $hcwa_is_post ) {
		$hcwa_related = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'category__in'        => wp_get_post_categories( $hcwa_post->ID ),
				'post__not_in'        => array( $hcwa_post->ID ),
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		$hcwa_rows    = '';
		foreach ( $hcwa_related->posts as $hcwa_related_post ) {
			$hcwa_rows .= $hcwa_related_post instanceof WP_Post ? hcwa_theme_dated_post( $hcwa_related_post ) : '';
		}
		if ( '' !== $hcwa_rows ) :
			?>
  <section class="related container" aria-labelledby="post-news">
	<h2 class="related__title" id="post-news"><span><?php hcwa_theme_e( 'relatedNews' ); ?></span></h2>
	<ol class="dated-list related__list"><?php echo $hcwa_rows; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></ol>
	<p class="related__all"><?php echo hcwa_theme_text_link( hcwa_theme_url( 'news' ), hcwa_theme_t( 'allNews' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></p>
  </section>
			<?php
		endif;
	}
	?>
</div>
	<?php
endwhile;

get_footer();
