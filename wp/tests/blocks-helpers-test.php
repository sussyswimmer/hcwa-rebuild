<?php
/**
 * hcwa/* blocks: pure helpers in includes/blocks.php (attributes, sorting, formatting,
 * category and language decisions, editor translations).
 *
 * @package hcwa-core
 */

// Attributes: defaults, clamping, allowed values.
hcwa_assert_same( 4, hcwa_blocks_int( array(), 'limit', 4, 1, 12 ), 'int: default when missing' );
hcwa_assert_same( 12, hcwa_blocks_int( array( 'limit' => 99 ), 'limit', 4, 1, 12 ), 'int: clamped to max' );
hcwa_assert_same( 1, hcwa_blocks_int( array( 'limit' => -3 ), 'limit', 4, 1, 12 ), 'int: clamped to min' );
hcwa_assert_same( 4, hcwa_blocks_int( array( 'limit' => 'abc' ), 'limit', 4, 1, 12 ), 'int: not numeric gives default' );
hcwa_assert_same( 7, hcwa_blocks_int( array( 'limit' => '7' ), 'limit', 4, 1, 12 ), 'int: numeric string' );
hcwa_assert_same( 'center', hcwa_blocks_enum( array( 'scopeType' => 'center' ), 'scopeType', array( 'organization', 'center' ), 'organization' ), 'enum: allowed value' );
hcwa_assert_same( 'organization', hcwa_blocks_enum( array( 'scopeType' => 'x' ), 'scopeType', array( 'organization', 'center' ), 'organization' ), 'enum: unknown value gives default' );
hcwa_assert_same( 'organization', hcwa_blocks_enum( array( 'scopeType' => array() ), 'scopeType', array( 'organization' ), 'organization' ), 'enum: non-string gives default' );
hcwa_assert_same( '', hcwa_blocks_enum( array( 'forceLang' => '' ), 'forceLang', array( '', 'vi', 'en' ), '' ), 'enum: empty string is allowed' );
hcwa_assert_true( hcwa_blocks_bool( array(), 'showLink', true ), 'bool: default when missing' );
hcwa_assert_false( hcwa_blocks_bool( array( 'showLink' => false ), 'showLink', true ), 'bool: false' );
hcwa_assert_false( hcwa_blocks_bool( array( 'showLink' => 'no' ), 'showLink', true ), 'bool: unknown string is false' );
hcwa_assert_true( hcwa_blocks_bool( array( 'homeOnly' => '1' ), 'homeOnly', false ), 'bool: "1"' );

// Context post: global post, else 0 outside a request.
hcwa_assert_same( 0, hcwa_blocks_context_post_id( null ), 'context post: none outside a request' );

// Query arguments: published only, filters on (Polylang needs them).
$hcwa_q = hcwa_blocks_query_args( 'hcwa_center', 3 );
hcwa_assert_same( 'publish', $hcwa_q['post_status'], 'query: published only' );
hcwa_assert_false( $hcwa_q['suppress_filters'], 'query: filters on' );
hcwa_assert_same( 3, $hcwa_q['numberposts'], 'query: number' );
hcwa_assert_false( isset( $hcwa_q['lang'] ), 'query: no lang argument without Polylang' );

// Impact numbers: whole numbers without decimals, localized separators, token when missing.
hcwa_assert_same( 0, hcwa_blocks_decimals( 300000.0 ), 'decimals: whole number' );
hcwa_assert_same( 1, hcwa_blocks_decimals( 12.5 ), 'decimals: one place' );
hcwa_assert_same( 2, hcwa_blocks_decimals( 3.25 ), 'decimals: two places' );
hcwa_assert_same( '300.000+', hcwa_blocks_figure_value( array( 'value' => 300000.0, 'suffix' => '+' ), 'vi' ), 'figure value vi' );
hcwa_assert_same( '300,000+', hcwa_blocks_figure_value( array( 'value' => 300000.0, 'suffix' => '+' ), 'en' ), 'figure value en' );
hcwa_assert_same( '12,5%', hcwa_blocks_figure_value( array( 'value' => 12.5, 'suffix' => '%' ), 'vi' ), 'figure value with decimals vi' );
hcwa_assert_same( '[N]', hcwa_blocks_figure_value( array( 'value' => null, 'suffix' => '+' ), 'en' ), 'figure without value shows the token' );

// Program years and status order (06-PAGES: active first).
hcwa_assert_same( '2023–2026', hcwa_blocks_year_range( 2023, 2026 ), 'years: range' );
hcwa_assert_same( '2023–', hcwa_blocks_year_range( 2023, 0 ), 'years: still running' );
hcwa_assert_same( '2024', hcwa_blocks_year_range( 2024, 2024 ), 'years: one year' );
hcwa_assert_same( '2020', hcwa_blocks_year_range( 0, 2020 ), 'years: end only' );
hcwa_assert_same( '', hcwa_blocks_year_range( 0, 0 ), 'years: unknown' );
$hcwa_sorted = hcwa_blocks_sort_programs(
	array(
		array( 'title' => 'A', 'status' => 'completed' ),
		array( 'title' => 'B', 'status' => 'active' ),
		array( 'title' => 'C', 'status' => 'planned' ),
		array( 'title' => 'D', 'status' => 'active' ),
		array( 'title' => 'E', 'status' => 'odd' ),
	)
);
hcwa_assert_same( array( 'B', 'D', 'C', 'A', 'E' ), array_column( $hcwa_sorted, 'title' ), 'programs: active, planned, completed; query order kept inside a status' );
hcwa_assert_same( array(), hcwa_blocks_sort_programs( array() ), 'programs: empty list' );

// Reports: newest year first, no year last, ties keep the incoming order.
$hcwa_sorted = hcwa_blocks_sort_by_year(
	array(
		array( 'id' => 1, 'year' => 2023 ),
		array( 'id' => 2, 'year' => 0 ),
		array( 'id' => 3, 'year' => 2025 ),
		array( 'id' => 4, 'year' => 2023 ),
	)
);
hcwa_assert_same( array( 3, 1, 4, 2 ), array_column( $hcwa_sorted, 'id' ), 'reports: year desc, ties stable, no year last' );

// File sizes and document badges.
hcwa_assert_same( '', hcwa_blocks_file_size( 0, 'vi' ), 'size: none' );
hcwa_assert_same( '1 KB', hcwa_blocks_file_size( 200, 'en' ), 'size: tiny files show 1 KB' );
hcwa_assert_same( '820 KB', hcwa_blocks_file_size( 820 * 1024, 'vi' ), 'size: KB' );
hcwa_assert_same( '1,2 MB', hcwa_blocks_file_size( (int) ( 1.2 * 1024 * 1024 ), 'vi' ), 'size: MB vi' );
hcwa_assert_same( '1.2 MB', hcwa_blocks_file_size( (int) ( 1.2 * 1024 * 1024 ), 'en' ), 'size: MB en' );
hcwa_assert_same( '24 MB', hcwa_blocks_file_size( 24 * 1024 * 1024, 'en' ), 'size: large files without decimals' );
hcwa_assert_same( '<span class="badge">PDF · <span lang="vi">Tiếng Việt</span></span>', hcwa_blocks_doc_badge( 'pdf', 'vi' ), 'badge: Vietnamese PDF' );
hcwa_assert_same( '<span class="badge">PDF · <span lang="vi">Tiếng Việt</span>, <span lang="en">English</span></span>', hcwa_blocks_doc_badge( 'pdf', 'both' ), 'badge: both languages' );
hcwa_assert_same( '<span class="badge">DOCX</span>', hcwa_blocks_doc_badge( 'docx', '' ), 'badge: unknown language shows the format only' );

// Focus areas and categories (contract: VI slugs may carry -vi).
hcwa_assert_same( 'education', hcwa_blocks_area_key( 'education-vi' ), 'area: -vi suffix removed' );
hcwa_assert_same( 'protection', hcwa_blocks_area_key( 'protection' ), 'area: plain slug' );
hcwa_assert_same( 'family-en', hcwa_blocks_area_key( 'family-en-en' ), 'area: only the last suffix' );
hcwa_assert_same( ' data-areas="education family"', hcwa_blocks_areas_attr( array( 'education', '', 'family' ) ), 'areas attribute' );
hcwa_assert_same( '', hcwa_blocks_areas_attr( array() ), 'no areas, no attribute' );
hcwa_assert_same( 'stories', hcwa_blocks_category_key( array( 'cau-chuyen' ) ), 'category: VI stories' );
hcwa_assert_same( 'news', hcwa_blocks_category_key( array( 'news' ) ), 'category: EN news' );
hcwa_assert_same( 'events', hcwa_blocks_category_key( array( 'su-kien-vi' ) ), 'category: suffixed VI slug' );
hcwa_assert_same( 'announcements', hcwa_blocks_category_key( array( 'other', 'thong-bao' ) ), 'category: found through a translation slug' );
hcwa_assert_same( '', hcwa_blocks_category_key( array( 'uncategorized' ) ), 'category: not a seeded one' );
hcwa_assert_same( 'cat_story', hcwa_blocks_category_label_key( 'stories' ), 'label key: stories' );
hcwa_assert_same( 'blk_catAnnouncement', hcwa_blocks_category_label_key( 'announcements' ), 'label key: announcements' );
hcwa_assert_same( '', hcwa_blocks_category_label_key( '' ), 'label key: none' );

// Featured story: sticky first, then the newest story, then the newest post.
hcwa_assert_same( 2, hcwa_blocks_pick_featured( array( array( 'category' => 'stories' ), array( 'category' => 'news' ), array( 'sticky' => true, 'category' => 'news' ) ) ), 'featured: sticky wins' );
hcwa_assert_same( 1, hcwa_blocks_pick_featured( array( array( 'category' => 'news' ), array( 'category' => 'stories' ), array( 'category' => 'stories' ) ) ), 'featured: newest story' );
hcwa_assert_same( 0, hcwa_blocks_pick_featured( array( array( 'category' => 'news' ), array( 'category' => 'events' ) ) ), 'featured: newest post' );
hcwa_assert_same( -1, hcwa_blocks_pick_featured( array() ), 'featured: nothing' );

// Language notice: forceLang beats the request; never in the page's own language.
hcwa_assert_same( 'en', hcwa_blocks_notice_lang( '', 'en', 'vi' ), 'notice: request on a VI post' );
hcwa_assert_same( 'vi', hcwa_blocks_notice_lang( 'vi', 'en', 'en' ), 'notice: forceLang wins' );
hcwa_assert_same( '', hcwa_blocks_notice_lang( '', '', 'vi' ), 'notice: nothing asked' );
hcwa_assert_same( '', hcwa_blocks_notice_lang( '', 'vi', 'vi' ), 'notice: same language as the page' );
hcwa_assert_same( '', hcwa_blocks_notice_lang( 'fr', '', 'vi' ), 'notice: unknown language' );

// Accent and method anchors.
hcwa_assert_same( 'little-rose', hcwa_blocks_accent( 'little-rose' ), 'accent: known' );
hcwa_assert_same( 'blue', hcwa_blocks_accent( 'red";x' ), 'accent: unknown falls back to blue' );
hcwa_assert_same( array( 'in_vietnam' => 'trong-nuoc', 'abroad' => 'quoc-te' ), hcwa_blocks_method_ids( 'vi' ), 'method ids vi' );
hcwa_assert_same( array( 'in_vietnam' => 'in-vietnam', 'abroad' => 'from-abroad' ), hcwa_blocks_method_ids( 'en' ), 'method ids en' );

// Draft markers are outlined on staging only.
hcwa_assert_same( ' data-draft', hcwa_blocks_draft_attr( false, 'Done', '[DRAFT: text]' ), 'draft attr on staging' );
hcwa_assert_same( '', hcwa_blocks_draft_attr( true, '[DRAFT: text]' ), 'no draft attr on production' );
hcwa_assert_same( '', hcwa_blocks_draft_attr( false, 'Plain text' ), 'no draft attr for plain text' );

// Icons are static, decorative SVG.
hcwa_assert_contains( 'aria-hidden="true"', hcwa_blocks_icon( 'arrow' ), 'icon hidden from screen readers' );
hcwa_assert_contains( 'class="icon program-row__arrow"', hcwa_blocks_icon( 'arrow', 'icon program-row__arrow' ), 'icon class' );
hcwa_assert_same( '', hcwa_blocks_icon( 'nope' ), 'unknown icon prints nothing' );
hcwa_assert_same(
	'<a class="text-link" href="https://hcwa.test/en/news/" hreflang="en"><span>See &quot;news&quot;</span>' . hcwa_blocks_icon( 'arrow' ) . '</a>',
	hcwa_blocks_text_link( 'https://hcwa.test/en/news/', 'See "news"', array( 'hreflang' => 'en' ) ),
	'text link escapes its label'
);
hcwa_assert_contains( 'href=""', hcwa_blocks_text_link( 'javascript:alert(1)', 'x' ), 'text link drops javascript: URLs' );

// Inserter category: added once, first.
$hcwa_cats = hcwa_blocks_category( array( array( 'slug' => 'text', 'title' => 'Text', 'icon' => null ) ) );
hcwa_assert_same( 'hcwa', $hcwa_cats[0]['slug'], 'category added first' );
hcwa_assert_same( 2, count( hcwa_blocks_category( $hcwa_cats ) ), 'category not added twice' );
hcwa_assert_same( 'hcwa', hcwa_blocks_category( null )[0]['slug'], 'category from nothing' );

// Hooks.
$hcwa_hooks = $GLOBALS['hcwa_test']['hooks'];
$hcwa_has   = static function ( string $hook, string $callback ) use ( $hcwa_hooks ): bool {
	foreach ( $hcwa_hooks[ $hook ] ?? array() as $entry ) {
		if ( $entry[0] === $callback ) {
			return true;
		}
	}
	return false;
};
hcwa_assert_true( $hcwa_has( 'init', 'hcwa_blocks_register' ), 'blocks register on init' );
hcwa_assert_true( $hcwa_has( 'block_categories_all', 'hcwa_blocks_category' ), 'category filter' );
hcwa_assert_true( $hcwa_has( 'hcwa_strings', 'hcwa_blocks_add_strings' ), 'strings filter' );
hcwa_assert_true( $hcwa_has( 'pre_load_script_translations', 'hcwa_blocks_script_translations' ), 'editor translations filter' );

// Editor translations: our handles only, Vietnamese locale only, Jed shape.
hcwa_assert_true( hcwa_blocks_is_editor_handle( 'hcwa-blocks-editor' ), 'shared editor handle' );
hcwa_assert_true( hcwa_blocks_is_editor_handle( 'hcwa-impact-strip-editor-script' ), 'block editor handle' );
hcwa_assert_false( hcwa_blocks_is_editor_handle( 'hcwa-editor-guard' ), 'core plugin script is not ours' );
hcwa_assert_false( hcwa_blocks_is_editor_handle( 'wp-blocks' ), 'WordPress script is not ours' );
hcwa_assert_same( null, hcwa_blocks_script_translations( null, false, 'wp-blocks', 'hcwa' ), 'other handles untouched' );
hcwa_assert_same( null, hcwa_blocks_script_translations( null, false, 'hcwa-blocks-editor', 'default' ), 'other domains untouched' );
hcwa_assert_same( 'kept', hcwa_blocks_script_translations( 'kept', false, 'hcwa-blocks-editor', 'hcwa' ), 'an earlier filter result is kept' );
$hcwa_json = json_decode( (string) hcwa_blocks_script_translations( null, false, 'hcwa-center-grid-editor-script', 'hcwa' ), true );
hcwa_assert_same( 'Lưới cơ sở', $hcwa_json['locale_data']['hcwa']['Center grid'][0] ?? null, 'Vietnamese editor label served' );
hcwa_assert_same( 'hcwa', $hcwa_json['locale_data']['hcwa']['']['domain'] ?? null, 'Jed header' );
