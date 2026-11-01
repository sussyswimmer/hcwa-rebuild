<?php
/**
 * Center page (06-PAGES "Center page template"): unit band header, cover, at a glance, story,
 * figures, gallery, programs run here, how to help, related news, contact + CTA band.
 * Pages marked "no identifiable children" (Little Rose) refuse such images everywhere.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$hcwa_post   = get_post();
	$hcwa_key    = hcwa_meta( $hcwa_post, 'hcwa_key' );
	$hcwa_short  = hcwa_short_name( $hcwa_post );
	$hcwa_draft  = hcwa_is_draft( $hcwa_post );
	$hcwa_strict = hcwa_meta( $hcwa_post, 'hcwa_illustration_only' );
	$hcwa_since  = hcwa_since_year( $hcwa_post );
	$hcwa_vi     = function_exists( 'pll_get_post' ) && pll_get_post( get_the_ID(), 'vi' ) ? get_the_title( pll_get_post( get_the_ID(), 'vi' ) ) : '';
	$hcwa_logo   = hcwa_meta( $hcwa_post, 'hcwa_unit_logo' );

	// Header meta row: unit logo, since-year, location.
	$hcwa_meta = '<div class="page-header__meta">';
	if ( $hcwa_logo ) {
		// A unit logo is a mark, not a photo: no approval needed.
		$hcwa_meta .= wp_get_attachment_image( $hcwa_logo, 'thumbnail', false, array( 'class' => 'unit-logo', 'alt' => hcwa_t( 'unitLogo', array( 'x' => get_the_title() ) ) ) );
	}
	$hcwa_meta .= '<p>';
	if ( $hcwa_since ) {
		$hcwa_meta .= '<span' . hcwa_draft_attr( $hcwa_since['draft'] ) . '>' . esc_html( hcwa_t( 'since' ) . ' ' . $hcwa_since['text'] ) . '</span>';
	}
	if ( hcwa_meta( $hcwa_post, 'hcwa_location' ) ) {
		$hcwa_meta .= '<span' . hcwa_draft_attr( $hcwa_draft ) . '>' . esc_html( hcwa_meta( $hcwa_post, 'hcwa_location' ) ) . '</span>';
	}
	$hcwa_meta .= '</p></div>';

	echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
		array(
			'trail'   => array( array( hcwa_t( 'ourWork' ), hcwa_url( 'ourWork' ) ), array( hcwa_t( 'centers' ), hcwa_url( 'centers' ) ), array( get_the_title(), '' ) ),
			'title'   => get_the_title(),
			'vi_name' => $hcwa_vi,
			'accent'  => hcwa_meta( $hcwa_post, 'hcwa_accent' ),
			'extra'   => $hcwa_meta,
		)
	);
	?>
	<div class="container cover">
		<?php
		echo hcwa_image_slot( // phpcs:ignore WordPress.Security.EscapeOutput
			get_post_thumbnail_id(),
			array(
				'code'         => $hcwa_strict ? 'B2' : 'B1',
				'label'        => $hcwa_strict ? array( 'vi' => 'Tranh minh họa', 'en' => 'Illustration' ) : array( 'vi' => 'Ảnh bìa cơ sở', 'en' => 'Center cover' ),
				'ratio'        => '16:9',
				'class'        => 'cover__media',
				'strict'       => (bool) $hcwa_strict,
				'illustration' => (bool) $hcwa_strict,
				'eager'        => true,
				'sizes'        => '(min-width: 1200px) 1200px, 100vw',
			)
		);
		?>
	</div>

	<div class="container article-grid">
		<aside class="glance" aria-labelledby="glance-title"<?php echo hcwa_draft_attr( $hcwa_draft ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<h2 id="glance-title" class="h4"><?php hcwa_e( 'atGlance' ); ?></h2>
			<dl>
				<?php if ( hcwa_meta( $hcwa_post, 'hcwa_serves' ) ) : ?>
					<div><dt><?php hcwa_e( 'whoWeServe' ); ?></dt><dd><?php echo esc_html( hcwa_meta( $hcwa_post, 'hcwa_serves' ) ); ?></dd></div>
				<?php endif; ?>
				<?php if ( hcwa_meta( $hcwa_post, 'hcwa_activities' ) ) : ?>
					<div><dt><?php hcwa_e( 'whatWeDo' ); ?></dt><dd><ul>
						<?php foreach ( hcwa_meta( $hcwa_post, 'hcwa_activities' ) as $hcwa_line ) : ?>
							<li><?php echo esc_html( $hcwa_line ); ?></li>
						<?php endforeach; ?>
					</ul></dd></div>
				<?php endif; ?>
				<?php if ( $hcwa_since ) : ?>
					<div><dt><?php hcwa_e( 'since' ); ?></dt><dd<?php echo hcwa_draft_attr( $hcwa_since['draft'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $hcwa_since['text'] ); ?></dd></div>
				<?php endif; ?>
				<?php
				$hcwa_contact_lines = hcwa_meta( $hcwa_post, 'hcwa_contact' );
				if ( $hcwa_contact_lines && hcwa_meta( $hcwa_post, 'hcwa_contact_approved' ) ) :
					?>
					<div><dt><?php hcwa_e( 'contact' ); ?></dt><dd><?php echo wp_kses_post( implode( '<br>', array_map( 'esc_html', $hcwa_contact_lines ) ) ); ?></dd></div>
				<?php elseif ( hcwa_review_mode() ) : ?>
					<div data-draft><dt><?php hcwa_e( 'contact' ); ?></dt><dd><?php echo esc_html( 'en' === hcwa_lang() ? '[Approved contact person and phone]' : '[Người liên hệ và số điện thoại đã được duyệt]' ); ?></dd></div>
				<?php endif; ?>
			</dl>
		</aside>
		<section class="prose" aria-labelledby="story-title">
			<h2 id="story-title"><?php hcwa_e( 'ourStoryHeading' ); ?></h2>
			<?php the_content(); ?>
		</section>
	</div>

	<?php
	$hcwa_figures = $hcwa_key ? hcwa_figure_strip( $hcwa_key ) : '';
	if ( $hcwa_figures ) :
		?>
		<section class="section section--tight" aria-labelledby="unit-figures">
			<div class="container">
				<h2 id="unit-figures"><?php echo esc_html( hcwa_t( 'figuresAt', array( 'x' => $hcwa_short ) ) ); ?></h2>
				<?php echo $hcwa_figures; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Gallery: approved photos only; on strict pages also no identifiable child. Review mode
	// shows the labeled slots from the visuals list: six B3 activity photos, or for Little Rose
	// three B4 bakery photos (products, the space, hands; never residents' faces).
	$hcwa_gallery = array_values(
		array_filter(
			hcwa_meta( $hcwa_post, 'hcwa_gallery' ),
			function ( $id ) use ( $hcwa_strict ) {
				return hcwa_image_allowed( $id, (bool) $hcwa_strict );
			}
		)
	);
	$hcwa_slots   = $hcwa_gallery ? $hcwa_gallery : ( hcwa_review_mode() ? array_fill( 0, $hcwa_strict ? 3 : 6, 0 ) : array() );
	if ( $hcwa_slots ) :
		?>
		<section class="section section--tight" aria-labelledby="gallery-title">
			<div class="container">
				<h2 id="gallery-title"><?php hcwa_e( 'photos' ); ?></h2>
				<ul class="gallery" data-gallery>
					<?php foreach ( $hcwa_slots as $hcwa_i => $hcwa_id ) : ?>
						<?php
						$hcwa_alt = $hcwa_id ? get_post_meta( $hcwa_id, '_wp_attachment_image_alt', true ) : hcwa_t( 'photoN', array( 'n' => $hcwa_i + 1 ) );
						?>
						<li><button class="gallery__item" type="button" data-index="<?php echo (int) $hcwa_i; ?>" aria-label="<?php echo esc_attr( ( $hcwa_alt ? $hcwa_alt : hcwa_t( 'photoN', array( 'n' => $hcwa_i + 1 ) ) ) . ': ' . hcwa_t( 'openLarger' ) ); ?>">
							<?php
							echo hcwa_image_slot( // phpcs:ignore WordPress.Security.EscapeOutput
								$hcwa_id,
								array(
									'code'   => $hcwa_strict ? 'B4' : 'B3',
									'label'  => $hcwa_strict ? array( 'vi' => 'Ảnh tiệm bánh ' . ( $hcwa_i + 1 ), 'en' => 'Bakery photo ' . ( $hcwa_i + 1 ) ) : array( 'vi' => 'Ảnh hoạt động ' . ( $hcwa_i + 1 ), 'en' => 'Activity photo ' . ( $hcwa_i + 1 ) ),
									'ratio'  => '3:2',
									'strict' => (bool) $hcwa_strict,
									'sizes'  => '(min-width: 768px) 380px, 50vw',
								)
							);
							?>
						</button></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<dialog class="lightbox" data-lightbox aria-label="<?php echo esc_attr( hcwa_t( 'photos' ) ); ?>">
			<div class="lightbox__stage" data-lightbox-stage></div>
			<p class="lightbox__count" data-lightbox-count aria-live="polite"></p>
			<button class="icon-btn lightbox__prev" type="button" data-lightbox-prev aria-label="<?php echo esc_attr( hcwa_t( 'prevPhoto' ) ); ?>"><?php echo hcwa_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<button class="icon-btn lightbox__next" type="button" data-lightbox-next aria-label="<?php echo esc_attr( hcwa_t( 'nextPhoto' ) ); ?>"><?php echo hcwa_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<button class="icon-btn lightbox__close" type="button" data-lightbox-close aria-label="<?php echo esc_attr( hcwa_t( 'close' ) ); ?>"><?php echo hcwa_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</dialog>
	<?php endif; ?>

	<?php
	$hcwa_programs = $hcwa_key ? hcwa_get_programs( array( 'center' => $hcwa_key, 'count' => 10 ) ) : array();
	if ( $hcwa_programs ) :
		?>
		<section class="section section--tight" aria-labelledby="unit-programs">
			<div class="container">
				<h2 id="unit-programs"><?php hcwa_e( 'programsHere' ); ?></h2>
				<ul class="program-list">
					<?php
					foreach ( $hcwa_programs as $hcwa_program ) {
						echo hcwa_program_row( $hcwa_program ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section--sunken section--tight" aria-labelledby="unit-help">
		<div class="container split">
			<div class="split__lead">
				<h2 id="unit-help"><?php echo esc_html( hcwa_t( 'helpCenter', array( 'x' => $hcwa_short ) ) ); ?></h2>
				<?php if ( hcwa_meta( $hcwa_post, 'hcwa_help' ) ) : ?>
					<p<?php echo hcwa_draft_attr( $hcwa_draft ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( hcwa_meta( $hcwa_post, 'hcwa_help' ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="stack">
				<?php echo hcwa_button( add_query_arg( 'center', $hcwa_key, hcwa_url( 'donate' ) ), hcwa_t( 'donateTo', array( 'x' => $hcwa_short ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo hcwa_text_link( hcwa_url( 'volunteer' ), hcwa_t( 'volunteer' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( $hcwa_strict ) : ?>
					<?php echo hcwa_text_link( hcwa_url( 'bakery' ), hcwa_t( 'bakery' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	// Related news needs a tag: without one, "related" would just be the latest posts.
	$hcwa_tag  = hcwa_meta( $hcwa_post, 'hcwa_news_tag' );
	$hcwa_news = $hcwa_tag ? get_posts( array( 'post_type' => 'post', 'posts_per_page' => 3, 'tag' => $hcwa_tag, 'lang' => hcwa_lang() ) ) : array();
	if ( $hcwa_news ) :
		?>
		<section class="section section--tight" aria-labelledby="unit-news">
			<div class="container">
				<h2 id="unit-news"><?php hcwa_e( 'relatedNews' ); ?></h2>
				<ul class="news-list news-list--row">
					<?php
					foreach ( $hcwa_news as $hcwa_item ) {
						echo hcwa_news_item( $hcwa_item ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php
	echo hcwa_cta_band( // phpcs:ignore WordPress.Security.EscapeOutput
		hcwa_t( 'visitCenterCta', array( 'x' => $hcwa_short ) ),
		hcwa_button( hcwa_url( 'contact' ), hcwa_t( 'contactUs' ), 'on-blue' )
	);
endwhile;
get_footer();
