<?php
/**
 * News archive body (home.php, archive.php, index.php): page header, category chips,
 * a grid of story cards (12 per page, see inc/setup.php), numbered pagination.
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
	$hcwa_title   = $hcwa_page_id ? wp_strip_all_tags( get_the_title( $hcwa_page_id ) ) : $hcwa_news_label;
	$hcwa_trail   = array( array( $hcwa_title ) );
	if ( $hcwa_page_id && has_excerpt( $hcwa_page_id ) ) {
		$hcwa_intro = get_the_excerpt( $hcwa_page_id );
	}
} elseif ( is_category() || is_tag() || is_tax() ) {
	$hcwa_title = wp_strip_all_tags( single_term_title( '', false ) );
	$hcwa_intro = wp_strip_all_tags( term_description() );
	$hcwa_trail = $hcwa_is_posts ? array( array( $hcwa_news_label, $hcwa_news_url ), array( $hcwa_title ) ) : array( array( $hcwa_title ) );
} elseif ( is_search() ) {
	$hcwa_title = hcwa_theme_t( 'searchTitle' );
	$hcwa_trail = array( array( $hcwa_title ) );
} else {
	$hcwa_title = wp_strip_all_tags( get_the_archive_title() );
	$hcwa_trail = $hcwa_is_posts ? array( array( $hcwa_news_label, $hcwa_news_url ), array( $hcwa_title ) ) : array( array( $hcwa_title ) );
}
if ( '' === trim( $hcwa_title ) ) {
	$hcwa_title = $hcwa_news_label;
}

get_template_part(
	'template-parts/page-header',
	null,
	array(
		'trail' => $hcwa_trail,
		'title' => $hcwa_title,
		'intro' => $hcwa_intro,
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
<div class="section section--tight news-archive">
	<div class="container">
		<?php if ( count( $hcwa_chips ) > 1 ) : ?>
			<nav class="filters filters--flush" aria-label="<?php echo esc_attr( hcwa_theme_t( 'filterCategory' ) ); ?>">
				<ul>
					<?php foreach ( $hcwa_chips as $hcwa_chip ) : ?>
						<li><a class="chip" href="<?php echo esc_url( $hcwa_chip[1] ); ?>"<?php echo $hcwa_chip[2] ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $hcwa_chip[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<ul class="news-grid">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part(
						'template-parts/card',
						null,
						array(
							'post'    => get_post(),
							'variant' => 'story',
							'heading' => 'h2',
						)
					);
				}
				?>
			</ul>
			<?php get_template_part( 'template-parts/pagination' ); ?>
		<?php else : ?>
			<p class="empty-state"><?php hcwa_theme_e( 'noPosts' ); ?></p>
		<?php endif; ?>
	</div>
</div>
