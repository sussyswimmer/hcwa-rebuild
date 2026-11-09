<?php
/**
 * The page sections, one function per hcwa/* block (11-WP-PORT.md 2.4, 2.5, 4.1) and per
 * section of the center and program pages, each printing the prototype's markup
 * (prototype/src/pages/home/*.mjs, pages/work.mjs, pages/involve.mjs) with no wrapper.
 * blocks/<name>/render.php only calls the function here, so the markup is unit-tested
 * without WordPress's block registry.
 *
 * The production variants of 2.1 rules 7 and 10 to 12, 3.4 and 3.5 are decided here.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Home (H2 to H10)
 * ---------------------------------------------------------------------- */

/**
 * H2 hero. On production with a draft title the heading is the site name, visually hidden,
 * so aria-labelledby and the one-H1 rule hold (2.1 rule 10).
 *
 * @param array $a title, intro.
 */
function hcwa_render_hero( array $a ): string {
	$title = hcwa_blocks_text( $a, 'title' );
	$intro = hcwa_blocks_text( $a, 'intro' );
	$art   = hcwa_art();
	$media = '<div class="hero__media lit" aria-hidden="true">' . "\n  "
		. $art->poster( 'hero', 'base', array( 'cls' => 'lit__base' ) ) . "\n  "
		. $art->poster( 'hero', 'lightroom', array( 'cls' => 'lit__light' ) ) . "\n  "
		. $art->poster( 'hero', 'lightscreen', array( 'cls' => 'lit__screen' ) ) . "\n"
		. '</div>';
	$h1    = '' !== $title['text']
		? '<h1 class="hero__title" id="hero-title" data-text-safe><span' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</span></h1>'
		: '<h1 class="sr-only" id="hero-title">' . esc_html( hcwa_attr_text( (string) get_bloginfo( 'name' ) ) ) . '</h1>';
	$p     = '' !== $intro['text'] ? '<p class="hero__intro" data-text-safe><span' . hcwa_draft_attr( $intro['draft'] ) . '>' . hcwa_keep_hyphens( $intro['text'] ) . '</span></p>' : '';

	return "\n" . '<section class="hero" id="hero" data-world="room" aria-labelledby="hero-title">' . "\n  "
		. $media . "\n  "
		. '<div class="hero__stage" data-stage aria-hidden="true"></div>' . "\n  "
		. '<div class="container hero__inner">' . "\n    "
		. $h1 . "\n    "
		. $p . "\n    "
		. '<div class="hero__actions" data-text-safe>' . "\n      "
		. hcwa_button_html( hcwa_page_url( 'ourWork' ), hcwa_t( 'exploreWork' ) ) . "\n      "
		. hcwa_button_html( hcwa_page_url( 'getInvolved' ), hcwa_t( 'getInvolved' ), 'secondary' ) . "\n    "
		. '</div>' . "\n    "
		. hcwa_pause_toggle_html( 'hero__pause', 'display' ) . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * H3 passage. The exit still shows the home corridor's three doors, so it is withheld on
 * production until the Little Rose door is approved (the guard returns '' for it, 3.4).
 *
 * @param array $a title, statement.
 */
function hcwa_render_passage( array $a ): string {
	$title = hcwa_blocks_text( $a, 'title' );
	$text  = hcwa_blocks_text( $a, 'statement' );
	$h2    = '' !== $title['text'] ? '<h2 class="passage__title" id="what-title">' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</h2>' : '';
	$p     = '';
	if ( '' !== $text['text'] ) {
		$words = hcwa_split_words( $text['text'] );
		$p     = '<p class="statement"' . hcwa_draft_attr( $text['draft'] ) . ' style="--n:' . (int) $words['count'] . '"><span class="sr-only">' . esc_html( $text['text'] ) . '</span><span class="statement__words" aria-hidden="true">' . $words['html'] . '</span></p>';
	}
	return "\n" . '<section class="passage" id="passage" data-world="passage" data-stage' . ( '' !== $h2 ? ' aria-labelledby="what-title"' : '' ) . ">\n  "
		. '<div class="passage__frame">' . "\n    "
		. '<div class="passage__halo" aria-hidden="true"></div>' . "\n    "
		. '<div class="container passage__text">' . "\n      "
		. $h2 . "\n      "
		. $p . "\n    "
		. '</div>' . "\n  "
		. '</div>' . "\n  "
		. '<div class="passage__exit" aria-hidden="true">' . hcwa_art()->still(
			'passage-corridor',
			array(
				'cls'   => 'passage__still',
				'sizes' => '100vw',
			)
		) . '</div>' . "\n"
		. '</section>';
}

/**
 * The Little Rose privacy note in the current language, as it prints.
 *
 * @return array{text: string, draft: bool}
 */
function hcwa_little_rose_note(): array {
	return hcwa_draft_text( hcwa_attr_text( (string) hcwa_setting( 'little_rose_note_' . hcwa_lang(), '' ) ) );
}

/**
 * H4 corridor. One door per printed bay (only these cards carry data-beat, 2.1 rule 6). On
 * production before the Little Rose door is approved, a protected center has no bay and no
 * rail entry and appears in words in the head; the other bays show their single-door card
 * still instead of the three-door corridor crop (3.4).
 *
 * @param array $a intro.
 */
function hcwa_render_corridor( array $a ): string {
	$intro   = hcwa_blocks_text( $a, 'intro' );
	$allowed = hcwa_little_rose_door_allowed();
	$art     = hcwa_art();
	$note    = hcwa_little_rose_note();
	$bays    = array();
	$words   = '';

	foreach ( hcwa_blocks_centers() as $post ) {
		$c = hcwa_center_view( $post );
		if ( $c['protected'] && ! $allowed ) {
			$words .= "\n          " . '<p class="bay__note"' . hcwa_draft_attr( $c['name']['draft'] || $note['draft'] ) . '>' . esc_html( trim( $c['name']['text'] . '. ' . $note['text'] ) ) . '</p>'
				. "\n          " . '<p class="bay__links">' . hcwa_text_link_html( $c['url'], hcwa_t( 'aboutShelter' ) ) . hcwa_text_link_html( hcwa_page_url( 'bakery' ), hcwa_t( 'bakery' ) ) . '</p>';
			continue;
		}
		$bays[] = $c;
	}

	$rail  = '';
	$cards = '';
	foreach ( $bays as $i => $c ) {
		$lr     = $c['protected'];
		$spill  = $lr ? 'var(--color-sun-100)' : $c['color'];
		$still  = $allowed ? 'bay-' . $c['key'] : 'door-' . $c['key'];
		$links  = $lr
			? hcwa_text_link_html( $c['url'], hcwa_t( 'aboutShelter' ) ) . hcwa_text_link_html( hcwa_page_url( 'bakery' ), hcwa_t( 'bakery' ) )
			: hcwa_text_link_html( $c['url'], hcwa_t( 'visitCenter' ) );
		$since  = null !== $c['since'] ? '<p class="bay__since"' . hcwa_draft_attr( $c['since']['draft'] ) . '>' . esc_html( $c['since']['text'] ) . '</p>' : '';
		$serves = '' !== $c['serves']['text'] ? '<p class="bay__serves"' . hcwa_draft_attr( $c['serves']['draft'] ) . '>' . esc_html( $c['serves']['text'] ) . '</p>' : '';
		$where  = ! $lr && '' !== $c['location']['text'] ? '<p class="bay__where"' . hcwa_draft_attr( $c['location']['draft'] ) . '>' . esc_html( $c['location']['text'] ) . '</p>' : '';
		$lrnote = $lr && '' !== $note['text'] ? '<p class="bay__note"' . hcwa_draft_attr( $note['draft'] ) . '>' . esc_html( $note['text'] ) . '</p>' : '';

		$rail  .= '<li><a href="#center-' . esc_attr( $c['key'] ) . '" data-to="' . (int) $i . '" style="' . esc_attr( hcwa_unit_style( $c ) ) . '">' . $art->screen_art( $c['emblem'], 'rail__transom' ) . '<span>' . esc_html( $c['short']['text'] ) . '</span></a></li>';
		$cards .= '<li class="bay" id="center-' . esc_attr( $c['key'] ) . '" data-beat data-unit="' . esc_attr( $c['key'] ) . '" data-door="' . ( $lr ? 'closed' : 'open' ) . '" data-emblem="' . esc_attr( $c['emblem'] ) . '" style="' . esc_attr( hcwa_unit_style( $c ) . ';--spill:' . $spill . ';--i:' . $i ) . '">' . "\n  "
			. '<div class="bay__view" aria-hidden="true">' . $art->still( $still, array( 'sizes' => '(min-width: 1024px) 76vw, 100vw' ) ) . '</div>' . "\n  "
			. '<div class="bay__text">' . "\n    "
			. '<h3 class="bay__name">' . esc_html( hcwa_vi_keep( $c['name']['text'] ) ) . '</h3>' . "\n    "
			. $since . "\n    "
			. $serves . "\n    "
			. $where . "\n    "
			. $lrnote . "\n    "
			. '<p class="bay__links">' . $links . '</p>' . "\n  "
			. '</div>' . "\n"
			. '</li>';
	}

	$anchor = 'en' === hcwa_lang() ? '#centers' : '#co-so';
	return "\n" . '<section class="bays-section" id="centers" data-world="corridor" data-stage' . ( hcwa_traces_on() ? ' data-traces' : '' ) . ' style="--n:' . count( $bays ) . '" aria-labelledby="centers-title">' . "\n  "
		. '<a class="bays__skip" href="#work">' . esc_html( hcwa_t( 'skipCenters' ) ) . '</a>' . "\n  "
		. '<div class="bays__track" data-stage-track>' . "\n    "
		. '<div class="bays__frame">' . "\n      "
		. '<div class="bays__glass" aria-hidden="true"></div>' . "\n      "
		. '<div class="container bays__panel">' . "\n        "
		. '<div class="bays__head">' . "\n          "
		. '<h2 id="centers-title">' . esc_html( hcwa_vi_keep( hcwa_t( 'centers' ) ) ) . '</h2>' . "\n          "
		. ( '' !== $intro['text'] ? '<p class="bays__intro"' . hcwa_draft_attr( $intro['draft'] ) . '>' . esc_html( $intro['text'] ) . '</p>' : '' )
		. $words . "\n        "
		. '</div>' . "\n        "
		. '<nav class="bays__rail" aria-label="' . esc_attr( hcwa_t( 'centersRail' ) ) . '">' . "\n  <ol>" . $rail . "</ol>\n</nav>\n        "
		. '<ol class="bays">' . $cards . '</ol>' . "\n        "
		. '<p class="bays__all">' . hcwa_text_link_html( hcwa_page_url( 'ourWork' ) . $anchor, hcwa_t( 'allWork' ) ) . '</p>' . "\n      "
		. '</div>' . "\n    "
		. '</div>' . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * One focus area on the home floor (prototype gach()).
 *
 * @param array $area From hcwa_blocks_areas().
 */
function hcwa_gach_html( array $area ): string {
	$art = hcwa_art();
	$key = $area['key'];
	return '<li class="gach gach--' . esc_attr( $key ) . '">' . "\n    "
		. '<div class="gach__floor" aria-hidden="true">' . $art->gach_art( $key, 'art' ) . '<span class="gach__light">' . $art->gach_art( $key, 'lit' ) . '</span></div>' . "\n    "
		. '<div class="gach__body">' . "\n      "
		. '<h3 class="gach__name"><a href="' . esc_url( hcwa_page_url( 'ourWork' ) . '?area=' . $key . '#f-' . $key ) . '"><span>' . esc_html( hcwa_vi_keep( $area['name']['text'] ) ) . '</span>&#8288;' . hcwa_icon( 'arrow', 'icon gach__go' ) . '</a></h3>' . "\n      "
		. ( '' !== $area['text']['text'] ? '<p class="gach__text"' . hcwa_draft_attr( $area['text']['draft'] ) . '>' . esc_html( $area['text']['text'] ) . '</p>' : '' ) . "\n    "
		. '</div>' . "\n  "
		. '</li>';
}

/**
 * Organization figures as plaques (prototype plaques()): only with at least two visible
 * figures, in every environment (3.4).
 */
function hcwa_plaques_html(): string {
	$figures = hcwa_get_impact_figures(
		array(
			'scope_type' => 'organization',
			'limit'      => 12,
		)
	);
	$items   = '';
	$count   = 0;
	foreach ( $figures as $f ) {
		$label    = hcwa_draft_text( hcwa_attr_text( (string) ( $f['label'] ?? '' ) ) );
		$verified = ! empty( $f['verified'] ) && null !== ( $f['value'] ?? null );
		if ( '' === $label['text'] || ( ! $verified && hcwa_is_production() ) ) {
			continue;
		}
		++$count;
		$value  = $verified ? hcwa_number( (float) $f['value'] ) . (string) ( $f['suffix'] ?? '' ) : hcwa_figure_token( $f );
		$items .= '<li class="plaque"' . hcwa_draft_attr( $label['draft'] ) . ">\n      "
			. '<p class="plaque__value num">' . esc_html( $value ) . '</p>' . "\n      "
			. '<p class="plaque__label">' . esc_html( $label['text'] ) . '</p>' . "\n      "
			. ( $verified ? '' : hcwa_pending_chip_html() ) . "\n    "
			. '</li>';
	}
	if ( $count < 2 ) {
		return '';
	}
	return '<div class="plaques" role="group" aria-label="' . esc_attr( hcwa_t( 'impact' ) ) . '">' . "\n    "
		. '<ul class="plaques__row">' . $items . '</ul>' . "\n    "
		. '<a class="plaques__link text-link" href="' . esc_url( hcwa_page_url( 'impact' ) ) . '"><span>' . esc_html( hcwa_t( 'seeImpact' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</a>' . "\n  "
		. '</div>';
}

/**
 * H5 the Our work sheet: the floor of focus areas, the plaques, the program rows (active
 * first, at most four).
 *
 * @param array $a focusTitle.
 */
function hcwa_render_work_sheet( array $a ): string {
	$title  = hcwa_blocks_text( $a, 'focusTitle' );
	$areas  = '';
	foreach ( hcwa_blocks_areas() as $area ) {
		$areas .= hcwa_gach_html( $area );
	}
	// The page's one tile sprite comes right after the floor, before any program row (never
	// inside a row a filter may hide: Chromium then drops its filter and clip).
	$sprite = hcwa_art()->gach_sprite_once();
	$rows   = '';
	foreach ( array_slice( hcwa_blocks_programs(), 0, 4 ) as $program ) {
		$rows .= hcwa_program_row_html( hcwa_program_view( $program ) );
	}
	$anchor = 'en' === hcwa_lang() ? '#programs' : '#du-an';
	$h2     = '' !== $title['text'] ? '<h2 class="wall__title floor__title" id="focus-title" data-reveal' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</h2>' : '';

	return "\n" . '<section class="wall wall--sun wall--edge work" id="work"' . ( '' !== $h2 ? ' aria-labelledby="focus-title"' : '' ) . ">\n  "
		. '<div class="container">' . "\n    "
		. '<div class="floor">' . "\n      "
		. $h2 . "\n      "
		. '<ul class="floor__areas" data-reveal="stagger">' . $areas . '</ul>' . "\n    "
		. '</div>' . "\n    "
		. $sprite . "\n    "
		. hcwa_plaques_html() . "\n  "
		. '</div>' . "\n  "
		. '<section class="programs" aria-labelledby="programs-title">' . "\n    "
		. '<div class="container">' . "\n      "
		. '<div class="programs__head" data-reveal>' . "\n        "
		. '<span class="lam" aria-hidden="true"></span>' . "\n        "
		. '<h2 class="wall__title" id="programs-title">' . esc_html( hcwa_vi_keep( hcwa_t( 'programs' ) ) ) . '</h2>' . "\n      "
		. '</div>' . "\n      "
		. '<ul class="program-list program-list--home" data-reveal="stagger">' . "\n        "
		. $rows . "\n        "
		. '<li class="program-more"><a class="program-more__link" href="' . esc_url( hcwa_page_url( 'ourWork' ) . $anchor ) . '"><span>' . esc_html( hcwa_t( 'allPrograms' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</a></li>' . "\n      "
		. '</ul>' . "\n    "
		. '</div>' . "\n  "
		. '</section>' . "\n"
		. '</section>';
}

/**
 * The latest posts in the current language.
 *
 * @param int $limit Number of posts.
 * @return WP_Post[]
 */
function hcwa_latest_posts( int $limit = 3, array $exclude = array() ): array {
	$args            = hcwa_blocks_query_args( 'post', $limit );
	$args['orderby'] = array(
		'date' => 'DESC',
		'ID'   => 'ASC',
	);
	if ( $exclude ) {
		$args['post__not_in'] = array_map( 'intval', $exclude );
	}
	return array_values( array_filter( (array) get_posts( $args ), static fn( $p ) => $p instanceof WP_Post ) );
}

/**
 * The featured story of H6 (3.4): off production the latest story post, always pictured
 * by the story still (never a photo) and marked as the placeholder; on production only a
 * story with no draft text whose photo passes rule 2, else ''.
 */
function hcwa_featured_story_html(): string {
	return hcwa_featured_story()['html'];
}

/**
 * The featured story of H6 and its post ID (0 when there is none), so the dated list beside it
 * leaves that post out, as the prototype lists only the news.
 *
 * @return array{html: string, id: int}
 */
function hcwa_featured_story(): array {
	$args              = hcwa_blocks_query_args( 'post', 10 );
	$args['orderby']   = 'date';
	$args['order']     = 'DESC';
	$production        = hcwa_is_production();
	foreach ( (array) get_posts( $args ) as $post ) {
		if ( ! $post instanceof WP_Post || ! hcwa_post_is_story( $post ) ) {
			continue;
		}
		$title   = hcwa_draft_text( hcwa_blocks_title( $post ) );
		$excerpt = hcwa_draft_text( hcwa_attr_text( (string) $post->post_excerpt ) );
		$draft   = $title['draft'] || $excerpt['draft'];
		if ( $production ) {
			$photo = hcwa_image( (int) get_post_thumbnail_id( $post ), 'large', array( 'class' => 'feature__still' ), $post );
			if ( $draft || '' === $photo ) {
				return array(
					'html' => '',
					'id'   => 0,
				);
			}
			$wall = $photo;
		} else {
			$wall = hcwa_art()->still(
				'story',
				array(
					'cls'   => 'feature__still',
					'sizes' => '(min-width: 1024px) 55vw, 100vw',
				)
			);
			$wall = '' !== $wall ? $wall : '<span class="lit-wall" aria-hidden="true"><span class="lit-wall__light"></span></span>';
		}
		$html = '<article class="feature"' . ( $production ? '' : ' data-story-placeholder' ) . hcwa_draft_attr( $draft ) . ">\n  "
			. '<div class="feature__wall">' . $wall . '</div>' . "\n  "
			. '<div class="feature__text">' . "\n    "
			. '<p class="feature__cat">' . esc_html( hcwa_t( 'cat_story' ) ) . '</p>' . "\n    "
			. '<h3 class="feature__title"><a href="' . esc_url( (string) get_permalink( $post ) ) . '">' . esc_html( $title['text'] ) . '</a></h3>' . "\n    "
			. ( '' !== $excerpt['text'] ? '<p class="feature__excerpt">' . esc_html( $excerpt['text'] ) . '</p>' : '' ) . "\n    "
			. '<p class="feature__note">' . esc_html( hcwa_t( 'namesChanged' ) ) . '</p>' . "\n  "
			. '</div>' . "\n"
			. '</article>';
		return array(
			'html' => $html,
			'id'   => (int) $post->ID,
		);
	}
	return array(
		'html' => '',
		'id'   => 0,
	);
}

/**
 * H6 news: the three latest posts in the language and the featured story; the section is
 * left out when the language has no published post.
 *
 * @param array $a featured (bool), limit.
 */
function hcwa_render_news_latest( array $a ): string {
	$feature = hcwa_blocks_bool( $a, 'featured', true ) ? hcwa_featured_story() : array(
		'html' => '',
		'id'   => 0,
	);
	$story   = $feature['html'];
	$posts   = hcwa_latest_posts( hcwa_blocks_int( $a, 'limit', 3, 1, 12 ), $feature['id'] ? array( $feature['id'] ) : array() );
	if ( ! $posts ) {
		return '';
	}
	$rows  = implode( '', array_map( 'hcwa_dated_post_html', $posts ) );
	return "\n" . '<section class="wall wall--shade news' . ( '' !== $story ? '' : ' news--solo' ) . '" id="news" aria-labelledby="latest-title">' . "\n  "
		. '<div class="container news__grid">' . "\n    "
		. '<div class="news__list" data-reveal>' . "\n      "
		. '<h2 class="wall__title" id="latest-title">' . esc_html( hcwa_vi_keep( hcwa_t( 'latest' ) ) ) . '</h2>' . "\n      "
		. '<ol class="dated-list">' . $rows . '</ol>' . "\n      "
		. '<a class="news__all text-link" href="' . esc_url( hcwa_page_url( 'news' ) ) . '"><span>' . esc_html( hcwa_t( 'allNews' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</a>' . "\n    "
		. '</div>' . "\n    "
		. $story . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * H7 shutters (pictures only; the skip link is the one thing to read).
 */
function hcwa_render_shutters(): string {
	$art   = hcwa_art();
	$frame = static fn( string $name, string $beat ): string => '<div class="shutters__view shutters__view--' . $beat . '">' . $art->still( $name, array( 'sizes' => '100vw' ) ) . '</div>';
	return "\n" . '<section class="shutters" id="shutters" data-world="shutters" data-stage>' . "\n  "
		. '<a class="shutters__skip" href="#help">' . esc_html( hcwa_t( 'skipShutters' ) ) . '</a>' . "\n  "
		. '<div class="shutters__track" data-stage-track>' . "\n    "
		. '<div class="shutters__frame" aria-hidden="true">' . "\n      "
		. '<template class="shutters__beats">' . $frame( 'shutters-closed', 'closed' ) . $frame( 'shutters-louvers', 'louvers' ) . '</template>' . "\n      "
		. $frame( 'shutters-open', 'open' ) . "\n      "
		. '<div class="shutters__flood"></div>' . "\n    "
		. '</div>' . "\n    "
		. '<div class="shutters__sill" aria-hidden="true"></div>' . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * The ways to help from a block's ways[] attribute, in the fixed order of the four ids.
 *
 * @param array $a Block attributes.
 * @return array[]
 */
function hcwa_blocks_ways( array $a ): array {
	$ways = array();
	foreach ( (array) ( $a['ways'] ?? array() ) as $way ) {
		if ( is_array( $way ) && in_array( $way['id'] ?? '', array( 'donate', 'volunteer', 'partner', 'in-kind' ), true ) ) {
			$ways[] = $way;
		}
	}
	return $ways;
}

/**
 * H8 how you can help.
 *
 * @param array $a title, ways[].
 */
function hcwa_render_help( array $a ): string {
	$title = hcwa_blocks_text( $a, 'title' );
	$h2    = '' !== $title['text'] ? '<h2 class="help__title" id="help-title" data-reveal data-reveal-on="entry"' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</h2>' : '';
	return "\n" . '<section class="wall wall--light help" id="help"' . ( '' !== $h2 ? ' aria-labelledby="help-title"' : '' ) . ">\n  "
		. '<span class="sun-layer sun-layer--help" aria-hidden="true"></span>' . "\n  "
		. '<div class="container">' . "\n    "
		. $h2 . "\n  "
		. '</div>' . "\n  "
		. hcwa_louvers_html( hcwa_blocks_ways( $a ) ) . "\n"
		. '</section>';
}

/**
 * The partner logos the home wall may show (rule 5): partners marked for the home page,
 * allowed by hcwa_partner_logo_allowed(), with a logo.
 *
 * @param bool $home_only Only partners marked "Show on the home page".
 * @param int  $limit     Maximum number.
 * @return array<int, array{name: string, src: string, width: int, height: int}>
 */
function hcwa_partner_logos( bool $home_only = true, int $limit = 48 ): array {
	$args = hcwa_blocks_query_args( 'hcwa_partner', $limit );
	if ( $home_only ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => 'hcwa_show_on_home',
				'value' => '1',
			),
		);
	}
	$args['orderby'] = array(
		'menu_order' => 'ASC',
		'title'      => 'ASC',
	);
	$out = array();
	foreach ( (array) get_posts( $args ) as $partner ) {
		if ( ! $partner instanceof WP_Post || ! hcwa_partner_logo_allowed( $partner->ID ) ) {
			continue;
		}
		$logo = (int) get_post_thumbnail_id( $partner );
		$src  = $logo > 0 ? wp_get_attachment_image_src( $logo, 'medium' ) : false;
		if ( ! is_array( $src ) ) {
			continue;
		}
		$out[] = array(
			'name'   => hcwa_attr_text( (string) get_the_title( $partner ) ),
			'src'    => (string) $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
		);
	}
	return $out;
}

/**
 * H9 partners: the wall with at least four allowed logos (MIN_PARTNER_LOGOS, exported);
 * below that the pending strip on staging and nothing on production.
 *
 * @param array $a title, homeOnly, limit.
 */
function hcwa_render_partner_wall( array $a ): string {
	$min   = (int) ( hcwa_proto( 'guards', array() )['min_partner_logos'] ?? 4 );
	$min   = $min > 0 ? $min : 4;
	$logos = hcwa_partner_logos( hcwa_blocks_bool( $a, 'homeOnly', true ), hcwa_blocks_int( $a, 'limit', 12, 1, 48 ) );
	$ready = count( $logos ) >= $min;
	if ( ! $ready && hcwa_is_production() ) {
		return '';
	}
	$title = hcwa_blocks_text( $a, 'title' );
	if ( $ready ) {
		$items = '';
		foreach ( $logos as $l ) {
			$items .= '<li><img src="' . esc_url( $l['src'] ) . '" alt="' . esc_attr( $l['name'] ) . '" width="' . (int) $l['width'] . '" height="' . (int) $l['height'] . '" loading="lazy" decoding="async"></li>';
		}
		$body = '<ul class="partner-wall">' . $items . '</ul>';
	} else {
		$body = '<p class="partners__pending" data-draft>' . esc_html( hcwa_t( 'partnersPending' ) ) . '</p>';
	}
	$h2 = '' !== $title['text'] ? '<h2 class="partners__title" id="partners-title"' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</h2>' : '';
	return "\n" . '<section class="wall wall--light partners" id="partners"' . ( '' !== $h2 ? ' aria-labelledby="partners-title"' : '' ) . ' data-partners="' . count( $logos ) . '"' . ( $ready ? '' : ' data-partners-pending' ) . ">\n  "
		. '<div class="container partners__inner" data-reveal>' . "\n    "
		. $h2 . "\n    "
		. $body . "\n    "
		. '<p class="partners__links">' . "\n    "
		. hcwa_text_link_html( hcwa_page_url( 'partners' ), hcwa_t( 'partners' ) ) . "\n    "
		. hcwa_text_link_html( hcwa_page_url( 'partner' ), hcwa_t( 'becomePartner' ) ) . "\n  "
		. '</p>' . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * The buttons of a dusk band: one gets the dusk__btn class, two or more sit in
 * div.dusk__actions (prototype pages).
 *
 * @param string[] $actions donate | volunteer | partner | contact.
 * @param string   $partner_label Label of the partner button (a program's own).
 */
function hcwa_dusk_actions_html( array $actions, string $partner_label = '' ): string {
	$buttons = array();
	foreach ( $actions as $action ) {
		switch ( $action ) {
			case 'donate':
				$buttons[] = array( hcwa_page_url( 'donate' ), hcwa_t( 'donate' ), 'primary' );
				break;
			case 'volunteer':
				$buttons[] = array( hcwa_page_url( 'volunteer' ), hcwa_t( 'volunteer' ), 'on-dusk' );
				break;
			case 'partner':
				$buttons[] = array( hcwa_page_url( 'partner' ), '' !== $partner_label ? $partner_label : hcwa_t( 'partnerWithUs' ), 'on-dusk' );
				break;
			case 'contact':
				$buttons[] = array( hcwa_page_url( 'contact' ), hcwa_t( 'contactUs' ), 'on-dusk' );
				break;
		}
	}
	if ( ! $buttons ) {
		return '';
	}
	if ( 1 === count( $buttons ) ) {
		return hcwa_button_html( $buttons[0][0], $buttons[0][1], $buttons[0][2], '', 'dusk__btn' );
	}
	return '<div class="dusk__actions">' . implode( '', array_map( static fn( array $b ) => hcwa_button_html( $b[0], $b[1], $b[2] ), $buttons ) ) . '</div>';
}

/**
 * hcwa/dusk-band (H10 and the inner pages' bands, 2.6). A band whose lines are all left out
 * keeps its title and button; a band with neither gets the Contact button.
 *
 * @param array $a anchor, titleId, title, lines[], actions[], note.
 */
function hcwa_render_dusk_band( array $a ): string {
	$title   = hcwa_blocks_text( $a, 'title' );
	$note    = hcwa_blocks_text( $a, 'note' );
	$lines   = array_values( array_intersect( (array) ( $a['lines'] ?? array() ), array( 'phone', 'email', 'sponsorship' ) ) );
	$actions = array_values( array_intersect( (array) ( $a['actions'] ?? array() ), array( 'donate', 'volunteer', 'partner', 'contact' ) ) );
	$dl      = $lines ? hcwa_contact_lines_html( $lines, 'dusk__lines' ) : '';
	$body    = $dl;
	if ( '' !== $note['text'] ) {
		$body .= "\n    " . '<p class="dusk__note"' . hcwa_draft_attr( $note['draft'] ) . '>' . esc_html( $note['text'] ) . '</p>';
	}
	if ( ! $actions && '' === $dl ) {
		$actions = array( 'contact' );
	}
	$buttons = hcwa_dusk_actions_html( $actions );
	$body   .= ( '' !== $body && '' !== $buttons ? "\n    " : '' ) . $buttons;
	$anchor   = sanitize_title( (string) ( $a['anchor'] ?? '' ) );
	$title_id = sanitize_title( (string) ( $a['titleId'] ?? '' ) );
	return "\n" . hcwa_dusk_band_html(
		array(
			'anchor'   => $anchor,
			'title_id' => '' !== $title_id ? $title_id : ( '' !== $anchor ? $anchor . '-title' : 'dusk-title' ),
			'title'    => $title['text'],
			'draft'    => $title['draft'],
			'body'     => $body,
		)
	);
}

/* -------------------------------------------------------------------------
 * Inner-page shells
 * ---------------------------------------------------------------------- */

/**
 * The breadcrumb trail of a page from its ancestors: [label, url] pairs, the page last.
 *
 * @param WP_Post $post  Page.
 * @param string  $title The page's own label.
 * @return array[]
 */
function hcwa_page_trail( WP_Post $post, string $title ): array {
	$trail = array();
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
		$trail[] = array( hcwa_attr_text( (string) get_the_title( $ancestor ) ), (string) get_permalink( $ancestor ) );
	}
	$trail[] = array( $title );
	return $trail;
}

/**
 * The page a block is printed on.
 *
 * @param mixed $block WP_Block or null.
 */
function hcwa_blocks_page( $block = null ): ?WP_Post {
	$post = get_post( hcwa_blocks_context_post_id( $block ) );
	return $post instanceof WP_Post ? $post : null;
}

/**
 * hcwa/lit-header on Our Work, Get Involved and Contact (2.5). Contact adds the call and
 * write buttons for the lines rule 12 prints (actions 'reach').
 *
 * @param array        $a     still, size, intro, title, actions, callUs, writeUs.
 * @param WP_Post|null $post  The page.
 */
function hcwa_render_lit_header( array $a, ?WP_Post $post ): string {
	$title = hcwa_blocks_text( $a, 'title' );
	$text  = '' !== $title['text'] ? $title['text'] : ( $post ? hcwa_attr_text( (string) get_the_title( $post ) ) : '' );
	$intro = hcwa_blocks_text( $a, 'intro' );
	$extra = '';
	if ( 'reach' === ( $a['actions'] ?? '' ) ) {
		$buttons = '';
		$draft   = false;
		foreach ( array( 'phone' => 'callUs', 'email' => 'writeUs' ) as $which => $key ) {
			$line  = hcwa_contact_line( $which );
			$label = hcwa_blocks_text( $a, $key );
			if ( '' === $line['text'] || '' === $label['text'] ) {
				continue;
			}
			$draft    = $draft || $line['draft'] || $label['draft'];
			$buttons .= "\n    " . '<a class="btn btn--secondary" href="' . esc_url( $line['href'], array( 'tel', 'mailto' ) ) . '">' . hcwa_icon( 'phone' === $which ? 'phone' : 'mail' ) . '<span>' . esc_html( $label['text'] ) . '</span></a>';
		}
		if ( '' !== $buttons ) {
			$extra = '<p class="lit-head__actions"' . hcwa_draft_attr( $draft ) . '>' . $buttons . "\n  </p>";
		}
	}
	$size = hcwa_blocks_enum( $a, 'size', array( '', 'display', 'program' ), '' );
	return "\n" . hcwa_lit_header_html(
		array(
			'still'       => sanitize_key( (string) ( $a['still'] ?? '' ) ) === (string) ( $a['still'] ?? '' ) ? (string) ( $a['still'] ?? '' ) : '',
			'trail'       => $post ? hcwa_page_trail( $post, $text ) : array( array( $text ) ),
			'title'       => $text,
			'intro'       => $intro['text'],
			'intro_draft' => $intro['draft'],
			'extra'       => $extra,
			'size'        => $size,
		)
	);
}

/**
 * Fragment ids of the Donate page in the page language: the header's jump links and the
 * tabs share them.
 *
 * @return array{vn: string, abroad: string, tell: string}
 */
function hcwa_pay_ids(): array {
	return 'en' === hcwa_lang()
		? array(
			'vn'     => 'in-vietnam',
			'abroad' => 'from-abroad',
			'tell'   => 'tell-us',
		)
		: array(
			'vn'     => 'trong-nuoc',
			'abroad' => 'tu-nuoc-ngoai',
			'tell'   => 'bao-cho-hoi',
		);
}

/**
 * What the Donate page prints below its header (3.5): the slip panels while staging or
 * once finance has confirmed the details, the tell form while forms may print.
 *
 * @return array{slip: bool, tell: bool}
 */
function hcwa_donate_parts(): array {
	$production = hcwa_is_production();
	return array(
		'slip' => ! $production || null !== hcwa_donation_details(),
		'tell' => ! $production || hcwa_forms_enabled() || array() !== array_filter( array_map( static fn( $w ) => hcwa_contact_line( $w )['text'], array( 'phone', 'sponsorship' ) ) ),
	);
}

/**
 * hcwa/sun-head on Donate (2.5 P5): the jump links only to panels that print (rule 11).
 *
 * @param array        $a    intro, go, jumpAbroad, jumpTell.
 * @param WP_Post|null $post The page.
 */
function hcwa_render_sun_head( array $a, ?WP_Post $post ): string {
	$title = $post ? hcwa_attr_text( (string) get_the_title( $post ) ) : hcwa_t( 'donate' );
	$intro = hcwa_blocks_text( $a, 'intro' );
	$ids   = hcwa_pay_ids();
	$parts = hcwa_donate_parts();
	$go    = hcwa_blocks_text( $a, 'go' );
	$jumps = array();
	if ( $parts['slip'] ) {
		$abroad = hcwa_blocks_text( $a, 'jumpAbroad' );
		if ( '' !== $abroad['text'] ) {
			$jumps[] = array( '#' . $ids['abroad'], $abroad['text'] );
		}
	}
	if ( $parts['tell'] ) {
		$tell = hcwa_blocks_text( $a, 'jumpTell' );
		if ( '' !== $tell['text'] ) {
			$jumps[] = array( '#' . $ids['tell'], $tell['text'] );
		}
	}
	return "\n" . hcwa_sun_head_html(
		array(
			'title'       => $title,
			'trail'       => $post ? hcwa_page_trail( $post, $title ) : array( array( $title ) ),
			'intro'       => $intro['text'],
			'intro_draft' => $intro['draft'],
			'go'          => $parts['slip'] && '' !== $go['text'] ? array(
				'href'  => '#' . $ids['vn'],
				'label' => $go['text'],
			) : null,
			'jumps'       => $jumps,
		)
	);
}

/**
 * hcwa/day: the day gradient around its inner blocks only.
 *
 * @param array  $a       variant.
 * @param string $content Rendered inner blocks.
 */
function hcwa_render_day( array $a, string $content ): string {
	$variant = hcwa_blocks_enum( $a, 'variant', array( 'hub', 'involve', 'donate', 'contact', 'gap' ), 'involve' );
	$cls     = 'day day--' . $variant . ( 'hub' === $variant ? ' hub' : '' );
	return "\n" . '<div class="' . esc_attr( $cls ) . '"' . ( 'hub' === $variant ? ' data-hub' : '' ) . '>' . $content . "\n</div>";
}

/* -------------------------------------------------------------------------
 * Our Work (P1)
 * ---------------------------------------------------------------------- */

/**
 * The filter-empty note: shown by CSS for the areas listed in data-show-for, by the script
 * for any combination.
 *
 * @param string $areas Space-separated area keys that leave the group empty.
 */
function hcwa_filter_empty_html( string $areas ): string {
	return '<p class="filter-empty" data-filter-empty data-show-for="' . esc_attr( $areas ) . '" hidden>' . esc_html( hcwa_t( 'noResults' ) ) . ' <a href="./" data-area="">' . esc_html( hcwa_t( 'showAll' ) ) . '</a></p>';
}

/**
 * Area keys none of the items carries.
 *
 * @param array[] $items Views with 'areas'.
 */
function hcwa_empty_for( array $items ): string {
	return implode(
		' ',
		array_values(
			array_filter(
				hcwa_area_keys(),
				static function ( string $a ) use ( $items ): bool {
					foreach ( $items as $x ) {
						if ( in_array( $a, $x['areas'], true ) ) {
							return false;
						}
					}
					return true;
				}
			)
		)
	);
}

/**
 * A center as a door in the hub's wall (prototype door()).
 *
 * @param array  $c     From hcwa_center_view().
 * @param string $still Still name.
 * @param string $sizes sizes attribute.
 */
function hcwa_door_html( array $c, string $still, string $sizes ): string {
	$lr    = $c['protected'];
	$note  = hcwa_little_rose_note();
	$since = null !== $c['since'] ? '<p class="door__since"' . hcwa_draft_attr( $c['since']['draft'] ) . '>' . esc_html( $c['since']['text'] ) . '</p>' : '';
	return '<li class="door" data-areas="' . esc_attr( implode( ' ', $c['areas'] ) ) . '" data-unit="' . esc_attr( $c['key'] ) . '" data-door="' . ( $lr ? 'closed' : 'open' ) . '" style="' . esc_attr( hcwa_unit_style( $c ) ) . '">' . "\n  "
		. '<div class="door__still" aria-hidden="true">' . hcwa_art()->still(
			$still,
			array(
				'cls'   => 'door__img',
				'sizes' => $sizes,
			)
		) . '<span class="door__spill"></span></div>' . "\n  "
		. '<div class="door__plate">' . "\n    "
		. '<h3 class="door__name"><a href="' . esc_url( $c['url'] ) . '">' . esc_html( hcwa_vi_keep( $c['name']['text'] ) ) . '</a></h3>' . "\n    "
		. $since . "\n    "
		. ( '' !== $c['serves']['text'] ? '<p class="door__serves"' . hcwa_draft_attr( $c['serves']['draft'] ) . '>' . esc_html( $c['serves']['text'] ) . '</p>' : '' ) . "\n    "
		. ( ! $lr && '' !== $c['location']['text'] ? '<p class="door__where"' . hcwa_draft_attr( $c['location']['draft'] ) . '>' . hcwa_icon( 'pin', 'icon door__pin' ) . esc_html( $c['location']['text'] ) . '</p>' : '' ) . "\n    "
		. ( $lr && '' !== $note['text'] ? '<p class="door__note"' . hcwa_draft_attr( $note['draft'] ) . '>' . esc_html( $note['text'] ) . '</p>' : '' ) . "\n    "
		. '<p class="door__go" aria-hidden="true"><span>' . esc_html( hcwa_t( $lr ? 'aboutShelter' : 'visitCenter' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</p>' . "\n    "
		. ( $lr ? '<p class="door__extra">' . hcwa_text_link_html( hcwa_page_url( 'bakery' ), hcwa_t( 'bakery' ) ) . '</p>' : '' ) . "\n  "
		. '</div>' . "\n"
		. '</li>';
}

/**
 * hcwa/work-hub: the filters, the places (door wall) and the times (program rows) of Our
 * Work (2.5 P1). On production before the Little Rose door is approved there is no panorama
 * and no door for a protected center, which appears in words under the wall; the filter
 * notes are computed from the doors and rows printed (3.4).
 *
 * @param array $a centersLead, programsLead.
 */
function hcwa_render_work_hub( array $a ): string {
	$art     = hcwa_art();
	$lang    = hcwa_lang();
	$ids     = 'en' === $lang ? array( 'centers', 'programs' ) : array( 'co-so', 'du-an' );
	$allowed = hcwa_little_rose_door_allowed();
	$areas   = hcwa_blocks_areas();

	// Filters.
	$anchors = '';
	$chips   = '';
	foreach ( $areas as $area ) {
		$anchors .= '<span class="filters__anchor" id="f-' . esc_attr( $area['key'] ) . '"></span>';
		$chips   .= '<li><a class="chip" href="?area=' . esc_attr( $area['key'] ) . '#f-' . esc_attr( $area['key'] ) . '" data-area="' . esc_attr( $area['key'] ) . '">' . $art->gach_strip( array( $area['key'] ), 'gach-strip chip__tile' ) . esc_html( $area['name']['text'] ) . '</a></li>';
	}
	$filters = '<nav class="filters container" aria-labelledby="filters-label">' . "\n  "
		. $anchors . "\n  "
		. $art->gach_sprite_once() . "\n  "
		. '<p class="filters__label" id="filters-label">' . esc_html( hcwa_t( 'filterArea' ) ) . '</p>' . "\n  "
		. '<ul class="filters__list">' . "\n    "
		. '<li><a class="chip chip--all" href="./" data-area="">' . esc_html( hcwa_t( 'all' ) ) . '</a></li>' . "\n    "
		. $chips . "\n  "
		. '</ul>' . "\n  "
		. '<p class="sr-only" data-filter-status aria-live="polite" data-centers-one="' . esc_attr( hcwa_t( 'countCenter' ) ) . '" data-centers-many="' . esc_attr( hcwa_t( 'countCenters' ) ) . '" data-programs-one="' . esc_attr( hcwa_t( 'countProgram' ) ) . '" data-programs-many="' . esc_attr( hcwa_t( 'countPrograms' ) ) . '" data-template="' . esc_attr( hcwa_t( 'filterShowing' ) ) . '"></p>' . "\n"
		. '</nav>';

	// Places.
	$centers = array();
	$words   = '';
	foreach ( hcwa_blocks_centers() as $post ) {
		$c = hcwa_center_view( $post );
		if ( $c['protected'] && ! $allowed ) {
			$note   = hcwa_little_rose_note();
			$words .= "\n      " . '<p class="places__lead"' . hcwa_draft_attr( $c['name']['draft'] || $note['draft'] ) . '>' . esc_html( trim( $c['name']['text'] . '. ' . $note['text'] ) ) . '</p>'
				. "\n      " . '<p class="door__extra">' . hcwa_text_link_html( $c['url'], hcwa_t( 'aboutShelter' ) ) . hcwa_text_link_html( hcwa_page_url( 'bakery' ), hcwa_t( 'bakery' ) ) . '</p>';
			continue;
		}
		$centers[] = $c;
	}
	$pano  = $allowed && $art->has_still( 'hub-doors-wide' );
	$doors = '';
	foreach ( $centers as $c ) {
		$own    = $pano && $art->has_still( 'hub-door-' . $c['key'] );
		$doors .= hcwa_door_html( $c, $own ? 'hub-door-' . $c['key'] : 'door-' . $c['key'], $own ? '100vw' : '(min-width: 600px) 42vw, 100vw' );
	}
	$wall  = '<div class="doorwall' . ( $pano ? ' doorwall--pano' : '' ) . '"' . ( $pano ? ' style="--ratio:' . esc_attr( (string) wp_json_encode( $art->still_aspect( 'hub-doors-wide' ) ) ) . '"' : '' ) . ">\n  "
		. ( $pano ? '<div class="doorwall__pano" aria-hidden="true">' . $art->still(
			'hub-doors-wide',
			array(
				'cls'   => 'doorwall__img',
				'sizes' => '100vw',
			)
		) . '</div>' : '' ) . "\n  "
		. '<ul class="doors" data-reveal="stagger">' . $doors . '</ul>' . "\n"
		. '</div>';
	$lead  = hcwa_blocks_text( $a, 'centersLead' );
	$places = '<section class="places" aria-labelledby="' . $ids[0] . '" data-filter-group>' . "\n    "
		. '<div class="container"><h2 class="places__title" id="' . $ids[0] . '" data-reveal="rise"><span>' . esc_html( hcwa_vi_keep( hcwa_t( 'centers' ) ) ) . '</span></h2></div>' . "\n    "
		. $wall . "\n    "
		. '<div class="container places__foot">' . "\n      "
		. ( '' !== $lead['text'] ? '<p class="places__lead"' . hcwa_draft_attr( $lead['draft'] ) . ' data-reveal>' . esc_html( $lead['text'] ) . '</p>' : '' )
		. $words . "\n      "
		. hcwa_filter_empty_html( hcwa_empty_for( $centers ) ) . "\n    "
		. '</div>' . "\n  "
		. '</section>';

	// Times.
	$views = array_map( 'hcwa_program_view', hcwa_blocks_programs() );
	$plead = hcwa_blocks_text( $a, 'programsLead' );
	$segs  = '';
	foreach ( array( 'active', 'completed' ) as $s ) {
		$segs .= '<a class="seg__opt" href="?status=' . $s . '#f--' . $s . '" data-status="' . $s . '"><span class="seg__light seg__light--' . $s . '" aria-hidden="true"></span>' . esc_html( hcwa_t( 'status_' . $s ) ) . '</a>';
	}
	$times = '<section class="times container" aria-labelledby="' . $ids[1] . '" data-filter-group>' . "\n    "
		. '<div class="times__head">' . "\n      "
		. '<span class="filters__anchor" id="f--active"></span><span class="filters__anchor" id="f--completed"></span>' . "\n      "
		. '<span class="lam lam--head" aria-hidden="true"></span>' . "\n      "
		. '<h2 id="' . $ids[1] . '" data-reveal="rise"><span>' . esc_html( hcwa_vi_keep( hcwa_t( 'programs' ) ) ) . '</span></h2>' . "\n      "
		. ( '' !== $plead['text'] ? '<p class="times__lead"' . hcwa_draft_attr( $plead['draft'] ) . ' data-reveal>' . esc_html( $plead['text'] ) . '</p>' : '' ) . "\n      "
		. '<nav class="seg" aria-label="' . esc_attr( hcwa_t( 'filterStatus' ) ) . '" data-reveal>' . "\n        "
		. '<a class="seg__opt" href="./#' . $ids[1] . '" data-status="">' . esc_html( hcwa_t( 'all' ) ) . '</a>' . "\n        "
		. $segs . "\n      "
		. '</nav>' . "\n    "
		. '</div>' . "\n    "
		. '<div class="times__rows">' . "\n      "
		. '<ul class="program-list program-list--hub" data-reveal="stagger">' . implode( '', array_map( 'hcwa_program_row_html', $views ) ) . '</ul>' . "\n      "
		. hcwa_filter_empty_html( hcwa_empty_for( $views ) ) . "\n    "
		. '</div>' . "\n  "
		. '</section>';

	return "\n  " . $filters . "\n  " . $places . "\n  " . $times;
}

/* -------------------------------------------------------------------------
 * Get Involved (P4)
 * ---------------------------------------------------------------------- */

/**
 * hcwa/louvers: the ways to help as the page's own sections (level 2).
 *
 * @param array $a ways[].
 */
function hcwa_render_louvers( array $a ): string {
	return "\n  " . hcwa_louvers_html( hcwa_blocks_ways( $a ), 2, 'louvers--page' );
}

/**
 * hcwa/faq: the questions, each with its answer (one shared answer until HCWA writes them).
 *
 * @param array $a title, faq[] ({q, a}), answer.
 */
function hcwa_render_faq( array $a ): string {
	$title  = hcwa_blocks_text( $a, 'title' );
	$shared = hcwa_blocks_text( $a, 'answer' );
	$items  = '';
	foreach ( (array) ( $a['faq'] ?? array() ) as $item ) {
		$q = hcwa_blocks_item_text( $item, 'q' );
		$x = hcwa_blocks_item_text( $item, 'a' );
		$x = '' !== $x['text'] || ( is_array( $item ) && '' !== (string) ( $item['a'] ?? '' ) ) ? $x : $shared;
		if ( '' === $q['text'] || '' === $x['text'] ) {
			continue;
		}
		$items .= '<details class="ask">' . "\n        "
			. '<summary><span class="ask__q"' . hcwa_draft_attr( $q['draft'] ) . '>' . esc_html( $q['text'] ) . '</span><span class="ask__mark" aria-hidden="true">' . hcwa_icon( 'chevron' ) . '</span></summary>' . "\n        "
			. '<div class="ask__a"><p' . hcwa_draft_attr( $x['draft'] ) . '>' . esc_html( $x['text'] ) . '</p></div>' . "\n      "
			. '</details>';
	}
	if ( '' === $items ) {
		return '';
	}
	$h2 = '' !== $title['text'] ? '<h2 class="faq__title" id="faq-title" data-reveal="rise"><span' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</span></h2>' : '';
	return "\n  " . '<section class="faq container"' . ( '' !== $h2 ? ' aria-labelledby="faq-title"' : '' ) . ">\n    "
		. $h2 . "\n    "
		. '<div class="faq__list" data-reveal="stagger">' . "\n      "
		. $items . "\n    "
		. '</div>' . "\n  "
		. '</section>';
}

/* -------------------------------------------------------------------------
 * Donate (P5)
 * ---------------------------------------------------------------------- */

/**
 * hcwa/gifts: what a gift does. The amounts are tokens until HCWA sets them: staging only.
 *
 * @param array $a title, gifts[] ({amount, text}).
 */
function hcwa_render_gifts( array $a ): string {
	if ( hcwa_is_production() ) {
		return '';
	}
	$title = hcwa_blocks_text( $a, 'title' );
	$items = '';
	foreach ( (array) ( $a['gifts'] ?? array() ) as $gift ) {
		$amount = hcwa_blocks_item_text( $gift, 'amount' );
		$text   = hcwa_blocks_item_text( $gift, 'text' );
		$items .= '<li class="gift" data-draft>' . "\n      "
			. '<span class="gift__amount" data-pending>' . esc_html( $amount['text'] ) . '</span>' . "\n      "
			. '<span class="gift__text">' . esc_html( $text['text'] ) . '</span>' . "\n    "
			. '</li>';
	}
	return "\n  " . '<section class="gifts container" aria-labelledby="gift-title">' . "\n    "
		. '<h2 class="gifts__title" id="gift-title">' . esc_html( $title['text'] ) . '</h2>' . "\n    "
		. '<ul class="gifts__list" data-reveal="stagger">' . $items . '</ul>' . "\n  "
		. '</section>';
}

/**
 * The slip rows (prototype slipRows()): every value is HCWA finance's to confirm, so until
 * then each row shows the pending value with data-donation-placeholder (staging only).
 *
 * @param array[] $rows    label, value ('' while pending), copy, hint.
 * @param string  $note_id Id of the pending note.
 */
function hcwa_slip_rows_html( array $rows, string $note_id ): string {
	$out = '';
	foreach ( $rows as $r ) {
		$label = (string) ( $r['label'] ?? '' );
		$value = (string) ( $r['value'] ?? '' );
		$hint  = (string) ( $r['hint'] ?? '' );
		$dd    = '' !== $value ? '<span class="slip__value">' . esc_html( $value ) . '</span>' : '<span class="pending-value" data-donation-placeholder>' . esc_html( hcwa_t( 'pendingFinance' ) ) . '</span>';
		$out  .= '<div class="slip__row">' . "\n    "
			. '<dt>' . esc_html( $label ) . '</dt>' . "\n    "
			. '<dd>' . $dd . ( '' !== $hint ? '<span class="slip__hint">' . esc_html( $hint ) . '</span>' : '' ) . '</dd>' . "\n    "
			. ( ! empty( $r['copy'] ) ? hcwa_copy_button_html( $label, $value, $note_id ) : '' ) . "\n  "
			. '</div>';
	}
	return '<dl class="slip__rows">' . $out . '</dl>';
}

/**
 * The bank and abroad rows: labels from the block (involve.json bankFields, abroadFields),
 * values only from finance-confirmed settings (rule 3).
 *
 * @param array      $a       Block attributes (bankFields[], abroadFields[]).
 * @param array|null $details hcwa_donation_details().
 * @return array{vn: array[], abroad: array[]}
 */
function hcwa_slip_rows( array $a, ?array $details ): array {
	$values = array(
		'vn'     => array(),
		'abroad' => array(),
	);
	if ( null !== $details ) {
		foreach ( array( 'vn' => 'in_vietnam', 'abroad' => 'abroad' ) as $k => $key ) {
			foreach ( (array) ( $details[ $key ] ?? array() ) as $row ) {
				$values[ $k ][ (string) ( $row['id'] ?? '' ) ] = (string) ( $row['value'] ?? '' );
			}
		}
	}
	$out = array();
	foreach ( array( 'vn' => 'bankFields', 'abroad' => 'abroadFields' ) as $k => $attr ) {
		$out[ $k ] = array();
		foreach ( (array) ( $a[ $attr ] ?? array() ) as $field ) {
			$label = hcwa_blocks_item_text( $field, 'label' );
			$hint  = hcwa_blocks_item_text( $field, 'hint' );
			$id    = (string) ( is_array( $field ) ? ( $field['id'] ?? '' ) : '' );
			$value = $values[ $k ][ $id ] ?? '';
			if ( null !== $details && '' === $value ) {
				continue;
			}
			$out[ $k ][] = array(
				'label' => $label['text'],
				'value' => $value,
				'copy'  => is_array( $field ) && ! empty( $field['copy'] ),
				'hint'  => $hint['text'],
			);
		}
	}
	return $out;
}

/**
 * hcwa/donation-methods: the slip and the tell section in .give-grid, then the trust links
 * (2.5 P5, 3.5). Production without confirmed details prints no slip; without forms the
 * tell section prints its heading and the contact lines rule 12 allows.
 *
 * @param array $a The Donate page texts (involve.json donatePage) and bankFields[], abroadFields[].
 */
function hcwa_render_donation_methods( array $a ): string {
	$t       = static fn( string $k ): array => hcwa_blocks_text( $a, $k );
	$ids     = hcwa_pay_ids();
	$parts   = hcwa_donate_parts();
	$details = hcwa_donation_details();
	$note_id = 'pay-note';
	$grid    = '';

	if ( $parts['slip'] ) {
		$rows    = hcwa_slip_rows( $a, $details );
		$how     = $t( 'howTitle' );
		$tabs    = '';
		$panels  = array(
			array( $ids['vn'], $t( 'inVietnamShort' ), $t( 'inVietnam' ) ),
			array( $ids['abroad'], $t( 'fromAbroadShort' ), $t( 'fromAbroad' ) ),
		);
		foreach ( $panels as $i => $p ) {
			$tabs .= '<button class="tabs__tab" type="button" role="tab" id="tab-' . esc_attr( $p[0] ) . '" aria-controls="' . esc_attr( $p[0] ) . '" aria-selected="' . ( $i ? 'false' : 'true' ) . '"' . ( $i ? ' tabindex="-1"' : '' ) . '>' . esc_html( $p[1]['text'] ) . '</button>';
		}
		$qr_name = $t( 'qr' );
		if ( null !== $details && ! empty( $details['vietqr_id'] ) ) {
			$qr = '<div class="slip__qr">' . (string) wp_get_attachment_image( (int) $details['vietqr_id'], 'medium', false, array( 'alt' => $qr_name['text'], 'loading' => 'lazy' ) ) . '</div>';
		} elseif ( null === $details ) {
			$qr = '<div class="slip__qr" role="img" aria-label="' . esc_attr( $qr_name['text'] . ': ' . hcwa_t( 'pendingFinance' ) ) . '" data-donation-placeholder>' . "\n    "
				. '<span class="slip__qr-name">' . esc_html( $qr_name['text'] ) . '</span><span class="pending-value">' . esc_html( hcwa_t( 'pendingFinance' ) ) . '</span>' . "\n  "
				. '</div>';
		} else {
			$qr = '';
		}
		$paypal_label = $t( 'paypal' );
		if ( null === $details ) {
			$paypal = '<p class="slip__alt">' . esc_html( $paypal_label['text'] ) . ' <button class="btn btn--secondary btn--small" type="button" disabled aria-describedby="' . $note_id . '" data-donation-placeholder>PayPal</button></p>';
		} else {
			$url    = (string) ( $details['paypal_url'] ?? '' );
			$paypal = '' !== $url ? '<p class="slip__alt">' . esc_html( $paypal_label['text'] ) . ' <a class="btn btn--secondary btn--small" href="' . esc_url( $url ) . '">PayPal</a></p>' : '';
		}
		$pending  = $t( 'pendingNote' );
		$to_title = $t( 'toCenterTitle' );
		$to       = $t( 'toCenter' );
		$grid    .= "\n    " . '<section class="slip" aria-labelledby="how-title" data-tabs>' . "\n      "
			. '<h2 class="slip__head" id="how-title">' . esc_html( $how['text'] ) . '</h2>' . "\n      "
			. '<div class="tabs" role="tablist" aria-label="' . esc_attr( $t( 'tabsLabel' )['text'] ) . '" hidden>' . "\n        "
			. $tabs . "\n      "
			. '</div>' . "\n      "
			. '<div class="slip__panels">' . "\n        "
			. '<section class="slip__panel" id="' . esc_attr( $ids['vn'] ) . '" aria-labelledby="' . esc_attr( $ids['vn'] ) . '-title" data-tab-panel>' . "\n          "
			. '<h3 class="slip__title" id="' . esc_attr( $ids['vn'] ) . '-title">' . esc_html( $panels[0][2]['text'] ) . '</h3>' . "\n          "
			. '<div class="slip__body">' . hcwa_slip_rows_html( $rows['vn'], $note_id ) . $qr . '</div>' . "\n        "
			. '</section>' . "\n        "
			. '<section class="slip__panel" id="' . esc_attr( $ids['abroad'] ) . '" aria-labelledby="' . esc_attr( $ids['abroad'] ) . '-title" data-tab-panel>' . "\n          "
			. '<h3 class="slip__title" id="' . esc_attr( $ids['abroad'] ) . '-title">' . esc_html( $panels[1][2]['text'] ) . '</h3>' . "\n          "
			. '<div class="slip__body">' . hcwa_slip_rows_html( $rows['abroad'], $note_id ) . '</div>' . "\n          "
			. $paypal . "\n        "
			. '</section>' . "\n      "
			. '</div>' . "\n      "
			. '<p class="slip__note" id="' . $note_id . '"' . hcwa_draft_attr( $pending['draft'] ) . '>' . hcwa_icon( 'info' ) . '<span>' . esc_html( $pending['text'] ) . '</span></p>' . "\n      "
			. '<div class="slip__center">' . "\n        "
			. '<h3 class="slip__center-title">' . esc_html( $to_title['text'] ) . '</h3>' . "\n        "
			. ( '' !== $to['text'] ? '<p' . hcwa_draft_attr( $to['draft'] ) . '>' . esc_html( $to['text'] ) . '</p>' : '' ) . "\n      "
			. '</div>' . "\n    "
			. '</section>';
	}

	if ( $parts['tell'] ) {
		$title = $t( 'tellTitle' );
		$lead  = $t( 'tellLead' );
		if ( ! hcwa_is_production() || hcwa_forms_enabled() ) {
			$centers = array();
			foreach ( hcwa_blocks_centers() as $post ) {
				$c         = hcwa_center_view( $post );
				$centers[] = array( $c['key'], $c['name']['text'] );
			}
			$thanks = $t( 'thanks' );
			$body   = '<form class="form" data-form method="post" novalidate aria-labelledby="tell-title">' . "\n        "
				. '<div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>' . "\n        "
				. hcwa_field_html( array( 'id' => 'd-name', 'label' => hcwa_t( 'f_name' ), 'required' => true, 'autocomplete' => 'name', 'error' => hcwa_t( 'e_name' ) ) ) . "\n        "
				. hcwa_field_html( array( 'id' => 'd-email', 'label' => hcwa_t( 'f_email' ), 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'error' => hcwa_t( 'e_email' ) ) ) . "\n        "
				. hcwa_field_html( array( 'id' => 'd-amount', 'label' => $t( 'amount' )['text'], 'required' => true, 'error' => $t( 'amountError' )['text'] ) ) . "\n        "
				. hcwa_field_html(
					array(
						'id'      => 'd-center',
						'label'   => $t( 'giftFor' )['text'],
						'options' => array(
							'placeholder' => $t( 'giftForAny' )['text'],
							'items'       => $centers,
						),
						'error'   => '',
					)
				) . "\n        "
				. hcwa_consent_field_html( 'd-consent' ) . "\n        "
				. '<button class="btn btn--primary form__send" type="submit">' . esc_html( hcwa_t( 'f_send' ) ) . '</button>' . "\n        "
				. hcwa_form_sent_html( $thanks['text'], $thanks['draft'] ) . "\n      "
				. '</form>';
		} else {
			$body = hcwa_contact_lines_html( array( 'phone', 'sponsorship' ), 'office__lines' );
		}
		$grid .= "\n    " . '<section class="tell" id="' . esc_attr( $ids['tell'] ) . '" aria-labelledby="tell-title">' . "\n      "
			. '<h2 class="tell__title" id="tell-title">' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</h2>' . "\n      "
			. ( '' !== $lead['text'] ? '<p class="tell__lead"' . hcwa_draft_attr( $lead['draft'] ) . '>' . esc_html( $lead['text'] ) . '</p>' : '' ) . "\n      "
			. $body . "\n    "
			. '</section>';
	}

	$out = '';
	if ( '' !== $grid ) {
		$out .= "\n  " . '<div class="give-grid container">' . $grid . "\n  </div>";
	}
	$out .= "\n  " . '<nav class="trust container" aria-labelledby="trust-title">' . "\n    "
		. '<h2 class="trust__title" id="trust-title">' . esc_html( hcwa_t( 'trust' ) ) . '</h2>' . "\n    "
		. '<ul class="trust__list">' . "\n      "
		. '<li>' . hcwa_text_link_html( hcwa_page_url( 'reports' ), hcwa_t( 'annualReports' ) ) . '</li>' . "\n      "
		. '<li>' . hcwa_text_link_html( hcwa_page_url( 'safeguarding' ), hcwa_t( 'safeguarding' ) ) . '</li>' . "\n      "
		. '<li>' . hcwa_text_link_html( hcwa_page_url( 'privacy' ), hcwa_t( 'privacy' ) ) . '</li>' . "\n    "
		. '</ul>' . "\n  "
		. '</nav>';
	return $out;
}

/* -------------------------------------------------------------------------
 * Contact (P6)
 * ---------------------------------------------------------------------- */

/**
 * The office address as it prints: draft while the Settings text has a marker.
 *
 * @return array{text: string, draft: bool}
 */
function hcwa_office_address(): array {
	return hcwa_draft_text( hcwa_attr_text( (string) hcwa_setting( 'address_' . hcwa_lang(), '' ) ) );
}

/**
 * hcwa/contact-reach: the form (staging, or once forms may print) and the office card
 * (2.5 P6, 3.5). Contact controls are named c-{id} and the form posts (2.8).
 *
 * @param array $a formTitle, officeTitle, chat, chatPending.
 */
function hcwa_render_contact_reach( array $a ): string {
	$t          = static fn( string $k ): array => hcwa_blocks_text( $a, $k );
	$production = hcwa_is_production();
	$write      = '';
	$form_title = $t( 'formTitle' );

	if ( ! $production || hcwa_forms_enabled() ) {
		$topics = array();
		foreach ( array( 'donate', 'partner', 'volunteer', 'media', 'other' ) as $k ) {
			$topics[] = array( $k, hcwa_t( 'topic_' . $k ) );
		}
		$body = '<form class="form form--wide" data-form method="post" novalidate aria-labelledby="form-title">' . "\n        "
			. '<div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>' . "\n        "
			. '<div class="form__pair">' . "\n          "
			. hcwa_field_html( array( 'id' => 'name', 'name' => 'c-name', 'label' => hcwa_t( 'f_name' ), 'required' => true, 'autocomplete' => 'name', 'error' => hcwa_t( 'e_name' ) ) ) . "\n          "
			. hcwa_field_html( array( 'id' => 'phone', 'name' => 'c-phone', 'label' => hcwa_t( 'f_phone' ), 'type' => 'tel', 'autocomplete' => 'tel', 'error' => '' ) ) . "\n        "
			. '</div>' . "\n        "
			. hcwa_field_html( array( 'id' => 'email', 'name' => 'c-email', 'label' => hcwa_t( 'f_email' ), 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'help' => hcwa_t( 'f_emailHelp' ), 'error' => hcwa_t( 'e_email' ) ) ) . "\n        "
			. hcwa_choice_field_html( array( 'id' => 'topic', 'name' => 'c-topic', 'label' => hcwa_t( 'f_topic' ), 'items' => $topics, 'required' => true, 'error' => hcwa_t( 'e_topic' ) ) ) . "\n        "
			. hcwa_field_html( array( 'id' => 'message', 'name' => 'c-message', 'label' => hcwa_t( 'f_message' ), 'required' => true, 'rows' => 6, 'error' => hcwa_t( 'e_message' ) ) ) . "\n        "
			. hcwa_consent_field_html( 'consent', 'c-consent' ) . "\n        "
			. '<button class="btn btn--primary form__send" type="submit">' . esc_html( hcwa_t( 'f_send' ) ) . '</button>' . "\n        "
			. hcwa_form_sent_html( hcwa_t( 'f_sent' ) ) . "\n      "
			. '</form>';
	} else {
		$body = hcwa_contact_lines_html( array( 'phone', 'email' ), 'office__lines' );
	}
	if ( '' !== $body ) {
		$write = "\n    " . '<section class="reach__write" aria-labelledby="form-title">' . "\n      "
			. '<h2 class="reach__title" id="form-title" data-reveal="rise"><span>' . esc_html( $form_title['text'] ) . '</span></h2>' . "\n      "
			. $body . "\n    "
			. '</section>';
	}

	// The office card: address, lines, map (draft while the address is draft), chat (staging).
	$address = hcwa_office_address();
	$lines   = hcwa_contact_lines_html( array( 'phone', 'email' ), 'office__lines' );
	$map     = (string) hcwa_setting( 'map_url', '' );
	$parts   = '';
	if ( '' !== $address['text'] ) {
		$parts .= "\n      " . '<p class="office__address"' . hcwa_draft_attr( $address['draft'] ) . '>' . hcwa_quatrefoil( 'office__sun' ) . '<span>' . esc_html( $address['text'] ) . '</span></p>';
	}
	if ( '' !== $lines ) {
		$parts .= "\n      " . $lines;
	}
	if ( '' !== $map && ( ! $production || ! $address['draft'] ) && '' !== $address['text'] ) {
		$parts .= "\n      " . '<p class="office__map"><a class="text-link" href="' . esc_url( $map ) . '"><span>' . esc_html( hcwa_t( 'openMap' ) ) . '</span>' . hcwa_icon( 'external' ) . '</a></p>';
	}
	if ( ! $production ) {
		$chat     = $t( 'chat' );
		$pending  = $t( 'chatPending' );
		$facebook = (string) hcwa_setting( 'facebook', '' );
		$parts   .= "\n      " . '<p class="office__chat"' . hcwa_draft_attr( $chat['draft'] ) . '>' . esc_html( $chat['text'] ) . ' <span class="office__app">Zalo <span class="chip-pending">' . esc_html( $pending['text'] ) . '</span></span> ' . ( '' !== $facebook ? '<a href="' . esc_url( $facebook ) . '">Facebook Messenger</a>' : '' ) . '</p>';
	}
	$office = '';
	if ( '' !== $parts ) {
		$office_title = $t( 'officeTitle' );
		$office       = "\n    " . '<section class="office" aria-labelledby="office-title">' . "\n      "
			. '<h2 class="office__title" id="office-title">' . esc_html( $office_title['text'] ) . '</h2>'
			. $parts . "\n    "
			. '</section>';
	}
	if ( '' === $write && '' === $office ) {
		return '';
	}
	return "\n  " . '<div class="reach container">' . $write . $office . "\n  </div>";
}

/**
 * hcwa/contact-units: each center's way in (2.5 P6). Little Rose has no address and no line
 * of its own; before the door is approved on production its unit has no door (3.4).
 *
 * @param array $a littleRose, centerPending.
 */
function hcwa_render_contact_units( array $a ): string {
	$allowed = hcwa_little_rose_door_allowed();
	$note    = hcwa_little_rose_note();
	$lrline  = hcwa_blocks_text( $a, 'littleRose' );
	$pending = hcwa_blocks_text( $a, 'centerPending' );
	$items   = '';
	foreach ( hcwa_blocks_centers() as $post ) {
		$c    = hcwa_center_view( $post );
		$lr   = $c['protected'];
		$door = ! $lr || $allowed;
		$name = esc_html( hcwa_vi_keep( $c['name']['text'] ) );
		$text = $lr
			? ( '' !== $lrline['text'] ? '<p class="unit__line"' . hcwa_draft_attr( $lrline['draft'] ) . '>' . esc_html( $lrline['text'] ) . '</p>' : '' ) . ( '' !== $note['text'] ? '<p class="unit__note"' . hcwa_draft_attr( $note['draft'] ) . '>' . esc_html( $note['text'] ) . '</p>' : '' )
			: ( '' !== $pending['text'] ? '<p class="unit__line"' . hcwa_draft_attr( $pending['draft'] ) . '>' . esc_html( $pending['text'] ) . '</p>' : '' );
		$items .= '<li class="unit" data-unit="' . esc_attr( $c['key'] ) . '"' . ( $door ? ' data-door="' . ( $lr ? 'closed' : 'open' ) . '" style="' . esc_attr( hcwa_unit_style( $c ) ) . '"' : '' ) . ">\n    "
			. ( $door ? '<span class="unit__door" aria-hidden="true"><span class="unit__leaf"></span></span>' : '' ) . "\n    "
			. '<div class="unit__text">' . "\n      "
			. '<h3 class="unit__name">' . ( ! $lr ? '<a href="' . esc_url( $c['url'] ) . '">' . $name . '</a>' : $name ) . '</h3>' . "\n      "
			. $text . "\n    "
			. '</div>' . "\n  "
			. '</li>';
	}
	if ( '' === $items ) {
		return '';
	}
	return "\n  " . '<section class="units container" aria-labelledby="units-title">' . "\n    "
		. '<h2 class="units__title" id="units-title" data-reveal="rise"><span>' . esc_html( hcwa_vi_keep( hcwa_t( 'centers' ) ) ) . '</span></h2>' . "\n    "
		. '<ul class="units__list" data-reveal="stagger">' . $items . '</ul>' . "\n  "
		. '</section>';
}

/* -------------------------------------------------------------------------
 * Kept blocks: impact strip, program list, report list, announcement bar
 * ---------------------------------------------------------------------- */

/**
 * hcwa/impact-strip as section.counts (2.6): rule 1 on every scope.
 *
 * @param array $a     scopeType, scopeId, limit, title.
 * @param mixed $block WP_Block or null.
 */
function hcwa_render_impact_strip( array $a, $block = null ): string {
	$scope = hcwa_blocks_enum( $a, 'scopeType', array( 'organization', 'center', 'program' ), 'organization' );
	$id    = hcwa_blocks_int( $a, 'scopeId', 0, 0, PHP_INT_MAX );
	if ( 'organization' !== $scope && 0 === $id ) {
		$id = hcwa_blocks_context_post_id( $block );
	}
	$figures = 'organization' === $scope || $id > 0 ? hcwa_get_impact_figures(
		array(
			'scope_type' => $scope,
			'scope_id'   => $id,
			'limit'      => hcwa_blocks_int( $a, 'limit', 4, 1, 12 ),
		)
	) : array();
	if ( ! hcwa_impact_strip_visible( $figures, hcwa_is_production() ) ) {
		return '';
	}
	$title = hcwa_blocks_text( $a, 'title' );
	return hcwa_counts_html( $figures, '' !== $title['text'] ? $title['text'] : hcwa_t( 'results' ), 'counts-' . $scope . ( $id ? '-' . $id : '' ) );
}

/**
 * hcwa/program-list: ul.program-list of program rows, active first.
 *
 * @param array $a     status, limit, centerId.
 * @param mixed $block WP_Block or null.
 */
function hcwa_render_program_list( array $a, $block = null ): string {
	$status = hcwa_blocks_enum( $a, 'status', array( 'any', 'active', 'completed' ), 'any' );
	$center = hcwa_blocks_int( $a, 'centerId', 0, 0, PHP_INT_MAX );
	if ( 0 === $center ) {
		$here   = hcwa_blocks_context_post_id( $block );
		$center = 'hcwa_center' === get_post_type( $here ) ? $here : 0;
	}
	$rows = '';
	$n    = 0;
	$max  = hcwa_blocks_int( $a, 'limit', 4, 1, 50 );
	foreach ( hcwa_blocks_programs() as $program ) {
		$p = hcwa_program_view( $program );
		if ( 'any' !== $status && $status !== $p['status'] ) {
			continue;
		}
		if ( $center > 0 && ! in_array( $center, array_map( static fn( $c ) => (int) $c['id'], $p['centers'] ), true ) ) {
			continue;
		}
		$rows .= hcwa_program_row_html( $p );
		if ( ++$n >= $max ) {
			break;
		}
	}
	return '' !== $rows ? '<ul class="program-list">' . $rows . '</ul>' : '';
}

/**
 * Data of one report row: title, year, type label, file size, document language.
 *
 * @param WP_Post $report Report in the current language.
 * @return array
 */
function hcwa_report_view( WP_Post $report ): array {
	$file  = (int) get_post_meta( $report->ID, 'hcwa_file_id', true );
	$size  = 0;
	if ( $file > 0 ) {
		$meta = wp_get_attachment_metadata( $file );
		$size = is_array( $meta ) && ! empty( $meta['filesize'] ) ? (int) $meta['filesize'] : 0;
		if ( 0 === $size ) {
			$path = get_attached_file( $file );
			$size = is_string( $path ) && is_readable( $path ) ? (int) filesize( $path ) : 0;
		}
	}
	$type  = (string) get_post_meta( $report->ID, 'hcwa_report_type', true );
	$types = array( 'annual', 'financial', 'program', 'policy' );
	return array(
		'title' => hcwa_draft_text( hcwa_blocks_title( $report ) ),
		'year'  => (int) get_post_meta( $report->ID, 'hcwa_year', true ),
		'type'  => in_array( $type, $types, true ) ? hcwa_t( 'blk_type_' . $type ) : '',
		'url'   => $file > 0 ? (string) wp_get_attachment_url( $file ) : (string) get_permalink( $report ),
		'lang'  => in_array( (string) get_post_meta( $report->ID, 'hcwa_doc_language', true ), array( 'vi', 'en', 'both' ), true ) ? (string) get_post_meta( $report->ID, 'hcwa_doc_language', true ) : 'vi',
		'size'  => $size,
	);
}

/**
 * One document row (prototype li.doc): file icon, title (linked when there is a file or
 * page), meta line with year, type, format, document language and size.
 *
 * @param array $r From hcwa_report_view().
 */
function hcwa_doc_row_html( array $r ): string {
	$langs = array(
		'vi'   => '<span lang="vi">Tiếng Việt</span>',
		'en'   => '<span lang="en">English</span>',
		'both' => '<span lang="vi">Tiếng Việt</span>, <span lang="en">English</span>',
	);
	$meta  = array_filter(
		array(
			$r['year'] > 0 ? esc_html( (string) $r['year'] ) : '',
			esc_html( $r['type'] ),
			'PDF',
			$langs[ $r['lang'] ] ?? '',
			$r['size'] > 0 ? esc_html( size_format( $r['size'], $r['size'] >= MB_IN_BYTES ? 1 : 0 ) ) : '',
		),
		'strlen'
	);
	return '<li class="doc"' . hcwa_draft_attr( $r['title']['draft'] ) . ">\n        "
		. hcwa_icon( 'file', 'icon doc__icon' ) . "\n        "
		. '<p class="doc__title">' . ( '' !== $r['url'] ? '<a href="' . esc_url( $r['url'] ) . '">' . esc_html( $r['title']['text'] ) . '</a>' : esc_html( $r['title']['text'] ) ) . '</p>' . "\n        "
		. '<p class="doc__meta">' . implode( ' · ', $meta ) . '</p>' . "\n      "
		. '</li>';
}

/**
 * hcwa/report-list: ul.docs__list of li.doc rows, newest year first.
 *
 * @param array $a reportType, limit, programId.
 */
function hcwa_render_report_list( array $a ): string {
	$args   = hcwa_blocks_query_args( 'hcwa_report', hcwa_blocks_int( $a, 'limit', 6, 1, 50 ) );
	$type   = hcwa_blocks_enum( $a, 'reportType', array( 'any', 'annual', 'financial', 'program', 'policy' ), 'any' );
	$meta   = array();
	if ( 'any' !== $type ) {
		$meta[] = array(
			'key'   => 'hcwa_report_type',
			'value' => $type,
		);
	}
	$program = hcwa_blocks_int( $a, 'programId', 0, 0, PHP_INT_MAX );
	if ( $program > 0 ) {
		$meta[] = array(
			'key'   => 'hcwa_program_id',
			'value' => (string) hcwa_original_id( $program ),
		);
	}
	if ( $meta ) {
		$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	$args['meta_key'] = 'hcwa_year'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$args['orderby']  = array(
		'meta_value_num' => 'DESC',
		'date'           => 'DESC',
	);
	$rows = '';
	foreach ( (array) get_posts( $args ) as $report ) {
		if ( $report instanceof WP_Post ) {
			$view = hcwa_report_view( $report );
			if ( '' !== $view['title']['text'] ) {
				$rows .= hcwa_doc_row_html( $view );
			}
		}
	}
	return '' !== $rows ? '<ul class="docs__list">' . $rows . '</ul>' : '';
}

/**
 * hcwa/announcement-bar: the one-line announcement (Settings → HCWA), dismissed by
 * ui/en-bar.js and remembered per text by wp-bars.js.
 */
function hcwa_render_announcement(): string {
	$announcement = hcwa_announcement();
	if ( null === $announcement ) {
		return '';
	}
	$text = hcwa_draft_text( hcwa_attr_text( (string) $announcement['text'] ) );
	if ( '' === $text['text'] ) {
		return '';
	}
	$url = (string) $announcement['url'];
	return '<div class="announce" data-announce="' . esc_attr( $text['text'] ) . '">' . "\n  "
		. '<div class="container announce__inner">' . "\n    "
		. '<p' . hcwa_draft_attr( $text['draft'] ) . '>' . esc_html( $text['text'] ) . ( '' !== $url ? ' <a href="' . esc_url( $url ) . '">' . esc_html( hcwa_t( 'readMore' ) ) . '</a>' : '' ) . '</p>' . "\n    "
		. '<button class="icon-btn icon-btn--sm" type="button" data-dismiss-announce aria-label="' . esc_attr( hcwa_t( 'hideAnnouncement' ) ) . '">' . hcwa_icon( 'close' ) . '</button>' . "\n  "
		. '</div>' . "\n"
		. '</div>';
}

/* -------------------------------------------------------------------------
 * Center page (P2) and program page (P3), printed by the theme's templates
 * ---------------------------------------------------------------------- */

/**
 * The related news of a center or program: posts with its news tag in this language, else
 * the latest posts (as the prototype shows the latest news), at most three.
 *
 * @param int $post_id Center or program ID.
 * @return WP_Post[]
 */
function hcwa_related_posts( int $post_id ): array {
	$slug = (string) get_post_meta( $post_id, 'hcwa_news_tag', true );
	if ( '' !== $slug ) {
		$ids = hcwa_tag_group_ids( array( $slug ) );
		if ( $ids ) {
			$args              = hcwa_blocks_query_args( 'post', 3 );
			$args['tag__in']   = $ids;
			$args['orderby']   = 'date';
			$args['order']     = 'DESC';
			$tagged            = array_values( array_filter( (array) get_posts( $args ), static fn( $p ) => $p instanceof WP_Post ) );
			if ( $tagged ) {
				return $tagged;
			}
		}
	}
	$story = hcwa_featured_story()['id'];
	return hcwa_latest_posts( 3, $story ? array( $story ) : array() );
}

/**
 * section.related: the dated list of related news; '' when there is none.
 *
 * @param int    $post_id Center or program ID.
 * @param string $id      Heading id.
 */
function hcwa_related_news_html( int $post_id, string $id ): string {
	$posts = hcwa_related_posts( $post_id );
	if ( ! $posts ) {
		return '';
	}
	return '<section class="related container" aria-labelledby="' . esc_attr( $id ) . '">' . "\n  "
		. '<h2 class="related__title" id="' . esc_attr( $id ) . '" data-reveal="rise"><span>' . esc_html( hcwa_t( 'relatedNews' ) ) . '</span></h2>' . "\n  "
		. '<ol class="dated-list related__list" data-reveal="stagger">' . implode( '', array_map( 'hcwa_dated_post_html', $posts ) ) . '</ol>' . "\n  "
		. '<p class="related__all">' . hcwa_text_link_html( hcwa_page_url( 'news' ), hcwa_t( 'allNews' ) ) . '</p>' . "\n"
		. '</section>';
}

/**
 * The trail to a center or program: Our Work, its group, its short name.
 *
 * @param string $group centers | programs.
 * @param string $label The page's own label.
 * @return array[]
 */
function hcwa_work_trail( string $group, string $label ): array {
	$anchor = array(
		'centers'  => 'en' === hcwa_lang() ? '#centers' : '#co-so',
		'programs' => 'en' === hcwa_lang() ? '#programs' : '#du-an',
	);
	return array(
		array( hcwa_t( 'ourWork' ), hcwa_page_url( 'ourWork' ) ),
		array( hcwa_t( $group ), hcwa_page_url( 'ourWork' ) . $anchor[ $group ] ),
		array( $label ),
	);
}

/**
 * The Vietnamese name shown under an English page's heading (lit-head__vi), from the
 * published Vietnamese translation only.
 *
 * @param WP_Post $post Post.
 */
function hcwa_vi_name_html( WP_Post $post ): string {
	if ( 'en' !== hcwa_lang() ) {
		return '';
	}
	$vi = hcwa_translation_id( $post->ID, 'vi' );
	if ( $vi <= 0 || $vi === $post->ID || 'publish' !== get_post_status( $vi ) ) {
		return '';
	}
	$name = hcwa_draft_text( hcwa_attr_text( (string) get_the_title( $vi ) ) );
	return '' !== $name['text'] ? '<p class="lit-head__vi" lang="vi">' . esc_html( $name['text'] ) . '</p>' : '';
}

/**
 * Whether a center's contact person and own phone may print: HCWA approved them, or (off
 * production) they are still draft placeholders, which staging shows as such.
 *
 * @param int   $center_id Center ID.
 * @param array $text      hcwa_meta_text() of the field.
 */
function hcwa_center_contact_printable( int $center_id, array $text ): bool {
	if ( '' === $text['text'] ) {
		return false;
	}
	$approved = in_array( get_post_meta( $center_id, 'hcwa_contact_approved', true ), array( true, 1, '1', 'true' ), true );
	return $approved || ( ! hcwa_is_production() && $text['draft'] );
}

/**
 * The lit header of a center page.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_header_html( WP_Post $center ): string {
	$c     = hcwa_center_view( $center );
	$logo  = '';
	$logo_id = (int) get_post_meta( $center->ID, 'hcwa_logo_id', true );
	$src   = $logo_id > 0 ? wp_get_attachment_image_src( $logo_id, 'thumbnail' ) : false;
	if ( is_array( $src ) ) {
		$alt  = hcwa_meta_text( $center->ID, 'hcwa_logo_alt' );
		$logo = '<img class="lit-head__logo" src="' . esc_url( (string) $src[0] ) . '" width="56" height="56" alt="' . esc_attr( $alt['text'] ) . '">';
	}
	$facts = '';
	if ( null !== $c['since'] ) {
		$facts .= "\n      " . '<span' . hcwa_draft_attr( $c['since']['draft'] ) . '>' . esc_html( $c['since']['text'] ) . '</span>';
	}
	if ( ! $c['protected'] && '' !== $c['location']['text'] ) {
		$facts .= "\n      " . '<span' . hcwa_draft_attr( $c['location']['draft'] ) . '>' . hcwa_icon( 'pin' ) . esc_html( $c['location']['text'] ) . '</span>';
	}
	$meta = $logo . ( '' !== $facts ? "\n    " . '<p class="lit-head__facts">' . $facts . "\n    </p>" : '' );
	return hcwa_lit_header_html(
		array(
			'still'      => (string) get_post_meta( $center->ID, 'hcwa_header_still', true ),
			'unit_color' => $c['color'],
			'trail'      => hcwa_work_trail( 'centers', $c['short']['text'] ),
			'title'      => $c['name']['text'],
			'meta'       => $meta,
			'extra'      => hcwa_vi_name_html( $center ),
		)
	);
}

/**
 * section.plate: the center at a glance, who it serves, what it does.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_plate_html( WP_Post $center ): string {
	$c       = hcwa_center_view( $center );
	$contact = hcwa_meta_text( $center->ID, 'hcwa_contact_name' );
	$facts   = '';
	if ( null !== $c['since'] ) {
		$year   = trim( substr( $c['since']['text'], strlen( hcwa_t( 'since' ) ) ) );
		$facts .= "\n      " . '<div' . hcwa_draft_attr( $c['since']['draft'] ) . '><dt>' . esc_html( hcwa_t( 'since' ) ) . '</dt><dd>' . esc_html( $year ) . '</dd></div>';
	}
	if ( ! $c['protected'] && '' !== $c['location']['text'] ) {
		$facts .= "\n      " . '<div' . hcwa_draft_attr( $c['location']['draft'] ) . '><dt>' . esc_html( hcwa_t( 'address' ) ) . '</dt><dd>' . esc_html( $c['location']['text'] ) . '</dd></div>';
	}
	if ( hcwa_center_contact_printable( $center->ID, $contact ) ) {
		$facts .= "\n      " . '<div' . hcwa_draft_attr( $contact['draft'] ) . '><dt>' . esc_html( hcwa_t( 'contact' ) ) . '</dt><dd>' . esc_html( $contact['text'] ) . '</dd></div>';
	}
	$does = '';
	foreach ( hcwa_meta_text_lines( $center->ID, 'hcwa_what_we_do' ) as $line ) {
		$does .= '<li' . hcwa_draft_attr( $line['draft'] ) . '>' . esc_html( $line['text'] ) . '</li>';
	}
	return '<section class="plate container" aria-labelledby="glance-title">' . "\n    "
		. '<h2 class="plate__title" id="glance-title">' . esc_html( hcwa_t( 'glance' ) ) . '</h2>' . "\n    "
		. ( '' !== $facts ? '<dl class="plate__facts">' . $facts . "\n    </dl>" : '' ) . "\n    "
		. ( '' !== $c['serves']['text'] ? '<div class="plate__serves">' . "\n      " . '<p class="plate__label">' . esc_html( hcwa_t( 'whoWeServe' ) ) . '</p>' . "\n      " . '<p class="plate__statement"' . hcwa_draft_attr( $c['serves']['draft'] ) . ' data-reveal>' . esc_html( $c['serves']['text'] ) . '</p>' . "\n    </div>" : '' ) . "\n    "
		. ( '' !== $does ? '<div class="plate__does">' . "\n      " . '<p class="plate__label plate__label--mark">' . hcwa_art()->block_icon( $c['emblem'], 'plate__mark' ) . esc_html( hcwa_t( 'whatWeDo' ) ) . '</p>' . "\n      " . '<ul class="does" data-reveal="stagger">' . $does . '</ul>' . "\n    </div>" : '' ) . "\n  "
		. '</section>';
}

/**
 * section.story: the story and history from the center's post content (hcwa_center_body()),
 * with the center's figures nested under it.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_story_html( WP_Post $center ): string {
	$body  = hcwa_center_body( $center );
	$title = hcwa_meta_text( $center->ID, 'hcwa_story_title' );
	$i     = 0;
	$story = '';
	foreach ( $body['story'] as $n => $p ) {
		$story .= "\n      " . '<p class="' . ( $n ? '' : 'story__first' ) . '"' . hcwa_draft_attr( $body['draft'][ $i ] ?? false ) . ' data-reveal>' . esc_html( $p ) . '</p>';
		++$i;
	}
	$history = '';
	foreach ( $body['history'] as $p ) {
		$history .= '<p' . hcwa_draft_attr( $body['draft'][ $i ] ?? false ) . '>' . esc_html( $p ) . '</p>';
		++$i;
	}
	if ( '' !== $history ) {
		$story .= "\n      " . '<details class="more story__more">' . "\n        "
			. '<summary>' . esc_html( hcwa_t( 'readFullHistory' ) ) . hcwa_icon( 'chevron' ) . '</summary>' . "\n        "
			. '<div class="more__body">' . $history . '</div>' . "\n      "
			. '</details>';
	}
	$figures_title = hcwa_meta_text( $center->ID, 'hcwa_figures_title' );
	$figures       = hcwa_get_impact_figures(
		array(
			'scope_type' => 'center',
			'scope_id'   => hcwa_original_id( $center->ID ),
			'limit'      => 12,
		)
	);
	$counts = hcwa_impact_strip_visible( $figures, hcwa_is_production() ) ? hcwa_counts_html( $figures, '' !== $figures_title['text'] ? $figures_title['text'] : hcwa_t( 'results' ), 'center-figures', true ) : '';
	if ( '' === $story && '' === $counts ) {
		return '';
	}
	$h2 = '' !== $title['text'] ? '<h2 class="story__title" id="story-title" data-reveal="rise"><span' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</span></h2>' : '';
	return '<section class="story container"' . ( '' !== $h2 ? ' aria-labelledby="story-title"' : '' ) . ">\n    "
		. '<span class="sun-layer sun-layer--story" aria-hidden="true"></span>' . "\n    "
		. $h2 . "\n    "
		. '<div class="story__text">' . $story . "\n    </div>\n    "
		. $counts . "\n  "
		. '</section>';
}

/**
 * The approved photos of an unprotected center (2.5 "P2 gallery"): section.photos with the
 * lightbox the bundle's ui/lightbox.js drives. '' when none passes the gate.
 *
 * @param WP_Post $center Center.
 * @return array{section: string, lightbox: string}
 */
function hcwa_center_photos( WP_Post $center ): array {
	$none = array(
		'section'  => '',
		'lightbox' => '',
	);
	if ( hcwa_center_is_protected( $center->ID ) ) {
		return $none;
	}
	$items = '';
	foreach ( hcwa_id_list( get_post_meta( $center->ID, 'hcwa_gallery_ids', true ) ) as $id ) {
		$img = hcwa_image(
			$id,
			'large',
			array(
				'loading' => 'lazy',
				'sizes'   => '(min-width: 1024px) 33vw, 100vw',
			),
			$center
		);
		if ( '' !== $img ) {
			$items .= '<li><button class="gallery__item" type="button" aria-label="' . esc_attr( hcwa_t( 'openLarger' ) ) . '">' . $img . '</button></li>';
		}
	}
	if ( '' === $items ) {
		return $none;
	}
	return array(
		'section'  => '<section class="photos container" aria-labelledby="center-photos">' . "\n  "
			. '<h2 class="photos__title" id="center-photos" data-reveal="rise"><span>' . esc_html( hcwa_t( 'photos' ) ) . '</span></h2>' . "\n  "
			. '<ul class="gallery" data-gallery>' . $items . '</ul>' . "\n"
			. '</section>',
		'lightbox' => '<dialog class="lightbox" data-lightbox aria-label="' . esc_attr( hcwa_t( 'photos' ) ) . '">' . "\n  "
			. '<div class="lightbox__stage" data-lightbox-stage></div>' . "\n  "
			. '<p class="lightbox__count" data-lightbox-count></p>' . "\n  "
			. '<button class="icon-btn lightbox__prev" type="button" data-lightbox-prev aria-label="' . esc_attr( hcwa_t( 'prevPhoto' ) ) . '">' . hcwa_icon( 'prev' ) . '</button>' . "\n  "
			. '<button class="icon-btn lightbox__next" type="button" data-lightbox-next aria-label="' . esc_attr( hcwa_t( 'nextPhoto' ) ) . '">' . hcwa_icon( 'next' ) . '</button>' . "\n  "
			. '<button class="icon-btn lightbox__close" type="button" data-lightbox-close aria-label="' . esc_attr( hcwa_t( 'close' ) ) . '">' . hcwa_icon( 'close' ) . '</button>' . "\n"
			. '</dialog>',
	);
}

/**
 * section.hang: where approved photos will hang, off production only and never for a
 * protected center (one lit wall from the world, never a photo).
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_hang_html( WP_Post $center ): string {
	if ( hcwa_is_production() || hcwa_center_is_protected( $center->ID ) ) {
		return '';
	}
	$wall   = hcwa_art()->still(
		'gallery-wall',
		array(
			'cls'   => 'hang__still',
			'sizes' => '(min-width: 1360px) 1320px, 100vw',
		)
	);
	$wall   = '' !== $wall ? $wall : '<span class="lit-wall"><span class="lit-wall__light"></span></span>';
	$frames = '';
	for ( $i = 1; $i <= 6; $i++ ) {
		$frames .= '<li class="hang__frame hang__frame--' . $i . '"></li>';
	}
	return '<section class="hang container" aria-labelledby="gallery-title">' . "\n  "
		. '<h2 class="hang__title" id="gallery-title" data-reveal="rise"><span>' . esc_html( hcwa_t( 'photos' ) ) . '</span></h2>' . "\n  "
		. '<figure class="hang__wall" data-draft>' . "\n    "
		. '<div class="hang__lit" aria-hidden="true">' . $wall . "\n      "
		. '<ol class="hang__frames">' . $frames . '</ol>' . "\n    "
		. '</div>' . "\n    "
		. '<figcaption class="hang__cap"><span class="hang__code">B3</span> ' . esc_html( hcwa_t( 'galleryWaiting' ) ) . '</figcaption>' . "\n  "
		. '</figure>' . "\n"
		. '</section>';
}

/**
 * A program's span (prototype spanOf()): where "today" sits, and what kind of span the
 * years make (known, done, running, open).
 *
 * @param array $p From hcwa_program_view().
 * @return array{start: string, end: string, at: string, known: bool, kind: string}
 */
function hcwa_program_span( array $p ): array {
	$parts = explode( '–', $p['years']['text'] );
	$start = trim( (string) ( $parts[0] ?? '' ) );
	$end   = trim( (string) ( $parts[1] ?? '' ) );
	$y0    = ctype_digit( $start ) ? (int) $start : 0;
	$y1    = ctype_digit( $end ) ? (int) $end : 0;
	$known = $y0 > 1900 && $y1 > $y0;
	if ( $known ) {
		$at = min( 1, max( 0, ( (int) gmdate( 'Y' ) - $y0 ) / ( $y1 - $y0 ) ) );
	} else {
		$at = 'completed' === $p['status'] ? 1 : 0.5;
	}
	$kind = $known ? 'known' : ( 'completed' === $p['status'] ? 'done' : ( '' !== $end ? 'running' : 'open' ) );
	$pct  = round( $at * 1000 ) / 10;
	return array(
		'start' => $start,
		'end'   => $end,
		'at'    => rtrim( rtrim( number_format( $pct, 1, '.', '' ), '0' ), '.' ) . '%',
		'known' => $known,
		'kind'  => $kind,
	);
}

/**
 * One program running at a center, as a lit band (prototype band()).
 *
 * @param array $p From hcwa_program_view().
 */
function hcwa_band_html( array $p ): string {
	$span  = hcwa_program_span( $p );
	$years = '' !== $p['years']['text'] ? '<span class="band__years"' . hcwa_draft_attr( $p['years']['draft'] ) . '>' . esc_html( $p['years']['text'] ) . '</span>' : '';
	return '<li class="band" data-status="' . esc_attr( $p['status'] ) . '">' . "\n  "
		. '<h3 class="band__name"><a href="' . esc_url( $p['url'] ) . '">' . esc_html( hcwa_vi_keep( $p['name']['text'] ) ) . '</a></h3>' . "\n  "
		. '<p class="band__meta">' . hcwa_status_pill_html( $p['status'] ) . $years . hcwa_icon( 'arrow', 'icon band__go' ) . '</p>' . "\n  "
		. '<span class="band__bar" data-span="' . esc_attr( $span['kind'] ) . '"' . ( 'known' === $span['kind'] ? ' style="--at:' . esc_attr( $span['at'] ) . '"' : '' ) . ' aria-hidden="true"><span class="band__lit"></span></span>' . "\n  "
		. ( '' !== $p['summary']['text'] ? '<p class="band__text"' . hcwa_draft_attr( $p['summary']['draft'] ) . '>' . esc_html( $p['summary']['text'] ) . '</p>' : '' ) . "\n"
		. '</li>';
}

/**
 * section.here: the programs that run at the center; '' when none does.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_here_html( WP_Post $center ): string {
	$bands = '';
	foreach ( hcwa_blocks_programs() as $program ) {
		$p = hcwa_program_view( $program );
		if ( in_array( $center->ID, array_map( static fn( $c ) => (int) $c['id'], $p['centers'] ), true ) ) {
			$bands .= hcwa_band_html( $p );
		}
	}
	if ( '' === $bands ) {
		return '';
	}
	return '<section class="here container" aria-labelledby="center-programs">' . "\n    "
		. '<h2 class="here__title" id="center-programs" data-reveal="rise"><span>' . esc_html( hcwa_t( 'programsHere' ) ) . '</span></h2>' . "\n    "
		. '<ul class="bands" data-reveal="stagger">' . $bands . '</ul>' . "\n  "
		. '</section>';
}

/**
 * section.give: the center's needs and the donate button (?center={unit}) with the
 * volunteer link.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_give_html( WP_Post $center ): string {
	$c     = hcwa_center_view( $center );
	$title = hcwa_meta_text( $center->ID, 'hcwa_help_title' );
	$label = hcwa_meta_text( $center->ID, 'hcwa_donate_label' );
	$needs = '';
	foreach ( hcwa_meta_text_lines( $center->ID, 'hcwa_needs' ) as $need ) {
		$needs .= '<li' . hcwa_draft_attr( $need['draft'] ) . '>' . hcwa_quatrefoil( 'give__mark' ) . '<span>' . esc_html( $need['text'] ) . '</span></li>';
	}
	$h2 = '' !== $title['text'] ? '<h2 class="give__title" id="center-help" data-reveal="rise"><span' . hcwa_draft_attr( $title['draft'] ) . '>' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</span></h2>' : '';
	return '<section class="give container"' . ( '' !== $h2 ? ' aria-labelledby="center-help"' : '' ) . ">\n    "
		. $h2 . "\n    "
		. ( '' !== $needs ? '<ul class="give__needs" data-reveal="stagger">' . $needs . '</ul>' : '' ) . "\n    "
		. '<div class="give__act">' . "\n      "
		. hcwa_button_html( add_query_arg( 'center', $c['key'], hcwa_page_url( 'donate' ) ), '' !== $label['text'] ? $label['text'] : hcwa_t( 'donate' ), 'primary', '', 'give__btn' ) . "\n      "
		. hcwa_text_link_html( hcwa_page_url( 'volunteer' ), hcwa_t( 'volunteer' ) ) . "\n    "
		. '</div>' . "\n  "
		. '</section>';
}

/**
 * The center page's evening band: the contact person and phone (only approved, or draft
 * placeholders off production), the note and the Contact button. A protected center is
 * supported, never visited.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_dusk_html( WP_Post $center ): string {
	$c       = hcwa_center_view( $center );
	$title   = hcwa_meta_text( $center->ID, 'hcwa_visit_title' );
	$title   = $c['protected'] || '' === $title['text']
		? array(
			'text'  => hcwa_t( $c['protected'] ? 'supportTitle' : 'visitTitle', array( 'name' => $c['short']['text'] ) ),
			'draft' => false,
		)
		: $title;
	$contact = hcwa_meta_text( $center->ID, 'hcwa_contact_name' );
	$rows    = '';
	$draft   = false;
	if ( hcwa_center_contact_printable( $center->ID, $contact ) ) {
		$rows .= "\n      " . '<div' . hcwa_draft_attr( $contact['draft'] ) . '><dt>' . esc_html( hcwa_t( 'contact' ) ) . '</dt><dd>' . esc_html( $contact['text'] ) . '</dd></div>';
	}
	$phone = hcwa_contact_line( 'phone' );
	if ( '' !== $phone['text'] ) {
		$rows .= "\n      " . '<div' . hcwa_draft_attr( $phone['draft'] ) . '><dt>' . esc_html( hcwa_t( 'phone' ) ) . '</dt><dd><a href="' . esc_url( $phone['href'], array( 'tel' ) ) . '">' . esc_html( $phone['text'] ) . '</a></dd></div>';
	}
	$note = hcwa_meta_text( $center->ID, 'hcwa_contact_note' );
	$body = ( '' !== $rows ? '<dl class="dusk__lines">' . $rows . "\n    </dl>\n    " : '' )
		. ( '' !== $note['text'] ? '<p class="dusk__note"' . hcwa_draft_attr( $note['draft'] ) . '>' . esc_html( $note['text'] ) . "</p>\n    " : '' )
		. hcwa_button_html( hcwa_page_url( 'contact' ), hcwa_t( 'contactUs' ), 'on-dusk', '', 'dusk__btn' );
	return hcwa_dusk_band_html(
		array(
			'title_id' => 'center-visit',
			'title'    => $title['text'],
			'draft'    => $title['draft'] || $draft,
			'body'     => $body,
		)
	);
}

/**
 * The whole center page body (2.5 P2): lit header, the day, the dusk band, and the lightbox
 * when approved photos print. The theme's template parts call the section builders one by
 * one; this is the same order for callers without the theme.
 *
 * @param WP_Post $center Center.
 */
function hcwa_center_page_html( WP_Post $center ): string {
	$c      = hcwa_center_view( $center );
	$photos = hcwa_center_photos( $center );
	return "\n" . hcwa_center_header_html( $center ) . "\n"
		. '<div class="day day--center" style="' . esc_attr( hcwa_unit_style( $c ) ) . '">' . "\n  "
		. hcwa_center_plate_html( $center ) . "\n\n  "
		. hcwa_center_story_html( $center ) . "\n\n  "
		. ( '' !== $photos['section'] ? $photos['section'] : hcwa_center_hang_html( $center ) ) . "\n\n  "
		. hcwa_center_here_html( $center ) . "\n\n  "
		. hcwa_center_give_html( $center ) . "\n\n  "
		. hcwa_related_news_html( $center->ID, 'center-news' ) . "\n"
		. '</div>' . "\n"
		. hcwa_center_dusk_html( $center )
		. $photos['lightbox'];
}

/**
 * The lit header of a program page: the full name split on ': ', status, years, funders.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_header_html( WP_Post $program ): string {
	$p     = hcwa_program_view( $program );
	$full  = '' !== $p['full']['text'] ? $p['full']['text'] : $p['name']['text'];
	$split = explode( ': ', $full );
	$main  = array_shift( $split );
	$title = $split ? array(
		'main' => $main,
		'sub'  => implode( ': ', $split ),
	) : $main;
	$meta  = hcwa_status_pill_html( $p['status'] )
		. ( '' !== $p['years']['text'] ? '<span class="lit-head__years"' . hcwa_draft_attr( $p['years']['draft'] ) . '>' . esc_html( $p['years']['text'] ) . '</span>' : '' )
		. ( $p['partners'] ? "\n    " . '<span class="lit-head__funder"><span class="lit-head__k">' . esc_html( hcwa_t( 'fundedBy' ) ) . '</span> ' . esc_html( implode( ', ', $p['partners'] ) ) . '</span>' : '' );
	return hcwa_lit_header_html(
		array(
			'still' => (string) get_post_meta( $program->ID, 'hcwa_header_still', true ),
			'trail' => hcwa_work_trail( 'programs', $p['name']['text'] ),
			'title' => $title,
			'meta'  => $meta,
			'size'  => 'program',
			'extra' => hcwa_vi_name_html( $program ),
		)
	);
}

/**
 * section.brief: the summary and the focus.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_brief_html( WP_Post $program ): string {
	$p     = hcwa_program_view( $program );
	$focus = hcwa_meta_text( $program->ID, 'hcwa_focus' );
	if ( '' === $p['summary']['text'] && '' === $focus['text'] ) {
		return '';
	}
	return '<section class="brief container" aria-labelledby="program-summary">' . "\n    "
		. '<span class="lam lam--brief" aria-hidden="true"></span>' . "\n    "
		. '<h2 class="sr-only" id="program-summary">' . esc_html( hcwa_t( 'summary' ) ) . '</h2>' . "\n    "
		. ( '' !== $p['summary']['text'] ? '<p class="brief__lead"' . hcwa_draft_attr( $p['summary']['draft'] ) . ' data-reveal>' . esc_html( $p['summary']['text'] ) . '</p>' : '' ) . "\n    "
		. ( '' !== $focus['text'] ? '<p class="brief__focus"' . hcwa_draft_attr( $focus['draft'] ) . ' data-reveal>' . esc_html( $focus['text'] ) . '</p>' : '' ) . "\n  "
		. '</section>';
}

/**
 * section.span: the program's years as one band of light ('' while the years are draft on
 * production).
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_span_html( WP_Post $program ): string {
	$p = hcwa_program_view( $program );
	if ( '' === $p['years']['text'] ) {
		return '';
	}
	$s = hcwa_program_span( $p );
	return '<section class="span container" aria-labelledby="program-span">' . "\n  "
		. '<h2 class="sr-only" id="program-span">' . esc_html( hcwa_t( 'years' ) ) . '</h2>' . "\n  "
		. '<div class="timeband timeband--' . esc_attr( $p['status'] ) . '" style="--at:' . esc_attr( $s['at'] ) . '"' . ( $s['known'] ? '' : ' data-draft' ) . ">\n    "
		. '<p class="timeband__end timeband__end--start">' . esc_html( $s['start'] ) . '</p>' . "\n    "
		. '<p class="timeband__bar"><span class="timeband__lit" aria-hidden="true"></span><span class="timeband__today">' . esc_html( hcwa_t( 'today' ) ) . '</span></p>' . "\n    "
		. '<p class="timeband__end">' . esc_html( $s['end'] ) . '</p>' . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * section.aims: goals and activities as slats.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_aims_html( WP_Post $program ): string {
	$list = static function ( array $items, string $id, bool $lit ): string {
		$out = '';
		foreach ( $items as $i => $x ) {
			$out .= '<li class="slat"' . hcwa_draft_attr( $x['draft'] ) . '><span class="slat__n num" aria-hidden="true">' . sprintf( '%02d', $i + 1 ) . '</span><span class="slat__text">' . esc_html( $x['text'] ) . '</span></li>';
		}
		return '<ol class="slats' . ( $lit ? ' slats--lit' : '' ) . '" aria-labelledby="' . $id . '" data-reveal="stagger">' . $out . '</ol>';
	};
	$goals = hcwa_meta_text_lines( $program->ID, 'hcwa_goals' );
	$acts  = hcwa_meta_text_lines( $program->ID, 'hcwa_activities' );
	if ( ! $goals && ! $acts ) {
		return '';
	}
	$cols = '';
	if ( $goals ) {
		$cols .= "\n    " . '<div class="aims__col">' . "\n      "
			. '<h2 class="aims__title" id="program-goals" data-reveal="rise"><span>' . esc_html( hcwa_t( 'goals' ) ) . '</span></h2>' . "\n      "
			. $list( $goals, 'program-goals', true ) . "\n    "
			. '</div>';
	}
	if ( $acts ) {
		$cols .= "\n    " . '<div class="aims__col aims__col--next">' . "\n      "
			. '<h2 class="aims__title" id="program-activities" data-reveal="rise"><span>' . esc_html( hcwa_t( 'activities' ) ) . '</span></h2>' . "\n      "
			. $list( $acts, 'program-activities', false ) . "\n    "
			. '</div>';
	}
	return '<section class="aims container" aria-labelledby="' . ( $goals ? 'program-goals' : 'program-activities' ) . '">' . $cols . "\n  </section>";
}

/**
 * One center a program runs at, behind a small door (prototype placeDoor()). The name links
 * only to an unprotected center; before the Little Rose door is approved on production a
 * protected center has no door at all (3.4).
 *
 * @param array $c From hcwa_center_view().
 */
function hcwa_place_html( array $c ): string {
	$lr   = $c['protected'];
	$door = ! $lr || hcwa_little_rose_door_allowed();
	$name = esc_html( $c['short']['text'] );
	if ( ! $door ) {
		return '<li class="place" data-unit="' . esc_attr( $c['key'] ) . '">' . "\n  "
			. '<span class="place__name">' . $name . '</span>' . "\n"
			. '</li>';
	}
	$masks  = hcwa_proto( 'hole_masks', array() );
	$mask   = is_array( $masks ) ? (string) ( $masks[ $c['emblem'] ] ?? '' ) : '';
	$url    = ! $lr && '' !== $mask ? hcwa_art()->asset_url( $mask ) : '';
	$style  = hcwa_unit_style( $c ) . ( '' !== $url ? ";--emblem:url('" . $url . "')" : '' );
	return '<li class="place" data-unit="' . esc_attr( $c['key'] ) . '" data-door="' . ( $lr ? 'closed' : 'open' ) . '" style="' . esc_attr( $style ) . '">' . "\n  "
		. '<span class="place__door" aria-hidden="true"><span class="place__transom"></span><span class="place__way"></span><span class="place__leaf"></span></span>' . "\n  "
		. '<span class="place__name">' . ( ! $lr ? '<a href="' . esc_url( $c['url'] ) . '">' . $name . '</a>' : $name ) . '</span>' . "\n"
		. '</li>';
}

/**
 * section.where: where the program runs; the Little Rose note when it runs there.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_where_html( WP_Post $program ): string {
	$p = hcwa_program_view( $program );
	if ( ! $p['centers'] ) {
		return '';
	}
	$note  = hcwa_meta_text( $program->ID, 'hcwa_runs_at_note' );
	$lr    = hcwa_little_rose_note();
	$notes = ( '' !== $note['text'] ? "\n      " . '<p class="where__note"' . hcwa_draft_attr( $note['draft'] ) . '>' . esc_html( $note['text'] ) . '</p>' : '' )
		. ( array_filter( $p['centers'], static fn( $c ) => $c['protected'] ) && '' !== $lr['text'] ? "\n      " . '<p class="where__note"' . hcwa_draft_attr( $lr['draft'] ) . '>' . esc_html( $lr['text'] ) . '</p>' : '' );
	return '<section class="where container" aria-labelledby="program-where">' . "\n    "
		. '<h2 class="where__title" id="program-where" data-reveal="rise"><span>' . esc_html( hcwa_t( 'whereItRuns' ) ) . '</span></h2>' . "\n    "
		. '<ul class="places-row" data-reveal="stagger">' . implode( '', array_map( 'hcwa_place_html', $p['centers'] ) ) . '</ul>' . "\n    "
		. ( '' !== $notes ? '<div class="where__notes">' . $notes . "\n    </div>" : '' ) . "\n  "
		. '</section>';
}

/**
 * section.docs: the program's reports. Published reports as li.doc rows; off production the
 * unpublished draft reports linked to it show as placeholders; on production with none,
 * the empty line with a link to the annual reports.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_docs_html( WP_Post $program ): string {
	$args               = hcwa_blocks_query_args( 'hcwa_report', 20 );
	$args['post_status'] = hcwa_is_production() ? 'publish' : array( 'publish', 'draft' );
	$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'   => 'hcwa_program_id',
			'value' => (string) hcwa_original_id( $program->ID ),
		),
	);
	$args['orderby']    = array(
		'menu_order' => 'ASC',
		'ID'         => 'ASC',
	);
	$rows = '';
	foreach ( (array) get_posts( $args ) as $report ) {
		if ( ! $report instanceof WP_Post ) {
			continue;
		}
		$view = hcwa_report_view( $report );
		if ( 'publish' !== $report->post_status ) {
			// A placeholder for HCWA's review: tokens for what is not known yet, no link.
			$view['url']   = '';
			$view['draft'] = true;
		}
		if ( '' === $view['title']['text'] ) {
			continue;
		}
		$rows .= hcwa_doc_placeholder_or_row( $view, 'publish' !== $report->post_status );
	}
	$body = '' !== $rows
		? '<ul class="docs__list">' . $rows . '</ul>'
		: '<p class="docs__empty">' . esc_html( hcwa_t( 'noDocuments' ) ) . ' ' . hcwa_text_link_html( hcwa_page_url( 'reports' ), hcwa_t( 'annualReports' ) ) . '</p>';
	return '<section class="docs container" aria-labelledby="program-docs">' . "\n    "
		. '<h2 class="docs__title" id="program-docs" data-reveal="rise"><span>' . esc_html( hcwa_t( 'documents' ) ) . '</span></h2>' . "\n    "
		. $body . "\n  "
		. '</section>';
}

/**
 * A document row, or off production a draft placeholder with the prototype's tokens for an
 * unknown year and size.
 *
 * @param array $r           From hcwa_report_view().
 * @param bool  $placeholder Whether the report is an unpublished draft.
 */
function hcwa_doc_placeholder_or_row( array $r, bool $placeholder ): string {
	if ( ! $placeholder ) {
		return hcwa_doc_row_html( $r );
	}
	$langs = array(
		'vi'   => '<span lang="vi">Tiếng Việt</span>',
		'en'   => '<span lang="en">English</span>',
		'both' => '<span lang="vi">Tiếng Việt</span>, <span lang="en">English</span>',
	);
	$meta  = implode(
		' · ',
		array(
			esc_html( $r['year'] > 0 ? (string) $r['year'] : hcwa_t( 'blk_yearToken' ) ),
			esc_html( $r['type'] ),
			'PDF',
			$langs[ $r['lang'] ] ?? '',
			$r['size'] > 0 ? esc_html( size_format( $r['size'] ) ) : '[KB]',
		)
	);
	return '<li class="doc" data-draft>' . "\n        "
		. hcwa_icon( 'file', 'icon doc__icon' ) . "\n        "
		. '<p class="doc__title">' . esc_html( $r['title']['text'] ) . '</p>' . "\n        "
		. '<p class="doc__meta">' . $meta . '</p>' . "\n      "
		. '</li>';
}

/**
 * The program page's evening band: donate and the program's partner button.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_dusk_html( WP_Post $program ): string {
	$title   = hcwa_meta_text( $program->ID, 'hcwa_cta_title' );
	$partner = hcwa_meta_text( $program->ID, 'hcwa_partner_label' );
	return hcwa_dusk_band_html(
		array(
			'title_id' => 'program-cta',
			'title'    => $title['text'],
			'draft'    => $title['draft'],
			'body'     => hcwa_dusk_actions_html( array( 'donate', 'partner' ), $partner['text'] ),
		)
	);
}

/**
 * The whole program page body (2.5 P3). The program's post content is not printed: every
 * field is meta.
 *
 * @param WP_Post $program Program.
 */
function hcwa_program_page_html( WP_Post $program ): string {
	$figures = hcwa_get_impact_figures(
		array(
			'scope_type' => 'program',
			'scope_id'   => hcwa_original_id( $program->ID ),
			'limit'      => 12,
		)
	);
	$counts  = hcwa_impact_strip_visible( $figures, hcwa_is_production() ) ? hcwa_counts_html( $figures, hcwa_t( 'results' ), 'program-results' ) : '';
	return "\n" . hcwa_program_header_html( $program ) . "\n"
		. '<div class="day day--program">' . "\n  "
		. hcwa_program_brief_html( $program ) . "\n\n  "
		. hcwa_program_span_html( $program ) . "\n\n  "
		. hcwa_program_aims_html( $program ) . "\n\n  "
		. $counts . "\n\n  "
		. hcwa_program_where_html( $program ) . "\n\n  "
		. hcwa_program_docs_html( $program ) . "\n\n  "
		. hcwa_related_news_html( $program->ID, 'program-news' ) . "\n"
		. '</div>' . "\n"
		. hcwa_program_dusk_html( $program );
}
