<?php
/**
 * Site header (port of prototype/src/partials/layout.mjs): skip link, staging note,
 * "View this page in English" bar, announcement bar, sticky header with logo, main menu,
 * VI / EN switch and Donate. The mobile menu sheet is printed in footer.php.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_lang  = hcwa_theme_lang();
$hcwa_links = hcwa_theme_language_links();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php hcwa_theme_e( 'skip' ); ?></a>

<?php if ( ! hcwa_theme_is_production() ) : ?>
	<div class="staging-note">
		<div class="container">
			<p><span class="staging-note__full"><?php hcwa_theme_e( 'stagingNote' ); ?></span><span class="staging-note__short"><?php hcwa_theme_e( 'stagingNoteShort' ); ?></span></p>
		</div>
	</div>
<?php endif; ?>

<?php if ( 'vi' === $hcwa_lang ) : ?>
	<?php // English-speaking visitors on VI pages get a pointer to /en/; never an automatic redirect (07-BILINGUAL). ?>
	<div class="en-bar" lang="en" data-en-bar hidden>
		<div class="container en-bar__inner">
			<a href="<?php echo esc_url( $hcwa_links['en']['url'] ); ?>" hreflang="en"><?php echo esc_html( hcwa_theme_t_in( 'enBar', 'en' ) ); ?><?php echo hcwa_theme_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
			<button class="icon-btn icon-btn--sm" type="button" data-dismiss-en-bar aria-label="<?php echo esc_attr( hcwa_theme_t_in( 'enBarDismiss', 'en' ) ); ?>"><?php echo hcwa_theme_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
		</div>
	</div>
<?php endif; ?>

<?php
// The block decides whether the announcement is switched on (Settings → HCWA).
$hcwa_announcement = hcwa_theme_block( 'hcwa/announcement-bar' );
if ( '' !== $hcwa_announcement ) {
	echo '<div class="container announce-slot">' . $hcwa_announcement . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
}
?>

<div class="header-slot">
	<header class="site-header">
		<div class="container site-header__bar">
			<a class="logo" href="<?php echo esc_url( hcwa_theme_url( 'home' ) ); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>>
				<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/hcwa-logo.png' ) ); ?>" width="97" height="128" alt="<?php echo esc_attr( wp_strip_all_tags( get_bloginfo( 'name' ) ) ); ?>">
				<span class="logo__name" aria-hidden="true"><span><?php hcwa_theme_e( 'logoLine1' ); ?></span><span><?php hcwa_theme_e( 'logoLine2' ); ?></span></span>
			</a>
			<nav class="nav" aria-label="<?php echo esc_attr( hcwa_theme_t( 'mainNav' ) ); ?>">
				<?php hcwa_theme_primary_menu( 'desktop' ); ?>
			</nav>
			<div class="site-header__tools">
				<?php echo hcwa_theme_lang_switch( 'lang-switch--header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
				<a class="btn btn--primary" href="<?php echo esc_url( hcwa_theme_url( 'donate' ) ); ?>" data-donate><?php hcwa_theme_e( 'donate' ); ?></a>
				<button class="icon-btn menu-btn" type="button" aria-haspopup="dialog" aria-controls="menu-sheet" data-open-sheet><?php echo hcwa_theme_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span class="sr-only"><?php hcwa_theme_e( 'menu' ); ?></span></button>
			</div>
		</div>
	</header>
</div>

<main id="main" tabindex="-1">
