<?php
/**
 * Production refuses to publish draft text (contract gating rule 4).
 *
 * On the live site, saving a post as published or scheduled while its title, content,
 * excerpt, one of our text fields or a Yoast SEO text still contains a draft marker
 * ([DRAFT: …] or a bracket placeholder such as [N], [year], [năm], [Story title]; the rule
 * is the prototype's, exported into prototype-data.php) puts it back to draft and explains
 * why.
 * Covers the classic editor, quick edit, REST (block editor), the block editor's
 * separate meta box save and scheduled posts published by cron. Staging is not affected.
 * `wp hcwa draft-scan` lists offenders (run it after any database push to production).
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The placeholder tokens of the old rule. hcwa_contains_draft() no longer reads them (it
 * uses the prototype's exported bracket rule, which catches every one of these); kept
 * because scripts/wp-smoke.py's older content check (DRAFT_TOKENS) is compared with it.
 *
 * @return string[]
 */
function hcwa_draft_tokens(): array {
	return array(
		'N',
		'year',
		'năm',
		'amount',
		'số tiền',
		'start',
		'end',
		'năm bắt đầu',
		'năm kết thúc',
		'age range',
		'độ tuổi',
		'ward',
		'phường',
		'date',
		'ngày',
		'month/year',
		'tháng/năm',
	);
}

/**
 * Whether text or post content has a draft marker (contract rule 4, 11-WP-PORT.md 3.3): the
 * same rule hcwa_draft_text() applies, the prototype's exported BLOCKERS bracket rule plus
 * "[DRAFT" in any case, with the digit-only footnote exception ([1]). Content is read as a
 * visitor would see it: registered shortcodes ([gallery ids="1,2"]) are removed first, and a
 * block comment delimiter counts only through the text values of its attributes, so
 * structural attributes ({"lines":["phone","email"]}) pass and a draft text attribute
 * ({"title":"[DRAFT: …]"}) does not.
 *
 * @param string $s Text or HTML.
 */
function hcwa_contains_draft( string $s ): bool {
	if ( '' === $s ) {
		return false;
	}
	if ( false !== strpos( $s, '&#91;' ) || false !== strpos( $s, '&lbrack;' ) ) {
		$s = html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
	if ( false === strpos( $s, '[' ) ) {
		return false;
	}
	$s = hcwa_nfc( $s );
	$p = hcwa_draft_patterns();
	if ( preg_match( $p['draft'], $s ) ) {
		return true;
	}

	$s = hcwa_draft_block_text( hcwa_strip_registered_shortcodes( $s ) );
	$s = (string) preg_replace_callback(
		$p['token'],
		static fn( array $m ): string => preg_match( $p['footnote'], $m[0] ) ? '' : $m[0],
		$s
	);
	return (bool) preg_match( $p['bracket'], '>' . $s . '<' );
}

/**
 * Removes the registered shortcodes (WordPress's $shortcode_tags) from text, opening and
 * closing tags alike; other bracketed text stays.
 *
 * @param string $s Text.
 */
function hcwa_strip_registered_shortcodes( string $s ): string {
	global $shortcode_tags;
	$tags = is_array( $shortcode_tags ) ? array_keys( $shortcode_tags ) : array();
	if ( ! $tags || false === strpos( $s, '[' ) ) {
		return $s;
	}
	$re = '/\[\/?(?:' . implode( '|', array_map( static fn( $t ) => preg_quote( (string) $t, '/' ), $tags ) ) . ')(?=[\s\]\/])[^\]]*\]/u';
	return (string) preg_replace( $re, '', $s );
}

/**
 * Block comment delimiters replaced by the text values of their attributes, each as its
 * own text node ('>' … '<'), so the bracket rule sees attribute text and not the JSON.
 *
 * @param string $s Post content.
 */
function hcwa_draft_block_text( string $s ): string {
	if ( false === strpos( $s, '<!--' ) ) {
		return $s;
	}
	return (string) preg_replace_callback(
		'/<!--\s+\/?wp:[a-z0-9_\/-]+(\s+(\{.*?\}))?\s+\/?-->/s',
		static function ( array $m ): string {
			$attrs = isset( $m[2] ) ? json_decode( $m[2], true ) : null;
			if ( ! is_array( $attrs ) ) {
				return '';
			}
			$texts = array();
			array_walk_recursive(
				$attrs,
				static function ( $v ) use ( &$texts ) {
					if ( is_string( $v ) && '' !== $v ) {
						$texts[] = strtr( $v, array( '<' => ' ', '>' => ' ' ) );
					}
				}
			);
			return $texts ? '>' . implode( '<>', $texts ) . '<' : '';
		},
		$s
	);
}

/**
 * Post types the guard covers: public types, our admin-only types and synced patterns.
 *
 * @return string[]
 */
function hcwa_guarded_post_types(): array {
	$types = array_merge( array_values( get_post_types( array( 'public' => true ) ) ), hcwa_post_types(), array( 'wp_block' ) );
	return array_values( array_diff( array_unique( $types ), array( 'attachment' ) ) );
}

/**
 * Labels of the fields of a post that contain draft markers.
 *
 * @param array $fields label => text.
 * @return string[]
 */
function hcwa_draft_fields( array $fields ): array {
	$found = array();
	foreach ( $fields as $label => $text ) {
		if ( is_string( $text ) && hcwa_contains_draft( $text ) ) {
			$found[] = (string) $label;
		}
	}
	return $found;
}

/**
 * Request-level memory between the insert filter and the after-insert action, keyed by
 * post ID ('new' for a post that has no ID yet).
 *
 * @param int|string $key   Post ID or 'new'.
 * @param array|null $found Fields to remember; null reads and forgets the entry.
 * @return string[]
 */
function hcwa_publish_guard_pending( $key, ?array $found = null ): array {
	static $pending = array();

	if ( null !== $found ) {
		if ( $found ) {
			$pending[ $key ] = $found;
		} else {
			unset( $pending[ $key ] );
		}
		return $found;
	}

	$out = $pending[ $key ] ?? array();
	unset( $pending[ $key ] );
	return $out;
}

/**
 * wp_insert_post_data: switches publish/future to draft when title, content or excerpt
 * contain draft markers.
 *
 * @param array $data    Slashed, sanitized post data.
 * @param array $postarr Slashed post array.
 * @return array
 */
function hcwa_publish_guard_data( $data, $postarr ) {
	if ( ! hcwa_is_production() || ! is_array( $data ) || ! in_array( $data['post_type'] ?? '', hcwa_guarded_post_types(), true ) ) {
		return $data;
	}

	$key = ! empty( $postarr['ID'] ) ? (int) $postarr['ID'] : 'new';

	if ( ! in_array( $data['post_status'] ?? '', array( 'publish', 'future' ), true ) ) {
		hcwa_publish_guard_pending( $key, array() );
		return $data;
	}

	$found = hcwa_draft_fields(
		array(
			__( 'Title', 'hcwa' )   => wp_unslash( (string) ( $data['post_title'] ?? '' ) ),
			__( 'Content', 'hcwa' ) => wp_unslash( (string) ( $data['post_content'] ?? '' ) ),
			__( 'Excerpt', 'hcwa' ) => wp_unslash( (string) ( $data['post_excerpt'] ?? '' ) ),
		)
	);

	if ( $found ) {
		$data['post_status'] = 'draft';
	}
	hcwa_publish_guard_pending( $key, $found );

	return $data;
}
add_filter( 'wp_insert_post_data', 'hcwa_publish_guard_data', 99, 2 );

/**
 * wp_after_insert_post (fires after REST meta and after meta box saves): also checks our
 * registered text fields and Yoast's SEO texts (hcwa_publish_guard_texts()), reverts to
 * draft if needed and records the reason.
 *
 * Title, content and excerpt are checked here again: scheduled posts are published by
 * cron through wp_publish_post(), which sets the status with $wpdb and never runs
 * wp_insert_post_data (wp-includes/post.php), so a post scheduled where the guard was not
 * active (a staging copy pushed live) would otherwise go live with draft text.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 * @param bool    $update  Whether an existing post was updated.
 */
function hcwa_publish_guard_after( $post_id, $post, $update = true ): void {
	static $running = false;

	if ( $running || ! hcwa_is_production() || ! $post instanceof WP_Post || ! in_array( $post->post_type, hcwa_guarded_post_types(), true ) ) {
		return;
	}

	$found = hcwa_publish_guard_pending( (int) $post_id );
	if ( ! $update ) {
		$found = array_merge( $found, hcwa_publish_guard_pending( 'new' ) );
	}

	$live = in_array( $post->post_status, array( 'publish', 'future' ), true );

	if ( $live ) {
		$found = array_merge( $found, hcwa_draft_fields( hcwa_publish_guard_texts( $post ) ) );

		if ( $found ) {
			$running = true;
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'draft',
				)
			);
			$running = false;
		}
	}

	if ( $found ) {
		update_post_meta( $post->ID, '_hcwa_publish_blocked', implode( ', ', array_unique( $found ) ) );
		set_transient( 'hcwa_publish_blocked_' . get_current_user_id(), (int) $post->ID, 5 * MINUTE_IN_SECONDS );
	} elseif ( $live ) {
		delete_post_meta( $post->ID, '_hcwa_publish_blocked' );
	}
}
add_action( 'wp_after_insert_post', 'hcwa_publish_guard_after', 20, 3 );

/**
 * The texts of a post the guard checks, by the label editors see: title, content, excerpt,
 * our registered text fields and, with Yoast SEO active, its per-post SEO and social texts
 * (printed in <title>, the meta description and share previews; seo.php also keeps them
 * off the live site).
 *
 * @param WP_Post   $post Post.
 * @param bool|null $seo  Whether Yoast SEO is active; defaults to defined( 'WPSEO_VERSION' ).
 * @return array<string, string> Label => text.
 */
function hcwa_publish_guard_texts( WP_Post $post, ?bool $seo = null ): array {
	$texts = array(
		__( 'Title', 'hcwa' )   => (string) $post->post_title,
		__( 'Content', 'hcwa' ) => (string) $post->post_content,
		__( 'Excerpt', 'hcwa' ) => (string) $post->post_excerpt,
	);
	foreach ( get_registered_meta_keys( 'post', $post->post_type ) as $key => $args ) {
		if ( 0 === strpos( $key, 'hcwa_' ) && 'string' === ( $args['type'] ?? '' ) ) {
			$texts[ (string) ( $args['description'] ?? $key ) ] = (string) get_post_meta( $post->ID, $key, true );
		}
	}
	if ( $seo ?? defined( 'WPSEO_VERSION' ) ) {
		foreach ( hcwa_seo_text_meta_keys() as $key => $label ) {
			$texts[ $label ] = (string) get_post_meta( $post->ID, $key, true );
		}
	}
	return $texts;
}

/**
 * The explanation shown to editors.
 *
 * @param string $fields Comma-separated field names.
 */
function hcwa_publish_guard_message( string $fields ): string {
	return sprintf(
		/* translators: %s: list of fields */
		__( 'Not published. This is the live site, and the text still contains draft markers such as [DRAFT: …], [N] or [year] (in: %s). The post was saved as a draft. Replace the markers with approved text, then publish again.', 'hcwa' ),
		$fields
	);
}

/**
 * Admin notice after a blocked save (classic editor, quick edit after reload, lists).
 */
function hcwa_publish_guard_notice(): void {
	if ( ! hcwa_is_production() ) {
		return;
	}

	$post_id = 0;
	$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( $screen && 'post' === $screen->base && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		$post_id = (int) get_transient( 'hcwa_publish_blocked_' . get_current_user_id() );
		delete_transient( 'hcwa_publish_blocked_' . get_current_user_id() );
	}

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = (string) get_post_meta( $post_id, '_hcwa_publish_blocked', true );
	if ( '' === $fields || 'draft' !== get_post_status( $post_id ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html( get_the_title( $post_id ) ),
		esc_html( hcwa_publish_guard_message( $fields ) )
	);
}
add_action( 'admin_notices', 'hcwa_publish_guard_notice' );

/**
 * Exposes the reason to the block editor (edit context only).
 */
function hcwa_publish_guard_rest_field(): void {
	register_rest_field(
		hcwa_guarded_post_types(),
		'hcwa_publish_blocked',
		array(
			'get_callback' => static function ( $item ) {
				$id = (int) ( $item['id'] ?? 0 );
				return $id && current_user_can( 'edit_post', $id ) ? (string) get_post_meta( $id, '_hcwa_publish_blocked', true ) : '';
			},
			'schema'       => array(
				'description' => __( 'Fields that kept this post from being published on the live site.', 'hcwa' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			),
		)
	);
}
add_action( 'rest_api_init', 'hcwa_publish_guard_rest_field' );

/**
 * Block editor: after each save, asks the REST API whether the guard stepped in and
 * shows a warning (assets/editor-guard.js). Production only.
 */
function hcwa_publish_guard_editor_assets(): void {
	if ( ! hcwa_is_production() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	$rest_bases = array();
	foreach ( hcwa_guarded_post_types() as $post_type ) {
		$object = get_post_type_object( $post_type );
		if ( $object && $object->show_in_rest ) {
			$rest_bases[ $post_type ] = $object->rest_base ? $object->rest_base : $post_type;
		}
	}

	wp_enqueue_script( 'hcwa-editor-guard', HCWA_CORE_URL . 'assets/editor-guard.js', array( 'wp-data', 'wp-notices', 'wp-api-fetch', 'wp-editor' ), HCWA_CORE_VERSION, true );
	wp_add_inline_script(
		'hcwa-editor-guard',
		'window.hcwaPublishGuard = ' . wp_json_encode(
			array(
				'restBases' => $rest_bases,
				'message'   => hcwa_publish_guard_message( '%s' ),
				'reload'    => __( 'Reload the editor', 'hcwa' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'hcwa_publish_guard_editor_assets' );
