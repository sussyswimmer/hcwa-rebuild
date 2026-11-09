<?php
/**
 * WP-CLI commands (loaded only under WP-CLI):
 *
 *   wp hcwa coverage     Every published page, center, program, report, partner and
 *                        impact figure, category, center or program news tag, menu and UI
 *                        string missing a VI or EN counterpart, and every center marked "No
 *                        identifiable children" without a news tag. Exits non-zero when pages
 *                        or content types are missing one, such a center has no news tag, a
 *                        protected tag has no linked version in both languages or is not the
 *                        Vietnamese tag, a post or tag looks like Little Rose but is not
 *                        protected, or protect-little-rose has never been applied. Categories
 *                        with posts outside the four sections (hcwa_category_slugs()),
 *                        legacy story categories included, are listed as warnings. Get_terms()
 *                        is called with 'lang' => '' so Polylang does not filter by language
 *                        (polylang src/crud-terms.php:192-195).
 *   wp hcwa protect-little-rose
 *                        News posts (every language and status) that look like Little Rose
 *                        news (title, slug, tags or text name a protected center or a tag in
 *                        hcwa_little_rose_aliases()) but are not protected. --apply marks them,
 *                        and every language version, "No identifiable children" and records
 *                        the run (hcwa_little_rose_review_pending()).
 *   wp hcwa media-scrub  Removes camera metadata (GPS included) from every file of every
 *                        image attachment, losslessly and under the same names
 *                        (media-privacy.php), lists the attachments whose files carried a
 *                        location so HCWA can review those photos, and lists image files in
 *                        uploads that no attachment references. --dry-run changes nothing.
 *   wp hcwa draft-scan   Published content, our text fields, Yoast SEO texts and Settings →
 *                        HCWA values that still contain draft markers ([DRAFT: …], [N],
 *                        [year] …). Exits non-zero when anything is found.
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

	// Little Rose and similar centers: news tags, imported posts and tags outside the protection.
	$protection = array_merge( hcwa_cli_protection_coverage(), hcwa_cli_little_rose_rows() );
	$blocking  += count( $protection );

	// Categories and news tags in one language only (a protected center's tag blocks).
	$terms     = hcwa_cli_term_coverage();
	$blocking += $terms['blocking'];

	$rows = array_merge( $rows, $protection, $terms['rows'], hcwa_cli_port_field_coverage(), hcwa_cli_menu_coverage(), hcwa_cli_string_coverage() );

	$news = hcwa_cli_post_language_counts();
	WP_CLI::log( sprintf( 'News posts: %d with both languages, %d Vietnamese only, %d English only (allowed; the language notice covers them).', $news['both'], $news['vi'], $news['en'] ) );

	if ( $rows ) {
		WP_CLI\Utils\format_items( $format, $rows, array( 'area', 'id', 'title', 'language', 'missing', 'note' ) );
	}

	if ( $blocking ) {
		WP_CLI::error( sprintf( '%d page or content entries are missing a translation, or Little Rose news is not fully protected (see the protection, news tag and little rose rows). Launch rule: zero.', $blocking ) );
	}

	if ( $rows ) {
		WP_CLI::warning( sprintf( '%d category, news tag, menu or string gaps (see the table). Pages and content types are complete.', count( $rows ) ) );
		return;
	}

	WP_CLI::success( 'Every page, content entry, category, news tag, menu and UI string exists in Vietnamese and English.' );
}

/**
 * The fields the port added (11-WP-PORT.md 3.2), as warning rows, never blocking: a center
 * without a unit key (a stored 'blue' prints in HCWA blue with no door art), a center
 * without a short name in its language, a program still on the old one-center field, and a
 * focus area term outside the four the prototype knows (it renders without a tile). Also
 * logs one line per center and program with its unit key, short name, header picture and
 * centers.
 *
 * @return array[]
 */
function hcwa_cli_port_field_coverage(): array {
	$rows  = array();
	$units = hcwa_unit_keys();
	$query = static fn( string $type ): array => array_map(
		'intval',
		get_posts(
			array(
				'post_type'        => $type,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'lang'             => '',
				'orderby'          => 'menu_order ID',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		)
	);
	$lang_of = static fn( int $id ): string => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $id ) : 'vi';

	foreach ( $query( 'hcwa_center' ) as $id ) {
		$accent = (string) get_post_meta( $id, 'hcwa_accent', true );
		$short  = (string) get_post_meta( $id, 'hcwa_short_name', true );
		$still  = (string) get_post_meta( $id, 'hcwa_header_still', true );
		WP_CLI::log( sprintf( 'Center %d (%s): unit key %s, short name %s, header picture %s%s.', $id, $lang_of( $id ), '' !== $accent ? $accent : '-', '' !== $short ? '"' . $short . '"' : '-', '' !== $still ? $still : '- (plain header)', hcwa_center_is_protected( $id ) ? ', protected' : '' ) );
		if ( ! in_array( $accent, $units, true ) ) {
			$rows[] = hcwa_cli_row( 'center', $id, get_the_title( $id ), $lang_of( $id ), 'unit key', '' !== $accent ? sprintf( 'Unit "%s" is not one of %s: HCWA blue, no door art.', $accent, implode( ', ', $units ) ) : 'No unit key: HCWA blue, no door art.' );
		}
		if ( '' === trim( $short ) ) {
			$rows[] = hcwa_cli_row( 'center', $id, get_the_title( $id ), $lang_of( $id ), 'short name', 'No short name: the corridor index and door chips use the full name.' );
		}
	}

	foreach ( $query( 'hcwa_program' ) as $id ) {
		$ids   = hcwa_id_list( get_post_meta( $id, 'hcwa_center_ids', true ) );
		$old   = (int) get_post_meta( $id, 'hcwa_center_id', true );
		$still = (string) get_post_meta( $id, 'hcwa_header_still', true );
		WP_CLI::log( sprintf( 'Program %d (%s): centers %s, header picture %s.', $id, $lang_of( $id ), $ids ? implode( ', ', $ids ) : '-', '' !== $still ? $still : '- (plain header)' ) );
		if ( ! $ids && $old > 0 ) {
			$rows[] = hcwa_cli_row( 'program', $id, get_the_title( $id ), $lang_of( $id ), 'centers', sprintf( 'Still on the old one-center field (center %d): copy it into Centers.', $old ) );
		}
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'hcwa_focus_area',
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		$key = (string) preg_replace( '/-(vi|en)$/', '', (string) $term->slug );
		if ( ! in_array( $key, hcwa_area_keys(), true ) ) {
			$rows[] = hcwa_cli_row( 'focus area', (int) $term->term_id, (string) $term->name, function_exists( 'pll_get_term_language' ) ? (string) pll_get_term_language( (int) $term->term_id ) : '-', 'area', sprintf( 'Slug "%s" is not one of %s: it renders without a tile.', $term->slug, implode( ', ', hcwa_area_keys() ) ) );
		}
	}

	return $rows;
}

/**
 * Categories, and the news tags centers and programs point to, that lack a version in a
 * language. The theme shows a category chip only in a language the category exists in
 * (hcwa_theme_category()), and a center or program page lists only news carrying the
 * tag's version in the page language. A tag of a center marked "No identifiable children",
 * or of a program it runs, blocks: hcwa_no_identifiable_news_tags() protects the tag's
 * Polylang translation group, so an English post carrying an unlinked English tag would not
 * count as that center's news. Such a tag must also be the Vietnamese one (shared fields
 * store VI slugs, and staff reading the field expect it).
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
		$group = array( $term );
		foreach ( (array) pll_get_term_translations( $term->term_id ) as $tr_id ) {
			$seen[ (int) $tr_id ] = true;
			$other                = (int) $tr_id !== (int) $term->term_id ? get_term( (int) $tr_id, 'category' ) : null;
			if ( $other instanceof WP_Term ) {
				$group[] = $other;
			}
		}
		$lang = (string) pll_get_term_language( $term->term_id );
		foreach ( '' === $lang ? hcwa_languages() : $missing( $term ) as $want ) {
			$rows[] = hcwa_cli_row( 'category', $term->term_id, $term->name . ' (' . $term->slug . ')', '' === $lang ? '-' : $lang, $want, '' === $lang ? 'No language set' : 'No translation: the category chip and archive exist in one language only' );
		}
		$section = hcwa_cli_category_section_row( $group, '' === $lang ? '-' : $lang );
		if ( $section ) {
			$rows[] = $section;
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
		$protected = hcwa_image_context_no_identifiable( $owner_id );
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
		$lang = (string) pll_get_term_language( $term->term_id );
		if ( $tag['protected'] && 'vi' !== $lang ) {
			$vi_id     = function_exists( 'pll_get_term' ) ? (int) pll_get_term( $term->term_id, 'vi' ) : 0;
			$vi        = $vi_id ? get_term( $vi_id, 'post_tag' ) : null;
			$rows[]    = hcwa_cli_row( 'news tag', $owner, get_the_title( $owner ), '' === $lang ? '-' : $lang, 'vi', sprintf( 'The news tag "%s" is not the Vietnamese tag. Enter the Vietnamese tag\'s slug%s.', $slug, $vi instanceof WP_Term ? ' ("' . $vi->slug . '")' : '' ) . $note );
			$blocking += 1;
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
 * Blocking rows for Little Rose news outside the protection: posts that look like Little
 * Rose news but are not protected (hcwa_cli_little_rose_scan()), post tags named like a
 * protected tag that sit outside the protected set (new posts carrying them would not be
 * protected: merge them into the protected tag, or add their slug to the
 * hcwa_protected_tag_slugs filter), and a live site where protect-little-rose has never
 * been applied.
 *
 * @return array[]
 */
function hcwa_cli_little_rose_rows(): array {
	$rows = array();
	foreach ( hcwa_cli_little_rose_scan( array( 'publish', 'private', 'future' ) ) as $id => $alias ) {
		$rows[] = hcwa_cli_row( 'little rose', $id, get_the_title( $id ), (string) ( function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $id ) : '' ), 'protection', sprintf( 'Mentions "%s" but is not marked "No identifiable children". Run wp hcwa protect-little-rose --apply, or have an editor tick it on the post.', trim( $alias ) ) );
	}

	$protected = hcwa_no_identifiable_news_tags();
	$aliases   = hcwa_little_rose_aliases();
	$tags      = get_terms(
		array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	foreach ( is_array( $tags ) ? $tags : array() as $term ) {
		if ( ! $term instanceof WP_Term || in_array( (int) $term->term_id, $protected, true ) ) {
			continue;
		}
		$alias = hcwa_mentions( $term->name . ' ' . $term->slug, $aliases );
		if ( '' !== $alias ) {
			$rows[] = hcwa_cli_row( 'little rose', $term->term_id, $term->name . ' (' . $term->slug . ')', '-', 'protection', sprintf( 'Post tag named like "%s" but outside the protected tags: news carrying only this tag is not protected. Merge it into the protected tag, or add its slug to the hcwa_protected_tag_slugs filter.', trim( $alias ) ) );
		}
	}

	if ( hcwa_little_rose_review_pending() ) {
		$rows[] = hcwa_cli_row( 'little rose', 0, 'wp hcwa protect-little-rose', '-', 'run', 'Never applied on this live site: every news photo needs "Approved for web" until it has run (hcwa_little_rose_review_pending()).' );
	}
	return $rows;
}

/**
 * News posts (every language) that look like Little Rose news but are not a
 * no-identifiable context: their title, slug, tag names or text contain one of
 * hcwa_little_rose_aliases() (accents ignored, whole words).
 *
 * @param string[] $statuses Post statuses to scan.
 * @return array<int, string> Post ID => the alias found.
 */
function hcwa_cli_little_rose_scan( array $statuses ): array {
	$aliases = hcwa_little_rose_aliases();
	$found   = array();
	$posts   = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => $statuses,
			'numberposts'      => -1,
			'lang'             => '',
			'suppress_filters' => false,
		)
	);
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post || hcwa_image_context_no_identifiable( $post ) ) {
			continue;
		}
		$terms = get_the_terms( $post->ID, 'post_tag' );
		$names = is_array( $terms ) ? implode( ' ', wp_list_pluck( $terms, 'name' ) ) : '';
		$alias = hcwa_mentions( $post->post_title . ' ' . $post->post_name . ' ' . $names . ' ' . $post->post_content, $aliases );
		if ( '' !== $alias ) {
			$found[ $post->ID ] = $alias;
		}
	}
	return $found;
}

/**
 * Marks imported Little Rose news "No identifiable children".
 *
 * Lists every news post (any language, any status) that looks like Little Rose news but is
 * not protected yet: its title, slug, tags or text name a protected center, a protected
 * tag or a word in hcwa_little_rose_aliases(). Review the list with HCWA (01-AUDIT names the
 * Little Rose posts), then run again with --apply. Posts the scan misses can be added with
 * --ids. A marked post refuses photos marked "Identifiable child" in every language version.
 *
 * ## OPTIONS
 *
 * [--apply]
 * : Mark the listed posts and record the run. Without it nothing changes.
 *
 * [--ids=<ids>]
 * : Extra post IDs to mark, comma-separated.
 *
 * [--format=<format>]
 * : table, csv, json, yaml or count.
 * ---
 * default: table
 * ---
 *
 * ## EXAMPLES
 *
 *     wp hcwa protect-little-rose
 *     wp hcwa protect-little-rose --apply --ids=812,1044
 *
 * @param array $args       Positional arguments (unused).
 * @param array $assoc_args Associative arguments.
 */
function hcwa_cli_protect_little_rose( $args, $assoc_args ): void {
	$apply  = ! empty( $assoc_args['apply'] );
	$format = $assoc_args['format'] ?? 'table';
	$found  = hcwa_cli_little_rose_scan( array( 'publish', 'private', 'future', 'draft', 'pending' ) );
	foreach ( hcwa_id_list( $assoc_args['ids'] ?? '' ) as $id ) {
		if ( 'post' === get_post_type( $id ) && ! isset( $found[ $id ] ) ) {
			$found[ $id ] = '--ids';
		}
	}

	$rows = array();
	foreach ( $found as $id => $reason ) {
		$versions = hcwa_post_translation_ids( (int) $id );
		if ( $apply ) {
			foreach ( $versions as $version ) {
				update_post_meta( $version, 'hcwa_no_identifiable_children', true );
			}
		}
		$rows[] = array(
			'id'       => (int) $id,
			'title'    => get_the_title( (int) $id ),
			'status'   => (string) get_post_status( (int) $id ),
			'found'    => trim( $reason ),
			'versions' => implode( ', ', $versions ),
		);
	}
	if ( $rows ) {
		WP_CLI\Utils\format_items( $format, $rows, array( 'id', 'title', 'status', 'found', 'versions' ) );
	}

	if ( ! $apply ) {
		WP_CLI::warning( sprintf( 'Dry run: %d post(s) would be marked "No identifiable children". Review them with HCWA, then run again with --apply.', count( $rows ) ) );
		return;
	}
	update_option( 'hcwa_little_rose_review', gmdate( 'Y-m-d' ), false );
	WP_CLI::success( sprintf( '%d post(s) marked "No identifiable children" in every language version. Run wp hcwa coverage next.', count( $rows ) ) );
}

/**
 * Removes camera metadata (GPS included) from the image files already in the library.
 *
 * Every image attachment's main file, original_image, source_image, sizes and image-editor
 * backups are cleaned in place, losslessly and under the same names, so old posts and the
 * redirect map keep working (`wp media regenerate` does not do this: the kept original is
 * not touched). Attachments whose files carried a location are listed so HCWA can review
 * those photos. Without --ids, image files in uploads that no attachment references are
 * listed (and cleaned) too; delete the ones nobody needs. Run it on a copy first.
 *
 * ## OPTIONS
 *
 * [--dry-run]
 * : Only report.
 *
 * [--ids=<ids>]
 * : Only these attachment IDs, comma-separated.
 *
 * [--format=<format>]
 * : table, csv, json, yaml or count.
 * ---
 * default: table
 * ---
 *
 * ## EXAMPLES
 *
 *     wp hcwa media-scrub --dry-run
 *     wp hcwa media-scrub
 *
 * @param array $args       Positional arguments (unused).
 * @param array $assoc_args Associative arguments.
 */
function hcwa_cli_media_scrub( $args, $assoc_args ): void {
	$write   = empty( $assoc_args['dry-run'] );
	$format  = $assoc_args['format'] ?? 'table';
	$only    = hcwa_id_list( $assoc_args['ids'] ?? '' );
	$uploads = wp_get_upload_dir();
	$basedir = untrailingslashit( (string) ( $uploads['basedir'] ?? '' ) );
	$ids     = $only ? $only : get_posts(
		array(
			'post_type'        => 'attachment',
			'post_mime_type'   => 'image',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => false,
		)
	);

	$rows       = array();
	$known      = array();
	$failed     = 0;
	$located    = array();
	$scrub_file = static function ( string $path, int $id ) use ( $write, $basedir, &$rows, &$failed, &$located ): void {
		$result = hcwa_media_strip_file( $path, $write );
		if ( $id && ! $result['changed'] && ! $result['gps'] && $result['ok'] ) {
			return;
		}
		if ( $result['gps'] ) {
			$located[ $id ] = true;
		}
		$failed += $result['ok'] ? 0 : 1;
		$done    = $result['changed'] ? ( $write ? 'cleaned' : 'would clean' ) : 'no metadata';
		$rows[]  = array(
			'attachment' => $id ? (string) $id : 'none (not referenced by any attachment)',
			'file'       => 0 === strpos( $path, $basedir . '/' ) ? substr( $path, strlen( $basedir ) + 1 ) : $path,
			'location'   => $result['gps'] ? 'yes' : 'no',
			'result'     => $result['ok'] ? $done : 'could not clean',
		);
	};

	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		foreach ( hcwa_attachment_files( $id ) as $path ) {
			if ( isset( $known[ $path ] ) ) {
				continue; // Shared by Polylang media translations.
			}
			$known[ $path ] = true;
			$scrub_file( $path, $id );
		}
	}

	$orphans = 0;
	if ( ! $only && '' !== $basedir && is_dir( $basedir ) ) {
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $basedir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			$path = $file->getPathname();
			if ( isset( $known[ $path ] ) || ! preg_match( '/\.(?:jpe?g|png|webp|gif|heic|heif|avif|tiff?)$/i', $path ) ) {
				continue;
			}
			++$orphans;
			$scrub_file( $path, 0 );
		}
	}

	if ( $rows ) {
		WP_CLI\Utils\format_items( $format, $rows, array( 'attachment', 'file', 'location', 'result' ) );
	}
	unset( $located[0] );
	if ( $located ) {
		WP_CLI::warning( sprintf( 'Attachments whose files carried a location (review these photos with HCWA): %s', implode( ', ', array_keys( $located ) ) ) );
	}
	if ( $orphans ) {
		WP_CLI::warning( sprintf( '%d image file(s) in uploads belong to no attachment and stay reachable by URL. Delete the ones nobody needs.', $orphans ) );
	}
	if ( $failed ) {
		WP_CLI::error( sprintf( '%d file(s) carry a location that could not be removed (HEIC, AVIF, TIFF or GIF, or no write access). Replace them with JPEG copies.', $failed ) );
	}
	WP_CLI::success( $write ? 'Camera metadata removed from every image file.' : 'Dry run: nothing changed.' );
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
 * The printed column says how the live site prints the field: raw when it prints without
 * hcwa_draft_text() (a post title, a menu label, the body of a post, report or ordinary
 * page through the_content), guarded when it prints only through hcwa_draft_text(), which
 * prints nothing for draft text on production (block attributes of sections pages, center
 * and program fields, center body paragraphs, Settings → HCWA). After a database push to
 * production, unpublish the raw rows (11-WP-PORT.md 5.9); guarded rows may stay published.
 *
 * ## OPTIONS
 *
 * [--format=<format>]
 * : table, csv, json, yaml, count, or ids (the post IDs of the rows, space-separated).
 * ---
 * default: table
 * ---
 *
 * [--printed=<printed>]
 * : Only rows printed this way.
 * ---
 * options:
 *   - raw
 *   - guarded
 * ---
 *
 * ## EXAMPLES
 *
 *     wp hcwa draft-scan
 *     wp post update $(wp hcwa draft-scan --printed=raw --format=ids) --post_status=draft
 *
 * @param array $args       Positional arguments (unused).
 * @param array $assoc_args Associative arguments.
 */
function hcwa_cli_draft_scan( $args, $assoc_args ): void {
	global $wpdb;

	$format  = $assoc_args['format'] ?? 'table';
	$printed = $assoc_args['printed'] ?? '';
	$rows    = array();
	$like    = '%' . $wpdb->esc_like( '[' ) . '%';
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
				$rows[] = hcwa_cli_draft_row( $post->post_type, (int) $post->ID, $field, $post->post_status, (string) $post->$field, hcwa_cli_printed( (string) $post->post_type, $field, (string) $post->post_content ) );
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
	// Yoast SEO's per-post texts (printed in <title>, the meta description and share previews).
	$keys = array_values( array_unique( array_merge( $keys, array_keys( hcwa_seo_text_meta_keys() ) ) ) );
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
				$rows[] = hcwa_cli_draft_row( $meta->post_type, (int) $meta->post_id, $meta->meta_key, $meta->post_status, (string) $meta->meta_value, 'guarded' );
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
				// Focus areas print through hcwa_draft_text() (the work sheet, the hub chips); category and tag names print as they are.
				$rows[] = hcwa_cli_draft_row( $term->taxonomy, (int) $term->term_id, $field, 'term', (string) $term->$field, 'hcwa_focus_area' === $term->taxonomy ? 'guarded' : 'raw' );
			}
		}
	}

	// Settings → HCWA, and Yoast SEO's title templates and social defaults.
	foreach ( array( 'hcwa_settings', 'wpseo_titles', 'wpseo_social' ) as $option ) {
		$settings = get_option( $option, array() );
		foreach ( is_array( $settings ) ? $settings : array() as $key => $value ) {
			if ( is_scalar( $value ) && hcwa_contains_draft( (string) $value ) ) {
				// hcwa_setting_l() and the theme drop draft settings on production; so does seo.php for Yoast's texts.
				$rows[] = hcwa_cli_draft_row( 'setting', 0, ( 'hcwa_settings' === $option ? '' : $option . '.' ) . $key, 'option', (string) $value, 'guarded' );
			}
		}
	}

	if ( in_array( $printed, array( 'raw', 'guarded' ), true ) ) {
		$rows = array_values( array_filter( $rows, static fn( array $row ): bool => $printed === $row['printed'] ) );
	}

	if ( 'ids' === $format ) {
		$ids = array_values( array_unique( array_filter( array_map( static fn( array $row ): int => in_array( $row['status'], array( 'term', 'option' ), true ) ? 0 : (int) $row['id'], $rows ) ) ) );
		if ( $ids ) {
			WP_CLI::line( implode( ' ', $ids ) );
		}
		return;
	}

	if ( ! $rows ) {
		WP_CLI::success( '' !== $printed ? sprintf( 'No %s draft markers in published content, fields or settings.', $printed ) : 'No draft markers in published content, fields or settings.' );
		return;
	}

	WP_CLI\Utils\format_items( $format, $rows, array( 'type', 'id', 'field', 'status', 'printed', 'excerpt' ) );
	WP_CLI::error( sprintf( '%d places still contain draft markers.', count( $rows ) ) );
}

/**
 * How the live site prints a post field (see hcwa_cli_draft_scan()).
 *
 * @param string $post_type Post type.
 * @param string $field     post_title, post_content or post_excerpt.
 * @param string $content   The post's content (a sections page holds hcwa/sections).
 */
function hcwa_cli_printed( string $post_type, string $field, string $content ): string {
	if ( 'post_content' !== $field ) {
		return 'raw';
	}
	if ( in_array( $post_type, array( 'hcwa_center', 'hcwa_program' ), true ) ) {
		return 'guarded'; // Center bodies print through hcwa_center_body(); program bodies are not printed.
	}
	if ( 'page' === $post_type && false !== strpos( $content, '<!-- wp:hcwa/sections' ) ) {
		return 'guarded'; // hcwa_render_sections(): our blocks print attributes through hcwa_draft_text().
	}
	return 'raw';
}

/**
 * A coverage row for a category with posts that is not one of the four sections
 * (hcwa_category_slugs()): its posts get no chip and no section label, and only the story
 * categories hcwa_story_category_slugs() knows get the story photo gate and the "names
 * changed" note. Legacy story categories (our-stories, chuyen-cua-chung-toi) get their own
 * note: Phase 3 moves their posts into stories / cau-chuyen and redirects the old archive.
 * Informational (a warning, not a launch blocker): the posts keep working where they are.
 *
 * @param WP_Term[] $group    The category and its translations.
 * @param string    $language Language of the first term, '-' for none.
 * @return array|null Row, or null when the category is a section or has no posts.
 */
function hcwa_cli_category_section_row( array $group, string $language ): ?array {
	$slugs = array();
	$count = 0;
	foreach ( $group as $term ) {
		$slugs[] = (string) $term->slug;
		$count  += (int) ( $term->count ?? 0 );
	}
	$term = $group[0];
	if ( 0 === $count ) {
		return null;
	}
	$key    = hcwa_category_key( $slugs );
	$plain  = array_map( static fn( string $slug ): string => (string) preg_replace( '/-(vi|en)$/', '', $slug ), $slugs );
	$seeded = array_merge( ...array_values( hcwa_category_slugs() ) );
	if ( '' !== $key && array_intersect( $plain, $seeded ) ) {
		return null;
	}
	$title = $term->name . ' (' . $term->slug . ')';
	if ( 'stories' === $key ) {
		return hcwa_cli_row( 'category', (int) $term->term_id, $title, $language, 'section', sprintf( 'Legacy story category, %d post(s): photo gate and "names changed" note apply. Phase 3: move the posts to stories / cau-chuyen after the safeguarding review, then 301 this archive.', $count ) );
	}
	return hcwa_cli_row( 'category', (int) $term->term_id, $title, $language, 'section', sprintf( 'Not one of the four sections (news, stories, events, announcements), %d post(s): no chip, no section label, and no story photo gate. Phase 3: move the posts to a section and 301 this archive, or add the slug to the hcwa_story_category_slugs filter if they are stories.', $count ) );
}

/**
 * One draft-scan row with a short excerpt around the first marker.
 *
 * @param string $type   Post type, taxonomy or 'setting'.
 * @param int    $id     Object ID.
 * @param string $field  Field name.
 * @param string $status Post status or kind.
 * @param string $text   Field text.
 * @param string $printed raw or guarded.
 * @return array
 */
function hcwa_cli_draft_row( string $type, int $id, string $field, string $status, string $text, string $printed = 'raw' ): array {
	$plain = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
	$pos   = mb_strpos( $plain, '[' );
	$start = false === $pos ? 0 : max( 0, $pos - 20 );

	return array(
		'type'    => $type,
		'id'      => $id,
		'field'   => $field,
		'status'  => $status,
		'printed' => $printed,
		'excerpt' => mb_substr( $plain, $start, 80 ),
	);
}

WP_CLI::add_command( 'hcwa coverage', 'hcwa_cli_coverage' );
WP_CLI::add_command( 'hcwa draft-scan', 'hcwa_cli_draft_scan' );
WP_CLI::add_command( 'hcwa protect-little-rose', 'hcwa_cli_protect_little_rose' );
WP_CLI::add_command( 'hcwa media-scrub', 'hcwa_cli_media_scrub' );
