<?php
/**
 * Integration fixes between hcwa-core, the blocks and the theme (see wp/CONTRACT.md Status).
 * The cross-file checks (keys, functions, blocks, patterns, CSS classes, meta keys) live in
 * scripts/wp-static-check.py.
 *
 * @package hcwa-core
 */

// The plugin loads its blocks unconditionally.
hcwa_assert_true( function_exists( 'hcwa_blocks_register' ), 'hcwa-core.php requires includes/blocks.php' );

// Shared Vietnamese sources: English comes from the dictionary, never from a saved Polylang
// translation that belongs to another key with the same Vietnamese text.
$hcwa_ambiguous = hcwa_ambiguous_sources();
// (The test stubs do not run the hcwa_strings filters, so only ui.json and core keys are here.)
hcwa_assert_true( isset( $hcwa_ambiguous['Ủng hộ'] ), "'Ủng hộ' (donate / topic_donate) is a shared source" );
hcwa_assert_false( isset( $hcwa_ambiguous['Đọc thêm'] ), 'a source used once is not shared' );
hcwa_assert_false( isset( $hcwa_ambiguous['Menu'] ), 'identical vi and en text is not shared' );
hcwa_assert_same(
	array( 'Ngân hàng' => true ),
	hcwa_ambiguous_sources(
		array(
			'a' => array( 'vi' => 'Ngân hàng', 'en' => 'Bank' ),
			'b' => array( 'vi' => 'Ngân hàng', 'en' => 'Bank name' ),
			'c' => array( 'vi' => 'Số', 'en' => 'Number' ),
			'd' => array( 'vi' => 'Số', 'en' => 'Number' ),
		)
	),
	'same vi with different en is shared; same vi with same en is not'
);

$hcwa_saved = static fn( $source, $lang ) => 'en' === $lang ? 'Our Work' : $source;
$hcwa_entry = array( 'vi' => 'Hoạt động', 'en' => 'Activities' );
hcwa_assert_same( 'Our Work', hcwa_translate_entry( $hcwa_entry, 'en', $hcwa_saved ), 'unshared source: saved translation wins' );
hcwa_assert_same( 'Activities', hcwa_translate_entry( $hcwa_entry, 'en', $hcwa_saved, true ), 'shared source: our English wins' );
hcwa_assert_same( 'Hoạt động', hcwa_translate_entry( $hcwa_entry, 'vi', $hcwa_saved, true ), 'shared source: Vietnamese still read through Polylang' );
hcwa_assert_same( 'Donating', hcwa_t_in( 'topic_donate', 'en' ), 'hcwa_t_in en for a shared source' );
hcwa_assert_same( 'Donate', hcwa_t_in( 'donate', 'en' ), 'hcwa_t_in keeps each key its own English' );

// hreflang x-default = the Vietnamese URL (07-BILINGUAL), on every template Polylang prints.
hcwa_assert_same(
	array(
		'vi'        => 'https://hcwa.test/hoat-dong/',
		'en'        => 'https://hcwa.test/en/our-work/',
		'x-default' => 'https://hcwa.test/hoat-dong/',
	),
	hcwa_hreflang_x_default(
		array(
			'vi' => 'https://hcwa.test/hoat-dong/',
			'en' => 'https://hcwa.test/en/our-work/',
		)
	),
	'x-default added from vi'
);
hcwa_assert_same(
	array(
		'vi'        => 'https://hcwa.test/',
		'x-default' => 'https://hcwa.test/root/',
	),
	hcwa_hreflang_x_default(
		array(
			'vi'        => 'https://hcwa.test/',
			'x-default' => 'https://hcwa.test/root/',
		)
	),
	'an existing x-default is kept'
);
hcwa_assert_same( array( 'en' => 'https://hcwa.test/en/' ), hcwa_hreflang_x_default( array( 'en' => 'https://hcwa.test/en/' ) ), 'no vi entry, no x-default' );
hcwa_assert_contains(
	"add_filter( 'pll_rel_hreflang_attributes', 'hcwa_hreflang_x_default' );",
	(string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/polylang.php' ),
	'x-default filter is hooked to Polylang'
);

// Theme pieces that depend on core and block output.
$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';
$hcwa_pattern = (string) file_get_contents( $hcwa_theme_dir . '/patterns/page-our-work-en.php' );
hcwa_assert_contains( '<!-- wp:hcwa/work-hub ', $hcwa_pattern, 'Our Work pattern holds the work hub (its ?area= chips are printed by hcwa_render_work_hub())' );
$hcwa_hub = hcwa_render_work_hub( array( 'centersLead' => 'Cơ sở', 'programsLead' => 'Dự án' ) );
hcwa_assert_contains( '<a class="chip chip--all" href="./" data-area="">', $hcwa_hub, 'work hub: the "all" chip works without JS' );
hcwa_assert_same( 2, substr_count( $hcwa_hub, 'data-filter-group' ), 'work hub: places and times are filter groups' );
hcwa_assert_contains( 'data-filter-status aria-live="polite"', $hcwa_hub, 'work hub: the filter status is announced' );

// Partner logos: only hcwa/partner-wall prints them, through the rule-5 gate; the program
// page names its funders as text (2.5 P3), as the prototype does.
$hcwa_program = (string) file_get_contents( $hcwa_theme_dir . '/single-hcwa_program.php' );
hcwa_assert_false( str_contains( $hcwa_program, 'wp_get_attachment_image' ) || str_contains( $hcwa_program, 'hcwa_logo_permission' ), 'program page prints no partner logo' );
hcwa_assert_contains( 'hcwa_partner_logo_allowed(', (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/blocks-sections.php' ), 'the partner wall gates logos with hcwa_partner_logo_allowed()' );

// Rule 2 for photos placed in content (core/image blocks, classic HTML, cover, media-text).
hcwa_assert_true( hcwa_content_image_decision( 0, true, false ), 'not a library photo (printed by gated code, or a theme file): left alone' );
hcwa_assert_false( hcwa_content_image_decision( -1, true, false ), 'cannot be checked (another site, unknown upload) in a gated context: removed' );
hcwa_assert_true( hcwa_content_image_decision( 5, false, false ), 'ungated context: left alone' );
hcwa_assert_false( hcwa_content_image_decision( 5, true, false ), 'gated context, not allowed: removed' );
hcwa_assert_true( hcwa_content_image_decision( 5, true, true ), 'gated context, allowed: kept' );

hcwa_test_post( array( 'ID' => 301, 'post_type' => 'page' ) );
hcwa_test_post( array( 'ID' => 302, 'post_type' => 'hcwa_center' ) );
hcwa_test_post( array( 'ID' => 303, 'post_type' => 'post' ) );
hcwa_test_post( array( 'ID' => 304, 'post_type' => 'post' ) );
hcwa_test_post( array( 'ID' => 305, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][303]['category'] = array( new WP_Term( array( 'term_id' => 1, 'slug' => 'cau-chuyen', 'taxonomy' => 'category' ) ) );
$GLOBALS['hcwa_test']['terms'][304]['category'] = array( new WP_Term( array( 'term_id' => 2, 'slug' => 'tin-tuc', 'taxonomy' => 'category' ) ) );
$GLOBALS['hcwa_test']['terms'][305]['category'] = array( new WP_Term( array( 'term_id' => 3, 'slug' => 'stories-en', 'taxonomy' => 'category' ) ) );
hcwa_assert_true( hcwa_content_images_gated( 301 ), 'pages are gated (pattern image slots)' );
hcwa_assert_true( hcwa_content_images_gated( 302 ), 'center bodies are gated' );
hcwa_assert_true( hcwa_content_images_gated( 303 ), 'story posts are gated' );
hcwa_assert_true( hcwa_content_images_gated( 305 ), 'story category with a language suffix is gated' );
hcwa_assert_false( hcwa_content_images_gated( 304 ), 'ordinary news posts keep their inline photos' );
hcwa_assert_false( hcwa_content_images_gated( 999 ), 'no post, no gate' );

$hcwa_img = '<img src="https://hcwa.test/a.jpg" class="wp-image-5">';
hcwa_assert_same( $hcwa_img, hcwa_gate_content_img_tag( $hcwa_img, 'the_content', 0 ), 'outside a gated post, img tags pass' );
hcwa_assert_same( $hcwa_img, hcwa_gate_content_img_tag( $hcwa_img, 'widget_text_content', 5 ), 'only post content and excerpts are filtered' );
hcwa_assert_same( 'x', hcwa_gate_image_block( 'x', array( 'attrs' => array() ) ), 'core/image without an ID passes' );

// One category map in core (hcwa_category_slugs()): the theme's chips and labels, the
// blocks' card labels and `wp hcwa coverage` read it. Legacy story categories count as
// stories, so the "names changed" note and the story photo gate always agree.
hcwa_assert_same( array( 'news', 'stories', 'events', 'announcements' ), array_keys( hcwa_category_slugs() ), 'four sections' );
hcwa_assert_same( 'stories', hcwa_category_key( array( 'our-stories' ) ), 'legacy English story category is a story' );
hcwa_assert_same( 'stories', hcwa_category_key( array( 'chuyen-cua-chung-toi-vi' ) ), 'legacy Vietnamese story category, suffixed' );
hcwa_assert_same( 'events', hcwa_category_key( array( 'other', 'su-kien-vi' ) ), 'seeded section through a translation slug' );
hcwa_assert_same( '', hcwa_category_key( array( 'uncategorized' ) ), 'not a section' );
hcwa_test_post( array( 'ID' => 306, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][306]['category'] = array( new WP_Term( array( 'term_id' => 4, 'slug' => 'our-stories', 'taxonomy' => 'category', 'name' => 'Our stories' ) ) );
hcwa_assert_same( hcwa_t( 'cat_story' ), hcwa_post_category_label( get_post( 306 ) ), 'news lists label legacy stories as stories (core category map)' );

hcwa_assert_same( hcwa_category_slugs(), hcwa_theme_category_slugs(), 'theme reads the core category map' );
hcwa_assert_same( 'stories', hcwa_theme_category_key( new WP_Term( array( 'term_id' => 9310, 'slug' => 'our-stories', 'taxonomy' => 'category' ) ) ), 'theme card label: legacy story category is a story' );
hcwa_test_post( array( 'ID' => 9301, 'post_type' => 'post' ) );
hcwa_test_post( array( 'ID' => 9302, 'post_type' => 'post' ) );
$GLOBALS['hcwa_test']['terms'][9301]['category'] = array( new WP_Term( array( 'term_id' => 9311, 'slug' => 'chuyen-cua-chung-toi', 'taxonomy' => 'category' ) ) );
$GLOBALS['hcwa_test']['terms'][9302]['category'] = array( new WP_Term( array( 'term_id' => 9312, 'slug' => 'news', 'taxonomy' => 'category' ) ) );
$GLOBALS['hcwa_test']['translations'][9301]      = array( 'vi' => 9301, 'en' => 9302 );
$GLOBALS['hcwa_test']['translations'][9302]      = array( 'vi' => 9301, 'en' => 9302 );
hcwa_assert_true( hcwa_theme_is_story( get_post( 9301 ) ), 'names-changed note on a legacy story post' );
hcwa_assert_true( hcwa_theme_is_story( get_post( 9302 ) ), 'and on its English version filed under news' );
hcwa_assert_same( hcwa_content_images_gated( 9302 ), hcwa_theme_is_story( get_post( 9302 ) ), 'the note and the photo gate agree' );
hcwa_assert_false( hcwa_theme_is_story( get_post( 304 ) ), 'ordinary news: no note' );
unset( $GLOBALS['hcwa_test']['translations'][9301], $GLOBALS['hcwa_test']['translations'][9302] );
