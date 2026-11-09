<?php
/**
 * What sections pages render and how (11-WP-PORT.md 2.1, 3.2, 3.4, 3.5): contact lines,
 * unit keys, the Little Rose rules, traces, forms, the center body, and
 * hcwa_render_sections(), which prints a page built from hcwa/* blocks without the
 * the_content filters but with the photo gate on every block staff could add.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * One contact line (2.1 rule 12). Draft while "contact confirmed" is off in Settings → HCWA
 * or while the text has a draft marker; on production a draft line has no text, so callers
 * print a line only when its text is not empty and never a tel: or mailto: link without text.
 *
 * @param string    $which      phone | email | sponsorship.
 * @param bool|null $production Defaults to hcwa_is_production().
 * @return array{text: string, href: string, draft: bool}
 */
function hcwa_contact_line( string $which, ?bool $production = null ): array {
	$production = $production ?? hcwa_is_production();
	$confirmed  = (bool) hcwa_setting( 'contact_confirmed', false );
	$text       = '';
	$href       = '';

	if ( 'phone' === $which ) {
		$shown = hcwa_draft_text( hcwa_attr_text( (string) hcwa_setting( 'phone', '' ) ), false );
		$text  = $shown['text'];
		$tel   = (string) hcwa_setting( 'phone_tel', '' );
		$tel   = '' !== $tel ? $tel : (string) preg_replace( '/[^\d+]/', '', $text );
		$href  = '' !== $tel ? 'tel:' . $tel : '';
		$draft = ! $confirmed || $shown['draft'];
	} else {
		$emails = hcwa_contact_emails();
		$text   = (string) ( $emails[ 'sponsorship' === $which ? 1 : 0 ] ?? '' );
		$href   = '' !== $text ? 'mailto:' . $text : '';
		$draft  = ! $confirmed;
	}

	if ( '' === $text || ( $production && $draft ) ) {
		return array(
			'text'  => '',
			'href'  => '',
			'draft' => $draft,
		);
	}
	return array(
		'text'  => $text,
		'href'  => $href,
		'draft' => $draft,
	);
}

/**
 * A center's unit key (its accent): data-unit, #center-{unit}, ?center={unit}, the still
 * names and the emblem. '' when the accent is not one of the prototype's units.
 *
 * @param int $center_id Center ID (any language; the accent is shared).
 */
function hcwa_center_unit( int $center_id ): string {
	$accent = (string) get_post_meta( $center_id, 'hcwa_accent', true );
	return in_array( $accent, hcwa_unit_keys(), true ) ? $accent : '';
}

/**
 * The unit colour for --unit: the unit's token, or HCWA blue for a center without a unit
 * (a stored 'blue', listed by wp hcwa coverage).
 *
 * @param string $unit Unit key or ''.
 */
function hcwa_unit_color( string $unit ): string {
	return '' !== $unit ? 'var(--color-unit-' . $unit . ')' : 'var(--color-blue-700)';
}

/**
 * The emblem a unit's transom throws (prototype-data.php 'emblems'), hoaThi by default.
 *
 * @param string $unit Unit key.
 */
function hcwa_unit_emblem( string $unit ): string {
	$emblems = hcwa_proto( 'emblems', array() );
	return is_array( $emblems ) && isset( $emblems[ $unit ] ) ? (string) $emblems[ $unit ] : 'hoaThi';
}

/**
 * Whether a center is protected: "No identifiable children" on any translation, or the
 * Little Rose unit. Fails closed: either one is enough.
 *
 * @param int $center_id Center ID.
 */
function hcwa_center_is_protected( int $center_id ): bool {
	if ( $center_id <= 0 ) {
		return false;
	}
	return 'little-rose' === (string) get_post_meta( $center_id, 'hcwa_accent', true ) || hcwa_meta_flag_any( $center_id, 'hcwa_no_identifiable_children' );
}

/**
 * Whether the Little Rose door may show in any form (3.4): always off production; on
 * production only once HCWA's safeguarding lead has filled in both approval fields.
 *
 * @param bool|null $production Defaults to hcwa_is_production().
 */
function hcwa_little_rose_door_allowed( ?bool $production = null ): bool {
	if ( ! ( $production ?? hcwa_is_production() ) ) {
		return true;
	}
	return '' !== trim( (string) hcwa_setting( 'world_lr_door_approved_by', '' ) ) && hcwa_is_ymd( (string) hcwa_setting( 'world_lr_door_approved_date', '' ) );
}

/**
 * Whether the corridor carries data-traces: switched on in Settings → HCWA, and never on
 * production (the port's hard limit).
 *
 * @param bool|null $production Defaults to hcwa_is_production().
 */
function hcwa_traces_on( ?bool $production = null ): bool {
	return (bool) hcwa_setting( 'world_traces_enabled', false ) && ! ( $production ?? hcwa_is_production() );
}

/**
 * Whether forms may print on production. False until task 2.7 gives them a real handler;
 * staging always prints them, as the prototype does (3.5).
 */
function hcwa_forms_enabled(): bool {
	/**
	 * Filters whether the contact and donation forms print on production.
	 *
	 * @param bool $enabled False until a form handler exists.
	 */
	return (bool) apply_filters( 'hcwa_forms_enabled', false );
}

/**
 * The centers a program runs at, resolved to the current language and published: the
 * shared hcwa_center_ids, else the old one-item hcwa_center_id.
 *
 * @param int $program_id Program ID (any language).
 * @return int[]
 */
function hcwa_program_center_ids( int $program_id ): array {
	$ids = hcwa_id_list( get_post_meta( $program_id, 'hcwa_center_ids', true ) );
	if ( ! $ids ) {
		$one = (int) get_post_meta( $program_id, 'hcwa_center_id', true );
		$ids = $one > 0 ? array( $one ) : array();
	}
	$lang = hcwa_lang();
	$out  = array();
	foreach ( $ids as $id ) {
		$local = hcwa_translation_id( $id, $lang );
		if ( $local > 0 && 'publish' === get_post_status( $local ) && 'hcwa_center' === get_post_type( $local ) ) {
			$out[] = $local;
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * A center's story and history from its post content (2.5): the text before <!--more-->
 * is the story, after it the history; each paragraph is the inner text of a core/paragraph
 * block, or blank-line separated text without blocks, through hcwa_attr_text() and
 * hcwa_draft_text(). No the_content filter runs; images and other blocks are not printed.
 * Paragraphs left with no text (draft on production) are dropped. 'draft' lists one flag per
 * kept paragraph, story first, then history.
 *
 * @param WP_Post   $center     Center.
 * @param bool|null $production Defaults to hcwa_is_production().
 * @return array{story: string[], history: string[], draft: bool[]}
 */
function hcwa_center_body( WP_Post $center, ?bool $production = null ): array {
	$production = $production ?? hcwa_is_production();
	$out        = array(
		'story'   => array(),
		'history' => array(),
		'draft'   => array(),
	);
	foreach ( hcwa_body_parts( (string) $center->post_content ) as $part => $paragraphs ) {
		foreach ( $paragraphs as $raw ) {
			$shown = hcwa_draft_text( hcwa_attr_text( $raw ), $production );
			if ( '' === $shown['text'] ) {
				continue;
			}
			$out[ $part ][] = $shown['text'];
			$out['draft'][] = $shown['draft'];
		}
	}
	return $out;
}

/**
 * Paragraph texts (still HTML-encoded) of a center's content, before and after the "more"
 * break: core/paragraph blocks around a core/more block, or, without blocks, blank-line
 * separated text around <!--more-->.
 *
 * @param string $content Post content.
 * @return array{story: string[], history: string[]}
 */
function hcwa_body_parts( string $content ): array {
	$out = array(
		'story'   => array(),
		'history' => array(),
	);
	if ( '' === trim( $content ) ) {
		return $out;
	}
	if ( false !== strpos( $content, '<!-- wp:' ) && function_exists( 'parse_blocks' ) ) {
		$part = 'story';
		foreach ( parse_blocks( $content ) as $block ) {
			$name = $block['blockName'] ?? null;
			if ( 'core/more' === $name || ( null === $name && false !== strpos( (string) ( $block['innerHTML'] ?? '' ), '<!--more' ) ) ) {
				$part = 'history';
				continue;
			}
			if ( 'core/paragraph' === $name ) {
				$out[ $part ][] = (string) preg_replace( '#^\s*<p\b[^>]*>|</p>\s*$#i', '', (string) ( $block['innerHTML'] ?? '' ) );
			}
		}
		return $out;
	}
	$halves = preg_split( '/<!--more(.*?)?-->/', $content, 2 );
	foreach ( array( 'story', 'history' ) as $i => $part ) {
		$chunks       = preg_split( '/\R\s*\R/u', trim( (string) preg_replace( '#</?p\b[^>]*>#i', "\n\n", (string) ( $halves[ $i ] ?? '' ) ) ) );
		$out[ $part ] = array_values( array_filter( array_map( 'trim', (array) $chunks ), 'strlen' ) );
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Sections pages
 * ---------------------------------------------------------------------- */

/**
 * Block names removed from a sections page wherever they sit: raw HTML, classic content and
 * shortcodes could carry an unapproved image or any markup between the sections.
 *
 * @return array<int, string|null>
 */
function hcwa_sections_banned_blocks(): array {
	return array( 'core/html', 'core/freeform', 'core/shortcode', null );
}

/**
 * The first hcwa/sections block of a parsed tree, depth first, or null.
 *
 * @param array $blocks Parsed blocks.
 */
function hcwa_sections_find( array $blocks ): ?array {
	foreach ( $blocks as $block ) {
		if ( 'hcwa/sections' === ( $block['blockName'] ?? null ) ) {
			return $block;
		}
		$inner = hcwa_sections_find( (array) ( $block['innerBlocks'] ?? array() ) );
		if ( null !== $inner ) {
			return $inner;
		}
	}
	return null;
}

/**
 * Cleans a parsed block (2.1 rule 4): inside hcwa/sections and hcwa/day every literal HTML
 * string between inner blocks is dropped, and at any depth every banned block is removed
 * together with its place in innerContent.
 *
 * @param array $block Parsed block.
 * @return array
 */
function hcwa_sections_clean( array $block ): array {
	$container = in_array( $block['blockName'] ?? null, array( 'hcwa/sections', 'hcwa/day' ), true );
	$banned    = hcwa_sections_banned_blocks();
	$inner     = array();
	$content   = array();
	$index     = 0;

	foreach ( (array) ( $block['innerContent'] ?? array() ) as $chunk ) {
		if ( is_string( $chunk ) ) {
			if ( ! $container ) {
				$content[] = $chunk;
			}
			continue;
		}
		$child = $block['innerBlocks'][ $index ] ?? null;
		++$index;
		if ( ! is_array( $child ) || in_array( $child['blockName'] ?? null, $banned, true ) ) {
			continue;
		}
		$inner[]   = hcwa_sections_clean( $child );
		$content[] = null;
	}

	$block['innerBlocks']  = $inner;
	$block['innerContent'] = $content;
	if ( $container ) {
		$block['innerHTML'] = '';
	}
	return $block;
}

/**
 * render_block filter while a sections page renders: the output of every block that is not
 * one of ours (a core block staff added after unlocking a section) goes through the
 * classic-HTML half of the photo gate exactly as the_content would run it.
 *
 * @param string $html  Block output.
 * @param array  $block Parsed block.
 * @return string
 */
function hcwa_sections_gate_block( $html, $block ) {
	$name = (string) ( $block['blockName'] ?? '' );
	if ( ! is_string( $html ) || '' === $html || 0 === strpos( $name, 'hcwa/' ) ) {
		return $html;
	}
	hcwa_prime_content_images( $html );
	return (string) hcwa_content_strip_refused( wp_filter_content_tags( $html, 'the_content' ) );
}

/**
 * Prints a page built from hcwa/* blocks (2.1 rule 4). A password-protected page gets its
 * header and the password form. Otherwise only the first hcwa/sections block is rendered,
 * after hcwa_sections_clean(), with hcwa_sections_gate_block() on every block that is not
 * ours. Returns '' when the page has no hcwa/sections block.
 *
 * @param WP_Post $post Page.
 */
function hcwa_render_sections( WP_Post $post ): string {
	if ( post_password_required( $post ) ) {
		$title = hcwa_attr_text( (string) get_the_title( $post ) );
		return hcwa_sun_head_html( array( 'title' => $title ) ) . '<div class="day day--donate"><div class="container entry-content">' . get_the_password_form( $post ) . '</div></div>';
	}

	$sections = hcwa_sections_find( parse_blocks( (string) $post->post_content ) );
	if ( null === $sections ) {
		return '';
	}

	add_filter( 'render_block', 'hcwa_sections_gate_block', 10, 2 );
	$html = render_block( hcwa_sections_clean( $sections ) );
	remove_filter( 'render_block', 'hcwa_sections_gate_block', 10 );

	return $html;
}

/**
 * Whether a page's content holds an hcwa/sections block.
 *
 * @param WP_Post|null $post Page.
 */
function hcwa_is_sections_page( ?WP_Post $post ): bool {
	return $post instanceof WP_Post && false !== strpos( (string) $post->post_content, '<!-- wp:hcwa/sections' );
}
