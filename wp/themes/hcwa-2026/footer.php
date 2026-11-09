<?php
/**
 * The dusk footer (H11) and the menu sheet (11-WP-PORT.md 2.3; prototype layout.mjs footer()
 * and mobileMenu()): the room at 18:00 as a still band, the breeze-block edge, the house's
 * name, the brand column and three folding columns (Contact, Explore, Trust), the bottom bar
 * with the year, the Facebook link (never an embed), the language switch and the pause toggle.
 *
 * Contact lines follow 2.1 rules 7 and 12: a line prints only with text, on production only
 * when it is not draft; the list carries data-draft only when one of its lines is draft, and
 * the column is left out on production when no line is left. The map link counts as draft
 * while the address is, because its URL holds the address.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_production   = hcwa_theme_is_production();
$hcwa_names        = hcwa_theme_names();
$hcwa_description  = hcwa_theme_draft_text( (string) get_bloginfo( 'description' ) );
$hcwa_registration = hcwa_theme_draft_text( function_exists( 'hcwa_setting' ) ? (string) hcwa_setting( 'registration_' . hcwa_theme_lang(), '' ) : '' );
$hcwa_address      = hcwa_theme_draft_text( function_exists( 'hcwa_setting' ) ? (string) hcwa_setting( 'address_' . hcwa_theme_lang(), '' ) : '' );
$hcwa_map          = (string) hcwa_theme_setting( 'map_url', '' );
$hcwa_facebook     = (string) hcwa_theme_setting( 'facebook', '' );
$hcwa_logo_webp    = function_exists( 'hcwa_theme_dist_url' ) ? hcwa_theme_dist_url( 'img/hcwa-logo.webp' ) : '';
$hcwa_logo_png     = function_exists( 'hcwa_theme_dist_url' ) ? hcwa_theme_dist_url( 'img/hcwa-logo.png' ) : '';
$hcwa_dusk         = hcwa_theme_still( 'dusk', array( 'cls' => 'site-footer__dusk' ) );

// Contact column lines.
$hcwa_lines = array();
$hcwa_draft = false;
if ( '' !== $hcwa_address['text'] ) {
	$hcwa_lines[] = '<li class="foot-contact__address">' . hcwa_theme_quatrefoil( 'foot-contact__sun' ) . '<span>' . esc_html( $hcwa_address['text'] ) . '</span></li>';
	$hcwa_draft   = $hcwa_draft || $hcwa_address['draft'];
}
foreach ( array( 'phone' => 'phone', 'email' => 'mail' ) as $hcwa_which => $hcwa_icon ) {
	$hcwa_line = hcwa_theme_contact_line( $hcwa_which );
	if ( '' !== $hcwa_line['text'] ) {
		$hcwa_lines[] = '<li>' . hcwa_theme_icon( $hcwa_icon ) . '<a href="' . esc_url( $hcwa_line['href'], array( 'tel', 'mailto' ) ) . '">' . esc_html( $hcwa_line['text'] ) . '</a></li>';
		$hcwa_draft   = $hcwa_draft || $hcwa_line['draft'];
	}
}
if ( '' !== $hcwa_map && '' !== $hcwa_address['text'] && ( ! $hcwa_production || ! $hcwa_address['draft'] ) ) {
	$hcwa_lines[] = '<li>' . hcwa_theme_icon( 'external' ) . '<a href="' . esc_url( $hcwa_map ) . '">' . esc_html( hcwa_theme_t( 'openMap' ) ) . '</a></li>';
}

$hcwa_col_n = 0;
/**
 * One footer column: a plain heading from 1024 px, a fold below it (the bundle's ui/footer.js).
 *
 * @param string $title Column title.
 * @param string $body  Escaped HTML.
 */
$hcwa_col = static function ( string $title, string $body ) use ( &$hcwa_col_n ): string {
	$id = 'foot-col-' . ( ++$hcwa_col_n );
	return '<div class="foot-col"><h2 class="foot-col__title"><span class="foot-col__label">' . esc_html( $title ) . '</span><button class="foot-col__toggle" type="button" aria-expanded="true" aria-controls="' . esc_attr( $id ) . '">' . hcwa_theme_quatrefoil( 'foot-col__mark' ) . '<span>' . esc_html( $title ) . '</span>' . hcwa_theme_icon( 'chevron' ) . '</button></h2><div class="foot-col__body" id="' . esc_attr( $id ) . '">' . $body . '</div></div>';
};
$hcwa_menu = static function ( string $location ): string {
	ob_start();
	hcwa_theme_footer_menu( $location );
	return (string) ob_get_clean();
};
?>
</main>
<footer class="site-footer">
    <?php if ( '' !== $hcwa_dusk ) : ?>
<div class="site-footer__still" aria-hidden="true"><?php echo $hcwa_dusk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated picture. ?></div>
	<?php endif; ?>
    <div class="footer-edge" aria-hidden="true"></div>
    <div class="container site-footer__sign">
      <picture><?php if ( '' !== $hcwa_logo_webp ) : ?><source type="image/webp" srcset="<?php echo esc_url( $hcwa_logo_webp ); ?>"><?php endif; ?><img src="<?php echo esc_url( $hcwa_logo_png ); ?>" width="73" height="96" alt="" loading="lazy"></picture>
      <p class="site-footer__name"><?php echo esc_html( hcwa_theme_vi_keep( $hcwa_names['org'] ) ); ?></p>
    </div>
    <div class="site-footer__cols"><div class="container site-footer__grid">
      <div class="foot-brand">
        <?php if ( '' !== $hcwa_description['text'] ) : ?>
<p<?php echo $hcwa_description['draft'] ? ' data-draft' : ''; ?>><?php echo esc_html( $hcwa_description['text'] ); ?></p>
		<?php endif; ?>
        <?php if ( '' !== $hcwa_registration['text'] ) : ?>
<p class="foot-brand__reg"<?php echo $hcwa_registration['draft'] ? ' data-draft' : ''; ?>><?php echo esc_html( $hcwa_registration['text'] ); ?></p>
		<?php endif; ?>
      </div>
      <?php
		if ( $hcwa_lines ) {
			echo $hcwa_col( hcwa_theme_t( 'contact' ), '<ul class="foot-contact"' . ( $hcwa_draft ? ' data-draft' : '' ) . ">\n        " . implode( "\n        ", $hcwa_lines ) . "\n      </ul>" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
		// A column whose menu has no live links (on production, before HCWA publishes Impact, Policies and
		// Privacy) is left out, like the contact column, instead of printing a heading over an empty body.
		foreach ( array( 'explore' => 'footer-explore', 'trust' => 'footer-trust' ) as $hcwa_key => $hcwa_location ) {
			$hcwa_items = $hcwa_menu( $hcwa_location );
			if ( false !== stripos( $hcwa_items, '<li' ) ) {
				echo "\n      " . $hcwa_col( hcwa_theme_t( $hcwa_key ), $hcwa_items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- menu output.
			}
		}
		?>
    </div></div>
    <div class="site-footer__bar">
      <div class="container site-footer__bottom">
        <p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> HCWA</p>
        <?php if ( '' !== $hcwa_facebook ) : ?>
<p class="foot-social"><?php hcwa_theme_e( 'followUs' ); ?>: <a href="<?php echo esc_url( $hcwa_facebook ); ?>">Facebook</a></p>
		<?php endif; ?>
        <?php echo hcwa_theme_lang_switch(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
        <?php echo hcwa_theme_pause_toggle( 'pause-toggle--dusk' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
      </div>
    </div>
  </footer>
<dialog class="sheet" id="menu-sheet" aria-labelledby="menu-title">
  <div class="sheet__head">
    <p class="sheet__title" id="menu-title"><?php hcwa_theme_e( 'menu' ); ?></p>
    <button class="icon-btn" type="button" data-close-sheet aria-label="<?php echo esc_attr( hcwa_theme_t( 'close' ) ); ?>"><?php echo hcwa_theme_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
  </div>
  <nav aria-label="<?php echo esc_attr( hcwa_theme_t( 'mainNav' ) ); ?>">
    <?php hcwa_theme_primary_menu( 'sheet' ); ?>
  </nav>
  <div class="sheet__foot"><a class="btn btn--primary" href="<?php echo esc_url( hcwa_theme_url( 'donate' ) ); ?>"><?php hcwa_theme_e( 'donate' ); ?></a><?php echo hcwa_theme_lang_switch(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></div>
</dialog>
<?php wp_footer(); ?>
</body>
</html>
