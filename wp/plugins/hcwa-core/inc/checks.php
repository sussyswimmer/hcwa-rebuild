<?php
/**
 * Launch checks (07-BILINGUAL "coverage", 09-QA-CHECKLIST blockers), as plain functions so
 * they run from WP-CLI (`wp hcwa coverage`, `wp hcwa audit`) and from Tools → HCWA checks for
 * staff who don't use a terminal.
 *
 * Each check returns rows: [ status: ok|warn|fail, check, detail, link ].
 */

defined( 'ABSPATH' ) || exit;

function hcwa_check_row( $status, $check, $detail = '', $link = '' ) {
	return array( 'status' => $status, 'check' => $check, 'detail' => $detail, 'link' => $link );
}

/** Post types that must exist in both languages at launch (news posts may be VI-only). */
function hcwa_coverage_post_types() {
	return array_merge( array( 'page' ), hcwa_translated_post_types() );
}

/* ---------- translation coverage ---------- */

function hcwa_coverage_report() {
	$rows = array();
	if ( ! function_exists( 'pll_get_post_translations' ) ) {
		return array( hcwa_check_row( 'fail', 'Polylang', 'Polylang is not active.' ) );
	}
	$langs = array( 'vi', 'en' );

	// Pages and content types: every published item needs both languages.
	foreach ( hcwa_coverage_post_types() as $type ) {
		$missing = 0;
		$posts   = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '' ) );
		foreach ( $posts as $post ) {
			$lang = pll_get_post_language( $post->ID );
			if ( ! $lang ) {
				$rows[] = hcwa_check_row( 'fail', $type, sprintf( '"%s" (#%d) has no language.', get_the_title( $post ), $post->ID ), get_edit_post_link( $post->ID, 'raw' ) );
				++$missing;
				continue;
			}
			$translations = pll_get_post_translations( $post->ID );
			foreach ( $langs as $l ) {
				if ( $l === $lang ) {
					continue;
				}
				$tr = isset( $translations[ $l ] ) ? get_post( $translations[ $l ] ) : null;
				if ( ! $tr || 'publish' !== $tr->post_status ) {
					$rows[] = hcwa_check_row( 'fail', $type, sprintf( '"%s" (#%d, %s) has no published %s version.', get_the_title( $post ), $post->ID, strtoupper( $lang ), strtoupper( $l ) ), get_edit_post_link( $post->ID, 'raw' ) );
					++$missing;
				}
			}
		}
		if ( ! $missing ) {
			$rows[] = hcwa_check_row( 'ok', $type, sprintf( '%d published, all in both languages.', count( $posts ) ) );
		}
	}

	// News posts: allowed in one language (the switch shows the language notice). Report the count.
	$single = 0;
	$posts  = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '', 'fields' => 'ids' ) );
	foreach ( $posts as $id ) {
		if ( count( pll_get_post_translations( $id ) ) < 2 ) {
			++$single;
		}
	}
	$rows[] = hcwa_check_row( $single ? 'warn' : 'ok', 'post', sprintf( '%d of %d news posts exist in one language only (allowed: visitors get the language notice).', $single, count( $posts ) ) );

	// Terms.
	foreach ( array( 'hcwa_area', 'category' ) as $taxonomy ) {
		$missing = array();
		$terms   = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => '' ) );
		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			if ( count( pll_get_term_translations( $term->term_id ) ) < 2 && 'uncategorized' !== $term->slug && 'khong-phan-loai' !== $term->slug ) {
				$missing[] = $term->name;
			}
		}
		$rows[] = $missing
			? hcwa_check_row( 'fail', $taxonomy, 'No translation: ' . implode( ', ', $missing ) )
			: hcwa_check_row( 'ok', $taxonomy, 'All terms in both languages.' );
	}

	// Menus: one per language per location, same length.
	$options   = get_option( 'polylang' );
	$theme     = get_option( 'stylesheet' );
	$locations = get_registered_nav_menus();
	foreach ( $locations as $location => $label ) {
		if ( false !== strpos( $location, '___' ) ) {
			continue; // Polylang's internal per-language copies ("primary___en")
		}
		$assigned = isset( $options['nav_menus'][ $theme ][ $location ] ) ? $options['nav_menus'][ $theme ][ $location ] : array();
		$counts   = array();
		foreach ( $langs as $l ) {
			$menu         = ! empty( $assigned[ $l ] ) ? wp_get_nav_menu_items( $assigned[ $l ] ) : false;
			$counts[ $l ] = $menu ? count( $menu ) : 0;
		}
		if ( in_array( 0, $counts, true ) ) {
			$rows[] = hcwa_check_row( 'fail', 'menu: ' . $location, sprintf( 'Menu missing for: %s (Appearance → Menus → Manage locations).', implode( ', ', array_keys( $counts, 0, true ) ) ) );
		} elseif ( $counts['vi'] !== $counts['en'] ) {
			$rows[] = hcwa_check_row( 'warn', 'menu: ' . $location, sprintf( 'VI menu has %d items, EN has %d.', $counts['vi'], $counts['en'] ) );
		} else {
			$rows[] = hcwa_check_row( 'ok', 'menu: ' . $location, sprintf( '%d items in each language.', $counts['vi'] ) );
		}
	}

	// UI strings: both languages for every key.
	$bad = array();
	foreach ( hcwa_strings() as $key => $pair ) {
		if ( empty( $pair['vi'] ) || empty( $pair['en'] ) ) {
			$bad[] = $key;
		}
	}
	$rows[] = $bad ? hcwa_check_row( 'fail', 'UI strings', 'Missing a language: ' . implode( ', ', $bad ) ) : hcwa_check_row( 'ok', 'UI strings', count( hcwa_strings() ) . ' strings, all in both languages.' );

	// Role pages (header, footer and CTA links).
	$unset = array();
	foreach ( hcwa_page_roles() as $role => $label ) {
		if ( ! hcwa_page_id( $role, 'vi' ) || ! hcwa_page_id( $role, 'en' ) ) {
			$unset[] = $label;
		}
	}
	$rows[] = $unset
		? hcwa_check_row( 'fail', 'Pages by role', 'Not set or not translated: ' . implode( ', ', $unset ) . ' (Settings → HCWA).', admin_url( 'options-general.php?page=hcwa' ) )
		: hcwa_check_row( 'ok', 'Pages by role', 'All roles set in both languages.' );

	return $rows;
}

/* ---------- content and safety audit ---------- */

/** All blocks in a post, flattened. Children of a draft block are skipped: the draft covers them. */
function hcwa_blocks_flat( $blocks ) {
	$out = array();
	foreach ( $blocks as $block ) {
		$out[] = $block;
		if ( ! empty( $block['innerBlocks'] ) && empty( $block['attrs']['hcwaDraft'] ) ) {
			$out = array_merge( $out, hcwa_blocks_flat( $block['innerBlocks'] ) );
		}
	}
	return $out;
}

function hcwa_audit_report() {
	$rows  = array();
	$types = array_merge( array( 'page', 'post' ), hcwa_translated_post_types() );
	$posts = get_posts( array( 'post_type' => $types, 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '' ) );

	$drafts = $tokens = $feeds = $empty = $elementor = $flagged = array();
	$images = array();
	foreach ( $posts as $post ) {
		$label = sprintf( '%s "%s" (#%d)', $post->post_type, wp_strip_all_tags( get_the_title( $post ) ), $post->ID );
		$text  = $post->post_title . "\n" . $post->post_excerpt;
		foreach ( hcwa_fields()[ $post->post_type ] ?? array() as $field ) {
			if ( in_array( $field['type'], array( 'text', 'textarea', 'lines' ), true ) && 'hcwa_source' !== $field['key'] ) {
				$text .= "\n" . get_post_meta( $post->ID, $field['key'], true );
			}
		}
		$blocks = hcwa_blocks_flat( parse_blocks( $post->post_content ) );
		$clean  = '';
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['attrs']['hcwaDraft'] ) ) {
				$drafts[ $post->ID ] = $label;
				continue;
			}
			if ( empty( $block['innerBlocks'] ) ) {
				$clean .= "\n" . $block['innerHTML'];
			}
			if ( preg_match( '#^core/heading$#', (string) $block['blockName'] ) && '' === trim( wp_strip_all_tags( $block['innerHTML'] ) ) ) {
				$empty[ $post->ID ] = $label;
			}
			if ( in_array( $block['blockName'], array( 'core/embed' ), true ) && preg_match( '/instagram|facebook/i', wp_json_encode( $block['attrs'] ) ) ) {
				$feeds[ $post->ID ] = $label;
			}
			if ( 'hcwa/image-slot' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
				$images[ (int) $block['attrs']['id'] ][] = $label;
			}
		}
		$text .= "\n" . wp_strip_all_tags( $clean );
		// Bracketed placeholders like [N], [năm], [DRAFT: …] outside draft blocks.
		if ( preg_match( '/\[(?:DRAFT|[^\]\n]{1,60})\]/u', $text, $m ) && ! preg_match( '/^\[(?:\d+|caption|gallery)\b/', $m[0] ) ) {
			$tokens[ $post->ID ] = $label . ': ' . $m[0];
		}
		if ( preg_match( '/\[(instagram-feed|custom-facebook-feed|efb_likebox|fts_|instagram)/i', $post->post_content ) ) {
			$feeds[ $post->ID ] = $label;
		}
		if ( get_post_meta( $post->ID, '_elementor_data', true ) && 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true ) ) {
			$elementor[ $post->ID ] = $label;
		}
		if ( in_array( $post->post_type, array( 'hcwa_center', 'hcwa_program', 'hcwa_report' ), true ) && get_post_meta( $post->ID, 'hcwa_draft', true ) ) {
			$flagged[ $post->ID ] = $label;
		}
		if ( get_post_meta( $post->ID, 'hcwa_intro_draft', true ) ) {
			$flagged[ $post->ID ] = $label . ' (intro)';
		}
		if ( $thumb = get_post_thumbnail_id( $post ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
			$images[ (int) $thumb ][] = $label;
		}
		foreach ( array( 'hcwa_gallery' ) as $key ) {
			foreach ( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, $key, true ) ) ) ) as $id ) {
				$images[ $id ][] = $label;
			}
		}
		if ( preg_match_all( '/wp-image-(\d+)/', $post->post_content, $ids ) ) {
			foreach ( $ids[1] as $id ) {
				$images[ (int) $id ][] = $label;
			}
		}
	}

	$list = function ( $items ) {
		return implode( '; ', array_slice( array_values( $items ), 0, 15 ) ) . ( count( $items ) > 15 ? sprintf( ' … and %d more', count( $items ) - 15 ) : '' );
	};

	$rows[] = $drafts ? hcwa_check_row( 'fail', 'Draft blocks', count( $drafts ) . ' published items still have blocks marked draft (hidden on the live site): ' . $list( $drafts ) ) : hcwa_check_row( 'ok', 'Draft blocks', 'None.' );
	$rows[] = $flagged ? hcwa_check_row( 'fail', 'Draft wording', 'Marked "wording is a draft" (hidden on the live site): ' . $list( $flagged ) ) : hcwa_check_row( 'ok', 'Draft wording', 'None.' );
	$rows[] = $tokens ? hcwa_check_row( 'fail', 'Placeholders', 'Bracketed placeholder text: ' . $list( $tokens ) ) : hcwa_check_row( 'ok', 'Placeholders', 'No [..] placeholders in published text.' );
	$rows[] = $feeds ? hcwa_check_row( 'fail', 'Social feeds', 'Instagram/Facebook embeds or feed shortcodes (not allowed): ' . $list( $feeds ) ) : hcwa_check_row( 'ok', 'Social feeds', 'None.' );
	$rows[] = $empty ? hcwa_check_row( 'warn', 'Empty headings', $list( $empty ) ) : hcwa_check_row( 'ok', 'Empty headings', 'None.' );
	$rows[] = $elementor ? hcwa_check_row( 'warn', 'Elementor', count( $elementor ) . ' published items still built with Elementor (task 3.2/3.3): ' . $list( $elementor ) ) : hcwa_check_row( 'ok', 'Elementor', 'No published item uses Elementor.' );

	// Images: refused ones never render, but the pages using them will have gaps.
	$refused = array();
	foreach ( $images as $id => $where ) {
		if ( ! hcwa_image_approved( $id ) ) {
			$refused[] = sprintf( '#%d in %s', $id, implode( ', ', array_unique( $where ) ) );
		}
	}
	$rows[] = $refused ? hcwa_check_row( 'warn', 'Unapproved images', count( $refused ) . ' images are not approved and will not show: ' . $list( $refused ), admin_url( 'upload.php' ) ) : hcwa_check_row( 'ok', 'Unapproved images', 'Every image used is approved.' );

	// Figures.
	$figures    = get_posts( array( 'post_type' => 'hcwa_impact', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '' ) );
	$unverified = array_filter(
		$figures,
		function ( $f ) {
			return ! hcwa_figure_is_verified( $f );
		}
	);
	$rows[] = hcwa_check_row( $unverified ? 'warn' : 'ok', 'Impact figures', sprintf( '%d of %d figures unverified (they never show on the live site; the homepage strip needs 2 verified).', count( $unverified ), count( $figures ) ), admin_url( 'edit.php?post_type=hcwa_impact' ) );

	// Settings that guard hard rules.
	$rows[] = hcwa_donation_confirmed()
		? hcwa_check_row( 'ok', 'Donation details', sprintf( 'Confirmed by %s on %s.', hcwa_setting( 'donation_confirmed_by' ), hcwa_setting( 'donation_confirmed_on' ) ) )
		: hcwa_check_row( 'fail', 'Donation details', 'Not confirmed by HCWA finance: the Donate page shows only "contact us".', admin_url( 'options-general.php?page=hcwa' ) );
	$rows[] = hcwa_contact_verified()
		? hcwa_check_row( 'ok', 'Contact details', sprintf( 'Verified by %s on %s.', hcwa_setting( 'contact_verified_by' ), hcwa_setting( 'contact_verified_on' ) ) )
		: hcwa_check_row( 'fail', 'Contact details', 'Not verified: address, phone and email are hidden on the live site.', admin_url( 'options-general.php?page=hcwa' ) );
	foreach ( array( 'policies' => 'Privacy notice and safeguarding policy page' ) as $role => $what ) {
		$rows[] = hcwa_page_id( $role, 'vi' ) && hcwa_page_id( $role, 'en' ) ? hcwa_check_row( 'ok', $what, 'Linked in the footer in both languages.' ) : hcwa_check_row( 'fail', $what, 'Missing in one or both languages (09: required in the footer).' );
	}

	// Search engines: staging hidden, production visible.
	$public = (bool) get_option( 'blog_public' );
	if ( hcwa_is_production() ) {
		$rows[] = hcwa_check_row( $public ? 'ok' : 'fail', 'Search engines', $public ? 'Production is visible to search engines.' : 'Production is set to "discourage search engines".' );
	} else {
		$rows[] = hcwa_check_row( 'ok', 'Search engines', 'Not production: pages carry noindex (theme) regardless of the setting.' );
	}

	$log = (array) get_option( 'hcwa_refused_images', array() );
	if ( $log ) {
		$rows[] = hcwa_check_row( 'warn', 'Refused at render', sprintf( '%d images were refused while pages rendered recently (IDs: %s).', count( $log ), implode( ', ', array_keys( $log ) ) ) );
	}
	return $rows;
}

function hcwa_report_failed( $rows ) {
	return (bool) array_filter(
		$rows,
		function ( $r ) {
			return 'fail' === $r['status'];
		}
	);
}

/* ---------- Tools → HCWA checks ---------- */

add_action(
	'admin_menu',
	function () {
		add_management_page( __( 'HCWA checks', 'hcwa-core' ), __( 'HCWA checks', 'hcwa-core' ), 'edit_pages', 'hcwa-checks', 'hcwa_render_checks_page' );
	}
);

function hcwa_render_checks_page() {
	echo '<div class="wrap"><h1>' . esc_html__( 'HCWA checks', 'hcwa-core' ) . '</h1>';
	echo '<p>' . esc_html__( 'Run before every review and before launch. Red items block the launch (09-QA-CHECKLIST).', 'hcwa-core' ) . '</p>';
	foreach ( array( __( 'Translations', 'hcwa-core' ) => hcwa_coverage_report(), __( 'Content and safety', 'hcwa-core' ) => hcwa_audit_report() ) as $title => $rows ) {
		echo '<h2>' . esc_html( $title ) . '</h2><table class="widefat striped hcwa-check-table"><thead><tr><th>' . esc_html__( 'Status', 'hcwa-core' ) . '</th><th>' . esc_html__( 'Check', 'hcwa-core' ) . '</th><th>' . esc_html__( 'Detail', 'hcwa-core' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$labels = array( 'ok' => 'OK', 'warn' => __( 'Check', 'hcwa-core' ), 'fail' => __( 'Fix', 'hcwa-core' ) );
			echo '<tr><td class="hcwa-check--' . esc_attr( $row['status'] ) . '">' . esc_html( $labels[ $row['status'] ] ) . '</td><td>' . esc_html( $row['check'] ) . '</td><td>' . esc_html( $row['detail'] );
			if ( $row['link'] ) {
				echo ' <a href="' . esc_url( $row['link'] ) . '">' . esc_html__( 'Open', 'hcwa-core' ) . '</a>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'tools_page_hcwa-checks' === $hook ) {
			wp_enqueue_style( 'hcwa-admin', HCWA_CORE_URL . 'assets/admin.css', array(), HCWA_CORE_VERSION );
		}
	}
);
