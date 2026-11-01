<?php
/**
 * Footer (06-PAGES "Global: footer"): four columns on desktop, accordions on phones.
 * Contact details render only when verified (or outlined in review mode).
 */

defined( 'ABSPATH' ) || exit;

// Description and registration line belong to the same "verified" group as the address
// (Settings → HCWA → Contact details): hidden on production until verified.
$hcwa_contact  = hcwa_contact();
$hcwa_show     = hcwa_contact_verified() || hcwa_review_mode();
$hcwa_desc     = $hcwa_show ? hcwa_safe_text( hcwa_setting_tx( 'description' ) ) : '';
$hcwa_reg      = $hcwa_show ? hcwa_safe_text( hcwa_setting_tx( 'registration' ) ) : '';
$hcwa_unverify = ! hcwa_contact_verified();

/** Footer column: <details> so phones get an accordion; open on desktop (site.js). */
$hcwa_col = function ( $title, $body ) {
	return '<details class="foot-col" open><summary><h2>' . esc_html( $title ) . '</h2>' . hcwa_icon( 'chevron' ) . '</summary><div class="foot-col__body">' . $body . '</div></details>';
};
$hcwa_links = function ( $keys ) {
	$out = '<ul>';
	foreach ( $keys as $key => $label ) {
		$out .= '<li><a href="' . esc_url( 'news' === $key ? hcwa_url( 'news' ) : hcwa_url( $key ) ) . '">' . esc_html( hcwa_t( $label ) ) . '</a></li>';
	}
	return $out . '</ul>';
};
?>
</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="foot-brand">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/hcwa-logo.png' ); ?>" width="73" height="96" alt="" loading="lazy">
			<p class="foot-brand__name"><?php hcwa_e( 'orgName' ); ?></p>
			<?php if ( $hcwa_desc ) : ?>
				<p<?php echo hcwa_draft_attr( $hcwa_unverify || hcwa_has_token( $hcwa_desc ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $hcwa_desc ); ?></p>
			<?php endif; ?>
			<?php if ( $hcwa_reg ) : ?>
				<p class="foot-brand__reg"<?php echo hcwa_draft_attr( $hcwa_unverify || hcwa_has_token( $hcwa_reg ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $hcwa_reg ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		if ( $hcwa_contact ) {
			$hcwa_lines = '';
			if ( $hcwa_contact['address'] ) {
				$hcwa_lines .= '<li>' . hcwa_icon( 'pin' ) . '<span>' . esc_html( $hcwa_contact['address'] ) . '</span></li>';
			}
			if ( $hcwa_contact['phone'] ) {
				$hcwa_lines .= '<li>' . hcwa_icon( 'phone' ) . '<a href="tel:' . esc_attr( $hcwa_contact['tel'] ) . '">' . esc_html( $hcwa_contact['phone'] ) . '</a></li>';
			}
			if ( $hcwa_contact['email'] ) {
				$hcwa_lines .= '<li>' . hcwa_icon( 'mail' ) . '<a href="mailto:' . esc_attr( $hcwa_contact['email'] ) . '">' . esc_html( $hcwa_contact['email'] ) . '</a></li>';
			}
			if ( $hcwa_contact['map_url'] ) {
				$hcwa_lines .= '<li>' . hcwa_icon( 'external' ) . '<a href="' . esc_url( $hcwa_contact['map_url'] ) . '">' . esc_html( hcwa_t( 'openMap' ) ) . '</a></li>';
			}
			echo $hcwa_col( hcwa_t( 'contact' ), '<ul class="foot-contact"' . hcwa_draft_attr( $hcwa_contact['draft'] ) . '>' . $hcwa_lines . '</ul>' ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo $hcwa_col( hcwa_t( 'contact' ), $hcwa_links( array( 'contact' => 'contactUs' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo $hcwa_col( hcwa_t( 'explore' ), $hcwa_links( array( 'about' => 'about', 'ourWork' => 'ourWork', 'impact' => 'impact', 'news' => 'news', 'getInvolved' => 'getInvolved' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $hcwa_col( hcwa_t( 'trust' ), $hcwa_links( array( 'reports' => 'annualReports', 'safeguarding' => 'safeguarding', 'privacy' => 'privacy', 'partners' => 'partners' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>
	<div class="container site-footer__bottom">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> HCWA</p>
		<?php if ( hcwa_social_links() ) : ?>
			<p class="foot-social">
				<?php
				$hcwa_social = array();
				foreach ( hcwa_social_links() as $hcwa_name => $hcwa_url ) {
					$hcwa_social[] = '<a href="' . esc_url( $hcwa_url ) . '" rel="noopener">' . esc_html( $hcwa_name ) . '</a>';
				}
				echo implode( ' · ', $hcwa_social ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</p>
		<?php endif; ?>
		<?php echo hcwa_lang_switch(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</footer>
<?php echo hcwa_mobile_menu(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php wp_footer(); ?>
</body>
</html>
