<?php
/**
 * One report: header with year, type and language badge, summary, body, download button.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$hcwa_post    = get_post();
	$hcwa_info    = hcwa_theme_report_info( $hcwa_post );
	$hcwa_title   = hcwa_theme_clean_title( wp_strip_all_tags( get_the_title() ) );
	$hcwa_summary = (string) get_post_meta( $hcwa_post->ID, 'hcwa_summary', true );
	$hcwa_archive = (string) get_post_type_archive_link( 'hcwa_report' );

	get_template_part(
		'template-parts/page-header',
		null,
		array(
			'trail' => array(
				array( hcwa_theme_t( 'impact' ), hcwa_theme_url( 'impact' ) ),
				array( hcwa_theme_t( 'reportsTitle' ), '' !== $hcwa_archive ? $hcwa_archive : hcwa_theme_url( 'reports' ) ),
				array( $hcwa_title ),
			),
			'title' => $hcwa_title,
			'intro' => $hcwa_summary,
			'meta'  => static function () use ( $hcwa_info ): void {
				if ( '' === $hcwa_info['meta'] ) {
					return;
				}
				?>
				<div class="page-header__meta">
					<p><span><?php echo esc_html( $hcwa_info['meta'] ); ?></span></p>
				</div>
				<?php
			},
		)
	);

	$hcwa_cover = hcwa_theme_image(
		(int) get_post_thumbnail_id(),
		'medium',
		array(
			'class' => 'report-cover',
			'alt'   => '',
		),
		$hcwa_post
	);
	?>
	<div class="container article-grid article-grid--wide">
		<div class="prose">
			<div class="entry-content entry-content--prose">
				<?php the_content(); ?>
			</div>
			<?php if ( '' !== $hcwa_info['file_url'] ) : ?>
				<p class="stack">
					<a class="btn btn--primary" href="<?php echo esc_url( $hcwa_info['file_url'] ); ?>" download>
						<?php echo hcwa_theme_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span><?php hcwa_theme_e( 'download' ); ?></span>
					</a>
					<?php hcwa_theme_report_badge( $hcwa_info ); ?>
					<?php if ( '' !== $hcwa_info['size'] ) : ?>
						<span class="report-row__size"><?php echo esc_html( $hcwa_info['size'] ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $hcwa_cover ) : ?>
			<div class="report-cover-wrap"><?php echo $hcwa_cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by hcwa_image(). ?></div>
		<?php endif; ?>
	</div>
	<?php
endwhile;

get_footer();
