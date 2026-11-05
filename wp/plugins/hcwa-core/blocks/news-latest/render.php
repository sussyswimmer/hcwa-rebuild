<?php
/**
 * hcwa/news-latest: "Latest from HCWA" (06-PAGES home section 6): one featured story
 * (a sticky post, else the newest story, else the newest post) with its approved photo,
 * plus the latest posts as compact rows. Titles lose leading emoji; each item shows its
 * category label and the date in the page language.
 *
 * featured = false prints only the rows, three across on wide screens.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_featured   = hcwa_blocks_bool( $attributes, 'featured', true );
$hcwa_limit      = hcwa_blocks_int( $attributes, 'limit', 3, 1, 12 );
$hcwa_production = hcwa_is_production();
$hcwa_lang       = hcwa_lang();

// A few more than needed, so a story among the recent posts can lead.
$hcwa_posts = get_posts(
	array_merge(
		hcwa_blocks_query_args( 'post', $hcwa_featured ? $hcwa_limit + 12 : $hcwa_limit ),
		array(
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		)
	)
);
$hcwa_posts  = array_values( array_filter( $hcwa_posts, static fn( $p ) => $p instanceof WP_Post ) );
$hcwa_sticky = array_values( array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) ) );

// The newest sticky post in this language leads, however old it is.
if ( $hcwa_featured && $hcwa_sticky ) {
	$hcwa_pinned = get_posts(
		array_merge(
			hcwa_blocks_query_args( 'post', 1 ),
			array(
				'post__in'            => $hcwa_sticky,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			)
		)
	);
	if ( $hcwa_pinned && $hcwa_pinned[0] instanceof WP_Post && ! in_array( $hcwa_pinned[0]->ID, wp_list_pluck( $hcwa_posts, 'ID' ), true ) ) {
		array_unshift( $hcwa_posts, $hcwa_pinned[0] );
	}
}

if ( ! $hcwa_posts ) {
	echo hcwa_blocks_empty_note( 'blk_noNews' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	return;
}

$hcwa_rows = array_map( 'hcwa_blocks_news_data', $hcwa_posts );

if ( ! $hcwa_featured ) {
	?>
	<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'news-list news-list--row' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
		<?php
		foreach ( array_slice( $hcwa_rows, 0, $hcwa_limit ) as $hcwa_row ) {
			echo hcwa_blocks_news_item( $hcwa_row, $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>
	</ul>
	<?php
	return;
}

$hcwa_pick = hcwa_blocks_pick_featured(
	array_map(
		static fn( $post, $row ) => array(
			'sticky'   => in_array( (int) $post->ID, $hcwa_sticky, true ),
			'category' => $row['category'],
		),
		$hcwa_posts,
		$hcwa_rows
	)
);

$hcwa_story = hcwa_blocks_news_data( $hcwa_posts[ $hcwa_pick ], true );
unset( $hcwa_rows[ $hcwa_pick ] );
$hcwa_rows = array_slice( array_values( $hcwa_rows ), 0, $hcwa_limit );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'latest' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php echo hcwa_blocks_story_card( $hcwa_story, $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
	<?php if ( $hcwa_rows ) : ?>
		<ul class="news-list">
			<?php
			foreach ( $hcwa_rows as $hcwa_row ) {
				echo hcwa_blocks_news_item( $hcwa_row, $hcwa_lang, $hcwa_production ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
			}
			?>
		</ul>
	<?php endif; ?>
</div>
