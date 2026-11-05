<?php
/**
 * hcwa-2026 theme: the gates added after the Phase 2 review. Draft text from settings and
 * captions, attachment pages, the language switch and notice, category chips in one
 * language, the VI name on EN pages, no library photos without hcwa-core, lazy-loading
 * after the cover, the top bars before first paint, and the CSS hard rules.
 * Loads only the theme's helper files (no templates, no WordPress).
 *
 * @package hcwa-2026
 */

$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';
require_once $hcwa_theme_dir . '/inc/assets.php'; // Theme file helpers: integration-stubs.php.

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
hcwa_assert_contains( "hcwa_theme_public_text( wp_strip_all_tags( get_bloginfo( 'description' ) ) )", $hcwa_footer, 'footer tagline goes through the draft check' );
hcwa_assert_contains( "hcwa_theme_draft_attr( \$hcwa_registration )", $hcwa_footer, 'footer marks a draft registration line on staging' );
hcwa_assert_contains( 'hcwa_theme_public_text( (string) wp_get_attachment_caption(', $hcwa_read( 'single.php' ), 'post cover caption goes through the draft check' );
hcwa_assert_contains( 'hcwa_theme_public_text( wp_strip_all_tags( get_the_excerpt() ) )', $hcwa_read( 'attachment.php' ), 'attachment caption goes through the draft check' );
hcwa_assert_contains( 'hcwa_theme_public_text( trim( wp_strip_all_tags( (string) $description ) ) )', $hcwa_read( 'inc/assets.php' ), 'meta description goes through the draft check' );

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
hcwa_assert_contains( "add_action( 'template_redirect', 'hcwa_theme_refuse_attachment_page', 5 )", $hcwa_setup, 'refused attachment pages are handled before redirect_canonical' );
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
foreach ( array( 'single.php', 'single-hcwa_center.php', 'single-hcwa_program.php' ) as $hcwa_file ) {
	hcwa_assert_true( (bool) preg_match( '/if \( \'\' !== \$hcwa_cover \)[\s\S]{0,400}?hcwa_theme_cover_printed\(\);/', $hcwa_read( $hcwa_file ) ), "$hcwa_file counts the cover only when it was printed" );
}

// ---------- Top bars decided before first paint ----------
$hcwa_bars = hcwa_theme_bars_js();
hcwa_assert_contains( 'window.hcwaBars = bars;', $hcwa_bars, 'bars.js is inlined in the head' );
hcwa_assert_contains( 'hcwaBars.enBar(document.currentScript.previousElementSibling)', $hcwa_header, 'EN bar decided right after it is printed' );
hcwa_assert_contains( 'hcwaBars.announce(document.currentScript.previousElementSibling)', $hcwa_header, 'announcement decided right after it is printed' );
$hcwa_site_js = $hcwa_read( 'assets/js/site.js' );
hcwa_assert_false( str_contains( $hcwa_site_js, 'navigator.language' ), 'site.js no longer shows the EN bar after paint' );
hcwa_assert_contains( 'bars.announceKey(box)', $hcwa_site_js, 'site.js uses the same announcement key as bars.js' );
hcwa_assert_contains( '"hcwa-en-bar"', $hcwa_site_js, 'site.js remembers the EN bar dismissal' );
hcwa_assert_contains( '"hcwa-en-bar"', $hcwa_bars, 'bars.js reads the same EN bar key' );
hcwa_assert_contains( 's.tabIndex = desktop.matches ? -1 : 0;', $hcwa_site_js, 'inert desktop footer summaries leave the tab order' );

// ---------- CSS hard rules and layout ----------
$hcwa_css = $hcwa_read( 'assets/css/site.css' );
hcwa_assert_true( (bool) preg_match( '/\.entry-content blockquote, \.entry-content \.wp-block-quote \{[^}]*font: 500 var\(--fs-lg\) \/ var\(--lh-body\)/', $hcwa_css ), 'quotes use the body line-height' );
hcwa_assert_true( (bool) preg_match( '/\.entry-content blockquote cite \{[^}]*\/ 1\.6 /', $hcwa_css ), 'quote citations at 1.6' );
hcwa_assert_false( str_contains( $hcwa_css, '.entry-content :where(li) + li' ), 'list spacing no longer leaks into block lists' );
hcwa_assert_contains( '.entry-content :where(ul:not([class]), ol:not([class]), .wp-block-list) > li + li', $hcwa_css, 'list spacing for prose lists' );
hcwa_assert_contains( '.entry-content--page > :where([class*="wp-block-hcwa-"]):not(.alignwide):not(.alignfull) { width: min(100% - 2 * var(--gutter), var(--container)); margin-inline: auto; }', $hcwa_css, 'hcwa blocks in a page body keep to the container' );
hcwa_assert_true( (bool) preg_match( '/\.lang-switch a \{[^}]*min-width: 44px;[^}]*min-height: 44px;/', $hcwa_css ), 'VI / EN links are 44 x 44' );
hcwa_assert_true( (bool) preg_match( '/\.nav__toggle \{ width: 44px; height: 44px;/', $hcwa_css ), 'submenu toggle is 44 x 44' );
hcwa_assert_false( (bool) preg_match( '/\.nav__toggle \{[^}]*margin-left: -/', $hcwa_css ), 'submenu toggle does not overlap its link' );
hcwa_assert_true( (bool) preg_match( '/\.report-row__title a \{[^}]*min-height: 44px;/', $hcwa_css ), 'report links are 44 px tall' );
