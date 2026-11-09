<?php
/**
 * hcwa-2026 theme assets: what the "assets" stream of docs/plan/11-WP-PORT.md builds. The
 * bundle enqueued from its manifest with no query string on any dist URL, the module in <head>,
 * the draft CSS first on staging only, the dequeues on prototype views, the head-inline and
 * pageReveal scripts, #i18n, the bar scripts, data-lit, theme.json and the editor styles, and
 * HCWA_Theme_Art: every picture and SVG byte-equal to the prototype's own (golden.json), the
 * Little Rose stills refused by hcwa-core's guard, and the theme loading without hcwa-core.
 *
 * Runs in a fresh clone: the generated files and golden.json are committed; the checks that
 * need the built bundle (assets/dist/) are skipped without it.
 *
 * @package hcwa-2026
 */

$hcwa_theme_dir = dirname( __DIR__ ) . '/themes/hcwa-2026';
$hcwa_repo      = dirname( __DIR__, 2 );
require_once $hcwa_theme_dir . '/inc/strings.php';
require_once $hcwa_theme_dir . '/inc/core-bridge.php';
require_once $hcwa_theme_dir . '/inc/template-tags.php';
require_once $hcwa_theme_dir . '/inc/assets.php';
require_once $hcwa_theme_dir . '/inc/setup.php';
require_once $hcwa_theme_dir . '/inc/head.php';
require_once $hcwa_theme_dir . '/inc/art.php';

$hcwa_read     = static fn( string $file ): string => (string) file_get_contents( $hcwa_theme_dir . '/' . $file );
$hcwa_gen_dir  = $hcwa_theme_dir . '/assets/generated';
$hcwa_has_node = 0 === (int) ( proc_close( proc_open( array( 'node', '--version' ), array( 1 => array( 'file', '/dev/null', 'w' ), 2 => array( 'file', '/dev/null', 'w' ) ), $hcwa_pipes ) ) );
$hcwa_hooked   = static function ( string $hook, string $callback ): ?int {
	foreach ( $GLOBALS['hcwa_test']['hooks'][ $hook ] ?? array() as $h ) {
		if ( $h[0] === $callback ) {
			return (int) $h[1];
		}
	}
	return null;
};

// A manifest as Vite writes it, with one static import, so the tests do not need assets/dist/.
$hcwa_manifest = array(
	'src/main.js'          => array(
		'file'           => 'assets/main-AbC123xy.js',
		'isEntry'        => true,
		'imports'        => array( '_common-Zz9.js' ),
		'dynamicImports' => array( 'src/motion/index.js' ),
		'css'            => array( 'assets/main-Cs5.css' ),
	),
	'_common-Zz9.js'       => array( 'file' => 'assets/common-Zz9.js' ),
	'src/styles/draft.css' => array(
		'file'    => 'assets/draft-Dr4.css',
		'isEntry' => true,
	),
	'src/motion/index.js'  => array(
		'file'    => 'assets/motion-Mo1.js',
		'imports' => array( 'src/main.js' ),
		'css'     => array( 'assets/motion-Mo2.css' ),
	),
);
$hcwa_dist = 'https://hcwa.test/wp-content/themes/hcwa-2026/assets/dist/';

// ---------- The theme loads with and without hcwa-core (1.4) ----------
$hcwa_iso = 'require ' . var_export( $hcwa_theme_dir . '/functions.php', true ) . ';
hcwa_theme_register_art();
echo class_exists( "HCWA_Theme_Art", false ) ? "art-declared" : "art-none";
ob_start(); hcwa_theme_print_head_first(); $h = ob_get_clean();
echo substr_count( $h, "<script>" ) === 2 ? " head-ok" : " head-missing";
echo " ok";';
[ $hcwa_exit, $hcwa_out ] = hcwa_test_isolated( $hcwa_iso, false );
hcwa_assert_same( 0, $hcwa_exit, 'the theme loads without hcwa-core' );
hcwa_assert_same( 'art-none head-ok ok', trim( $hcwa_out ), 'without hcwa-core: no art class, no error, the head scripts still print' );
[ $hcwa_exit, $hcwa_out ] = hcwa_test_isolated( $hcwa_iso, true );
hcwa_assert_same( 'art-declared head-ok ok', trim( $hcwa_out ), 'with hcwa-core: HCWA_Theme_Art is declared' );

hcwa_assert_same( 10, $hcwa_hooked( 'after_setup_theme', 'hcwa_theme_register_art' ), 'the art provider registers on after_setup_theme' );
hcwa_theme_register_art();
hcwa_assert_true( class_exists( 'HCWA_Theme_Art', false ) && in_array( 'HCWA_Art', class_implements( 'HCWA_Theme_Art' ), true ), 'HCWA_Theme_Art implements hcwa-core\'s HCWA_Art' );
hcwa_assert_same( 10, $hcwa_hooked( 'hcwa_art_provider', 'hcwa_theme_art_provider' ), 'the provider is added to hcwa_art_provider' );
hcwa_assert_true( hcwa_theme_art_provider( null ) instanceof HCWA_Theme_Art, 'the provider is the theme\'s art' );
hcwa_assert_true( hcwa_theme_art_provider( null ) === hcwa_theme_art_provider( null ), 'one provider per request (the sprite prints once)' );

// ---------- HCWA_Theme_Art against the prototype's own output (golden.json) ----------
$hcwa_golden = hcwa_test_golden();
if ( null === $hcwa_golden ) {
	hcwa_test_skip( 'art golden cases: wp/tests/fixtures/golden.json is missing (npm --prefix prototype run export:wp)' );
} else {
	$hcwa_art = new HCWA_Theme_Art( '/', $hcwa_gen_dir );
	foreach ( $hcwa_golden['still'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->still( $hcwa_case['name'], $hcwa_case['opts'] ), 'still ' . $hcwa_case['name'] . ' ' . wp_json_encode( $hcwa_case['opts'] ) );
	}
	foreach ( $hcwa_golden['lit_still'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->lit_still( $hcwa_case['name'] ), 'lit still ' . $hcwa_case['name'] );
	}
	foreach ( $hcwa_golden['poster'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->poster( $hcwa_case['shot'], $hcwa_case['layer'], $hcwa_case['opts'] ), 'poster ' . $hcwa_case['layer'] . ' ' . wp_json_encode( $hcwa_case['opts'] ) );
	}
	foreach ( $hcwa_golden['gach_art'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->gach_art( $hcwa_case['area'], $hcwa_case['variant'] ), 'gạch ' . $hcwa_case['variant'] . ' ' . $hcwa_case['area'] );
	}
	foreach ( $hcwa_golden['gach_strip'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->gach_strip( $hcwa_case['areas'], $hcwa_case['cls'] ), 'gạch strip [' . implode( ' ', $hcwa_case['areas'] ) . '] ' . $hcwa_case['cls'] );
	}
	foreach ( $hcwa_golden['screen_art'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->screen_art( $hcwa_case['emblem'], $hcwa_case['cls'] ), 'transom ' . $hcwa_case['emblem'] . ' ' . $hcwa_case['cls'] );
	}
	foreach ( $hcwa_golden['block_icon'] as $hcwa_case ) {
		hcwa_assert_same( $hcwa_case['html'], $hcwa_art->block_icon( $hcwa_case['emblem'], $hcwa_case['cls'] ), 'plate mark ' . $hcwa_case['emblem'] . ' ' . $hcwa_case['cls'] );
	}
	foreach ( $hcwa_golden['has_still'] as $hcwa_name => $hcwa_ready ) {
		hcwa_assert_same( $hcwa_ready, $hcwa_art->has_still( $hcwa_name ), "has_still $hcwa_name" );
		hcwa_assert_true( abs( (float) $hcwa_golden['still_aspect'][ $hcwa_name ] - $hcwa_art->still_aspect( $hcwa_name ) ) < 1e-9, "still_aspect $hcwa_name" );
		hcwa_assert_same( $hcwa_golden['shows_little_rose_door'][ $hcwa_name ], $hcwa_art->shows_little_rose_door( $hcwa_name ), "shows_little_rose_door $hcwa_name" );
	}

	// The sprite: once per request, then nothing.
	HCWA_Theme_Art::reset_sprite();
	hcwa_assert_same( $hcwa_golden['gach_sprite'], $hcwa_art->gach_sprite_once(), 'the gạch sprite, the first time' );
	hcwa_assert_same( '', $hcwa_art->gach_sprite_once(), 'the gạch sprite only once per request' );
	hcwa_assert_same( '', ( new HCWA_Theme_Art( '/', $hcwa_gen_dir ) )->gach_sprite_once(), 'once per request across instances too' );
	HCWA_Theme_Art::reset_sprite();

	// The head scripts are the prototype's own.
	$GLOBALS['hcwa_test']['env'] = 'staging';
	ob_start();
	hcwa_theme_print_head_first();
	$hcwa_head = (string) ob_get_clean();
	hcwa_assert_same( "<script>\n" . $hcwa_golden['head_inline']['staging'] . "\n</script>\n<script>\n" . $hcwa_golden['page_reveal'] . "\n</script>\n", $hcwa_head, 'staging: head-inline (staging variant) then pageReveal' );
	$GLOBALS['hcwa_test']['env'] = 'production';
	ob_start();
	hcwa_theme_print_head_first();
	$hcwa_head = (string) ob_get_clean();
	hcwa_assert_true( str_starts_with( $hcwa_head, "<script>\n" . $hcwa_golden['head_inline']['production'] . "\n</script>" ), 'production: the production variant (no ?tier, ?motion or ?drafts flags)' );
	hcwa_assert_contains( 'var proto = false;', $hcwa_head, 'production head-inline has the prototype flags off' );
	$GLOBALS['hcwa_test']['env'] = 'staging';
}

// Unknown names fail closed; the guard refuses every listed still until the door is approved.
$hcwa_art = new HCWA_Theme_Art( '/', $hcwa_gen_dir );
hcwa_assert_true( $hcwa_art->shows_little_rose_door( 'a-still-nobody-made' ), 'an unknown still counts as showing the Little Rose door' );
hcwa_assert_false( $hcwa_art->has_still( 'a-still-nobody-made' ), 'an unknown still is missing' );
hcwa_assert_same( '', $hcwa_art->still( 'a-still-nobody-made' ), 'an unknown still prints nothing' );
hcwa_assert_same( '', $hcwa_art->lit_still( 'dusk' ), 'a plain still has no lit layers' );
hcwa_assert_same( '', $hcwa_art->poster( 'not-a-shot', 'base' ), 'only the hero has posters' );
hcwa_assert_same( '', $hcwa_art->gach_art( '../x', 'art' ), 'gạch art names are checked' );
hcwa_assert_same( '', $hcwa_art->screen_art( '../../x', 'c' ), 'transom names are checked' );
hcwa_assert_same( '', $hcwa_art->gach_strip( array( 'not-an-area' ), 'gach-strip' ), 'a strip of unknown areas is empty' );
hcwa_assert_same( 'https://hcwa.test/wp-content/themes/hcwa-2026/assets/dist/svg/hole-dot-tre.svg', ( new HCWA_Theme_Art() )->asset_url( 'svg/hole-dot-tre.svg' ), 'asset_url: under assets/dist, no query string' );
$hcwa_stills = json_decode( (string) file_get_contents( $hcwa_gen_dir . '/stills.json' ), true );
$hcwa_listed = array_keys( array_filter( $hcwa_stills['stills'], static fn( array $s ): bool => true === $s['showsLittleRoseDoor'] ) );
sort( $hcwa_listed );
hcwa_assert_same( array( 'bay-cau-han', 'bay-green-bamboo', 'bay-little-rose', 'door-little-rose', 'hub-door-cau-han', 'hub-door-green-bamboo', 'hub-door-little-rose', 'hub-doors-wide', 'passage-corridor' ), $hcwa_listed, 'stills.json lists the 9 stills that show the Little Rose door (1.3)' );
if ( class_exists( 'HCWA_Art_Guard' ) ) {
	$hcwa_shut = new HCWA_Art_Guard( $hcwa_art, false );
	foreach ( $hcwa_listed as $hcwa_name ) {
		hcwa_assert_same( '', $hcwa_shut->still( $hcwa_name, array( 'sizes' => '100vw' ) ), "before approval the guard refuses $hcwa_name" );
		hcwa_assert_false( $hcwa_shut->has_still( $hcwa_name ), "before approval $hcwa_name reads as missing" );
	}
	foreach ( array( 'door-green-bamboo', 'door-cau-han', 'dusk', 'gallery-wall' ) as $hcwa_name ) {
		hcwa_assert_true( str_starts_with( $hcwa_shut->still( $hcwa_name ), '<picture' ), "before approval $hcwa_name still prints" );
	}
	hcwa_assert_true( str_starts_with( $hcwa_shut->lit_still( 'header-our-work' ), '<div class="lit lit--still"' ), 'header stills are not refused' );
	hcwa_assert_true( str_starts_with( ( new HCWA_Art_Guard( $hcwa_art, true ) )->still( 'passage-corridor' ), '<picture' ), 'after approval the corridor still prints' );
} else {
	hcwa_test_skip( 'HCWA_Art_Guard cases: hcwa-core has no includes/art.php yet' );
}

// ---------- Manifest enqueue: no query string on any dist URL (1.2) ----------
hcwa_theme_manifest( $hcwa_manifest );
hcwa_assert_same( $hcwa_dist . 'assets/main-AbC123xy.js', hcwa_theme_dist_url( hcwa_theme_dist_entry( 'src/main.js' )['file'] ), 'dist URL: the file under assets/dist, nothing appended' );
hcwa_assert_same( array( 'assets/common-Zz9.js' ), hcwa_theme_dist_imports( 'src/main.js' ), 'modulepreload: the entry\'s static imports only' );
hcwa_assert_same( 20, $hcwa_hooked( 'wp_enqueue_scripts', 'hcwa_theme_enqueue_assets' ), 'enqueued at priority 20' );

$GLOBALS['hcwa_test']['enqueued_styles'] = array();
hcwa_theme_assets_context( array( 'body_class' => 'page-home', 'lit' => true ) );
hcwa_theme_enqueue_assets();
hcwa_assert_same(
	array(
		array( 'hcwa-draft', $hcwa_dist . 'assets/draft-Dr4.css', array(), null ),
		array( 'hcwa-main', $hcwa_dist . 'assets/main-Cs5.css', array( 'hcwa-draft' ), null ),
	),
	$GLOBALS['hcwa_test']['enqueued_styles'],
	'staging: draft CSS, then the main CSS after it, both with version null (no ?ver)'
);
$GLOBALS['hcwa_test']['env']             = 'production';
$GLOBALS['hcwa_test']['enqueued_styles'] = array();
hcwa_theme_enqueue_assets();
hcwa_assert_same( array( array( 'hcwa-main', $hcwa_dist . 'assets/main-Cs5.css', array(), null ) ), $GLOBALS['hcwa_test']['enqueued_styles'], 'production: the main CSS only, never the draft CSS' );
$GLOBALS['hcwa_test']['env'] = 'staging';

ob_start();
hcwa_theme_print_module();
$hcwa_module = (string) ob_get_clean();
hcwa_assert_same( '<script type="module" crossorigin src="' . $hcwa_dist . 'assets/main-AbC123xy.js"></script>' . "\n" . '<link rel="modulepreload" crossorigin href="' . $hcwa_dist . 'assets/common-Zz9.js">' . "\n", $hcwa_module, 'the module and its modulepreload links, printed by hand in <head>' );
hcwa_assert_same( 9, $hcwa_hooked( 'wp_head', 'hcwa_theme_print_module' ), 'the module prints in wp_head (priority 9), not in the footer' );
hcwa_assert_false( str_contains( $hcwa_module . wp_json_encode( $GLOBALS['hcwa_test']['enqueued_styles'] ), '?ver' ), 'no ?ver on any dist URL' );

$hcwa_fonts = hcwa_theme_preload_fonts( array() );
hcwa_assert_same( array( $hcwa_dist . 'fonts/archivo-w78-600-700.woff2', $hcwa_dist . 'fonts/be-vietnam-pro-400.woff2' ), array_column( $hcwa_fonts, 'href' ), 'font preloads: Archivo then Be Vietnam Pro 400, at the bundle\'s own URLs' );
hcwa_assert_same( array( 'anonymous', 'anonymous' ), array_column( $hcwa_fonts, 'crossorigin' ), 'font preloads are crossorigin' );
ob_start();
hcwa_theme_favicon_fallback();
$hcwa_icons = (string) ob_get_clean();
hcwa_assert_contains( 'href="' . $hcwa_dist . 'img/favicon-32.png"', $hcwa_icons, 'favicon from the bundle' );
hcwa_assert_contains( 'href="' . $hcwa_dist . 'img/apple-touch-icon.png"', $hcwa_icons, 'touch icon from the bundle' );

// Share script on single posts only; wp.css only on WordPress-only pages (and the bars).
$GLOBALS['hcwa_test']['enqueued_scripts'] = array();
hcwa_theme_enqueue_assets();
hcwa_assert_same( array(), $GLOBALS['hcwa_test']['enqueued_scripts'] ?? array(), 'no share script off single posts' );
$GLOBALS['hcwa_test']['is']['is_singular'] = true;
hcwa_theme_enqueue_assets();
hcwa_assert_same( 'hcwa-share', $GLOBALS['hcwa_test']['enqueued_scripts'][0][0] ?? '', 'the share script on a single post' );
hcwa_assert_same( array( 'strategy' => 'defer', 'in_footer' => false ), $GLOBALS['hcwa_test']['enqueued_scripts'][0][4] ?? null, 'deferred' );
unset( $GLOBALS['hcwa_test']['is']['is_singular'] );

// ---------- Dequeues on prototype views, none on WordPress-only pages ----------
foreach ( array( 'page-home', 'page-work', 'page-center', 'page-program', 'page-gap' ) as $hcwa_class ) {
	hcwa_theme_assets_context( array( 'body_class' => $hcwa_class ) );
	hcwa_assert_true( hcwa_theme_prototype_view(), "$hcwa_class is a prototype view" );
}
$GLOBALS['hcwa_test']['style_queue']     = array( 'wp-block-library', 'global-styles', 'classic-theme-styles', 'wp-block-paragraph', 'hcwa-main', 'wp-img-auto-sizes-contain', 'admin-bar' );
$GLOBALS['hcwa_test']['dequeued_styles'] = array();
hcwa_theme_assets_context( array( 'body_class' => 'page-home' ) );
hcwa_theme_dequeue_core_styles();
hcwa_assert_same( array( 'hcwa-main', 'admin-bar' ), $GLOBALS['hcwa_test']['style_queue'], 'a prototype view keeps the bundle and the admin bar, drops core block, global and classic styles' );
$GLOBALS['hcwa_test']['style_queue']     = array( 'wp-block-library', 'global-styles', 'hcwa-main' );
$GLOBALS['hcwa_test']['dequeued_styles'] = array();
hcwa_theme_assets_context( array( 'body_class' => 'page-wp' ) );
hcwa_theme_dequeue_core_styles();
hcwa_assert_same( array( 'wp-block-library', 'global-styles', 'hcwa-main' ), $GLOBALS['hcwa_test']['style_queue'], 'a WordPress-only page keeps core\'s styles for its blocks' );
hcwa_assert_same( 100, $hcwa_hooked( 'wp_enqueue_scripts', 'hcwa_theme_dequeue_core_styles' ), 'dequeued after everything is queued' );
hcwa_assert_same( 0, $hcwa_hooked( 'wp_footer', 'hcwa_theme_dequeue_core_styles' ), 'and again before the footer prints late styles' );
$hcwa_trim = function_exists( 'hcwa_theme_trim_core_head' ) ? ( new ReflectionFunction( 'hcwa_theme_trim_core_head' ) ) : null;
$hcwa_trim = $hcwa_trim ? implode( '', array_slice( file( $hcwa_trim->getFileName() ), $hcwa_trim->getStartLine() - 1, $hcwa_trim->getEndLine() - $hcwa_trim->getStartLine() + 1 ) ) : '';
foreach ( array( "'wp_print_font_faces', 50", "'wp_enqueue_img_auto_sizes_contain_css_fix', 0", "'wp_enqueue_global_styles', 1", "'wp_enqueue_stored_styles', 1", "'wp_enqueue_classic_theme_styles'", "'wp_common_block_scripts_and_styles'" ) as $hcwa_needle ) {
	hcwa_assert_contains( $hcwa_needle, $hcwa_trim, "prototype views drop $hcwa_needle" );
}
hcwa_assert_contains( "remove_action( 'wp_head', 'wp_oembed_add_host_js' );", $hcwa_read( 'inc/assets.php' ), 'no wp-embed host script' );
hcwa_assert_contains( "remove_action( 'wp_head', 'print_emoji_detection_script', 7 );", $hcwa_read( 'inc/assets.php' ), 'no emoji script' );
hcwa_theme_assets_context( array() );

// ---------- #i18n, data-lit, bar scripts (inc/head.php) ----------
$hcwa_strings = hcwa_theme_js_strings();
hcwa_assert_same( array( 'copy', 'copied', 'copyFailed', 'f_sending', 'f_send', 'e_summary', 'close', 'motionPause', 'motionPlay', 'draftHide', 'draftShow' ), array_keys( $hcwa_strings ), '#i18n carries the prototype\'s 11 keys' );
foreach ( $hcwa_strings as $hcwa_key => $hcwa_text ) {
	hcwa_assert_true( '' !== $hcwa_text && $hcwa_key !== $hcwa_text, "#i18n $hcwa_key has text" );
}
ob_start();
hcwa_theme_print_i18n();
$hcwa_i18n = (string) ob_get_clean();
hcwa_assert_true( str_starts_with( $hcwa_i18n, '<script type="application/json" id="i18n">' ), '#i18n is a JSON script' );
hcwa_assert_same( $hcwa_strings, json_decode( (string) preg_replace( '#^<script[^>]*>|</script>\s*$#', '', $hcwa_i18n ), true ), '#i18n parses back to the strings' );
hcwa_assert_same( 99, $hcwa_hooked( 'wp_head', 'hcwa_theme_print_i18n' ), '#i18n at wp_head priority 99' );
hcwa_assert_false( str_contains( implode( '', array_map( $hcwa_read, array( 'inc/assets.php', 'inc/head.php' ) ) ), 'hcwaI18n =' ), 'window.hcwaI18n is gone' );

hcwa_theme_assets_context( array( 'lit' => true, 'body_class' => 'page-work' ) );
hcwa_assert_same( 'lang="vi" data-lit', hcwa_theme_language_attributes( 'lang="vi"', 'html' ), 'data-lit on a lit page' );
hcwa_assert_same( 'lang="vi"', hcwa_theme_language_attributes( 'lang="vi"', 'xhtml' ), 'not for the xhtml doctype' );
hcwa_theme_assets_context( array( 'lit' => false, 'body_class' => 'page-donate' ) );
hcwa_assert_same( 'lang="en-GB"', hcwa_theme_language_attributes( 'lang="en-GB"', 'html' ), 'no data-lit on a page without a lit header' );
hcwa_theme_assets_context( array() );

$hcwa_en       = hcwa_theme_bar_script( 'en' );
$hcwa_announce = hcwa_theme_bar_script( 'announce' );
hcwa_assert_true( str_starts_with( $hcwa_en, "<script>\n(function (el, which) {" ), 'EN bar script: the wp-bars.js function, without its comment' );
hcwa_assert_true( str_ends_with( $hcwa_en, '})(document.currentScript.previousElementSibling, "en");' . "\n</script>\n" ), 'called with the element just printed and "en"' );
hcwa_assert_contains( '(document.currentScript.previousElementSibling, "announce");', $hcwa_announce, 'the announcement variant' );
hcwa_assert_same( '', hcwa_theme_bar_script( 'other' ), 'no other variant' );
hcwa_assert_contains( '"hcwa-announce-" + hash', $hcwa_announce, 'dismissal remembered per announcement text, as before the port' );
hcwa_assert_contains( 'get("hcwa-en-bar")', $hcwa_en, 'the EN bar reads the bundle\'s own dismissal key' );
if ( $hcwa_has_node ) {
	foreach ( array( 'en' => $hcwa_en, 'announce' => $hcwa_announce ) as $hcwa_which => $hcwa_tag ) {
		$hcwa_js = tempnam( sys_get_temp_dir(), 'hcwa-bar-' ) . '.js';
		file_put_contents( $hcwa_js, (string) preg_replace( '#^<script>|</script>\s*$#', '', $hcwa_tag ) );
		exec( 'node --check ' . escapeshellarg( $hcwa_js ) . ' 2>&1', $hcwa_lines, $hcwa_status );
		unlink( $hcwa_js );
		hcwa_assert_same( 0, $hcwa_status, "the inline $hcwa_which bar script parses" );
	}
} else {
	hcwa_test_skip( 'inline bar scripts not parsed: no node' );
}

// ---------- CSP hashes of the inline scripts, as printed (sources.json, 1.2) ----------
$hcwa_sources = json_decode( (string) file_get_contents( $hcwa_gen_dir . '/sources.json' ), true );
$hcwa_csp     = static fn( string $tag ): string => "'sha256-" . base64_encode( hash( 'sha256', (string) preg_replace( '#^<script[^>]*>|</script>\s*$#', '', $tag ), true ) ) . "'";
$GLOBALS['hcwa_test']['env'] = 'staging';
ob_start();
hcwa_theme_print_head_first();
$hcwa_tags = preg_split( '#(?<=</script>\n)#', (string) ob_get_clean(), -1, PREG_SPLIT_NO_EMPTY );
hcwa_assert_same( $hcwa_sources['csp']['head-inline.staging.js'] ?? null, $hcwa_csp( $hcwa_tags[0] ?? '' ), 'CSP hash of the staging head-inline script as printed' );
hcwa_assert_same( $hcwa_sources['csp']['page-reveal.js'] ?? null, $hcwa_csp( $hcwa_tags[1] ?? '' ), 'CSP hash of pageReveal as printed' );
$GLOBALS['hcwa_test']['env'] = 'production';
ob_start();
hcwa_theme_print_head_first();
$hcwa_tags = preg_split( '#(?<=</script>\n)#', (string) ob_get_clean(), -1, PREG_SPLIT_NO_EMPTY );
hcwa_assert_same( $hcwa_sources['csp']['head-inline.production.js'] ?? null, $hcwa_csp( $hcwa_tags[0] ?? '' ), 'CSP hash of the production head-inline script as printed' );
$GLOBALS['hcwa_test']['env'] = 'staging';
hcwa_assert_same( $hcwa_sources['csp']['wp-bars en'] ?? null, $hcwa_csp( $hcwa_en ), 'CSP hash of the EN bar script as printed' );
hcwa_assert_same( $hcwa_sources['csp']['wp-bars announce'] ?? null, $hcwa_csp( $hcwa_announce ), 'CSP hash of the announcement bar script as printed' );

// ---------- Generated files (1.3) ----------
foreach ( array( 'head-inline.staging.js', 'head-inline.production.js' ) as $hcwa_file ) {
	$hcwa_size = (int) @filesize( $hcwa_gen_dir . '/' . $hcwa_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- missing file reads as 0.
	hcwa_assert_true( $hcwa_size > 0 && $hcwa_size <= 1536, "$hcwa_file is there and within 1536 B ($hcwa_size B)" );
}
hcwa_assert_true( str_starts_with( hcwa_theme_generated( 'page-reveal.js' ), '(function pageReveal() {' ), 'page-reveal.js is the prototype\'s pageReveal' );

// ---------- The bundle itself, when built (gate 7.2, 7.10) ----------
hcwa_theme_manifest( array() );
$hcwa_real = hcwa_theme_manifest();
if ( ! $hcwa_real ) {
	hcwa_test_skip( 'bundle checks: no assets/dist (npm --prefix prototype run export:wp)' );
} else {
	$hcwa_main = basename( (string) $hcwa_real['src/main.js']['file'] );
	$hcwa_seen = 0;
	foreach ( glob( $hcwa_theme_dir . '/assets/dist/assets/*.js' ) as $hcwa_chunk ) {
		preg_match_all( '#(?:from\s*|import\(\s*)["\']\./(main-[\w-]+\.js)["\']#', (string) file_get_contents( $hcwa_chunk ), $hcwa_m );
		foreach ( $hcwa_m[1] as $hcwa_imported ) {
			++$hcwa_seen;
			hcwa_assert_same( $hcwa_main, $hcwa_imported, basename( $hcwa_chunk ) . ' imports the entry the module tag loads (main.js evaluated once)' );
		}
	}
	hcwa_assert_true( $hcwa_seen > 0, 'the motion and world chunks import the entry' );
	foreach ( array( '_headers', '_redirects', 'robots.txt' ) as $hcwa_file ) {
		hcwa_assert_false( file_exists( $hcwa_theme_dir . '/assets/dist/' . $hcwa_file ), "no Netlify $hcwa_file in the theme" );
	}
	foreach ( array( 'fonts/archivo-w78-600-700.woff2', 'fonts/be-vietnam-pro-400.woff2', 'img/hcwa-logo.webp', 'img/favicon-32.png', 'img/apple-touch-icon.png', 'svg/hole-dot-tre.svg' ) as $hcwa_file ) {
		hcwa_assert_true( file_exists( $hcwa_theme_dir . '/assets/dist/' . $hcwa_file ), "dist has $hcwa_file" );
	}
	foreach ( (array) ( $hcwa_real['src/main.js']['css'] ?? array() ) as $hcwa_css ) {
		hcwa_assert_false( (bool) preg_match( '#url\(\s*["\']?/(?:fonts|svg|img|posters|assets)/#', (string) file_get_contents( $hcwa_theme_dir . '/assets/dist/' . $hcwa_css ) ), "$hcwa_css: no root-absolute url()" );
	}
}

// ---------- Editor styles (1.5) ----------
hcwa_theme_manifest( $hcwa_manifest );
$GLOBALS['hcwa_test']['editor_styles'] = array();
hcwa_theme_editor_styles();
hcwa_assert_same( array( array( 'assets/dist/assets/draft-Dr4.css', 'assets/dist/assets/main-Cs5.css', 'assets/css/editor.css' ) ), $GLOBALS['hcwa_test']['editor_styles'], 'editor styles: the bundle\'s draft and main CSS, then editor.css (no world or motion chunk)' );
hcwa_assert_same( 11, $hcwa_hooked( 'after_setup_theme', 'hcwa_theme_editor_styles' ), 'editor styles registered from inc/assets.php after theme setup' );
hcwa_theme_manifest( array() );
$hcwa_editor = $hcwa_read( 'assets/css/editor.css' );
hcwa_assert_contains( '[data-draft] { outline: 1px dashed', $hcwa_editor, 'editors always see the draft outlines' );
hcwa_assert_false( (bool) preg_match( '/#[0-9a-fA-F]{3,8}\b/', (string) preg_replace( '#/\*.*?\*/#s', '', $hcwa_editor ) ), 'editor.css uses prototype tokens only' );

// ---------- theme.json (1.5) ----------
$hcwa_json   = json_decode( $hcwa_read( 'theme.json' ), true );
$hcwa_tokens = (string) file_get_contents( $hcwa_repo . '/prototype/src/styles/tokens.css' );
hcwa_assert_same( 3, $hcwa_json['version'] ?? null, 'theme.json version 3' );
foreach ( array( 'appearanceTools' => $hcwa_json['settings']['appearanceTools'] ?? null, 'color.defaultPalette' => $hcwa_json['settings']['color']['defaultPalette'] ?? null, 'color.defaultGradients' => $hcwa_json['settings']['color']['defaultGradients'] ?? null, 'color.custom' => $hcwa_json['settings']['color']['custom'] ?? null, 'typography.defaultFontSizes' => $hcwa_json['settings']['typography']['defaultFontSizes'] ?? null, 'typography.customFontSize' => $hcwa_json['settings']['typography']['customFontSize'] ?? null, 'typography.textTransform' => $hcwa_json['settings']['typography']['textTransform'] ?? null ) as $hcwa_key => $hcwa_value ) {
	hcwa_assert_false( $hcwa_value, "theme.json $hcwa_key off" );
}
hcwa_assert_false( isset( $hcwa_json['styles'] ), 'theme.json has no styles (the bundle styles everything)' );
$hcwa_palette = array_column( $hcwa_json['settings']['color']['palette'], 'color', 'slug' );
hcwa_assert_same( array( 'ink-900', 'ink-700', 'blue-700', 'blue-900', 'sun-400', 'sun-100', 'lime-50', 'lime-100' ), array_keys( $hcwa_palette ), 'the 8 editor colours, slugs are token names' );
foreach ( $hcwa_palette as $hcwa_slug => $hcwa_color ) {
	hcwa_assert_true( (bool) preg_match( '/--color-' . preg_quote( $hcwa_slug, '/' ) . ':\s*' . preg_quote( $hcwa_color, '/' ) . '\b/i', $hcwa_tokens ), "palette $hcwa_slug equals tokens.css" );
}
$hcwa_families = array_column( $hcwa_json['settings']['typography']['fontFamilies'], null, 'slug' );
hcwa_assert_same( array( 'display', 'body' ), array_keys( $hcwa_families ), 'two font families' );
hcwa_assert_same( 'Archivo HCWA', $hcwa_families['display']['name'] ?? '', 'display face: Archivo HCWA' );
hcwa_assert_same( 'Be Vietnam Pro', $hcwa_families['body']['name'] ?? '', 'body face: Be Vietnam Pro' );
foreach ( $hcwa_families as $hcwa_slug => $hcwa_family ) {
	hcwa_assert_true( (bool) preg_match( '/--font-' . $hcwa_slug . ':\s*' . preg_quote( $hcwa_family['fontFamily'], '/' ) . ';/', $hcwa_tokens ), "font stack $hcwa_slug equals tokens.css" );
	foreach ( $hcwa_family['fontFace'] as $hcwa_face ) {
		foreach ( $hcwa_face['src'] as $hcwa_src ) {
			hcwa_assert_true( str_starts_with( $hcwa_src, 'file:./assets/dist/fonts/' ) && is_file( $hcwa_repo . '/prototype/public/fonts/' . basename( $hcwa_src ) ), "font face from the bundle, a prototype font: $hcwa_src" );
		}
	}
}
hcwa_assert_false( (bool) preg_match( '/uppercase/i', $hcwa_read( 'theme.json' ) . $hcwa_editor ), 'no uppercase anywhere in theme.json or editor.css' );

// ---------- What the port removed ----------
foreach ( array( 'assets/css/site.css', 'assets/js/site.js', 'assets/js/bars.js', 'assets/fonts', 'assets/img' ) as $hcwa_gone ) {
	hcwa_assert_false( file_exists( $hcwa_theme_dir . '/' . $hcwa_gone ), "$hcwa_gone is gone (the bundle replaces it)" );
}
foreach ( glob( $hcwa_theme_dir . '/{*.php,inc/*.php,theme.json}', GLOB_BRACE ) as $hcwa_file ) {
	hcwa_assert_false( (bool) preg_match( '/fraunces/i', (string) file_get_contents( $hcwa_file ) ), basename( $hcwa_file ) . ': no Fraunces' );
}
$hcwa_share = $hcwa_read( 'assets/js/wp-share.js' );
hcwa_assert_contains( 'typeof navigator.share !== "function"', $hcwa_share, 'share sheet only where the browser has one' );
hcwa_assert_contains( 'item.hidden = false;', $hcwa_share, 'reveals li[data-share-native]' );

// ---------- Meta description (moved verbatim from theme-gates-test.php) ----------
hcwa_assert_contains( 'hcwa_theme_public_text( trim( wp_strip_all_tags( (string) $description ) ) )', $hcwa_read( 'inc/assets.php' ), 'meta description goes through the draft check' );
