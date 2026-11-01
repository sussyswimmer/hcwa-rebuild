<?php
/**
 * News list layout shared by home.php and archive.php.
 *
 * @var array $args title, intro, intro_draft
 */

defined( 'ABSPATH' ) || exit;

$hcwa_args    = wp_parse_args( $args, array( 'title' => '', 'intro' => '', 'intro_draft' => false ) );
$hcwa_current = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : ( is_category() ? get_queried_object()->slug : '' ); // phpcs:ignore WordPress.Security.NonceVerification
$hcwa_hub     = hcwa_url( 'news' );

echo hcwa_page_header( // phpcs:ignore WordPress.Security.EscapeOutput
	array(
		'trail'       => array( array( $hcwa_args['title'], '' ) ),
		'title'       => $hcwa_args['title'],
		'intro'       => $hcwa_args['intro'],
		'intro_draft' => $hcwa_args['intro_draft'],
	)
);

$hcwa_cats = get_categories( array( 'hide_empty' => true, 'lang' => hcwa_lang(), 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
?>
<div class="container">
	<?php if ( $hcwa_cats ) : ?>
		<nav class="filters" aria-label="<?php echo esc_attr( hcwa_t( 'filterCategory' ) ); ?>">
			<ul>
				<li><a class="chip" href="<?php echo esc_url( $hcwa_hub ); ?>"<?php echo $hcwa_current ? '' : ' aria-current="true"'; ?>><?php hcwa_e( 'all' ); ?></a></li>
				<?php foreach ( $hcwa_cats as $hcwa_cat ) : ?>
					<li><a class="chip" href="<?php echo esc_url( add_query_arg( 'category', $hcwa_cat->slug, $hcwa_hub ) ); ?>"<?php echo $hcwa_current === $hcwa_cat->slug ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $hcwa_cat->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
</div>

<section class="section section--tight" aria-label="<?php echo esc_attr( $hcwa_args['title'] ); ?>">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<ul class="news-grid">
				<?php
				while ( have_posts() ) {
					the_post();
					echo '<li>' . hcwa_story_card( get_post(), false, 'h2' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</ul>
			<?php echo hcwa_pagination(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>
			<p class="empty-state"><?php hcwa_e( 'noNews' ); ?></p>
		<?php endif; ?>
	</div>
</section>
