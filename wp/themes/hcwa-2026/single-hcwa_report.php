<?php
/**
 * One report: cover, summary, download with language badge, link back to the library.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$hcwa_post    = get_post();
	$hcwa_file    = hcwa_meta( $hcwa_post, 'hcwa_file' );
	$hcwa_url     = $hcwa_file ? wp_get_attachment_url( $hcwa_file ) : '';
	$hcwa_program = hcwa_meta( $hcwa_post, 'hcwa_program' ) ? hcwa_get_by_key( 'hcwa_program', hcwa_meta( $hcwa_post, 'hcwa_program' ) ) : null;
	$hcwa_meta    = array_filter( array( (string) hcwa_meta( $hcwa_post, 'hcwa_year' ), hcwa_report_type_label( hcwa_meta( $hcwa_post, 'hcwa_type' ) ) ) );

	echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
		array(
			'trail'       => array( array( hcwa_t( 'impact' ), hcwa_url( 'impact' ) ), array( hcwa_t( 'reportsTitle' ), hcwa_reports_url() ), array( get_the_title(), '' ) ),
			'title'       => get_the_title(),
			'intro'       => has_excerpt() ? get_the_excerpt() : '',
			'intro_draft' => hcwa_is_draft( $hcwa_post ),
			'extra'       => '<div class="page-header__meta"><p>' . esc_html( implode( ' · ', $hcwa_meta ) ) . '</p>' . hcwa_doc_lang_badge( hcwa_meta( $hcwa_post, 'hcwa_doc_lang' ) ) . '</div>',
		)
	);
	?>
	<section class="section section--tight">
		<div class="container report-single">
			<?php echo hcwa_image_slot( get_post_thumbnail_id(), array( 'code' => 'C3', 'label' => array( 'vi' => 'Bìa báo cáo', 'en' => 'Report cover' ), 'ratio' => '3:4', 'class' => 'report-single__cover', 'sizes' => '280px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="stack">
				<?php if ( $hcwa_url ) : ?>
					<a class="btn btn--primary" href="<?php echo esc_url( $hcwa_url ); ?>"><?php echo hcwa_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php hcwa_e( 'download' ); ?></span></a>
				<?php endif; ?>
				<?php if ( $hcwa_program ) : ?>
					<?php echo hcwa_text_link( get_permalink( $hcwa_program ), hcwa_short_name( $hcwa_program ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<?php echo hcwa_text_link( hcwa_reports_url(), hcwa_t( 'allReports' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
