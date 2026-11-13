<?php
/**
 * hcwa/* blocks: static checks on the block folders. block.json against the contract
 * (wp/CONTRACT.md "Names"), the asset files, render.php safety, and the strings: every
 * front-end key exists in both languages, every editor label has a Vietnamese translation.
 *
 * @package hcwa-core
 */

$hcwa_blocks_dir = dirname( __DIR__ ) . '/plugins/hcwa-core/blocks';

// Contract (docs/plan/11-WP-PORT.md 4.1): block => attribute => [type, default, enum or null, role].
// Text attributes carry "role": "content" so a contentOnly container keeps them editable (4.2).
$hcwa_text = static fn(): array => array( 'string', '', null, 'content' );
$hcwa_list = static fn(): array => array( 'array', array(), null, 'content' );
$hcwa_contract = array(
	'sections'         => array(
		'templateLock' => array( 'string', 'contentOnly', array( 'all', 'contentOnly' ), null ),
	),
	'day'              => array(
		'variant'      => array( 'string', 'involve', array( 'hub', 'involve', 'donate', 'contact', 'gap' ), null ),
		'templateLock' => array( 'string', 'contentOnly', array( 'all', 'contentOnly' ), null ),
	),
	'lit-header'       => array(
		'still'   => array( 'string', '', null, null ),
		'size'    => array( 'string', '', array( '', 'display', 'program' ), null ),
		'title'   => $hcwa_text(),
		'intro'   => $hcwa_text(),
		'actions' => array( 'string', '', array( '', 'reach' ), null ),
		'callUs'  => $hcwa_text(),
		'writeUs' => $hcwa_text(),
	),
	'involve-head'     => array(
		'title'   => $hcwa_text(),
		'intro'   => $hcwa_text(),
		'seeWays' => $hcwa_text(),
	),
	'sun-head'         => array(
		'intro'      => $hcwa_text(),
		'go'         => $hcwa_text(),
		'jumpAbroad' => $hcwa_text(),
		'jumpTell'   => $hcwa_text(),
	),
	'dusk-band'        => array(
		'anchor'  => array( 'string', '', null, null ),
		'titleId' => array( 'string', '', null, null ),
		'title'   => $hcwa_text(),
		'lines'   => array( 'array', array(), null, null ),
		'actions' => array( 'array', array(), null, null ),
		'note'    => $hcwa_text(),
	),
	'hero'             => array(
		'title' => $hcwa_text(),
		'intro' => $hcwa_text(),
	),
	'passage'          => array(
		'title'     => $hcwa_text(),
		'statement' => $hcwa_text(),
	),
	'corridor'         => array(
		'intro' => $hcwa_text(),
	),
	'work-sheet'       => array(
		'focusTitle' => $hcwa_text(),
	),
	'shutters'         => array(),
	'help'             => array(
		'title' => $hcwa_text(),
		'ways'  => $hcwa_list(),
	),
	'work-hub'         => array(
		'centersLead'  => $hcwa_text(),
		'programsLead' => $hcwa_text(),
	),
	'louvers'          => array(
		'ways' => $hcwa_list(),
	),
	'ways'             => array(
		'title' => $hcwa_text(),
		'ways'  => $hcwa_list(),
	),
	'faq'              => array(
		'title'  => $hcwa_text(),
		'faq'    => $hcwa_list(),
		'answer' => $hcwa_text(),
	),
	'gifts'            => array(
		'title' => $hcwa_text(),
		'gifts' => $hcwa_list(),
	),
	'contact-reach'    => array(
		'formTitle'   => $hcwa_text(),
		'officeTitle' => $hcwa_text(),
		'chat'        => $hcwa_text(),
		'chatPending' => $hcwa_text(),
	),
	'contact-units'    => array(
		'littleRose'    => $hcwa_text(),
		'centerPending' => $hcwa_text(),
	),
	'news-latest'      => array(
		'featured' => array( 'boolean', true, null, null ),
		'limit'    => array( 'integer', 3, null, null ),
	),
	'partner-wall'     => array(
		'title'    => $hcwa_text(),
		'homeOnly' => array( 'boolean', true, null, null ),
		'limit'    => array( 'integer', 12, null, null ),
	),
	'donation-methods' => array_merge(
		array_fill_keys( array( 'howTitle', 'tabsLabel', 'inVietnam', 'inVietnamShort', 'fromAbroad', 'fromAbroadShort', 'qr', 'pendingNote', 'paypal', 'toCenterTitle', 'toCenter', 'tellTitle', 'tellLead', 'amount', 'amountError', 'giftFor', 'giftForAny', 'thanks' ), $hcwa_text() ),
		array(
			'bankFields'   => $hcwa_list(),
			'abroadFields' => $hcwa_list(),
		)
	),
	'impact-strip'     => array(
		'scopeType' => array( 'string', 'organization', array( 'organization', 'center', 'program' ), null ),
		'scopeId'   => array( 'integer', 0, null, null ),
		'limit'     => array( 'integer', 4, null, null ),
		'showLink'  => array( 'boolean', true, null, null ),
		'title'     => $hcwa_text(),
	),
	'program-list'     => array(
		'status'   => array( 'string', 'any', array( 'any', 'active', 'completed' ), null ),
		'limit'    => array( 'integer', 4, null, null ),
		'centerId' => array( 'integer', 0, null, null ),
	),
	'report-list'      => array(
		'reportType' => array( 'string', 'any', array( 'any', 'annual', 'financial', 'program', 'policy' ), null ),
		'limit'      => array( 'integer', 6, null, null ),
		'programId'  => array( 'integer', 0, null, null ),
	),
	'announcement-bar' => array(),
	'language-notice'  => array(
		'forceLang' => array( 'string', '', array( '', 'vi', 'en' ), null ),
	),
);
// center-grid is gone (4.1: replaced by hcwa/corridor and hcwa/work-hub).
hcwa_assert_false( is_dir( "$hcwa_blocks_dir/center-grid" ), 'hcwa/center-grid is deleted' );

$hcwa_names = array_keys( $hcwa_contract );
sort( $hcwa_names );
hcwa_assert_same( $hcwa_names, hcwa_blocks_names(), 'exactly the contract blocks are registered' );

// blocks-manifest.php (what WordPress registers from) is exactly the block.json files.
// After changing a block.json: php wp/tests/blocks-manifest-build.php
$hcwa_manifest = require "$hcwa_blocks_dir/blocks-manifest.php";
$hcwa_folders  = array();
foreach ( glob( "$hcwa_blocks_dir/*/block.json" ) as $hcwa_file ) {
	$hcwa_folders[ basename( dirname( $hcwa_file ) ) ] = json_decode( (string) file_get_contents( $hcwa_file ), true );
}
hcwa_assert_true( is_array( $hcwa_manifest ), 'manifest returns an array' );
$hcwa_manifest = is_array( $hcwa_manifest ) ? $hcwa_manifest : array();
ksort( $hcwa_manifest );
ksort( $hcwa_folders );
hcwa_assert_same( array_keys( $hcwa_folders ), array_keys( $hcwa_manifest ), 'manifest: one entry per block folder (run php wp/tests/blocks-manifest-build.php)' );
foreach ( $hcwa_folders as $hcwa_name => $hcwa_meta ) {
	hcwa_assert_same( $hcwa_meta, $hcwa_manifest[ $hcwa_name ] ?? null, "manifest: $hcwa_name matches its block.json (run php wp/tests/blocks-manifest-build.php)" );
}
$hcwa_register_src = (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/blocks.php' );
hcwa_assert_contains( "wp_register_block_metadata_collection( HCWA_CORE_DIR . 'blocks', HCWA_CORE_DIR . 'blocks/blocks-manifest.php' )", $hcwa_register_src, 'blocks register from the manifest' );
hcwa_assert_false( str_contains( $hcwa_register_src, 'glob(' ), 'no folder scan on every request' );

foreach ( $hcwa_contract as $hcwa_name => $hcwa_attrs ) {
	$hcwa_dir = "$hcwa_blocks_dir/$hcwa_name";
	foreach ( array( 'block.json', 'index.js', 'index.asset.php', 'render.php' ) as $hcwa_file ) {
		hcwa_assert_true( is_file( "$hcwa_dir/$hcwa_file" ), "$hcwa_name/$hcwa_file exists" );
	}

	$hcwa_meta = json_decode( (string) file_get_contents( "$hcwa_dir/block.json" ), true );
	hcwa_assert_true( is_array( $hcwa_meta ), "$hcwa_name: block.json is valid JSON" );
	$hcwa_meta = is_array( $hcwa_meta ) ? $hcwa_meta : array();
	hcwa_assert_same( 3, $hcwa_meta['apiVersion'] ?? null, "$hcwa_name: apiVersion 3" );
	hcwa_assert_same( "hcwa/$hcwa_name", $hcwa_meta['name'] ?? null, "$hcwa_name: name" );
	hcwa_assert_same( 'hcwa', $hcwa_meta['category'] ?? null, "$hcwa_name: category" );
	hcwa_assert_same( 'hcwa', $hcwa_meta['textdomain'] ?? null, "$hcwa_name: textdomain" );
	hcwa_assert_same( 'file:./render.php', $hcwa_meta['render'] ?? null, "$hcwa_name: render file" );
	hcwa_assert_same( 'file:./index.js', $hcwa_meta['editorScript'] ?? null, "$hcwa_name: editor script" );
	hcwa_assert_true( '' !== trim( (string) ( $hcwa_meta['title'] ?? '' ) ) && '' !== trim( (string) ( $hcwa_meta['description'] ?? '' ) ), "$hcwa_name: title and description" );
	hcwa_assert_false( $hcwa_meta['supports']['html'] ?? true, "$hcwa_name: no HTML editing (dynamic block)" );

	$hcwa_json_attrs = (array) ( $hcwa_meta['attributes'] ?? array() );
	hcwa_assert_same( array_keys( $hcwa_attrs ), array_keys( $hcwa_json_attrs ), "$hcwa_name: attributes are exactly the contract's" );
	foreach ( $hcwa_attrs as $hcwa_attr => $hcwa_spec ) {
		hcwa_assert_same( $hcwa_spec[0], $hcwa_json_attrs[ $hcwa_attr ]['type'] ?? null, "$hcwa_name.$hcwa_attr type" );
		hcwa_assert_same( $hcwa_spec[1], $hcwa_json_attrs[ $hcwa_attr ]['default'] ?? null, "$hcwa_name.$hcwa_attr default" );
		if ( null !== $hcwa_spec[2] ) {
			$hcwa_enum = $hcwa_json_attrs[ $hcwa_attr ]['enum'] ?? null;
			hcwa_assert_same( $hcwa_spec[2], $hcwa_enum, "$hcwa_name.$hcwa_attr allowed values" );
		}
		hcwa_assert_same( $hcwa_spec[3], $hcwa_json_attrs[ $hcwa_attr ]['role'] ?? null, "$hcwa_name.$hcwa_attr role" );
	}

	// index.asset.php: what register_block_script_handle() reads.
	$hcwa_asset = require "$hcwa_dir/index.asset.php";
	hcwa_assert_true( is_array( $hcwa_asset ) && isset( $hcwa_asset['dependencies'], $hcwa_asset['version'] ), "$hcwa_name: asset file returns dependencies and version" );
	foreach ( array( 'hcwa-blocks-editor', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ) as $hcwa_dep ) {
		hcwa_assert_true( in_array( $hcwa_dep, (array) ( $hcwa_asset['dependencies'] ?? array() ), true ), "$hcwa_name: depends on $hcwa_dep" );
	}

	// render.php is required on every render, so it must not declare functions or classes.
	$hcwa_source = (string) file_get_contents( "$hcwa_dir/render.php" );
	$hcwa_tokens = token_get_all( $hcwa_source );
	$hcwa_decl   = false;
	foreach ( $hcwa_tokens as $hcwa_i => $hcwa_token ) {
		if ( is_array( $hcwa_token ) && in_array( $hcwa_token[0], array( T_FUNCTION, T_CLASS ), true ) ) {
			$hcwa_next = $hcwa_tokens[ $hcwa_i + 2 ] ?? null;
			if ( is_array( $hcwa_next ) && T_STRING === $hcwa_next[0] ) {
				$hcwa_decl = true;
			}
		}
	}
	hcwa_assert_false( $hcwa_decl, "$hcwa_name: render.php declares no functions" );
	hcwa_assert_contains( "defined( 'ABSPATH' ) || exit;", $hcwa_source, "$hcwa_name: render.php has the direct-access guard" );
	// The prototype's markup, no wrapper classes (2.1): only the kept language notice has one.
	hcwa_assert_same( 'language-notice' === $hcwa_name, str_contains( $hcwa_source, 'get_block_wrapper_attributes(' ), "$hcwa_name: block wrapper attributes only on the language notice" );
	hcwa_assert_false( (bool) preg_match( '/<iframe|facebook\.com\/plugins|instagram\.com\/embed/i', $hcwa_source ), "$hcwa_name: no social feed embeds" );

	$hcwa_js = (string) file_get_contents( "$hcwa_dir/index.js" );
	hcwa_assert_true( (bool) preg_match( "#hcwa\\.register(?:Text|Container)?\\( 'hcwa/" . preg_quote( $hcwa_name, '#' ) . "'#", $hcwa_js ), "$hcwa_name: index.js registers the block" );
}

// Gating calls stay where the contract needs them.
$hcwa_src      = static fn( string $name ): string => (string) file_get_contents( "$hcwa_blocks_dir/$name/render.php" );
$hcwa_core_src = (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/blocks.php' )
	. (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/blocks-sections.php' );
hcwa_assert_contains( 'hcwa_get_impact_figures(', $hcwa_core_src, 'impact strip reads gated figures' );
hcwa_assert_contains( 'hcwa_impact_strip_visible(', $hcwa_core_src, 'impact strip applies the 2-figure rule' );
hcwa_assert_contains( 'hcwa_donation_details()', $hcwa_core_src, 'donation reads finance-confirmed details only' );
hcwa_assert_contains( 'hcwa_announcement()', $hcwa_core_src, 'announcement reads the gated setting' );
hcwa_assert_contains( 'hcwa_lang_notice_request()', $hcwa_src( 'language-notice' ), 'language notice reads the switch request' );
hcwa_assert_contains( 'hcwa_partner_logo_allowed(', $hcwa_core_src, 'partner logos check permission' );
hcwa_assert_contains( 'hcwa_little_rose_door_allowed(', $hcwa_core_src, 'door chips and doors check the Little Rose approval' );
hcwa_assert_contains( 'hcwa_traces_on(', $hcwa_core_src, 'corridor traces are switched by the setting and never on production' );
hcwa_assert_contains( 'hcwa_forms_enabled()', $hcwa_core_src, 'forms are off on production without a handler' );
hcwa_assert_false( str_contains( $hcwa_core_src, 'wp_get_attachment_image( (int) get_post_thumbnail_id' ), 'no ungated thumbnails' );
hcwa_assert_false( (bool) preg_match( '/<iframe|facebook\.com\/plugins|instagram\.com\/embed/i', $hcwa_core_src ), 'no social feed embeds in the block markup' );
hcwa_assert_false( (bool) preg_match( '/text-transform\s*:\s*uppercase/i', $hcwa_core_src ), 'no uppercase in inline styles' );

// Front-end strings: every key the blocks use exists in vi and en; every block string is used.
$hcwa_dict = hcwa_blocks_add_strings( hcwa_strings() );
$hcwa_used = array();
$hcwa_php  = array( $hcwa_core_src, (string) file_get_contents( dirname( __DIR__ ) . '/plugins/hcwa-core/includes/sections.php' ) );
foreach ( array_keys( $hcwa_contract ) as $hcwa_name ) {
	$hcwa_php[] = $hcwa_src( $hcwa_name );
}
$hcwa_all_php = implode( "\n", $hcwa_php );
preg_match_all( "/(?:hcwa_t|hcwa_t_in)\(\s*'([A-Za-z_]+)'\s*[,)]/", $hcwa_all_php, $hcwa_m );
foreach ( $hcwa_m[1] as $hcwa_key ) {
	$hcwa_used[ $hcwa_key ] = true;
}
// Keys built at runtime: status pills, report types.
foreach ( array( 'status_active', 'status_completed', 'status_planned', 'blk_type_annual', 'blk_type_financial', 'blk_type_program', 'blk_type_policy' ) as $hcwa_key ) {
	$hcwa_used[ $hcwa_key ] = true;
}
hcwa_assert_true( count( $hcwa_used ) > 30, 'found the block string keys (' . count( $hcwa_used ) . ')' );
foreach ( array_keys( $hcwa_used ) as $hcwa_key ) {
	$hcwa_ok = '' !== trim( (string) ( $hcwa_dict[ $hcwa_key ]['vi'] ?? '' ) ) && '' !== trim( (string) ( $hcwa_dict[ $hcwa_key ]['en'] ?? '' ) );
	hcwa_assert_true( $hcwa_ok, "string $hcwa_key exists in vi and en" );
}
foreach ( hcwa_blocks_strings() as $hcwa_key => $hcwa_entry ) {
	hcwa_assert_true( str_contains( $hcwa_all_php, "'$hcwa_key'" ) || isset( $hcwa_used[ $hcwa_key ] ), "block string $hcwa_key is used" );
	hcwa_assert_false( array_key_exists( $hcwa_key, hcwa_strings() ), "block string $hcwa_key does not redefine a core key" );
	$hcwa_vi = (string) $hcwa_entry['vi'];
	hcwa_assert_same( 0, preg_match( '/\b(?!(?:VI|EN|HCWA|PDF|TP)\b)\p{Lu}{2,}\b/u', $hcwa_vi ), "block string $hcwa_key: no capitalised Vietnamese words" );
	hcwa_assert_same( 0, preg_match( '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $hcwa_vi . $hcwa_entry['en'] ), "block string $hcwa_key: no emoji" );
	hcwa_assert_false( str_contains( $hcwa_vi . $hcwa_entry['en'], '[DRAFT' ), "block string $hcwa_key: no draft marker" );
}
$hcwa_merged = hcwa_blocks_add_strings( array( 'since' => array( 'vi' => 'X', 'en' => 'Y' ) ) );
hcwa_assert_same( 'X', $hcwa_merged['since']['vi'], 'strings filter keeps existing keys' );
hcwa_assert_true( isset( $hcwa_merged['blk_yearToken'] ), 'strings filter adds block keys' );

// Editor labels: every __( '…', 'hcwa' ) in the block scripts has a Vietnamese entry, and
// every entry is used.
$hcwa_vi_labels = hcwa_blocks_editor_vi();
$hcwa_js_files  = array_merge( glob( "$hcwa_blocks_dir/*.js" ), glob( "$hcwa_blocks_dir/*/index.js" ) );
$hcwa_labels    = array();
foreach ( $hcwa_js_files as $hcwa_file ) {
	$hcwa_js = (string) file_get_contents( $hcwa_file );
	hcwa_assert_false( (bool) preg_match( "/__\(\s*\"/", $hcwa_js ), basename( dirname( $hcwa_file ) ) . '/' . basename( $hcwa_file ) . ': labels use single quotes (checked below)' );
	hcwa_assert_false( (bool) preg_match( "/__\(\s*'(?:[^'\\\\]|\\\\.)*'\s*\)/", $hcwa_js ), basename( $hcwa_file ) . ': every label names the hcwa text domain' );
	preg_match_all( "/__\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*,\s*'hcwa'\s*\)/", $hcwa_js, $hcwa_m );
	foreach ( $hcwa_m[1] as $hcwa_label ) {
		$hcwa_labels[ str_replace( "\\'", "'", $hcwa_label ) ] = true;
	}
}
hcwa_assert_true( count( $hcwa_labels ) > 60, 'found the editor labels (' . count( $hcwa_labels ) . ')' );
foreach ( array_keys( $hcwa_labels ) as $hcwa_label ) {
	hcwa_assert_true( '' !== trim( $hcwa_vi_labels[ $hcwa_label ] ?? '' ), "editor label has Vietnamese: $hcwa_label" );
}
foreach ( array_keys( $hcwa_vi_labels ) as $hcwa_label ) {
	hcwa_assert_true( isset( $hcwa_labels[ $hcwa_label ] ), "Vietnamese editor entry is used: $hcwa_label" );
}
// block.json titles and descriptions are the same English text the scripts translate.
foreach ( $hcwa_contract as $hcwa_name => $hcwa_attrs ) {
	$hcwa_meta = json_decode( (string) file_get_contents( "$hcwa_blocks_dir/$hcwa_name/block.json" ), true );
	hcwa_assert_true( isset( $hcwa_vi_labels[ $hcwa_meta['title'] ?? '' ] ), "$hcwa_name: title has Vietnamese" );
	hcwa_assert_true( isset( $hcwa_vi_labels[ $hcwa_meta['description'] ?? '' ] ), "$hcwa_name: description has Vietnamese" );
}
