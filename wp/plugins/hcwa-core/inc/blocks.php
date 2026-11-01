<?php
/**
 * Server-rendered blocks (08 task 2.5). Each block lives in blocks/{name}/ with a block.json
 * and a render.php; one editor script (assets/editor.js, no build step) gives them sidebar
 * controls and a live preview.
 *
 * Also adds a "Draft: waiting for HCWA approval" switch to every block. Draft blocks render
 * with a dashed outline on staging and not at all on production.
 */

defined( 'ABSPATH' ) || exit;

function hcwa_block_names() {
	return array( 'link', 'image-slot', 'impact-strip', 'focus-areas', 'center-grid', 'program-list', 'latest-news', 'report-list', 'partner-wall', 'work-filters', 'contact-details', 'donation-details', 'language-notice' );
}

add_action(
	'init',
	function () {
		foreach ( hcwa_block_names() as $name ) {
			register_block_type( HCWA_CORE_DIR . 'blocks/' . $name );
		}
	}
);

add_filter(
	'block_categories_all',
	function ( $categories ) {
		array_unshift( $categories, array( 'slug' => 'hcwa', 'title' => 'HCWA', 'icon' => null ) );
		return $categories;
	}
);

/* ---------- editor ---------- */

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'hcwa-blocks-editor',
			HCWA_CORE_URL . 'assets/editor.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-server-side-render', 'wp-data', 'wp-plugins', 'wp-editor' ),
			HCWA_CORE_VERSION,
			false
		);
		wp_add_inline_script( 'hcwa-blocks-editor', 'window.hcwaEditor = ' . wp_json_encode( hcwa_editor_data() ) . ';', 'before' );
	}
);

// The editor canvas is an iframe: styles for things drawn inside it (draft outline, empty
// block notice) must come through enqueue_block_assets, which reaches the iframe.
add_action(
	'enqueue_block_assets',
	function () {
		if ( is_admin() ) {
			wp_enqueue_style( 'hcwa-blocks-editor', HCWA_CORE_URL . 'assets/editor.css', array(), HCWA_CORE_VERSION );
		}
	}
);

/** Choices for block controls: unit keys, focus areas, report types, link targets. */
function hcwa_editor_data() {
	$areas = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'hcwa_area',
			'hide_empty' => false,
			'lang'       => '', // all languages: the editor may be on either
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		$lang = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $term->term_id ) : '';
		$areas[] = array( 'value' => $term->slug, 'label' => $term->name . ( $lang ? ' (' . strtoupper( $lang ) . ')' : '' ) );
	}
	$targets = array(
		array( 'value' => 'home', 'label' => __( 'Homepage', 'hcwa-core' ) ),
		array( 'value' => 'news', 'label' => __( 'News', 'hcwa-core' ) ),
		array( 'value' => 'reports', 'label' => __( 'Reports library', 'hcwa-core' ) ),
	);
	foreach ( hcwa_page_roles() as $role => $label ) {
		$targets[] = array( 'value' => $role, 'label' => $label );
	}
	foreach ( array_keys( hcwa_anchor_routes() ) as $anchor ) {
		$targets[] = array( 'value' => $anchor, 'label' => $anchor );
	}
	$options = function ( $map ) {
		$out = array();
		foreach ( $map as $value => $label ) {
			$out[] = array( 'value' => (string) $value, 'label' => $label );
		}
		return $out;
	};
	return array(
		'centers'     => $options( hcwa_key_options( 'hcwa_center' ) ),
		'programs'    => $options( hcwa_key_options( 'hcwa_program' ) ),
		'areas'       => $areas,
		'reportTypes' => $options( hcwa_report_types() ),
		'targets'     => $targets,
		'review'      => hcwa_review_mode(),
	);
}

/* ---------- draft switch on every block ---------- */

add_filter(
	'register_block_type_args',
	function ( $args ) {
		if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
			$args['attributes'] = array();
		}
		$args['attributes']['hcwaDraft'] = array( 'type' => 'boolean', 'default' => false );
		return $args;
	}
);

add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( empty( $block['attrs']['hcwaDraft'] ) ) {
			return $content;
		}
		if ( hcwa_is_production() ) {
			return '';
		}
		if ( '' === trim( $content ) ) {
			return $content;
		}
		$tags = new WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'data-draft', true );
		}
		return $tags->get_updated_html();
	},
	5,
	2
);

/* ---------- sections hide when their data is empty ---------- */

/** Data blocks that render nothing when there is nothing (verified) to show. */
function hcwa_data_blocks() {
	return array( 'hcwa/center-grid', 'hcwa/program-list', 'hcwa/latest-news', 'hcwa/partner-wall', 'hcwa/report-list', 'hcwa/impact-strip' );
}

/** Names of data blocks inside a block tree. */
function hcwa_find_data_blocks( $blocks ) {
	$found = array();
	foreach ( $blocks as $block ) {
		if ( in_array( $block['blockName'], hcwa_data_blocks(), true ) ) {
			$found[] = $block['blockName'];
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = array_merge( $found, hcwa_find_data_blocks( $block['innerBlocks'] ) );
		}
	}
	return $found;
}

/**
 * A section (group with the "section" class) built around data blocks disappears when none of
 * them produced output, so visitors never see a heading over nothing ("a section with zero
 * verified figures hides itself", 04-SITEMAP). The hero is not a section and always stays.
 */
add_filter(
	'render_block_core/group',
	function ( $content, $block ) {
		$class = ' ' . ( isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '' ) . ' ';
		if ( false === strpos( $class, ' section ' ) || empty( $block['innerBlocks'] ) ) {
			return $content;
		}
		$data = hcwa_find_data_blocks( $block['innerBlocks'] );
		if ( ! $data ) {
			return $content;
		}
		foreach ( array_unique( $data ) as $name ) {
			if ( false !== strpos( $content, 'wp-block-' . str_replace( '/', '-', $name ) ) ) {
				return $content;
			}
		}
		return '';
	},
	20,
	2
);

/* ---------- shared render helpers ---------- */

/** Area filter from ?area= when a block is set to follow it (Our Work hub). */
function hcwa_request_area() {
	return isset( $_GET['area'] ) ? sanitize_title( wp_unslash( $_GET['area'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

/** Wrapper attributes string for a dynamic block, with extra classes. */
function hcwa_block_wrapper( $classes, $extra = array() ) {
	return get_block_wrapper_attributes( array_merge( array( 'class' => $classes ), $extra ) );
}
