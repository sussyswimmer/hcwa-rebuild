<?php
/**
 * hcwa/latest-news. Homepage: featured story + latest items (06-PAGES "Stories & news").
 * Inner pages: featured off and a tag set → a row of related news.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$hcwa_lang     = hcwa_lang();
$hcwa_story    = null;
$hcwa_exclude  = array_filter( array( (int) $attributes['exclude'] ) );
if ( $attributes['featured'] ) {
	$hcwa_stories = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 1,
			'category_name'  => 'en' === $hcwa_lang ? 'stories' : 'cau-chuyen',
			'lang'           => $hcwa_lang,
			'post__not_in'   => $hcwa_exclude,
		)
	);
	$hcwa_story = $hcwa_stories ? $hcwa_stories[0] : null;
}

$hcwa_query = array(
	'post_type'           => 'post',
	'posts_per_page'      => max( 1, (int) $attributes['count'] ),
	'lang'                => $hcwa_lang,
	'ignore_sticky_posts' => true,
	'post__not_in'        => array_merge( $hcwa_exclude, $hcwa_story ? array( $hcwa_story->ID ) : array() ),
);
if ( $attributes['tag'] ) {
	$hcwa_query['tag'] = $attributes['tag'];
}
$hcwa_posts = get_posts( $hcwa_query );

if ( ! $hcwa_posts && ! $hcwa_story ) {
	return;
}

$hcwa_list = '';
foreach ( $hcwa_posts as $hcwa_post ) {
	$hcwa_list .= hcwa_news_item( $hcwa_post );
}

if ( ! $attributes['featured'] ) {
	echo '<ul ' . hcwa_block_wrapper( 'news-list news-list--row' ) . '>' . $hcwa_list . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

if ( $hcwa_story ) {
	$hcwa_featured = hcwa_story_card( $hcwa_story, true );
} elseif ( hcwa_review_mode() ) {
	// No approved story yet: show where it goes (prototype placeholder), never on production.
	$hcwa_featured = '<article class="story-card story-card--featured" data-draft>'
		. hcwa_image_slot( 0, array( 'code' => 'C2', 'label' => array( 'vi' => 'Ảnh câu chuyện', 'en' => 'Story photo' ), 'ratio' => '3:2' ) )
		. '<p class="label">' . esc_html( hcwa_t( 'cat_story' ) ) . '</p>'
		. '<h3 class="story-card__title">' . esc_html( 'en' === $hcwa_lang ? '[Story title]' : '[Tên câu chuyện]' ) . '</h3>'
		. '<p class="story-card__excerpt">' . esc_html( 'en' === $hcwa_lang ? '[Two-line summary of an HCWA-approved story, told from a child\'s or family\'s point of view.]' : '[Hai dòng tóm tắt câu chuyện, kể từ góc nhìn của trẻ hoặc gia đình, đã được HCWA duyệt.]' ) . '</p>'
		. '<p class="story-card__note">' . esc_html( hcwa_t( 'namesChanged' ) ) . '</p></article>';
} else {
	$hcwa_featured = '';
}

if ( ! $hcwa_featured ) {
	echo '<ul ' . hcwa_block_wrapper( 'news-list news-list--row' ) . '>' . $hcwa_list . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
echo '<div ' . hcwa_block_wrapper( 'latest' ) . '>' . $hcwa_featured . ( $hcwa_list ? '<ul class="news-list">' . $hcwa_list . '</ul>' : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
