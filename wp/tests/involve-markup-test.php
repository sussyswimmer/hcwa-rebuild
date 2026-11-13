<?php
/**
 * Get Involved (M7 one-object ways, ported in M8): hcwa/involve-head and hcwa/ways rendered from
 * the generated page patterns must print the prototype's own header and ways section, byte for
 * byte, in Vietnamese and English (golden.json "involve", cut by the export from the prototype's
 * rendered page). Then the gates the prototype does not have: production drops draft words (and
 * falls back to the page title for the H1 and to the way's name for a button), unpublished pages
 * lose their links and buttons, each way prints once, Ủng hộ is the only sun button, the see-ways
 * link needs the ways on the page, and the window is decorative and missing without theme art.
 *
 * @package hcwa-core
 */

$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';
require_once $hcwa_theme_dir . '/inc/assets.php';
require_once $hcwa_theme_dir . '/inc/art.php';
hcwa_theme_register_art();

$hcwa_gen_dir = $hcwa_theme_dir . '/assets/generated';
$hcwa_art_of  = static fn( $p ) => new HCWA_Theme_Art( '/', $hcwa_gen_dir );
// The stub apply_filters() runs these two hooks for this file only (hcwa_test_reset() clears it).
$GLOBALS['hcwa_test']['run_filters'] = array( 'hcwa_art_provider', 'hcwa_lang' );
$GLOBALS['hcwa_test']['hooks']['hcwa_art_provider'] = array( array( $hcwa_art_of, 10, 1 ) );
$GLOBALS['hcwa_test']['hooks']['hcwa_lang']         = array( array( static fn( $l ) => $GLOBALS['hcwa_lang_now'] ?? $l, 10, 1 ) );
hcwa_art( true );

// The prototype prints root-relative links and escapes ' as &#39;; WordPress prints the site URL and &#039;.
$hcwa_norm = static fn( string $html ): string => str_replace( array( 'https://hcwa.test/', '&#039;' ), array( '/', '&#39;' ), $html );

// The pattern's blocks, by name: [attrs] of hcwa/involve-head, hcwa/ways, and the body.
$hcwa_pattern = static function ( string $lang ) use ( $hcwa_theme_dir ): array {
	$src  = (string) file_get_contents( "$hcwa_theme_dir/patterns/page-get-involved-$lang.php" );
	$body = substr( $src, (int) strpos( $src, '?>' ) + 2 );
	$out  = array( 'body' => $body );
	$walk = static function ( array $blocks ) use ( &$walk, &$out ): void {
		foreach ( $blocks as $b ) {
			if ( null !== $b['blockName'] ) {
				$out[ $b['blockName'] ] = $b['attrs'];
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $body ) );
	return $out;
};

$hcwa_page_post = static function ( string $lang, string $body ) {
	return hcwa_test_post(
		array(
			'ID'           => 'vi' === $lang ? 8801 : 8802,
			'post_type'    => 'page',
			'post_name'    => 'vi' === $lang ? 'chung-tay' : 'get-involved',
			'post_title'   => 'vi' === $lang ? 'Chung tay' : 'Get Involved',
			'post_content' => $body,
		)
	);
};


// ---------- Golden: the prototype's own markup ----------
$hcwa_golden = hcwa_test_golden( 'involve' );
if ( null === $hcwa_golden ) {
	hcwa_test_skip( 'Get Involved golden cases: golden.json has no "involve" section (npm --prefix prototype run export:wp)' );
} else {
	hcwa_assert_same( array( 'vi', 'en' ), array_column( $hcwa_golden, 'lang' ), 'golden: one case per language' );
	foreach ( $hcwa_golden as $hcwa_case ) {
		$hcwa_lang                 = $hcwa_case['lang'];
		$GLOBALS['hcwa_lang_now']  = $hcwa_lang;
		$GLOBALS['hcwa_test']['env'] = 'staging';
		$hcwa_p                    = $hcwa_pattern( $hcwa_lang );
		hcwa_assert_true( isset( $hcwa_p['hcwa/involve-head'], $hcwa_p['hcwa/ways'] ), "$hcwa_lang: the pattern holds hcwa/involve-head and hcwa/ways" );
		hcwa_assert_false( isset( $hcwa_p['hcwa/louvers'] ) || isset( $hcwa_p['hcwa/lit-header'] ), "$hcwa_lang: no louver slats and no lit header left on Get Involved" );
		$hcwa_post = $hcwa_page_post( $hcwa_lang, $hcwa_p['body'] );
		$hcwa_head = $hcwa_norm( hcwa_render_involve_head( (array) ( $hcwa_p['hcwa/involve-head'] ?? array() ), $hcwa_post ) );
		$hcwa_ways = $hcwa_norm( hcwa_render_ways( (array) ( $hcwa_p['hcwa/ways'] ?? array() ) ) );
		hcwa_assert_same( "\n" . $hcwa_case['head'], $hcwa_head, "$hcwa_lang: hcwa/involve-head prints the prototype's header (window, breadcrumb, H1, intro, see-ways link)" );
		hcwa_assert_same( "\n  " . $hcwa_case['ways'], $hcwa_ways, "$hcwa_lang: hcwa/ways prints the prototype's ways section" );
		// Through render.php, as WordPress runs it.
		$hcwa_render = static function ( string $name, array $attributes ) use ( $hcwa_post ): string {
			$file    = dirname( __DIR__ ) . '/plugins/hcwa-core/blocks/' . $name . '/render.php';
			$content = '';
			$block   = null;
			$GLOBALS['post'] = $hcwa_post;
			ob_start();
			require $file;
			return (string) ob_get_clean();
		};
		hcwa_assert_same( $hcwa_ways, $hcwa_norm( $hcwa_render( 'ways', (array) $hcwa_p['hcwa/ways'] ) ), "$hcwa_lang: ways/render.php prints the same" );
		hcwa_assert_contains( '<h1 class="gi-head__title"', $hcwa_norm( $hcwa_render( 'involve-head', (array) $hcwa_p['hcwa/involve-head'] ) ), "$hcwa_lang: involve-head/render.php prints the header" );
	}
	$GLOBALS['hcwa_lang_now'] = 'vi';
}

// ---------- The drawing ----------
$hcwa_window = hcwa_art()->drawing( 'involve-window' );
hcwa_assert_true( str_starts_with( $hcwa_window, '<div class="gi-stage" aria-hidden="true">' ), 'window: decorative, hidden from assistive tech' );
hcwa_assert_false( (bool) preg_match( '/<(img|image|text|picture)\b|https?:/i', $hcwa_window ), 'window: drawn inline, no photo, no lettering, no request' );
hcwa_assert_same( '', hcwa_art()->drawing( '../stills' ), 'window: only plain drawing names are read' );
hcwa_assert_same( '', hcwa_art()->drawing( 'not-drawn-yet' ), 'window: a missing drawing prints nothing' );
hcwa_assert_same( '', ( new HCWA_Art_None() )->drawing( 'involve-window' ), 'window: none without theme art' );

// ---------- Gates ----------
$hcwa_p    = $hcwa_pattern( 'vi' );
$hcwa_head = (array) $hcwa_p['hcwa/involve-head'];
$hcwa_ways = (array) $hcwa_p['hcwa/ways'];
$hcwa_post = $hcwa_page_post( 'vi', $hcwa_p['body'] );

$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_html = hcwa_render_ways( $hcwa_ways );
hcwa_assert_same( 4, substr_count( $hcwa_html, '<li class="way ' ), 'ways: four ways' );
foreach ( array( 'donate', 'volunteer', 'partner', 'in-kind' ) as $hcwa_id ) {
	hcwa_assert_same( 1, substr_count( $hcwa_html, 'id="way-' . $hcwa_id . '"' ), "ways: $hcwa_id once" );
}
hcwa_assert_same( 1, substr_count( $hcwa_html, 'btn--primary' ), 'ways: one sun button' );
hcwa_assert_contains( '<a class="btn btn--primary way__btn" href="https://hcwa.test/chung-tay/ung-ho/">', $hcwa_html, 'ways: Ủng hộ is the sun button' );
hcwa_assert_same( 4, substr_count( $hcwa_html, 'class="way__act"' ), 'ways: one button per way' );
hcwa_assert_false( str_contains( $hcwa_html, '[DRAFT' ), 'ways: no draft marker printed' );
hcwa_assert_false( str_contains( $hcwa_html, 'louver' ), 'ways: no louver slats' );

// A way stored twice prints once.
$hcwa_twice         = $hcwa_ways;
$hcwa_twice['ways'] = array_merge( $hcwa_ways['ways'], array( $hcwa_ways['ways'][1] ) );
hcwa_assert_same( 1, substr_count( hcwa_render_ways( $hcwa_twice ), 'id="way-volunteer"' ), 'ways: a way stored twice prints once' );
hcwa_assert_same( '', hcwa_render_ways( array( 'ways' => array( array( 'id' => 'stranger', 'title' => 'x' ) ) ) ), 'ways: unknown ids print nothing' );
// Draft words in a list item or link stay outlined on staging.
$hcwa_draft                         = $hcwa_ways;
$hcwa_draft['ways'][1]['items'][0]  = '[DRAFT: Dạy kèm]';
$hcwa_draft['ways'][1]['links'][0]  = '[DRAFT: Bạn sẽ làm gì]';
$hcwa_html                          = hcwa_render_ways( $hcwa_draft );
hcwa_assert_contains( '<li data-draft>Dạy kèm</li>', $hcwa_html, 'ways: a draft list item is outlined' );
hcwa_assert_contains( '#expect-title" data-draft><span>Bạn sẽ làm gì</span>', $hcwa_html, 'ways: a draft link is outlined' );

// Production: the four pages are published (the stub's get_page_by_path() finds them).
foreach ( array( 'chung-tay/ung-ho' => 8811, 'chung-tay/tinh-nguyen' => 8812, 'chung-tay/doi-tac-doanh-nghiep' => 8813, 'chung-tay/hien-vat' => 8814 ) as $hcwa_path => $hcwa_id ) {
	hcwa_test_post( array( 'ID' => $hcwa_id, 'post_type' => 'page', 'post_name' => basename( $hcwa_path ), 'post_status' => 'publish' ) );
	$GLOBALS['hcwa_test']['page_paths'][ $hcwa_path ] = $hcwa_id;
}
$GLOBALS['hcwa_test']['env'] = 'production';
$hcwa_html                   = hcwa_render_ways( $hcwa_draft );
hcwa_assert_false( str_contains( $hcwa_html, 'data-draft' ), 'production: no draft outline' );
hcwa_assert_false( str_contains( $hcwa_html, 'Dạy kèm' ) || str_contains( $hcwa_html, 'Bạn sẽ làm gì' ) || str_contains( $hcwa_html, 'way__text' ), 'production: draft words are left out' );
hcwa_assert_contains( '<a class="btn btn--secondary way__btn" href="https://hcwa.test/chung-tay/tinh-nguyen/">Tình nguyện</a>', $hcwa_html, 'production: a draft button label falls back to the way\'s name' );
hcwa_assert_contains( '<a class="btn btn--primary way__btn" href="https://hcwa.test/chung-tay/ung-ho/">Cách ủng hộ</a>', $hcwa_html, 'production: Ủng hộ keeps its verified label' );
hcwa_assert_contains( 'href="https://hcwa.test/chung-tay/hien-vat/#tiem-banh"', $hcwa_html, 'production: the bakery link while In-kind is published' );

$hcwa_html = hcwa_render_involve_head( $hcwa_head, $hcwa_post );
hcwa_assert_contains( '<h1 class="gi-head__title">' . hcwa_vi_keep( 'Chung tay' ) . '</h1>', $hcwa_html, 'production: a draft welcome heading falls back to the page title' );
hcwa_assert_false( str_contains( $hcwa_html, 'gi-head__intro' ) || str_contains( $hcwa_html, 'gi-head__go' ), 'production: draft intro and link are left out' );
hcwa_assert_same( 1, substr_count( $hcwa_html, '<h1' ), 'production: one H1' );

// Production with the sub-pages unpublished: no link or button into them, Donate kept.
foreach ( array( 'chung-tay/tinh-nguyen', 'chung-tay/doi-tac-doanh-nghiep', 'chung-tay/hien-vat' ) as $hcwa_path ) {
	unset( $GLOBALS['hcwa_test']['page_paths'][ $hcwa_path ] );
}
$hcwa_html = hcwa_render_ways( $hcwa_ways );
hcwa_assert_false( (bool) preg_match( '#tinh-nguyen|doi-tac-doanh-nghiep|hien-vat#', $hcwa_html ), 'production: no link or button into an unpublished page' );
hcwa_assert_contains( 'way__btn" href="https://hcwa.test/chung-tay/ung-ho/"', $hcwa_html, 'production: Donate keeps its button' );
hcwa_assert_same( 1, substr_count( $hcwa_html, 'class="way__act"' ), 'production: only Donate has a button' );
hcwa_assert_same( 4, substr_count( $hcwa_html, '<li class="way ' ), 'production: the ways keep their words' );
unset( $GLOBALS['hcwa_test']['page_paths'] );

// The see-ways link needs the ways on the page (rule 11).
$GLOBALS['hcwa_test']['env'] = 'staging';
$hcwa_bare                   = $hcwa_page_post( 'vi', '<!-- wp:hcwa/sections --><!-- wp:hcwa/involve-head /--><!-- /wp:hcwa/sections -->' );
hcwa_assert_false( str_contains( hcwa_render_involve_head( $hcwa_head, $hcwa_bare ), 'href="#ways"' ), 'head: no jump to #ways on a page without hcwa/ways' );
hcwa_assert_contains( 'href="#ways"', hcwa_render_involve_head( $hcwa_head, $hcwa_post ), 'head: the jump when the ways are there' );

// Without theme art the header still prints its words.
$GLOBALS['hcwa_test']['hooks']['hcwa_art_provider'] = array();
hcwa_art( true );
$hcwa_html = hcwa_render_involve_head( $hcwa_head, $hcwa_post );
hcwa_assert_false( str_contains( $hcwa_html, 'gi-stage' ), 'no theme art: no window' );
hcwa_assert_contains( hcwa_vi_keep( 'Mời bạn cùng chung tay' ), $hcwa_html, 'no theme art: the words stay' );
hcwa_assert_same( '', hcwa_art()->block_icon( 'hoaThi', 'way__block' ), 'no theme art: no tile' );
hcwa_assert_contains( '<span class="way__tile" aria-hidden="true"></span>', hcwa_render_ways( $hcwa_ways ), 'no theme art: the ways still print' );

// The page context: an involve-head page is not lit and takes the involve day's class (prototype bodyClass).
hcwa_assert_same(
	array(
		'lit'        => false,
		'body_class' => 'page-involve',
	),
	hcwa_theme_sections_context( $hcwa_p['body'] ),
	'context: not lit, page-involve'
);
// Its intro is the page description.
hcwa_assert_same( 'Bạn có thể ủng hộ, tình nguyện, hợp tác hoặc tặng hiện vật.', hcwa_page_description( $hcwa_post )['text'], 'description: the header intro (staging)' );
unset( $GLOBALS['hcwa_lang_now'], $GLOBALS['hcwa_test']['hooks']['hcwa_art_provider'], $GLOBALS['hcwa_test']['hooks']['hcwa_lang'] );
