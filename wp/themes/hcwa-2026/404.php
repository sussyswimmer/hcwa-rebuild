<?php
/**
 * Not found (11-WP-PORT.md 2.5 P7; prototype pages/involve.mjs notFound()): "a brick is
 * missing", in the language of the URL (/en/… is English). The still loads eagerly (it is
 * the first thing on the page); nothing here moves.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

get_header();

$hcwa_art = hcwa_theme_still(
	'not-found',
	array(
		'cls'   => 'gap__img',
		'sizes' => '(min-width: 1024px) 46vw, 100vw',
		'eager' => true,
	)
);
$hcwa_art = '' !== $hcwa_art ? $hcwa_art : '<span class="lit-wall" aria-hidden="true"><span class="lit-wall__light"></span></span>';
?>

<div class="day day--gap">
  <section class="gap container" aria-labelledby="nf-title">
    <div class="gap__still" aria-hidden="true"><?php echo $hcwa_art; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated picture. ?></div>
    <div class="gap__text">
      <h1 class="gap__title" id="nf-title"><?php echo esc_html( hcwa_theme_vi_keep( hcwa_theme_t( 'nf_title' ) ) ); ?></h1>
      <p class="gap__lead"><?php hcwa_theme_e( 'nf_text' ); ?></p>
      <?php if ( ! hcwa_theme_is_production() ) : ?>
<p class="gap__proto" data-draft><?php echo hcwa_theme_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php hcwa_theme_e( 'notInPrototype' ); ?></span></p>
	  <?php endif; ?>
      <?php get_search_form(); ?>
      <nav class="gap__nav" aria-labelledby="nf-links">
        <p class="gap__nav-title" id="nf-links"><?php hcwa_theme_e( 'nf_links' ); ?></p>
        <ul class="gap__links"><?php foreach ( array( 'home', 'ourWork', 'contact' ) as $hcwa_key ) : ?><li><a href="<?php echo esc_url( hcwa_theme_url( $hcwa_key ) ); ?>"><span><?php hcwa_theme_e( $hcwa_key ); ?></span><?php echo hcwa_theme_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a></li><?php endforeach; ?></ul>
      </nav>
    </div>
  </section>
</div>
<?php
get_footer();
