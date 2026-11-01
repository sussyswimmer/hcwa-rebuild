<?php
/**
 * Program page (06-PAGES "Program page template"): status, years, H1, partners; summary;
 * goals, activities, verified results; where it runs and who funds it; documents; news; CTA.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$hcwa_post     = get_post();
	$hcwa_key      = hcwa_meta( $hcwa_post, 'hcwa_key' );
	$hcwa_draft    = hcwa_is_draft( $hcwa_post );
	$hcwa_years    = hcwa_program_years( $hcwa_post );
	$hcwa_partners = array_filter( array_map( 'get_post', hcwa_meta( $hcwa_post, 'hcwa_partners' ) ) );
	$hcwa_vi       = function_exists( 'pll_get_post' ) && pll_get_post( get_the_ID(), 'vi' ) ? hcwa_short_name( pll_get_post( get_the_ID(), 'vi' ) ) : '';

	$hcwa_meta = '<div class="page-header__meta">' . hcwa_status_pill( hcwa_meta( $hcwa_post, 'hcwa_status' ) );
	if ( $hcwa_years ) {
		$hcwa_meta .= '<p><span' . hcwa_draft_attr( $hcwa_years['draft'] ) . '>' . esc_html( $hcwa_years['text'] ) . '</span></p>';
	}
	if ( $hcwa_partners ) {
		$hcwa_meta .= '<ul class="partner-inline" aria-label="' . esc_attr( hcwa_t( 'fundedBy' ) ) . '">';
		foreach ( $hcwa_partners as $hcwa_partner ) {
			$hcwa_meta .= hcwa_partner_logo( $hcwa_partner );
		}
		$hcwa_meta .= '</ul>';
	}
	$hcwa_meta .= '</div>';

	echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
		array(
			'trail'   => array( array( hcwa_t( 'ourWork' ), hcwa_url( 'ourWork' ) ), array( hcwa_t( 'programs' ), hcwa_url( 'programs' ) ), array( hcwa_short_name( $hcwa_post ), '' ) ),
			'title'   => get_the_title(),
			'vi_name' => $hcwa_vi,
			'extra'   => $hcwa_meta,
		)
	);

	$hcwa_list = function ( $items ) {
		$out = '<ul class="check-list">';
		foreach ( $items as $item ) {
			$out .= '<li>' . hcwa_icon( 'check' ) . '<span>' . esc_html( $item ) . '</span></li>';
		}
		return $out . '</ul>';
	};
	$hcwa_goals      = hcwa_meta( $hcwa_post, 'hcwa_goals' );
	$hcwa_activities = hcwa_meta( $hcwa_post, 'hcwa_activities' );
	$hcwa_centers    = array_filter(
		array_map(
			function ( $key ) {
				return hcwa_get_by_key( 'hcwa_center', $key );
			},
			hcwa_meta( $hcwa_post, 'hcwa_centers' )
		)
	);
	?>
	<div class="container article-grid article-grid--wide">
		<section class="prose" aria-labelledby="program-summary">
			<h2 id="program-summary" class="sr-only"><?php hcwa_e( 'summary' ); ?></h2>
			<?php if ( has_excerpt() ) : ?>
				<p class="lead"<?php echo hcwa_draft_attr( $hcwa_draft ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<?php the_content(); ?>
			<?php if ( $hcwa_goals || $hcwa_activities ) : ?>
				<div class="two-col"<?php echo hcwa_draft_attr( $hcwa_draft ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php if ( $hcwa_goals ) : ?>
						<div><h2 class="h3"><?php hcwa_e( 'goals' ); ?></h2><?php echo $hcwa_list( $hcwa_goals ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<?php if ( $hcwa_activities ) : ?>
						<div><h2 class="h3"><?php hcwa_e( 'activities' ); ?></h2><?php echo $hcwa_list( $hcwa_activities ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php if ( $hcwa_centers || $hcwa_partners ) : ?>
			<aside class="glance" aria-labelledby="program-where">
				<h2 id="program-where" class="h4"><?php hcwa_e( 'whereFunds' ); ?></h2>
				<dl>
					<?php if ( $hcwa_centers ) : ?>
						<div><dt><?php hcwa_e( 'centers' ); ?></dt><dd><ul class="plain-list">
							<?php foreach ( $hcwa_centers as $hcwa_center ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $hcwa_center ) ); ?>"><?php echo esc_html( get_the_title( $hcwa_center ) ); ?></a></li>
							<?php endforeach; ?>
						</ul></dd></div>
					<?php endif; ?>
					<?php if ( $hcwa_partners ) : ?>
						<div><dt><?php hcwa_e( 'fundedBy' ); ?></dt><dd><?php echo esc_html( implode( ', ', wp_list_pluck( $hcwa_partners, 'post_title' ) ) ); ?></dd></div>
					<?php endif; ?>
				</dl>
			</aside>
		<?php endif; ?>
	</div>

	<?php
	$hcwa_figures = $hcwa_key ? hcwa_figure_strip( $hcwa_key ) : '';
	if ( $hcwa_figures ) :
		?>
		<section class="section section--tight" aria-labelledby="program-results">
			<div class="container">
				<h2 id="program-results"><?php hcwa_e( 'results' ); ?></h2>
				<?php echo $hcwa_figures; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$hcwa_reports = $hcwa_key ? hcwa_get_reports( array( 'program' => $hcwa_key, 'count' => 10 ) ) : null;
	if ( $hcwa_reports && $hcwa_reports->have_posts() ) :
		?>
		<section class="section section--tight" aria-labelledby="program-docs">
			<div class="container">
				<h2 id="program-docs"><?php hcwa_e( 'documents' ); ?></h2>
				<ul class="report-list">
					<?php
					foreach ( $hcwa_reports->posts as $hcwa_report ) {
						echo hcwa_report_row( $hcwa_report ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$hcwa_tag  = hcwa_meta( $hcwa_post, 'hcwa_news_tag' );
	$hcwa_news = $hcwa_tag ? get_posts( array( 'post_type' => 'post', 'posts_per_page' => 3, 'tag' => $hcwa_tag, 'lang' => hcwa_lang() ) ) : array();
	if ( $hcwa_news ) :
		?>
		<section class="section section--tight" aria-labelledby="program-news">
			<div class="container">
				<h2 id="program-news"><?php hcwa_e( 'relatedNews' ); ?></h2>
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
	$hcwa_actions = hcwa_button( hcwa_url( 'donate' ), hcwa_t( 'donate' ) ) . hcwa_button( hcwa_url( 'partner' ), hcwa_t( 'partnerOnProgram' ), 'on-blue' );
	echo hcwa_cta_band( hcwa_t( 'supportLikeThis' ), $hcwa_actions ); // phpcs:ignore WordPress.Security.EscapeOutput
endwhile;
get_footer();
