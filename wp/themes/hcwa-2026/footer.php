<?php
/**
 * Site footer (06-PAGES): brand column, contact, Explore menu, Reports & policies menu,
 * bottom row with the year, social links and the VI / EN switch. Then the mobile menu sheet.
 *
 * Contact details come from Settings → HCWA (hcwa-core); empty lines are left out.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

// Settings → HCWA keys (hcwa-core includes/settings.php). The tagline is translated by Polylang.
$hcwa_description  = wp_strip_all_tags( get_bloginfo( 'description' ) );
$hcwa_registration = hcwa_theme_setting_l( 'registration' );
$hcwa_address      = hcwa_theme_setting_l( 'address' );
$hcwa_phone        = (string) hcwa_theme_setting( 'phone', '' );
$hcwa_phone_tel    = (string) hcwa_theme_setting( 'phone_tel', '' );
$hcwa_phone_tel    = '' !== $hcwa_phone_tel ? 'tel:' . $hcwa_phone_tel : hcwa_theme_tel( $hcwa_phone );
$hcwa_emails       = function_exists( 'hcwa_contact_emails' ) ? hcwa_contact_emails() : array();
$hcwa_email        = (string) ( $hcwa_emails[0] ?? '' );
$hcwa_zalo_url     = (string) hcwa_theme_setting( 'zalo', '' ); // Stored as a zalo.me link.
$hcwa_map          = (string) hcwa_theme_setting( 'map_url', '' );
$hcwa_facebook     = (string) hcwa_theme_setting( 'facebook', '' );

/**
 * Footer column: accordion on mobile, plain column on desktop (site.js keeps them in sync).
 *
 * @param string   $title Column title.
 * @param callable $body  Prints the column body.
 */
$hcwa_col = static function ( string $title, callable $body ): void {
	?>
	<details class="foot-col" open>
		<summary><h2><?php echo esc_html( $title ); ?></h2><?php echo hcwa_theme_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></summary>
		<div class="foot-col__body"><?php $body(); ?></div>
	</details>
	<?php
};
?>
</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="foot-brand">
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/hcwa-logo.png' ) ); ?>" width="73" height="96" alt="" loading="lazy">
			<p class="foot-brand__name"><?php echo esc_html( wp_strip_all_tags( get_bloginfo( 'name' ) ) ); ?></p>
			<?php if ( '' !== $hcwa_description ) : ?>
				<p><?php echo esc_html( $hcwa_description ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $hcwa_registration ) : ?>
				<p class="foot-brand__reg"><?php echo esc_html( $hcwa_registration ); ?></p>
			<?php endif; ?>
		</div>

		<?php
		$hcwa_col(
			hcwa_theme_t( 'contact' ),
			static function () use ( $hcwa_address, $hcwa_phone, $hcwa_phone_tel, $hcwa_email, $hcwa_zalo_url, $hcwa_map ): void {
				?>
				<ul class="foot-contact">
					<?php if ( '' !== $hcwa_address ) : ?>
						<li><?php echo hcwa_theme_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo nl2br( esc_html( $hcwa_address ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped first. ?></span></li>
					<?php endif; ?>
					<?php if ( '' !== $hcwa_phone ) : ?>
						<li><?php echo hcwa_theme_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( $hcwa_phone_tel, array( 'tel' ) ); ?>"><?php echo esc_html( $hcwa_phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $hcwa_email && is_email( $hcwa_email ) ) : ?>
						<li><?php echo hcwa_theme_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( 'mailto:' . $hcwa_email, array( 'mailto' ) ); ?>"><?php echo esc_html( $hcwa_email ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $hcwa_zalo_url ) : ?>
						<li><?php echo hcwa_theme_icon( 'message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( $hcwa_zalo_url ); ?>">Zalo</a></li>
					<?php endif; ?>
					<?php if ( '' !== $hcwa_map ) : ?>
						<li><?php echo hcwa_theme_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( $hcwa_map ); ?>"><?php hcwa_theme_e( 'openMap' ); ?></a></li>
					<?php endif; ?>
					<li><?php echo hcwa_theme_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( hcwa_theme_url( 'contact' ) ); ?>"><?php hcwa_theme_e( 'contactUs' ); ?></a></li>
				</ul>
				<?php
			}
		);

		$hcwa_col(
			hcwa_theme_t( 'explore' ),
			static function (): void {
				hcwa_theme_footer_menu( 'footer-explore' );
			}
		);

		$hcwa_col(
			hcwa_theme_t( 'trust' ),
			static function (): void {
				hcwa_theme_footer_menu( 'footer-trust' );
			}
		);
		?>
	</div>
	<div class="container site-footer__bottom">
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> HCWA</p>
		<?php if ( '' !== $hcwa_facebook ) : ?>
			<ul class="foot-social" aria-label="<?php echo esc_attr( hcwa_theme_t( 'followUs' ) ); ?>">
				<li><a href="<?php echo esc_url( $hcwa_facebook ); ?>">Facebook</a></li>
			</ul>
		<?php endif; ?>
		<?php echo hcwa_theme_lang_switch(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
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
	<div class="sheet__foot">
		<a class="btn btn--primary" href="<?php echo esc_url( hcwa_theme_url( 'donate' ) ); ?>"><?php hcwa_theme_e( 'donate' ); ?></a>
		<?php echo hcwa_theme_lang_switch(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
	</div>
</dialog>

<?php wp_footer(); ?>
</body>
</html>
