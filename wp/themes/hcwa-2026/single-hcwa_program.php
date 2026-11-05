<?php
/**
 * Program page (06-PAGES): status pill, years, H1, funders; summary and body; goals and
 * activities; verified results; where it runs and who funds it; related news; CTA band.
 *
 * Program documents: the contract has no report-to-program link yet, so editors add a
 * Report list block to the program body if they want one (see the theme report).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post    = get_post();
	$hcwa_id      = $hcwa_post->ID;
	$hcwa_name    = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
	$hcwa_summary = (string) get_post_meta( $hcwa_id, 'hcwa_summary', true );
	$hcwa_status  = (string) get_post_meta( $hcwa_id, 'hcwa_status', true );
	$hcwa_status  = in_array( $hcwa_status, array( 'active', 'completed', 'planned' ), true ) ? $hcwa_status : '';
	$hcwa_years   = hcwa_theme_year_range( (int) get_post_meta( $hcwa_id, 'hcwa_start_year', true ), (int) get_post_meta( $hcwa_id, 'hcwa_end_year', true ) );
	$hcwa_goals   = hcwa_theme_meta_lines( $hcwa_id, 'hcwa_goals' );
	$hcwa_acts    = hcwa_theme_meta_lines( $hcwa_id, 'hcwa_activities' );
	$hcwa_tag     = (string) get_post_meta( $hcwa_id, 'hcwa_news_tag', true );

	// Linked center (sync meta stores the VI original).
	$hcwa_center_id = (int) get_post_meta( $hcwa_id, 'hcwa_center_id', true );
	$hcwa_center_id = $hcwa_center_id > 0 ? hcwa_theme_translated_id( $hcwa_center_id ) : 0;
	$hcwa_center    = $hcwa_center_id > 0 ? get_post( $hcwa_center_id ) : null;
	$hcwa_center    = ( $hcwa_center instanceof WP_Post && 'publish' === $hcwa_center->post_status ) ? $hcwa_center : null;

	// Funders: names always; a small one-tone logo only where hcwa-core allows it (contract
	// rule 5, the same gate as the hcwa/partner-wall block: written permission on the live site).
	$hcwa_partners    = array();
	$hcwa_partner_ids = hcwa_theme_meta_ids( $hcwa_id, 'hcwa_partner_ids' );
	hcwa_theme_prime_ids( $hcwa_partner_ids ); // Originals with their language terms, then the translations and their logos.
	$hcwa_partner_ids = array_map( 'hcwa_theme_translated_id', $hcwa_partner_ids );
	hcwa_theme_prime_ids( $hcwa_partner_ids );
	hcwa_theme_prime_attachments( $hcwa_partner_ids );
	foreach ( $hcwa_partner_ids as $hcwa_partner_id ) {
		$hcwa_partner = get_post( $hcwa_partner_id );
		if ( ! $hcwa_partner instanceof WP_Post || 'publish' !== $hcwa_partner->post_status ) {
			continue;
		}
		$hcwa_partner_name = wp_strip_all_tags( get_the_title( $hcwa_partner ) );
		$hcwa_logo_id      = (int) get_post_thumbnail_id( $hcwa_partner );
		$hcwa_logo         = '';
		if ( $hcwa_logo_id > 0 && function_exists( 'hcwa_partner_logo_allowed' ) && hcwa_partner_logo_allowed( $hcwa_partner->ID ) ) {
			$hcwa_logo = (string) wp_get_attachment_image(
				$hcwa_logo_id,
				'medium',
				false,
				array(
					'class'   => 'partner-logo',
					'alt'     => $hcwa_partner_name,
					'loading' => 'lazy',
				)
			);
		}
		$hcwa_partners[] = array(
			'name' => $hcwa_partner_name,
			'logo' => $hcwa_logo,
		);
	}

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'trail'   => array(
				array( hcwa_theme_t( 'ourWork' ), hcwa_theme_url( 'ourWork' ) ),
				array( hcwa_theme_t( 'programs' ), hcwa_theme_url( 'programs' ) ),
				array( $hcwa_name ),
			),
			'title'   => $hcwa_name,
			'vi_name' => hcwa_theme_vi_name( $hcwa_id ),
			'meta'    => static function () use ( $hcwa_status, $hcwa_years, $hcwa_partners ): void {
				$hcwa_logos = array_filter( wp_list_pluck( $hcwa_partners, 'logo' ) );
				if ( '' === $hcwa_status && '' === $hcwa_years && ! $hcwa_logos ) {
					return;
				}
				?>
				<div class="page-header__meta">
					<?php if ( '' !== $hcwa_status ) : ?>
						<span class="status status--<?php echo esc_attr( $hcwa_status ); ?>"><?php hcwa_theme_e( 'status_' . $hcwa_status ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $hcwa_years ) : ?>
						<p><span><?php echo esc_html( $hcwa_years ); ?></span></p>
					<?php endif; ?>
					<?php if ( $hcwa_logos ) : ?>
						<span class="partner-logos">
							<?php
							foreach ( $hcwa_logos as $hcwa_logo ) {
								echo $hcwa_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by wp_get_attachment_image() (escaped).
							}
							?>
						</span>
					<?php endif; ?>
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

	$hcwa_content = str_replace( ']]>', ']]&gt;', (string) apply_filters( 'the_content', get_the_content() ) );
	?>
	<div class="container article-grid article-grid--wide">
		<section class="prose" aria-labelledby="program-summary">
			<h2 id="program-summary" class="sr-only"><?php hcwa_theme_e( 'summary' ); ?></h2>
			<?php if ( '' !== $hcwa_summary ) : ?>
				<p class="lead"><?php echo esc_html( $hcwa_summary ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $hcwa_content ) ) ) : ?>
				<div class="entry-content entry-content--prose">
					<?php echo $hcwa_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
				</div>
			<?php endif; ?>
			<?php if ( $hcwa_goals || $hcwa_acts ) : ?>
				<div class="two-col">
					<?php foreach ( array( 'goals' => $hcwa_goals, 'activities' => $hcwa_acts ) as $hcwa_key => $hcwa_items ) : ?>
						<?php if ( $hcwa_items ) : ?>
							<div>
								<h2 class="h3"><?php hcwa_theme_e( $hcwa_key ); ?></h2>
								<ul class="check-list">
									<?php foreach ( $hcwa_items as $hcwa_item ) : ?>
										<li><?php echo hcwa_theme_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php echo esc_html( $hcwa_item ); ?></span></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( $hcwa_center || $hcwa_partners ) : ?>
			<aside class="glance" aria-labelledby="program-where">
				<h2 id="program-where" class="h4"><?php hcwa_theme_e( 'whereAndWho' ); ?></h2>
				<dl>
					<?php if ( $hcwa_center ) : ?>
						<div><dt><?php hcwa_theme_e( 'runsAt' ); ?></dt><dd><a href="<?php echo esc_url( get_permalink( $hcwa_center ) ); ?>"><?php echo esc_html( wp_strip_all_tags( get_the_title( $hcwa_center ) ) ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $hcwa_partners ) : ?>
						<div><dt><?php hcwa_theme_e( 'fundedBy' ); ?></dt><dd><?php echo esc_html( implode( ', ', wp_list_pluck( $hcwa_partners, 'name' ) ) ); ?></dd></div>
					<?php endif; ?>
				</dl>
			</aside>
		<?php endif; ?>
	</div>

	<?php
	$hcwa_figures = hcwa_theme_block(
		'hcwa/impact-strip',
		array(
			'scopeType' => 'program',
			'scopeId'   => 0,
			'limit'     => 4,
			'showLink'  => false,
		)
	);
	if ( '' !== $hcwa_figures ) :
		?>
		<section class="section section--tight" aria-labelledby="program-results">
			<div class="container">
				<h2 id="program-results"><?php hcwa_theme_e( 'results' ); ?></h2>
				<?php echo $hcwa_figures; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
			</div>
		</section>
		<?php
	endif;

	get_template_part( 'template-parts/related-news', null, array( 'posts' => hcwa_theme_tagged_posts( $hcwa_tag, 3 ) ) );

	get_template_part(
		'template-parts/cta-band',
		null,
		array(
			'title'   => hcwa_theme_t( 'supportWork' ),
			'actions' => array(
				array( hcwa_theme_url( 'donate' ), hcwa_theme_t( 'donate' ), 'primary' ),
				array( hcwa_theme_url( 'partner' ), hcwa_theme_t( 'partnerProgram' ), 'on-blue' ),
			),
		)
	);
endwhile;

get_footer();
