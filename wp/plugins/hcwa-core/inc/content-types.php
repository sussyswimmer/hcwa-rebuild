<?php
/**
 * Content types from 04-SITEMAP-AND-CONTENT-MODEL: centers, programs, impact figures,
 * reports, partners, and the shared focus-area taxonomy.
 *
 * URLs: Polylang free can't translate a post type's URL base, so centers, programs and
 * reports get their own rewrite rules per language:
 *   /hoat-dong/co-so/{slug}/      /en/our-work/centers/{slug}/
 *   /hoat-dong/du-an/{slug}/      /en/our-work/programs/{slug}/
 *   /tac-dong/bao-cao/{slug}/     /en/impact/reports/{slug}/
 */

defined( 'ABSPATH' ) || exit;

/** URL bases per language for public post types. */
function hcwa_url_bases() {
	return array(
		'hcwa_center'  => array( 'vi' => 'hoat-dong/co-so', 'en' => 'our-work/centers' ),
		'hcwa_program' => array( 'vi' => 'hoat-dong/du-an', 'en' => 'our-work/programs' ),
		'hcwa_report'  => array( 'vi' => 'tac-dong/bao-cao', 'en' => 'impact/reports' ),
	);
}

add_action( 'init', 'hcwa_register_content_types', 5 );

function hcwa_register_content_types() {
	$common = array(
		'show_in_rest' => true,
		'rewrite'      => false, // see hcwa_rewrite_rules()
		'map_meta_cap' => true,
	);

	register_post_type(
		'hcwa_center',
		$common + array(
			'labels'        => hcwa_cpt_labels( __( 'Centers', 'hcwa-core' ), __( 'Center', 'hcwa-core' ) ),
			'description'   => __( 'Permanent places HCWA runs (Green Bamboo, Little Rose, Cau Han).', 'hcwa-core' ),
			'public'        => true,
			'has_archive'   => false,
			'query_var'     => 'hcwa_center',
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-building',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
		)
	);

	register_post_type(
		'hcwa_program',
		$common + array(
			'labels'        => hcwa_cpt_labels( __( 'Programs', 'hcwa-core' ), __( 'Program', 'hcwa-core' ) ),
			'description'   => __( 'Funded projects and programs with a start and an end.', 'hcwa-core' ),
			'public'        => true,
			'has_archive'   => false,
			'query_var'     => 'hcwa_program',
			'menu_position' => 22,
			'menu_icon'     => 'dashicons-calendar-alt',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
		)
	);

	register_post_type(
		'hcwa_impact',
		$common + array(
			'labels'          => hcwa_cpt_labels( __( 'Impact figures', 'hcwa-core' ), __( 'Impact figure', 'hcwa-core' ) ),
			'description'     => __( 'One number per row. Unverified figures never show on the live site.', 'hcwa-core' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 23,
			'menu_icon'       => 'dashicons-chart-bar',
			'supports'        => array( 'title', 'page-attributes' ),
			'capability_type' => 'post',
		)
	);

	register_post_type(
		'hcwa_report',
		$common + array(
			'labels'        => hcwa_cpt_labels( __( 'Reports', 'hcwa-core' ), __( 'Report', 'hcwa-core' ) ),
			'description'   => __( 'Annual reports, financial statements, program reports and policies (PDF).', 'hcwa-core' ),
			'public'        => true,
			'has_archive'   => false, // archive rules below
			'query_var'     => 'hcwa_report',
			'menu_position' => 24,
			'menu_icon'     => 'dashicons-media-document',
			'supports'      => array( 'title', 'excerpt', 'thumbnail', 'revisions' ),
		)
	);

	register_post_type(
		'hcwa_partner',
		$common + array(
			'labels'        => hcwa_cpt_labels( __( 'Partners', 'hcwa-core' ), __( 'Partner', 'hcwa-core' ) ),
			'description'   => __( 'Partner and sponsor logos. Names are the same in both languages, so partners are not translated.', 'hcwa-core' ),
			'public'        => false,
			'show_ui'       => true,
			'menu_position' => 25,
			'menu_icon'     => 'dashicons-groups',
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	register_taxonomy(
		'hcwa_area',
		array( 'hcwa_center', 'hcwa_program', 'post' ),
		array(
			'labels'            => array(
				'name'          => __( 'Focus areas', 'hcwa-core' ),
				'singular_name' => __( 'Focus area', 'hcwa-core' ),
				'add_new_item'  => __( 'Add focus area', 'hcwa-core' ),
			),
			'public'            => true,
			'publicly_queryable' => false, // Our Work filters with ?area=, there are no term archives
			'hierarchical'      => true,  // checkbox UI in the editor
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => false,
		)
	);
}

function hcwa_cpt_labels( $plural, $singular ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		/* translators: %s: singular content type name */
		'add_new_item'       => sprintf( __( 'Add %s', 'hcwa-core' ), $singular ),
		/* translators: %s: singular content type name */
		'edit_item'          => sprintf( __( 'Edit %s', 'hcwa-core' ), $singular ),
		/* translators: %s: singular content type name */
		'new_item'           => sprintf( __( 'New %s', 'hcwa-core' ), $singular ),
		/* translators: %s: singular content type name */
		'view_item'          => sprintf( __( 'View %s', 'hcwa-core' ), $singular ),
		/* translators: %s: plural content type name */
		'search_items'       => sprintf( __( 'Search %s', 'hcwa-core' ), $plural ),
		'not_found'          => __( 'Nothing found.', 'hcwa-core' ),
		'all_items'          => $plural,
		'menu_name'          => $plural,
	);
}

/* ---------- per-language URLs ---------- */

add_action( 'init', 'hcwa_rewrite_rules', 20 );

function hcwa_rewrite_rules() {
	foreach ( hcwa_url_bases() as $type => $bases ) {
		foreach ( $bases as $lang => $base ) {
			$prefix = 'vi' === $lang ? '' : 'en/';
			add_rewrite_rule( '^' . $prefix . $base . '/([^/]+)/?$', 'index.php?' . $type . '=$matches[1]&lang=' . $lang, 'top' );
		}
	}
	// Reports library with filters: /tac-dong/bao-cao/ and /en/impact/reports/.
	foreach ( hcwa_url_bases()['hcwa_report'] as $lang => $base ) {
		$prefix = 'vi' === $lang ? '' : 'en/';
		add_rewrite_rule( '^' . $prefix . $base . '/?$', 'index.php?hcwa_reports=1&lang=' . $lang, 'top' );
		add_rewrite_rule( '^' . $prefix . $base . '/page/([0-9]+)/?$', 'index.php?hcwa_reports=1&paged=$matches[1]&lang=' . $lang, 'top' );
	}
}

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'hcwa_reports';
		return $vars;
	}
);

// The reports library is the report post type archive, reached through our own query var.
add_action(
	'parse_query',
	function ( $query ) {
		if ( $query->is_main_query() && $query->get( 'hcwa_reports' ) ) {
			$query->set( 'post_type', 'hcwa_report' );
			$query->is_home              = false;
			$query->is_archive           = true;
			$query->is_post_type_archive = true;
		}
	}
);

// Our rules already carry the language; stop Polylang adding a second /en/ variant to them.
add_filter(
	'pll_modify_rewrite_rule',
	function ( $modify, $rule ) {
		$key = (string) key( $rule );
		foreach ( hcwa_url_bases() as $bases ) {
			foreach ( $bases as $base ) {
				if ( false !== strpos( $key, $base ) ) {
					return false;
				}
			}
		}
		return $modify;
	},
	10,
	2
);

/**
 * A center/program/report slug under the other language's base (/en/our-work/centers/tre-xanh/)
 * 301s to the post's real URL. Without this, canonical redirection strips only the /en/ prefix
 * and lands on a URL that doesn't exist.
 */
add_filter(
	'request',
	function ( $vars ) {
		foreach ( array_keys( hcwa_url_bases() ) as $type ) {
			if ( empty( $vars[ $type ] ) || empty( $vars['lang'] ) || ! function_exists( 'pll_get_post_language' ) ) {
				continue;
			}
			// Polylang doesn't filter queries by name, so check each match's language ourselves.
			$ids = get_posts( array( 'post_type' => $type, 'name' => $vars[ $type ], 'posts_per_page' => 5, 'fields' => 'ids', 'post_status' => 'publish', 'lang' => '' ) );
			$own = array_filter(
				$ids,
				function ( $id ) use ( $vars ) {
					return pll_get_post_language( $id, 'slug' ) === $vars['lang'];
				}
			);
			if ( $own ) {
				$vars['p']         = (int) reset( $own ); // exact post: same slug may exist in the other language one day
				$vars['post_type'] = $type;
				unset( $vars[ $type ], $vars['name'] );
				continue;
			}
			if ( $ids ) {
				wp_safe_redirect( get_permalink( $ids[0] ), 301 );
				exit;
			}
		}
		return $vars;
	}
);

add_filter( 'post_type_link', 'hcwa_post_type_link', 5, 2 );

function hcwa_post_type_link( $link, $post ) {
	$bases = hcwa_url_bases();
	if ( ! isset( $bases[ $post->post_type ] ) || ! $post->post_name || in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ), true ) ) {
		return $link;
	}
	$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post->ID, 'slug' ) : 'vi';
	$lang = 'en' === $lang ? 'en' : 'vi';
	$path = ( 'en' === $lang ? 'en/' : '' ) . $bases[ $post->post_type ][ $lang ] . '/' . $post->post_name . '/';
	return home_url( user_trailingslashit( $path ) );
}

/** Reports library URL in a language. */
function hcwa_reports_url( $lang = null ) {
	$lang = $lang ? $lang : hcwa_lang();
	return home_url( ( 'en' === $lang ? 'en/' : '' ) . hcwa_url_bases()['hcwa_report'][ $lang ] . '/' );
}

/* ---------- drafts never reach the live site ---------- */

/**
 * On production, centers, programs and reports whose wording is still marked draft are left
 * out of every front-end query, so they also 404 as single pages. Staging shows them outlined.
 */
add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || hcwa_review_mode() ) {
			return;
		}
		$types   = (array) $query->get( 'post_type' );
		$guarded = array( 'hcwa_center', 'hcwa_program', 'hcwa_report' );
		$single  = $query->get( 'hcwa_center' ) || $query->get( 'hcwa_program' ) || $query->get( 'hcwa_report' );
		if ( ! $single && ! array_intersect( $types, $guarded ) ) {
			return;
		}
		$meta   = (array) $query->get( 'meta_query' );
		$meta[] = array(
			'key'     => 'hcwa_draft',
			'compare' => 'NOT EXISTS',
		);
		$query->set( 'meta_query', $meta );
	}
);

/** Is this center, program or report still a draft (outlined in review mode)? */
function hcwa_is_draft( $post ) {
	return (bool) get_post_meta( get_post( $post )->ID, 'hcwa_draft', true );
}

// Rules change only when this file changes; flush once per version, not on every request.
add_action(
	'init',
	function () {
		if ( get_option( 'hcwa_rewrite_version' ) !== HCWA_CORE_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'hcwa_rewrite_version', HCWA_CORE_VERSION );
		}
	},
	99
);
