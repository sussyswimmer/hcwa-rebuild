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
fixtures = {"refused_photo": 412, "refused_photo_file": "hcwa-20261105-ab12cd34", "refused_photo_page": "/hoat-dong/co-so/hoa-hong-nho/"}
probes = {p["id"]: p for p in smoke.gate_probes(urls, fixtures)}
base = "http://127.0.0.1:8881"
redirect = probes["gate:other-language-slug"]
refused = probes["gate:refused-attachment-page"]
on_page = probes["gate:refused-photo-on-page"]
print(json.dumps({
    "probes": probes,
    "none": smoke.gate_probes([]),
    "fails": {
        "refused 404": smoke.check_probe(refused, base, 404, ""),
        "refused redirect": smoke.check_probe(refused, base, 301, base + "/wp-content/uploads/2026/11/hcwa-20261105-ab12cd34.jpg"),
        "refused 404 with location": smoke.check_probe(refused, base, 404, "/x/"),
        "photo not on page": smoke.check_probe(on_page, base, 200, "", "<html><img src=\"/logo.svg\"></html>"),
        "photo on page": smoke.check_probe(on_page, base, 200, "", '<meta property="og:image" content="/uploads/hcwa-20261105-ab12cd34-1200x630.webp">'),
        "head absent": smoke.check_probe(probes["gate:rest-seo-head"], base, 200, "", '[{"id":5}]'),
        "head present": smoke.check_probe(probes["gate:rest-seo-head"], base, 200, "", '[{"id":5,"yoast_head_json":{}}]'),
        "get_head 404": smoke.check_probe(probes["gate:seo-get-head"], base, 404, ""),
        "get_head open": smoke.check_probe(probes["gate:seo-get-head"], base, 200, ""),
        "sitemap any status": smoke.check_probe(probes["gate:image-sitemap"], base, 404, "", "<html>not found</html>"),
        "sitemap images": smoke.check_probe(probes["gate:image-sitemap"], base, 200, "", "<url><image:image><image:loc>x</image:loc></image:image></url>"),
        "embed ok": smoke.check_probe(probes["gate:embed"], base, 404, ""),
        "embed open": smoke.check_probe(probes["gate:embed"], base, 200, ""),
        "media open": smoke.check_probe(probes["gate:rest-media-case"], base, 200, ""),
        "redirect ok": smoke.check_probe(redirect, base, 301, base + "/hoat-dong/co-so/tre-xanh/"),
        "redirect relative": smoke.check_probe(redirect, base, 301, "/hoat-dong/co-so/tre-xanh/"),
        "redirect 404": smoke.check_probe(redirect, base, 301, base + "/khong-co/"),
        "redirect port": smoke.check_probe(redirect, base, 301, "http://127.0.0.1:8880/hoat-dong/co-so/tre-xanh/"),
        "no redirect": smoke.check_probe(redirect, base, 404, ""),
        "refused page names path": smoke.check_probe(refused, base, 404, "", "<html><a href=\"/hoat-dong/co-so/hoa-hong-nho/hcwa-20261105-ab12cd34/\">x</a></html>"),
        "refused feed clean": smoke.check_probe(probes["gate:refused-attachment-page-feed-rss2"], base, 404, "", "<html><body><h1>Not found</h1></body></html>"),
        "refused feed item": smoke.check_probe(probes["gate:refused-attachment-page-feed-rss2"], base, 404, "", "<rss><channel><item><title>x</title><link>http://h/hoat-dong/co-so/hoa-hong-nho/hcwa-20261105-ab12cd34/</link></item></channel></rss>"),
        "refused atom entry": smoke.check_probe(probes["gate:refused-attachment-p-feed-atom"], base, 404, "", "<feed><entry><title>x</title></entry></feed>"),
        "refused comments feed 200": smoke.check_probe(probes["gate:refused-attachment-pt-p-feed-comments-rss2"], base, 200, "", "<rss><channel></channel></rss>"),
        "type feed 404": smoke.check_probe(probes["gate:feed-type-hcwa-center-rss2"], base, 404, "", "<html>Not found</html>"),
        "type feed items": smoke.check_probe(probes["gate:feed-type-hcwa-program-rss2"], base, 200, "", "<rss><channel><item>x</item></channel></rss>"),
        "search feed items": smoke.check_probe(probes["gate:feed-search-atom"], base, 404, "", "<feed><entry>x</entry></feed>"),
        "any feed posts": smoke.check_probe(probes["gate:feed-type-any-rss2"], base, 200, "", "<item><link>http://h/tin/</link><description>Xem <a href=\"http://h/hoat-dong/co-so/tre-xanh/\">Tre Xanh</a></description></item>"),
        "any feed center link": smoke.check_probe(probes["gate:feed-type-any-rss2"], base, 200, "", "<item><link>http://h/hoat-dong/co-so/tre-xanh/</link></item>"),
        "any feed atom center": smoke.check_probe(probes["gate:feed-type-any-atom"], base, 200, "", "<entry><link rel=\"alternate\" type=\"text/html\" href=\"http://h/en/our-work/centers/green-bamboo/\" /></entry>"),
        "any feed center guid": smoke.check_probe(probes["gate:feed-type-any-rss2"], base, 200, "", "<item><guid isPermaLink=\"false\">http://h/?post_type=hcwa_center&#038;p=34</guid></item>"),
        "any feed refused file": smoke.check_probe(probes["gate:feed-type-any-rss2"], base, 404, "", "<enclosure url=\"http://h/wp-content/uploads/hcwa-20261105-ab12cd34.jpg\"/>"),
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
hcwa_assert_same( 13, count( (array) ( $hcwa_result['none'] ?? array() ) ), 'probe: without centers or fixtures only the REST, sitemap and feed checks (5 + 8 feeds)' );
hcwa_assert_same( '/?attachment_id=412', $hcwa_probes['gate:refused-attachment-page']['path'] ?? null, 'probe: the refused fixture\'s attachment page' );
hcwa_assert_same( array( 'hcwa-20261105-ab12cd34' ), $hcwa_probes['gate:refused-photo-on-page']['body_absent'] ?? null, 'probe: the Little Rose page never names the refused file' );
hcwa_assert_same( array( 401, 404 ), $hcwa_probes['gate:seo-get-head']['status'] ?? null, 'probe: the SEO head route never answers 200' );
$hcwa_expect = array(
	'refused 404'               => array(),
	'refused redirect'          => array( 'gate:refused-attachment-page: HTTP 301, expected 404', 'gate:refused-attachment-page: redirects to http://127.0.0.1:8881/wp-content/uploads/2026/11/hcwa-20261105-ab12cd34.jpg, expected no redirect' ),
	'refused 404 with location' => array( 'gate:refused-attachment-page: redirects to /x/, expected no redirect' ),
	'photo not on page'         => array(),
	'photo on page'             => array( "gate:refused-photo-on-page: the response contains 'hcwa-20261105-ab12cd34'" ),
	'head absent'               => array(),
	'head present'              => array( "gate:rest-seo-head: the response contains 'yoast_head'" ),
	'get_head 404'              => array(),
	'get_head open'             => array( 'gate:seo-get-head: HTTP 200, expected 401 or 404' ),
	'sitemap any status'        => array(),
	'sitemap images'            => array( "gate:image-sitemap: the response contains '<image:loc'" ),
	'embed ok'          => array(),
	'embed open'        => array( 'gate:embed: HTTP 200, expected 404' ),
	'media open'        => array( 'gate:rest-media-case: HTTP 200, expected 401' ),
	'redirect ok'       => array(),
	'redirect relative' => array(),
	'redirect 404'      => array( 'gate:other-language-slug: redirects to http://127.0.0.1:8881/khong-co/, expected /hoat-dong/co-so/tre-xanh/' ),
	'redirect port'     => array( 'gate:other-language-slug: redirects to host 127.0.0.1:8880, not 127.0.0.1:8881' ),
	'no redirect'       => array( 'gate:other-language-slug: HTTP 404, expected 301' ),
	// Critic round 2: a refused answer is checked by its body too, and in every feed format.
	'refused page names path'   => array( "gate:refused-attachment-page: the response contains 'hcwa-20261105-ab12cd34'", "gate:refused-attachment-page: the response contains '/hoa-hong-nho/'" ),
	'refused feed clean'        => array(),
	'refused feed item'         => array( "gate:refused-attachment-page-feed-rss2: the response contains '<item>'", "gate:refused-attachment-page-feed-rss2: the response contains 'hcwa-20261105-ab12cd34'", "gate:refused-attachment-page-feed-rss2: the response contains '/hoa-hong-nho/'" ),
	'refused atom entry'        => array( "gate:refused-attachment-p-feed-atom: the response contains '<entry>'" ),
	'refused comments feed 200' => array( 'gate:refused-attachment-pt-p-feed-comments-rss2: HTTP 200, expected 404' ),
	'type feed 404'             => array(),
	'type feed items'           => array( 'gate:feed-type-hcwa-program-rss2: HTTP 200, expected 404', "gate:feed-type-hcwa-program-rss2: the response contains '<item>'" ),
	'search feed items'         => array( "gate:feed-search-atom: the response contains '<entry>'" ),
	'any feed posts'            => array(),
	'any feed center link'      => array( "gate:feed-type-any-rss2: the response contains '<link>http://h/hoat-dong/co-so/tre-xanh/</link>'" ),
	'any feed center guid'      => array( "gate:feed-type-any-rss2: the response contains 'post_type=hcwa_center'" ),
	'any feed refused file'     => array( "gate:feed-type-any-rss2: the response contains 'hcwa-20261105-ab12cd34'" ),
);
foreach ( $hcwa_expect as $hcwa_name => $hcwa_want ) {
	hcwa_assert_same( $hcwa_want, $hcwa_result['fails'][ $hcwa_name ] ?? null, "smoke gate: $hcwa_name" );
}
$hcwa_atom = (array) ( $hcwa_result['fails']['any feed atom center'] ?? array() );
hcwa_assert_true( 1 === count( $hcwa_atom ) && str_contains( (string) $hcwa_atom[0], 'href="http://h/en/our-work/centers/green-bamboo/"' ), 'smoke gate: an Atom entry linking a center fails the any-type feed' );
$hcwa_refused_html = array_filter( $hcwa_probes, static fn( array $p, string $id ): bool => str_starts_with( $id, 'gate:refused-attachment-' ) && ! str_contains( $id, '-feed-' ), ARRAY_FILTER_USE_BOTH );
$hcwa_refused_feed = array_filter( $hcwa_probes, static fn( array $p, string $id ): bool => str_starts_with( $id, 'gate:refused-attachment-' ) && str_contains( $id, '-feed-' ), ARRAY_FILTER_USE_BOTH );
hcwa_assert_same( 8, count( $hcwa_refused_html ), 'probes: 8 HTML queries name the refused attachment' );
hcwa_assert_same( 24, count( $hcwa_refused_feed ), 'probes: each of them as an rss2, atom and comments-rss2 feed' );
foreach ( $hcwa_refused_html as $hcwa_id => $hcwa_probe ) {
	hcwa_assert_same( array( 404, true, array( 'hcwa-20261105-ab12cd34', '/hoa-hong-nho/', '/little-rose/' ) ), array( $hcwa_probe['status'] ?? null, $hcwa_probe['no_location'] ?? null, $hcwa_probe['body_absent'] ?? null ), "probe $hcwa_id: 404, no Location, no file name or Little Rose path in the body" );
}
foreach ( $hcwa_refused_html as $hcwa_id => $hcwa_probe ) {
	foreach ( array( 'rss2', 'atom', 'comments-rss2' ) as $hcwa_feed ) {
		$hcwa_fp = $hcwa_probes[ "$hcwa_id-feed-$hcwa_feed" ] ?? array();
		hcwa_assert_same( array( $hcwa_probe['path'] . "&feed=$hcwa_feed", 404, true ), array( $hcwa_fp['path'] ?? null, $hcwa_fp['status'] ?? null, $hcwa_fp['no_location'] ?? null ), "probe $hcwa_id as $hcwa_feed: 404 with no Location" );
		hcwa_assert_true( ! array_diff( array( '<item>', '<entry>', 'hcwa-20261105-ab12cd34', '/hoa-hong-nho/', '/little-rose/' ), (array) ( $hcwa_fp['body_absent'] ?? array() ) ), "probe $hcwa_id as $hcwa_feed: no item, entry, file name or Little Rose path" );
	}
}
foreach ( array( '/?feed=rss2&post_type=hcwa_center', '/?feed=atom&post_type=hcwa_center', '/?feed=rss2&post_type=hcwa_program', '/?feed=atom&post_type=hcwa_program', '/?s=a&feed=rss2', '/?s=a&feed=atom' ) as $hcwa_path ) {
	$hcwa_fp = array_values( array_filter( $hcwa_probes, static fn( array $p ): bool => $hcwa_path === ( $p['path'] ?? '' ) ) );
	hcwa_assert_true( 1 === count( $hcwa_fp ) && 404 === ( $hcwa_fp[0]['status'] ?? null ) && in_array( '<item>', (array) ( $hcwa_fp[0]['body_absent'] ?? array() ), true ), "probe $hcwa_path: 404 with no item" );
}

// ---------------------------------------------------------------- 11-WP-PORT.md harness (9 Nov)

// The test harness itself: a separate process with or without hcwa-core.
[ $hcwa_code, $hcwa_out ] = hcwa_test_isolated( "echo function_exists( 'hcwa_t' ) ? 'core' : 'none';", false );
hcwa_assert_same( array( 0, 'none' ), array( $hcwa_code, trim( $hcwa_out ) ), 'hcwa_test_isolated() can load the stubs without hcwa-core' );
[ $hcwa_code, $hcwa_out ] = hcwa_test_isolated( "echo function_exists( 'hcwa_t' ) ? 'core' : 'none';" );
hcwa_assert_same( array( 0, 'core' ), array( $hcwa_code, trim( $hcwa_out ) ), 'hcwa_test_isolated() loads hcwa-core by default' );
$hcwa_golden = hcwa_test_golden();
hcwa_assert_true( null === $hcwa_golden || is_array( $hcwa_golden ), 'hcwa_test_golden(): the fixtures, or null before the export' );

// Seed: header stills only where the prototype rendered one, never for a protected center.
hcwa_seed_repo( $hcwa_repo );
hcwa_assert_same( 'header-green-bamboo', hcwa_seed_header_still( 'green-bamboo' ), 'seed: Green Bamboo gets its header still' );
hcwa_assert_same( 'header-thrive-together', hcwa_seed_header_still( 'thrive-together' ), 'seed: Thrive Together gets its header still' );
hcwa_assert_same( 'header-cau-han', hcwa_seed_header_still( 'cau-han' ), 'seed: Cau Han gets its header still (M6)' );
hcwa_assert_same( '', hcwa_seed_header_still( 'charity-class' ), 'seed: no still rendered for Charity Class, so the placeholder' );
hcwa_assert_same( '', hcwa_seed_header_still( 'little-rose', true ), 'seed: never a header still for a protected center' );
hcwa_assert_true( function_exists( 'hcwa_seed_program_reports' ) && function_exists( 'hcwa_seed_parity' ), 'seed writes the program reports and the parity block' );

// wp-local.sh: the parity command.
if ( '' !== $hcwa_bash ) {
	[ $hcwa_code, $hcwa_out ] = $hcwa_run( array( $hcwa_bash, '-c', 'source "$1"; declare -F cmd_parity >/dev/null && usage | grep -c "parity <name>"', 'hcwa-test', $hcwa_repo . '/scripts/wp-local.sh' ) );
	hcwa_assert_same( array( 0, '1' ), array( $hcwa_code, trim( $hcwa_out ) ), 'wp-local.sh has the parity command and lists it' );
	hcwa_assert_contains( 'parity) cmd_parity "$@" ;;', (string) file_get_contents( $hcwa_repo . '/scripts/wp-local.sh' ), 'wp-local.sh dispatches parity' );
}

// wp-smoke.py: the port checks of 11-WP-PORT.md 5.7 on synthetic pages (no browser).
$hcwa_port_py = <<<'PY'
import importlib.util, json, re, sys
spec = importlib.util.spec_from_file_location("wp_smoke", sys.argv[1])
smoke = importlib.util.module_from_spec(spec)
spec.loader.exec_module(smoke)
H = "http://h"
DIST = H + "/wp-content/themes/hcwa-2026/assets/dist/assets/"
MODULE = DIST + "main-AAAAAAAA.js"
html = '<html><head><script>h.setAttribute("data-tier", 1)</script><script>addEventListener("pagereveal", f)</script></head><body><p>Trang chủ</p></body></html>'
good = {
    "tier": "1", "motion": "on", "lit": True, "bodyClass": ["page-home", "hcwa-lang-vi"],
    "bodyKids": ["a.skip-link", "div.proto-note", "div.header-slot.header-slot--over", "canvas#world.world-canvas", "main#main", "footer.site-footer", "dialog#menu-sheet.sheet"],
    "i18n": {k: "x" for k in smoke.I18N_KEYS}, "hasI18nGlobal": False,
    "langSwitch": [{"lang": "en", "href": H + "/en/"}], "menuLinks": [H + "/hoat-dong/"],
    "litHead": False, "litHeadDay": False, "endsOnDusk": True, "beats": 3, "beatsOutside": 0, "hasCenters": True,
    "centersTraces": True, "traces": 1,
    "protectedFacts": [{"sig": "li.bay#center-little-rose", "where": False, "visit": False, "spill": False, "door": "closed", "unitVar": True}],
    "chips": [{"unit": "little-rose", "door": True, "unitVar": True}], "nums": [{"text": "12", "anim": "none", "trans": "0s"}], "numsDCL": ["12"],
    "forms": 1, "partners": 0, "danglingHash": [], "emptyContact": 0, "modules": [MODULE],
    "runs": [{"text": "Trang chủ", "lang": "vi"}, {"text": "English version", "lang": "en"}],
}
requests = [{"url": DIST + "main-BBBBBBBB.css", "status": 200, "type": "stylesheet"}, {"url": MODULE, "status": 200, "type": "script"},
            {"url": H + "/wp-content/themes/hcwa-2026/assets/dist/posters/hero-desk-base-1600.avif", "status": 200, "type": "image"}]
guards = {"blockers": [{"re": re.compile(r">[^<]*\[[^\]<]{1,80}\][^<]*<"), "why": "bracket placeholder token in text"}, {"re": re.compile(r"data-draft"), "why": "draft text (data-draft)"}],
          "door": re.compile(r"hub-doors-wide"), "footnote": re.compile(r"^\[\d+\]$"), "minPartnerLogos": 4}
ctx = {"production": False, "lang": "vi", "expected_beats": 3, "protected": ["little-rose"], "listed_stills": ["passage-corridor"],
       "guards": guards, "dicts": {"en_words": {"Cầu", "Hàn"}, "phrases": ["Read more"]}, "traces_enabled": True,
       "link_status": lambda u: (200, "vi"), "chunk_imports": {DIST + "motion-CCCCCCCC.js": [MODULE]},
       "hreflang": [{"lang": "en", "href": H + "/en/"}, {"lang": "vi", "href": H + "/"}]}
t_vi = {"lang": "vi", "path": "/"}
t_en = {"lang": "en", "path": "/en/"}
def run(port=None, html_=html, req=None, **over):
    p = dict(good, **(port or {}))
    c = dict(ctx, **over)
    return smoke.port_checks(c.pop("target", t_vi), html_, p, req if req is not None else requests, c)
prod = {"production": True}
cases = {
    "good staging": run(),
    "no head scripts": run(html_="<html><head></head><body></body></html>"),
    "ver on dist": run(req=requests + [{"url": DIST + "main-BBBBBBBB.css?ver=1", "status": 200, "type": "stylesheet"}]),
    "chunk imports another URL": run(chunk_imports={DIST + "motion-CCCCCCCC.js": [MODULE + "?ver=7"]}),
    "body order": run({"bodyKids": ["main#main", "div.header-slot"]}),
    "lit missing": run({"lit": False}),
    "switch differs": run({"langSwitch": [{"lang": "en", "href": H + "/en/other/"}]}),
    "menu link 404": run(link_status=lambda u: (404, "vi")),
    "beat outside": run({"beatsOutside": 1, "beats": 4}),
    "no traces off production": run({"centersTraces": False}),
    "traces on production": run({"chips": [], "forms": 0}, **prod),
    "little rose location": run({"protectedFacts": [dict(good["protectedFacts"][0], where=True, spill=True)]}),
    "production door chip and still": run({"protectedFacts": [], "traces": 0, "forms": 0}, html_=html + '<img src="/posters/passage-corridor-1.avif">', **prod),
    "production no stills.json": run({"protectedFacts": [], "chips": [], "traces": 0, "forms": 0}, listed_stills=None, **prod),
    "production clean": run({"protectedFacts": [], "chips": [], "traces": 0, "forms": 0, "centersTraces": None}, html_=html.replace("</body>", "<p>Chú thích [1]</p><script>w[s]</script></body>"), **prod),
    "production bracket token": run({"protectedFacts": [], "chips": [], "traces": 0, "forms": 0}, html_=html.replace("</body>", "<p>Đã giúp [N] trẻ</p></body>"), **prod),
    "number counted up": run({"nums": [{"text": "12", "anim": "count", "trans": "0s"}], "numsDCL": ["0"]}),
    "english phrase on vi": run({"runs": [{"text": "Read more about us", "lang": "vi"}]}),
    "vi word on en": run({"runs": [{"text": "Trang chủ", "lang": "en"}]}, target=t_en, lang="en"),
    "proper name on en": run({"runs": [{"text": "Cầu Hàn Project", "lang": "en"}]}, target=t_en, lang="en", link_status=lambda u: (200, "en")),
    "broken poster": run(req=requests + [{"url": H + "/wp-content/themes/hcwa-2026/assets/dist/posters/x.avif", "status": 404, "type": "image"}]),
}
print(json.dumps({
    "cases": cases,
    "blockers": {
        "footnote passes": smoke.blocker_hits("<p>Chú thích [1] và [12]</p>", guards),
        "token fails": smoke.blocker_hits("<p>Đã giúp [N] trẻ</p>", guards),
        "script ignored": smoke.blocker_hits('<script>var w = x[s]; /^[012]$/.test(q)</script><style>a[x]{}</style><p>ok</p>', guards),
    },
    "probes": [p["path"] for p in smoke.gate_probes([], {"refused_photo": 40})],
    "probesSlug": [p for p in smoke.gate_probes([], {"refused_photo": 40, "refused_photo_file": "hcwa-20261109-v1lzeuoz"}) if "name" in p["id"] and "-feed-" not in p["id"]],
    "unpublished": smoke.unpublished_probes(["http://h/cau-chuyen-mau/", "/en/sample-story/?x=1"]),
    "kept": [t["path"] for t in smoke.without_unpublished([{"path": "/"}, {"path": "/cau-chuyen-mau/"}, {"path": "/en/sample-story/"}],
                                                            smoke.unpublished_probes(["http://h/cau-chuyen-mau/", "/en/sample-story/?x=1"]))],
}, ensure_ascii=False))
PY;
[ $hcwa_code, $hcwa_out, $hcwa_err ] = $hcwa_run( array( $hcwa_python, '-I', '-B', '-c', $hcwa_port_py, $hcwa_repo . '/scripts/wp-smoke.py' ) );
hcwa_assert_same( 0, $hcwa_code, 'wp-smoke.py port checks run without Playwright' . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
$hcwa_result = (array) json_decode( $hcwa_out, true );
$hcwa_port   = (array) ( $hcwa_result['cases'] ?? array() );
$hcwa_has    = static fn( string $case, string $text ): bool => (bool) array_filter( (array) ( $hcwa_port[ $case ] ?? array() ), static fn( $f ) => str_contains( $f, $text ) );
hcwa_assert_same( array(), $hcwa_port['good staging'] ?? null, 'port: a page as the spec describes passes' );
hcwa_assert_true( $hcwa_has( 'no head scripts', 'head-inline' ) && $hcwa_has( 'no head scripts', 'pageReveal' ), 'port: head-inline and pageReveal first in <head>' );
hcwa_assert_true( $hcwa_has( 'ver on dist', '?ver on assets/dist/' ), 'port: no ?ver on dist URLs' );
hcwa_assert_true( $hcwa_has( 'chunk imports another URL', 'chunks import the entry from another URL' ), 'port: chunks import the entry from the module tag URL' );
hcwa_assert_true( $hcwa_has( 'body order', 'out of the 2.2 order' ), 'port: body children in the 2.2 order' );
hcwa_assert_true( $hcwa_has( 'lit missing', 'data-lit missing' ), 'port: data-lit on lit pages' );
hcwa_assert_true( $hcwa_has( 'switch differs', 'hreflang says' ), 'port: language switch equals hreflang' );
hcwa_assert_true( $hcwa_has( 'menu link 404', 'menu links not answering 200' ), 'port: menu links answer 200' );
hcwa_assert_true( $hcwa_has( 'beat outside', 'outside section.bays-section#centers' ) && $hcwa_has( 'beat outside', '4 beats, expected 3' ), 'port: beats only in the corridor section, as many as centers' );
hcwa_assert_true( $hcwa_has( 'no traces off production', 'no data-traces on #centers' ), 'port: traces on #centers off production' );
hcwa_assert_true( $hcwa_has( 'traces on production', 'data-traces on production' ), 'port: never traces on production' );
hcwa_assert_true( $hcwa_has( 'little rose location', 'a location line' ) && $hcwa_has( 'little rose location', 'unit-colour --spill' ), 'port: no location or unit-colour spill on a protected center' );
hcwa_assert_true( $hcwa_has( 'production door chip and still', 'protected door chip' ) && $hcwa_has( 'production door chip and still', 'passage-corridor' ), 'port: production before approval: no door chip, no listed still' );
hcwa_assert_true( $hcwa_has( 'production no stills.json', 'stills.json is missing' ), 'port: production fails closed without stills.json' );
hcwa_assert_same( array(), $hcwa_port['production clean'] ?? null, 'port: production page with a footnote and a script bracket passes' );
hcwa_assert_true( $hcwa_has( 'production bracket token', 'bracket placeholder token' ), 'port: production BLOCKERS scan' );
hcwa_assert_true( $hcwa_has( 'number counted up', 'changed after DOMContentLoaded' ) && $hcwa_has( 'number counted up', 'animation or transition' ), 'port: impact numbers never count up' );
hcwa_assert_true( $hcwa_has( 'english phrase on vi', 'English phrases on a Vietnamese page' ), 'port: no English UI phrase on a VI page' );
hcwa_assert_true( $hcwa_has( 'vi word on en', 'Vietnamese words on an English page' ), 'port: no Vietnamese words on an EN page' );
hcwa_assert_same( array(), $hcwa_port['proper name on en'] ?? null, 'port: proper names from content/draft pass on EN pages' );
hcwa_assert_true( $hcwa_has( 'broken poster', 'did not answer 200' ), 'port: posters, svg, fonts and img answer 200' );
hcwa_assert_same( array(), $hcwa_result['blockers']['footnote passes'] ?? null, 'BLOCKERS: a digit-only bracket is a footnote' );
hcwa_assert_same( 1, count( (array) ( $hcwa_result['blockers']['token fails'] ?? array() ) ), 'BLOCKERS: [N] is a placeholder' );
hcwa_assert_same( array(), $hcwa_result['blockers']['script ignored'] ?? null, 'BLOCKERS: scripts and styles are removed first' );
hcwa_assert_same( array( '/wp-json/wp/v2/media', '/wp-json/wp/v2/MEDIA', '/wp-json/wp/v2/posts?_fields=id,yoast_head,yoast_head_json', '/wp-json/yoast/v1/get_head?url=%2F', '/post-sitemap.xml', '/?attachment_id=40', '/?p=40', '/?page_id=40', '/?post_type=attachment&p=40', '/?post_type=attachment&page_id=40', '/?post_type%5B%5D=attachment&p=40' ), array_values( array_filter( (array) ( $hcwa_result['probes'] ?? array() ), static fn( string $path ): bool => ! str_contains( $path, 'feed=' ) ) ), 'probes: the refused attachment through attachment_id, p and page_id, also with post_type=attachment (and post_type[])' );
hcwa_assert_same( array( '/?post_type=attachment&name=hcwa-20261109-v1lzeuoz', '/?attachment=hcwa-20261109-v1lzeuoz' ), array_column( (array) ( $hcwa_result['probesSlug'] ?? array() ), 'path' ), 'probes: and by its slug (post_type=attachment&name, attachment)' );
foreach ( (array) ( $hcwa_result['probesSlug'] ?? array() ) as $hcwa_probe ) {
	hcwa_assert_same( array( 404, true ), array( $hcwa_probe['status'] ?? null, $hcwa_probe['no_location'] ?? null ), 'probe ' . ( $hcwa_probe['id'] ?? '?' ) . ': 404 with no Location' );
}
hcwa_assert_same( array( '/cau-chuyen-mau/', '/en/sample-story/?x=1' ), array_column( (array) ( $hcwa_result['unpublished'] ?? array() ), 'path' ), 'probes: --unpublished URLs must answer 404' );
hcwa_assert_same( array( '/', '/en/sample-story/' ), $hcwa_result['kept'] ?? null, 'smoke: an unpublished URL is no longer a page to render (the path with a query string is a different URL)' );

// The exported guards come from guards.json once the export has written it, until then from
// prototype/src/render.mjs through node (which needs prototype/node_modules: render.mjs reaches
// three.js through the world objects), never re-typed.
$hcwa_guards_json = is_file( $hcwa_repo . '/wp/themes/hcwa-2026/assets/generated/guards.json' );
if ( ! $hcwa_guards_json && ( '' === $hcwa_which( 'node' ) || ! is_dir( $hcwa_repo . '/prototype/node_modules' ) ) ) {
	hcwa_test_skip( 'smoke load_guards(): no guards.json yet, and node or prototype/node_modules is missing (npm --prefix prototype ci)' );
} else {
	$hcwa_guard_py = 'import importlib.util, json, sys' . "\n"
		. 'spec = importlib.util.spec_from_file_location("wp_smoke", sys.argv[1]); smoke = importlib.util.module_from_spec(spec); spec.loader.exec_module(smoke)' . "\n"
		. 'g = smoke.load_guards(); print(json.dumps({"source": g["source"], "why": [b["why"] for b in g["blockers"]], "door": g["door"].pattern, "min": g["minPartnerLogos"]}))';
	[ $hcwa_code, $hcwa_out, $hcwa_err ] = $hcwa_run( array( $hcwa_python, '-I', '-B', '-c', $hcwa_guard_py, $hcwa_repo . '/scripts/wp-smoke.py' ) );
	$hcwa_guards = (array) json_decode( $hcwa_out, true );
	hcwa_assert_same( 0, $hcwa_code, 'smoke load_guards() runs' . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
	hcwa_assert_true( in_array( 'bracket placeholder token in text', (array) ( $hcwa_guards['why'] ?? array() ), true ), 'guards: the prototype BLOCKERS, bracket rule included' );
	hcwa_assert_contains( 'hub-doors-wide', (string) ( $hcwa_guards['door'] ?? '' ), 'guards: the prototype LITTLE_ROSE_DOOR pattern' );
	hcwa_assert_same( 4, $hcwa_guards['min'] ?? null, 'guards: MIN_PARTNER_LOGOS' );
}

// The parity driver's pure helpers (scripts/wp-parity/run.py), routes and allow lists.
$hcwa_parity_py = <<<'PY'
import importlib.util, json, sys
spec = importlib.util.spec_from_file_location("parity", sys.argv[1])
parity = importlib.util.module_from_spec(spec)
spec.loader.exec_module(parity)
parity.ALLOW = [{"route": "*", "section": "#news", "kind": "text", "match": r"^\(externalPost\)", "reason": "local posts", "owner": "templates"}]
proto = [{"ok": True, "text": "sun-in finished after ~2.3 s (2104 ms)", "key": parity.line_key("sun-in finished after ~2.3 s (2104 ms)")},
         {"ok": True, "text": "vi-390-t0: three.js not requested", "key": parity.line_key("vi-390-t0: three.js not requested")},
         {"ok": True, "text": "vi-390-t0: H1 2 lines", "key": parity.line_key("vi-390-t0: H1 2 lines")},
         {"ok": False, "text": "vi-390-t0: header text contrast 6.1 >= 7", "key": parity.line_key("vi-390-t0: header text contrast 6.1 >= 7")}]
wp = [{"ok": True, "text": "sun-in finished after ~2.5 s (2450 ms)", "key": parity.line_key("sun-in finished after ~2.5 s (2450 ms)")},
      {"ok": False, "text": "vi-390-t0: H1 3 lines", "key": parity.line_key("vi-390-t0: H1 3 lines")}]
cmp = parity.compare_lines(proto, wp)
d1 = parity.diff_lists(["a", "(externalPost)", "b"], ["a", "b"], "text", "/", "#news")
d2 = parity.diff_lists(["a", "x"], ["a", "y"], "text", "/", "#news")
routes = parity.ROUTES["routes"]
allow = {}
for owner in ("assets", "templates"):
    allow[owner] = json.load(open(str(parity.HERE / f"allow-{owner}.json")))
# Width and mode scopes (5.4): a 390-only pixel entry does not accept the same section at 1440.
parity.ALLOW = [{"route": "/en/", "section": "footer", "kind": "pixels", "match": "", "reason": "29 px shift at 390", "owner": "templates", "width": "390"},
                {"route": "*", "section": "#news", "kind": "text", "match": "^x$", "reason": "no-JS only", "owner": "templates", "mode": ["nojs"]}]
scoped = [len(parity.allowed("pixels", "/en/", "footer", "390", "reduced")), len(parity.allowed("pixels", "/en/", "footer", "1440", "reduced")),
          len(parity.allowed("pixels", "/en/", "footer")), parity.diff_lists(["x"], [], "text", "/", "#news", "390", "nojs")["pass"],
          parity.diff_lists(["x"], [], "text", "/", "#news", "390", "reduced")["pass"]]
good = {"route": "*", "section": "#news", "kind": "text", "match": "^a$", "reason": "why", "owner": "templates"}
bad = {
    "kind": dict(good, kind="colour"), "regex": dict(good, match="(unclosed"), "empty match": dict(good, match=""),
    "owner": dict(good, owner="assets"), "reason": dict(good, reason=" "), "route": dict(good, route="en/"),
    "section": dict(good, section=""), "width": dict(good, width="391"), "mode": dict(good, mode=["reduced", "paused"]),
    "extra key": dict(good, widths="390"),
}
shape = {"good": parity.allow_entry_problems(good, "templates"),
         "pixels empty match": parity.allow_entry_problems(dict(good, kind="pixels", match="", width=["390"], mode="reduced"), "templates"),
         "bad": {k: len(parity.allow_entry_problems(v, "templates")) for k, v in bad.items()}}
# Frame strips (5.5): positions anchored to section tops; hard lines (7.14) placed in sections.
pb = [{"key": "main>section#hero.hero", "top": 0, "height": 900}, {"key": "main>section#news.wall", "top": 900, "height": 600},
      {"key": "main>section#help.wall", "top": 1500, "height": 800}, {"key": "footer", "top": 2300, "height": 500}]
wb = [{"key": "main>section#hero.hero", "top": 0, "height": 900}, {"key": "main>section#news.wall", "top": 900, "height": 571},
      {"key": "main>section#help.wall", "top": 1471, "height": 800}, {"key": "footer", "top": 2271, "height": 500}]
mapped = parity.map_positions(pb, wb, [0, 450, 1000, 1499, 1600, 2300, 3000])
pl = parity.place_lines([{"y": 900}, {"y": 1450}, {"y": 1500}], pb)
wl = parity.place_lines([{"y": 900}, {"y": 1421}, {"y": 1471}, {"y": 1800}], wb)
lines = {"wpOnly": [l["y"] for l in parity.wp_only_lines(pl, wl)], "placed": [[l["section"], l["offset"], l["offsetEnd"]] for l in wl]}
# A viewport frame at the news/help seam: each section's band on both sides, WordPress rows taken at
# the same offsets into the section (news is 29 px shorter there, so help starts 29 px higher).
bands = [[b["section"], b["rowsProto"], b["rowsWp"]] for b in parity.frame_bands(1200, 1171, 844, pb, wb)]
print(json.dumps({
    "mapped": mapped, "lines": lines, "bands": bands,
    "key": parity.line_key("vi-1440-t2: drawing buffers ~33.4 MB <= 48 MB (D6.7)"),
    "missing": [r["text"] for r in cmp["missing"]], "only": cmp["prototypeOnly"], "protoFails": cmp["protoFails"],
    "allowed": [d1["pass"], d1["allowed"]], "plain": [d2["pass"], d2["changes"]],
    "routes": len(routes), "paths": sorted({r["path"] for r in routes}), "widths": sorted(parity.ROUTES["widths"]),
    "norm": parity.norm_url("http://h/wp-content/themes/hcwa-2026/assets/dist/assets/main-BwCzEVtk.js?ver=1"),
    "allowCounts": {k: len(v.get("entries", [])) for k, v in allow.items()},
    "allowProblems": parity.allow_problems(),
    "scoped": scoped, "shape": shape,
}))
PY;
[ $hcwa_code, $hcwa_out, $hcwa_err ] = $hcwa_run( array( $hcwa_python, '-I', '-B', '-c', $hcwa_parity_py, $hcwa_repo . '/scripts/wp-parity/run.py' ) );
hcwa_assert_same( 0, $hcwa_code, 'wp-parity/run.py helpers load without Playwright' . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
$hcwa_parity = (array) json_decode( $hcwa_out, true );
hcwa_assert_same( 'vi-1440-t2: drawing buffers ~# MB <= # MB', $hcwa_parity['key'] ?? null, 'parity: a check line matches across sides without its numbers' );
hcwa_assert_same( array( 'vi-390-t0: H1 2 lines' ), $hcwa_parity['missing'] ?? null, 'parity: a prototype PASS line failing on WordPress is reported, a timing value is not' );
hcwa_assert_same( array( 'vi-390-t0: three.js not requested' ), $hcwa_parity['only'] ?? null, "parity: check-hero's three.js lines are prototype-only" );
hcwa_assert_same( array( 'vi-390-t0: header text contrast 6.1 >= 7' ), $hcwa_parity['protoFails'] ?? null, 'parity: prototype FAIL lines are listed, not required' );
hcwa_assert_same( array( true, 1 ), $hcwa_parity['allowed'] ?? null, 'parity: an allow entry drops the line it matches' );
hcwa_assert_same( array( false, 2 ), $hcwa_parity['plain'] ?? null, 'parity: other differences stay' );
hcwa_assert_same( 16, $hcwa_parity['routes'] ?? null, 'parity: 16 routes' );
hcwa_assert_same( array( 0, 450, 1000, 1470, 1571, 2271, 3000 ), $hcwa_parity['mapped'] ?? null, 'parity strips: WordPress frames sit at the same offset into the same section, capped at a shorter section' );
hcwa_assert_same( array( array( 'main>section#news.wall', array( 0, 300 ), array( 29, 300 ) ), array( 'main>section#help.wall', array( 300, 844 ), array( 300, 844 ) ) ), $hcwa_parity['bands'] ?? null, 'parity strips: a frame is compared section by section, each band at the same offsets into its section' );
hcwa_assert_same( array( 1800 ), $hcwa_parity['lines']['wpOnly'] ?? null, 'parity 7.14: a hard line only WordPress has is reported; lines at the same offset from the section top or bottom are not' );
hcwa_assert_same( array( array( 'main>section#news.wall', 0, 571 ), array( 'main>section#news.wall', 521, 50 ), array( 'main>section#help.wall', 0, 800 ), array( 'main>section#help.wall', 329, 471 ) ), $hcwa_parity['lines']['placed'] ?? null, 'parity 7.14: lines placed by section, offset and distance to the section end' );
hcwa_assert_same( array( '1440', '390' ), $hcwa_parity['widths'] ?? null, 'parity: 390 and 1440' );
hcwa_assert_true( in_array( '/en/404.html', (array) ( $hcwa_parity['paths'] ?? array() ), true ) && in_array( '/hoat-dong/co-so/tre-xanh/', (array) ( $hcwa_parity['paths'] ?? array() ), true ), 'parity: the 404 and Green Bamboo routes' );
hcwa_assert_same( '/assets/main.js', $hcwa_parity['norm'] ?? null, 'parity: dist URLs compare as the prototype names them' );
// 5.4: the streams fill their allow lists with reviewed entries; each must have the right shape.
hcwa_assert_true( isset( $hcwa_parity['allowCounts']['assets'], $hcwa_parity['allowCounts']['templates'] ), 'parity: both allow lists load (allow-assets.json, allow-templates.json)' );
hcwa_assert_same( array(), $hcwa_parity['allowProblems'] ?? null, 'parity: every allow entry has route, section, a known kind, a match that compiles, a reason and the owner of its file' );
hcwa_assert_same( array( 1, 0, 0, true, false ), $hcwa_parity['scoped'] ?? null, 'parity: an allow entry scoped to a width or mode applies only there (and never to an unscoped comparison)' );
hcwa_assert_same( array(), $hcwa_parity['shape']['good'] ?? null, 'parity: a well-formed allow entry has no problems' );
hcwa_assert_same( array(), $hcwa_parity['shape']['pixels empty match'] ?? null, 'parity: a pixels entry may have an empty match and width/mode scopes' );
foreach ( (array) ( $hcwa_parity['shape']['bad'] ?? array() ) as $hcwa_case => $hcwa_n ) {
	hcwa_assert_same( 1, $hcwa_n, "parity: an allow entry with a bad $hcwa_case is reported" );
}
hcwa_assert_same( 10, count( (array) ( $hcwa_parity['shape']['bad'] ?? array() ) ), 'parity: ten malformed allow-entry cases' );
if ( '' !== $hcwa_which( 'node' ) ) {
	foreach ( array( 'skeleton.js', 'gzip-proxy.mjs', 'lcp.cjs' ) as $hcwa_file ) {
		[ $hcwa_code, , $hcwa_err ] = $hcwa_run( array( $hcwa_which( 'node' ), '--check', $hcwa_repo . '/scripts/wp-parity/' . $hcwa_file ) );
		hcwa_assert_same( 0, $hcwa_code, "wp-parity/$hcwa_file parses" . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
	}
}

// Critic round 2: the media-library images WordPress serves blurred are left out of the pixel
// count (with the prototype image of the same class), and nothing else is; and the head
// description and og tags are compared with the prototype's.
$hcwa_mask_py = <<<'PY'
import importlib.util, json, sys, tempfile
from pathlib import Path
spec = importlib.util.spec_from_file_location("parity", sys.argv[1])
parity = importlib.util.module_from_spec(spec)
spec.loader.exec_module(parity)
out = {}
proto_imgs = [{"src": "http://h/img/tre-xanh-logo.png", "cls": "lit-head__logo", "x": 16.0, "y": 221.4, "w": 56, "h": 56},
              {"src": "http://h/posters/hdr-green-bamboo-390.avif", "cls": "lit-head__still", "x": 0, "y": 0, "w": 390, "h": 300}]
wp_imgs = [{"src": "http://h/wp-content/uploads/2026/11/tre-xanh-logo.png", "cls": "lit-head__logo", "x": 16.0, "y": 221.4, "w": 56, "h": 56},
           {"src": "http://h/wp-content/themes/hcwa-2026/assets/dist/posters/hdr-green-bamboo-390.avif", "cls": "lit-head__still", "x": 0, "y": 0, "w": 390, "h": 300},
           {"src": "http://h/wp-content/uploads/2026/11/photo.webp", "cls": "", "x": 200, "y": 10, "w": 20, "h": 10}]
out["boxes"] = parity.mask_boxes(proto_imgs, wp_imgs)
out["none"] = parity.mask_boxes(proto_imgs, [wp_imgs[1]])
if parity.Image is None:
    out["pixels"] = None
else:
    Image = parity.Image
    tmp = Path(tempfile.mkdtemp())
    a = Image.new("RGB", (100, 100), (250, 248, 240))
    b = a.copy()
    b.paste((20, 20, 20), (10, 10, 30, 30))      # 400 px inside the logo box: 4% of the section
    a.save(tmp / "a.png"); b.save(tmp / "b.png")
    c = b.copy()
    c.paste((20, 20, 20), (60, 60, 75, 70))      # 150 px outside it
    c.save(tmp / "c.png")
    box = [[9, 9, 22, 22]]
    run = lambda x, y, m=None: parity.compare_pixels(str(tmp / x), str(tmp / y), tmp / "diff.png", "/x/", "main>header.lit-head", "390", "reduced", m)
    r1, r2, r3 = run("a.png", "b.png"), run("a.png", "b.png", box), run("a.png", "c.png", box)
    out["pixels"] = {"unmasked": [r1["pass"], r1["share"]], "masked": [r2["pass"], r2["share"], r2.get("maskedShare")],
                     "outside": [r3["pass"], r3["share"]], "diffMap": Path(r3.get("diffMap", "")).is_file()}
proto_doc = '<html><head><title>Our Work | HCWA</title><meta name="description" content="HCWA runs long-term centers."><meta property="og:title" content="Our Work | HCWA"><meta property="og:description" content="HCWA runs long-term centers."><meta property="og:locale" content="en_GB"></head><body></body></html>'
same = "<html><head><meta name='description' content='HCWA runs long-term centers.'>\n<meta property=\"og:title\" content=\"Our Work | HCWA\">\n<meta property=\"og:description\" content=\"HCWA runs long-term centers.\">\n<meta property=\"og:locale\" content=\"en_GB\"></head></html>"
draft = '<html><head><meta name="description" content="[DRAFT: A voluntary, non-profit social organization]"></head></html>'
out["meta"] = {"same": parity.head_meta_diff(proto_doc, same), "draft": parity.head_meta_diff(proto_doc, draft),
               "parsed": parity.head_meta(proto_doc), "bodyIgnored": parity.head_meta('<head></head><meta name="description" content="x">')}
print(json.dumps(out))
PY;
[ $hcwa_code, $hcwa_out, $hcwa_err ] = $hcwa_run( array( $hcwa_python, '-I', '-B', '-c', $hcwa_mask_py, $hcwa_repo . '/scripts/wp-parity/run.py' ) );
hcwa_assert_same( 0, $hcwa_code, 'parity masks and head meta run' . ( '' !== $hcwa_err ? ': ' . trim( $hcwa_err ) : '' ) );
$hcwa_mask = (array) json_decode( $hcwa_out, true );
hcwa_assert_same( array( array( 15, 220, 58, 59 ), array( 199, 9, 22, 12 ) ), $hcwa_mask['boxes'] ?? null, 'parity masks: the uploaded logo (padded 1 px, same box on the prototype) and an uploaded photo; theme stills are not masked' );
hcwa_assert_same( array(), $hcwa_mask['none'] ?? null, 'parity masks: no uploaded image, no mask' );
if ( null === ( $hcwa_mask['pixels'] ?? null ) ) {
	hcwa_test_skip( 'parity pixel masks: Pillow is not installed' );
} else {
	hcwa_assert_same( array( false, 4.0 ), $hcwa_mask['pixels']['unmasked'] ?? null, 'parity pixels: a 400 px difference fails a 100x100 section unmasked (4%)' );
	hcwa_assert_same( array( true, 0.0, 4.84 ), $hcwa_mask['pixels']['masked'] ?? null, 'parity pixels: the same difference inside a masked box does not count' );
	hcwa_assert_same( array( false, 1.58 ), $hcwa_mask['pixels']['outside'] ?? null, 'parity pixels: a difference outside the masked box still fails (150 px over the unmasked area)' );
	hcwa_assert_true( (bool) ( $hcwa_mask['pixels']['diffMap'] ?? false ), 'parity pixels: a failing section still writes its diff map' );
}
hcwa_assert_same( array(), $hcwa_mask['meta']['same'] ?? null, 'parity head meta: the same tags in either quote style match' );
hcwa_assert_same(
	array( "description: '[DRAFT: A voluntary, non-profit social organization]' (prototype 'HCWA runs long-term centers.')", "og:title: None (prototype 'Our Work | HCWA')", "og:description: None (prototype 'HCWA runs long-term centers.')", "og:locale: None (prototype 'en_GB')" ),
	$hcwa_mask['meta']['draft'] ?? null,
	'parity head meta: a draft tagline description and missing og tags are reported'
);
hcwa_assert_same( array( 'description' => 'HCWA runs long-term centers.', 'og:title' => 'Our Work | HCWA', 'og:description' => 'HCWA runs long-term centers.', 'og:locale' => 'en_GB' ), $hcwa_mask['meta']['parsed'] ?? null, 'parity head meta: the four tags read from <head>' );
hcwa_assert_same( array(), $hcwa_mask['meta']['bodyIgnored'] ?? null, 'parity head meta: tags after </head> are not read' );

// The static check's port checks on fixture inputs (11-WP-PORT.md 6.1).
[ $hcwa_code, $hcwa_out ] = $hcwa_run( array( $hcwa_python, '-I', '-B', $hcwa_repo . '/scripts/wp-static-check.py', '--self-test' ) );
hcwa_assert_same( 0, $hcwa_code, 'wp-static-check.py --self-test passes: ' . trim( (string) preg_replace( '/^ok .*\n/m', '', $hcwa_out ) ) );
