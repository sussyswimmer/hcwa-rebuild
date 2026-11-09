<?php
/**
 * Seeds a local or staging HCWA WordPress instance from content/draft/*.json and the
 * theme's page patterns. `scripts/wp-local.sh seed <name>` runs it through WP-CLI twice:
 *
 *   wp --user=admin eval-file scripts/wp-seed.php languages <repo>
 *   wp --user=admin eval-file scripts/wp-seed.php content <repo> <seed-urls.json>
 *
 * languages  Polylang languages vi (default, locale vi) and en (locale en_GB), Polylang
 *            settings from 07-BILINGUAL, pretty permalinks, site title and time zone.
 * content    Focus areas and news categories in both languages; a page for every logical
 *            page key (bodies from the hcwa-2026 page patterns where they exist, otherwise
 *            the page header plus a [DRAFT: …] paragraph); centers, programs and their
 *            funders from work.json; impact figures (all unverified); news posts; static
 *            front and posts pages; menus per language; Settings → HCWA defaults without
 *            donation details; and the list of seeded URLs for scripts/wp-smoke.py.
 *
 * Polylang 3.9.1 API used (checked in .wp-local/ref/src/polylang):
 * - WP-CLI counts as admin (PLL_ADMIN in src/class-polylang.php), so PLL() is a PLL_Admin
 *   even before any language exists. Languages: PLL()->model->languages->add()
 *   (src/Model/Languages.php). Settings: PLL()->model->options->set() and save()
 *   (src/Options/Options.php, keys in src/Options/Business/).
 * - Polylang wires its post and term hooks (PLL_Base::init, modules/sync/load.php) only
 *   when languages exist at startup, which is why content is a second WP-CLI run.
 * - Menus per language live in the Polylang option nav_menus[theme][location][lang]
 *   (src/Options/Business/Nav_Menus.php). The front end swaps them into the theme's
 *   nav_menu_locations, but only for locations already in that theme mod
 *   (src/frontend/frontend-nav-menu.php), so the theme mod is filled with the VI menus.
 * - Polylang free gives every post its own slug, so a post slug wanted in both languages
 *   gets the English name as its English slug (hcwa_seed_cpt_slugs()). Term slugs used
 *   twice get a "-<lang>" suffix (src/term-slug.php), so Vietnamese focus areas are
 *   seeded as "<slug>-vi" from the start (wp/CONTRACT.md).
 * - "Custom fields" sync stays off (hcwa_seed_polylang_options()). With it on, Polylang
 *   syncs every public meta key (src/modules/sync/sync-post-metas.php:51), including
 *   per-language text such as core's footnotes. hcwa-core's pll_copy_post_metas filter
 *   adds the shared fields whatever the option says (sync-post-metas.php:78).
 * - Polylang copies every unprotected custom field into a translation made with "+"
 *   (sync-post-metas.php:51-58, is_protected_meta()), so the seed's bookkeeping key is the
 *   protected _hcwa_seed_id. Both translations carry the same value; the post's language
 *   tells them apart.
 *
 * Idempotent: everything seeded carries _hcwa_seed_id (post or term meta) and is updated in
 * place on the next run, never duplicated. Content made by hand is left alone; seeded
 * posts are reset to the seed. Refuses to run on production.
 *
 * Draft copy: fields marked "draft" in content/draft/*.json, and the bits of prototype copy
 * below, are wrapped in [DRAFT: …], so the production publish guard and
 * `wp hcwa draft-scan` catch them. No statistic, partner, quote or donation detail is
 * added that is not in content/draft/.
 *
 * @package hcwa-scripts
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Entry point.
 *
 * @param array $argv Positional arguments: stage, repository root, manifest path.
 */
function hcwa_seed_main( array $argv ): void {
	$stage    = (string) ( $argv[0] ?? 'all' );
	$repo     = rtrim( (string) ( $argv[1] ?? ( getenv( 'HCWA_REPO' ) ?: dirname( __DIR__ ) ) ), '/' );
	$manifest = (string) ( $argv[2] ?? '' );

	if ( ! in_array( $stage, array( 'languages', 'content', 'all' ), true ) ) {
		WP_CLI::error( "Unknown stage '$stage'. Use languages, content or all." );
	}
	if ( 'production' === wp_get_environment_type() ) {
		WP_CLI::error( 'This site is set to production. The seed writes draft content and is only for local or staging sites.' );
	}

	hcwa_seed_require_code();
	hcwa_seed_repo( $repo );
	$data = array(
		'site'   => hcwa_seed_json( $repo, 'site' ),
		'work'   => hcwa_seed_json( $repo, 'work' ),
		'impact' => hcwa_seed_json( $repo, 'impact' ),
		'news'   => hcwa_seed_json( $repo, 'news' ),
		'world'  => hcwa_seed_json( $repo, 'world' ),
	);

	if ( 'content' !== $stage ) {
		$had_languages = hcwa_seed_has_languages();
		hcwa_seed_languages( $data['site'] );
		if ( 'all' === $stage && ! $had_languages ) {
			WP_CLI::warning( 'Languages were just created. Polylang only wires its content hooks at startup, so run the content stage in a new WP-CLI run (scripts/wp-local.sh seed does both).' );
			return;
		}
	}

	if ( 'languages' !== $stage ) {
		if ( ! hcwa_seed_has_languages() ) {
			WP_CLI::error( 'Polylang has no vi and en languages yet: run the languages stage first.' );
		}
		hcwa_seed_content( $data, $manifest );
	}
}

/**
 * Stops unless Polylang and hcwa-core are active; warns when the theme is not.
 */
function hcwa_seed_require_code(): void {
	foreach ( array( 'PLL', 'pll_insert_post', 'pll_set_post_language', 'pll_save_post_translations', 'pll_insert_term', 'pll_set_term_language', 'pll_save_term_translations', 'pll_get_post_language', 'pll_get_term_language', 'pll_home_url' ) as $function ) {
		if ( ! function_exists( $function ) ) {
			WP_CLI::error( "Polylang is not active ($function() is missing): wp plugin activate polylang" );
		}
	}
	foreach ( array( 'hcwa_t_in', 'hcwa_page_paths', 'hcwa_page_path', 'hcwa_page_keys', 'hcwa_attachment_files', 'hcwa_image_bytes_have_gps' ) as $function ) {
		if ( ! function_exists( $function ) ) {
			WP_CLI::error( "hcwa-core is not active ($function() is missing): wp plugin activate hcwa-core" );
		}
	}
	if ( 'hcwa-2026' !== get_stylesheet() ) {
		WP_CLI::warning( 'The hcwa-2026 theme is not active, so its page patterns are not available: wp theme activate hcwa-2026' );
	}
}

/**
 * One file from content/draft/ as an array.
 *
 * @param string $repo Repository root.
 * @param string $name File name without .json.
 */
function hcwa_seed_json( string $repo, string $name ): array {
	$file = $repo . '/content/draft/' . $name . '.json';
	if ( ! is_readable( $file ) ) {
		WP_CLI::error( "Cannot read $file. Pass the repository root as the second argument." );
	}
	$data = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $data ) ) {
		WP_CLI::error( "$file is not valid JSON: " . json_last_error_msg() );
	}
	return $data;
}

/**
 * The repository root, set once by hcwa_seed_main().
 *
 * @param string|null $set Root to remember.
 */
function hcwa_seed_repo( ?string $set = null ): string {
	static $repo = '';
	if ( null !== $set ) {
		$repo = $set;
	}
	return $repo;
}

/**
 * The lit header still for a center or program: 'header-<id>' when the prototype has rendered
 * a still of that name (the theme's generated stills.json once the export exists, otherwise the
 * prototype's poster files), else '' (the template prints the plain placeholder). Never one for a
 * protected center (docs/plan/11-WP-PORT.md 3.4: Little Rose has no header still).
 *
 * @param string $id        Center or program id from work.json.
 * @param bool   $protected Whether the center is protected.
 */
function hcwa_seed_header_still( string $id, bool $protected = false ): string {
	if ( $protected ) {
		return '';
	}
	$name   = 'header-' . sanitize_title( $id );
	$repo   = hcwa_seed_repo();
	$stills = $repo . '/wp/themes/hcwa-2026/assets/generated/stills.json';
	if ( is_readable( $stills ) ) {
		$data = json_decode( (string) file_get_contents( $stills ), true );
		$list = is_array( $data ) ? ( $data['stills'] ?? $data ) : array();
		return is_array( $list ) && isset( $list[ $name ] ) ? $name : '';
	}
	return glob( $repo . '/prototype/public/posters/' . $name . '-*' ) ? $name : '';
}

// ------------------------------------------------------------------------- text helpers

/**
 * Wraps draft text in [DRAFT: …], always and whole, so it prints back exactly as the
 * prototype prints it: hcwa_draft_text() unwraps one whole-string wrapper, so
 * "[Story title]" is stored as "[DRAFT: [Story title]]" and shows "[Story title]", and the
 * site.json registration "[DRAFT: legal status …]" is stored wrapped once more and shows
 * as written there (11-WP-PORT.md 2.1 rule 7, 3.3).
 *
 * @param string $text Text.
 */
function hcwa_seed_draft( string $text ): string {
	$text = trim( $text );
	return '' === $text ? '' : '[DRAFT: ' . $text . ']';
}

/**
 * Text in one language from a {vi, en, draft?} field of content/draft/*.json, wrapped
 * when the field is marked as draft.
 *
 * @param mixed  $field Field.
 * @param string $lang  'vi' or 'en'.
 */
function hcwa_seed_text( $field, string $lang ): string {
	if ( ! is_array( $field ) ) {
		return is_scalar( $field ) ? (string) $field : '';
	}
	$text = (string) ( $field[ $lang ] ?? '' );
	return empty( $field['draft'] ) ? $text : hcwa_seed_draft( $text );
}

/**
 * A paragraph block.
 *
 * @param string $text Plain text.
 */
function hcwa_seed_block_p( string $text ): string {
	return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";
}

/**
 * A paragraph block with a plain-text lead and one link.
 *
 * @param string $lead  Text before the link.
 * @param string $url   Link target.
 * @param string $label Link text.
 */
function hcwa_seed_block_p_link( string $lead, string $url, string $label ): string {
	return "<!-- wp:paragraph -->\n<p>" . esc_html( $lead ) . ' <a href="' . esc_url( $url ) . '">' . esc_html( $label ) . "</a></p>\n<!-- /wp:paragraph -->";
}

/**
 * An h2 heading block with an anchor (the id other pages link to).
 *
 * @param string $text   Plain text.
 * @param string $anchor Id without '#'.
 */
function hcwa_seed_block_h2( string $text, string $anchor ): string {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\" id=\"" . esc_attr( $anchor ) . '">' . esc_html( $text ) . "</h2>\n<!-- /wp:heading -->";
}

/**
 * A dynamic hcwa/* block, saved as a self-closing comment like the editor does.
 *
 * @param string $name  Block name, e.g. 'hcwa/report-list'.
 * @param array  $attrs Contract attributes only (wp/CONTRACT.md).
 */
function hcwa_seed_block_dynamic( string $name, array $attrs = array() ): string {
	$json = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
	return '<!-- wp:' . $name . $json . ' /-->';
}

/**
 * A group with the class `container`, as the page patterns wrap their hcwa/* blocks, so a
 * block and its heading share the container's left edge instead of the heading sitting in
 * the narrower reading column (site.css .entry-content--page rules).
 *
 * @param string[] $blocks Serialized inner blocks.
 */
function hcwa_seed_block_container( array $blocks ): string {
	return '<!-- wp:group {"className":"container"} -->' . "\n" . '<div class="wp-block-group container">' . "\n" . implode( "\n\n", $blocks ) . "\n</div>\n<!-- /wp:group -->";
}

/**
 * The "more" block: the theme folds the text after it into "Read the full history".
 */
function hcwa_seed_block_more(): string {
	return "<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->";
}

/**
 * Root-relative path of a URL ('/' for the site root).
 *
 * @param string $url Absolute URL.
 */
function hcwa_seed_path( string $url ): string {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	return '' === $path ? '/' : $path;
}

/**
 * Last segment of a path: '/en/our-work/centers/green-bamboo/' → 'green-bamboo'.
 *
 * @param string $path Path or URL.
 */
function hcwa_seed_last_segment( string $path ): string {
	$path  = (string) ( wp_parse_url( $path, PHP_URL_PATH ) ?? $path );
	$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
	return $parts ? sanitize_title( rawurldecode( (string) end( $parts ) ) ) : '';
}

// ------------------------------------------------------------------------- lookups

/**
 * The seeded post with this seed ID in this language, or 0.
 *
 * @param string $seed_id   Seed ID.
 * @param string $post_type Post type.
 * @param string $lang      'vi' or 'en'.
 */
function hcwa_seed_find_post( string $seed_id, string $post_type, string $lang ): int {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			WHERE m.meta_key = '_hcwa_seed_id' AND m.meta_value = %s AND p.post_type = %s AND p.post_status <> 'trash'
			ORDER BY p.ID",
			$seed_id,
			$post_type
		)
	);
	foreach ( $ids as $id ) {
		if ( pll_get_post_language( (int) $id ) === $lang ) {
			return (int) $id;
		}
	}
	return 0;
}

/**
 * The seeded term with this seed ID in this language, or 0.
 *
 * @param string $seed_id  Seed ID.
 * @param string $taxonomy Taxonomy.
 * @param string $lang     'vi' or 'en'.
 */
function hcwa_seed_find_term( string $seed_id, string $taxonomy, string $lang ): int {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT tt.term_id FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->termmeta} m ON m.term_id = tt.term_id
			WHERE m.meta_key = '_hcwa_seed_id' AND m.meta_value = %s AND tt.taxonomy = %s
			ORDER BY tt.term_id",
			$seed_id,
			$taxonomy
		)
	);
	foreach ( $ids as $id ) {
		if ( pll_get_term_language( (int) $id ) === $lang ) {
			return (int) $id;
		}
	}
	return 0;
}

// ------------------------------------------------------------------------- writers

/**
 * Creates or updates the seeded post for one language and returns its ID. Only fields
 * that differ are written, so a second run adds no revisions.
 *
 * @param string $seed_id Seed ID shared by both translations.
 * @param string $lang    'vi' or 'en'.
 * @param array  $fields  wp_insert_post() fields, unslashed; post_type is required.
 * @param array  $insert_only Fields used only when the post is created (e.g. a date).
 */
function hcwa_seed_upsert_post( string $seed_id, string $lang, array $fields, array $insert_only = array() ): int {
	$id = hcwa_seed_find_post( $seed_id, (string) $fields['post_type'], $lang );

	if ( $id ) {
		$post    = get_post( $id );
		$changes = array();
		foreach ( $fields as $key => $value ) {
			if ( 'post_type' !== $key && is_scalar( $value ) && (string) $post->$key !== (string) $value ) {
				$changes[ $key ] = $value;
			}
		}
		if ( isset( $fields['post_category'] ) ) {
			$have = array_map( 'intval', (array) wp_get_post_categories( $id ) );
			$want = array_map( 'intval', (array) $fields['post_category'] );
			sort( $have );
			sort( $want );
			if ( $have !== $want ) {
				$changes['post_category'] = $want;
			}
		}
		if ( $changes ) {
			$changes['ID'] = $id;
			$result        = wp_update_post( wp_slash( $changes ), true );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( "Could not update $seed_id ($lang): " . $result->get_error_message() );
			}
		}
	} else {
		$result = pll_insert_post( wp_slash( $fields + $insert_only ), $lang );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "Could not create $seed_id ($lang): " . $result->get_error_message() );
		}
		$id = (int) $result;
	}

	if ( pll_get_post_language( $id ) !== $lang ) {
		pll_set_post_language( $id, $lang );
	}
	update_post_meta( $id, '_hcwa_seed_id', $seed_id );

	return $id;
}

/**
 * Creates or updates the VI and EN posts of one seeded item and links them.
 *
 * @param string $seed_id     Seed ID.
 * @param array  $by_lang     'vi' => fields, 'en' => fields.
 * @param array  $insert_only 'vi' => fields, 'en' => fields used only on creation.
 * @return array{vi: int, en: int}
 */
function hcwa_seed_upsert_pair( string $seed_id, array $by_lang, array $insert_only = array() ): array {
	$pair = array();
	foreach ( array( 'vi', 'en' ) as $lang ) {
		$pair[ $lang ] = hcwa_seed_upsert_post( $seed_id, $lang, $by_lang[ $lang ], $insert_only[ $lang ] ?? array() );
	}
	pll_save_post_translations( $pair );
	return $pair;
}

/**
 * Writes post meta: null deletes the key. Registered keys go through hcwa-core's
 * sanitize callbacks.
 *
 * @param int   $post_id Post ID.
 * @param array $meta    key => value.
 */
function hcwa_seed_meta( int $post_id, array $meta ): void {
	foreach ( $meta as $key => $value ) {
		if ( null === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}
	}
}

/**
 * Creates or updates a term in both languages and links them.
 *
 * @param string $taxonomy Taxonomy.
 * @param string $seed_id  Seed ID.
 * @param array  $by_lang  'vi'|'en' => [name, slug, description].
 * @return array{vi: int, en: int}
 */
function hcwa_seed_term_pair( string $taxonomy, string $seed_id, array $by_lang ): array {
	$pair = array();
	foreach ( array( 'vi', 'en' ) as $lang ) {
		$term = $by_lang[ $lang ];
		$id   = hcwa_seed_find_term( $seed_id, $taxonomy, $lang );
		$args = array( 'description' => (string) ( $term['description'] ?? '' ) );

		if ( $id ) {
			$result = wp_update_term( $id, $taxonomy, $args + array( 'name' => $term['name'] ) );
		} else {
			$result = pll_insert_term( $term['name'], $taxonomy, $lang, $args + array( 'slug' => $term['slug'] ) );
		}
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "Could not save the $taxonomy term $seed_id ($lang): " . $result->get_error_message() );
		}

		$id = (int) $result['term_id'];
		if ( pll_get_term_language( $id ) !== $lang ) {
			pll_set_term_language( $id, $lang );
		}
		update_term_meta( $id, '_hcwa_seed_id', $seed_id );
		$pair[ $lang ] = $id;

		$saved = get_term( $id, $taxonomy );
		$slug  = $saved instanceof WP_Term ? $saved->slug : '';
		if ( $slug !== $term['slug'] ) {
			WP_CLI::warning( "The $taxonomy term $seed_id ($lang) has the slug '$slug', not '{$term['slug']}'." );
		}
	}
	pll_save_term_translations( $pair );
	return $pair;
}

// ------------------------------------------------------------------------- stage 1: languages

/**
 * Whether both site languages exist in Polylang.
 */
function hcwa_seed_has_languages(): bool {
	$languages = PLL()->model->languages;
	return (bool) $languages->get( 'vi' ) && (bool) $languages->get( 'en' );
}

/**
 * Polylang settings from 07-BILINGUAL. Order matters: hide_default and browser depend on
 * force_lang.
 *
 * Sync leaves out 'post_meta' ("Custom fields"): with it on, Polylang syncs every public
 * meta key on every save (src/modules/sync/sync-post-metas.php:51), so per-language text
 * such as core's footnotes would overwrite the other language. 07 syncs only fields that
 * are not text, and hcwa-core's pll_copy_post_metas filter adds those itself (Polylang
 * applies the filter whatever this option says, sync-post-metas.php:78).
 *
 * @return array<string, mixed>
 */
function hcwa_seed_polylang_options(): array {
	return array(
		'force_lang'    => 1,     // Language from the directory name.
		'hide_default'  => true,  // Vietnamese has no /vi/ prefix.
		'rewrite'       => true,  // /en/…, not /language/en/….
		'redirect_lang' => true,  // English front page is /en/, not /en/home/.
		'browser'       => false, // No redirect by browser language: the theme shows a "View in English" bar.
		'media_support' => true,  // Alt text and captions per language.
		'sync'          => array( 'taxonomies', 'post_parent', '_thumbnail_id' ),
	);
}

/**
 * Languages, Polylang settings, permalinks, site title and time zone.
 *
 * @param array $site content/draft/site.json.
 */
function hcwa_seed_languages( array $site ): void {
	$model = PLL()->model;

	$languages = array(
		array(
			'name'       => 'Tiếng Việt',
			'slug'       => 'vi',
			'locale'     => 'vi',
			'rtl'        => false,
			'flag'       => 'vn',
			'term_group' => 0,
		),
		array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_GB',
			'rtl'        => false,
			'flag'       => 'gb',
			'term_group' => 1,
		),
	);

	foreach ( $languages as $language ) {
		$existing = $model->languages->get( $language['slug'] );
		if ( $existing ) {
			if ( $existing->locale !== $language['locale'] ) {
				WP_CLI::warning( "Language {$language['slug']} exists with locale {$existing->locale}, not {$language['locale']}; left as it is." );
			}
			continue;
		}
		$result = $model->languages->add( $language );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "Could not add the language {$language['slug']}: " . $result->get_error_message() );
		}
		WP_CLI::log( "Added language {$language['name']} ({$language['slug']}, {$language['locale']})." );
	}

	if ( 'vi' !== $model->options->get( 'default_lang' ) ) {
		$errors = $model->languages->update_default( 'vi' );
		if ( $errors->has_errors() ) {
			WP_CLI::error( 'Could not make Vietnamese the default language: ' . $errors->get_error_message() );
		}
	}

	foreach ( hcwa_seed_polylang_options() as $key => $value ) {
		$errors = $model->options->set( $key, $value );
		if ( $errors->has_errors() ) {
			WP_CLI::warning( "Polylang setting $key: " . implode( ' ', $errors->get_error_messages() ) );
		}
	}
	$model->options->save();
	WP_CLI::log( 'Polylang: directory URLs, Vietnamese without prefix, no browser detection, media translation on; taxonomies, page parent and featured image synced (custom fields: only the shared ones hcwa-core names).' );

	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );

	$name_vi = (string) ( $site['orgName']['vi'] ?? '' );
	$name_en = (string) ( $site['orgName']['en'] ?? '' );
	if ( '' !== $name_vi ) {
		update_option( 'blogname', $name_vi );
	}
	// The tagline is site.json's description (the footer's brand line, 11-WP-PORT.md 3.1), draft
	// until HCWA approves it: the theme prints it through hcwa_draft_text(), never on production.
	$desc_vi = hcwa_seed_text( $site['description'] ?? '', 'vi' );
	$desc_en = hcwa_seed_text( $site['description'] ?? '', 'en' );
	update_option( 'blogdescription', $desc_vi );
	update_option( 'timezone_string', 'Asia/Ho_Chi_Minh' );

	// The English site title and tagline are Polylang string translations (src/mo.php).
	$en = $model->languages->get( 'en' );
	if ( $en && '' !== $name_vi && '' !== $name_en && class_exists( 'PLL_MO' ) ) {
		$mo = new PLL_MO();
		$mo->import_from_db( $en );
		$mo->add_entry( $mo->make_entry( $name_vi, $name_en ) );
		if ( '' !== $desc_vi && '' !== $desc_en ) {
			$mo->add_entry( $mo->make_entry( $desc_vi, $desc_en ) );
		}
		$mo->export_to_db( $en );
	}

	$model->clean_languages_cache();
	WP_CLI::success( 'Languages ready: Tiếng Việt (default) and English.' );
}

// ------------------------------------------------------------------------- stage 2: content

/**
 * Everything except languages.
 *
 * @param array  $data     The content/draft files.
 * @param string $manifest Where to write the seeded URL list ('' for nowhere).
 */
function hcwa_seed_content( array $data, string $manifest ): void {
	$renamed = hcwa_seed_migrate_key();
	if ( $renamed ) {
		WP_CLI::log( "Renamed the seed marker hcwa_seed_id to the protected _hcwa_seed_id on $renamed items." );
	}
	hcwa_seed_remove_wp_samples();
	hcwa_seed_default_category();

	$focus      = hcwa_seed_focus_areas( $data['work'] );
	$categories = hcwa_seed_categories();
	$pages      = hcwa_seed_pages();
	$centers    = hcwa_seed_centers( $data['work'], $focus );
	hcwa_seed_news_tags( $data['work'], $centers );
	$fixtures   = array( 'refused_photo' => hcwa_seed_little_rose_photo( $centers ) );
	hcwa_seed_unit_logos( $centers );
	$partners   = hcwa_seed_partners( $data['work'] );
	$programs   = hcwa_seed_programs( $data['work'], $focus, $centers, $partners );
	$reports    = hcwa_seed_program_reports( $data['work'], $programs );
	$scopes     = hcwa_seed_impact( $data['impact'], $centers, $programs );
	$posts      = hcwa_seed_posts( $data['news'], $categories );

	hcwa_seed_reading( $pages );
	hcwa_seed_menus( $pages );
	hcwa_seed_settings( $data['site'], $pages, $data['work'], $data['world'] );

	PLL()->model->clean_languages_cache();
	flush_rewrite_rules( false );

	if ( '' !== $manifest ) {
		$fixtures['parity'] = hcwa_seed_parity( $data['work'], $centers, $programs, $reports );
		hcwa_seed_write_manifest( $manifest, $pages, $centers, $programs, $posts, $categories, $scopes, $fixtures );
	}

	WP_CLI::success(
		sprintf(
			'Seeded %d pages, %d centers, %d programs, %d partners, %d impact figures (all unverified) and %d news posts, each in Vietnamese and English.',
			count( $pages ),
			count( $centers ),
			count( $programs ),
			count( $partners ),
			array_sum( array_map( 'count', $scopes ) ),
			count( $posts )
		)
	);
}

/**
 * Earlier seeds marked items with the unprotected key hcwa_seed_id, which Polylang copies
 * into translations made by hand. Renames it to _hcwa_seed_id in post and term meta, so
 * existing instances keep their seeded items. Returns the number of rows renamed.
 */
function hcwa_seed_migrate_key(): int {
	global $wpdb;
	$renamed = 0;
	foreach ( array( $wpdb->postmeta, $wpdb->termmeta ) as $table ) {
		$renamed += (int) $wpdb->update( $table, array( 'meta_key' => '_hcwa_seed_id' ), array( 'meta_key' => 'hcwa_seed_id' ) );
	}
	if ( $renamed ) {
		wp_cache_flush(); // The update bypassed the meta API, so drop any cached meta.
	}
	return $renamed;
}

/**
 * Deletes WordPress's sample post and pages on a fresh local install (no language, no
 * seed ID), so they do not show up in news lists without a language.
 */
function hcwa_seed_remove_wp_samples(): void {
	if ( 'local' !== wp_get_environment_type() ) {
		return;
	}
	foreach ( array( array( 'post', 'hello-world' ), array( 'page', 'sample-page' ), array( 'page', 'privacy-policy' ) ) as $sample ) {
		$post = get_page_by_path( $sample[1], OBJECT, $sample[0] );
		if ( ! $post || get_post_meta( $post->ID, '_hcwa_seed_id', true ) || pll_get_post_language( $post->ID ) ) {
			continue;
		}
		if ( (int) get_option( 'wp_page_for_privacy_policy' ) === (int) $post->ID ) {
			update_option( 'wp_page_for_privacy_policy', 0 );
		}
		wp_delete_post( $post->ID, true );
		WP_CLI::log( "Removed the WordPress sample {$sample[0]} '{$sample[1]}'." );
	}
}

/**
 * "Uncategorized" predates the languages; give it the default language.
 */
function hcwa_seed_default_category(): void {
	$default = (int) get_option( 'default_category' );
	if ( $default && ! pll_get_term_language( $default ) ) {
		pll_set_term_language( $default, 'vi' );
	}
}

/**
 * Focus areas from work.json (contract: EN slugs education, protection, family, policy;
 * VI slugs with "-vi").
 *
 * @param array $work content/draft/work.json.
 * @return array<string, array{vi: int, en: int}> area id => term IDs.
 */
function hcwa_seed_focus_areas( array $work ): array {
	$out = array();
	foreach ( (array) ( $work['focusAreas'] ?? array() ) as $area ) {
		$id         = sanitize_title( (string) $area['id'] );
		$out[ $id ] = hcwa_seed_term_pair(
			'hcwa_focus_area',
			'focus:' . $id,
			array(
				'vi' => array(
					'name'        => (string) $area['name']['vi'],
					'slug'        => $id . '-vi',
					'description' => hcwa_seed_text( $area['text'] ?? '', 'vi' ),
				),
				'en' => array(
					'name'        => (string) $area['name']['en'],
					'slug'        => $id,
					'description' => hcwa_seed_text( $area['text'] ?? '', 'en' ),
				),
			)
		);
	}
	WP_CLI::log( sprintf( 'Focus areas: %d in each language.', count( $out ) ) );
	return $out;
}

/**
 * News categories (wp/CONTRACT.md, glossary in 07-BILINGUAL).
 *
 * @return array<string, array{vi: int, en: int}> key => term IDs.
 */
function hcwa_seed_categories(): array {
	$categories = array(
		'news'          => array( array( 'Tin tức', 'tin-tuc' ), array( 'News', 'news' ) ),
		'stories'       => array( array( 'Câu chuyện', 'cau-chuyen' ), array( 'Stories', 'stories' ) ),
		'events'        => array( array( 'Sự kiện', 'su-kien' ), array( 'Events', 'events' ) ),
		'announcements' => array( array( 'Thông báo', 'thong-bao' ), array( 'Announcements', 'announcements' ) ),
	);
	$out = array();
	foreach ( $categories as $key => $names ) {
		$out[ $key ] = hcwa_seed_term_pair(
			'category',
			'category:' . $key,
			array(
				'vi' => array(
					'name' => $names[0][0],
					'slug' => $names[0][1],
				),
				'en' => array(
					'name' => $names[1][0],
					'slug' => $names[1][1],
				),
			)
		);
	}
	WP_CLI::log( sprintf( 'News categories: %d in each language.', count( $out ) ) );
	return $out;
}

/**
 * Pages per logical page key (hcwa_page_keys()), parents first.
 *
 * - label:    UI string key for the title.
 * - pattern:  page composition pattern (hcwa-2026/<pattern>-<lang>).
 * - text:     [DRAFT] paragraph for pages without a pattern.
 * - sections: [anchor key, UI string key, draft text, optional hcwa/* blocks]: headings
 *             other pages link to (the anchors in hcwa_page_paths()).
 * - blocks:   hcwa/* blocks after the intro paragraph, as [block name, attributes].
 *
 * @return array<string, array>
 */
function hcwa_seed_page_defs(): array {
	return array(
		'home'        => array(
			'label'   => 'home',
			'pattern' => 'page-home',
		),
		'about'       => array(
			'label' => 'about',
			'text'  => array(
				'vi' => 'Giới thiệu ngắn về Hội: sứ mệnh, cách Hội làm việc và tư cách pháp lý. Nội dung do HCWA cung cấp.',
				'en' => 'A short introduction to HCWA: our mission, how we work and our legal status. Text to come from HCWA.',
			),
		),
		'ourStory'    => array(
			'parent' => 'about',
			'label'  => 'ourStory',
			'text'   => array(
				'vi' => 'Lịch sử của Hội qua các mốc thời gian đã được HCWA xác minh.',
				'en' => 'HCWA’s history, told through milestones HCWA has verified.',
			),
		),
		'team'        => array(
			'parent' => 'about',
			'label'  => 'team',
			'text'   => array(
				'vi' => 'Ban điều hành và đội ngũ, chỉ với tên, chức danh và ảnh mà từng người đã đồng ý công khai.',
				'en' => 'The board and staff, with only the names, roles and photos each person has agreed to share.',
			),
		),
		'partners'    => array(
			'parent' => 'about',
			'label'  => 'partners',
			'text'   => array(
				'vi' => 'Các đối tác và nhà tài trợ đã được HCWA xác nhận, kèm logo được phép sử dụng.',
				'en' => 'Partners and sponsors HCWA has confirmed, with the logos we have permission to use.',
			),
			'blocks' => array(
				array(
					'hcwa/partner-wall',
					array(
						'homeOnly' => false,
						'limit'    => 48,
					),
				),
			),
		),
		'policies'    => array(
			'parent'   => 'about',
			'label'    => 'policies',
			'text'     => array(
				'vi' => 'Các chính sách của Hội. Toàn văn do HCWA cung cấp.',
				'en' => 'HCWA’s policies. The full text comes from HCWA.',
			),
			'sections' => array(
				array(
					'safeguarding',
					'safeguarding',
					array(
						'vi' => 'Chính sách bảo vệ trẻ em của Hội, bản đã được HCWA duyệt.',
						'en' => 'HCWA’s child safeguarding policy, as approved by HCWA.',
					),
				),
				array(
					'privacy',
					'privacy',
					array(
						'vi' => 'Thông báo quyền riêng tư: các biểu mẫu thu thập thông tin gì và Hội sử dụng thông tin đó như thế nào.',
						'en' => 'Privacy notice: what the forms collect and how HCWA uses it.',
					),
				),
			),
		),
		'ourWork'     => array(
			'label'   => 'ourWork',
			'pattern' => 'page-our-work',
		),
		'impact'      => array(
			'label'    => 'impact',
			'text'     => array(
				'vi' => 'Các con số tác động đã được HCWA xác minh, kèm năm và nguồn.',
				'en' => 'Impact figures HCWA has verified, with the year and the source.',
			),
			// 06-PAGES Impact: all verified figures, then the reports library.
			'blocks'   => array(
				array(
					'hcwa/impact-strip',
					array(
						'limit'    => 12,
						'showLink' => false,
					),
				),
			),
			'sections' => array(
				array(
					'reports',
					'annualReports',
					array(
						'vi' => 'Báo cáo thường niên và báo cáo tài chính (PDF) do HCWA cung cấp.',
						'en' => 'Annual and financial reports (PDF) from HCWA.',
					),
					array( array( 'hcwa/report-list', array( 'limit' => 12 ) ) ),
				),
			),
		),
		'news'        => array(
			'label' => 'news',
			'posts' => true,
		),
		'getInvolved' => array(
			'label'   => 'getInvolved',
			'pattern' => 'page-get-involved',
		),
		'donate'      => array(
			'parent'  => 'getInvolved',
			'label'   => 'donate',
			'pattern' => 'page-donate',
		),
		'volunteer'   => array(
			'parent' => 'getInvolved',
			'label'  => 'volunteer',
			'text'   => array(
				'vi' => 'Các vai trò tình nguyện, yêu cầu và cách đăng ký. Biểu mẫu đăng ký sẽ được thêm khi HCWA chọn công cụ biểu mẫu.',
				'en' => 'Volunteer roles, what they ask of you and how to apply. The form comes once HCWA chooses a form tool.',
			),
		),
		'partner'     => array(
			'parent' => 'getInvolved',
			'label'  => 'partnerWithUs',
			'text'   => array(
				'vi' => 'Cách doanh nghiệp, trường học và tổ chức hợp tác với Hội, và người liên hệ.',
				'en' => 'How companies, schools and organizations can work with HCWA, and who to contact.',
			),
		),
		'inKind'      => array(
			'parent'   => 'getInvolved',
			'label'    => 'inKind',
			'text'     => array(
				'vi' => 'Những hiện vật Hội đang cần và cách gửi đến Hội.',
				'en' => 'The items HCWA needs now and how to send them.',
			),
			'sections' => array(
				array(
					'bakery',
					'bakery',
					array(
						'vi' => 'Giới thiệu Tiệm bánh Hoa Hồng Nhỏ và cách đặt bánh.',
						'en' => 'About the Little Rose Bakery and how to order.',
					),
				),
			),
		),
		'contact'     => array(
			'label'   => 'contact',
			'pattern' => 'page-contact',
		),
	);
}

/**
 * Content of a theme pattern in one language, or '' when it is not registered.
 *
 * @param string $name Pattern name without the language, e.g. 'page-home'.
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_seed_pattern( string $name, string $lang ): string {
	$slug     = 'hcwa-2026/' . $name . '-' . $lang;
	$registry = WP_Block_Patterns_Registry::get_instance();
	if ( ! $registry->is_registered( $slug ) ) {
		return '';
	}
	$pattern = $registry->get_registered( $slug );
	return is_array( $pattern ) ? trim( (string) ( $pattern['content'] ?? '' ) ) : '';
}

/**
 * Page body: the page pattern, or a [DRAFT] paragraph plus anchored sections. The page
 * template prints the page header (breadcrumb, H1) for bodies without an H1.
 *
 * @param string $key  Page key.
 * @param array  $def  Definition from hcwa_seed_page_defs().
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_seed_page_body( string $key, array $def, string $lang ): string {
	if ( ! empty( $def['posts'] ) ) {
		return '';
	}

	if ( ! empty( $def['pattern'] ) ) {
		$content = hcwa_seed_pattern( $def['pattern'], $lang );
		if ( '' !== $content ) {
			return $content;
		}
		WP_CLI::warning( "Pattern hcwa-2026/{$def['pattern']}-$lang is not registered yet; the $key page ($lang) gets a placeholder. Run the seed again once the theme has it." );
		$def['text'] = array(
			'vi' => 'Phần thân trang này sẽ được dựng từ mẫu trang của giao diện hcwa-2026.',
			'en' => 'This page body will come from the hcwa-2026 theme’s page pattern.',
		);
	}

	$blocks  = array( hcwa_seed_block_p( hcwa_seed_draft( (string) ( $def['text'][ $lang ] ?? '' ) ) ) );
	$dynamic = array();
	foreach ( (array) ( $def['blocks'] ?? array() ) as $block ) {
		$dynamic[] = hcwa_seed_block_dynamic( $block[0], $block[1] );
	}
	if ( $dynamic ) {
		$blocks[] = hcwa_seed_block_container( $dynamic );
	}
	$paths = hcwa_page_paths();
	foreach ( (array) ( $def['sections'] ?? array() ) as $section ) {
		list( $anchor_key, $label_key, $text ) = $section;
		$anchor = ltrim( (string) ( $paths[ $anchor_key ][ $lang ] ?? $anchor_key ), '#' );
		$inner  = array(
			hcwa_seed_block_h2( hcwa_t_in( $label_key, $lang ), $anchor ),
			hcwa_seed_block_p( hcwa_seed_draft( $text[ $lang ] ) ),
		);
		$dynamic = array();
		foreach ( (array) ( $section[3] ?? array() ) as $block ) {
			$dynamic[] = hcwa_seed_block_dynamic( $block[0], $block[1] );
		}
		// A section with an hcwa/* block is one container (heading, text and block aligned,
		// as in the page patterns); a text-only section stays in the reading column.
		$blocks = $dynamic ? array_merge( $blocks, array( hcwa_seed_block_container( array_merge( $inner, $dynamic ) ) ) ) : array_merge( $blocks, $inner );
	}
	return implode( "\n\n", $blocks );
}

/**
 * Slug of a logical page in one language: the last part of its default path.
 *
 * @param string $key  Page key.
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_seed_page_slug( string $key, string $lang ): string {
	if ( 'home' === $key ) {
		return 'en' === $lang ? 'home' : 'trang-chu';
	}
	return hcwa_seed_last_segment( (string) hcwa_page_path( $key, $lang ) );
}

/**
 * Pages for every logical page key, both languages, linked as translations.
 *
 * @return array<string, array{vi: int, en: int}> page key => IDs.
 */
function hcwa_seed_pages(): array {
	$pages = array();
	$order = 0;
	foreach ( hcwa_seed_page_defs() as $key => $def ) {
		$by_lang = array();
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$parent           = (string) ( $def['parent'] ?? '' );
			$by_lang[ $lang ] = array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => hcwa_t_in( $def['label'], $lang ),
				'post_name'      => hcwa_seed_page_slug( $key, $lang ),
				'post_parent'    => '' !== $parent ? (int) $pages[ $parent ][ $lang ] : 0,
				'post_content'   => hcwa_seed_page_body( $key, $def, $lang ),
				'menu_order'     => $order,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			);
		}
		$pages[ $key ] = hcwa_seed_upsert_pair( 'page:' . $key, $by_lang );
		++$order;
	}

	$missing = array_diff( hcwa_page_keys(), array_keys( $pages ) );
	if ( $missing ) {
		WP_CLI::warning( 'Page keys without a seeded page: ' . implode( ', ', $missing ) );
	}
	WP_CLI::log( sprintf( 'Pages: %d in each language.', count( $pages ) ) );
	return $pages;
}

/**
 * Copy from the prototype pages (prototype/src/pages/work.mjs) that work.json does not
 * hold. Draft: everything here is wrapped in [DRAFT: …] when written.
 */
function hcwa_seed_prototype_copy(): array {
	return array(
		'center_intro'  => array(
			'vi' => 'Giới thiệu về cơ sở, do HCWA cung cấp.',
			'en' => 'An introduction to the center, supplied by HCWA.',
		),
		'center_help'   => array(
			'vi' => 'Nhu cầu cụ thể của cơ sở do HCWA cung cấp, ví dụ học bổng, đồ dùng học tập, giáo viên tình nguyện.',
			'en' => 'Specific needs supplied by HCWA, for example scholarships, school supplies, volunteer teachers.',
		),
		'program_intro' => array(
			'vi' => 'Mô tả chương trình, do HCWA cung cấp.',
			'en' => 'A description of the program, supplied by HCWA.',
		),
		'green-bamboo'  => array(
			'story'      => array(
				array(
					'vi' => 'Tre Xanh bắt đầu là một câu lạc bộ, sau đó trở thành mái ấm cho trẻ trai không nơi nương tựa, giúp các em có chỗ ở, được học và học nghề.',
					'en' => 'Green Bamboo began as a club and later became a shelter for homeless boys, giving them a home, schooling and vocational training.',
				),
				array(
					'vi' => 'Từ [tháng/năm], Tre Xanh chuyển sang mô hình cơ sở bảo trợ xã hội. Thay vì nuôi dạy tập trung, cơ sở hỗ trợ trẻ và gia đình ngay tại cộng đồng: lớp học, câu lạc bộ, tư vấn, chăm sóc sức khỏe và kết nối dịch vụ xã hội.',
					'en' => 'In [month/year] Green Bamboo became a social protection center. Instead of residential care, it now supports children and families in their community: classes, clubs, counseling, healthcare and links to social services.',
				),
			),
			'history'    => array(
				'vi' => 'Lịch sử đầy đủ do HCWA cung cấp, kèm các năm đã được xác minh.',
				'en' => 'Full history supplied by HCWA, with verified years.',
			),
			'what_we_do' => array(
				array(
					'vi' => 'Lớp bồi dưỡng văn hóa và tiếng Anh',
					'en' => 'Catch-up classes and English',
				),
				array(
					'vi' => 'Câu lạc bộ giáo dục và kỹ năng sống cho thanh thiếu niên',
					'en' => 'Education and life-skills clubs for young people',
				),
				array(
					'vi' => 'Hội thảo cho phụ huynh và tư vấn gia đình',
					'en' => 'Workshops for parents and family counseling',
				),
				array(
					'vi' => 'Hỗ trợ chăm sóc sức khỏe cho trẻ',
					'en' => 'Help accessing healthcare',
				),
			),
		),
		'thrive-together' => array(
			'intro'      => array(
				'vi' => 'Chương trình đặc biệt hướng đến trẻ em di cư, trẻ thiếu giấy tờ pháp lý, trẻ khuyết tật, trẻ chịu ảnh hưởng bởi HIV/AIDS và trẻ sống trong môi trường thiếu an toàn.',
				'en' => 'It focuses on migrant children, children without legal documents, children with disabilities, children affected by HIV/AIDS and children living in unsafe places.',
			),
			'goals'      => array(
				array(
					'vi' => 'Trẻ được tiếp cận liên tục với giáo dục',
					'en' => 'Children keep steady access to education',
				),
				array(
					'vi' => 'Trẻ được chăm sóc sức khỏe thể chất và tinh thần',
					'en' => 'Children get physical and mental healthcare',
				),
				array(
					'vi' => 'Trẻ phát triển kỹ năng phù hợp với độ tuổi',
					'en' => 'Children build age-appropriate skills',
				),
			),
			'activities' => array(
				array(
					'vi' => 'Củng cố và mở rộng hoạt động tại các cơ sở của Hội',
					'en' => 'Strengthen and expand activities at HCWA’s centers',
				),
				array(
					'vi' => 'Tập huấn cho phụ huynh, nhân viên và cộng tác viên',
					'en' => 'Train parents, staff and community collaborators',
				),
			),
		),
	);
}

/**
 * Draft lines for a "one per line" field.
 *
 * @param array  $items [{vi, en}, …].
 * @param string $lang  'vi' or 'en'.
 */
function hcwa_seed_draft_lines( array $items, string $lang ): string {
	return implode( "\n", array_map( static fn( $item ) => hcwa_seed_draft( (string) $item[ $lang ] ), $items ) );
}

/**
 * VI and EN slugs from a work.json path pair. Polylang free cannot give two posts the
 * same slug, so when both paths end in the same slug the English post uses its English
 * name instead (reported, since its URL then differs from work.json).
 *
 * @param array  $path ['vi' => path, 'en' => path].
 * @param array  $name ['vi' => name, 'en' => name].
 * @param string $what Label for the message.
 * @return array{vi: string, en: string}
 */
function hcwa_seed_cpt_slugs( array $path, array $name, string $what ): array {
	$slugs = array(
		'vi' => hcwa_seed_last_segment( (string) ( $path['vi'] ?? '' ) ),
		'en' => hcwa_seed_last_segment( (string) ( $path['en'] ?? '' ) ),
	);
	if ( '' === $slugs['vi'] ) {
		$slugs['vi'] = sanitize_title( (string) $name['vi'] );
	}
	if ( '' === $slugs['en'] || $slugs['en'] === $slugs['vi'] ) {
		$english = sanitize_title( (string) $name['en'] );
		if ( '' !== $slugs['en'] ) {
			WP_CLI::warning( "$what: '{$slugs['en']}' is wanted in both languages, but Polylang gives each post its own slug; English uses '$english'." );
		}
		$slugs['en'] = $english;
	}
	return $slugs;
}

/**
 * Term IDs in one language for a list of focus area ids.
 *
 * @param array  $areas Area ids.
 * @param array  $focus hcwa_seed_focus_areas() result.
 * @param string $lang  'vi' or 'en'.
 * @return int[]
 */
function hcwa_seed_area_ids( array $areas, array $focus, string $lang ): array {
	$ids = array();
	foreach ( $areas as $area ) {
		if ( isset( $focus[ $area ][ $lang ] ) ) {
			$ids[] = (int) $focus[ $area ][ $lang ];
		}
	}
	return $ids;
}

/**
 * Centers from work.json. Little Rose gets hcwa_no_identifiable_children (CLAUDE.md).
 *
 * @param array $work  content/draft/work.json.
 * @param array $focus Focus area term IDs.
 * @return array<string, array{vi: int, en: int}> center id => IDs.
 */
function hcwa_seed_centers( array $work, array $focus ): array {
	$copy  = hcwa_seed_prototype_copy();
	$pages = (array) ( $work['centerPages'] ?? array() );
	$out   = array();

	foreach ( array_values( (array) ( $work['centers'] ?? array() ) ) as $i => $center ) {
		$id        = sanitize_title( (string) $center['id'] );
		$page      = (array) ( $pages[ $id ] ?? array() );
		$extra     = $copy[ $id ] ?? array();
		$protected = 'little-rose' === $id;
		$slugs     = hcwa_seed_cpt_slugs( (array) ( $center['path'] ?? array() ), (array) $center['name'], "Center $id" );

		// Story before <!--more-->, history after (hcwa_center_body(), 11-WP-PORT.md 2.5):
		// work.json centerPages first, the old prototype copy as a fallback.
		$story   = (array) ( $page['story'] ?? $extra['story'] ?? array() );
		$history = $page['history'] ?? ( $extra['history'] ?? null );

		$by_lang = array();
		foreach ( array( 'vi', 'en' ) as $lang ) {
			if ( $story ) {
				$blocks = array();
				foreach ( $story as $paragraph ) {
					$blocks[] = hcwa_seed_block_p( hcwa_seed_draft( (string) $paragraph[ $lang ] ) );
				}
				if ( is_array( $history ) ) {
					$blocks[] = hcwa_seed_block_more();
					$blocks[] = hcwa_seed_block_p( hcwa_seed_draft( (string) $history[ $lang ] ) );
				}
				$body = implode( "\n\n", $blocks );
			} else {
				$body = hcwa_seed_block_p( hcwa_seed_draft( $copy['center_intro'][ $lang ] ) );
			}

			$by_lang[ $lang ] = array(
				'post_type'    => 'hcwa_center',
				'post_status'  => 'publish',
				'post_title'   => (string) $center['name'][ $lang ],
				'post_name'    => $slugs[ $lang ],
				'post_content' => $body,
				'menu_order'   => $i,
			);
		}
		$pair = hcwa_seed_upsert_pair( 'center:' . $id, $by_lang );

		$activities = (array) ( $page['activities'] ?? $extra['what_we_do'] ?? array() );
		foreach ( $pair as $lang => $post_id ) {
			$text = static fn( string $key ) => isset( $page[ $key ] ) ? hcwa_seed_text( $page[ $key ], $lang ) : '';
			hcwa_seed_meta(
				$post_id,
				array(
					'hcwa_summary'                  => hcwa_seed_text( $center['serves'] ?? '', $lang ),
					'hcwa_who_we_serve'             => hcwa_seed_text( $center['serves'] ?? '', $lang ),
					'hcwa_what_we_do'               => hcwa_seed_draft_lines( $activities, $lang ),
					'hcwa_location'                 => $protected ? '' : hcwa_seed_text( $center['location'] ?? '', $lang ),
					'hcwa_help_text'                => hcwa_seed_draft( $copy['center_help'][ $lang ] ),
					'hcwa_contact_name'             => $text( 'contactPerson' ),
					// Per-language fields of the center page (11-WP-PORT.md 3.2); '' where work.json
					// has no centerPages entry, so the template falls back to its own wording.
					'hcwa_short_name'               => hcwa_seed_text( $center['shortName'] ?? '', $lang ),
					'hcwa_story_title'              => $text( 'storyTitle' ),
					'hcwa_figures_title'            => $text( 'figuresTitle' ),
					'hcwa_help_title'               => $text( 'helpTitle' ),
					'hcwa_needs'                    => hcwa_seed_draft_lines( (array) ( $page['needs'] ?? array() ), $lang ),
					'hcwa_donate_label'             => $text( 'donate' ),
					'hcwa_visit_title'              => $text( 'visitTitle' ),
					'hcwa_contact_note'             => $text( 'contactNote' ),
					'hcwa_logo_alt'                 => $text( 'logoAlt' ),
					// Shared fields. Years are tokens in work.json until HCWA confirms them.
					'hcwa_since_year'               => 0,
					'hcwa_since_verified'           => false,
					// The unit key: data-unit, #center-{unit}, ?center={unit}, still names.
					'hcwa_accent'                   => (string) ( $center['accent'] ?? $id ),
					'hcwa_header_still'             => hcwa_seed_header_still( $id, $protected ),
					'hcwa_contact_approved'         => false,
					'hcwa_no_identifiable_children' => $protected,
				)
			);
			wp_set_object_terms( $post_id, hcwa_seed_area_ids( (array) ( $center['areas'] ?? array() ), $focus, $lang ), 'hcwa_focus_area' );
		}

		$out[ $id ] = $pair;
	}

	WP_CLI::log( sprintf( 'Centers: %d in each language (Little Rose marked "no identifiable children"; unit keys, short names, header stills).', count( $out ) ) );
	return $out;
}

/**
 * One post tag per center, in both languages and linked, set as the center's news tag
 * (shared field, the Vietnamese slug). The center page lists news with that tag, and for
 * Little Rose it is what extends the "no identifiable children" photo rule to its news
 * (hcwa_no_identifiable_news_tags()); `wp hcwa coverage` fails while a center with that
 * flag has no news tag. Tag slugs are the centers' own slugs, which already differ per
 * language (Polylang free gives each term its own slug). No seeded post is tagged: none
 * of the posts in content/draft/news.json is about one center, and tagging one would
 * claim it is.
 *
 * @param array $work    content/draft/work.json.
 * @param array $centers hcwa_seed_centers() result: center id => ['vi' => ID, 'en' => ID].
 * @return array<string, array{vi: int, en: int}> center id => tag term IDs.
 */
function hcwa_seed_news_tags( array $work, array $centers ): array {
	$out = array();
	foreach ( (array) ( $work['centers'] ?? array() ) as $center ) {
		$id = sanitize_title( (string) ( $center['id'] ?? '' ) );
		if ( empty( $centers[ $id ]['vi'] ) || empty( $centers[ $id ]['en'] ) ) {
			continue;
		}
		$by_lang = array();
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$by_lang[ $lang ] = array(
				'name' => (string) ( $center['name'][ $lang ] ?? '' ),
				'slug' => (string) get_post_field( 'post_name', (int) $centers[ $id ][ $lang ] ),
			);
		}
		$out[ $id ] = hcwa_seed_term_pair( 'post_tag', 'tag:' . $id, $by_lang );

		$vi_tag = get_term( (int) $out[ $id ]['vi'], 'post_tag' );
		$slug   = $vi_tag instanceof WP_Term ? $vi_tag->slug : $by_lang['vi']['slug'];
		foreach ( $centers[ $id ] as $post_id ) {
			hcwa_seed_meta( (int) $post_id, array( 'hcwa_news_tag' => $slug ) );
		}
	}
	WP_CLI::log( sprintf( 'News tags: %d centers, each with a linked VI / EN tag (no posts tagged).', count( $out ) ) );
	return $out;
}

/**
 * The unit logos the prototype has (prototype/public/img/unit-<id>.png: today only Green
 * Bamboo's), uploaded once as each center's hcwa_logo_id, which the center page's lit header
 * prints (11-WP-PORT.md 2.5 P2). The logo is center media, so it passes the photo gate
 * (contract rule 2, hcwa_center_header_html()): the seed marks these files approved for web,
 * because each is the prototype's own drawn emblem, not a photo. Any other upload staff pick
 * as a unit logo needs the approval like every photo.
 *
 * @param array $centers hcwa_seed_centers() result.
 */
function hcwa_seed_unit_logos( array $centers ): void {
	global $wpdb;
	$done = 0;
	foreach ( $centers as $id => $pair ) {
		$file = dirname( __DIR__ ) . '/prototype/public/img/unit-' . $id . '.png';
		if ( empty( $pair['vi'] ) || ! is_readable( $file ) ) {
			continue;
		}
		$seed_id = 'media:unit-logo:' . $id;
		$logo    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
				WHERE m.meta_key = '_hcwa_seed_id' AND m.meta_value = %s AND p.post_type = 'attachment' ORDER BY p.ID LIMIT 1",
				$seed_id
			)
		);
		if ( 0 === $logo ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$tmp = wp_tempnam( 'hcwa-logo.png' );
			copy( $file, $tmp );
			$logo = media_handle_sideload(
				array(
					'name'     => 'unit-logo.png',
					'tmp_name' => $tmp,
				),
				(int) $pair['vi'],
				null,
				array( 'post_title' => 'Unit logo' )
			);
			if ( is_file( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			if ( is_wp_error( $logo ) ) {
				WP_CLI::warning( "The $id unit logo could not be uploaded: " . $logo->get_error_message() );
				continue;
			}
			$logo = (int) $logo;
			hcwa_seed_meta( $logo, array( '_hcwa_seed_id' => $seed_id ) );
		}
		// The prototype's drawn emblem, not a photo: approved for web, never identifiable.
		hcwa_seed_meta(
			$logo,
			array(
				'hcwa_approved_for_web'   => true,
				'hcwa_identifiable_child' => false,
			)
		);
		foreach ( $pair as $post_id ) {
			hcwa_seed_meta( (int) $post_id, array( 'hcwa_logo_id' => $logo ) );
		}
		++$done;
	}
	WP_CLI::log( sprintf( 'Unit logos: %d from prototype/public/img.', $done ) );
}

/**
 * A synthetic test photo (one flat colour, no person in it) uploaded the way staff upload
 * (media_handle_sideload(), so it passes hcwa-core's upload filters), for checks that need
 * a real WordPress:
 *
 * - the file carries a GPS position, and afterwards no file WordPress kept (the original,
 *   the converted main file, every size) may still have one (media-privacy.php);
 * - it is stored as hcwa-{date}-{random} and titled that way, not after the upload name;
 * - it is attached to the Vietnamese Little Rose center and is its featured image, marked
 *   "Approved for web" and "Identifiable child": the gate refuses it only because of the
 *   Little Rose context, so the center page must not print it (nor an SEO plugin's
 *   og:image or schema) and its attachment page must answer 404 (wp-smoke.py probes).
 *
 * A problem with the location stops the seed; a name problem is a warning. Returns the
 * attachment ID, or 0 when there is no Little Rose center or GD is missing.
 *
 * @param array $centers hcwa_seed_centers() result.
 */
function hcwa_seed_little_rose_photo( array $centers ): int {
	$center = (int) ( $centers['little-rose']['vi'] ?? 0 );
	if ( $center <= 0 ) {
		WP_CLI::warning( 'No Little Rose center, so no photo-gate fixture (the refused-photo probes are skipped).' );
		return 0;
	}
	$seed_id = 'media:little-rose-photo';

	global $wpdb;
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			WHERE m.meta_key = '_hcwa_seed_id' AND m.meta_value = %s AND p.post_type = 'attachment' ORDER BY p.ID LIMIT 1",
			$seed_id
		)
	);

	if ( 0 === $id ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagejpeg' ) ) {
			WP_CLI::warning( 'PHP GD is missing, so no photo-gate fixture (the refused-photo probes are skipped).' );
			return 0;
		}
		$jpeg = hcwa_seed_gps_jpeg();
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( 'hcwa-seed.jpg' );
		file_put_contents( $tmp, $jpeg ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$id = media_handle_sideload(
			array(
				'name'     => 'little-rose-seed-photo.jpg',
				'tmp_name' => $tmp,
			),
			$center,
			null,
			array( 'post_content' => 'Seed fixture: one flat colour, no person. It checks the upload clean-up and the Little Rose photo gate on local instances.' )
		);
		if ( is_file( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( 'The photo-gate fixture could not be uploaded: ' . $id->get_error_message() );
		}
		$id = (int) $id;
		if ( function_exists( 'pll_set_post_language' ) && ! pll_get_post_language( $id ) ) {
			pll_set_post_language( $id, 'vi' );
		}
	}

	hcwa_seed_meta(
		$id,
		array(
			'_hcwa_seed_id'           => $seed_id,
			'hcwa_approved_for_web'   => true,
			'hcwa_identifiable_child' => true,
		)
	);
	if ( (int) get_post_thumbnail_id( $center ) !== $id ) {
		set_post_thumbnail( $center, $id );
	}

	// The upload clean-up: no location left in any file, a neutral name and title.
	$located = array();
	foreach ( hcwa_attachment_files( $id ) as $key => $path ) {
		$bytes = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$exif  = function_exists( 'exif_read_data' ) && preg_match( '/\.jpe?g$/i', $path ) ? @exif_read_data( $path ) : array(); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$gps   = array_filter( array_keys( is_array( $exif ) ? $exif : array() ), static fn( $k ) => 0 === strpos( (string) $k, 'GPS' ) );
		if ( hcwa_image_bytes_have_gps( $bytes ) || $gps ) {
			$located[] = $key . ' (' . wp_basename( $path ) . ')';
		}
	}
	if ( $located ) {
		WP_CLI::error( 'The photo-gate fixture still carries a location in: ' . implode( ', ', $located ) . '. The upload clean-up (hcwa-core media-privacy.php) did not run.' );
	}
	$file = wp_basename( (string) get_attached_file( $id ) );
	if ( 0 !== strpos( $file, 'hcwa-' ) || 0 !== strpos( (string) get_the_title( $id ), 'hcwa-' ) ) {
		WP_CLI::warning( sprintf( 'The photo-gate fixture is stored as "%s" and titled "%s": uploads should get a neutral hcwa-… name and title.', $file, get_the_title( $id ) ) );
	}

	WP_CLI::log( sprintf( 'Photo-gate fixture: attachment %d (%s), Little Rose featured image, no location in %d file(s).', $id, $file, count( hcwa_attachment_files( $id ) ) ) );
	return $id;
}

/**
 * A 1200 × 800 JPEG of one flat colour carrying a GPS position the way a phone writes it:
 * an EXIF block with a GPS IFD (latitude reference N) and XMP with exif:GPSLatitude.
 */
function hcwa_seed_gps_jpeg(): string {
	$image = imagecreatetruecolor( 1200, 800 );
	imagefill( $image, 0, 0, (int) imagecolorallocate( $image, 47, 107, 79 ) );
	ob_start();
	imagejpeg( $image, null, 80 );
	$jpeg = (string) ob_get_clean();
	imagedestroy( $image );

	$tiff    = "II*\0" . pack( 'V', 8 ) . pack( 'v', 2 )
		. pack( 'vvVv', 0x0112, 3, 1, 1 ) . "\0\0"   // Orientation 1.
		. pack( 'vvVV', 0x8825, 4, 1, 38 )           // GPS IFD at offset 38.
		. pack( 'V', 0 )
		. pack( 'v', 1 ) . pack( 'vvV', 1, 2, 2 ) . "N\0\0\0" . pack( 'V', 0 ); // GPSLatitudeRef N.
	$segment = static fn( int $marker, string $body ): string => "\xFF" . chr( $marker ) . pack( 'n', strlen( $body ) + 2 ) . $body;
	$xmp     = "http://ns.adobe.com/xap/1.0/\0<x:xmpmeta xmlns:x=\"adobe:ns:meta/\"><rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"><rdf:Description xmlns:exif=\"http://ns.adobe.com/exif/1.0/\" exif:GPSLatitude=\"10,46.5N\" exif:GPSLongitude=\"106,41.9E\"/></rdf:RDF></x:xmpmeta>";

	$app0_end = 'JFIF' === substr( $jpeg, 6, 4 ) ? 4 + unpack( 'n', substr( $jpeg, 4, 2 ) )[1] : 2;
	return substr( $jpeg, 0, $app0_end ) . $segment( 0xE1, "Exif\0\0" . $tiff ) . $segment( 0xE1, $xmp ) . substr( $jpeg, $app0_end );
}

/**
 * Funders named in work.json programs (from the current site), as admin-only partner
 * records: not shown on the home page and without logo permission until HCWA says so.
 *
 * @param array $work content/draft/work.json.
 * @return array<string, array{vi: int, en: int}> partner name => IDs.
 */
function hcwa_seed_partners( array $work ): array {
	$out = array();
	foreach ( (array) ( $work['programs'] ?? array() ) as $program ) {
		foreach ( (array) ( $program['partners'] ?? array() ) as $name ) {
			$name = trim( (string) $name );
			if ( '' === $name || isset( $out[ $name ] ) ) {
				continue;
			}
			$fields = array(
				'post_type'   => 'hcwa_partner',
				'post_status' => 'publish',
				'post_title'  => $name,
			);
			$pair   = hcwa_seed_upsert_pair(
				'partner:' . sanitize_title( $name ),
				array(
					'vi' => $fields,
					'en' => $fields,
				)
			);
			foreach ( $pair as $post_id ) {
				hcwa_seed_meta(
					$post_id,
					array(
						'hcwa_show_on_home'    => false,
						'hcwa_logo_permission' => false,
					)
				);
			}
			$out[ $name ] = $pair;
		}
	}
	return $out;
}

/**
 * Programs from work.json.
 *
 * @param array $work     content/draft/work.json.
 * @param array $focus    Focus area term IDs.
 * @param array $centers  Center IDs by center id.
 * @param array $partners Partner IDs by name.
 * @return array<string, array{vi: int, en: int}> program id => IDs.
 */
function hcwa_seed_programs( array $work, array $focus, array $centers, array $partners ): array {
	$copy  = hcwa_seed_prototype_copy();
	$pages = (array) ( $work['programPages'] ?? array() );
	$out   = array();

	foreach ( array_values( (array) ( $work['programs'] ?? array() ) ) as $i => $program ) {
		$id    = sanitize_title( (string) $program['id'] );
		$page  = (array) ( $pages[ $id ] ?? array() );
		$extra = $copy[ $id ] ?? array();
		$slugs = hcwa_seed_cpt_slugs( (array) ( $program['path'] ?? array() ), (array) $program['name'], "Program $id" );

		$by_lang = array();
		foreach ( array( 'vi', 'en' ) as $lang ) {
			// The old templates print the post content; the port prints only meta (11-WP-PORT.md P3).
			$intro            = isset( $page['focus'][ $lang ] ) ? (string) $page['focus'][ $lang ] : ( $extra['intro'][ $lang ] ?? $copy['program_intro'][ $lang ] );
			$by_lang[ $lang ] = array(
				'post_type'    => 'hcwa_program',
				'post_status'  => 'publish',
				'post_title'   => (string) $program['name'][ $lang ],
				'post_name'    => $slugs[ $lang ],
				'post_content' => hcwa_seed_block_p( hcwa_seed_draft( $intro ) ),
				'menu_order'   => $i,
			);
		}
		$pair = hcwa_seed_upsert_pair( 'program:' . $id, $by_lang );

		// Every center work.json lists, as Vietnamese originals (hcwa_center_ids). hcwa_center_id
		// keeps the first one for templates that still read the single field.
		$center_ids = array();
		foreach ( (array) ( $program['centers'] ?? array() ) as $center ) {
			if ( isset( $centers[ $center ]['vi'] ) ) {
				$center_ids[] = (int) $centers[ $center ]['vi'];
			}
		}

		$partner_ids = array();
		foreach ( (array) ( $program['partners'] ?? array() ) as $name ) {
			if ( isset( $partners[ trim( (string) $name ) ] ) ) {
				$partner_ids[] = (int) $partners[ trim( (string) $name ) ]['vi'];
			}
		}

		$goals      = (array) ( $page['goals'] ?? $extra['goals'] ?? array() );
		$activities = (array) ( $page['activities'] ?? $extra['activities'] ?? array() );
		foreach ( $pair as $lang => $post_id ) {
			$text = static fn( string $key ) => isset( $page[ $key ] ) ? hcwa_seed_text( $page[ $key ], $lang ) : '';
			hcwa_seed_meta(
				$post_id,
				array(
					'hcwa_summary'       => hcwa_seed_text( $program['summary'] ?? '', $lang ),
					'hcwa_goals'         => hcwa_seed_draft_lines( $goals, $lang ),
					'hcwa_activities'    => hcwa_seed_draft_lines( $activities, $lang ),
					// Per-language fields of the program page (11-WP-PORT.md 3.2).
					'hcwa_full_name'     => hcwa_seed_text( $program['fullName'] ?? $program['name'], $lang ),
					// Kept as work.json writes it: the [năm bắt đầu]–[năm kết thúc] tokens are the
					// draft marker (hcwa_draft_text() treats them as draft).
					'hcwa_years_label'   => hcwa_seed_text( $program['years'] ?? '', $lang ),
					'hcwa_focus'         => $text( 'focus' ),
					'hcwa_runs_at_note'  => $text( 'runsAtNote' ),
					'hcwa_cta_title'     => $text( 'ctaTitle' ),
					'hcwa_partner_label' => $text( 'partner' ),
					// Shared fields: IDs point at the Vietnamese originals (wp/CONTRACT.md).
					'hcwa_status'        => (string) ( $program['status'] ?? 'active' ),
					'hcwa_start_year'    => 0,
					'hcwa_end_year'      => 0,
					'hcwa_center_ids'    => $center_ids,
					'hcwa_center_id'     => $center_ids[0] ?? 0,
					'hcwa_partner_ids'   => $partner_ids,
					'hcwa_header_still'  => hcwa_seed_header_still( $id ),
				)
			);
			wp_set_object_terms( $post_id, hcwa_seed_area_ids( (array) ( $program['areas'] ?? array() ), $focus, $lang ), 'hcwa_focus_area' );
		}

		$out[ $id ] = $pair;
	}

	WP_CLI::log( sprintf( 'Programs: %d in each language (every center they run at, full names, year labels).', count( $out ) ) );
	return $out;
}

/**
 * The program reports listed in work.json programPages[...].reports as draft hcwa_report posts,
 * linked to their program with hcwa_program_id (the Vietnamese original). Titles and years are
 * placeholders, so the posts stay drafts and carry no file.
 *
 * @param array $work     content/draft/work.json.
 * @param array $programs hcwa_seed_programs() result.
 * @return array<string, array{vi: int, en: int}> seed id => IDs.
 */
function hcwa_seed_program_reports( array $work, array $programs ): array {
	$out = array();
	foreach ( (array) ( $work['programPages'] ?? array() ) as $program => $page ) {
		$program = sanitize_title( (string) $program );
		if ( empty( $programs[ $program ]['vi'] ) ) {
			continue;
		}
		foreach ( array_values( (array) ( $page['reports'] ?? array() ) ) as $i => $report ) {
			$seed_id = 'report:' . $program . ':' . ( $i + 1 );
			$by_lang = array();
			foreach ( array( 'vi', 'en' ) as $lang ) {
				$by_lang[ $lang ] = array(
					'post_type'   => 'hcwa_report',
					'post_status' => 'draft',
					'post_title'  => hcwa_seed_text( $report['title'] ?? '', $lang ),
					'post_name'   => sanitize_title( $program . '-report-' . ( $i + 1 ) . '-' . $lang ),
				);
			}
			$pair = hcwa_seed_upsert_pair( $seed_id, $by_lang );
			$doc  = (string) ( $report['docLang'] ?? 'vi' );
			foreach ( $pair as $post_id ) {
				hcwa_seed_meta(
					$post_id,
					array(
						'hcwa_year'         => 0,
						'hcwa_report_type'  => 'program',
						'hcwa_doc_language' => in_array( $doc, array( 'vi', 'en', 'both' ), true ) ? $doc : 'vi',
						'hcwa_file_id'      => 0,
						'hcwa_program_id'   => (int) $programs[ $program ]['vi'],
					)
				);
			}
			$out[ $seed_id ] = $pair;
		}
	}
	WP_CLI::log( sprintf( 'Program reports: %d in each language, as drafts.', count( $out ) ) );
	return $out;
}

/**
 * Impact figures from impact.json, all unverified: no value, no year, no verifier.
 *
 * @param array $impact   content/draft/impact.json.
 * @param array $centers  Center IDs.
 * @param array $programs Program IDs.
 * @return array<string, string[]> scope ('organization' or a center/program id) => figure ids.
 */
function hcwa_seed_impact( array $impact, array $centers, array $programs ): array {
	$scopes = array( 'organization' => array( 'organization', 0 ) );
	foreach ( $centers as $id => $pair ) {
		$scopes[ $id ] = array( 'center', (int) $pair['vi'] );
	}
	foreach ( $programs as $id => $pair ) {
		$scopes[ $id ] = array( 'program', (int) $pair['vi'] );
	}

	$seeded = array();
	foreach ( (array) ( $impact['figures'] ?? array() ) as $figure ) {
		$figure_id = sanitize_title( (string) ( $figure['id'] ?? '' ) );
		$scope     = (string) ( $figure['scope'] ?? 'organization' );
		if ( '' === $figure_id || ! isset( $scopes[ $scope ] ) ) {
			WP_CLI::warning( "Impact figure '$figure_id': unknown scope '$scope', skipped." );
			continue;
		}
		$seeded[ $scope ][] = $figure_id;

		$by_lang = array();
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$by_lang[ $lang ] = array(
				'post_type'   => 'hcwa_impact',
				'post_status' => 'publish',
				'post_title'  => hcwa_seed_text( $figure['label'] ?? '', $lang ),
			);
		}
		$pair = hcwa_seed_upsert_pair( 'impact:' . $figure_id, $by_lang );

		$value = $figure['value'] ?? null;
		foreach ( $pair as $lang => $post_id ) {
			hcwa_seed_meta(
				$post_id,
				array(
					'hcwa_value'         => is_numeric( $value ) ? (float) $value : null,
					'hcwa_suffix'        => (string) ( $figure['suffix'] ?? '' ),
					'hcwa_year'          => 0,
					'hcwa_verified'      => false,
					'hcwa_verified_by'   => '',
					'hcwa_verified_date' => '',
					'hcwa_order'         => count( $seeded[ $scope ] ),
					'hcwa_scope_type'    => $scopes[ $scope ][0],
					'hcwa_scope_id'      => $scopes[ $scope ][1],
					'hcwa_source_note'   => (string) ( $figure['source'] ?? '' ),
				)
			);
		}
	}

	WP_CLI::log( sprintf( 'Impact figures: %d in each language, all unverified.', array_sum( array_map( 'count', $seeded ) ) ) );
	return $seeded;
}

/**
 * News posts from news.json: the featured story placeholder and the real posts (titles,
 * dates and slugs from the current site; bodies are placeholders that link to the
 * original until the posts are migrated in Phase 3).
 *
 * @param array $news       content/draft/news.json.
 * @param array $categories Category IDs.
 * @return array<string, array{vi: int, en: int}> seed id => IDs.
 */
function hcwa_seed_posts( array $news, array $categories ): array {
	$category_keys = array(
		'news'         => 'news',
		'event'        => 'events',
		'story'        => 'stories',
		'announcement' => 'announcements',
	);
	$out           = array();

	$story = $news['featuredStory'] ?? null;
	if ( is_array( $story ) ) {
		$cat     = $category_keys[ $story['category'] ?? 'story' ] ?? 'stories';
		$by_lang = array();
		$body    = array(
			'vi' => 'Câu chuyện đã được HCWA duyệt, kể từ góc nhìn của trẻ hoặc gia đình. Tên nhân vật được thay đổi để bảo vệ các em.',
			'en' => 'An HCWA-approved story told from a child’s or family’s point of view. Names are changed to protect the children.',
		);
		$slugs   = array(
			'vi' => 'cau-chuyen-mau',
			'en' => 'sample-story',
		);
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$by_lang[ $lang ] = array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => hcwa_seed_draft( (string) ( $story['title'][ $lang ] ?? '' ) ),
				'post_name'     => $slugs[ $lang ],
				'post_excerpt'  => hcwa_seed_draft( (string) ( $story['excerpt'][ $lang ] ?? '' ) ),
				'post_content'  => hcwa_seed_block_p( hcwa_seed_draft( $body[ $lang ] ) ),
				'post_category' => array( (int) $categories[ $cat ][ $lang ] ),
			);
		}
		$out['post:featured-story'] = hcwa_seed_upsert_pair( 'post:featured-story', $by_lang );
	}

	foreach ( (array) ( $news['posts'] ?? array() ) as $post ) {
		$cat   = $category_keys[ $post['category'] ?? 'news' ] ?? 'news';
		$slugs = array(
			'vi' => hcwa_seed_last_segment( (string) ( $post['url']['vi'] ?? '' ) ),
			'en' => hcwa_seed_last_segment( (string) ( $post['url']['en'] ?? '' ) ),
		);
		if ( '' === $slugs['vi'] || '' === $slugs['en'] ) {
			WP_CLI::warning( 'A news.json post without a VI and EN URL was skipped.' );
			continue;
		}
		$date      = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $post['published'] ?? '' ) ) ? $post['published'] . ' 09:00:00' : '';
		$intro     = array(
			'vi' => hcwa_seed_draft( 'Nội dung bài viết sẽ được chuyển từ website hiện tại.' ),
			'en' => hcwa_seed_draft( 'The article text will be moved over from the current website.' ),
		);
		$original  = array(
			'vi' => 'Bài gốc:',
			'en' => 'Original post:',
		);
		$by_lang   = array();
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$url              = (string) $post['url'][ $lang ];
			$by_lang[ $lang ] = array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => (string) $post['title'][ $lang ],
				'post_name'     => $slugs[ $lang ],
				'post_content'  => hcwa_seed_block_p( $intro[ $lang ] ) . "\n\n" . hcwa_seed_block_p_link( $original[ $lang ], $url, $url ),
				'post_category' => array( (int) $categories[ $cat ][ $lang ] ),
			);
			if ( '' !== $date ) {
				$by_lang[ $lang ]['post_date']     = $date;
				$by_lang[ $lang ]['post_date_gmt'] = get_gmt_from_date( $date );
			}
		}
		$seed_id         = 'post:' . substr( $slugs['vi'], 0, 80 );
		$out[ $seed_id ] = hcwa_seed_upsert_pair( $seed_id, $by_lang );
	}

	WP_CLI::log( sprintf( 'News posts: %d in each language (one placeholder story).', count( $out ) ) );
	return $out;
}

/**
 * Static front page and posts page. Polylang maps them to the English translations.
 *
 * @param array $pages Page IDs.
 */
function hcwa_seed_reading( array $pages ): void {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', (int) $pages['home']['vi'] );
	update_option( 'page_for_posts', (int) $pages['news']['vi'] );
	WP_CLI::log( 'Reading: static front page and posts page set (Polylang follows the translations).' );
}

/**
 * Menu trees mirroring the theme's fallback menus (hcwa-2026/inc/navigation.php).
 * Each item: [ 'page' | 'anchor', page or anchor key, UI string key, children ].
 *
 * @return array<string, array>
 */
function hcwa_seed_menu_trees(): array {
	return array(
		'primary'        => array(
			array(
				'page',
				'about',
				'about',
				array(
					array( 'page', 'about', 'whoWeAre' ),
					array( 'page', 'ourStory', 'ourStory' ),
					array( 'page', 'team', 'team' ),
					array( 'page', 'partners', 'partners' ),
					array( 'page', 'policies', 'policies' ),
				),
			),
			array(
				'page',
				'ourWork',
				'ourWork',
				array(
					array( 'anchor', 'centers', 'centers' ),
					array( 'anchor', 'programs', 'programs' ),
					array( 'page', 'ourWork', 'allWork' ),
				),
			),
			array( 'page', 'impact', 'impact', array() ),
			array( 'page', 'news', 'news', array() ),
			array(
				'page',
				'getInvolved',
				'getInvolved',
				array(
					array( 'page', 'donate', 'donate' ),
					array( 'page', 'volunteer', 'volunteer' ),
					array( 'page', 'partner', 'partnerWithUs' ),
					array( 'page', 'inKind', 'inKind' ),
					array( 'anchor', 'bakery', 'bakery' ),
				),
			),
			array( 'page', 'contact', 'contact', array() ),
		),
		'footer-explore' => array(
			array( 'page', 'about', 'about', array() ),
			array( 'page', 'ourWork', 'ourWork', array() ),
			array( 'page', 'impact', 'impact', array() ),
			array( 'page', 'news', 'news', array() ),
			array( 'page', 'getInvolved', 'getInvolved', array() ),
		),
		'footer-trust'   => array(
			array( 'anchor', 'reports', 'annualReports', array() ),
			array( 'anchor', 'safeguarding', 'safeguarding', array() ),
			array( 'anchor', 'privacy', 'privacy', array() ),
			array( 'page', 'partners', 'partners', array() ),
		),
	);
}

/**
 * Adds one menu item and returns its ID.
 *
 * @param int    $menu_id   Menu term ID.
 * @param array  $item      [type, key, label key].
 * @param string $lang      'vi' or 'en'.
 * @param array  $pages     Page IDs.
 * @param int    $parent_id Parent item ID.
 * @param int    $position  Position in the menu.
 */
function hcwa_seed_menu_item( int $menu_id, array $item, string $lang, array $pages, int $parent_id, int $position ): int {
	list( $type, $key, $label ) = $item;
	$args                       = array(
		'menu-item-title'     => hcwa_t_in( $label, $lang ),
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => $parent_id,
		'menu-item-position'  => $position,
	);

	if ( 'page' === $type ) {
		$args['menu-item-type']      = 'post_type';
		$args['menu-item-object']    = 'page';
		$args['menu-item-object-id'] = (int) $pages[ $key ][ $lang ];
	} else {
		// A section of another page: root-relative, so a port change does not break it.
		$paths                  = hcwa_page_paths();
		$base                   = (string) $paths[ $key ]['base'];
		$args['menu-item-type'] = 'custom';
		$args['menu-item-url']  = wp_make_link_relative( (string) get_permalink( (int) $pages[ $base ][ $lang ] ) ) . $paths[ $key ][ $lang ];
	}

	$id = wp_update_nav_menu_item( $menu_id, 0, $args );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( "Could not add the menu item $key ($lang): " . $id->get_error_message() );
	}
	return (int) $id;
}

/**
 * One menu per location and language, assigned through Polylang's per-language locations.
 * Existing seeded menus are emptied and rebuilt.
 *
 * @param array $pages Page IDs.
 */
function hcwa_seed_menus( array $pages ): void {
	$names    = array(
		'primary'        => 'Main menu',
		'footer-explore' => 'Footer: Explore',
		'footer-trust'   => 'Footer: Reports & policies',
	);
	$assigned = array();

	foreach ( hcwa_seed_menu_trees() as $location => $tree ) {
		foreach ( array( 'vi', 'en' ) as $lang ) {
			$name = sprintf( '%s (%s)', $names[ $location ], strtoupper( $lang ) );
			$menu = wp_get_nav_menu_object( $name );
			if ( $menu ) {
				$menu_id = (int) $menu->term_id;
				foreach ( (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) ) as $old ) {
					wp_delete_post( (int) $old->ID, true );
				}
			} else {
				$menu_id = wp_create_nav_menu( $name );
				if ( is_wp_error( $menu_id ) ) {
					WP_CLI::error( "Could not create the menu '$name': " . $menu_id->get_error_message() );
				}
				$menu_id = (int) $menu_id;
			}

			$position = 0;
			foreach ( $tree as $entry ) {
				$parent_id = hcwa_seed_menu_item( $menu_id, $entry, $lang, $pages, 0, ++$position );
				foreach ( (array) ( $entry[3] ?? array() ) as $child ) {
					hcwa_seed_menu_item( $menu_id, $child, $lang, $pages, $parent_id, ++$position );
				}
			}
			$assigned[ $location ][ $lang ] = $menu_id;
		}
	}

	$theme   = get_stylesheet();
	$options = PLL()->model->options;
	$nav     = (array) $options->get( 'nav_menus' );
	foreach ( $assigned as $location => $by_lang ) {
		$nav[ $theme ][ $location ] = $by_lang;
	}
	$errors = $options->set( 'nav_menus', $nav );
	if ( $errors->has_errors() ) {
		WP_CLI::warning( 'Polylang menu locations: ' . implode( ' ', $errors->get_error_messages() ) );
	}
	$options->save();

	// Written to the option directly: get_theme_mod() in admin returns Polylang's extra
	// "location___en" entries, which must not be stored back.
	$mods = get_option( 'theme_mods_' . $theme );
	$mods = is_array( $mods ) ? $mods : array();
	$locs = isset( $mods['nav_menu_locations'] ) && is_array( $mods['nav_menu_locations'] ) ? $mods['nav_menu_locations'] : array();
	foreach ( $assigned as $location => $by_lang ) {
		$locs[ $location ] = $by_lang['vi'];
	}
	$mods['nav_menu_locations'] = $locs;
	update_option( 'theme_mods_' . $theme, $mods );

	WP_CLI::log( sprintf( 'Menus: %d locations, one menu per language each.', count( $assigned ) ) );
}

/**
 * Settings → HCWA defaults from site.json. Fills empty fields only and never touches
 * donation details: HCWA finance has not confirmed any (CLAUDE.md).
 *
 * @param array $site  content/draft/site.json.
 * @param array $pages Page IDs.
 */
function hcwa_seed_settings( array $site, array $pages, array $work = array(), array $world = array() ): void {
	$settings = get_option( 'hcwa_settings', array() );
	$settings = is_array( $settings ) ? $settings : array();

	$emails    = array_filter( array( (string) ( $site['email']['general'] ?? '' ), (string) ( $site['email']['sponsorship'] ?? '' ) ) );
	$phone     = (array) ( $site['phone'] ?? array() );
	$note      = $work['littleRose']['privacyNote'] ?? '';
	// "HCWA has confirmed the phone number and email addresses": on only when site.json marks
	// neither as draft (11-WP-PORT.md 3.2). Emails cannot carry a [DRAFT: …] wrapper.
	$confirmed = empty( $phone['draft'] ) && empty( $site['email']['draft'] ) && ( $phone || $emails );
	$defaults  = array(
		'short_name_vi'        => array( 'text', hcwa_seed_text( $site['shortName'] ?? '', 'vi' ) ),
		'short_name_en'        => array( 'text', hcwa_seed_text( $site['shortName'] ?? '', 'en' ) ),
		'little_rose_note_vi'  => array( 'textarea', hcwa_seed_text( $note, 'vi' ) ),
		'little_rose_note_en'  => array( 'textarea', hcwa_seed_text( $note, 'en' ) ),
		'contact_confirmed'    => array( 'checkbox', $confirmed ? '1' : '' ),
		// Staging only in every case: hcwa_traces_on() ignores it on production.
		'world_traces_enabled' => array( 'checkbox', ! empty( $world['traces']['enabled'] ) ? '1' : '' ),
		'announcement_enabled' => array( 'checkbox', '' ),
		'address_vi'           => array( 'textarea', hcwa_seed_text( $site['address'] ?? '', 'vi' ) ),
		'address_en'           => array( 'textarea', hcwa_seed_text( $site['address'] ?? '', 'en' ) ),
		'phone'                => array( 'text', empty( $phone['draft'] ) ? (string) ( $phone['display'] ?? '' ) : hcwa_seed_draft( (string) ( $phone['display'] ?? '' ) ) ),
		'phone_tel'            => array( 'tel', (string) ( $phone['tel'] ?? '' ) ),
		'emails'               => array( 'emails', implode( "\n", $emails ) ),
		'facebook'             => array( 'url', (string) ( $site['facebook'] ?? '' ) ),
		'map_url'              => array( 'url', (string) ( $site['mapUrl'] ?? '' ) ),
		'registration_vi'      => array( 'text', hcwa_seed_text( $site['registration'] ?? '', 'vi' ) ),
		'registration_en'      => array( 'text', hcwa_seed_text( $site['registration'] ?? '', 'en' ) ),
	);

	foreach ( $defaults as $key => $default ) {
		list( $type, $value ) = $default;
		// Filled-in fields are kept, except a seeded placeholder still carrying its draft marker:
		// that one follows site.json (and the seed's current [DRAFT: …] format).
		$stored = $settings[ $key ] ?? null;
		if ( null !== $stored && '' !== $stored && ! ( is_string( $stored ) && hcwa_contains_draft( $stored ) && $stored !== $value ) ) {
			continue;
		}
		$settings[ $key ] = function_exists( 'hcwa_sanitize_setting_value' ) ? hcwa_sanitize_setting_value( $type, $value ) : $value;
	}

	$map = isset( $settings['page_map'] ) && is_array( $settings['page_map'] ) ? $settings['page_map'] : array();
	foreach ( hcwa_page_keys() as $key ) {
		$current = absint( $map[ $key ] ?? 0 );
		if ( isset( $pages[ $key ] ) && ( ! $current || 'page' !== get_post_type( $current ) ) ) {
			$map[ $key ] = (int) $pages[ $key ]['vi'];
		}
	}
	$settings['page_map'] = $map;

	// Never seeded: the Little Rose door approval (world_lr_door_approved_*) and any donation_* value.
	foreach ( array_keys( $settings ) as $key ) {
		if ( 0 === strpos( (string) $key, 'world_lr_door_approved' ) && empty( $settings[ $key ] ) ) {
			unset( $settings[ $key ] );
		}
	}

	update_option( 'hcwa_settings', $settings );
	$kept = array_diff( array( 'short_name_vi', 'little_rose_note_vi', 'contact_confirmed', 'world_traces_enabled' ), array_keys( (array) get_option( 'hcwa_settings', array() ) ) );
	if ( $kept ) {
		WP_CLI::log( 'Settings → HCWA does not know ' . implode( ', ', $kept ) . ' yet (hcwa-core drops unknown keys); run the seed again once hcwa-core has the fields.' );
	}
	WP_CLI::log( 'Settings → HCWA: contact details from site.json (marked draft, not confirmed), page map filled, no donation details, no approvals.' );
}

/**
 * What the parity driver and the smoke test need to know about the seeded world
 * (docs/plan/11-WP-PORT.md 5.6): the 16 parity routes, each center's unit key and whether it
 * is protected, the programs' unit keys, the expected corridor beat count (all centers off
 * production; on production before the Little Rose door is approved, the unprotected ones), and
 * the draft program reports.
 *
 * @param array $work     content/draft/work.json.
 * @param array $centers  Center IDs.
 * @param array $programs Program IDs.
 * @param array $reports  Report IDs.
 */
function hcwa_seed_parity( array $work, array $centers, array $programs, array $reports ): array {
	$units = array();
	foreach ( array_values( (array) ( $work['centers'] ?? array() ) ) as $i => $center ) {
		$id = sanitize_title( (string) $center['id'] );
		if ( empty( $centers[ $id ] ) ) {
			continue;
		}
		$units[] = array(
			'id'        => $id,
			'unit'      => (string) ( $center['accent'] ?? $id ),
			'protected' => 'little-rose' === $id || (bool) get_post_meta( (int) $centers[ $id ]['vi'], 'hcwa_no_identifiable_children', true ),
			'order'     => $i,
			'paths'     => array(
				'vi' => hcwa_seed_path( (string) get_permalink( (int) $centers[ $id ]['vi'] ) ),
				'en' => hcwa_seed_path( (string) get_permalink( (int) $centers[ $id ]['en'] ) ),
			),
		);
	}
	$program_units = array();
	foreach ( (array) ( $work['programs'] ?? array() ) as $program ) {
		$id = sanitize_title( (string) $program['id'] );
		if ( empty( $programs[ $id ] ) ) {
			continue;
		}
		$program_units[] = array(
			'id'      => $id,
			'centers' => array_values( array_filter( array_map( 'strval', (array) ( $program['centers'] ?? array() ) ), static fn( $c ) => isset( $centers[ $c ] ) ) ),
			'paths'   => array(
				'vi' => hcwa_seed_path( (string) get_permalink( (int) $programs[ $id ]['vi'] ) ),
				'en' => hcwa_seed_path( (string) get_permalink( (int) $programs[ $id ]['en'] ) ),
			),
		);
	}
	$routes = array( '/', '/en/', '/hoat-dong/', '/en/our-work/', '/hoat-dong/co-so/tre-xanh/', '/en/our-work/centers/green-bamboo/', '/hoat-dong/du-an/cung-vuon-len/', '/en/our-work/programs/thrive-together/', '/chung-tay/', '/en/get-involved/', '/chung-tay/ung-ho/', '/en/get-involved/donate/', '/lien-he/', '/en/contact/', '/404.html', '/en/404.html' );
	$routes_file = hcwa_seed_repo() . '/scripts/wp-parity/routes.json';
	if ( is_readable( $routes_file ) ) {
		$data = json_decode( (string) file_get_contents( $routes_file ), true );
		if ( ! empty( $data['routes'] ) ) {
			$routes = array_values( array_map( static fn( $r ) => (string) $r['path'], (array) $data['routes'] ) );
		}
	}
	$protected = count( array_filter( $units, static fn( $u ) => $u['protected'] ) );
	return array(
		'routes'                     => $routes,
		'centers'                    => $units,
		'programs'                   => $program_units,
		'expected_beats'             => count( $units ),
		'expected_beats_production'  => count( $units ) - $protected,
		'reports'                    => array_keys( $reports ),
	);
}

/**
 * Writes the seeded URLs for scripts/wp-smoke.py, and warns where a URL differs from the
 * one the contract and content/draft expect.
 *
 * @param string $file       Manifest path.
 * @param array  $pages      Page IDs.
 * @param array  $centers    Center IDs.
 * @param array  $programs   Program IDs.
 * @param array  $posts      Post IDs.
 * @param array  $categories Category IDs.
 * @param array  $scopes     Seeded impact figures by scope.
 * @param array  $fixtures   Seeded test objects: 'refused_photo' => attachment ID.
 */
function hcwa_seed_write_manifest( string $file, array $pages, array $centers, array $programs, array $posts, array $categories, array $scopes, array $fixtures = array() ): void {
	$urls          = array();
	$parity        = (array) ( $fixtures['parity'] ?? array() );
	$parity_routes = (array) ( $parity['routes'] ?? array() );
	$add           = static function ( string $id, string $kind, array $pair, callable $link, bool $figures = false, array $expected = array() ) use ( &$urls, $parity_routes ) {
		$paths = array();
		foreach ( $pair as $lang => $object_id ) {
			$url = $link( (int) $object_id, $lang );
			if ( ! is_string( $url ) || '' === $url ) {
				WP_CLI::warning( "$id ($lang) has no URL; left out of the URL list." );
				continue;
			}
			$paths[ $lang ] = hcwa_seed_path( $url );
			if ( isset( $expected[ $lang ] ) && $expected[ $lang ] !== $paths[ $lang ] ) {
				WP_CLI::warning( "$id ($lang) is at {$paths[ $lang ]}, expected {$expected[ $lang ]}." );
			}
		}
		foreach ( $paths as $lang => $path ) {
			$entry = array(
				'id'          => $id,
				'kind'        => $kind,
				'lang'        => $lang,
				'path'        => $path,
				'translation' => $paths[ 'vi' === $lang ? 'en' : 'vi' ] ?? '',
				'figures'     => $figures,
			);
			// The parity driver's routes (scripts/wp-parity/routes.json) are marked, not repeated.
			if ( in_array( $path, $parity_routes, true ) ) {
				$entry['parity'] = true;
			}
			$urls[] = $entry;
		}
	};

	$post_link = static fn( int $id ) => get_permalink( $id );

	foreach ( $pages as $key => $pair ) {
		$expected = array(
			'vi' => '/' . hcwa_page_path( $key, 'vi' ),
			'en' => '/' . hcwa_page_path( $key, 'en' ),
		);
		$link     = 'home' === $key ? static fn( int $id, string $lang ) => pll_home_url( $lang ) : $post_link;
		// Home and Impact carry the organization's impact strip.
		$add( 'page:' . $key, 'page', $pair, $link, in_array( $key, array( 'home', 'impact' ), true ) && ! empty( $scopes['organization'] ), $expected );
	}
	foreach ( $centers as $id => $pair ) {
		$add( 'center:' . $id, 'center', $pair, $post_link, ! empty( $scopes[ $id ] ) );
	}
	foreach ( $programs as $id => $pair ) {
		$add( 'program:' . $id, 'program', $pair, $post_link, ! empty( $scopes[ $id ] ) );
	}
	foreach ( $posts as $id => $pair ) {
		$add( $id, 'post', $pair, $post_link );
	}
	foreach ( $categories as $key => $pair ) {
		$add( 'category:' . $key, 'category', $pair, static fn( int $id ) => get_term_link( $id, 'category' ) );
	}

	$manifest = array(
		'generated'   => gmdate( 'c' ),
		'home'        => (string) get_option( 'home' ),
		'environment' => wp_get_environment_type(),
		'urls'        => $urls,
		// Seeded test objects the smoke test probes (hcwa_seed_little_rose_photo()).
		'fixtures'    => array(
			'refused_photo'      => (int) ( $fixtures['refused_photo'] ?? 0 ),
			'refused_photo_file' => ! empty( $fixtures['refused_photo'] ) ? (string) pathinfo( (string) get_attached_file( (int) $fixtures['refused_photo'] ), PATHINFO_FILENAME ) : '',
			'refused_photo_page' => ! empty( $fixtures['refused_photo'] ) ? hcwa_seed_path( (string) get_permalink( (int) $centers['little-rose']['vi'] ) ) : '',
		),
		// Paths that must answer 404, in each language; the parity driver's two 404 routes too.
		'not_found'   => array(
			array(
				'lang' => 'vi',
				'path' => '/khong-co-trang-nay-hcwa-smoke/',
			),
			array(
				'lang' => 'en',
				'path' => '/en/no-such-page-hcwa-smoke/',
			),
			array(
				'lang' => 'vi',
				'path' => '/404.html',
				'kind' => 'parity',
			),
			array(
				'lang' => 'en',
				'path' => '/en/404.html',
				'kind' => 'parity',
			),
		),
		// Units, routes and the expected beat count for the parity driver and the smoke test.
		'parity'      => $parity,
	);

	$dir = dirname( $file );
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		WP_CLI::error( "Cannot create $dir for the URL list." );
	}
	$json = wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $json || false === file_put_contents( $file, $json . "\n" ) ) {
		WP_CLI::error( "Cannot write the URL list to $file." );
	}
	WP_CLI::log( sprintf( 'URL list: %d URLs written to %s.', count( $urls ), $file ) );
}

hcwa_seed_main( isset( $args ) && is_array( $args ) ? $args : (array) ( $GLOBALS['args'] ?? array() ) );
