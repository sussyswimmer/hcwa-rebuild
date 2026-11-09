<?php
/**
 * News hub and archives (home.php, archive.php, index.php; 11-WP-PORT.md 2.7): the sun head
 * with breadcrumb and H1, then in the day's gradient the category chips, the posts as the
 * prototype's dated list (12 per page, inc/setup.php), pagination or the empty state.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_news_url   = hcwa_theme_url( 'news' );
$hcwa_news_label = hcwa_theme_t( 'news' );
$hcwa_intro      = '';
$hcwa_is_posts   = is_home() || is_category() || is_tag() || is_date();

if ( is_home() ) {
	$hcwa_page_id = (int) get_option( 'page_for_posts' );
	$hcwa_title   = $hcwa_page_id ? wp_strip_all_tags( (string) get_the_title( $hcwa_page_id ) ) : $hcwa_news_label;
	$hcwa_trail   = array( array( $hcwa_title ) );
	if ( $hcwa_page_id && has_excerpt( $hcwa_page_id ) ) {
		$hcwa_intro = (string) get_the_excerpt( $hcwa_page_id );
	}
} elseif ( is_category() || is_tag() || is_tax() ) {
	$hcwa_title = wp_strip_all_tags( (string) single_term_title( '', false ) );
	$hcwa_intro = wp_strip_all_tags( (string) term_description() );
	$hcwa_trail = $hcwa_is_posts ? array( array( $hcwa_news_label, $hcwa_news_url ), array( $hcwa_title ) ) : array( array( $hcwa_title ) );
} else {
	$hcwa_title = wp_strip_all_tags( (string) get_the_archive_title() );
	$hcwa_trail = $hcwa_is_posts ? array( array( $hcwa_news_label, $hcwa_news_url ), array( $hcwa_title ) ) : array( array( $hcwa_title ) );
}
if ( '' === trim( $hcwa_title ) ) {
	$hcwa_title = $hcwa_news_label;
	$hcwa_trail = array( array( $hcwa_title ) );
}
$hcwa_title = hcwa_theme_draft_text( $hcwa_title );
$hcwa_intro = hcwa_theme_draft_text( trim( $hcwa_intro ) );

echo hcwa_theme_sun_head( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	array(
		'title'       => $hcwa_title['text'],
		'title_draft' => $hcwa_title['draft'],
		'trail'       => $hcwa_trail,
		'intro'       => $hcwa_intro['text'],
		'intro_draft' => $hcwa_intro['draft'],
	)
);

// Category chips: All / News / Stories / Events, each only if it exists in this language.
$hcwa_chips = array();
if ( is_home() || is_category() ) {
	$hcwa_current_term = is_category() ? get_queried_object() : null;
	$hcwa_chips[]      = array( hcwa_theme_t( 'all' ), $hcwa_news_url, is_home() );
	foreach ( array( 'news', 'stories', 'events' ) as $hcwa_key ) {
		$hcwa_term = hcwa_theme_category( $hcwa_key );
		if ( ! $hcwa_term ) {
			continue;
		}
		$hcwa_link = get_term_link( $hcwa_term );
		if ( is_wp_error( $hcwa_link ) ) {
			continue;
		}
		$hcwa_chips[] = array( $hcwa_term->name, $hcwa_link, $hcwa_current_term instanceof WP_Term && $hcwa_current_term->term_id === $hcwa_term->term_id );
	}
}
?>
<div class="day day--donate">
  <div class="container wp-list">
	<?php if ( count( $hcwa_chips ) > 1 ) : ?>
	<nav class="wp-chips" aria-label="<?php echo esc_attr( hcwa_theme_t( 'filterCategory' ) ); ?>">
	  <ul class="filters__list">
		<?php foreach ( $hcwa_chips as $hcwa_chip ) : ?>
		<li><a class="chip chip--all" href="<?php echo esc_url( $hcwa_chip[1] ); ?>"<?php echo $hcwa_chip[2] ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $hcwa_chip[0] ); ?></a></li>
		<?php endforeach; ?>
	  </ul>
	</nav>
	<?php endif; ?>
	<?php if ( have_posts() ) : ?>
	<ol class="dated-list">
		<?php
		while ( have_posts() ) {
			the_post();
			echo hcwa_theme_dated_post( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>
	</ol>
		<?php get_template_part( 'template-parts/pagination' ); ?>
	<?php else : ?>
	<p class="wp-empty"><?php hcwa_theme_e( 'noPosts' ); ?></p>
	<?php endif; ?>
  </div>
</div>
