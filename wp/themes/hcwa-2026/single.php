<?php
/**
 * News post (06-PAGES "News archive and post"): breadcrumb, category, localized date, H1,
 * lead image, body (max 72ch), share links, related posts, CTA band. Stories carry the
 * "names changed" line.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$hcwa_cat = hcwa_post_category( get_post() );
	$hcwa_url = get_permalink();
	?>
	<article class="post">
		<header class="page-header page-header--post">
			<div class="container">
				<?php
				$hcwa_trail = array( array( hcwa_t( 'news' ), hcwa_url( 'news' ) ) );
				if ( $hcwa_cat ) {
					$hcwa_trail[] = array( $hcwa_cat->name, add_query_arg( 'category', $hcwa_cat->slug, hcwa_url( 'news' ) ) );
				}
				$hcwa_trail[] = array( hcwa_clean_title( get_the_title() ), '' );
				echo hcwa_breadcrumb( $hcwa_trail ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
				<p class="post__meta">
					<?php if ( $hcwa_cat ) : ?>
						<span class="label"><?php echo esc_html( $hcwa_cat->name ); ?></span>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( hcwa_date( get_post() ) ); ?></time>
				</p>
				<h1><?php echo esc_html( hcwa_clean_title( get_the_title() ) ); ?></h1>
				<?php if ( hcwa_page_intro() ) : ?>
					<p class="page-header__intro"<?php echo hcwa_draft_attr( hcwa_intro_is_draft() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( hcwa_page_intro() ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( has_post_thumbnail() && hcwa_image_allowed( get_post_thumbnail_id(), hcwa_is_story( get_post() ) ) ) : ?>
			<div class="container post__lead">
				<?php echo hcwa_image_slot( get_post_thumbnail_id(), array( 'ratio' => '3:2', 'eager' => true, 'strict' => hcwa_is_story( get_post() ), 'sizes' => '(min-width: 900px) 860px, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		<?php endif; ?>

		<div class="container">
			<div class="prose post__body">
				<?php the_content(); ?>
				<?php if ( hcwa_is_story( get_post() ) ) : ?>
					<p class="story-note"><?php hcwa_e( 'namesChanged' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="share" role="group" aria-label="<?php echo esc_attr( hcwa_t( 'share' ) ); ?>">
				<p class="share__title"><?php hcwa_e( 'share' ); ?></p>
				<a class="share__link" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $hcwa_url ) ); ?>" rel="noopener" target="_blank">Facebook<span class="sr-only"> (<?php hcwa_e( 'newWindow' ); ?>)</span></a>
				<a class="share__link" href="<?php echo esc_url( 'https://zalo.me/share?u=' . rawurlencode( $hcwa_url ) ); ?>" rel="noopener" target="_blank">Zalo<span class="sr-only"> (<?php hcwa_e( 'newWindow' ); ?>)</span></a>
				<button class="btn btn--small btn--secondary" type="button" data-copy="<?php echo esc_attr( $hcwa_url ); ?>"><?php echo hcwa_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php hcwa_e( 'copyLink' ); ?></span></button>
			</div>
		</div>
	</article>

	<?php
	$hcwa_related = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'post__not_in'   => array( get_the_ID() ),
			'cat'            => $hcwa_cat ? $hcwa_cat->term_id : 0,
			'lang'           => hcwa_lang(),
		)
	);
	if ( $hcwa_related ) :
		?>
		<section class="section section--tight" aria-labelledby="related-title">
			<div class="container">
				<h2 id="related-title"><?php hcwa_e( 'relatedNews' ); ?></h2>
				<ul class="news-list news-list--row">
					<?php
					foreach ( $hcwa_related as $hcwa_item ) {
						echo hcwa_news_item( $hcwa_item ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php
	echo hcwa_cta_band( // phpcs:ignore WordPress.Security.EscapeOutput
		hcwa_t( 'supportLikeThis' ),
		hcwa_button( hcwa_url( 'donate' ), hcwa_t( 'donate' ) ) . hcwa_button( hcwa_url( 'getInvolved' ), hcwa_t( 'getInvolved' ), 'on-blue' )
	);
endwhile;
get_footer();
