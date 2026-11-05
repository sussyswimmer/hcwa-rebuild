<?php
/**
 * News post (06-PAGES): breadcrumb, category and localized date, H1, lead image, body,
 * "names changed" note on stories, share links, related posts, CTA band. When the visitor
 * arrived from the language switch and this post has no translation, the language notice
 * comes first.
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
	$hcwa_title    = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
	$hcwa_ymd      = get_the_date( 'Y-m-d' );

	$hcwa_trail = array();
	if ( $hcwa_is_post ) {
		$hcwa_trail[] = array( hcwa_theme_t( 'news' ), hcwa_theme_url( 'news' ) );
		if ( $hcwa_category['term'] instanceof WP_Term ) {
			$hcwa_cat_link = get_term_link( $hcwa_category['term'] );
			$hcwa_trail[]  = array( $hcwa_category['term']->name, is_wp_error( $hcwa_cat_link ) ? '' : $hcwa_cat_link );
		}
	}
	$hcwa_trail[] = array( $hcwa_title );

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'trail'   => $hcwa_trail,
			'title'   => $hcwa_title,
			'class'   => 'post-header',
			'eyebrow' => static function () use ( $hcwa_category, $hcwa_ymd ): void {
				?>
				<p class="post-header__meta">
					<?php if ( '' !== $hcwa_category['label'] ) : ?>
						<span class="label"><?php echo esc_html( $hcwa_category['label'] ); ?></span>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( $hcwa_ymd ); ?>"><?php echo esc_html( hcwa_theme_date( $hcwa_ymd ) ); ?></time>
				</p>
				<?php
			},
		)
	);

	// Only while the post really has no published version in the language asked for: a bookmarked
	// ?hcwa_lang_notice= link to a post translated since is redirected (inc/setup.php).
	$hcwa_notice_lang = hcwa_theme_lang_notice_request();
	if ( '' !== $hcwa_notice_lang && 0 === hcwa_theme_published_translation( (int) get_the_ID(), $hcwa_notice_lang ) ) {
		$hcwa_notice = hcwa_theme_block( 'hcwa/language-notice', array( 'forceLang' => $hcwa_notice_lang ) );
		if ( '' !== $hcwa_notice ) {
			echo '<div class="container post-notice">' . $hcwa_notice . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
		}
	}

	$hcwa_thumb_id = (int) get_post_thumbnail_id();
	$hcwa_cover    = hcwa_theme_image(
		$hcwa_thumb_id,
		'hcwa-1200',
		array(
			'class'         => 'post-cover__img',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'sizes'         => '(min-width: 800px) 760px, 100vw',
		),
		$hcwa_post
	);
	?>
	<article class="post-body">
		<?php if ( '' !== $hcwa_cover ) : ?>
			<figure class="container post-cover">
				<?php
				echo $hcwa_cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image().
				hcwa_theme_cover_printed();
				// Only a caption staff wrote, never one read from the file; no draft text on the live site.
				$hcwa_caption = hcwa_theme_attachment_text( $hcwa_thumb_id, 'caption' );
				if ( '' !== $hcwa_caption ) :
					?>
					<figcaption<?php echo hcwa_theme_draft_attr( $hcwa_caption ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute. ?>><?php echo esc_html( $hcwa_caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="container post-body__inner">
			<div class="entry-content entry-content--prose">
				<?php the_content(); ?>
			</div>
			<?php if ( $hcwa_is_story ) : ?>
				<p class="note story-note"><?php echo hcwa_theme_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php hcwa_theme_e( 'namesChanged' ); ?></span></p>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/share' ); ?>
		</div>
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
		get_template_part( 'template-parts/related-news', null, array( 'posts' => $hcwa_related->posts ) );
	}

	get_template_part(
		'template-parts/cta-band',
		null,
		array(
			'title'   => hcwa_theme_t( 'helpTitle' ),
			'actions' => array(
				array( hcwa_theme_url( 'donate' ), hcwa_theme_t( 'donate' ), 'primary' ),
				array( hcwa_theme_url( 'getInvolved' ), hcwa_theme_t( 'getInvolved' ), 'on-blue' ),
			),
		)
	);
endwhile;

get_footer();
