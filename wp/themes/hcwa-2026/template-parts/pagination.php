<?php
/**
 * Numbered pagination for the main query, with labels in the page language.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_links = paginate_links(
	array(
		'type'               => 'array',
		'mid_size'           => 1,
		'end_size'           => 1,
		'prev_text'          => hcwa_theme_icon( 'prev' ) . '<span>' . esc_html( hcwa_theme_t( 'prevPage' ) ) . '</span>',
		'next_text'          => '<span>' . esc_html( hcwa_theme_t( 'nextPage' ) ) . '</span>' . hcwa_theme_icon( 'next' ),
		'before_page_number' => '<span class="sr-only">' . esc_html( hcwa_theme_t( 'page' ) ) . ' </span>',
	)
);

if ( empty( $hcwa_links ) ) {
	return;
}
?>
<nav class="pagination" aria-label="<?php echo esc_attr( hcwa_theme_t( 'pagination' ) ); ?>">
	<ul>
		<?php foreach ( $hcwa_links as $hcwa_link ) : ?>
			<li><?php echo $hcwa_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() escapes URLs; labels escaped above. ?></li>
		<?php endforeach; ?>
	</ul>
</nav>
