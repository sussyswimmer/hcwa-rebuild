<?php
/**
 * Dev runtime scripts: the seed's Polylang settings and bookkeeping key
 * (scripts/wp-seed.php, loaded without WP-CLI so only its functions are declared), the
 * wp-config.php that scripts/wp-local.sh writes (bash), and the page checks of
 * scripts/wp-smoke.py (python3, without Playwright).
 *
 * @package hcwa-core
 */

$hcwa_repo = dirname( __DIR__, 2 );
require_once $hcwa_repo . '/scripts/wp-seed.php';

/**
 * Runs a command without a shell: [exit code, stdout, stderr].
 */
$hcwa_run = static function ( array $cmd, string $stdin = '' ): array {
	$proc = proc_open( $cmd, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	if ( ! is_resource( $proc ) ) {
		return array( -1, '', 'could not start ' . $cmd[0] );
	}
	fwrite( $pipes[0], $stdin );
	fclose( $pipes[0] );
	$out = (string) stream_get_contents( $pipes[1] );
	$err = (string) stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	return array( proc_close( $proc ), $out, $err );
};

/**
 * Full path of a program on PATH, or ''.
 */
$hcwa_which = static function ( string $name ): string {
	foreach ( explode( PATH_SEPARATOR, (string) getenv( 'PATH' ) ) as $dir ) {
		if ( '' !== $dir && is_file( "$dir/$name" ) && is_executable( "$dir/$name" ) ) {
			return "$dir/$name";
		}
	}
	return '';
};

// ---------------------------------------------------------------- wp-seed.php

$hcwa_sync = hcwa_seed_polylang_options()['sync'];
hcwa_assert_false( in_array( 'post_meta', $hcwa_sync, true ), "seed leaves Polylang's custom field sync off, so per-language text such as footnotes is never copied" );
hcwa_assert_same( array( 'taxonomies', 'post_parent', '_thumbnail_id' ), $hcwa_sync, 'seed syncs taxonomies, page parent and featured image' );

// The bookkeeping key is protected, so Polylang never copies it into a hand-made translation.
$hcwa_wpdb_before = $GLOBALS['wpdb'] ?? null;
$GLOBALS['wpdb']  = new class() {
	public $posts         = 'wp_posts';
	public $postmeta      = 'wp_postmeta';
	public $term_taxonomy = 'wp_term_taxonomy';
	public $termmeta      = 'wp_termmeta';
	public $sql           = array();
	public $updates       = array();
	public $renamed       = array( 'wp_postmeta' => 3, 'wp_termmeta' => 1 );

	public function prepare( $query, ...$args ) {
		return vsprintf( str_replace( '%s', "'%s'", $query ), $args );
	}

	public function get_col( $query ) {
		$this->sql[] = $query;
		return array(); // Nothing found, so the lookups never ask Polylang for a language.
	}

	public function update( $table, $data, $where ) {
		$this->updates[] = array( $table, $data, $where );
		return $this->renamed[ $table ] ?? 0;
	}
};

hcwa_assert_same( 0, hcwa_seed_find_post( 'page:home', 'page', 'en' ), 'find_post: nothing seeded yet' );
hcwa_assert_same( 0, hcwa_seed_find_term( 'category:news', 'category', 'en' ), 'find_term: nothing seeded yet' );
foreach ( $GLOBALS['wpdb']->sql as $hcwa_i => $hcwa_sql ) {
	hcwa_assert_contains( "m.meta_key = '_hcwa_seed_id'", $hcwa_sql, "lookup $hcwa_i reads the protected key" );
}
hcwa_assert_same( 2, count( $GLOBALS['wpdb']->sql ), 'one query per lookup' );

hcwa_assert_same( 4, hcwa_seed_migrate_key(), 'migration renames the old key in post and term meta' );
hcwa_assert_same(
	array(
		array( 'wp_postmeta', array( 'meta_key' => '_hcwa_seed_id' ), array( 'meta_key' => 'hcwa_seed_id' ) ),
		array( 'wp_termmeta', array( 'meta_key' => '_hcwa_seed_id' ), array( 'meta_key' => 'hcwa_seed_id' ) ),
	),
	$GLOBALS['wpdb']->updates,
	'migration: hcwa_seed_id becomes _hcwa_seed_id'
);
hcwa_assert_same( 1, $GLOBALS['hcwa_test']['cache_flushes'] ?? 0, 'migration flushes the object cache after renaming' );
$GLOBALS['wpdb']->renamed = array();
hcwa_assert_same( 0, hcwa_seed_migrate_key(), 'migration on an instance without the old key renames nothing' );
hcwa_assert_same( 1, $GLOBALS['hcwa_test']['cache_flushes'] ?? 0, 'no cache flush when nothing was renamed' );

if ( null === $hcwa_wpdb_before ) {
	unset( $GLOBALS['wpdb'] );
} else {
	$GLOBALS['wpdb'] = $hcwa_wpdb_before;
}

// Pages without a pattern: hcwa/* blocks sit in a `container` group with their heading, as
// in the page patterns; text-only sections stay in the reading column.
$hcwa_defs = hcwa_seed_page_defs();
foreach ( array( 'vi', 'en' ) as $hcwa_lang ) {
	$hcwa_body  = hcwa_seed_page_body( 'impact', $hcwa_defs['impact'], $hcwa_lang );
	$hcwa_group = "<!-- wp:group {\"className\":\"container\"} -->\n<div class=\"wp-block-group container\">\n";
	hcwa_assert_contains( $hcwa_group . '<!-- wp:hcwa/impact-strip {"limit":12,"showLink":false} /-->' . "\n</div>\n<!-- /wp:group -->", $hcwa_body, "impact ($hcwa_lang): impact strip in a container" );
	hcwa_assert_contains( $hcwa_group . "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\" id=\"" . ltrim( (string) hcwa_page_paths()['reports'][ $hcwa_lang ], '#' ) . '"', $hcwa_body, "impact ($hcwa_lang): reports heading opens the container" );
	hcwa_assert_contains( '<!-- wp:hcwa/report-list {"limit":12} /-->' . "\n</div>\n<!-- /wp:group -->", $hcwa_body, "impact ($hcwa_lang): report list closes the container" );
	hcwa_assert_same( substr_count( $hcwa_body, '<!-- wp:group' ), substr_count( $hcwa_body, '<!-- /wp:group -->' ), "impact ($hcwa_lang): groups balanced" );

	$hcwa_body = hcwa_seed_page_body( 'policies', $hcwa_defs['policies'], $hcwa_lang );
	hcwa_assert_false( str_contains( $hcwa_body, 'wp:group' ), "policies ($hcwa_lang): text-only sections are not wrapped" );
	$hcwa_body = hcwa_seed_page_body( 'partners', $hcwa_defs['partners'], $hcwa_lang );
	hcwa_assert_contains( $hcwa_group . '<!-- wp:hcwa/partner-wall {"homeOnly":false,"limit":48} /-->', $hcwa_body, "partners ($hcwa_lang): partner wall in a container" );
}
hcwa_assert_true( function_exists( 'hcwa_seed_news_tags' ), 'seed gives each center a news tag (Little Rose needs one for its news to be protected)' );

// ---------------------------------------------------------------- wp-local.sh

$hcwa_bash = $hcwa_which( 'bash' );
hcwa_assert_true( '' !== $hcwa_bash, 'bash is on PATH' );
if ( '' !== $hcwa_bash ) {
	$hcwa_tmp = sys_get_temp_dir() . '/hcwa-runtime-test-' . getmypid();
	[ $hcwa_code, , $hcwa_err ] = $hcwa_run(
		array(
			$hcwa_bash,
			'-c',
			'set -euo pipefail; source "$1"; LOCAL_DIR="$2"; PHP_BIN="$3"; mkdir -p "$LOCAL_DIR/demo"; write_config demo 8899 >/dev/null',
			'hcwa-test',
			$hcwa_repo . '/scripts/wp-local.sh',
			$hcwa_tmp,
			PHP_BINARY,
		)
	);
	$hcwa_config = "$hcwa_tmp/demo/wp-config.php";
	hcwa_assert_same( 0, $hcwa_code, 'wp-local.sh can be sourced and write_config runs' . ( '' !== $hcwa_err ? ": $hcwa_err" : '' ) );
	$hcwa_text = is_file( $hcwa_config ) ? (string) file_get_contents( $hcwa_config ) : '';
	hcwa_assert_contains( "define( 'PLL_CACHE_HOME_URL', false );", $hcwa_text, "wp-config.php: Polylang's home URLs follow the current port" );
	hcwa_assert_contains( "\$hcwa_local_url = 'http://127.0.0.1:8899';", $hcwa_text, 'wp-config.php: fallback address uses the setup port' );
	[ $hcwa_code, $hcwa_out ] = $hcwa_run( array( PHP_BINARY, '-l', $hcwa_config ) );
	hcwa_assert_same( 0, $hcwa_code, 'wp-config.php is valid PHP: ' . trim( $hcwa_out ) );
	if ( is_file( $hcwa_config ) ) {
		unlink( $hcwa_config );
	}
	foreach ( array( "$hcwa_tmp/demo", $hcwa_tmp ) as $hcwa_dir ) {
		if ( is_dir( $hcwa_dir ) ) {
			rmdir( $hcwa_dir );
		}
	}
}

// ---------------------------------------------------------------- wp-smoke.py check()

$hcwa_python = $hcwa_which( 'python3' );
hcwa_assert_true( '' !== $hcwa_python, 'python3 is on PATH' );
if ( '' === $hcwa_python ) {
	return;
}

$hcwa_base = 'http://127.0.0.1:8881';

/**
 * Keyword arguments for check(): the About page in one language with its alternates.
 *
 * @param string $lang     'vi' or 'en'.
 * @param array  $hreflang [vi, en, x-default] hrefs.
 * @param array  $extra    Values replacing the defaults (merged recursively).
 */
$hcwa_case = static function ( string $lang, array $hreflang, array $extra = array() ) use ( $hcwa_base ): array {
	$path = 'vi' === $lang ? '/gioi-thieu/' : '/en/about/';
	$alts = array();
	foreach ( array( 'vi', 'en', 'x-default' ) as $i => $code ) {
		$alts[] = array(
			'lang' => $code,
			'href' => $hreflang[ $i ],
		);
	}
	return array_replace_recursive(
		array(
			'target'      => array(
				'id'          => 'page:about',
				'lang'        => $lang,
				'path'        => $path,
				'translation' => 'vi' === $lang ? '/en/about/' : '/gioi-thieu/',
			),
			'base'        => $hcwa_base,
			'status'      => 200,
			'redirects'   => array(),
			'final_url'   => $hcwa_base . $path,
			'html'        => '<p>Giới thiệu</p>',
			'data'        => array(
				'lang'         => $lang,
				'hreflang'     => $alts,
				'robots'       => 'noindex, nofollow',
				'h1'           => array( 'Giới thiệu' ),
				'sideways'     => 0,
				'overflow'     => array(),
				'caps'         => array(),
				'chipsVisible' => 0,
				'pendingInDom' => 0,
				'text'         => 'Giới thiệu',
			),
			'production'  => false,
			'allow_draft' => false,
		),
		$extra
	);
};
$hcwa_right = array( "$hcwa_base/gioi-thieu/", "$hcwa_base/en/about/", "$hcwa_base/gioi-thieu/" );
$hcwa_live  = static fn( string $text, bool $allow = false ): array => array(
	'production'  => true,
	'allow_draft' => $allow,
	'data'        => array(
		'robots' => '',
		'text'   => $text,
	),
);

$hcwa_cases = array(
	'vi ok'            => $hcwa_case( 'vi', $hcwa_right ),
	'en ok'            => $hcwa_case( 'en', $hcwa_right ),
	'x-default en'     => $hcwa_case( 'vi', array( $hcwa_right[0], $hcwa_right[1], "$hcwa_base/en/about/" ) ),
	'x-default root'   => $hcwa_case( 'en', array( $hcwa_right[0], $hcwa_right[1], "$hcwa_base/" ) ),
	'self wrong'       => $hcwa_case( 'vi', array( "$hcwa_base/", $hcwa_right[1], $hcwa_right[2] ) ),
	'old port'         => $hcwa_case( 'vi', str_replace( ':8881', ':8880', $hcwa_right ) ),
	'live ok'          => $hcwa_case( 'vi', $hcwa_right, $hcwa_live( 'Chú thích [1], [gallery ids="1,2"] và [Ghi chú]' ) ),
	'live [N]'         => $hcwa_case( 'vi', $hcwa_right, $hcwa_live( 'Đã giúp [N] trẻ em' ) ),
	'live [ year ]'    => $hcwa_case( 'en', $hcwa_right, $hcwa_live( 'Since [ year ]' ) ),
	'live [năm] NFD'   => $hcwa_case( 'vi', $hcwa_right, $hcwa_live( "Từ [na\u{0306}m]" ) ),
	'live HCWA note'   => $hcwa_case( 'en', $hcwa_right, $hcwa_live( 'Answer: [Answer supplied by HCWA]' ) ),
	'live allow draft' => $hcwa_case( 'vi', $hcwa_right, $hcwa_live( 'Đã giúp [N] trẻ em', true ) ),
	'live [DRAFT html' => $hcwa_case( 'vi', $hcwa_right, array( 'html' => '<img alt="[DRAFT: ảnh]">' ) + $hcwa_live( 'Giới thiệu' ) ),
);

$hcwa_py = <<<'PY'
import importlib.util, json, sys
spec = importlib.util.spec_from_file_location("wp_smoke", sys.argv[1])
smoke = importlib.util.module_from_spec(spec)
spec.loader.exec_module(smoke)
cases = json.load(sys.stdin)
print(json.dumps({"tokens": list(smoke.DRAFT_TOKENS), "fails": {k: smoke.check(**v) for k, v in cases.items()}}))
PY;

[ $hcwa_code, $hcwa_out, $hcwa_err ] = $hcwa_run(
	array( $hcwa_python, '-I', '-B', '-c', $hcwa_py, $hcwa_repo . '/scripts/wp-smoke.py' ),
	(string) json_encode( $hcwa_cases, JSON_UNESCAPED_SLASHES )
);
hcwa_assert_same( 0, $hcwa_code, 'wp-smoke.py check() runs without Playwright' . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
$hcwa_result = json_decode( $hcwa_out, true );
$hcwa_fails  = (array) ( $hcwa_result['fails'] ?? array() );

hcwa_assert_same( hcwa_draft_tokens(), $hcwa_result['tokens'] ?? null, 'wp-smoke.py DRAFT_TOKENS equal hcwa_draft_tokens() in publish-guard.php' );

$hcwa_expect = array(
	'vi ok'            => array(),
	'en ok'            => array(),
	'x-default en'     => array( 'hreflang: x-default points to /en/about/, expected /gioi-thieu/' ),
	'x-default root'   => array( 'hreflang: x-default points to /, expected /gioi-thieu/' ),
	'self wrong'       => array( 'hreflang: vi points to /, expected /gioi-thieu/' ),
	'old port'         => array( 'hreflang: links to 127.0.0.1:8880, expected 127.0.0.1:8881' ),
	'live ok'          => array(),
	'live [N]'         => array( 'draft text: Đã giúp [N] trẻ em' ),
	'live [ year ]'    => array( 'draft text: Since [ year ]' ),
	'live [năm] NFD'   => array( 'draft text: Từ [năm]' ),
	'live HCWA note'   => array( 'draft text: Answer: [Answer supplied by HCWA]' ),
	'live allow draft' => array(),
	'live [DRAFT html' => array( 'draft text: <img alt="[DRAFT: ảnh]">' ),
);
foreach ( $hcwa_expect as $hcwa_name => $hcwa_want ) {
	hcwa_assert_same( $hcwa_want, $hcwa_fails[ $hcwa_name ] ?? null, "smoke check: $hcwa_name" );
}

// HTTP gates (no browser): probes built from the seed's URL list, and their check.
$hcwa_probe_py = <<<'PY'
import importlib.util, json, sys
spec = importlib.util.spec_from_file_location("wp_smoke", sys.argv[1])
smoke = importlib.util.module_from_spec(spec)
spec.loader.exec_module(smoke)
urls = [
    {"id": "center:green-bamboo", "kind": "center", "lang": "vi", "path": "/hoat-dong/co-so/tre-xanh/", "translation": "/en/our-work/centers/green-bamboo/"},
    {"id": "center:green-bamboo", "kind": "center", "lang": "en", "path": "/en/our-work/centers/green-bamboo/", "translation": "/hoat-dong/co-so/tre-xanh/"},
    {"id": "page:about", "kind": "page", "lang": "vi", "path": "/gioi-thieu/", "translation": "/en/about/"},
]
probes = {p["id"]: p for p in smoke.gate_probes(urls)}
base = "http://127.0.0.1:8881"
redirect = probes["gate:other-language-slug"]
print(json.dumps({
    "probes": probes,
    "none": smoke.gate_probes([]),
    "fails": {
        "embed ok": smoke.check_probe(probes["gate:embed"], base, 404, ""),
        "embed open": smoke.check_probe(probes["gate:embed"], base, 200, ""),
        "media open": smoke.check_probe(probes["gate:rest-media-case"], base, 200, ""),
        "redirect ok": smoke.check_probe(redirect, base, 301, base + "/hoat-dong/co-so/tre-xanh/"),
        "redirect relative": smoke.check_probe(redirect, base, 301, "/hoat-dong/co-so/tre-xanh/"),
        "redirect 404": smoke.check_probe(redirect, base, 301, base + "/khong-co/"),
        "redirect port": smoke.check_probe(redirect, base, 301, "http://127.0.0.1:8880/hoat-dong/co-so/tre-xanh/"),
        "no redirect": smoke.check_probe(redirect, base, 404, ""),
    },
}))
PY;
[ $hcwa_code, $hcwa_out, $hcwa_err ] = $hcwa_run( array( $hcwa_python, '-I', '-B', '-c', $hcwa_probe_py, $hcwa_repo . '/scripts/wp-smoke.py' ) );
hcwa_assert_same( 0, $hcwa_code, 'wp-smoke.py gate probes run without Playwright' . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
$hcwa_result = (array) json_decode( $hcwa_out, true );
$hcwa_probes = (array) ( $hcwa_result['probes'] ?? array() );
hcwa_assert_same( '/hoat-dong/co-so/tre-xanh/embed/', $hcwa_probes['gate:embed']['path'] ?? null, 'probe: a center embed URL must answer 404' );
hcwa_assert_same( 404, $hcwa_probes['gate:embed']['status'] ?? null, 'probe: embed expects 404' );
hcwa_assert_same( 401, $hcwa_probes['gate:rest-media']['status'] ?? null, 'probe: anonymous media REST expects 401' );
hcwa_assert_same( '/wp-json/wp/v2/MEDIA', $hcwa_probes['gate:rest-media-case']['path'] ?? null, 'probe: media REST in upper case too' );
hcwa_assert_same( '/en/our-work/centers/tre-xanh/', $hcwa_probes['gate:other-language-slug']['path'] ?? null, 'probe: English base with the Vietnamese slug' );
hcwa_assert_same( '/hoat-dong/co-so/tre-xanh/', $hcwa_probes['gate:other-language-slug']['location'] ?? null, 'probe: expected at the Vietnamese permalink' );
hcwa_assert_same( 2, count( (array) ( $hcwa_result['none'] ?? array() ) ), 'probe: without centers only the REST checks' );
$hcwa_expect = array(
	'embed ok'          => array(),
	'embed open'        => array( 'gate:embed: HTTP 200, expected 404' ),
	'media open'        => array( 'gate:rest-media-case: HTTP 200, expected 401' ),
	'redirect ok'       => array(),
	'redirect relative' => array(),
	'redirect 404'      => array( 'gate:other-language-slug: redirects to http://127.0.0.1:8881/khong-co/, expected /hoat-dong/co-so/tre-xanh/' ),
	'redirect port'     => array( 'gate:other-language-slug: redirects to host 127.0.0.1:8880, not 127.0.0.1:8881' ),
	'no redirect'       => array( 'gate:other-language-slug: HTTP 404, expected 301' ),
);
foreach ( $hcwa_expect as $hcwa_name => $hcwa_want ) {
	hcwa_assert_same( $hcwa_want, $hcwa_result['fails'][ $hcwa_name ] ?? null, "smoke gate: $hcwa_name" );
}
