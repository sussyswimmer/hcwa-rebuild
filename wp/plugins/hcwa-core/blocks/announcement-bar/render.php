<?php
/**
 * hcwa/announcement-bar (05-DESIGN, 06-PAGES): one dismissible line on --sun-100 above
 * the header, with an optional "Read more" link. Text and link per language come from
 * Settings → HCWA through hcwa_announcement(), which returns null when the bar is
 * switched off, has no text in this language, or (live site) still holds draft text.
 *
 * The theme prints this block in header.php; it is hidden from the inserter so it is not
 * added to page content a second time. The theme's site.js remembers a dismissal per
 * announcement text (data-announce).
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes (none).
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_announcement = hcwa_announcement();

if ( null === $hcwa_announcement ) {
	return;
}

$hcwa_text = (string) $hcwa_announcement['text'];
$hcwa_url  = (string) $hcwa_announcement['url'];
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'announce', 'data-announce' => $hcwa_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<div class="announce__inner">
		<p<?php echo hcwa_blocks_draft_attr( hcwa_is_production(), $hcwa_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute. ?>>
			<?php echo esc_html( $hcwa_text ); ?>
			<?php if ( '' !== $hcwa_url ) : ?>
				<a href="<?php echo esc_url( $hcwa_url ); ?>"><?php echo esc_html( hcwa_t( 'readMore' ) ); ?></a>
			<?php endif; ?>
		</p>
		<button class="icon-btn icon-btn--sm" type="button" data-dismiss-announce aria-label="<?php echo esc_attr( hcwa_t( 'hideAnnouncement' ) ); ?>"><?php echo hcwa_blocks_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
	</div>
</div>
