<?php
/**
 * hcwa-2026 block patterns: static checks that need no WordPress.
 *
 * Headers as WP_Theme::get_block_patterns() reads them, VI/EN pairs, balanced block
 * comments with valid JSON, structure locks, hcwa/* attributes from the contract, one H1
 * per page body, links in the pattern's own language, and the safety rules (no image
 * sources, no data-draft, draft text wrapped in [DRAFT: …]).
 *
 * @package hcwa-2026
 */

$hcwa_pattern_dir   = dirname( __DIR__ ) . '/themes/hcwa-2026/patterns';
$hcwa_pattern_files = glob( $hcwa_pattern_dir . '/*.php' );
sort( $hcwa_pattern_files );

$hcwa_pattern_names = array( 'hero-split', 'focus-areas', 'cta-band', 'ways-to-help', 'faq', 'contact-band', 'page-header', 'page-home', 'page-our-work', 'page-get-involved', 'page-donate', 'page-contact' );

// Contract: block attributes patterns may set (name => type or list of allowed values).
$hcwa_block_attrs = array(
	'impact-strip'     => array(
		'scopeType' => array( 'organization', 'center', 'program' ),
		'scopeId'   => 'int',
		'limit'     => 'int',
		'showLink'  => 'bool',
	),
	'center-grid'      => array( 'limit' => 'int' ),
	'program-list'     => array(
		'status'   => array( 'any', 'active', 'completed' ),
		'limit'    => 'int',
		'centerId' => 'int',
	),
	'report-list'      => array(
		'reportType' => 'string',
		'limit'      => 'int',
	),
	'partner-wall'     => array(
		'homeOnly' => 'bool',
		'limit'    => 'int',
	),
	'news-latest'      => array(
		'featured' => 'bool',
		'limit'    => 'int',
	),
	'language-notice'  => array( 'forceLang' => array( '', 'vi', 'en' ) ),
	'donation-methods' => array(),
	'announcement-bar' => array(),
);

// Every hcwa/* block may print nothing on production (contract "Markup and CSS"), so in a
// pattern each one sits inside a wrapper that patterns.css hides when the block printed
// nothing, taking the section heading with it: block => [wrapper class, what the rule's
// :has() looks for].
$hcwa_empty_hides = array(
	'impact-strip'     => array( 'hcwa-impact-slot', '*' ),
	'partner-wall'     => array( 'hcwa-partners', '.hcwa-partner-slot > *' ),
	'center-grid'      => array( 'hcwa-block-section', '.wp-block-hcwa-center-grid' ),
	'program-list'     => array( 'hcwa-block-section', '.wp-block-hcwa-program-list' ),
	'news-latest'      => array( 'hcwa-block-section', '.wp-block-hcwa-news-latest' ),
	'donation-methods' => array( 'donate-methods', '.wp-block-hcwa-donation-methods' ),
);

foreach ( $hcwa_pattern_names as $hcwa_name ) {
	foreach ( array( 'vi', 'en' ) as $hcwa_lang ) {
		hcwa_assert_true( is_file( "$hcwa_pattern_dir/$hcwa_name-$hcwa_lang.php" ), "$hcwa_name-$hcwa_lang.php exists" );
	}
}

/**
 * Header fields, read the way get_file_data() does (case-insensitive "Name: value" lines).
 *
 * @param string $source File source.
 * @return array<string, string>
 */
function hcwa_test_pattern_headers( string $source ): array {
	$out = array();
	foreach ( array( 'Title', 'Slug', 'Description', 'Viewport Width', 'Inserter', 'Categories', 'Keywords', 'Block Types', 'Post Types', 'Template Types' ) as $field ) {
		if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $field, '/' ) . ':(.*)$/mi', $source, $m ) ) {
			$out[ $field ] = trim( $m[1] );
		}
	}
	return $out;
}

$hcwa_structures = array();

foreach ( $hcwa_pattern_files as $hcwa_file ) {
	$hcwa_base = basename( $hcwa_file, '.php' );
	if ( ! preg_match( '/^(.+)-(vi|en)$/', $hcwa_base, $hcwa_m ) ) {
		hcwa_assert_true( false, "$hcwa_base: file name ends in -vi or -en" );
		continue;
	}
	list( , $hcwa_name, $hcwa_lang ) = $hcwa_m;
	$hcwa_is_page = 0 === strpos( $hcwa_name, 'page-' ) && 'page-header' !== $hcwa_name;
	$hcwa_source  = (string) file_get_contents( $hcwa_file );
	$hcwa_head    = hcwa_test_pattern_headers( $hcwa_source );

	hcwa_assert_same( "hcwa-2026/$hcwa_base", $hcwa_head['Slug'] ?? '', "$hcwa_base: slug" );
	hcwa_assert_same( 'hcwa', $hcwa_head['Categories'] ?? '', "$hcwa_base: category" );
	hcwa_assert_same( 'page', $hcwa_head['Post Types'] ?? '', "$hcwa_base: page bodies only" );
	hcwa_assert_true( ctype_digit( $hcwa_head['Viewport Width'] ?? '' ), "$hcwa_base: viewport width" );
	hcwa_assert_contains( 'vi' === $hcwa_lang ? '(Tiếng Việt)' : '(English)', $hcwa_head['Title'] ?? '', "$hcwa_base: title names the language" );
	hcwa_assert_true( '' !== ( $hcwa_head['Keywords'] ?? '' ), "$hcwa_base: keywords" );
	// Inserter: no would also drop the compositions from the "Choose a pattern" modal for new
	// pages: the editor builds that list from the inserter-visible patterns only.
	hcwa_assert_false( isset( $hcwa_head['Inserter'] ), "$hcwa_base: no Inserter header" );
	hcwa_assert_same( $hcwa_is_page ? 'core/post-content' : '', $hcwa_head['Block Types'] ?? '', "$hcwa_base: compositions are offered for new pages" );

	ob_start();
	include $hcwa_file;
	$hcwa_html = (string) ob_get_clean();

	// Block comments: balanced, JSON parses, escaped like serializeAttributes().
	preg_match_all( '/<!--\s+(\/)?wp:([a-z][a-z0-9_-]*\/)?([a-z][a-z0-9_-]*)\s+(\{.*?\}\s+)?(\/)?-->/s', $hcwa_html, $hcwa_blocks, PREG_SET_ORDER );
	$hcwa_stack     = array();
	$hcwa_classes   = array(); // className of each open block, parallel to $hcwa_stack.
	$hcwa_hides_ok  = true;
	$hcwa_balanced  = true;
	$hcwa_json_ok   = true;
	$hcwa_top_ok    = true;
	$hcwa_attrs_ok  = true;
	$hcwa_structure = array();
	foreach ( $hcwa_blocks as $hcwa_block ) {
		$hcwa_closer = '/' === $hcwa_block[1];
		$hcwa_bname  = ( '' !== $hcwa_block[2] ? $hcwa_block[2] : 'core/' ) . $hcwa_block[3];
		$hcwa_void   = '/' === ( $hcwa_block[5] ?? '' );
		if ( $hcwa_closer ) {
			$hcwa_balanced = $hcwa_balanced && array_pop( $hcwa_stack ) === $hcwa_bname;
			array_pop( $hcwa_classes );
			continue;
		}
		$hcwa_raw   = trim( $hcwa_block[4] ?? '' );
		$hcwa_attrs = array();
		if ( '' !== $hcwa_raw ) {
			$hcwa_attrs   = json_decode( $hcwa_raw, true );
			$hcwa_json_ok = $hcwa_json_ok && is_array( $hcwa_attrs ) && ! preg_match( '/--|[<>&]/', $hcwa_raw );
			$hcwa_attrs   = is_array( $hcwa_attrs ) ? $hcwa_attrs : array();
		}
		if ( ! $hcwa_stack ) {
			$hcwa_top_ok = $hcwa_top_ok && 'core/group' === $hcwa_bname && 'contentOnly' === ( $hcwa_attrs['templateLock'] ?? '' ) && 'full' === ( $hcwa_attrs['align'] ?? '' );
		}
		if ( 0 === strpos( $hcwa_bname, 'hcwa/' ) ) {
			$hcwa_short   = substr( $hcwa_bname, 5 );
			$hcwa_allowed = $hcwa_block_attrs[ $hcwa_short ] ?? null;
			$hcwa_ok      = null !== $hcwa_allowed && $hcwa_void;
			foreach ( $hcwa_attrs as $hcwa_key => $hcwa_value ) {
				$hcwa_type = $hcwa_allowed[ $hcwa_key ] ?? null;
				if ( is_array( $hcwa_type ) ) {
					$hcwa_ok = $hcwa_ok && in_array( $hcwa_value, $hcwa_type, true );
				} elseif ( 'int' === $hcwa_type ) {
					$hcwa_ok = $hcwa_ok && is_int( $hcwa_value );
				} elseif ( 'bool' === $hcwa_type ) {
					$hcwa_ok = $hcwa_ok && is_bool( $hcwa_value );
				} elseif ( 'string' === $hcwa_type ) {
					$hcwa_ok = $hcwa_ok && is_string( $hcwa_value );
				} else {
					$hcwa_ok = false;
				}
			}
			$hcwa_attrs_ok = $hcwa_attrs_ok && $hcwa_ok;

			$hcwa_wrapper  = $hcwa_empty_hides[ $hcwa_short ][0] ?? null;
			$hcwa_hides_ok = $hcwa_hides_ok && null !== $hcwa_wrapper
				&& in_array( $hcwa_wrapper, preg_split( '/\s+/', implode( ' ', $hcwa_classes ) ), true );
		}
		// Structure shared by the VI and EN files: block names, classes, tags, locks.
		$hcwa_structure[] = $hcwa_bname . '|' . ( $hcwa_attrs['className'] ?? '' ) . '|' . ( $hcwa_attrs['tagName'] ?? '' ) . '|' . ( $hcwa_attrs['level'] ?? '' ) . '|' . ( $hcwa_attrs['templateLock'] ?? '' );
		if ( ! $hcwa_void ) {
			$hcwa_stack[]   = $hcwa_bname;
			$hcwa_classes[] = (string) ( $hcwa_attrs['className'] ?? '' );
		}
	}
	$hcwa_structures[ $hcwa_name ][ $hcwa_lang ] = $hcwa_structure;

	hcwa_assert_true( $hcwa_blocks && $hcwa_balanced && ! $hcwa_stack, "$hcwa_base: every block opener has its closer" );
	hcwa_assert_true( $hcwa_json_ok, "$hcwa_base: block attributes are valid, escaped JSON" );
	hcwa_assert_true( $hcwa_top_ok, "$hcwa_base: top-level groups are full width and contentOnly-locked" );
	hcwa_assert_true( $hcwa_attrs_ok, "$hcwa_base: hcwa/* blocks are self-closing with contract attributes only" );
	hcwa_assert_true( $hcwa_hides_ok, "$hcwa_base: each hcwa/* block sits in a wrapper that hides when it prints nothing" );

	hcwa_assert_same( ( $hcwa_is_page || 'page-header' === $hcwa_name ) ? 1 : 0, substr_count( $hcwa_html, '<h1' ), "$hcwa_base: H1 count (one per page body, none in sections)" );
	hcwa_assert_false( (bool) preg_match( '/<img[^>]*\ssrc=/', $hcwa_html ), "$hcwa_base: image slots carry no src (no stock photos)" );
	hcwa_assert_false( false !== strpos( $hcwa_html, 'data-draft' ), "$hcwa_base: no data-draft attribute (invalid on core blocks)" );
	hcwa_assert_false( (bool) preg_match( '/\[DRAFT(?!: )/', $hcwa_html ), "$hcwa_base: draft markers read [DRAFT: …]" );
	if ( 'cta-band' !== $hcwa_name ) {
		hcwa_assert_contains( '[DRAFT: ', $hcwa_html, "$hcwa_base: draft copy is marked" );
	}
	if ( function_exists( 'hcwa_contains_draft' ) ) {
		// Production refuses to publish a page built from a pattern until its drafts are replaced.
		hcwa_assert_same( 'cta-band' !== $hcwa_name, hcwa_contains_draft( $hcwa_html ), "$hcwa_base: the publish guard sees the draft copy" );
	}

	preg_match_all( '/href="(\/[^"]*)"/', $hcwa_html, $hcwa_links );
	$hcwa_wrong = array_filter(
		$hcwa_links[1],
		static fn( $href ) => ( 'en' === $hcwa_lang ) !== ( 0 === strpos( $href, '/en/' ) )
	);
	hcwa_assert_same( array(), array_values( $hcwa_wrong ), "$hcwa_base: internal links stay in the pattern's language" );
}

foreach ( $hcwa_structures as $hcwa_name => $hcwa_by_lang ) {
	hcwa_assert_same( $hcwa_by_lang['vi'] ?? null, $hcwa_by_lang['en'] ?? null, "$hcwa_name: VI and EN share one block structure" );
}

$hcwa_css = (string) file_get_contents( dirname( __DIR__ ) . '/themes/hcwa-2026/assets/css/patterns.css' );
hcwa_assert_true( '' !== $hcwa_css, 'patterns.css exists' );
hcwa_assert_false( (bool) preg_match( '/text-transform\s*:\s*uppercase/i', $hcwa_css ), 'patterns.css never uppercases (Vietnamese diacritics)' );
hcwa_assert_same( substr_count( $hcwa_css, '{' ), substr_count( $hcwa_css, '}' ), 'patterns.css braces balance' );

// The wrapper rules: ".wrapper:not(:has(…)) { display: none; }", whose :has() names the block.
preg_match_all( '/^\.([\w-]+):not\(:has\((.+)\)\)\s*\{\s*display:\s*none;\s*\}/m', $hcwa_css, $hcwa_hide_rules, PREG_SET_ORDER );
foreach ( $hcwa_empty_hides as $hcwa_short => list( $hcwa_wrapper, $hcwa_needle ) ) {
	$hcwa_found = array_filter(
		$hcwa_hide_rules,
		static fn( $rule ) => $hcwa_wrapper === $rule[1] && false !== strpos( $rule[2], $hcwa_needle )
	);
	hcwa_assert_true( (bool) $hcwa_found, "patterns.css hides .$hcwa_wrapper when hcwa/$hcwa_short printed nothing" );
}
