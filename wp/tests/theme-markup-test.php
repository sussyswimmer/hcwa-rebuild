<?php
/**
 * The prototype's markup rules ported to PHP (docs/plan/11-WP-PORT.md 2.1 to 2.8, 3.3 to 3.5):
 * the text helpers against the prototype's own output (golden.json), hcwa_attr_text(), the
 * draft and bracket rules over every bracket string in content/draft/*.json, center bodies,
 * door chips and the Little Rose rules, contact lines, hcwa_render_sections() cleaning and
 * gating, and the attachment-redirect fix.
 *
 * @package hcwa-core
 */

// ---------- Text helpers against golden.json ----------
$hcwa_golden = hcwa_test_golden();
if ( null === $hcwa_golden ) {
	hcwa_test_skip( 'text golden cases: wp/tests/fixtures/golden.json is missing (npm --prefix prototype run export:wp)' );
} else {
	foreach ( $hcwa_golden['split_words'] as $hcwa_case ) {
		hcwa_assert_same( array( 'count' => $hcwa_case['count'], 'html' => $hcwa_case['html'] ), hcwa_split_words( $hcwa_case['in'] ), 'splitWords ' . wp_json_encode( $hcwa_case['in'] ) );
	}
	foreach ( $hcwa_golden['vi_keep'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['out'], hcwa_vi_keep( $hcwa_case['in'] ), 'viKeep ' . wp_json_encode( $hcwa_case['in'] ) );
	}
	foreach ( $hcwa_golden['keep_hyphens'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['out'], hcwa_keep_hyphens( $hcwa_case['in'] ), 'keepHyphens ' . wp_json_encode( $hcwa_case['in'] ) );
	}
	foreach ( $hcwa_golden['dates'] as $hcwa_case ) {
		hcwa_assert_same( array( $hcwa_case['dm'], $hcwa_case['sep'], $hcwa_case['year'] ), hcwa_date_parts( $hcwa_case['iso'], $hcwa_case['lang'] ), 'date parts ' . $hcwa_case['iso'] . ' ' . $hcwa_case['lang'] );
	}
	foreach ( $hcwa_golden['icons'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], hcwa_icon( $hcwa_case['name'], $hcwa_case['cls'] ), 'icon ' . $hcwa_case['name'] . ' ' . $hcwa_case['cls'] );
	}
	foreach ( $hcwa_golden['quatrefoil'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], hcwa_quatrefoil( $hcwa_case['cls'] ), 'quatrefoil ' . $hcwa_case['cls'] );
	}
}
hcwa_assert_same( '', hcwa_icon( 'no-such-icon' ), 'an unknown icon prints nothing' );

// ---------- hcwa_attr_text (4.1 attribute format) ----------
hcwa_assert_same( 'Trẻ em & gia đình', hcwa_attr_text( 'Trẻ em &amp; gia đình' ), 'attr text: &amp; decodes once' );
hcwa_assert_same( 'Trẻ em & gia đình', hcwa_attr_text( 'Trẻ em & gia đình' ), 'attr text: a bare & stays' );
hcwa_assert_same( 'a < b', hcwa_attr_text( 'a &lt; b' ), 'attr text: &lt; decodes to the character' );
hcwa_assert_same( 'bold text', hcwa_attr_text( '<strong>bold</strong> text' ), 'attr text: tags are dropped' );
hcwa_assert_same( "TP.\u{00A0}HCM", hcwa_attr_text( 'TP.&nbsp;HCM' ), 'attr text: a no-break space is kept as the character' );
hcwa_assert_same( 'dòng một dòng hai', hcwa_attr_text( "dòng một<br>dòng hai" ), 'attr text: a soft break becomes a space' );
hcwa_assert_same( 'dòng một dòng hai', hcwa_attr_text( "  dòng một<br />\n dòng hai  " ), 'attr text: whitespace collapses' );
hcwa_assert_same( hcwa_nfc( 'Hội Bảo trợ' ), hcwa_attr_text( "Ho\u{0302}\u{0323}i Bảo trợ" ), 'attr text: NFC' );
hcwa_assert_same( 'A &amp; B &lt;b&gt;', esc_html( hcwa_attr_text( 'A &amp; B &lt;b&gt;' ) ), 'attr text then esc_html: one &amp;, no double-escaped entity, no literal tag' );

// ---------- The draft rule (2.1 rule 7, 3.3) ----------
hcwa_assert_true( hcwa_text_has_draft( '[DRAFT: Lời chào]' ), 'draft: [DRAFT: …]' );
hcwa_assert_true( hcwa_text_has_draft( 'Câu [draft text] ở giữa' ), 'draft: a bracket placeholder inside text' );
hcwa_assert_true( hcwa_text_has_draft( 'Từ năm [năm]' ), 'draft: [năm]' );
hcwa_assert_true( hcwa_text_has_draft( '[N] trẻ em' ), 'draft: [N]' );
hcwa_assert_false( hcwa_text_has_draft( 'Xem chú thích [1]' ), 'draft: a digit-only footnote passes' );
hcwa_assert_true( hcwa_text_has_draft( 'Chú thích [1] và [N]' ), 'draft: a footnote does not hide a placeholder' );
hcwa_assert_false( hcwa_text_has_draft( 'Không có ngoặc' ), 'draft: plain text' );
hcwa_assert_same( 'Lời chào', hcwa_draft_unwrap( '[DRAFT: Lời chào]' ), 'unwrap: whole wrapper' );
hcwa_assert_same( '[Second need]', hcwa_draft_unwrap( '[DRAFT: [Second need]]' ), 'unwrap: a nested placeholder stays as the prototype prints it' );
hcwa_assert_same( '[DRAFT: a] b [c]', hcwa_draft_unwrap( '[DRAFT: a] b [c]' ), 'unwrap: not a whole wrapper' );
hcwa_assert_same( array( 'text' => 'Lời chào', 'draft' => true ), hcwa_draft_text( '[DRAFT: Lời chào]', false ), 'draft text: staging prints the inner text' );
hcwa_assert_same( array( 'text' => '', 'draft' => true ), hcwa_draft_text( '[DRAFT: Lời chào]', true ), 'draft text: production prints nothing' );
hcwa_assert_same( array( 'text' => 'Từ năm [năm]', 'draft' => true ), hcwa_draft_text( 'Từ năm [năm]', false ), 'draft text: a placeholder prints as stored on staging' );
hcwa_assert_same( array( 'text' => 'Đã xác nhận', 'draft' => false ), hcwa_draft_text( 'Đã xác nhận', true ), 'draft text: plain text on production' );

// The bracket rule over every bracket string and every draft field in the prototype's content.
$hcwa_tokens = 0;
$hcwa_fields = 0;
$hcwa_walk   = static function ( $node ) use ( &$hcwa_walk, &$hcwa_tokens, &$hcwa_fields ): void {
	if ( is_array( $node ) ) {
		if ( true === ( $node['draft'] ?? null ) ) {
			foreach ( array( 'vi', 'en' ) as $lang ) {
				$value = trim( (string) ( $node[ $lang ] ?? '' ) );
				if ( '' === $value ) {
					continue;
				}
				++$hcwa_fields;
				$stored = '[DRAFT: ' . $value . ']';
				hcwa_assert_true( hcwa_text_has_draft( $stored ), "draft field is draft: $stored" );
				hcwa_assert_same( array( 'text' => $value, 'draft' => true ), hcwa_draft_text( $stored, false ), "draft field prints the prototype's text: $value" );
				hcwa_assert_same( '', hcwa_draft_text( $stored, true )['text'], "draft field prints nothing on production: $value" );
			}
		}
		foreach ( $node as $child ) {
			$hcwa_walk( $child );
		}
		return;
	}
	if ( is_string( $node ) && preg_match_all( '/\[[^\]<]{1,80}\]/u', $node, $m ) ) {
		foreach ( $m[0] as $token ) {
			if ( preg_match( '/^\[\d+\]$/', $token ) ) {
				continue;
			}
			++$hcwa_tokens;
			hcwa_assert_true( hcwa_text_has_draft( $node ), "bracket string is draft: $node" );
		}
	}
};
foreach ( glob( dirname( __DIR__, 2 ) . '/content/draft/*.json' ) as $hcwa_file ) {
	$hcwa_walk( json_decode( (string) file_get_contents( $hcwa_file ), true ) );
}
hcwa_assert_true( $hcwa_tokens >= 50, "found the bracket placeholders in content/draft ($hcwa_tokens)" );
hcwa_assert_true( $hcwa_fields >= 100, "found the draft fields in content/draft ($hcwa_fields)" );

// ---------- Center body (2.5): story before <!--more-->, history after ----------
$hcwa_blocks_body = "<!-- wp:paragraph -->\n<p>Câu chuyện &amp; một</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:image {\"id\":77} -->\n<figure><img class=\"wp-image-77\"></figure>\n<!-- /wp:image -->\n\n<!-- wp:paragraph -->\n<p>[DRAFT: Câu chuyện hai]</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->\n\n<!-- wp:paragraph -->\n<p>Lịch sử<br>một</p>\n<!-- /wp:paragraph -->";
$hcwa_center      = hcwa_test_post( array( 'ID' => 300, 'post_type' => 'hcwa_center', 'post_content' => $hcwa_blocks_body ) );
hcwa_assert_same(
	array( 'story' => array( 'Câu chuyện & một', 'Câu chuyện hai' ), 'history' => array( 'Lịch sử một' ), 'draft' => array( false, true, false ) ),
	hcwa_center_body( $hcwa_center, false ),
	'center body (blocks): paragraphs only, draft unwrapped on staging, story then history'
);
hcwa_assert_same(
	array( 'story' => array( 'Câu chuyện & một' ), 'history' => array( 'Lịch sử một' ), 'draft' => array( false, false ) ),
	hcwa_center_body( $hcwa_center, true ),
	'center body (blocks): a draft paragraph is dropped on production'
);
$hcwa_center->post_content = "Đoạn một\n\nĐoạn hai\n<!--more-->\nLịch sử";
hcwa_assert_same(
	array( 'story' => array( 'Đoạn một', 'Đoạn hai' ), 'history' => array( 'Lịch sử' ), 'draft' => array( false, false, false ) ),
	hcwa_center_body( $hcwa_center, false ),
	'center body (classic): blank-line paragraphs around <!--more-->'
);
$hcwa_center->post_content = '';
hcwa_assert_same( array( 'story' => array(), 'history' => array(), 'draft' => array() ), hcwa_center_body( $hcwa_center, false ), 'center body: empty' );

// ---------- Little Rose rules (3.4) ----------
hcwa_test_post( array( 'ID' => 310, 'post_type' => 'hcwa_center' ), array( 'hcwa_accent' => 'little-rose' ) );
hcwa_test_post( array( 'ID' => 311, 'post_type' => 'hcwa_center' ), array( 'hcwa_accent' => 'green-bamboo', 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 312, 'post_type' => 'hcwa_center' ), array( 'hcwa_accent' => 'green-bamboo' ) );
hcwa_assert_true( hcwa_center_is_protected( 310 ), 'protected: the Little Rose unit' );
hcwa_assert_true( hcwa_center_is_protected( 311 ), 'protected: the no-identifiable-children flag' );
hcwa_assert_false( hcwa_center_is_protected( 312 ), 'not protected: an ordinary center' );
hcwa_assert_false( hcwa_center_is_protected( 0 ), 'not protected: no center' );
hcwa_assert_same( 'little-rose', hcwa_center_unit( 310 ), 'unit key from the accent' );
hcwa_assert_same( '', hcwa_center_unit( 999 ), 'no unit for an unknown center' );

$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array();
hcwa_assert_true( hcwa_little_rose_door_allowed( false ), 'door: always allowed off production' );
hcwa_assert_false( hcwa_little_rose_door_allowed( true ), 'door: refused on production before approval' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array( 'world_lr_door_approved_by' => 'Safeguarding lead', 'world_lr_door_approved_date' => 'soon' );
hcwa_assert_false( hcwa_little_rose_door_allowed( true ), 'door: refused with a date that is not a date' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array( 'world_lr_door_approved_date' => '2026-11-01' );
hcwa_assert_false( hcwa_little_rose_door_allowed( true ), 'door: refused without the approver' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array( 'world_lr_door_approved_by' => 'Safeguarding lead', 'world_lr_door_approved_date' => '2026-11-01' );
hcwa_assert_true( hcwa_little_rose_door_allowed( true ), 'door: allowed on production with both approval fields' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array( 'world_traces_enabled' => '1' );
hcwa_assert_true( hcwa_traces_on( false ), 'traces: on in staging when switched on' );
hcwa_assert_false( hcwa_traces_on( true ), 'traces: never on production' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array();
hcwa_assert_false( hcwa_traces_on( false ), 'traces: off by default' );
hcwa_assert_false( hcwa_forms_enabled(), 'forms: off on production until they have a handler' );

// Door chips (2.6, 2.8): every chip carries data-unit; a protected chip before approval has no door and no --unit.
$hcwa_chip = static fn( string $key, bool $protected ): array => array(
	'key'       => $key,
	'protected' => $protected,
	'color'     => "var(--color-unit-$key)",
	'short'     => array(
		'text'  => 'Tên & ngắn',
		'draft' => false,
	),
);
hcwa_assert_same(
	'<li class="door-chip" data-unit="green-bamboo" style="--unit:var(--color-unit-green-bamboo)"><span class="door-chip__door" aria-hidden="true"></span>Tên &amp; ngắn</li>',
	hcwa_door_chip_html( $hcwa_chip( 'green-bamboo', false ) ),
	'door chip: data-unit, the unit colour and the door'
);
$GLOBALS['hcwa_test']['env'] = 'production';
hcwa_assert_same( '<li class="door-chip" data-unit="little-rose">Tên &amp; ngắn</li>', hcwa_door_chip_html( $hcwa_chip( 'little-rose', true ) ), 'door chip: protected on production before approval, the name only' );
$hcwa_lr_chip = hcwa_door_chip_html( $hcwa_chip( 'little-rose', true ) );
hcwa_assert_false( str_contains( $hcwa_lr_chip, 'door-chip__door' ) || str_contains( $hcwa_lr_chip, '--unit' ), 'door chip: no door span and no --unit for Little Rose on production' );
hcwa_assert_contains( 'door-chip__door', hcwa_door_chip_html( $hcwa_chip( 'green-bamboo', false ) ), 'door chip: an ordinary center keeps its door on production' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array( 'world_lr_door_approved_by' => 'Safeguarding lead', 'world_lr_door_approved_date' => '2026-11-01' );
hcwa_assert_contains( 'door-chip__door', hcwa_door_chip_html( $hcwa_chip( 'little-rose', true ) ), 'door chip: Little Rose door once approved' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array();
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_contains( 'door-chip__door', hcwa_door_chip_html( $hcwa_chip( 'little-rose', true ) ), 'door chip: Little Rose door on staging' );

// The art guard refuses every still that shows the door, on production, before approval.
$hcwa_inner = new class() extends HCWA_Art_None {
	public function has_still( string $name ): bool {
		return true;
	}
	public function shows_little_rose_door( string $name ): bool {
		return 'door-little-rose' === $name;
	}
	public function still( string $name, array $opts = array() ): string {
		return "<picture data-still=\"$name\"></picture>";
	}
};
$hcwa_refusing = new HCWA_Art_Guard( $hcwa_inner, false );
hcwa_assert_false( $hcwa_refusing->has_still( 'door-little-rose' ), 'guard: the door still is missing before approval' );
hcwa_assert_same( '', $hcwa_refusing->still( 'door-little-rose' ), 'guard: the door still prints nothing before approval' );
hcwa_assert_true( $hcwa_refusing->has_still( 'door-green-bamboo' ), 'guard: other stills stay' );
hcwa_assert_true( ( new HCWA_Art_Guard( $hcwa_inner, true ) )->has_still( 'door-little-rose' ), 'guard: the door still once approved' );

// ---------- Contact lines (2.1 rule 12) ----------
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array(
	'phone'  => '(028) 3930 0000',
	'emails' => "info@hcwa.test\nsponsor@hcwa.test",
);
hcwa_assert_same( array( 'text' => '(028) 3930 0000', 'href' => 'tel:02839300000', 'draft' => true ), hcwa_contact_line( 'phone', false ), 'contact: phone is draft until HCWA confirms it' );
hcwa_assert_same( array( 'text' => 'sponsor@hcwa.test', 'href' => 'mailto:sponsor@hcwa.test', 'draft' => true ), hcwa_contact_line( 'sponsorship', false ), 'contact: the second email is the sponsorship line' );
hcwa_assert_same( array( 'text' => '', 'href' => '', 'draft' => true ), hcwa_contact_line( 'email', true ), 'contact: an unconfirmed line prints nothing on production' );
$GLOBALS['hcwa_test']['options']['hcwa_settings']['contact_confirmed'] = '1';
$GLOBALS['hcwa_test']['options']['hcwa_settings']['phone_tel']         = '+842839300000';
hcwa_assert_same( array( 'text' => '(028) 3930 0000', 'href' => 'tel:+842839300000', 'draft' => false ), hcwa_contact_line( 'phone', true ), 'contact: confirmed phone on production, tel from its own field' );
hcwa_assert_same( array( 'text' => 'info@hcwa.test', 'href' => 'mailto:info@hcwa.test', 'draft' => false ), hcwa_contact_line( 'email', true ), 'contact: confirmed email on production' );
$GLOBALS['hcwa_test']['options']['hcwa_settings']['phone'] = '[DRAFT: (028) 3930 0000]';
hcwa_assert_same( '', hcwa_contact_line( 'phone', true )['text'], 'contact: a draft phone prints nothing on production even when confirmed' );
$GLOBALS['hcwa_test']['options']['hcwa_settings'] = array();
hcwa_assert_same( array( 'text' => '', 'href' => '', 'draft' => true ), hcwa_contact_line( 'phone', false ), 'contact: nothing set, nothing printed' );

// ---------- hcwa_render_sections (2.1 rule 4) ----------
$hcwa_page_body = "<!-- wp:paragraph -->\n<p>Before the sections</p>\n<!-- /wp:paragraph -->\n\n"
	. "<!-- wp:hcwa/sections {\"templateLock\":\"contentOnly\"} -->\n<div>literal between</div>\n"
	. "<!-- wp:hcwa/hero {\"title\":\"Xin chào\"} /-->\n\n"
	. "<!-- wp:html -->\n<img src=\"https://hcwa.test/wp-content/uploads/kid.jpg\">\n<!-- /wp:html -->\n\n"
	. "<!-- wp:hcwa/day {\"variant\":\"involve\"} -->\nloose text\n"
	. "<!-- wp:paragraph -->\n<p>Kept <img class=\"wp-image-401\" src=\"x.jpg\"> and <img class=\"wp-image-402\" src=\"y.jpg\"></p>\n<!-- /wp:paragraph -->\n\n"
	. "<!-- wp:shortcode -->\n[gallery ids=\"401\"]\n<!-- /wp:shortcode -->\n"
	. "<!-- /wp:hcwa/day -->\n"
	. "<!-- /wp:hcwa/sections -->\n\n<!-- wp:paragraph -->\n<p>After the sections</p>\n<!-- /wp:paragraph -->";
$hcwa_page      = hcwa_test_post( array( 'ID' => 400, 'post_type' => 'page', 'post_content' => $hcwa_page_body ) );
hcwa_test_post( array( 'ID' => 401, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '' ) );
hcwa_test_post( array( 'ID' => 402, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '1' ) );
$GLOBALS['hcwa_test']['current_post'] = $hcwa_page;
hcwa_assert_true( hcwa_is_sections_page( $hcwa_page ), 'sections page: has an hcwa/sections block' );
hcwa_assert_false( hcwa_is_sections_page( hcwa_test_post( array( 'ID' => 403, 'post_type' => 'page', 'post_content' => '<p>About</p>' ) ) ), 'sections page: an ordinary page is not one' );
hcwa_assert_false( hcwa_is_sections_page( null ), 'sections page: no post' );
$hcwa_sections = hcwa_sections_find( parse_blocks( $hcwa_page_body ) );
hcwa_assert_same( 'hcwa/sections', $hcwa_sections['blockName'] ?? null, 'find: the sections block' );
$hcwa_clean = hcwa_sections_clean( $hcwa_sections );
hcwa_assert_same( array( 'hcwa/hero', 'hcwa/day' ), array_column( $hcwa_clean['innerBlocks'], 'blockName' ), 'clean: core/html is removed from the sections' );
hcwa_assert_same( array( null, null ), $hcwa_clean['innerContent'], 'clean: no literal HTML between sections' );
hcwa_assert_same( array( 'core/paragraph' ), array_column( $hcwa_clean['innerBlocks'][1]['innerBlocks'], 'blockName' ), 'clean: the shortcode block is removed inside a day' );
hcwa_assert_same( array( null ), $hcwa_clean['innerBlocks'][1]['innerContent'], 'clean: no loose text inside a day' );

$hcwa_saved_hooks = $GLOBALS['hcwa_test']['hooks']['render_block'] ?? null;
$hcwa_html        = hcwa_render_sections( $hcwa_page );
// bootstrap.php's remove_filter() is a no-op: drop the gate by hand, as WordPress would.
if ( null === $hcwa_saved_hooks ) {
	unset( $GLOBALS['hcwa_test']['hooks']['render_block'] );
} else {
	$GLOBALS['hcwa_test']['hooks']['render_block'] = $hcwa_saved_hooks;
}
hcwa_assert_same( 0, strpos( $hcwa_html, "<sections><hero></hero><day>\n<p>" ), 'render: only the sections, in order, with nothing between them' );
hcwa_assert_same( 1, preg_match( '#</day></sections>$#', $hcwa_html ), 'render: nothing after the sections' );
foreach ( array( 'Before the sections', 'After the sections', 'literal between', 'loose text', 'kid.jpg', 'gallery', 'wp-image-401' ) as $hcwa_gone ) {
	hcwa_assert_false( str_contains( $hcwa_html, $hcwa_gone ), "render: '$hcwa_gone' is not printed" );
}
hcwa_assert_contains( 'wp-image-402', $hcwa_html, 'render: an approved photo in a core block stays' );
hcwa_assert_contains( "remove_filter( 'render_block', 'hcwa_sections_gate_block', 10 );", (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/sections.php' ), 'render: the gate is removed after the page renders' );
hcwa_assert_same( '<hero>x</hero>', hcwa_sections_gate_block( '<hero>x</hero>', array( 'blockName' => 'hcwa/hero' ) ), 'gate: our own blocks pass untouched' );
hcwa_assert_same( '', hcwa_render_sections( hcwa_test_post( array( 'ID' => 404, 'post_type' => 'page', 'post_content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ) ) ), 'render: no sections block, nothing' );
$GLOBALS['hcwa_test']['password'][400] = true;
$hcwa_locked = hcwa_render_sections( $hcwa_page );
hcwa_assert_contains( 'post-password-form', $hcwa_locked, 'render: a password-protected page prints the form' );
hcwa_assert_false( str_contains( $hcwa_locked, '<hero>' ), 'render: and not its sections' );
unset( $GLOBALS['hcwa_test']['password'], $GLOBALS['hcwa_test']['current_post'] );

// ---------- The attachment redirect (6.3: ?attachment_id, ?p and ?page_id answer 404, no Location) ----------
hcwa_test_post( array( 'ID' => 40, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_parent' => 310 ), array( 'hcwa_approved_for_web' => '' ) );
hcwa_test_post( array( 'ID' => 41, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( 'hcwa_approved_for_web' => '1' ) );
$hcwa_target = 'https://hcwa.test/hoat-dong/co-so/hoa-hong-nho/hcwa-20261109-v1lzeuoz/';
foreach ( array( 'attachment_id', 'p', 'page_id' ) as $hcwa_var ) {
	$GLOBALS['hcwa_test']['query_vars'] = array( $hcwa_var => '40' );
	hcwa_assert_false( hcwa_refused_attachment_canonical( $hcwa_target, "https://hcwa.test/?$hcwa_var=40" ), "redirect: ?$hcwa_var= of a refused photo does not redirect" );
	$GLOBALS['hcwa_test']['query_vars'] = array();
	$_GET[ $hcwa_var ]                  = '40';
	hcwa_assert_false( hcwa_refused_attachment_canonical( $hcwa_target, "https://hcwa.test/?$hcwa_var=40" ), "redirect: ?$hcwa_var= in the request alone does not redirect either" );
	unset( $_GET[ $hcwa_var ] );
	$GLOBALS['hcwa_test']['query_vars'] = array( $hcwa_var => '41' );
	hcwa_assert_same( 'https://hcwa.test/approved/', hcwa_refused_attachment_canonical( 'https://hcwa.test/approved/', "https://hcwa.test/?$hcwa_var=41" ), "redirect: ?$hcwa_var= of an approved photo redirects as usual" );
}
$GLOBALS['hcwa_test']['query_vars'] = array( 'p' => '312' );
hcwa_assert_same( 'https://hcwa.test/center/', hcwa_refused_attachment_canonical( 'https://hcwa.test/center/', 'https://hcwa.test/?p=312' ), 'redirect: an ordinary post redirects as usual' );
hcwa_assert_false( hcwa_refused_attachment_canonical( false, '' ), 'redirect: false stays false' );
$GLOBALS['hcwa_test']['query_vars'] = array();
$hcwa_hooked = false;
foreach ( $GLOBALS['hcwa_test']['hooks']['redirect_canonical'] ?? array() as $hcwa_entry ) {
	$hcwa_hooked = $hcwa_hooked || 'hcwa_refused_attachment_canonical' === $hcwa_entry[0];
}
hcwa_assert_true( $hcwa_hooked, 'redirect: the filter is on redirect_canonical' );
