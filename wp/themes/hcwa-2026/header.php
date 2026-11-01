<?php
/**
 * Header (06-PAGES "Global: header"): optional announcement, "View in English" bar on VI pages,
 * logo, menu, VI / EN switch and Donate. The switch and Donate stay outside the phone menu.
 */

defined( 'ABSPATH' ) || exit;
$hcwa_lang = hcwa_lang();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<?php echo hcwa_client_strings(); // phpcs:ignore WordPress.Security.EscapeOutput -- JSON, escaped ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php hcwa_e( 'skip' ); ?></a>

<?php if ( hcwa_review_mode() ) : ?>
<div class="proto-note"><div class="container"><p><span class="proto-note__full"><?php hcwa_e( 'reviewNote' ); ?></span><span class="proto-note__short"><?php hcwa_e( 'reviewNoteShort' ); ?></span></p></div></div>
<?php endif; ?>

<?php
$hcwa_announce = hcwa_setting( 'announce_on' ) ? hcwa_safe_text( hcwa_setting_tx( 'announce_text' ) ) : '';
if ( $hcwa_announce ) :
	$hcwa_announce_url = hcwa_setting_tx( 'announce_url' );
	?>
<div class="announce-bar" data-announce="<?php echo esc_attr( substr( md5( $hcwa_announce ), 0, 8 ) ); ?>">
	<div class="container announce__inner">
		<p<?php echo hcwa_draft_attr( hcwa_has_token( $hcwa_announce ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php if ( $hcwa_announce_url ) : ?>
				<a href="<?php echo esc_url( $hcwa_announce_url ); ?>"><?php echo esc_html( $hcwa_announce ); ?><?php echo hcwa_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php else : ?>
				<?php echo esc_html( $hcwa_announce ); ?>
			<?php endif; ?>
		</p>
		<button class="icon-btn icon-btn--sm" type="button" data-dismiss-announce aria-label="<?php echo esc_attr( hcwa_t( 'hideAnnouncement' ) ); ?>"><?php echo hcwa_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
</div>
<script>
/* A dismissed announcement stays hidden until its text changes (the key is a hash of the text). */
(function (bar) {
	var key = 'hcwa-announce';
	try { if (localStorage.getItem(key) === bar.dataset.announce) bar.hidden = true; } catch (e) {}
	bar.querySelector('[data-dismiss-announce]').addEventListener('click', function () {
		try { localStorage.setItem(key, bar.dataset.announce); } catch (e) {}
	});
})(document.currentScript.previousElementSibling);
</script>
<?php endif; ?>

<?php if ( 'vi' === $hcwa_lang ) : ?>
<div class="en-bar" lang="en" data-en-bar hidden>
	<div class="container en-bar__inner">
		<a href="<?php echo esc_url( hcwa_language_links()['en']['url'] ); ?>" hreflang="en"><?php echo esc_html( hcwa_t( 'enBar', array(), 'en' ) ); ?><?php echo hcwa_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<button class="icon-btn icon-btn--sm" type="button" data-dismiss-en-bar aria-label="<?php echo esc_attr( hcwa_t( 'enBarDismiss', array(), 'en' ) ); ?>"><?php echo hcwa_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
</div>
<?php endif; ?>

<div class="header-slot">
	<header class="site-header">
		<div class="container site-header__bar">
			<?php echo hcwa_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo hcwa_desktop_nav(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="site-header__tools">
				<?php echo hcwa_lang_switch( 'lang-switch--header' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo hcwa_button( hcwa_url( 'donate' ), hcwa_t( 'donate' ), 'primary', ' data-donate' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<button class="icon-btn menu-btn" type="button" aria-haspopup="dialog" aria-controls="menu-sheet" data-open-sheet><?php echo hcwa_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="sr-only"><?php hcwa_e( 'menu' ); ?></span></button>
			</div>
		</div>
	</header>
</div>

<main id="main" tabindex="-1">
<?php
$hcwa_untranslated = hcwa_untranslated_post();
if ( $hcwa_untranslated ) {
	echo '<div class="container lang-notice-wrap">' . hcwa_language_notice( $hcwa_untranslated ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
