<?php
/**
 * Photo approval (contract gating rule 2).
 *
 * @package hcwa-core
 */

$approved     = array( 'approved_for_web' => true, 'identifiable_child' => false );
$approved_kid = array( 'approved_for_web' => true, 'identifiable_child' => true );
$unapproved   = array( 'approved_for_web' => false, 'identifiable_child' => false );

hcwa_assert_true( hcwa_image_decision( $approved, false ), 'approved photo shows' );
hcwa_assert_true( hcwa_image_decision( $approved, true ), 'approved, not identifiable: shows on Little Rose' );
hcwa_assert_true( hcwa_image_decision( $approved_kid, false ), 'identifiable child shows where allowed' );
hcwa_assert_false( hcwa_image_decision( $approved_kid, true ), 'identifiable child refused on Little Rose' );
hcwa_assert_false( hcwa_image_decision( $unapproved, false ), 'unapproved photo refused' );
hcwa_assert_false( hcwa_image_decision( $unapproved, true ), 'unapproved photo refused on Little Rose' );
hcwa_assert_false( hcwa_image_decision( array(), false ), 'missing flags count as not approved' );

// Contexts.
hcwa_assert_true( hcwa_image_context_no_identifiable( true ), 'bool context' );
hcwa_assert_true( hcwa_image_context_no_identifiable( array( 'no_identifiable_children' => true ) ), 'array context' );
hcwa_assert_false( hcwa_image_context_no_identifiable( array() ), 'empty array context' );

hcwa_test_post( array( 'ID' => 10, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 11, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '' ) );
hcwa_test_post( array( 'ID' => 20, 'post_type' => 'hcwa_program' ), array( 'hcwa_center_id' => 10 ) );
hcwa_test_post( array( 'ID' => 21, 'post_type' => 'hcwa_program' ), array( 'hcwa_center_id' => 11 ) );

hcwa_assert_true( hcwa_image_context_no_identifiable( 10 ), 'Little Rose center' );
hcwa_assert_false( hcwa_image_context_no_identifiable( 11 ), 'other center' );
hcwa_assert_true( hcwa_image_context_no_identifiable( 20 ), 'program run by Little Rose inherits the rule' );
hcwa_assert_false( hcwa_image_context_no_identifiable( 21 ), 'program of another center' );
hcwa_assert_false( hcwa_image_context_no_identifiable( 999 ), 'unknown post' );

// hcwa_image_allowed with stored flags.
hcwa_test_post( array( 'ID' => 100, 'post_type' => 'attachment', 'post_status' => 'inherit' ), array( 'hcwa_approved_for_web' => '1', 'hcwa_identifiable_child' => '1' ) );
hcwa_test_post( array( 'ID' => 101, 'post_type' => 'attachment', 'post_status' => 'inherit' ), array( 'hcwa_approved_for_web' => '' ) );
hcwa_test_post( array( 'ID' => 102, 'post_type' => 'attachment', 'post_status' => 'inherit' ), array( 'hcwa_approved_for_web' => '1' ) );

hcwa_assert_true( hcwa_image_allowed( 100, 11 ), 'approved identifiable photo on another center' );
hcwa_assert_false( hcwa_image_allowed( 100, 10 ), 'approved identifiable photo refused on Little Rose' );
hcwa_assert_false( hcwa_image_allowed( 100, 20 ), 'and on a Little Rose program' );
hcwa_assert_true( hcwa_image_allowed( 102, 10 ), 'approved non-identifiable photo on Little Rose' );
hcwa_assert_false( hcwa_image_allowed( 101, 11 ), 'unapproved photo refused' );
hcwa_assert_false( hcwa_image_allowed( 999, 11 ), 'missing attachment refused' );
hcwa_assert_false( hcwa_image_allowed( 0 ), 'ID 0 refused' );

hcwa_assert_same( array(), get_option( 'hcwa_image_refusals', array() ), 'refusals are written once, at the end of the request' );
hcwa_image_flush_refusals();
$log = get_option( 'hcwa_image_refusals', array() );
hcwa_assert_true( isset( $log['101:not_approved'], $log['100:identifiable_child'] ), 'refusals logged for the Media library' );
hcwa_assert_same( '', hcwa_image( 101, 'large', array(), 11 ), 'refused image prints nothing' );
hcwa_image_flush_refusals();

// Anonymous visitors never cause a database write.
$GLOBALS['hcwa_test']['options']['hcwa_image_refusals'] = array();
$GLOBALS['hcwa_test']['can']                            = false;
hcwa_assert_false( hcwa_image_allowed( 101, 11 ), 'still refused for visitors' );
hcwa_image_flush_refusals();
hcwa_assert_same( array(), get_option( 'hcwa_image_refusals', array() ), 'but not logged for them' );
$GLOBALS['hcwa_test']['can'] = true;

// The log keeps the 50 newest entries plus everything added in this request.
$old_log = array();
for ( $i = 1; $i <= 60; $i++ ) {
	$old_log[ "$i:not_approved" ] = array( 'id' => $i, 'reason' => 'not_approved', 'context' => 0, 'time' => time() - 2 * DAY_IN_SECONDS - $i );
}
$GLOBALS['hcwa_test']['options']['hcwa_image_refusals'] = $old_log;
hcwa_image_log_refusal( 999, 'not_approved', 0 );
hcwa_image_log_refusal( 999, 'not_approved', 0 );
hcwa_image_flush_refusals();
$log = get_option( 'hcwa_image_refusals', array() );
hcwa_assert_same( 50, count( $log ), 'capped at 50' );
hcwa_assert_true( isset( $log['999:not_approved'], $log['1:not_approved'] ) && ! isset( $log['60:not_approved'] ), 'new entry kept, oldest dropped' );

// Little Rose news: tags of every language version count (Polylang drops an unlinked tag
// when it creates the English version). Protected tags are term IDs.
$lr_tag = new WP_Term( array( 'term_id' => 70, 'slug' => 'hoa-hong-nho-vi', 'taxonomy' => 'post_tag' ) );
$vi_news = hcwa_test_post( array( 'ID' => 80, 'post_type' => 'post' ) );
$en_news = hcwa_test_post( array( 'ID' => 81, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][80]['post_tag'] = array( $lr_tag );
hcwa_assert_true( hcwa_post_has_protected_tag( $vi_news, array( 70 ) ), 'Vietnamese news with the Little Rose tag' );
hcwa_assert_false( hcwa_post_has_protected_tag( $en_news, array( 70 ) ), 'unlinked English version alone has no tag' );
$GLOBALS['hcwa_test']['translations'][81] = array( 'vi' => 80, 'en' => 81 );
hcwa_assert_true( hcwa_post_has_protected_tag( $en_news, array( '70' ) ), 'linked English version inherits the protection' );
hcwa_assert_false( hcwa_post_has_protected_tag( $en_news, array() ), 'no protected tags, nothing protected' );

// The protected set: every protected center's and program's news tag, whatever language's
// slug was typed, plus the live site's Little Rose tags (two English tags: hoa-hong-nho and
// lrws, Vietnamese hoa-hong-nho-vi), each resolved to term IDs.
$GLOBALS['hcwa_test']['term_list'] = array(
	$lr_tag,
	new WP_Term( array( 'term_id' => 71, 'slug' => 'hoa-hong-nho', 'name' => 'Hoa Hồng Nhỏ', 'taxonomy' => 'post_tag' ) ),
	new WP_Term( array( 'term_id' => 72, 'slug' => 'lrws', 'name' => 'LRWS', 'taxonomy' => 'post_tag' ) ),
	new WP_Term( array( 'term_id' => 73, 'slug' => 'tre-xanh', 'name' => 'Tre Xanh', 'taxonomy' => 'post_tag' ) ),
	new WP_Term( array( 'term_id' => 74, 'slug' => 'tiem-banh', 'name' => 'Tiệm bánh', 'taxonomy' => 'post_tag' ) ),
	new WP_Term( array( 'term_id' => 75, 'slug' => 'hoa-hong-nho', 'name' => 'Hoa Hồng Nhỏ', 'taxonomy' => 'category' ) ),
);
hcwa_assert_same( array( 72, 73 ), hcwa_tag_group_ids( array( 'lrws', 'tre-xanh', '', 'lrws' ) ), 'tag slugs resolve to post tag IDs only' );
hcwa_test_post( array( 'ID' => 26, 'post_type' => 'hcwa_program' ), array( 'hcwa_center_id' => 10, 'hcwa_news_tag' => 'tiem-banh' ) );
hcwa_test_post( array( 'ID' => 27, 'post_type' => 'hcwa_program' ), array( 'hcwa_center_id' => 11, 'hcwa_news_tag' => 'tre-xanh' ) );
$GLOBALS['hcwa_test']['post_meta'][10]['hcwa_news_tag'] = 'hoa-hong-nho';
$GLOBALS['hcwa_test']['get_posts'] = static fn( array $args ) => 'hcwa_center' === ( $args['post_type'] ?? '' ) ? array( 10 ) : ( 'hcwa_program' === ( $args['post_type'] ?? '' ) ? array( 26, 27 ) : array() );
$protected = hcwa_no_identifiable_news_tags( true );
sort( $protected );
hcwa_assert_same( array( 70, 71, 72, 74 ), $protected, 'protected: Little Rose tags in any language, lrws, and the news tag of a Little Rose program; not another center\'s program' );
$lrws_news = hcwa_test_post( array( 'ID' => 82, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][82]['post_tag'] = array( $GLOBALS['hcwa_test']['term_list'][2] );
hcwa_assert_true( hcwa_image_context_no_identifiable( 82 ), 'English-only post tagged lrws is a Little Rose context' );
hcwa_assert_false( hcwa_image_allowed( 100, 82 ), 'so an identifiable child is refused there' );

// A news post can be marked itself (imported Little Rose posts with no tag), in any language.
hcwa_test_post( array( 'ID' => 83, 'post_type' => 'post' ), array( 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 84, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['translations'][84] = array( 'vi' => 83, 'en' => 84 );
hcwa_assert_true( hcwa_image_context_no_identifiable( 83 ), 'news post marked "No identifiable children"' );
hcwa_assert_true( hcwa_image_context_no_identifiable( 84 ), 'its English version too' );
hcwa_assert_true( hcwa_content_images_gated( 84 ), 'and its photos need approval' );
hcwa_test_post( array( 'ID' => 87, 'post_type' => 'post' ) );
hcwa_assert_false( hcwa_image_context_no_identifiable( 87 ), 'an ordinary news post is not' );
$fields = hcwa_meta_fields();
hcwa_assert_true( $fields['post']['hcwa_no_identifiable_children']['protective'] ?? false, 'the news flag is protective (never copied, counts in any language)' );
hcwa_assert_same( 'approve', $fields['post']['hcwa_no_identifiable_children']['auth'] ?? '', 'only editors change it' );
$GLOBALS['hcwa_test']['can'] = false;
hcwa_assert_false( hcwa_meta_user_can_edit( $fields['post']['hcwa_no_identifiable_children'] ), 'an author cannot untick it in the details box' );
hcwa_assert_true( hcwa_meta_user_can_edit( $fields['hcwa_center']['hcwa_summary'] ), 'ordinary fields stay editable' );
$GLOBALS['hcwa_test']['can'] = true;

// Words that mark a post as Little Rose news (wp hcwa protect-little-rose, coverage).
$aliases = hcwa_little_rose_aliases();
hcwa_assert_true( in_array( 'lrws', $aliases, true ) && in_array( 'hoa hong nho', $aliases, true ), 'aliases include lrws and the tag name without accents' );
hcwa_assert_same( 'hoa hong nho', hcwa_mentions( '<p>Các em ở Mái ấm <strong>Hoa Hồng Nhỏ</strong> học võ</p>', $aliases ), 'Vietnamese text with accents matches' );
hcwa_assert_same( 'litte rose', hcwa_mentions( 'Viet vu dao martial arts is coming to the Litte Rose Warm Shelter', $aliases ), 'the live site\'s misspelling matches' );
hcwa_assert_same( 'lrws', hcwa_mentions( 'viet-vu-dao-lrws', $aliases ), 'a slug matches' );
hcwa_assert_same( '', hcwa_mentions( 'Our talented girls were awarded scholarships', $aliases ), 'a post naming no alias is left to HCWA\'s list (--ids)' );
hcwa_assert_same( '', hcwa_mentions( 'flrwsx hoa hồng', $aliases ), 'whole words only' );
unset( $GLOBALS['hcwa_test']['get_posts'] );
hcwa_no_identifiable_news_tags( true );

// Legacy story categories (live site: our-stories / chuyen-cua-chung-toi) and translations.
hcwa_test_post( array( 'ID' => 85, 'post_type' => 'post' ) );
hcwa_test_post( array( 'ID' => 86, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][85]['category'] = array( new WP_Term( array( 'term_id' => 76, 'slug' => 'chuyen-cua-chung-toi', 'taxonomy' => 'category' ) ) );
$GLOBALS['hcwa_test']['translations'][86]      = array( 'vi' => 85, 'en' => 86 );
hcwa_assert_true( hcwa_post_is_story( get_post( 85 ) ), 'legacy Vietnamese story category' );
hcwa_assert_true( hcwa_post_is_story( get_post( 86 ) ), 'English version whose own category is not a story one' );
hcwa_assert_true( hcwa_content_images_gated( 86 ), 'its photos need approval' );
hcwa_assert_true( in_array( 'our-stories', hcwa_story_category_slugs(), true ), 'legacy English story category listed' );

// On the live site, until protect-little-rose has run, every news post is gated.
hcwa_assert_false( hcwa_content_images_gated( 87 ), 'staging: ordinary news keeps its inline photos' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_true( hcwa_little_rose_review_pending(), 'production before the review' );
hcwa_assert_true( hcwa_content_images_gated( 87 ), 'production before the review: ordinary news is gated too' );
update_option( 'hcwa_little_rose_review', '2026-11-05' );
hcwa_assert_false( hcwa_content_images_gated( 87 ), 'after the review ordinary news keeps its inline photos' );
$GLOBALS['hcwa_test']['env'] = 'staging';

// A program counts through any language version's center.
hcwa_test_post( array( 'ID' => 22, 'post_type' => 'hcwa_program' ), array() );
$GLOBALS['hcwa_test']['translations'][22] = array( 'vi' => 20, 'en' => 22 );
hcwa_assert_true( hcwa_image_context_no_identifiable( 22 ), 'English program without its own center ID still inherits the rule' );

// Images placed in content without a library ID.
$GLOBALS['hcwa_test']['upload_urls']['https://hcwa.test/wp-content/uploads/2026/01/kid.jpg']        = 101;
$GLOBALS['hcwa_test']['upload_urls']['https://hcwa.test/wp-content/uploads/2026/01/ok.jpg']         = 102;
$GLOBALS['hcwa_test']['upload_urls']['https://hcwa.test/wp-content/uploads/2026/01/big-scaled.jpg'] = 102;
hcwa_assert_same( 101, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/kid.jpg' ), 'upload URL resolves to its attachment' );
hcwa_assert_same( 102, hcwa_content_url_attachment_id( '/wp-content/uploads/2026/01/ok-300x200.jpg' ), 'relative URL of a size resolves too' );
hcwa_assert_same( 102, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/big-1024x768.jpg' ), 'size of a -scaled original resolves' );
hcwa_assert_same( -1, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/unknown.jpg' ), 'unknown upload cannot be checked' );
$GLOBALS['hcwa_test']['upload_urls']['https://hcwa.test/wp-content/uploads/2026/01/new.webp'] = 103;
$GLOBALS['hcwa_test']['upload_urls']['https://hcwa.test/wp-content/uploads/2026/01/old.jpg']  = 104;
hcwa_assert_same( 103, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/new-768x512.webp' ), 'WebP size of a converted upload resolves' );
hcwa_assert_same( 104, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/old-768x512.webp' ), 'WebP size of an upload whose main file is still JPEG resolves' );
hcwa_assert_same( -1, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/gone-768x512.webp' ), 'unknown WebP upload cannot be checked' );

// JPEG uploads are saved as WebP; WordPress itself keeps JPEG when the editor cannot write WebP.
hcwa_assert_same( 'image/webp', hcwa_image_output_format( array() )['image/jpeg'] ?? null, 'JPEG uploads become WebP' );
hcwa_assert_same( 'image/jpeg', hcwa_image_output_format( array( 'image/heic' => 'image/jpeg' ) )['image/heic'], 'core HEIC mapping kept' );
hcwa_assert_false( isset( hcwa_image_output_format( array() )['image/png'] ), 'PNG (logos, illustrations, VietQR) left alone' );
hcwa_assert_same( 'image/avif', hcwa_image_output_format( array( 'image/jpeg' => 'image/avif' ) )['image/jpeg'], 'a mapping set earlier by someone else is kept' );
hcwa_assert_same( 'image/webp', hcwa_image_output_format( array( 'image/jpeg' => 'image/jpeg' ), '', 'image/jpeg' )['image/jpeg'], 'the REST identity probe counts as unset, so client-made sizes are WebP too' );
hcwa_assert_same( -1, hcwa_content_url_attachment_id( 'https://cdn.example.org/kid.jpg' ), 'another site cannot be checked' );
hcwa_assert_same( -1, hcwa_content_url_attachment_id( 'data:image/png;base64,AAAA' ), 'data: URI cannot be checked' );
hcwa_assert_same( 0, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/themes/hcwa-2026/assets/img/a.svg' ), 'theme file is not a library photo' );
hcwa_printed_image_srcs( 'https://hcwa.test/wp-content/uploads/2026/01/logo.png' );
hcwa_assert_same( 0, hcwa_content_url_attachment_id( 'https://hcwa.test/wp-content/uploads/2026/01/logo.png' ), 'printed by gated code in this request (logo, QR, hcwa_image())' );

hcwa_assert_false( hcwa_content_image_decision( -1, true, false ), 'unknown image refused in a gated context' );
hcwa_assert_true( hcwa_content_image_decision( -1, false, false ), 'unknown image left alone elsewhere' );

// Gated content: pages (post 40, Polylang off) and story posts.
$page = hcwa_test_post( array( 'ID' => 90, 'post_type' => 'page' ) );
$GLOBALS['hcwa_test']['current_post'] = $page;
$url_block = '<figure class="wp-block-image"><img src="https://cdn.example.org/kid.jpg" alt="A2 · Homepage hero · 4:5"/></figure>';
hcwa_assert_same( '', hcwa_gate_image_block( $url_block, array( 'attrs' => array() ) ), 'pattern slot filled from a URL: refused on a page' );
$ok_block = '<figure class="wp-block-image"><img src="https://hcwa.test/wp-content/uploads/2026/01/ok.jpg" alt=""/></figure>';
hcwa_assert_same( $ok_block, hcwa_gate_image_block( $ok_block, array( 'attrs' => array() ) ), 'URL of an approved upload: kept' );
hcwa_assert_same( '', hcwa_gate_image_block( '<figure><img src="x.jpg" class="wp-image-101"></figure>', array( 'attrs' => array( 'id' => 101 ) ) ), 'library photo not approved: refused' );

// Classic HTML: the link to the full file and the caption go with the image.
$marker  = hcwa_refused_marker();
$img_tag = '<img src="https://hcwa.test/wp-content/uploads/2026/01/kid.jpg" alt="">';
hcwa_assert_same( $marker, hcwa_gate_content_img_tag( $img_tag, 'the_content', 0 ), 'class-less upload of an unapproved photo: refused' );
hcwa_assert_same( $marker, hcwa_gate_content_img_tag( '<img src="x.jpg" class="wp-image-101">', 'the_content', 101 ), 'wp-image-N of an unapproved photo: refused' );
hcwa_assert_same( $img_tag, hcwa_gate_content_img_tag( $img_tag, 'widget_text_content', 0 ), 'only post content and excerpts are filtered' );
$classic = '<p>Intro</p><figure id="attachment_101" class="wp-caption alignnone"><a href="https://hcwa.test/wp-content/uploads/2026/01/kid.jpg">' . $marker . '</a><figcaption class="wp-caption-text">Lan, 9, at school</figcaption></figure><p><a href="/full.jpg"> ' . $marker . ' </a>After</p>';
hcwa_assert_same( '<p>Intro</p><p>After</p>', hcwa_content_strip_refused( $classic ), 'caption and link around a refused image removed' );
hcwa_assert_same( '<p>Untouched <a href="/x">link</a></p>', hcwa_content_strip_refused( '<p>Untouched <a href="/x">link</a></p>' ), 'content without refusals untouched' );

// Classic [gallery]: only allowed images are passed on.
hcwa_assert_same( '<div class="gallery">102</div>', hcwa_gate_gallery_shortcode( '', array( 'ids' => '101,102' ) ), 'gallery keeps only approved photos' );
hcwa_assert_same( $marker, hcwa_gate_gallery_shortcode( '', array( 'ids' => '101' ) ), 'gallery of refused photos prints nothing' );
hcwa_assert_same( '', hcwa_gate_gallery_shortcode( '', array( 'ids' => '102' ) ), 'all approved: WordPress renders it as usual' );
$GLOBALS['hcwa_test']['children'][90] = array( 101, 102 );
hcwa_assert_same( '<div class="gallery">102</div>', hcwa_gate_gallery_shortcode( '', array() ), 'gallery of attached images gated too' );

// Cover: a fixed background is a div no img filter sees.
$cover = '<div class="wp-block-cover"><div role="img" class="wp-block-cover__image-background wp-image-101 has-parallax" style="background-image:url(https://hcwa.test/wp-content/uploads/2026/01/kid.jpg)"></div><div class="wp-block-cover__inner-container"><p>Text</p></div></div>';
hcwa_assert_same( '<div class="wp-block-cover"><div class="wp-block-cover__inner-container"><p>Text</p></div></div>', hcwa_gate_cover_block( $cover, array( 'attrs' => array( 'id' => 101, 'hasParallax' => true ) ) ), 'refused cover background removed, text kept' );
hcwa_assert_same( $cover, hcwa_gate_cover_block( $cover, array( 'attrs' => array( 'id' => 102 ) ) ), 'approved cover kept' );
hcwa_assert_same( $cover, hcwa_gate_cover_block( $cover, array( 'attrs' => array( 'id' => 101, 'backgroundType' => 'video' ) ) ), 'video cover left alone' );

// Featured images printed by core blocks.
hcwa_test_post( array( 'ID' => 91, 'post_type' => 'post' ) );
hcwa_assert_same( '', hcwa_gate_post_thumbnail_html( '<img src="a.jpg">', 91, 101 ), 'unapproved featured image not printed' );
hcwa_assert_same( '<img src="b.jpg">', hcwa_gate_post_thumbnail_html( '<img src="b.jpg">', 91, 102 ), 'approved featured image printed' );
// Backstop for code that reads the ID itself (share buttons, feeds, other plugins).
hcwa_assert_same( 0, hcwa_gate_post_thumbnail_id( 101, get_post( 91 ) ), 'front end: a refused featured image counts as none' );
hcwa_assert_same( 102, hcwa_gate_post_thumbnail_id( 102, 91 ), 'front end: an approved one is kept' );
hcwa_assert_same( 0, hcwa_gate_post_thumbnail_id( 0, 91 ), 'no featured image stays none' );
hcwa_assert_true( has_filter( 'post_thumbnail_id' ), 'hooked to core get_post_thumbnail_id()' );
hcwa_test_post( array( 'ID' => 310, 'post_type' => 'hcwa_partner' ), array( 'hcwa_logo_permission' => '1' ) );
hcwa_test_post( array( 'ID' => 311, 'post_type' => 'hcwa_partner' ), array() );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '<img src="c.jpg">', hcwa_gate_post_thumbnail_html( '<img src="c.jpg">', 310, 101 ), 'partner logo with permission: shown (photo approval not needed)' );
hcwa_assert_same( '', hcwa_gate_post_thumbnail_html( '<img src="c.jpg">', 311, 102 ), 'partner logo without permission: hidden on production' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$GLOBALS['hcwa_test']['current_post'] = hcwa_test_post( array( 'ID' => 92, 'post_type' => 'post' ), array( '_thumbnail_id' => 101 ) );
$media_text = '<div class="wp-block-media-text"><figure class="wp-block-media-text__media"><img src="a.jpg" class="wp-image-101 size-full"></figure></div>';
hcwa_assert_same( '<div class="wp-block-media-text"><figure class="wp-block-media-text__media"></figure></div>', hcwa_gate_media_text_block( $media_text, array( 'attrs' => array( 'useFeaturedImage' => true ) ) ), 'media-text featured image refused, even in ordinary news' );
hcwa_assert_same( $media_text, hcwa_gate_media_text_block( $media_text, array( 'attrs' => array() ) ), 'media-text library image is left to the img pass' );

// Embeds are switched off.
hcwa_assert_true( has_filter( 'oembed_request_post_id' ), 'oEmbed finds no post' );
$embed_hook = array_values( array_filter( $GLOBALS['hcwa_test']['hooks']['template_redirect'] ?? array(), static fn( $h ) => 'hcwa_disable_embed_view' === $h[0] ) );
hcwa_assert_same( 0, $embed_hook[0][1] ?? null, 'embed view becomes a 404 before any redirect' );

// Partner logos (rule 5).
hcwa_test_post( array( 'ID' => 300, 'post_type' => 'hcwa_partner' ), array( 'hcwa_logo_permission' => '1' ) );
hcwa_test_post( array( 'ID' => 301, 'post_type' => 'hcwa_partner' ), array() );
hcwa_assert_true( hcwa_partner_logo_allowed( 300, true ), 'logo with permission on production' );
hcwa_assert_false( hcwa_partner_logo_allowed( 301, true ), 'logo without permission hidden on production' );
hcwa_assert_true( hcwa_partner_logo_allowed( 301, false ), 'staging shows every logo' );

$GLOBALS['wp_query'] = new class() {
	public $is_embed = true;
	public $is_404   = false;

	public function set_404() {
		$this->is_404 = true; // Like WP_Query::set_404(), is_embed is left as it was.
	}
};
$GLOBALS['hcwa_test']['is']['is_embed'] = true;
hcwa_disable_embed_view();
hcwa_assert_true( $GLOBALS['wp_query']->is_404 && ! $GLOBALS['wp_query']->is_embed, 'embed request: 404 with is_embed cleared, so the 404 template loads' );
hcwa_assert_same( 404, $GLOBALS['hcwa_test']['status'] ?? null, 'and a 404 status' );
unset( $GLOBALS['wp_query'] );

// Attachment pages of refused photos are a 404 in the plugin too (whatever the theme).
hcwa_test_post( array( 'ID' => 520, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 521, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_parent' => 520 ), array( 'hcwa_approved_for_web' => '1', 'hcwa_identifiable_child' => '1' ) );
hcwa_test_post( array( 'ID' => 522, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '1', 'hcwa_identifiable_child' => '1' ) );
hcwa_test_post( array( 'ID' => 523, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array() );
hcwa_test_post( array( 'ID' => 524, 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf' ), array() );
hcwa_assert_true( hcwa_attachment_no_identifiable( 521 ), 'photo attached to Little Rose: no-identifiable context on its own page' );
hcwa_assert_false( hcwa_attachment_no_identifiable( 522 ), 'unattached photo nobody protected uses: ordinary context' );
hcwa_assert_false( hcwa_attachment_no_identifiable( 9999 ), 'no attachment: no context' );
hcwa_assert_true( hcwa_attachment_page_refused( 521 ), 'identifiable child attached to Little Rose: page refused' );
hcwa_assert_false( hcwa_attachment_page_refused( 522 ), 'approved photo in an ordinary context: page kept' );
hcwa_assert_true( hcwa_attachment_page_refused( 523 ), 'photo not approved for web: page refused' );
hcwa_assert_false( hcwa_attachment_page_refused( 524 ), 'report PDF pages are not photo pages' );
hcwa_assert_false( hcwa_attachment_page_refused( 0 ), 'nothing to refuse' );
hcwa_test_post( array( 'ID' => 525, 'post_type' => 'hcwa_center' ), array( '_thumbnail_id' => '7', 'hcwa_logo_id' => 8, 'hcwa_gallery_ids' => array( 9, '10', 7 ) ) );
hcwa_assert_same( array( 7, 8, 9, 10 ), hcwa_center_media_ids( 525 ), 'center media: featured image, logo and gallery, once each' );
hcwa_assert_same( array(), hcwa_center_media_ids( 526 ), 'center media: none' );
$attachment_hook = array_values( array_filter( $GLOBALS['hcwa_test']['hooks']['wp'] ?? array(), static fn( $h ) => 'hcwa_refuse_attachment_page' === $h[0] ) );
hcwa_assert_same( 1, count( $attachment_hook ), 'refused attachment pages become a 404 on "wp", before every template_redirect redirect (Yoast priority 1, redirect_canonical 10)' );
hcwa_assert_same( 0, $attachment_hook[0][2] ?? null, 'called without arguments' );
hcwa_assert_same( array(), array_values( array_filter( $GLOBALS['hcwa_test']['hooks']['template_redirect'] ?? array(), static fn( $h ) => 'hcwa_refuse_attachment_page' === $h[0] ) ), 'not on template_redirect any more' );

$GLOBALS['wp_query'] = new class() {
	public $is_404 = false;

	public function set_404() {
		$this->is_404 = true;
	}
};
unset( $GLOBALS['hcwa_test']['status'] );
$GLOBALS['hcwa_test']['is']['is_attachment'] = true;
hcwa_refuse_attachment_page(); // get_queried_object_id() is 0 in the stubs: nothing to refuse.
hcwa_assert_false( $GLOBALS['wp_query']->is_404, 'no attachment ID: page kept' );
unset( $GLOBALS['wp_query'], $GLOBALS['hcwa_test']['is']['is_attachment'] );

// Attachment text WordPress filled in by itself (file name, text read from the file) is
// never printed; text staff wrote is (hcwa_attachment_text(), alt and caption filters).
hcwa_test_post(
	array( 'ID' => 870, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_title' => 'nguyen-thi-lan-lop-5', 'post_excerpt' => 'Nguyen Thi Lan, lop 5, truong X' ),
	array(
		'_wp_attached_file'        => '2026/11/nguyen-thi-lan-lop-5-scaled.jpg',
		'_wp_attachment_image_alt' => 'Lan at school',
		'_wp_attachment_metadata'  => array( 'image_meta' => array( 'title' => '', 'caption' => 'Nguyen Thi Lan, lop 5, truong X', 'alt' => 'Lan at school' ) ),
	)
);
hcwa_test_post(
	array( 'ID' => 871, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Giờ đọc sách', 'post_excerpt' => '[DRAFT: chú thích]' ),
	array(
		'_wp_attached_file'        => '2026/11/hcwa-20261105-ab12cd34.jpg',
		'_wp_attachment_image_alt' => 'Two volunteers reading aloud',
		'_wp_attachment_metadata'  => array( 'image_meta' => array( 'title' => '', 'caption' => '', 'alt' => '' ) ),
	)
);
hcwa_assert_same( array( '', '', '' ), array( hcwa_attachment_text( 870, 'title' ), hcwa_attachment_text( 870, 'caption' ), hcwa_attachment_text( 870, 'alt' ) ), 'file name and text read from the file: not printed' );
hcwa_assert_same( 'Giờ đọc sách', hcwa_attachment_text( 871, 'title' ), 'title staff wrote' );
hcwa_assert_same( 'Two volunteers reading aloud', hcwa_attachment_text( 871, 'alt' ), 'alt staff wrote' );
hcwa_assert_same( '', hcwa_attachment_text( 90, 'title' ), 'not an attachment' );
hcwa_assert_same( hcwa_attachment_file_name_key( 'Nguyen Thi Lan lop 5' ), hcwa_attachment_file_name_key( 'nguyen-thi-lan-lop-5-1-scaled' ), 'a title made from the file name matches the stored name with WordPress endings' );

$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( '[DRAFT: chú thích]', hcwa_attachment_text( 871, 'caption' ), 'staging: a draft caption is shown for review' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_attachment_text( 871, 'caption' ), 'production: a draft caption is never printed' );
$GLOBALS['hcwa_test']['env'] = 'staging';

$attr = hcwa_attachment_alt_attr( array( 'src' => 'x', 'alt' => 'Lan at school' ), get_post( 870 ) );
hcwa_assert_same( '', $attr['alt'], 'front end: default alt read from the file is emptied' );
$attr = hcwa_attachment_alt_attr( array( 'src' => 'x', 'alt' => 'Two volunteers reading aloud' ), get_post( 871 ) );
hcwa_assert_same( 'Two volunteers reading aloud', $attr['alt'], 'front end: alt staff wrote is kept' );
$attr = hcwa_attachment_alt_attr( array( 'src' => 'x', 'alt' => 'A caller\'s own alt' ), get_post( 870 ) );
hcwa_assert_same( 'A caller\'s own alt', $attr['alt'], 'an alt the caller passed is kept' );
hcwa_assert_same( '', hcwa_attachment_caption( 'Nguyen Thi Lan, lop 5, truong X', 870 ), 'front end: caption read from the file is not printed' );
hcwa_assert_same( 'Volunteers', hcwa_attachment_caption( 'Volunteers', 871 ), 'front end: caption staff wrote is kept' );
hcwa_assert_true( has_filter( 'wp_get_attachment_image_attributes', 'hcwa_attachment_alt_attr' ) && has_filter( 'wp_get_attachment_caption', 'hcwa_attachment_caption' ), 'alt and caption filters added' );
