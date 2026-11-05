<?php
/**
 * hcwa/impact-strip: verified figures for the organization, a center or a program
 * (contract gating rule 1). Markup: prototype figureStrip() inside the home page's
 * .impact-strip panel, with the "See our impact and reports" link.
 *
 * Live site: only verified figures, and nothing at all with fewer than 2 of them.
 * Staging: every figure; unverified ones carry the "Pending verification" chip.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_scope      = hcwa_blocks_enum( $attributes, 'scopeType', array( 'organization', 'center', 'program' ), 'organization' );
$hcwa_scope_id   = hcwa_blocks_int( $attributes, 'scopeId', 0, 0, PHP_INT_MAX );
$hcwa_limit      = hcwa_blocks_int( $attributes, 'limit', 4, 1, 12 );
$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();

// 0 = the post the block is on (a center or program page).
if ( 'organization' !== $hcwa_scope && 0 === $hcwa_scope_id ) {
	$hcwa_scope_id = hcwa_blocks_context_post_id( $block ?? null );
}

$hcwa_figures = array();
if ( 'organization' === $hcwa_scope || $hcwa_scope_id > 0 ) {
	$hcwa_figures = hcwa_get_impact_figures(
		array(
			'scope_type' => $hcwa_scope,
			'scope_id'   => $hcwa_scope_id,
			'limit'      => $hcwa_limit,
		)
	);
}

if ( ! hcwa_impact_strip_visible( $hcwa_figures, $hcwa_production ) ) {
	echo hcwa_blocks_empty_note( 'blk_noFigures' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}

$hcwa_verified = count( array_filter( $hcwa_figures, static fn( $f ) => ! empty( $f['verified'] ) ) );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'impact-strip' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<div class="figures figures--strip">
		<?php
		foreach ( $hcwa_figures as $hcwa_figure ) {
			echo hcwa_blocks_figure_html( $hcwa_figure, $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>
	</div>
	<?php
	if ( hcwa_blocks_bool( $attributes, 'showLink', true ) ) {
		echo hcwa_blocks_text_link( hcwa_page_url( 'impact' ), hcwa_t( 'seeImpact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	}
	if ( ! $hcwa_production && $hcwa_verified < 2 ) {
		// Tells reviewers this strip will be hidden on the live site until 2 figures are verified.
		echo '<p class="empty-state" data-draft>' . esc_html( hcwa_t( 'blk_hiddenOnLive' ) ) . '</p>';
	}
	?>
</div>
