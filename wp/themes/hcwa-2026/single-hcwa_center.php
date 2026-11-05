<?php
/**
 * Center page (06-PAGES): unit band header, cover, "At a glance", our story (the part after
 * a More block goes into "Read the full history"), verified figures, approved photos,
 * programs run here, how to help, related news, contact band.
 *
 * Every image goes through hcwa_image(), which refuses unapproved photos and, for Little
 * Rose (hcwa_no_identifiable_children), any photo marked as showing an identifiable child.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post      = get_post();
	$hcwa_id        = $hcwa_post->ID;
	$hcwa_name      = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
	$hcwa_accent    = hcwa_theme_accent( $hcwa_id );
	$hcwa_since     = (int) get_post_meta( $hcwa_id, 'hcwa_since_year', true );
	$hcwa_protected = hcwa_theme_center_is_protected( $hcwa_id );
	$hcwa_location  = $hcwa_protected ? '' : (string) get_post_meta( $hcwa_id, 'hcwa_location', true );
	$hcwa_summary   = (string) get_post_meta( $hcwa_id, 'hcwa_summary', true );
	$hcwa_who       = (string) get_post_meta( $hcwa_id, 'hcwa_who_we_serve', true );
	$hcwa_what      = hcwa_theme_meta_lines( $hcwa_id, 'hcwa_what_we_do' );
	$hcwa_help      = (string) get_post_meta( $hcwa_id, 'hcwa_help_text', true );
	$hcwa_tag       = (string) get_post_meta( $hcwa_id, 'hcwa_news_tag', true );

	// Contact details only when HCWA approved them for the web.
	$hcwa_contact_ok    = (bool) get_post_meta( $hcwa_id, 'hcwa_contact_approved', true );
	$hcwa_contact_name  = $hcwa_contact_ok ? (string) get_post_meta( $hcwa_id, 'hcwa_contact_name', true ) : '';
	$hcwa_contact_phone = $hcwa_contact_ok ? (string) get_post_meta( $hcwa_id, 'hcwa_phone', true ) : '';
	$hcwa_contact_email = $hcwa_contact_ok ? (string) get_post_meta( $hcwa_id, 'hcwa_email', true ) : '';
	$hcwa_contact_email = is_email( $hcwa_contact_email ) ? $hcwa_contact_email : '';

	// Donate links carry the center (the VI original's slug, same in both languages).
	$hcwa_original = function_exists( 'pll_get_post' ) ? (int) pll_get_post( $hcwa_id, 'vi' ) : 0;
	$hcwa_center   = get_post_field( 'post_name', $hcwa_original > 0 ? $hcwa_original : $hcwa_id );
	$hcwa_donate   = add_query_arg( 'center', rawurlencode( (string) $hcwa_center ), hcwa_theme_url( 'donate' ) );

	$hcwa_logo = hcwa_theme_image(
		(int) get_post_meta( $hcwa_id, 'hcwa_logo_id', true ),
		'thumbnail',
		array(
			'class' => 'unit-logo',
			'alt'   => hcwa_theme_t( 'unitLogo', array( 'name' => $hcwa_name ) ),
		),
		$hcwa_post
	);

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'trail'   => array(
				array( hcwa_theme_t( 'ourWork' ), hcwa_theme_url( 'ourWork' ) ),
				array( hcwa_theme_t( 'centers' ), hcwa_theme_url( 'centers' ) ),
				array( $hcwa_name ),
			),
			'title'   => $hcwa_name,
			'vi_name' => hcwa_theme_vi_name( $hcwa_id ),
			'accent'  => $hcwa_accent,
			'intro'   => $hcwa_summary,
			'meta'    => static function () use ( $hcwa_logo, $hcwa_since, $hcwa_location ): void {
				if ( '' === $hcwa_logo && $hcwa_since <= 0 && '' === $hcwa_location ) {
					return;
				}
				?>
				<div class="page-header__meta">
					<?php echo $hcwa_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?>
					<p>
						<?php if ( $hcwa_since > 0 ) : ?>
							<span><?php hcwa_theme_e( 'since' ); ?> <?php echo esc_html( (string) $hcwa_since ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $hcwa_location ) : ?>
							<span><?php echo esc_html( $hcwa_location ); ?></span>
						<?php endif; ?>
					</p>
				</div>
				<?php
			},
		)
	);

	$hcwa_cover = hcwa_theme_image(
		(int) get_post_thumbnail_id(),
		'hcwa-1600',
		array(
			'class'         => 'cover__media',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'sizes'         => '(min-width: 1264px) 1200px, calc(100vw - 32px)',
		),
		$hcwa_post
	);
	if ( '' !== $hcwa_cover ) {
		echo '<div class="container cover">' . $hcwa_cover . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image().
		hcwa_theme_cover_printed();
	}

	$hcwa_story = hcwa_theme_split_content();
	?>
	<div class="container article-grid">
		<aside class="glance" aria-labelledby="glance-title">
			<h2 id="glance-title" class="h4"><?php hcwa_theme_e( 'atGlance' ); ?></h2>
			<dl>
				<?php if ( '' !== $hcwa_who ) : ?>
					<div><dt><?php hcwa_theme_e( 'whoWeServe' ); ?></dt><dd><?php echo esc_html( $hcwa_who ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $hcwa_what ) : ?>
					<div>
						<dt><?php hcwa_theme_e( 'whatWeDo' ); ?></dt>
						<dd><ul>
							<?php foreach ( $hcwa_what as $hcwa_line ) : ?>
								<li><?php echo esc_html( $hcwa_line ); ?></li>
							<?php endforeach; ?>
						</ul></dd>
					</div>
				<?php endif; ?>
				<?php if ( $hcwa_since > 0 ) : ?>
					<div><dt><?php hcwa_theme_e( 'since' ); ?></dt><dd><?php echo esc_html( (string) $hcwa_since ); ?></dd></div>
				<?php endif; ?>
				<?php if ( '' !== $hcwa_location ) : ?>
					<div><dt><?php hcwa_theme_e( 'location' ); ?></dt><dd><?php echo esc_html( $hcwa_location ); ?></dd></div>
				<?php endif; ?>
				<div>
					<dt><?php hcwa_theme_e( 'contact' ); ?></dt>
					<dd>
						<?php if ( '' !== $hcwa_contact_name ) : ?>
							<?php echo esc_html( $hcwa_contact_name ); ?><br>
						<?php endif; ?>
						<?php if ( '' !== $hcwa_contact_phone ) : ?>
							<a href="<?php echo esc_url( hcwa_theme_tel( $hcwa_contact_phone ), array( 'tel' ) ); ?>"><?php echo esc_html( $hcwa_contact_phone ); ?></a><br>
						<?php endif; ?>
						<?php if ( '' !== $hcwa_contact_email ) : ?>
							<a href="<?php echo esc_url( 'mailto:' . $hcwa_contact_email, array( 'mailto' ) ); ?>"><?php echo esc_html( $hcwa_contact_email ); ?></a>
						<?php endif; ?>
						<?php if ( '' === $hcwa_contact_phone && '' === $hcwa_contact_email ) : ?>
							<a href="<?php echo esc_url( hcwa_theme_url( 'contact' ) ); ?>"><?php hcwa_theme_e( 'contactUs' ); ?></a>
						<?php endif; ?>
					</dd>
				</div>
			</dl>
		</aside>

		<section class="prose" aria-labelledby="story-title">
			<h2 id="story-title"><?php hcwa_theme_e( 'centerStory' ); ?></h2>
			<div class="entry-content entry-content--prose">
				<?php echo $hcwa_story['main']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
			</div>
			<?php if ( '' !== $hcwa_story['extended'] ) : ?>
				<details class="more">
					<summary><?php hcwa_theme_e( 'fullHistory' ); ?><?php echo hcwa_theme_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></summary>
					<div class="entry-content entry-content--prose">
						<?php echo $hcwa_story['extended']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
					</div>
				</details>
			<?php endif; ?>
		</section>
	</div>

	<?php
	// Verified figures for this center; the block prints nothing when none may show.
	$hcwa_figures = hcwa_theme_block(
		'hcwa/impact-strip',
		array(
			'scopeType' => 'center',
			'scopeId'   => 0,
			'limit'     => 4,
			'showLink'  => false,
		)
	);
	if ( '' !== $hcwa_figures ) :
		?>
		<section class="section section--tight" aria-labelledby="center-figures">
			<div class="container">
				<h2 id="center-figures"><?php hcwa_theme_e( 'centerFigures', array( 'name' => $hcwa_name ) ); ?></h2>
				<?php echo $hcwa_figures; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
			</div>
		</section>
		<?php
	endif;

	// Gallery: approved photos only, in the page language (alt text comes from the translation).
	// The stored IDs are the Vietnamese originals: load them (their language terms answer the
	// translation lookup), then the photos in this language, before hcwa_image() reads each.
	$hcwa_gallery     = array();
	$hcwa_gallery_ids = hcwa_theme_meta_ids( $hcwa_id, 'hcwa_gallery_ids' );
	hcwa_theme_prime_ids( $hcwa_gallery_ids );
	$hcwa_gallery_ids = array_map( 'hcwa_theme_translated_id', $hcwa_gallery_ids );
	hcwa_theme_prime_ids( $hcwa_gallery_ids );
	foreach ( $hcwa_gallery_ids as $hcwa_att ) {
		$hcwa_img = hcwa_theme_image(
			$hcwa_att,
			'medium_large',
			array(
				'loading' => 'lazy',
				'sizes'   => '(min-width: 768px) 33vw, 50vw',
			),
			$hcwa_post
		);
		if ( '' !== $hcwa_img ) {
			$hcwa_gallery[] = array(
				'img'  => $hcwa_img,
				'full' => (string) wp_get_attachment_image_url( $hcwa_att, 'hcwa-1600' ),
				'alt'  => hcwa_theme_attachment_text( $hcwa_att, 'alt' ),
			);
		}
	}
	if ( $hcwa_gallery ) :
		?>
		<section class="section section--tight" aria-labelledby="gallery-title">
			<div class="container">
				<h2 id="gallery-title"><?php hcwa_theme_e( 'photos' ); ?></h2>
				<ul class="gallery" data-gallery>
					<?php foreach ( $hcwa_gallery as $hcwa_i => $hcwa_item ) : ?>
						<li>
							<button class="gallery__item" type="button" data-full="<?php echo esc_url( $hcwa_item['full'] ); ?>" data-alt="<?php echo esc_attr( $hcwa_item['alt'] ); ?>" aria-label="<?php echo esc_attr( trim( ( '' !== $hcwa_item['alt'] ? $hcwa_item['alt'] : hcwa_theme_t( 'photos' ) . ' ' . ( $hcwa_i + 1 ) ) . ': ' . hcwa_theme_t( 'openLarger' ) ) ); ?>">
								<?php echo $hcwa_item['img']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<dialog class="lightbox" data-lightbox aria-label="<?php echo esc_attr( hcwa_theme_t( 'photos' ) ); ?>">
			<div class="lightbox__stage" data-lightbox-stage></div>
			<p class="lightbox__count" data-lightbox-count aria-live="polite"></p>
			<button class="icon-btn lightbox__prev" type="button" data-lightbox-prev aria-label="<?php echo esc_attr( hcwa_theme_t( 'prevPhoto' ) ); ?>"><?php echo hcwa_theme_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
			<button class="icon-btn lightbox__next" type="button" data-lightbox-next aria-label="<?php echo esc_attr( hcwa_theme_t( 'nextPhoto' ) ); ?>"><?php echo hcwa_theme_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
			<button class="icon-btn lightbox__close" type="button" data-lightbox-close aria-label="<?php echo esc_attr( hcwa_theme_t( 'close' ) ); ?>"><?php echo hcwa_theme_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
		</dialog>
		<?php
	endif;

	// Programs run here (centerId 0 = this center).
	$hcwa_programs = hcwa_theme_block(
		'hcwa/program-list',
		array(
			'centerId' => 0,
			'status'   => 'any',
			'limit'    => 6,
		)
	);
	if ( '' !== $hcwa_programs ) :
		?>
		<section class="section section--tight" aria-labelledby="center-programs">
			<div class="container">
				<h2 id="center-programs"><?php hcwa_theme_e( 'programsHere' ); ?></h2>
				<?php echo $hcwa_programs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section--sunken section--tight" aria-labelledby="center-help">
		<div class="container split">
			<div class="split__lead">
				<h2 id="center-help"><?php hcwa_theme_e( 'helpCenter', array( 'name' => $hcwa_name ) ); ?></h2>
				<?php
				if ( '' !== trim( $hcwa_help ) ) {
					echo wp_kses_post( wpautop( esc_html( $hcwa_help ) ) );
				}
				?>
			</div>
			<div class="stack">
				<?php
				echo hcwa_theme_button( $hcwa_donate, hcwa_theme_t( 'donateTo', array( 'name' => $hcwa_name ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
				echo hcwa_theme_text_link( hcwa_theme_url( 'volunteer' ), hcwa_theme_t( 'volunteer' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
				if ( 'little-rose' === $hcwa_accent ) {
					echo hcwa_theme_text_link( hcwa_theme_url( 'bakery' ), hcwa_theme_t( 'bakery' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
				}
				?>
			</div>
		</div>
	</section>

	<?php
	get_template_part( 'template-parts/related-news', null, array( 'posts' => hcwa_theme_tagged_posts( $hcwa_tag, 3 ) ) );

	$hcwa_contact_lines = array();
	if ( '' !== $hcwa_contact_phone ) {
		$hcwa_contact_lines[] = array( 'phone', hcwa_theme_tel( $hcwa_contact_phone ), $hcwa_contact_phone );
	}
	if ( '' !== $hcwa_contact_email ) {
		$hcwa_contact_lines[] = array( 'mail', 'mailto:' . $hcwa_contact_email, $hcwa_contact_email );
	}
	get_template_part(
		'template-parts/cta-band',
		null,
		array(
			// A protected shelter is reached through the HCWA office, never by visiting.
			'title'   => $hcwa_protected
				? hcwa_theme_t( 'supportTitle', array( 'name' => $hcwa_name ) )
				: hcwa_theme_t( 'visitTitle', array( 'name' => $hcwa_name ) ),
			'actions' => array( array( hcwa_theme_url( 'contact' ), hcwa_theme_t( 'contactUs' ), 'on-blue' ) ),
			'contact' => $hcwa_contact_lines,
		)
	);
endwhile;

get_footer();
