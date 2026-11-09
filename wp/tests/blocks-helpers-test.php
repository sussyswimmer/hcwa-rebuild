<?php
/**
 * hcwa/* blocks: the small helpers in includes/blocks.php (attributes, text attributes, markup
 * helpers, language notice, editor translations, hooks).
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

// Text attributes (4.1 attribute format, 2.1 rule 7): plain text, draft unwrapped off production.
hcwa_assert_same( array( 'text' => 'Trẻ em & gia đình', 'draft' => false ), hcwa_blocks_text( array( 'title' => 'Trẻ em &amp; gia đình' ), 'title' ), 'text attr: stored &amp; prints as one &' );
hcwa_assert_same( array( 'text' => 'Một hai', 'draft' => false ), hcwa_blocks_text( array( 'title' => 'Một<br>hai' ), 'title' ), 'text attr: a soft break becomes a space' );
hcwa_assert_same( array( 'text' => 'Lời chào', 'draft' => true ), hcwa_blocks_text( array( 'intro' => '[DRAFT: Lời chào]' ), 'intro' ), 'text attr: draft unwrapped on staging' );
hcwa_assert_same( array( 'text' => '', 'draft' => false ), hcwa_blocks_text( array(), 'intro' ), 'text attr: missing is empty' );
hcwa_assert_same( array( 'text' => '', 'draft' => false ), hcwa_blocks_text( array( 'intro' => array( 'x' ) ), 'intro' ), 'text attr: not a string is empty' );
hcwa_assert_same( array( 'text' => 'Câu hỏi', 'draft' => true ), hcwa_blocks_item_text( array( 'q' => '[DRAFT: Câu hỏi]' ), 'q' ), 'item text: draft item' );
hcwa_assert_same( array( 'text' => '', 'draft' => false ), hcwa_blocks_item_text( 'loose', 'q' ), 'item text: not an array' );
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_prod = hcwa_blocks_text( array( 'intro' => '[DRAFT: Lời chào]' ), 'intro' );
hcwa_assert_same( '', $hcwa_prod['text'], 'text attr: draft prints nothing on production' );
hcwa_assert_same( '', hcwa_pending_chip_html(), 'pending chip: none on production' );
hcwa_assert_same( '', hcwa_draft_attr( true ), 'draft attr: never on production (7.7), even when a caller asks for it' );
$GLOBALS['hcwa_test']['env'] = 'staging';

// Context post: global post, else 0 outside a request.
hcwa_assert_same( 0, hcwa_blocks_context_post_id( null ), 'context post: none outside a request' );

// Query arguments: published only, filters on (Polylang needs them).
$hcwa_q = hcwa_blocks_query_args( 'hcwa_center', 3 );
hcwa_assert_same( 'publish', $hcwa_q['post_status'], 'query: published only' );
hcwa_assert_false( $hcwa_q['suppress_filters'], 'query: filters on' );
hcwa_assert_same( 3, $hcwa_q['numberposts'], 'query: number' );
hcwa_assert_false( isset( $hcwa_q['lang'] ), 'query: no lang argument without Polylang' );

// Focus area keys (the four of work.json, in the prototype's order).
hcwa_assert_same( 'education', hcwa_area_key( 'education-vi' ), 'area: -vi suffix removed' );
hcwa_assert_same( 'protection', hcwa_area_key( 'protection' ), 'area: plain slug' );
hcwa_assert_same( '', hcwa_area_key( 'family-en-en' ), 'area: only one suffix is removed' );
hcwa_assert_same( '', hcwa_area_key( 'sports' ), 'area: not one of the four' );

// Markup helpers (prototype components.mjs).
hcwa_assert_same( ' data-draft', hcwa_draft_attr( true ), 'draft attr' );
hcwa_assert_same( '', hcwa_draft_attr( false ), 'no draft attr' );
hcwa_assert_same( '<a class="btn btn--primary" href="https://hcwa.test/ung-ho/" data-donate>Ủng hộ</a>', hcwa_button_html( 'https://hcwa.test/ung-ho/', 'Ủng hộ', 'primary', 'data-donate' ), 'button: link' );
hcwa_assert_same( '<button class="btn btn--secondary x" type="button">A &amp; B</button>', hcwa_button_html( '', 'A & B', 'secondary', '', 'x' ), 'button: no href is a button, label escaped' );
hcwa_assert_same(
	'<a class="text-link" href="https://hcwa.test/en/news/" hreflang="en"><span>See &quot;news&quot;</span>' . hcwa_icon( 'arrow' ) . '</a>',
	hcwa_text_link_html( 'https://hcwa.test/en/news/', 'See "news"', 'hreflang="en"' ),
	'text link escapes its label'
);
hcwa_assert_contains( 'href=""', hcwa_text_link_html( 'javascript:alert(1)', 'x' ), 'text link drops javascript: URLs' );
hcwa_assert_contains( 'aria-hidden="true"', hcwa_icon( 'arrow' ), 'icon hidden from screen readers' );
hcwa_assert_same( '<span class="status status--completed">' . esc_html( hcwa_t( 'status_completed' ) ) . '</span>', hcwa_status_pill_html( 'completed' ), 'status pill' );
hcwa_assert_contains( 'data-pending', hcwa_pending_chip_html(), 'pending chip on staging' );
hcwa_assert_same( '--unit:var(--color-unit-tre-xanh)', hcwa_unit_style( array( 'color' => 'var(--color-unit-tre-xanh)' ) ), 'unit style' );
$hcwa_crumb = hcwa_breadcrumb_html( array( array( 'Hoạt động', 'https://hcwa.test/hoat-dong/' ), array( 'Tre Xanh' ) ) );
hcwa_assert_contains( '<a href="https://hcwa.test/hoat-dong/">Hoạt động</a>', $hcwa_crumb, 'breadcrumb: ancestor linked' );
hcwa_assert_contains( '<span aria-current="page">Tre Xanh</span>', $hcwa_crumb, 'breadcrumb: last is the page' );
hcwa_assert_same( 3, substr_count( $hcwa_crumb, '<li>' ), 'breadcrumb: home plus the trail' );

// Language notice: forceLang beats the request; never in the page's own language.
hcwa_assert_same( 'en', hcwa_blocks_notice_lang( '', 'en', 'vi' ), 'notice: request on a VI post' );
hcwa_assert_same( 'vi', hcwa_blocks_notice_lang( 'vi', 'en', 'en' ), 'notice: forceLang wins' );
hcwa_assert_same( '', hcwa_blocks_notice_lang( '', '', 'vi' ), 'notice: nothing asked' );
hcwa_assert_same( '', hcwa_blocks_notice_lang( '', 'vi', 'vi' ), 'notice: same language as the page' );
hcwa_assert_same( '', hcwa_blocks_notice_lang( 'fr', '', 'vi' ), 'notice: unknown language' );

// Published translation (no notice needed when one exists).
hcwa_test_post( array( 'ID' => 40, 'post_type' => 'post' ) );
hcwa_test_post( array( 'ID' => 41, 'post_type' => 'post', 'post_status' => 'draft' ) );
hcwa_assert_same( 0, hcwa_blocks_published_translation( 40, 'en' ), 'translation: none' );
$GLOBALS['hcwa_test']['translations'][40] = array( 'vi' => 40, 'en' => 41 );
hcwa_assert_same( 0, hcwa_blocks_published_translation( 40, 'en' ), 'translation: draft does not count' );
$GLOBALS['hcwa_test']['posts'][41]->post_status = 'publish';
hcwa_assert_same( 41, hcwa_blocks_published_translation( 40, 'en' ), 'translation: published' );
hcwa_assert_same( 0, hcwa_blocks_published_translation( 40, 'vi' ), 'translation: the post itself is not a translation' );
hcwa_assert_same( 0, hcwa_blocks_published_translation( 0, 'en' ), 'translation: no post' );
unset( $GLOBALS['hcwa_test']['translations'] );

// One notice per request.
hcwa_blocks_notice_claim( true );
hcwa_assert_true( hcwa_blocks_notice_claim(), 'notice claim: the first one prints' );
hcwa_assert_false( hcwa_blocks_notice_claim(), 'notice claim: a second one does not' );
hcwa_assert_false( hcwa_blocks_notice_claim( true ), 'notice claim: reset' );
hcwa_assert_true( hcwa_blocks_notice_claim(), 'notice claim: prints again after a reset' );
hcwa_blocks_notice_claim( true );

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
$hcwa_json = json_decode( (string) hcwa_blocks_script_translations( null, false, 'hcwa-lit-header-editor-script', 'hcwa' ), true );
hcwa_assert_same( 'Đầu trang có ánh nắng', $hcwa_json['locale_data']['hcwa']['Lit header'][0] ?? null, 'Vietnamese editor label served' );
hcwa_assert_same( 'hcwa', $hcwa_json['locale_data']['hcwa']['']['domain'] ?? null, 'Jed header' );
