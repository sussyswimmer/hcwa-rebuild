<?php
/**
 * Text helpers shared by the blocks and the theme (11-WP-PORT.md 1.3, 3.2, 3.3): the
 * prototype's data (prototype-data.php, generated), plain-text attributes, the draft rule,
 * the word split of the passage statement, the Vietnamese no-break glossary, hyphenated
 * words kept whole, dates split for the big Archivo date, icons and the quatrefoil.
 *
 * Every function here returns plain text unless its name or comment says it returns
 * escaped HTML.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The prototype's exported data (includes/prototype-data.php, written by
 * `npm --prefix prototype run export:wp`; schema in 11-WP-PORT.md 6.4). An empty array
 * while the file is missing: the blocks then print text only (no icons, no tiles).
 *
 * @param array|null $replace Tests only: replaces the data for the rest of the request.
 * @return array
 */
function hcwa_prototype_data( ?array $replace = null ): array {
	static $data = null;

	if ( null !== $replace ) {
		$data = $replace;
		return $data;
	}
	if ( null === $data ) {
		$data = array();
		if ( defined( 'HCWA_CORE_PROTOTYPE_DATA' ) ) {
			$loaded = include HCWA_CORE_PROTOTYPE_DATA;
			$data   = is_array( $loaded ) ? $loaded : array();
		}
	}
	return $data;
}

/**
 * One key of the prototype's data.
 *
 * @param string $key     Top-level key (areas, units, emblems, icons, guards …).
 * @param mixed  $default Value while the key is missing.
 * @return mixed
 */
function hcwa_proto( string $key, $default = array() ) {
	$data = hcwa_prototype_data();
	return array_key_exists( $key, $data ) ? $data[ $key ] : $default;
}

/**
 * The four focus areas in display order (education, protection, family, policy).
 *
 * @return string[]
 */
function hcwa_area_keys(): array {
	$areas = hcwa_proto( 'areas', array() );
	return is_array( $areas ) && $areas ? array_values( array_map( 'strval', $areas ) ) : array( 'education', 'protection', 'family', 'policy' );
}

/**
 * The unit keys of the centers the prototype knows (green-bamboo, little-rose, cau-han).
 *
 * @return string[]
 */
function hcwa_unit_keys(): array {
	$units = hcwa_proto( 'units', array() );
	return is_array( $units ) && $units ? array_values( array_map( 'strval', $units ) ) : array( 'green-bamboo', 'little-rose', 'cau-han' );
}

/**
 * Normalises to Unicode NFC when the intl extension is there.
 *
 * @param string $s Text.
 */
function hcwa_nfc( string $s ): string {
	if ( '' === $s || ! class_exists( 'Normalizer' ) ) {
		return $s;
	}
	$n = Normalizer::normalize( $s, Normalizer::FORM_C );
	return false === $n ? $s : $n;
}

/**
 * A stored text attribute as plain text (2.1 rule 13): a soft break becomes a space, tags
 * are dropped, entities decoded (so kses's &amp; and a pasted &nbsp; read as the
 * characters), NFC, whitespace collapsed (a no-break space is kept: it is a character,
 * not layout). Escape at output.
 *
 * @param string $v Stored value.
 */
function hcwa_attr_text( string $v ): string {
	if ( '' === $v ) {
		return '';
	}
	$v = (string) preg_replace( '/<br\s*\/?>/i', ' ', $v );
	$v = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $v ) : strip_tags( $v );
	$v = html_entity_decode( $v, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$v = hcwa_nfc( $v );
	$v = (string) preg_replace( '/[ \t\r\n\f\v]+/u', ' ', $v );
	return trim( $v, " \t\r\n" );
}

/**
 * The draft rule as compiled patterns: '[DRAFT' (any case), the prototype's exported bracket
 * rule (render.mjs BLOCKERS, applied to '>' . text . '<') and the digit-only footnote
 * exception (11-WP-PORT.md 3.3). Read once from prototype-data.php; without it the same
 * bracket rule is used, so a missing export can only make the rule stricter, never looser.
 *
 * @return array{draft: string, bracket: string, footnote: string, token: string}
 */
function hcwa_draft_patterns(): array {
	static $cache = null;
	static $for   = null;

	$guards = hcwa_proto( 'guards', array() );
	$key    = md5( serialize( $guards ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- cache key only.
	if ( null !== $cache && $for === $key ) {
		return $cache;
	}

	$bracket  = '/>[^<]*\[[^\]<]{1,80}\][^<]*</u';
	$footnote = '/^\[\d+\]$/u';
	foreach ( (array) ( $guards['blockers'] ?? array() ) as $blocker ) {
		$pattern = hcwa_pcre( (string) ( is_array( $blocker ) ? ( $blocker['pattern'] ?? $blocker[0] ?? '' ) : $blocker ) );
		if ( '' !== $pattern && false !== strpos( $pattern, '\]' ) && false === stripos( $pattern, 'DRAFT' ) ) {
			$bracket = $pattern;
		}
	}
	if ( ! empty( $guards['footnote'] ) ) {
		$footnote = hcwa_pcre( (string) $guards['footnote'] );
	}

	$for   = $key;
	$cache = array(
		'draft'    => '/\[\s*DRAFT/iu',
		'bracket'  => $bracket,
		'footnote' => $footnote,
		// One bracket token, for the footnote exception (same length limit as the bracket rule).
		'token'    => '/\[[^\]<]{1,80}\]/u',
	);
	return $cache;
}

/**
 * A pattern from the export as a delimited PCRE with the u flag ('/…/u' or the bare source).
 *
 * @param string $p Pattern.
 */
function hcwa_pcre( string $p ): string {
	if ( '' === $p ) {
		return '';
	}
	if ( preg_match( '#^([/~#%]).*\1[imsuxU]*$#s', $p ) ) {
		$p = false === strpos( substr( $p, (int) strrpos( $p, $p[0] ) ), 'u' ) ? $p . 'u' : $p;
	} else {
		$p = '~' . str_replace( '~', '\~', $p ) . '~u';
	}
	return false === @preg_match( $p, '' ) ? '' : $p; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- an invalid export must not warn on every page.
}

/**
 * Whether plain visible text has a draft marker: '[DRAFT' or a bracket placeholder token
 * ([N], [năm], [Story title], [Second need, …]); a digit-only footnote ([1]) passes.
 *
 * @param string $text Plain text (not HTML).
 */
function hcwa_text_has_draft( string $text ): bool {
	if ( '' === $text || false === strpos( $text, '[' ) ) {
		return false;
	}
	$p    = hcwa_draft_patterns();
	$text = hcwa_nfc( $text );
	if ( preg_match( $p['draft'], $text ) ) {
		return true;
	}
	$rest = (string) preg_replace_callback(
		$p['token'],
		static fn( array $m ): string => preg_match( $p['footnote'], $m[0] ) ? '' : $m[0],
		$text
	);
	return (bool) preg_match( $p['bracket'], '>' . strtr( $rest, array( '<' => ' ', '>' => ' ' ) ) . '<' );
}

/**
 * How a stored text prints (2.1 rule 7, 3.3). Off production: a whole-string [DRAFT: …]
 * wrapper prints its inner text, other text prints as stored, and 'draft' says whether the
 * element carries data-draft. On production any draft marker returns no text, so the caller
 * leaves the element out.
 *
 * @param string    $s          Stored text (plain; run hcwa_attr_text() first on attributes).
 * @param bool|null $production Defaults to hcwa_is_production().
 * @return array{text: string, draft: bool}
 */
function hcwa_draft_text( string $s, ?bool $production = null ): array {
	$production = $production ?? hcwa_is_production();
	$s          = trim( $s );
	if ( ! hcwa_text_has_draft( $s ) ) {
		return array(
			'text'  => $s,
			'draft' => false,
		);
	}
	if ( $production ) {
		return array(
			'text'  => '',
			'draft' => true,
		);
	}
	return array(
		'text'  => hcwa_draft_unwrap( $s ),
		'draft' => true,
	);
}

/**
 * The inner text of a whole-string [DRAFT: …] wrapper whose brackets balance, else the text
 * unchanged. "[DRAFT: [Second need]]" gives "[Second need]", as the prototype prints it.
 *
 * @param string $s Text.
 */
function hcwa_draft_unwrap( string $s ): string {
	if ( ! preg_match( '/^\[\s*DRAFT\s*:?\s*(.*)\]$/isu', $s, $m ) ) {
		return $s;
	}
	$depth = 0;
	$inner = $m[1];
	$len   = strlen( $inner );
	for ( $i = 0; $i < $len; $i++ ) {
		if ( '[' === $inner[ $i ] ) {
			++$depth;
		} elseif ( ']' === $inner[ $i ] && --$depth < 0 ) {
			return $s;
		}
	}
	return 0 === $depth ? trim( $inner ) : $s;
}

/**
 * The passage statement split into word spans for the sunlit reading
 * (prototype src/lib/split-words.mjs): each word keeps its following space inside its span.
 *
 * @param string $text Plain text.
 * @return array{count: int, html: string} html is escaped.
 */
function hcwa_split_words( string $text ): array {
	$words = preg_split( '/\s+/u', trim( hcwa_nfc( $text ) ), -1, PREG_SPLIT_NO_EMPTY );
	$words = is_array( $words ) ? $words : array();
	$last  = count( $words ) - 1;
	$html  = '';
	foreach ( $words as $i => $w ) {
		$html .= '<span class="w" style="--w:' . (int) $i . '">' . hcwa_esc( $w ) . ( $i < $last ? ' ' : '' ) . '</span>';
	}
	return array(
		'count' => count( $words ),
		'html'  => $html,
	);
}

/**
 * The prototype's escaping (src/lib/html.mjs): & < > " ' as entities, nothing else touched.
 * esc_html() would also turn an existing entity back into itself; this never double-encodes
 * because its input is plain text.
 *
 * @param string $s Plain text.
 */
function hcwa_esc( string $s ): string {
	return strtr(
		$s,
		array(
			'&'  => '&amp;',
			'<'  => '&lt;',
			'>'  => '&gt;',
			'"'  => '&quot;',
			"'"  => '&#39;',
		)
	);
}

/**
 * Heading compounds joined with U+00A0 so they never break across lines (prototype
 * src/lib/vikeep.mjs): the glossary from prototype-data.php, longest first, matched only
 * where no letter sits on either side, any letter case. Returns plain text.
 *
 * @param string $text Plain text.
 */
function hcwa_vi_keep( string $text ): string {
	static $re  = null;
	static $for = null;

	$text     = hcwa_nfc( $text );
	$glossary = hcwa_proto( 'vi_keep', array() );
	if ( ! is_array( $glossary ) || ! $glossary || '' === $text ) {
		return $text;
	}
	$key = md5( implode( "\n", $glossary ) );
	if ( $for !== $key ) {
		$list = array_map( 'strval', $glossary );
		usort( $list, static fn( $a, $b ) => mb_strlen( $b ) <=> mb_strlen( $a ) );
		$re  = '/(?<![\p{L}\p{M}])(?:' . implode( '|', array_map( static fn( $g ) => preg_quote( hcwa_nfc( $g ), '/' ), $list ) ) . ')(?![\p{L}\p{M}])/iu';
		$for = $key;
	}
	return (string) preg_replace_callback( $re, static fn( array $m ): string => str_replace( ' ', "\u{00A0}", $m[0] ), $text );
}

/**
 * Hyphenated compounds kept on one line with span.nowrap (prototype keepHyphens). Returns
 * escaped HTML.
 *
 * @param string $text Plain text.
 */
function hcwa_keep_hyphens( string $text ): string {
	return (string) preg_replace_callback(
		'/[\p{L}\p{M}]+(?:-[\p{L}\p{M}]+)+/u',
		static fn( array $m ): string => '<span class="nowrap">' . $m[0] . '</span>',
		hcwa_esc( $text )
	);
}

/**
 * A date cut for the dated list: the day and month, the separator before the year, the year
 * ('25/05', '/', '2026' in Vietnamese; '25 May', ' ', '2026' in English).
 *
 * @param string      $ymd  'Y-m-d' or a MySQL datetime.
 * @param string|null $lang 'vi' or 'en'.
 * @return array{0: string, 1: string, 2: string}
 */
function hcwa_date_parts( string $ymd, ?string $lang = null ): array {
	$s = hcwa_date_in( $ymd, $lang ?? hcwa_lang() );
	if ( preg_match( '/^(.*?)(\D)(\d{4})$/u', $s, $m ) ) {
		return array( $m[1], $m[2], $m[3] );
	}
	return array( $s, '', '' );
}

/**
 * A Lucide outline icon from the prototype's paths (prototype-data.php 'icons'), as the
 * prototype's icon(). '' for an unknown name or while the export is missing.
 *
 * @param string $name Icon name (arrow, chevron, close, pin, phone, mail, external, …).
 * @param string $cls  Class attribute.
 */
function hcwa_icon( string $name, string $cls = 'icon' ): string {
	$icons = hcwa_proto( 'icons', array() );
	if ( ! is_array( $icons ) || ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return '<svg class="' . hcwa_esc( $cls ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
}

/**
 * The Hoa thị quatrefoil (the last sun of the day), as the prototype's quatrefoil().
 *
 * @param string $cls Class attribute.
 */
function hcwa_quatrefoil( string $cls = 'quatrefoil' ): string {
	$path = hcwa_proto( 'quatrefoil', '' );
	if ( ! is_string( $path ) || '' === $path ) {
		return '';
	}
	// The export may give the whole <path …/> or only its d attribute.
	$inner = 0 === strpos( ltrim( $path ), '<' ) ? $path : '<path d="' . hcwa_esc( $path ) . '"/>';
	return '<svg class="' . hcwa_esc( $cls ) . '" viewBox="-112 -112 224 224" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}
