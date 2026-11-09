<?php
/**
 * hcwa-2026 theme: the gates added after the Phase 2 review. Draft text from settings and
 * captions, attachment pages, the language switch and notice, category chips in one
 * language, the VI name on EN pages, no library photos without hcwa-core, lazy-loading
 * after the cover, attachment text
 * nobody wrote (file names, text read from the file) and the description Yoast lacks.
 * Loads only the theme's helper files (no templates, no WordPress).
 *
 * @package hcwa-2026
 */

$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';
require_once $hcwa_theme_dir . '/inc/setup.php';
require_once $hcwa_theme_dir . '/inc/assets.php';

$hcwa_read = static fn( string $file ): string => (string) file_get_contents( $hcwa_theme_dir . '/' . $file );

// ---------- Draft text that skips the publish guard (settings, captions, tagline) ----------
$hcwa_draft = '[DRAFT: legal status / decision number]';
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array(
	'registration_vi' => $hcwa_draft,
	'address_vi'      => '[DRAFT: địa chỉ]',
	'address_en'      => '14 Example Street',
	'phone'           => '[DRAFT: phone]',
	'facebook'        => 'https://www.facebook.com/hcwa',
);

$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( $hcwa_draft, hcwa_theme_public_text( $hcwa_draft ), 'staging: draft text is shown' );
hcwa_assert_same( ' data-draft', hcwa_theme_draft_attr( $hcwa_draft ), 'staging: draft text gets the data-draft outline' );
hcwa_assert_same( '', hcwa_theme_draft_attr( 'Hội Bảo trợ Trẻ em' ), 'no outline on ordinary text' );
hcwa_assert_same( $hcwa_draft, hcwa_theme_setting_l( 'registration' ), 'staging: draft registration line shown for review' );
hcwa_assert_same( '[DRAFT: phone]', hcwa_theme_setting( 'phone', '' ), 'staging: draft phone shown for review' );

$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_theme_public_text( $hcwa_draft ), 'production: draft text is never printed' );
hcwa_assert_same( '', hcwa_theme_public_text( 'Ảnh: [Draft caption]' ), 'production: draft marker in any case' );
hcwa_assert_same( 'Ảnh: lớp học buổi sáng', hcwa_theme_public_text( 'Ảnh: lớp học buổi sáng' ), 'production: ordinary caption kept' );
hcwa_assert_same( '', hcwa_theme_setting_l( 'registration' ), 'production: draft registration line is dropped' );
hcwa_assert_same( '', hcwa_theme_setting_l( 'address' ), 'production: draft address is dropped' );
hcwa_assert_same( '', hcwa_theme_setting( 'phone', '' ), 'production: draft phone falls back to the default' );
hcwa_assert_same( 'https://www.facebook.com/hcwa', hcwa_theme_setting( 'facebook', '' ), 'production: ordinary setting kept' );
$GLOBALS['hcwa_test']['env'] = 'staging';

$hcwa_footer = $hcwa_read( 'footer.php' );
hcwa_assert_contains( "hcwa_theme_draft_text( (string) get_bloginfo( 'description' ) )", $hcwa_footer, 'footer tagline goes through the draft rule' );
hcwa_assert_contains( "<p class=\"foot-brand__reg\"<?php echo \$hcwa_registration['draft'] ? ' data-draft' : ''; ?>>", $hcwa_footer, 'footer marks a draft registration line on staging' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_theme_draft_text( $hcwa_draft )['text'], 'production: a draft registration line prints nothing' );
$GLOBALS['hcwa_test']['env'] = 'staging';
// Captions go through hcwa_theme_attachment_text(), which ends in hcwa_theme_public_text() (tested below).
hcwa_assert_contains( "hcwa_theme_attachment_text( \$hcwa_thumb_id, 'caption' )", $hcwa_read( 'single.php' ), 'post cover caption: staff-written and draft-checked' );
hcwa_assert_contains( "hcwa_theme_attachment_text( (int) get_the_ID(), 'caption' )", $hcwa_read( 'attachment.php' ), 'attachment caption: staff-written and draft-checked' );

// ---------- Attachment pages ----------
hcwa_test_post( array( 'ID' => 501, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => true ) );
hcwa_test_post( array( 'ID' => 502, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_parent' => 501 ), array( 'hcwa_approved_for_web' => true, 'hcwa_identifiable_child' => true ) );
hcwa_test_post( array( 'ID' => 503, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => true, 'hcwa_identifiable_child' => true ) );
hcwa_test_post( array( 'ID' => 504, 'post_type' => 'attachment', 'post_mime_type' => 'image/png' ), array( 'hcwa_approved_for_web' => false ) );
hcwa_test_post( array( 'ID' => 505, 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf' ) );
hcwa_test_post( array( 'ID' => 506, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => true ) );

hcwa_assert_true( hcwa_theme_attachment_no_identifiable( 502 ), 'photo attached to Little Rose is in a no-identifiable context' );
hcwa_assert_true( hcwa_theme_attachment_refused( 502 ), 'identifiable child attached to Little Rose: page refused' );
hcwa_assert_false( hcwa_theme_attachment_no_identifiable( 503 ), 'unattached photo nobody protected uses: ordinary context' );
hcwa_assert_false( hcwa_theme_attachment_refused( 503 ), 'approved photo in an ordinary context: page shown' );
hcwa_assert_true( hcwa_theme_attachment_refused( 504 ), 'photo not approved for web: page refused' );
hcwa_assert_false( hcwa_theme_attachment_refused( 505 ), 'PDF attachment pages are not photo pages' );
hcwa_assert_false( hcwa_theme_attachment_refused( 506 ), 'approved photo, not identifiable: page shown' );
hcwa_assert_false( hcwa_theme_attachment_refused( 0 ), 'no attachment, nothing to refuse' );

hcwa_test_post( array( 'ID' => 510, 'post_type' => 'hcwa_center' ), array( '_thumbnail_id' => '7', 'hcwa_logo_id' => 8, 'hcwa_gallery_ids' => array( 9, '10', 7 ) ) );
hcwa_assert_same( array( 7, 8, 9, 10 ), hcwa_theme_center_media_ids( 510 ), 'center media: featured image, logo and gallery, once each' );
hcwa_assert_same( array(), hcwa_theme_center_media_ids( 511 ), 'center without media' );

$hcwa_setup = $hcwa_read( 'inc/setup.php' );
hcwa_assert_contains( "add_action( 'wp', 'hcwa_theme_refuse_attachment_page', 5, 0 )", $hcwa_setup, 'refused attachment pages become a 404 on "wp", before every template_redirect redirect (SEO plugin, redirect_canonical)' );
hcwa_assert_false( str_contains( $hcwa_setup, "add_action( 'template_redirect', 'hcwa_theme_refuse_attachment_page'" ), 'not on template_redirect any more (the plugin moved too)' );
hcwa_assert_contains( '$wp_query->set_404();', $hcwa_setup, 'refused attachment page becomes a 404' );
hcwa_assert_contains( "\$robots['noindex'] = true;", $hcwa_setup, 'attachment pages are noindex on production' );
hcwa_assert_contains( 'hcwa_theme_attachment_no_identifiable( get_the_ID() )', $hcwa_read( 'attachment.php' ), 'attachment page uses the full context, not the parent only' );

// ---------- Language switch and notice ----------
$hcwa_switch = hcwa_theme_lang_switch();
hcwa_assert_contains( 'lang="vi" hreflang="vi" aria-current="true"', $hcwa_switch, 'current language keeps hreflang' );
hcwa_assert_contains( 'lang="en">', $hcwa_switch, 'link without a translation keeps lang' );
hcwa_assert_false( str_contains( $hcwa_switch, 'hreflang="en"' ), 'link without a translation promises no hreflang' );

hcwa_test_post( array( 'ID' => 520, 'post_type' => 'post' ) );
hcwa_assert_same( 0, hcwa_theme_published_translation( 520, 'en' ), 'no Polylang: no other-language version' );
hcwa_assert_contains( '0 === hcwa_theme_published_translation(', $hcwa_read( 'single.php' ), 'the notice only shows while there is no published translation' );
hcwa_assert_contains( "add_action( 'template_redirect', 'hcwa_theme_lang_notice_redirect' )", $hcwa_setup, 'a translated post redirects ?hcwa_lang_notice= to the translation' );

$hcwa_header = $hcwa_read( 'header.php' );
hcwa_assert_contains( "! empty( \$hcwa_links['en']['has_translation'] )", $hcwa_header, 'EN bar promises this page only when it is translated' );
hcwa_assert_contains( "hcwa_theme_t_in( 'langNoticeHub', 'en' )", $hcwa_header, 'EN bar on a VI-only article says it leads to English news' );
hcwa_assert_false( str_contains( $hcwa_header, "\$hcwa_links['en']['url'] ); ?>\" hreflang" ), 'EN bar never links the untranslated switch target' );

// ---------- Category chips stay in one language ----------
$hcwa_vi_term = new WP_Term( array( 'term_id' => 30, 'slug' => 'cau-chuyen', 'name' => 'Câu chuyện', 'taxonomy' => 'category' ) );
$hcwa_en_term = new WP_Term( array( 'term_id' => 31, 'slug' => 'stories', 'name' => 'Stories', 'taxonomy' => 'category' ) );
hcwa_assert_same( $hcwa_vi_term, hcwa_theme_pick_term( $hcwa_vi_term, null, null, true ), 'no Polylang: the term as found' );
hcwa_assert_same( $hcwa_vi_term, hcwa_theme_pick_term( $hcwa_vi_term, 30, null, true ), 'term already in this language' );
hcwa_assert_same( $hcwa_en_term, hcwa_theme_pick_term( $hcwa_vi_term, 31, $hcwa_en_term, true ), 'translation in this language' );
hcwa_assert_same( null, hcwa_theme_pick_term( $hcwa_vi_term, 0, null, true ), 'strict: no English version, no chip' );
hcwa_assert_same( $hcwa_vi_term, hcwa_theme_pick_term( $hcwa_vi_term, 0, null, false ), 'lenient: falls back to the term as found' );
hcwa_assert_same( null, hcwa_theme_pick_term( $hcwa_vi_term, 31, null, true ), 'strict: unreadable translation, no chip' );
hcwa_assert_contains( "hcwa_theme_term_in_lang( 'category', \$slug, true )", $hcwa_read( 'inc/template-tags.php' ), 'category chips use the strict lookup' );

// ---------- VI name on EN pages ----------
$hcwa_vi = static fn( array $props ): WP_Post => new WP_Post( $props + array( 'ID' => 540, 'post_type' => 'hcwa_center', 'post_status' => 'publish', 'post_title' => 'Mái ấm Hoa Hồng Nhỏ' ) );
hcwa_assert_same( 'Mái ấm Hoa Hồng Nhỏ', hcwa_theme_vi_name_of( $hcwa_vi( array() ), 'Little Rose Warm Shelter' ), 'published VI name shown' );
hcwa_assert_same( '', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_status' => 'draft' ) ), 'Little Rose Warm Shelter' ), 'draft VI translation: no name' );
hcwa_assert_same( '', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_status' => 'pending' ) ), 'Little Rose Warm Shelter' ), 'pending VI translation: no name' );
hcwa_assert_same( '', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_status' => 'private' ) ), 'Little Rose Warm Shelter' ), 'private VI translation: no name' );
hcwa_assert_same( '', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_password' => 'x' ) ), 'Little Rose Warm Shelter' ), 'password-protected VI translation: no name' );
hcwa_assert_same( '', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_title' => 'Cầu Hàn' ) ), 'Cầu Hàn' ), 'same name in both languages: not repeated' );
hcwa_assert_same( '', hcwa_theme_vi_name_of( null, 'Little Rose Warm Shelter' ), 'no VI post' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_title' => '[DRAFT: tên cơ sở]' ) ), 'Little Rose Warm Shelter' ), 'production: draft VI name never shown' );
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( '[DRAFT: tên cơ sở]', hcwa_theme_vi_name_of( $hcwa_vi( array( 'post_title' => '[DRAFT: tên cơ sở]' ) ), 'Little Rose Warm Shelter' ), 'staging: draft VI name shown for review' );

// ---------- No library photos without hcwa-core ----------
hcwa_assert_same( '', hcwa_theme_drop_library_image_block( '<figure class="wp-block-image"><img src="a.jpg"></figure>', array( 'attrs' => array( 'id' => 12 ) ) ), 'core/image with a library photo dropped' );
hcwa_assert_same( '<figure></figure>', hcwa_theme_drop_library_image_block( '<figure></figure>', array( 'attrs' => array() ) ), 'core/image without a library ID kept' );
hcwa_assert_same( '', hcwa_theme_drop_library_img_tag( '<img class="wp-image-12">', 'the_content', 12 ), 'library img in content dropped' );
hcwa_assert_same( '<img src="https://example.org/a.jpg">', hcwa_theme_drop_library_img_tag( '<img src="https://example.org/a.jpg">', 'the_content', 0 ), 'external img kept' );
hcwa_assert_same( '', trim( hcwa_theme_drop_gallery( '' ) ), 'classic gallery prints nothing' );
hcwa_assert_true( '' !== hcwa_theme_drop_gallery( '' ), 'classic gallery: non-empty return so core does not build it' );
$hcwa_no_core_hooks = array();
foreach ( array( 'render_block_core/image', 'wp_content_img_tag', 'post_gallery', 'post_thumbnail_html' ) as $hcwa_hook ) {
	foreach ( $GLOBALS['hcwa_test']['hooks'][ $hcwa_hook ] ?? array() as $hcwa_entry ) {
		if ( is_string( $hcwa_entry[0] ) && in_array( $hcwa_entry[0], array( 'hcwa_theme_drop_library_image_block', 'hcwa_theme_drop_library_img_tag', 'hcwa_theme_drop_gallery', '__return_empty_string' ), true ) ) {
			$hcwa_no_core_hooks[] = $hcwa_hook;
		}
	}
}
hcwa_assert_same( array(), $hcwa_no_core_hooks, 'with hcwa-core active the theme leaves photos to the core gate' );

// ---------- Lazy-loading after the cover ----------
$hcwa_before = wp_increase_content_media_count( 0 );
hcwa_theme_cover_printed();
hcwa_assert_same( $hcwa_before + 3, wp_increase_content_media_count( 0 ), 'the cover uses up the eager allowance, so body images are lazy' );
foreach ( array( 'single.php' ) as $hcwa_file ) {
	hcwa_assert_true( (bool) preg_match( '/if \( \'\' !== \$hcwa_cover \)[\s\S]{0,400}?hcwa_theme_cover_printed\(\);/', $hcwa_read( $hcwa_file ) ), "$hcwa_file counts the cover only when it was printed" );
}
// Center and program pages print no cover photo (2.5: the lit header carries the page).
foreach ( array( 'single-hcwa_center.php', 'single-hcwa_program.php' ) as $hcwa_file ) {
	hcwa_assert_false( str_contains( $hcwa_read( $hcwa_file ), 'get_post_thumbnail_id' ), "$hcwa_file prints no cover photo" );
}



// ---------- Attachment text nobody wrote: the file name, IPTC / EXIF / XMP text ----------
$GLOBALS['hcwa_test']['env'] = 'staging';
// Uploaded as "nguyen-thi-lan-lop-5.jpg" (a second one with that name, so -1), big (-scaled),
// converted to WebP; the photo app wrote the caption and alt into the file.
hcwa_test_post(
	array( 'ID' => 560, 'post_type' => 'attachment', 'post_mime_type' => 'image/webp', 'post_title' => 'nguyen-thi-lan-lop-5', 'post_excerpt' => 'Nguyen Thi Lan, lop 5, truong X' ),
	array(
		'_wp_attached_file'        => '2026/11/nguyen-thi-lan-lop-5-1-scaled.webp',
		'_wp_attachment_image_alt' => 'Lan at school',
		'_wp_attachment_metadata'  => array(
			'original_image' => 'nguyen-thi-lan-lop-5-1.jpg',
			'image_meta'     => array( 'title' => '', 'caption' => 'Nguyen Thi Lan, lop 5, truong X', 'alt' => 'Lan at school' ),
		),
	)
);
hcwa_assert_same( '', hcwa_theme_attachment_text( 560, 'title' ), 'title still the file name: not printed' );
hcwa_assert_same( '', hcwa_theme_attachment_text( 560, 'caption' ), 'caption still the text read from the file: not printed' );
hcwa_assert_same( '', hcwa_theme_attachment_text( 560, 'alt' ), 'alt still the XMP alt text from the file: not printed' );
hcwa_assert_same( 'Ảnh', hcwa_theme_attachment_heading( 560 ), 'photo page without a written title is headed "Ảnh"' );
hcwa_assert_same( 'Photo', hcwa_theme_t_in( 'photo', 'en' ), 'and "Photo" in English' );

// Title from the IPTC headline / EXIF description, in another case and spacing; REST upload
// keeps the raw client file name as the title.
hcwa_test_post(
	array( 'ID' => 561, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Olympus  digital camera' ),
	array( '_wp_attached_file' => '2019/04/P1010203.JPG', '_wp_attachment_metadata' => array( 'image_meta' => array( 'title' => 'OLYMPUS DIGITAL CAMERA', 'caption' => 'OLYMPUS DIGITAL CAMERA' ) ) )
);
hcwa_assert_same( '', hcwa_theme_attachment_text( 561, 'title' ), 'title read from the file: not printed, whatever the case' );
hcwa_test_post( array( 'ID' => 562, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Nguyen Thi Lan lop 5' ), array( '_wp_attached_file' => '2026/11/Nguyen-Thi-Lan-lop-5.jpg' ) );
hcwa_assert_same( '', hcwa_theme_attachment_text( 562, 'title' ), 'raw client file name as the title: not printed' );

// Staff wrote the text: printed, draft-checked like any caption.
hcwa_test_post(
	array( 'ID' => 563, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Reading hour at the library', 'post_excerpt' => 'Volunteers read with a group of children.' ),
	array(
		'_wp_attached_file'        => '2026/11/img-2041.jpg',
		'_wp_attachment_image_alt' => 'Two volunteers reading aloud to a group of children',
		'_wp_attachment_metadata'  => array( 'image_meta' => array( 'title' => '', 'caption' => 'OLYMPUS DIGITAL CAMERA', 'alt' => '' ) ),
	)
);
hcwa_assert_same( 'Reading hour at the library', hcwa_theme_attachment_text( 563, 'title' ), 'title staff wrote is printed' );
hcwa_assert_same( 'Volunteers read with a group of children.', hcwa_theme_attachment_text( 563, 'caption' ), 'caption staff rewrote is printed' );
hcwa_assert_same( 'Two volunteers reading aloud to a group of children', hcwa_theme_attachment_text( 563, 'alt' ), 'alt staff wrote is printed' );
hcwa_assert_same( 'Reading hour at the library', hcwa_theme_attachment_heading( 563 ), 'written title heads the attachment page' );
hcwa_test_post( array( 'ID' => 564, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_excerpt' => '[DRAFT: chú thích ảnh]' ) );
hcwa_assert_same( '[DRAFT: chú thích ảnh]', hcwa_theme_attachment_text( 564, 'caption' ), 'staging: draft caption shown for review' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_theme_attachment_text( 564, 'caption' ), 'production: draft caption never printed' );
$GLOBALS['hcwa_test']['env'] = 'staging';

hcwa_test_post( array( 'ID' => 565, 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf', 'post_title' => 'bao-cao-2025' ), array( '_wp_attached_file' => '2026/01/bao-cao-2025.pdf' ) );
hcwa_assert_same( 'Tệp đính kèm', hcwa_theme_attachment_heading( 565 ), 'other file without a written title is headed "Tệp đính kèm"' );
hcwa_assert_same( '', hcwa_theme_attachment_text( 520, 'title' ), 'not an attachment: nothing' );
hcwa_assert_same( '', hcwa_theme_attachment_text( 0, 'caption' ), 'no attachment: nothing' );
hcwa_assert_same( '', hcwa_theme_attachment_text( 563, 'description' ), 'unknown field: nothing' );
hcwa_assert_same( hcwa_theme_text_key( 'OLYMPUS DIGITAL CAMERA' ), hcwa_theme_text_key( ' olympus   digital camera ' ), 'text key ignores case and spacing' );
hcwa_assert_same( 'lop-hoc', hcwa_theme_file_name_key( 'lop-hoc-2-scaled' ), 'file name key drops the endings WordPress adds' );
hcwa_assert_same( 'lop-hoc', hcwa_theme_file_name_key( 'lop-hoc-e1730000000000' ), 'file name key drops the edit stamp' );

$hcwa_attachment_tpl = $hcwa_read( 'attachment.php' );
hcwa_assert_contains( 'hcwa_theme_attachment_heading( (int) get_the_ID() )', $hcwa_attachment_tpl, 'attachment page heading never prints the file name' );
hcwa_assert_false( str_contains( $hcwa_attachment_tpl, 'get_the_title() )' ), 'attachment page no longer prints its raw title' );
$hcwa_core_sections = (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/blocks-sections.php' );
hcwa_assert_contains( 'hcwa_image(', substr( $hcwa_core_sections, (int) strpos( $hcwa_core_sections, 'function hcwa_center_photos' ), 1200 ), 'center gallery photos go through hcwa_image() (alt only as staff wrote it, core filter)' );

// Browser title of an attachment page.
hcwa_assert_same( array( 'title' => 'Tin tức' ), hcwa_theme_attachment_document_title( array( 'title' => 'Tin tức' ) ), 'other pages keep their browser title' );
$GLOBALS['hcwa_test']['is']['is_attachment'] = true;
$hcwa_parts = hcwa_theme_attachment_document_title( array( 'title' => 'nguyen-thi-lan-lop-5', 'site' => 'HCWA' ) );
hcwa_assert_same( hcwa_theme_attachment_heading( (int) get_queried_object_id() ), $hcwa_parts['title'], 'attachment page browser title is the page heading' );
hcwa_assert_same( 'HCWA', $hcwa_parts['site'], 'site name kept' );
unset( $GLOBALS['hcwa_test']['is']['is_attachment'] );
hcwa_assert_true( has_filter( 'document_title_parts' ), 'browser title filter registered' );

// Search never lists attachments.
$hcwa_searchable = array( 'post', 'page', 'attachment', 'hcwa_center', 'hcwa_program', 'hcwa_report' );
$hcwa_content    = array( 'post', 'page', 'hcwa_center', 'hcwa_program', 'hcwa_report' );
hcwa_assert_same( $hcwa_content, hcwa_theme_search_post_types( '', $hcwa_searchable ), 'search: every searchable type except attachments' );
hcwa_assert_same( $hcwa_content, hcwa_theme_search_post_types( 'any', $hcwa_searchable ), "search: 'any' without attachments" );
hcwa_assert_same( $hcwa_content, hcwa_theme_search_post_types( 'attachment', $hcwa_searchable ), 'search: ?post_type=attachment lists content instead' );
hcwa_assert_same( array( 'hcwa_report' ), hcwa_theme_search_post_types( array( 'hcwa_report', 'attachment' ), $hcwa_searchable ), 'search: types asked for kept, attachments dropped' );
hcwa_assert_same( array( 'post' ), hcwa_theme_search_post_types( '', array( 'attachment' ) ), 'search: never an empty list (WordPress would read it as any)' );
hcwa_assert_contains( "hcwa_theme_search_post_types( \$query->get( 'post_type' )", $hcwa_read( 'inc/setup.php' ), 'the main search query uses it' );

// ---------- Description Yoast lacks: the page's own, draft-safe ----------
hcwa_test_post( array( 'ID' => 570, 'post_type' => 'hcwa_center', 'post_excerpt' => 'Excerpt' ), array( 'hcwa_summary' => 'Lớp học và bữa ăn cho trẻ em <strong>Tre Xanh</strong>.' ) );
hcwa_test_post( array( 'ID' => 571, 'post_type' => 'hcwa_program', 'post_excerpt' => 'Hand-written excerpt.' ) );
hcwa_test_post( array( 'ID' => 572, 'post_type' => 'hcwa_report' ), array( 'hcwa_summary' => '[DRAFT: tóm tắt báo cáo]' ) );
hcwa_test_post( array( 'ID' => 573, 'post_type' => 'hcwa_program' ), array( 'hcwa_summary' => str_repeat( 'Dự án hỗ trợ học tập. ', 20 ) ) );

hcwa_assert_same( '', hcwa_theme_page_description(), 'not a single page or term: no description' );
hcwa_assert_same( 'Yoast text', hcwa_theme_seo_description_fallback( 'Yoast text' ), "Yoast's own description wins" );
hcwa_assert_same( '', hcwa_theme_seo_description_fallback( '' ), 'nothing of our own: Yoast keeps its empty value' );
$GLOBALS['hcwa_test']['is']['is_singular'] = true;
$GLOBALS['hcwa_test']['queried']           = get_post( 570 );
hcwa_assert_same( 'Lớp học và bữa ăn cho trẻ em Tre Xanh.', hcwa_theme_seo_description_fallback( '' ), 'center without a Yoast description: its summary' );
hcwa_assert_same( 'Lớp học và bữa ăn cho trẻ em Tre Xanh.', hcwa_theme_seo_description_fallback( '  ' ), 'blank Yoast description counts as empty' );
hcwa_assert_same( 'Written in Yoast', hcwa_theme_seo_description_fallback( 'Written in Yoast' ), 'center with a Yoast description keeps it' );
$GLOBALS['hcwa_test']['queried'] = get_post( 571 );
hcwa_assert_same( 'Hand-written excerpt.', hcwa_theme_page_description(), 'no summary: the hand-written excerpt' );
$GLOBALS['hcwa_test']['queried'] = get_post( 572 );
hcwa_assert_same( '[DRAFT: tóm tắt báo cáo]', hcwa_theme_page_description(), 'staging: draft summary shown for review' );
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '', hcwa_theme_seo_description_fallback( '' ), 'production: draft summary never fills in' );
$GLOBALS['hcwa_test']['env'] = 'staging';
$GLOBALS['hcwa_test']['queried'] = get_post( 573 );
hcwa_assert_true( mb_strlen( hcwa_theme_page_description() ) <= 161 && str_ends_with( hcwa_theme_page_description(), '…' ), 'long summary cut to 160 characters' );
$GLOBALS['hcwa_test']['queried'] = get_post( 560 );
hcwa_assert_same( '', hcwa_theme_page_description(), 'photo page: a caption read from the file is no description' );
$GLOBALS['hcwa_test']['queried'] = get_post( 563 );
hcwa_assert_same( 'Volunteers read with a group of children.', hcwa_theme_page_description(), 'photo page: the caption staff wrote' );
unset( $GLOBALS['hcwa_test']['is']['is_singular'], $GLOBALS['hcwa_test']['queried'] );

foreach ( array( 'wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description' ) as $hcwa_hook ) {
	$hcwa_hooked = array_filter( $GLOBALS['hcwa_test']['hooks'][ $hcwa_hook ] ?? array(), static fn( array $entry ): bool => 'hcwa_theme_seo_description_fallback' === $entry[0] );
	hcwa_assert_true( (bool) $hcwa_hooked, "$hcwa_hook falls back to the page's own description" );
	hcwa_assert_true( (int) ( array_values( $hcwa_hooked )[0][1] ?? 0 ) > 99, "$hcwa_hook: after hcwa-core drops draft SEO text (priority 99)" );
}
hcwa_assert_false( hcwa_theme_seo_plugin_active(), 'no SEO plugin in the tests: the theme prints the meta description itself' );

// ---------- Little Rose: no location, no invitation to visit ----------
$GLOBALS['hcwa_test']['post_meta'][9101] = array( 'hcwa_no_identifiable_children' => '1', 'hcwa_location' => 'Phường X, TP. Hồ Chí Minh' );
$GLOBALS['hcwa_test']['post_meta'][9102] = array( 'hcwa_location' => 'Phường Y, TP. Hồ Chí Minh' );
hcwa_assert_true( hcwa_theme_center_is_protected( 9101 ), 'a center flagged no-identifiable is protected' );
hcwa_assert_false( hcwa_theme_center_is_protected( 9102 ), 'an ordinary center is not protected' );
// The center page markup is built in hcwa-core (11-WP-PORT.md 2.5 P2).
hcwa_test_post( array( 'ID' => 9101, 'post_type' => 'hcwa_center', 'post_title' => 'Mái ấm Hoa Hồng Nhỏ' ) );
hcwa_test_post( array( 'ID' => 9102, 'post_type' => 'hcwa_center', 'post_title' => 'Tre Xanh' ) );
hcwa_assert_same( '', hcwa_center_view( get_post( 9101 ) )['location']['text'], 'protected centers print no location' );
hcwa_assert_same( 'Phường Y, TP. Hồ Chí Minh', hcwa_center_view( get_post( 9102 ) )['location']['text'], 'an ordinary center prints its location' );
$hcwa_core_sections = (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/blocks-sections.php' );
hcwa_assert_contains( "\$c['protected'] ? 'supportTitle' : 'visitTitle'", $hcwa_core_sections, 'protected centers do not invite visits' );

