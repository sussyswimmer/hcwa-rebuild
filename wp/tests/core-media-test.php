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
// when it creates the English version).
$lr_tag = new WP_Term( array( 'term_id' => 70, 'slug' => 'hoa-hong-nho', 'taxonomy' => 'post_tag' ) );
$vi_news = hcwa_test_post( array( 'ID' => 80, 'post_type' => 'post' ) );
$en_news = hcwa_test_post( array( 'ID' => 81, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][80]['post_tag'] = array( $lr_tag );
hcwa_assert_true( hcwa_post_has_protected_tag( $vi_news, array( 'hoa-hong-nho' ) ), 'Vietnamese news with the Little Rose tag' );
hcwa_assert_false( hcwa_post_has_protected_tag( $en_news, array( 'hoa-hong-nho' ) ), 'unlinked English version alone has no tag' );
$GLOBALS['hcwa_test']['translations'][81] = array( 'vi' => 80, 'en' => 81 );
hcwa_assert_true( hcwa_post_has_protected_tag( $en_news, array( 'hoa-hong-nho' ) ), 'linked English version inherits the protection' );
hcwa_assert_false( hcwa_post_has_protected_tag( $en_news, array() ), 'no protected tags, nothing protected' );

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
$attachment_hook = array_values( array_filter( $GLOBALS['hcwa_test']['hooks']['template_redirect'] ?? array(), static fn( $h ) => 'hcwa_refuse_attachment_page' === $h[0] ) );
hcwa_assert_same( 5, $attachment_hook[0][1] ?? null, 'refused attachment pages become a 404 before redirect_canonical' );
