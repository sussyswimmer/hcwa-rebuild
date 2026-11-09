<?php
/**
 * Document shell and site header (11-WP-PORT.md 2.2, 2.3; prototype src/partials/layout.mjs):
 * skip link, the staging note, the "View this page in English" bar, the announcement slot,
 * the header with logo, main menu, VI / EN switch, Donate and Menu, the world canvas on the
 * front page. The menu sheet and the footer are printed in footer.php.
 *
 * Nothing sits between the body's children but what 2.2 lists: the CSS pairs siblings
 * (body:has(> .proto-note), body:has(> .en-bar:not([hidden]))).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_lang    = hcwa_theme_lang();
$hcwa_links   = hcwa_theme_language_links();
$hcwa_context = function_exists( 'hcwa_theme_page_context' ) ? hcwa_theme_page_context() : array( 'world' => false, 'header_over' => false );
$hcwa_names   = hcwa_theme_names();
$hcwa_over    = ! empty( $hcwa_context['header_over'] );

// "View this page in English" only where it is true. A VI-only article points to the English
// news page with a label that says so (07-BILINGUAL); an untranslated page or archive, or the
// language notice already on screen, gets no bar.
$hcwa_en_bar_url   = '';
$hcwa_en_bar_label = '';
if ( 'vi' === $hcwa_lang ) {
	if ( ! empty( $hcwa_links['en']['has_translation'] ) ) {
		$hcwa_en_bar_url   = $hcwa_links['en']['url'];
		$hcwa_en_bar_label = hcwa_theme_t( 'enBar' );
	} elseif ( is_singular( 'post' ) && 'en' !== hcwa_theme_lang_notice_request() ) {
		$hcwa_en_bar_url   = hcwa_theme_url( 'news', 'en' );
		$hcwa_en_bar_label = hcwa_theme_t_in( 'langNoticeHub', 'en' );
	}
}
$hcwa_logo_webp = function_exists( 'hcwa_theme_dist_url' ) ? hcwa_theme_dist_url( 'img/hcwa-logo.webp' ) : '';
$hcwa_logo_png  = function_exists( 'hcwa_theme_dist_url' ) ? hcwa_theme_dist_url( 'img/hcwa-logo.png' ) : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php
if ( function_exists( 'hcwa_theme_print_head_first' ) ) {
	hcwa_theme_print_head_first();
}
wp_head();
?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php hcwa_theme_e( 'skip' ); ?></a>
<?php if ( ! hcwa_theme_is_production() ) : ?>
<div class="proto-note"><div class="container proto-note__inner"><p><span class="proto-note__full"><?php hcwa_theme_e( 'prototypeNote' ); ?></span><span class="proto-note__short"><?php hcwa_theme_e( 'prototypeNoteShort' ); ?></span></p><button class="proto-note__toggle" type="button" data-draft-toggle><?php hcwa_theme_e( 'draftShow' ); ?></button></div></div>
<?php endif; ?>
<?php if ( '' !== $hcwa_en_bar_url ) : ?>
<aside class="en-bar" aria-label="<?php echo esc_attr( hcwa_theme_t( 'enBarLabel' ) ); ?>" data-en-bar hidden>
        <div class="container en-bar__inner" lang="en">
          <a href="<?php echo esc_url( $hcwa_en_bar_url ); ?>" hreflang="en"><?php echo esc_html( $hcwa_en_bar_label ); ?><?php echo hcwa_theme_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
          <button class="icon-btn icon-btn--sm" type="button" data-dismiss-en-bar aria-label="<?php echo esc_attr( hcwa_theme_t( 'enBarDismiss' ) ); ?>"><?php echo hcwa_theme_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
        </div>
      </aside>
	<?php
	if ( function_exists( 'hcwa_theme_bar_script' ) ) {
		echo hcwa_theme_bar_script( 'en' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme file, inline script tag.
	}
	?>
<?php endif; ?>
<?php
// The block decides whether the announcement is switched on (Settings → HCWA); off by default.
$hcwa_announcement = hcwa_theme_block( 'hcwa/announcement-bar' );
if ( '' !== $hcwa_announcement ) {
	echo '<div class="announce-slot">' . $hcwa_announcement . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
	if ( function_exists( 'hcwa_theme_bar_script' ) ) {
		echo hcwa_theme_bar_script( 'announce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme file, inline script tag.
	}
}
?>
<div class="header-slot<?php echo $hcwa_over ? ' header-slot--over' : ''; ?>">
  <header class="site-header<?php echo $hcwa_over ? ' site-header--over' : ''; ?>" data-header>
    <div class="container site-header__bar">
      <a class="logo" href="<?php echo esc_url( hcwa_theme_url( 'home' ) ); ?>" data-text-safe>
    <picture><?php if ( '' !== $hcwa_logo_webp ) : ?><source type="image/webp" srcset="<?php echo esc_url( $hcwa_logo_webp ); ?>"><?php endif; ?><img src="<?php echo esc_url( $hcwa_logo_png ); ?>" width="97" height="128" alt="<?php echo esc_attr( $hcwa_names['org'] ); ?>"></picture>
    <span class="logo__name" aria-hidden="true"><span><?php hcwa_theme_e( 'logoLine1' ); ?></span><span><?php hcwa_theme_e( 'logoLine2' ); ?></span></span>
  </a>
      <nav class="nav" aria-label="<?php echo esc_attr( hcwa_theme_t( 'mainNav' ) ); ?>" data-text-safe>
  <?php hcwa_theme_primary_menu( 'desktop' ); ?>
</nav>
      <div class="site-header__tools" data-text-safe>
        <?php echo hcwa_theme_lang_switch( 'lang-switch--header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
        <a class="btn btn--primary btn--header" href="<?php echo esc_url( hcwa_theme_url( 'donate' ) ); ?>" data-donate><?php hcwa_theme_e( 'donate' ); ?></a>
        <button class="menu-btn" type="button" aria-haspopup="dialog" aria-controls="menu-sheet" data-open-sheet><?php hcwa_theme_e( 'menu' ); ?></button>
      </div>
    </div>
  </header>
</div>
<?php if ( ! empty( $hcwa_context['world'] ) ) : ?>
<canvas class="world-canvas" id="world" aria-hidden="true" role="presentation"></canvas>
<?php endif; ?>
<main id="main" tabindex="-1">
