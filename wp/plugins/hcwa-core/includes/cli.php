<?php
/**
 * WP-CLI commands (loaded only under WP-CLI):
 *
 *   wp hcwa coverage     Every published page, center, program, report, partner and
 *                        impact figure, category, center or program news tag, menu and UI
 *                        string missing a VI or EN counterpart, and every center marked "No
 *                        identifiable children" without a news tag. Exits non-zero when pages
 *                        or content types are missing one, or such a center has no news tag
 *                        or its tag has no linked version in both languages. Get_terms() is
 *                        called with 'lang' => '' so Polylang does not filter by language
 *                        (polylang src/crud-terms.php:192-195).
 *   wp hcwa draft-scan   Published content, our text fields and Settings → HCWA values
 *                        that still contain draft markers ([DRAFT: …], [N], [year] …).
 *                        Exits non-zero when anything is found.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Lists content, menus and UI strings missing a Vietnamese or English counterpart.
 *
 * ## OPTIONS
 *
 * [--format=<format>]
 * : table, csv, json, yaml or count.
 * ---
 * default: table
 * ---
 *
 * ## EXAMPLES
 *
 *     wp hcwa coverage
 *
 * @param array $args       Positional arguments (unused).
 * @param array $assoc_args Associative arguments.
 */
function hcwa_cli_coverage( $args, $assoc_args ): void {
	if ( ! function_exists( 'pll_get_post_translations' ) || ! function_exists( 'pll_get_post_language' ) ) {
		WP_CLI::error( 'Polylang is not active, so there are no translations to check.' );
	}

	$format   = $assoc_args['format'] ?? 'table';
	$rows     = array();
	$blocking = 0;

	foreach ( array( 'page', 'hcwa_center', 'hcwa_program', 'hcwa_report', 'hcwa_partner', 'hcwa_impact' ) as $post_type ) {
		$ids  = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'lang'             => '',
				'suppress_filters' => false,
			)
		);
		$seen = array();

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( isset( $seen[ $id ] ) ) {
				continue;
			}

			$translations = array_map( 'intval', (array) pll_get_post_translations( $id ) );
			foreach ( $translations as $tr_id ) {
				$seen[ $tr_id ] = true;
			}

			$lang = (string) pll_get_post_language( $id );
			if ( '' === $lang ) {
				$rows[] = hcwa_cli_row( $post_type, $id, get_the_title( $id ), '-', 'vi, en', 'No language set' );
				++$blocking;
				continue;
			}

			foreach ( hcwa_languages() as $want ) {
				$tr_id = $translations[ $want ] ?? 0;
				if ( $tr_id && 'publish' === get_post_status( $tr_id ) ) {
					continue;
				}
				$note   = $tr_id ? sprintf( 'Translation %d is %s', $tr_id, get_post_status( $tr_id ) ) : 'No translation';
				$rows[] = hcwa_cli_row( $post_type, $id, get_the_title( $id ), $lang, $want, $note );
				++$blocking;
			}
		}
	}

	// Little Rose and similar centers: their news is protected only through the news tag.
	$protection = hcwa_cli_protection_coverage();
	$blocking  += count( $protection );

	// Categories and news tags in one language only (a protected center's tag blocks).
	$terms     = hcwa_cli_term_coverage();
	$blocking += $terms['blocking'];

	$rows = array_merge( $rows, $protection, $terms['rows'], hcwa_cli_menu_coverage(), hcwa_cli_string_coverage() );

	$news = hcwa_cli_post_language_counts();
	WP_CLI::log( sprintf( 'News posts: %d with both languages, %d Vietnamese only, %d English only (allowed; the language notice covers them).', $news['both'], $news['vi'], $news['en'] ) );

	if ( $rows ) {
		WP_CLI\Utils\format_items( $format, $rows, array( 'area', 'id', 'title', 'language', 'missing', 'note' ) );
	}

	if ( $blocking ) {
		WP_CLI::error( sprintf( '%d page or content entries are missing a translation, or a protected center has no news tag in both languages. Launch rule: zero.', $blocking ) );
	}

	if ( $rows ) {
		WP_CLI::warning( sprintf( '%d category, news tag, menu or string gaps (see the table). Pages and content types are complete.', count( $rows ) ) );
		return;
	}

	WP_CLI::success( 'Every page, content entry, category, news tag, menu and UI string exists in Vietnamese and English.' );
}

/**
 * Categories, and the news tags centers and programs point to, that lack a version in a
 * language. The theme shows a category chip only in a language the category exists in
 * (hcwa_theme_category()), and a center or program page lists only news carrying the
 * tag's version in the page language. A tag of a center marked "No identifiable children"
 * blocks: hcwa_post_has_protected_tag() maps a tag to its Vietnamese version through the
 * Polylang link, so an English post carrying an unlinked English tag would not count as
 * that center's news.
 *
 * @return array{rows: array[], blocking: int}
 */
function hcwa_cli_term_coverage(): array {
	$rows     = array();
	$blocking = 0;
	if ( ! function_exists( 'pll_get_term_translations' ) || ! function_exists( 'pll_get_term_language' ) ) {
		return compact( 'rows', 'blocking' );
	}

	$missing = static function ( WP_Term $term ): array {
		$translations = array_map( 'intval', (array) pll_get_term_translations( $term->term_id ) );
		return array_values( array_filter( hcwa_languages(), static fn( string $lang ): bool => empty( $translations[ $lang ] ) ) );
	};

	// Categories (the seeded news, stories, events and announcements, and any staff add).
	$seen       = array();
	$categories = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	foreach ( is_array( $categories ) ? $categories : array() as $term ) {
		if ( ! $term instanceof WP_Term || isset( $seen[ $term->term_id ] ) ) {
			continue;
		}
		foreach ( (array) pll_get_term_translations( $term->term_id ) as $tr_id ) {
			$seen[ (int) $tr_id ] = true;
		}
		$lang = (string) pll_get_term_language( $term->term_id );
		foreach ( '' === $lang ? hcwa_languages() : $missing( $term ) as $want ) {
			$rows[] = hcwa_cli_row( 'category', $term->term_id, $term->name . ' (' . $term->slug . ')', '' === $lang ? '-' : $lang, $want, '' === $lang ? 'No language set' : 'No translation: the category chip and archive exist in one language only' );
		}
	}

	// News tags named by centers and programs (shared field: the Vietnamese slug).
	$owners = get_posts(
		array(
			'post_type'        => array( 'hcwa_center', 'hcwa_program' ),
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => false,
		)
	);
	$tags   = array();
	foreach ( $owners as $owner_id ) {
		$owner_id = hcwa_original_id( (int) $owner_id );
		$slug     = trim( (string) get_post_meta( $owner_id, 'hcwa_news_tag', true ) );
		if ( '' === $slug ) {
			continue;
		}
		$protected = 'hcwa_center' === get_post_type( $owner_id ) && hcwa_meta_flag_any( $owner_id, 'hcwa_no_identifiable_children' );
		if ( ! isset( $tags[ $slug ] ) || ( $protected && ! $tags[ $slug ]['protected'] ) ) {
			// One row per tag, named after a protected center when one uses it.
			$tags[ $slug ] = array(
				'owner'     => $owner_id,
				'protected' => $protected,
			);
		}
	}
	foreach ( $tags as $slug => $tag ) {
		$found = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'slug'       => (string) $slug,
				'hide_empty' => false,
				'lang'       => '',
			)
		);
		$term  = is_array( $found ) && $found && $found[0] instanceof WP_Term ? $found[0] : null;
		$owner = $tag['owner'];
		$note  = $tag['protected'] ? ' "No identifiable children" applies to this tag\'s news, so this blocks.' : '';
		if ( ! $term ) {
			$rows[]    = hcwa_cli_row( 'news tag', $owner, get_the_title( $owner ), 'vi', 'vi, en', sprintf( 'The news tag "%s" does not exist.', $slug ) . $note );
			$blocking += $tag['protected'] ? 1 : 0;
			continue;
		}
		foreach ( $missing( $term ) as $want ) {
			$rows[]    = hcwa_cli_row( 'news tag', $owner, get_the_title( $owner ), (string) pll_get_term_language( $term->term_id ), $want, sprintf( 'The news tag "%s" has no %s version linked in Polylang.', $slug, hcwa_language_name( $want ) ) . $note );
			$blocking += $tag['protected'] ? 1 : 0;
		}
	}

	return compact( 'rows', 'blocking' );
}

/**
 * One coverage row.
 *
 * @param string $area     What was checked.
 * @param int    $id       Object ID.
 * @param string $title    Title.
 * @param string $language Language of the object.
 * @param string $missing  Missing language(s).
 * @param string $note     Detail.
 * @return array
 */
function hcwa_cli_row( string $area, int $id, string $title, string $language, string $missing, string $note ): array {
	return compact( 'area', 'id', 'title', 'language', 'missing', 'note' );
}

/**
 * Centers marked "No identifiable children" (in any language) without a news tag: none of
 * their news posts would be recognised as a protected context (media-approval.php).
 *
 * @return array[]
 */
function hcwa_cli_protection_coverage(): array {
	$rows = array();
	$ids  = get_posts(
		array(
			'post_type'        => 'hcwa_center',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => false,
		)
	);
	$seen = array();
	foreach ( $ids as $id ) {
		$original = hcwa_original_id( (int) $id );
		if ( isset( $seen[ $original ] ) ) {
			continue;
		}
		$seen[ $original ] = true;
		if ( hcwa_center_news_unprotected( $original ) ) {
			$rows[] = hcwa_cli_row( 'protection', $original, get_the_title( $original ), 'vi', 'news tag', '"No identifiable children" is ticked but the news tag is empty, so this center\'s news is not protected' );
		}
	}
	return $rows;
}

/**
 * Menus: each theme location needs a VI and an EN menu (stored by Polylang in its
 * 'polylang' option under nav_menus[theme][location][lang]), and every VI item that
 * links to content needs an EN item linking to that content's translation.
 *
 * @return array[]
 */
function hcwa_cli_menu_coverage(): array {
	$rows      = array();
	$polylang  = get_option( 'polylang', array() );
	$theme     = (string) get_option( 'stylesheet' );
	$nav_menus = is_array( $polylang ) && isset( $polylang['nav_menus'][ $theme ] ) ? (array) $polylang['nav_menus'][ $theme ] : array();

	foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
		$vi_menu = (int) ( $nav_menus[ $location ]['vi'] ?? 0 );
		$en_menu = (int) ( $nav_menus[ $location ]['en'] ?? 0 );

		foreach ( array( 'vi' => $vi_menu, 'en' => $en_menu ) as $lang => $menu_id ) {
			if ( ! $menu_id ) {
				$rows[] = hcwa_cli_row( 'menu', 0, $location, $lang === 'vi' ? 'en' : 'vi', $lang, 'No menu assigned to this location' );
			}
		}
		if ( ! $vi_menu || ! $en_menu ) {
			continue;
		}

		$vi_items = (array) wp_get_nav_menu_items( $vi_menu );
		$en_items = (array) wp_get_nav_menu_items( $en_menu );

		$en_objects = array();
		$en_custom  = 0;
		foreach ( $en_items as $item ) {
			if ( 'custom' === $item->type ) {
				++$en_custom;
			} else {
				$en_objects[ $item->type . ':' . (int) $item->object_id ] = true;
			}
		}

		$vi_custom = 0;
		foreach ( $vi_items as $item ) {
			if ( 'custom' === $item->type ) {
				++$vi_custom;
				continue;
			}

			$target = 0;
			if ( 'post_type' === $item->type ) {
				$target = (int) pll_get_post( (int) $item->object_id, 'en' );
			} elseif ( 'taxonomy' === $item->type && function_exists( 'pll_get_term' ) ) {
				$target = (int) pll_get_term( (int) $item->object_id, 'en' );
			}

			if ( ! $target ) {
				$rows[] = hcwa_cli_row( 'menu item', (int) $item->ID, $item->title, 'vi', 'en', sprintf( 'Links to %s %d, which has no English translation', $item->object, (int) $item->object_id ) );
			} elseif ( ! isset( $en_objects[ $item->type . ':' . $target ] ) ) {
				$rows[] = hcwa_cli_row( 'menu item', (int) $item->ID, $item->title, 'vi', 'en', sprintf( 'The English "%s" menu has no item for %s %d', $location, $item->object, $target ) );
			}
		}

		if ( $vi_custom !== $en_custom ) {
			$rows[] = hcwa_cli_row( 'menu', $vi_menu, $location, 'vi', $vi_custom > $en_custom ? 'en' : 'vi', sprintf( 'Custom links: %d in Vietnamese, %d in English', $vi_custom, $en_custom ) );
		}
	}

	return $rows;
}

/**
 * UI strings with an empty Vietnamese or English text.
 *
 * @return array[]
 */
function hcwa_cli_string_coverage(): array {
	$rows = array();
	foreach ( hcwa_strings() as $key => $entry ) {
		foreach ( hcwa_languages() as $lang ) {
			if ( '' === trim( (string) ( $entry[ $lang ] ?? '' ) ) ) {
				$rows[] = hcwa_cli_row( 'string', 0, $key, hcwa_other_lang( $lang ), $lang, 'Empty in the dictionary' );
			}
		}
	}
	return $rows;
}

/**
 * Counts of published news posts by translation state.
 *
 * @return array{both: int, vi: int, en: int}
 */
function hcwa_cli_post_language_counts(): array {
	$counts = array(
		'both' => 0,
		'vi'   => 0,
		'en'   => 0,
	);
	$ids    = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => false,
		)
	);
	$seen   = array();

	foreach ( $ids as $id ) {
		if ( isset( $seen[ (int) $id ] ) ) {
			continue;
		}
		$translations = array();
		foreach ( (array) pll_get_post_translations( (int) $id ) as $lang => $tr_id ) {
			$seen[ (int) $tr_id ] = true;
			if ( 'publish' === get_post_status( (int) $tr_id ) ) {
				$translations[ $lang ] = true;
			}
		}
		if ( isset( $translations['vi'], $translations['en'] ) ) {
			++$counts['both'];
		} elseif ( isset( $translations['en'] ) ) {
			++$counts['en'];
		} else {
			++$counts['vi'];
		}
	}

	return $counts;
}

/**
 * Lists published content, text fields and settings that still contain draft markers.
 *
 * ## OPTIONS
 *
 * [--format=<format>]
 * : table, csv, json, yaml or count.
 * ---
 * default: table
 * ---
 *
 * ## EXAMPLES
 *
 *     wp hcwa draft-scan
 *
 * @param array $args       Positional arguments (unused).
 * @param array $assoc_args Associative arguments.
 */
function hcwa_cli_draft_scan( $args, $assoc_args ): void {
	global $wpdb;

	$format = $assoc_args['format'] ?? 'table';
	$rows   = array();
	$like   = '%' . $wpdb->esc_like( '[' ) . '%';
	$like2  = '%' . $wpdb->esc_like( '&#91;' ) . '%';
	$types  = array_merge( hcwa_guarded_post_types(), array( 'nav_menu_item' ) );
	$in     = implode( ', ', array_fill( 0, count( $types ), '%s' ) );

	// Posts: title, content, excerpt.
	$posts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT ID, post_type, post_status, post_title, post_content, post_excerpt FROM {$wpdb->posts}
			WHERE post_status IN ('publish', 'future', 'private') AND post_type IN ($in)
			AND ( post_title LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s OR post_content LIKE %s )", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			array_merge( $types, array( $like, $like, $like, $like2 ) )
		)
	);
	foreach ( (array) $posts as $post ) {
		foreach ( array( 'post_title', 'post_content', 'post_excerpt' ) as $field ) {
			if ( hcwa_contains_draft( (string) $post->$field ) ) {
				$rows[] = hcwa_cli_draft_row( $post->post_type, (int) $post->ID, $field, $post->post_status, (string) $post->$field );
			}
		}
	}

	// Our registered text fields on published posts.
	$keys = array();
	foreach ( hcwa_meta_fields() as $fields ) {
		foreach ( $fields as $key => $spec ) {
			if ( 'string' === $spec['type'] ) {
				$keys[] = $key;
			}
		}
	}
	$keys = array_values( array_unique( $keys ) );
	if ( $keys ) {
		$key_in = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );
		$metas  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT pm.post_id, pm.meta_key, pm.meta_value, p.post_type, p.post_status FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key IN ($key_in) AND p.post_status IN ('publish', 'future', 'private') AND pm.meta_value LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				array_merge( $keys, array( $like ) )
			)
		);
		foreach ( (array) $metas as $meta ) {
			if ( hcwa_contains_draft( (string) $meta->meta_value ) ) {
				$rows[] = hcwa_cli_draft_row( $meta->post_type, (int) $meta->post_id, $meta->meta_key, $meta->post_status, (string) $meta->meta_value );
			}
		}
	}

	// Category, tag and focus area names and descriptions.
	$terms = get_terms(
		array(
			'taxonomy'   => array( 'category', 'post_tag', 'hcwa_focus_area' ),
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		foreach ( array( 'name', 'description' ) as $field ) {
			if ( hcwa_contains_draft( (string) $term->$field ) ) {
				$rows[] = hcwa_cli_draft_row( $term->taxonomy, (int) $term->term_id, $field, 'term', (string) $term->$field );
			}
		}
	}

	// Settings → HCWA.
	$settings = get_option( 'hcwa_settings', array() );
	foreach ( is_array( $settings ) ? $settings : array() as $key => $value ) {
		if ( is_scalar( $value ) && hcwa_contains_draft( (string) $value ) ) {
			$rows[] = hcwa_cli_draft_row( 'setting', 0, (string) $key, 'option', (string) $value );
		}
	}

	if ( ! $rows ) {
		WP_CLI::success( 'No draft markers in published content, fields or settings.' );
		return;
	}

	WP_CLI\Utils\format_items( $format, $rows, array( 'type', 'id', 'field', 'status', 'excerpt' ) );
	WP_CLI::error( sprintf( '%d places still contain draft markers.', count( $rows ) ) );
}

/**
 * One draft-scan row with a short excerpt around the first marker.
 *
 * @param string $type   Post type, taxonomy or 'setting'.
 * @param int    $id     Object ID.
 * @param string $field  Field name.
 * @param string $status Post status or kind.
 * @param string $text   Field text.
 * @return array
 */
function hcwa_cli_draft_row( string $type, int $id, string $field, string $status, string $text ): array {
	$plain = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
	$pos   = mb_strpos( $plain, '[' );
	$start = false === $pos ? 0 : max( 0, $pos - 20 );

	return array(
		'type'    => $type,
		'id'      => $id,
		'field'   => $field,
		'status'  => $status,
		'excerpt' => mb_substr( $plain, $start, 80 ),
	);
}

WP_CLI::add_command( 'hcwa coverage', 'hcwa_cli_coverage' );
WP_CLI::add_command( 'hcwa draft-scan', 'hcwa_cli_draft_scan' );
