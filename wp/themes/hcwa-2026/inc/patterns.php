<?php
/**
 * Block patterns (08 task 2.6). Each pattern is a builder function: called with no content it
 * returns the empty pattern for the inserter (placeholders tell editors what goes where);
 * called with content it returns the same structure filled in, which is how the local seed
 * builds the homepage and hub pages. One structure, two uses, so they can't drift apart.
 *
 * Sections are wrapped in a group locked to "content only": editors change words, links and
 * images, but can't break the layout. "Modify" in the toolbar unlocks it when really needed.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- block markup helpers ---------- */

function hcwa_b_attrs( $attrs ) {
	$attrs = array_filter(
		$attrs,
		function ( $v ) {
			return null !== $v && '' !== $v && false !== $v && array() !== $v;
		}
	);
	return $attrs ? ' ' . serialize_block_attributes( $attrs ) : '';
}

/** Paragraph. $html is inline HTML (already escaped); empty → placeholder. */
function hcwa_b_p( $html, $class = '', $placeholder = '', $draft = false ) {
	$attrs = array( 'className' => $class, 'placeholder' => '' === $html ? $placeholder : '', 'hcwaDraft' => $draft );
	return '<!-- wp:paragraph' . hcwa_b_attrs( $attrs ) . ' --><p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . $html . '</p><!-- /wp:paragraph -->';
}

function hcwa_b_h( $level, $html, $args = array() ) {
	$args  = wp_parse_args( $args, array( 'class' => '', 'anchor' => '', 'placeholder' => '', 'draft' => false ) );
	$attrs = array( 'level' => 2 === $level ? null : $level, 'anchor' => $args['anchor'], 'className' => $args['class'], 'placeholder' => '' === $html ? $args['placeholder'] : '', 'hcwaDraft' => $args['draft'] );
	$class = trim( 'wp-block-heading ' . $args['class'] );
	return '<!-- wp:heading' . hcwa_b_attrs( $attrs ) . ' --><h' . $level . ' class="' . esc_attr( $class ) . '"' . ( $args['anchor'] ? ' id="' . esc_attr( $args['anchor'] ) . '"' : '' ) . '>' . $html . '</h' . $level . '><!-- /wp:heading -->';
}

/**
 * Group block. Sections (tag "section") are full width ("alignfull"), so the editor shows them
 * edge to edge like the site does instead of squeezing them into the text column.
 */
function hcwa_b_group( $class, $inner, $args = array() ) {
	$args  = wp_parse_args( $args, array( 'tag' => 'div', 'lock' => false, 'draft' => false, 'anchor' => '', 'align' => null ) );
	$align = null === $args['align'] ? ( 'section' === $args['tag'] ? 'full' : '' ) : $args['align'];
	$attrs = array(
		'tagName'      => 'div' === $args['tag'] ? '' : $args['tag'],
		'anchor'       => $args['anchor'],
		'templateLock' => $args['lock'] ? 'contentOnly' : '',
		'align'        => $align,
		'className'    => $class,
		'hcwaDraft'    => $args['draft'],
		'layout'       => array( 'type' => 'default' ),
	);
	$classes = trim( 'wp-block-group ' . ( $align ? 'align' . $align . ' ' : '' ) . $class );
	return '<!-- wp:group' . hcwa_b_attrs( $attrs ) . ' --><' . $args['tag'] . ' class="' . esc_attr( $classes ) . '"' . ( $args['anchor'] ? ' id="' . esc_attr( $args['anchor'] ) . '"' : '' ) . '>' . $inner . '</' . $args['tag'] . '><!-- /wp:group -->';
}

/** A dynamic HCWA block (self-closing). */
function hcwa_b_dyn( $name, $attrs = array() ) {
	return '<!-- wp:hcwa/' . $name . hcwa_b_attrs( $attrs ) . ' /-->';
}

function hcwa_b_link( $label, $target, $kind = 'text', $class = '' ) {
	return hcwa_b_dyn( 'link', array( 'label' => $label, 'target' => $target, 'kind' => 'text' === $kind ? '' : $kind, 'className' => $class ) );
}

function hcwa_b_details( $summary, $answer_html, $draft = false ) {
	return '<!-- wp:details' . hcwa_b_attrs( array( 'hcwaDraft' => $draft ) ) . ' --><details class="wp-block-details"><summary>' . $summary . '</summary>' . hcwa_b_p( $answer_html, '', 'Câu trả lời · Answer' ) . '</details><!-- /wp:details -->';
}

/** Text value from a content array: escaped string, '' when missing. */
function hcwa_pc( $c, $key ) {
	return isset( $c[ $key ] ) ? esc_html( $c[ $key ] ) : '';
}

/** Draft flag from a content array ("draft" lists the keys that are drafts). */
function hcwa_pd( $c, $key ) {
	return ! empty( $c['draft'] ) && in_array( $key, (array) $c['draft'], true );
}

/* ---------- patterns ---------- */

/** Homepage hero: eyebrow, H1, intro, two buttons, 4:5 photo; impact strip attached below. */
function hcwa_pattern_hero( $c = array() ) {
	$text = hcwa_b_p( hcwa_pc( $c, 'eyebrow' ), 'hero__eyebrow', 'Dòng giới thiệu ngắn · Short eyebrow line', hcwa_pd( $c, 'eyebrow' ) )
		. hcwa_b_h( 1, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Tiêu đề chính · Main headline', 'draft' => hcwa_pd( $c, 'title' ) ) )
		. hcwa_b_p( hcwa_pc( $c, 'intro' ), 'hero__intro', 'Một hoặc hai câu: Hội làm gì cho trẻ em · One or two sentences: what HCWA does for children', hcwa_pd( $c, 'intro' ) )
		. hcwa_b_group(
			'hero__actions',
			hcwa_b_link( isset( $c['primary'] ) ? $c['primary'] : '', 'ourWork', 'primary' )
			. hcwa_b_link( isset( $c['secondary'] ) ? $c['secondary'] : '', 'getInvolved', 'secondary' )
		);
	$grid = hcwa_b_group(
		'container hero__grid',
		hcwa_b_group( 'hero__text', $text )
		. hcwa_b_dyn( 'image-slot', array( 'code' => 'A2', 'labelVi' => 'Ảnh chính trang chủ', 'labelEn' => 'Homepage hero', 'ratio' => '4:5', 'eager' => true, 'className' => 'hero__media', 'sizes' => '(min-width: 900px) 500px, 100vw' ) )
	);
	return hcwa_b_group( 'hero', $grid . hcwa_b_dyn( 'impact-strip', array( 'variant' => 'strip', 'min' => 2 ) ), array( 'tag' => 'section', 'lock' => true ) );
}

/** Section head: H2, optional intro, optional "see all" link on the right. */
function hcwa_section_head( $c, $link_target = '', $anchor = '' ) {
	$inner = hcwa_b_h( 2, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Tiêu đề mục · Section title', 'anchor' => $anchor, 'draft' => hcwa_pd( $c, 'title' ) ) );
	if ( array_key_exists( 'intro', $c ) ) {
		$inner .= hcwa_b_p( hcwa_pc( $c, 'intro' ), 'section-head__intro', 'Một dòng giới thiệu · One-line intro', hcwa_pd( $c, 'intro' ) );
	}
	if ( $link_target ) {
		$inner .= hcwa_b_link( isset( $c['link'] ) ? $c['link'] : '', $link_target );
	}
	return hcwa_b_group( 'section-head', $inner );
}

/** "What we do": lead text left, focus areas right (06-PAGES 3). */
function hcwa_pattern_what_we_do( $c = array() ) {
	$lead = hcwa_b_group(
		'split__lead',
		hcwa_b_h( 2, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Chúng tôi làm gì · What we do' ) )
		. hcwa_b_p( hcwa_pc( $c, 'summary' ), '', 'Tóm tắt hai câu · Two-sentence summary', hcwa_pd( $c, 'summary' ) )
	);
	return hcwa_b_group( 'section', hcwa_b_group( 'container split', $lead . hcwa_b_dyn( 'focus-areas' ) ), array( 'tag' => 'section', 'lock' => true ) );
}

function hcwa_pattern_centers( $c = array() ) {
	$c += array( 'intro' => '' );
	return hcwa_b_group( 'section section--sunken', hcwa_b_group( 'container', hcwa_section_head( $c, 'centers' ) . hcwa_b_dyn( 'center-grid' ) ), array( 'tag' => 'section', 'lock' => true ) );
}

function hcwa_pattern_programs( $c = array() ) {
	return hcwa_b_group( 'section', hcwa_b_group( 'container', hcwa_section_head( $c, 'programs' ) . hcwa_b_dyn( 'program-list', array( 'count' => 4 ) ) ), array( 'tag' => 'section', 'lock' => true ) );
}

function hcwa_pattern_latest( $c = array() ) {
	return hcwa_b_group( 'section section--line', hcwa_b_group( 'container', hcwa_section_head( $c, 'news' ) . hcwa_b_dyn( 'latest-news' ) ), array( 'tag' => 'section', 'lock' => true ) );
}

/**
 * Four ways to help (06-PAGES 7). $c['ways']: list of [ title, text, url, draft ]. The title
 * is a heading whose link covers the whole item. Illustration slots are A7 in the visuals list.
 */
function hcwa_pattern_ways( $c = array(), $heading_level = 3 ) {
	$ways = isset( $c['ways'] ) ? $c['ways'] : array_fill( 0, 4, array( '', '', '', false ) );
	$list = '';
	foreach ( $ways as $way ) {
		list( $title, $text, $url, $draft ) = array_pad( $way, 4, false );
		$heading = $title ? '<a href="' . esc_url( (string) $url ) . '">' . esc_html( $title ) . '</a>' : '';
		$list   .= hcwa_b_group(
			'way',
			hcwa_b_dyn( 'image-slot', array( 'code' => 'A7', 'labelVi' => 'Minh họa', 'labelEn' => 'Illustration', 'ratio' => '1:1', 'illustration' => true, 'className' => 'way__art', 'sizes' => '160px' ) )
			. hcwa_b_h( $heading_level, $heading, array( 'class' => 2 === $heading_level ? 'h3' : '', 'placeholder' => 'Cách giúp, có liên kết · Way to help, linked' ) )
			. hcwa_b_p( esc_html( (string) $text ), '', 'Một câu · One sentence', (bool) $draft )
		);
	}
	$head = isset( $c['title'] ) ? hcwa_section_head( $c ) : '';
	// Sunken on the homepage; plain on Get Involved, where the FAQ below is the sunken band.
	$tone = isset( $c['tone'] ) ? $c['tone'] : 'section section--sunken';
	return hcwa_b_group( $tone, hcwa_b_group( 'container', $head . hcwa_b_group( 'ways', $list ) ), array( 'tag' => 'section', 'lock' => true ) );
}

function hcwa_pattern_partners( $c = array() ) {
	$links = hcwa_b_group(
		'logo-wall__links',
		hcwa_b_link( isset( $c['all'] ) ? $c['all'] : '', 'partners' ) . hcwa_b_link( isset( $c['become'] ) ? $c['become'] : '', 'partner' )
	);
	return hcwa_b_group( 'section', hcwa_b_group( 'container', hcwa_section_head( $c ) . hcwa_b_dyn( 'partner-wall' ) . $links ), array( 'tag' => 'section', 'lock' => true ) );
}

/** Blue contact band (06-PAGES 9): question, Contact us, phone and email from settings. */
function hcwa_pattern_contact_band( $c = array() ) {
	$inner = hcwa_b_h( 2, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Bạn có câu hỏi? · Have a question?' ) )
		. hcwa_b_group( 'cta-band__actions', hcwa_b_link( isset( $c['button'] ) ? $c['button'] : '', 'contact', 'on-blue' ) )
		. hcwa_b_dyn( 'contact-details', array( 'variant' => 'band' ) );
	return hcwa_b_group( 'cta-band', hcwa_b_group( 'container cta-band__inner', $inner ), array( 'tag' => 'section', 'lock' => true ) );
}

/** Blue CTA band with up to two buttons (05-DESIGN: one sentence, max two buttons). */
function hcwa_pattern_cta( $c = array() ) {
	$actions = hcwa_b_link( isset( $c['primary'] ) ? $c['primary'] : '', isset( $c['primary_target'] ) ? $c['primary_target'] : 'donate', 'primary' )
		. hcwa_b_link( isset( $c['secondary'] ) ? $c['secondary'] : '', isset( $c['secondary_target'] ) ? $c['secondary_target'] : 'getInvolved', 'on-blue' );
	$inner   = hcwa_b_h( 2, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Một câu kêu gọi · One sentence' ) ) . hcwa_b_group( 'cta-band__actions', $actions );
	return hcwa_b_group( 'cta-band', hcwa_b_group( 'container cta-band__inner', $inner ), array( 'tag' => 'section', 'lock' => true ) );
}

/** FAQ: lead text + accordion of questions (core Details blocks). $c['items']: [ q, a, draft ]. */
function hcwa_pattern_faq( $c = array() ) {
	$items = isset( $c['items'] ) ? $c['items'] : array_fill( 0, 5, array( '', '', false ) );
	$acc   = '';
	foreach ( $items as $item ) {
		list( $q, $a, $draft ) = array_pad( $item, 3, false );
		$acc .= hcwa_b_details( esc_html( (string) $q ), esc_html( (string) $a ), (bool) $draft );
	}
	$lead = hcwa_b_group(
		'split__lead',
		hcwa_b_h( 2, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Câu hỏi thường gặp · Common questions' ) )
		. hcwa_b_p( hcwa_pc( $c, 'intro' ), '', 'Một dòng · One line', hcwa_pd( $c, 'intro' ) )
		. hcwa_b_link( isset( $c['link'] ) ? $c['link'] : '', 'contact' )
	);
	// The accordion is not locked: editors add and remove questions.
	return hcwa_b_group( 'section section--sunken', hcwa_b_group( 'container split', $lead . hcwa_b_group( 'accordion', $acc ) ), array( 'tag' => 'section' ) );
}

/** Plain text section: heading and paragraphs at reading width. */
function hcwa_pattern_text( $c = array() ) {
	$paras = isset( $c['paragraphs'] ) ? $c['paragraphs'] : array( '' );
	// The empty pattern offers a heading; filled-in text without a title gets none (no empty <h2>).
	$inner = isset( $c['title'] ) || ! isset( $c['paragraphs'] ) ? hcwa_b_h( 2, hcwa_pc( $c, 'title' ), array( 'placeholder' => 'Tiêu đề · Heading', 'anchor' => isset( $c['anchor'] ) ? $c['anchor'] : '' ) ) : '';
	foreach ( $paras as $p ) {
		$inner .= hcwa_b_p( esc_html( (string) $p ), '', 'Nội dung · Text', ! empty( $c['draft'] ) );
	}
	return hcwa_b_group( 'section section--tight', hcwa_b_group( 'container prose', $inner ), array( 'tag' => 'section' ) );
}

/** Our Work hub: focus-area chips, centers, programs, and the note on the difference. */
function hcwa_pattern_our_work( $c = array() ) {
	$c       += array( 'centers' => '', 'programs' => '', 'note' => '', 'anchors' => array( 'co-so', 'du-an' ) );
	$filters = hcwa_b_group( 'container', hcwa_b_dyn( 'work-filters' ) );
	$centers = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_h( 2, esc_html( $c['centers'] ), array( 'anchor' => $c['anchors'][0], 'placeholder' => 'Cơ sở · Centers' ) ) . hcwa_b_dyn( 'center-grid', array( 'followFilter' => true ) ) ), array( 'tag' => 'section' ) );
	$progs   = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_h( 2, esc_html( $c['programs'] ), array( 'anchor' => $c['anchors'][1], 'placeholder' => 'Dự án & chương trình · Projects & programs' ) ) . hcwa_b_dyn( 'program-list', array( 'followFilter' => true, 'count' => 0 ) ) ), array( 'tag' => 'section' ) );
	$note    = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_group( 'note', hcwa_b_p( esc_html( $c['note'] ), '', 'Cơ sở khác dự án thế nào · How centers differ from programs', hcwa_pd( $c, 'note' ) ) ) ) );
	return hcwa_b_group( 'our-work', $filters . $centers . $progs . $note, array( 'lock' => true, 'align' => 'full' ) );
}

/**
 * Donate: what a gift does, then the methods (bank in Vietnam open by default, from abroad,
 * PayPal, a specific center). Every detail comes from Settings → HCWA, finance-confirmed.
 */
function hcwa_pattern_donate( $c = array() ) {
	$c     += array( 'gifts_title' => '', 'gifts' => array(), 'methods_title' => '', 'vietnam' => '', 'abroad' => '', 'paypal' => '', 'center' => '', 'center_text' => '' );
	$gifts = '';
	foreach ( $c['gifts'] as $gift ) {
		$gifts .= hcwa_b_p( esc_html( $gift ), 'gift', 'Số tiền và điều nó làm được · Amount and what it does' );
	}
	// Gift amounts are figures: until HCWA re-confirms them the whole section is a draft.
	$gifts   = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_h( 2, esc_html( $c['gifts_title'] ), array( 'class' => 'h3', 'placeholder' => 'Khoản ủng hộ giúp được gì · What your gift does' ) ) . hcwa_b_group( 'gift-list', $gifts ) ), array( 'tag' => 'section', 'draft' => (bool) $c['gifts'] ) );
	$method  = function ( $title, $inner, $open = false ) {
		return '<!-- wp:details' . hcwa_b_attrs( array( 'showContent' => $open, 'className' => 'method' ) ) . ' --><details class="wp-block-details method"' . ( $open ? ' open' : '' ) . '><summary>' . esc_html( $title ) . '</summary>' . $inner . '</details><!-- /wp:details -->';
	};
	$methods = $method( $c['vietnam'], hcwa_b_dyn( 'donation-details', array( 'method' => 'vietnam' ) ), true )
		. $method( $c['abroad'], hcwa_b_dyn( 'donation-details', array( 'method' => 'abroad' ) ) . hcwa_b_dyn( 'donation-details', array( 'method' => 'paypal' ) ) )
		. $method( $c['center'], hcwa_b_p( esc_html( $c['center_text'] ), '', 'Ủng hộ cho một cơ sở · Giving to one center', true ) );
	$methods = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_h( 2, esc_html( $c['methods_title'] ), array( 'placeholder' => 'Cách ủng hộ · How to give' ) ) . hcwa_b_group( 'methods', $methods ) ), array( 'tag' => 'section' ) );
	return hcwa_b_group( 'donate', $gifts . $methods, array( 'lock' => true, 'align' => 'full' ) );
}

/* ---------- registration ---------- */

add_action(
	'init',
	function () {
		register_block_pattern_category( 'hcwa', array( 'label' => 'HCWA' ) );
		$patterns = array(
			'hero'          => array( __( 'Homepage hero with impact figures', 'hcwa-2026' ), 'hcwa_pattern_hero' ),
			'what-we-do'    => array( __( 'What we do (focus areas)', 'hcwa-2026' ), 'hcwa_pattern_what_we_do' ),
			'centers'       => array( __( 'Our centers (cards)', 'hcwa-2026' ), 'hcwa_pattern_centers' ),
			'programs'      => array( __( 'Projects & programs (rows)', 'hcwa-2026' ), 'hcwa_pattern_programs' ),
			'latest'        => array( __( 'Latest news', 'hcwa-2026' ), 'hcwa_pattern_latest' ),
			'ways'          => array( __( 'Ways to help', 'hcwa-2026' ), 'hcwa_pattern_ways' ),
			'partners'      => array( __( 'Partners', 'hcwa-2026' ), 'hcwa_pattern_partners' ),
			'contact-band'  => array( __( 'Contact band (blue)', 'hcwa-2026' ), 'hcwa_pattern_contact_band' ),
			'cta'           => array( __( 'Call to action band (blue)', 'hcwa-2026' ), 'hcwa_pattern_cta' ),
			'faq'           => array( __( 'Questions and answers', 'hcwa-2026' ), 'hcwa_pattern_faq' ),
			'text'          => array( __( 'Text section', 'hcwa-2026' ), 'hcwa_pattern_text' ),
			'our-work'      => array( __( 'Our Work hub (filters, centers, programs)', 'hcwa-2026' ), 'hcwa_pattern_our_work' ),
			'donate'        => array( __( 'Donate: gift examples and methods', 'hcwa-2026' ), 'hcwa_pattern_donate' ),
		);
		foreach ( $patterns as $slug => $pattern ) {
			register_block_pattern(
				'hcwa/' . $slug,
				array(
					'title'      => $pattern[0],
					'categories' => array( 'hcwa' ),
					'content'    => call_user_func( $pattern[1] ),
				)
			);
		}
	}
);
