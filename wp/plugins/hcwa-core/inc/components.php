<?php
/**
 * Components from 05-DESIGN, the same markup as prototype/src/partials/components.mjs so the
 * theme's CSS (synced from the prototype) styles both. Blocks and theme templates call these.
 * Every function returns escaped HTML.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- links and buttons ---------- */

function hcwa_button( $url, $label, $kind = 'primary', $attrs = '' ) {
	return '<a class="btn btn--' . esc_attr( $kind ) . '" href="' . esc_url( $url ) . '"' . $attrs . '>' . esc_html( $label ) . '</a>';
}

function hcwa_text_link( $url, $label, $icon = 'arrow', $attrs = '' ) {
	return '<a class="text-link" href="' . esc_url( $url ) . '"' . $attrs . '><span>' . esc_html( $label ) . '</span>' . hcwa_icon( $icon ) . '</a>';
}

function hcwa_status_pill( $status ) {
	$status = in_array( $status, array( 'active', 'completed', 'planned' ), true ) ? $status : 'active';
	return '<span class="status status--' . esc_attr( $status ) . '">' . esc_html( hcwa_t( 'status_' . $status ) ) . '</span>';
}

/* ---------- images ---------- */

/**
 * An image slot. Renders the attachment when it is approved (and, on strict pages, shows no
 * identifiable child). Otherwise, in review mode, a tinted block labeled with the asset code
 * from 10-ASSET-REQUEST ("B1 · Center cover · 4:3"); on production a plain tinted block.
 *
 * $args: code, label (vi/en pair), ratio ("4:3"), class, sizes, strict, illustration,
 *        eager (above the fold: no lazy loading, fetchpriority high)
 */
function hcwa_image_slot( $attachment_id, $args = array() ) {
	$args  = wp_parse_args(
		$args,
		array(
			'code'         => '',
			'label'        => array( 'vi' => 'Ảnh', 'en' => 'Image' ),
			'ratio'        => '4:3',
			'class'        => '',
			'sizes'        => '(min-width: 1200px) 600px, 100vw',
			'strict'       => false,
			'illustration' => false,
			'eager'        => false,
			'alt'          => null,
		)
	);
	$ratio = str_replace( ':', '/', $args['ratio'] );
	if ( $attachment_id && hcwa_image_allowed( $attachment_id, $args['strict'] ) ) {
		$attrs = array(
			'class'   => trim( 'media ' . $args['class'] ),
			'style'   => '--ratio:' . $ratio,
			'sizes'   => $args['sizes'],
			'loading' => $args['eager'] ? 'eager' : 'lazy',
		);
		if ( $args['eager'] ) {
			$attrs['fetchpriority'] = 'high';
		}
		if ( null !== $args['alt'] ) {
			$attrs['alt'] = $args['alt'];
		}
		return wp_get_attachment_image( $attachment_id, 'large', false, $attrs );
	}
	$classes = trim( 'ph' . ( $args['illustration'] ? ' ph--art' : '' ) . ' ' . $args['class'] );
	if ( ! hcwa_review_mode() || ! $args['code'] ) {
		return '<div class="' . esc_attr( $classes ) . ' ph--empty" style="--ratio:' . esc_attr( $ratio ) . '" aria-hidden="true"></div>';
	}
	$label = hcwa_tx( $args['label'] ) . ' · ' . $args['ratio'];
	return '<div class="' . esc_attr( $classes ) . '" role="img" aria-label="' . esc_attr( $args['code'] . ' · ' . $label ) . '" style="--ratio:' . esc_attr( $ratio ) . '"><span class="ph__code">' . esc_html( $args['code'] ) . '</span><span class="ph__label">' . esc_html( $label ) . '</span></div>';
}

/* ---------- centers and programs ---------- */

/** Short name for headings and lists ("Tre Xanh"), falling back to the full title. */
function hcwa_short_name( $post ) {
	$name = hcwa_meta( $post, 'hcwa_short_name' );
	return $name ? $name : get_the_title( $post );
}

function hcwa_accent_style( $post ) {
	$accent = hcwa_meta( $post, 'hcwa_accent' );
	return $accent ? ' style="--unit:var(--color-unit-' . esc_attr( $accent ) . ')"' : '';
}

function hcwa_center_card( $post ) {
	$post   = get_post( $post );
	$draft  = hcwa_is_draft( $post );
	$since  = hcwa_since_year( $post );
	$strict = hcwa_meta( $post, 'hcwa_illustration_only' );
	$areas  = wp_get_post_terms( $post->ID, 'hcwa_area', array( 'fields' => 'slugs' ) );
	$out    = '<article class="center-card"' . hcwa_accent_style( $post ) . ' data-areas="' . esc_attr( implode( ' ', is_wp_error( $areas ) ? array() : $areas ) ) . '">';
	$out   .= hcwa_image_slot(
		get_post_thumbnail_id( $post ),
		array(
			'code'         => $strict ? 'B2' : 'B1',
			'label'        => $strict ? array( 'vi' => 'Tranh minh họa ' . get_the_title( $post ), 'en' => 'Illustration · ' . get_the_title( $post ) ) : array( 'vi' => 'Ảnh bìa', 'en' => 'Center cover' ),
			'ratio'        => '4:3',
			'strict'       => (bool) $strict,
			'illustration' => (bool) $strict,
			'sizes'        => '(min-width: 900px) 400px, 82vw',
			'alt'          => '',
		)
	);
	$out .= '<div class="center-card__body">';
	$out .= '<h3 class="center-card__name"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
	if ( $since ) {
		$out .= '<p class="center-card__since"' . hcwa_draft_attr( $since['draft'] ) . '>' . esc_html( hcwa_t( 'since' ) . ' ' . $since['text'] ) . '</p>';
	}
	$serves = hcwa_meta( $post, 'hcwa_serves' );
	if ( $serves ) {
		$out .= '<p class="center-card__serves"' . hcwa_draft_attr( $draft ) . '>' . esc_html( $serves ) . '</p>';
	}
	$out .= '<span class="text-link" aria-hidden="true"><span>' . esc_html( hcwa_t( 'visitCenter' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</span>';
	return $out . '</div></article>';
}

/** Partner names for a program, in display order. */
function hcwa_program_partner_names( $post ) {
	$names = array();
	foreach ( hcwa_meta( $post, 'hcwa_partners' ) as $id ) {
		$partner = get_post( $id );
		if ( $partner && 'publish' === $partner->post_status ) {
			$names[] = get_the_title( $partner );
		}
	}
	return $names;
}

function hcwa_program_row( $post ) {
	$post     = get_post( $post );
	$draft    = hcwa_is_draft( $post );
	$years    = hcwa_program_years( $post );
	$name     = hcwa_short_name( $post );
	$partners = hcwa_program_partner_names( $post );
	$areas    = wp_get_post_terms( $post->ID, 'hcwa_area', array( 'fields' => 'slugs' ) );
	$out      = '<li class="program-row" data-areas="' . esc_attr( implode( ' ', is_wp_error( $areas ) ? array() : $areas ) ) . '" data-status="' . esc_attr( hcwa_meta( $post, 'hcwa_status' ) ) . '">';
	$out     .= '<div class="program-row__meta">' . hcwa_status_pill( hcwa_meta( $post, 'hcwa_status' ) );
	if ( $years ) {
		$out .= '<span class="program-row__years"' . hcwa_draft_attr( $years['draft'] ) . '>' . esc_html( $years['text'] ) . '</span>';
	}
	$out .= '</div><div class="program-row__main">';
	$out .= '<h3 class="program-row__name"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $name ) . '</a></h3>';
	if ( has_excerpt( $post ) ) {
		$out .= '<p' . hcwa_draft_attr( $draft ) . '>' . esc_html( get_the_excerpt( $post ) ) . '</p>';
	}
	if ( $partners ) {
		$out .= '<p class="program-row__partners">' . esc_html( implode( ', ', $partners ) ) . '</p>';
	}
	$out .= '</div>' . hcwa_icon( 'arrow', 'icon program-row__arrow' );
	return $out . '</li>';
}

/** Centers in the current language, ordered by menu order. */
function hcwa_get_centers( $args = array() ) {
	return get_posts(
		wp_parse_args(
			$args,
			array(
				'post_type'      => 'hcwa_center',
				'posts_per_page' => 20,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'lang'           => hcwa_lang(),
			)
		)
	);
}

/**
 * Programs in the current language. $filters: status, center (key), area (term slug),
 * count, active_first.
 */
function hcwa_get_programs( $filters = array() ) {
	$filters = wp_parse_args( $filters, array( 'status' => '', 'center' => '', 'area' => '', 'count' => 20, 'active_first' => true, 'exclude' => 0 ) );
	$args    = array(
		'post_type'      => 'hcwa_program',
		'posts_per_page' => 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'lang'           => hcwa_lang(),
		'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
		'post__not_in'   => array_filter( array( (int) $filters['exclude'] ) ),
	);
	if ( $filters['status'] ) {
		$args['meta_query'][] = array( 'key' => 'hcwa_status', 'value' => $filters['status'] );
	}
	if ( $filters['area'] ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'hcwa_area', 'field' => 'slug', 'terms' => $filters['area'] ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$programs = get_posts( $args );
	if ( $filters['center'] ) {
		$programs = array_values(
			array_filter(
				$programs,
				function ( $p ) use ( $filters ) {
					return in_array( $filters['center'], hcwa_meta( $p, 'hcwa_centers' ), true );
				}
			)
		);
	}
	if ( $filters['active_first'] ) {
		$rank = array( 'active' => 0, 'planned' => 1, 'completed' => 2 );
		usort(
			$programs,
			function ( $a, $b ) use ( $rank ) {
				$ra = isset( $rank[ hcwa_meta( $a, 'hcwa_status' ) ] ) ? $rank[ hcwa_meta( $a, 'hcwa_status' ) ] : 3;
				$rb = isset( $rank[ hcwa_meta( $b, 'hcwa_status' ) ] ) ? $rank[ hcwa_meta( $b, 'hcwa_status' ) ] : 3;
				return $ra === $rb ? $a->menu_order - $b->menu_order : $ra - $rb;
			}
		);
	}
	return array_slice( $programs, 0, max( 1, (int) $filters['count'] ) );
}

/** Post of a center or program by its unit key, in the current language. */
function hcwa_get_by_key( $post_type, $key ) {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'meta_key'       => 'hcwa_key', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'lang'           => hcwa_lang(),
		)
	);
	return $posts ? $posts[0] : null;
}

/* ---------- news and stories ---------- */

/** Stories get the "names changed" line (06-PAGES). Category slugs per 04-SITEMAP. */
function hcwa_is_story( $post ) {
	return has_category( array( 'cau-chuyen', 'stories' ), $post );
}

function hcwa_post_category( $post ) {
	$cats = get_the_category( get_post( $post )->ID );
	return $cats ? $cats[0] : null;
}

function hcwa_news_item( $post ) {
	$post = get_post( $post );
	$cat  = hcwa_post_category( $post );
	$out  = '<li class="news-item"><p class="news-item__meta">';
	if ( $cat ) {
		$out .= '<span class="label">' . esc_html( $cat->name ) . '</span>';
	}
	$out .= '<time datetime="' . esc_attr( get_the_date( 'Y-m-d', $post ) ) . '">' . esc_html( hcwa_date( $post ) ) . '</time></p>';
	$out .= '<h3 class="news-item__title"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( hcwa_clean_title( get_the_title( $post ) ) ) . '</a></h3>';
	return $out . '</li>';
}

/** Card with image (news archive, featured story). */
function hcwa_story_card( $post, $featured = false, $heading = 'h3' ) {
	$post = get_post( $post );
	$cat  = hcwa_post_category( $post );
	$out  = '<article class="story-card' . ( $featured ? ' story-card--featured' : '' ) . '">';
	$out .= hcwa_image_slot(
		get_post_thumbnail_id( $post ),
		array(
			// C2 = story photo in the visuals list; other posts keep their own image (no asset code).
			'code'  => $featured || hcwa_is_story( $post ) ? 'C2' : '',
			'label' => array( 'vi' => 'Ảnh câu chuyện', 'en' => 'Story photo' ),
			'ratio' => '3:2',
			'sizes' => $featured ? '(min-width: 900px) 700px, 100vw' : '(min-width: 900px) 380px, 100vw',
			'alt'   => '',
		)
	);
	$out .= '<p class="story-card__meta">';
	if ( $cat ) {
		$out .= '<span class="label">' . esc_html( $cat->name ) . '</span>';
	}
	$out .= '<time datetime="' . esc_attr( get_the_date( 'Y-m-d', $post ) ) . '">' . esc_html( hcwa_date( $post ) ) . '</time></p>';
	$out .= '<' . $heading . ' class="story-card__title"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( hcwa_clean_title( get_the_title( $post ) ) ) . '</a></' . $heading . '>';
	if ( $featured ) {
		$out .= '<p class="story-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $post ), 32 ) ) . '</p>';
	}
	if ( hcwa_is_story( $post ) ) {
		$out .= '<p class="story-card__note">' . esc_html( hcwa_t( 'namesChanged' ) ) . '</p>';
	}
	return $out . '</article>';
}

/* ---------- reports ---------- */

function hcwa_report_type_label( $type ) {
	$key = 'report_' . $type;
	return isset( hcwa_strings()[ $key ] ) ? hcwa_t( $key ) : '';
}

function hcwa_doc_lang_badge( $doc_lang ) {
	$names = array( 'vi' => 'Tiếng Việt', 'en' => 'English', 'both' => 'Tiếng Việt + English' );
	$name  = isset( $names[ $doc_lang ] ) ? $names[ $doc_lang ] : $names['vi'];
	$lang  = 'en' === $doc_lang ? 'en' : 'vi';
	return '<span class="badge">PDF · <span lang="' . esc_attr( $lang ) . '">' . esc_html( $name ) . '</span></span>';
}

function hcwa_report_row( $post ) {
	$post  = get_post( $post );
	$file  = hcwa_meta( $post, 'hcwa_file' );
	$url   = $file ? wp_get_attachment_url( $file ) : '';
	$path  = $file ? get_attached_file( $file ) : '';
	$size  = $path && file_exists( $path ) ? size_format( filesize( $path ), 1 ) : '';
	$draft = hcwa_is_draft( $post ) || ! $url;
	$meta  = array_filter( array( hcwa_meta( $post, 'hcwa_year' ) ? (string) hcwa_meta( $post, 'hcwa_year' ) : '', hcwa_report_type_label( hcwa_meta( $post, 'hcwa_type' ) ) ) );
	$out   = '<li class="report-row"' . hcwa_draft_attr( $draft ) . ' data-type="' . esc_attr( hcwa_meta( $post, 'hcwa_type' ) ) . '" data-year="' . esc_attr( (string) hcwa_meta( $post, 'hcwa_year' ) ) . '">';
	$out  .= hcwa_image_slot(
		get_post_thumbnail_id( $post ),
		array(
			'code'  => 'C3',
			'label' => array( 'vi' => 'Bìa báo cáo', 'en' => 'Report cover' ),
			'ratio' => '3:4',
			'class' => 'report-row__cover',
			'sizes' => '56px',
			'alt'   => '',
		)
	);
	$out .= '<div><h3 class="report-row__title">';
	$out .= $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $post ) ) . '</a>' : esc_html( get_the_title( $post ) );
	$out .= '</h3><p class="report-row__meta">' . esc_html( implode( ' · ', $meta ) ) . '</p></div>';
	$out .= hcwa_doc_lang_badge( hcwa_meta( $post, 'hcwa_doc_lang' ) );
	$out .= '<span class="report-row__size">' . esc_html( $size ? $size : ( hcwa_review_mode() ? '[KB]' : '' ) ) . '</span>';
	return $out . '</li>';
}

/**
 * Meta query + orderby for reports: newest year first, reports without a year still listed
 * (last). A plain meta_key ordering would drop them.
 */
function hcwa_report_order_args( $meta = array() ) {
	$meta['relation']    = 'AND';
	$meta['hcwa_year_q'] = array(
		'relation'     => 'OR',
		'hcwa_has_year' => array( 'key' => 'hcwa_year', 'type' => 'NUMERIC' ),
		'hcwa_no_year'  => array( 'key' => 'hcwa_year', 'compare' => 'NOT EXISTS' ),
	);
	return array(
		'meta_query' => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'    => array( 'hcwa_has_year' => 'DESC', 'title' => 'ASC' ),
	);
}

/** Reports in the current language. $filters: type, year, program, count. */
function hcwa_get_reports( $filters = array() ) {
	$filters = wp_parse_args( $filters, array( 'type' => '', 'year' => 0, 'program' => '', 'count' => 20, 'paged' => 1 ) );
	$meta    = array();
	foreach ( array( 'type' => 'hcwa_type', 'year' => 'hcwa_year', 'program' => 'hcwa_program' ) as $filter => $key ) {
		if ( $filters[ $filter ] ) {
			$meta[] = array( 'key' => $key, 'value' => $filters[ $filter ] );
		}
	}
	return new WP_Query(
		array(
			'post_type'      => 'hcwa_report',
			'posts_per_page' => max( 1, (int) $filters['count'] ),
			'paged'          => max( 1, (int) $filters['paged'] ),
			'lang'           => hcwa_lang(),
		) + hcwa_report_order_args( $meta )
	);
}

/* ---------- partners ---------- */

function hcwa_partner_logo( $post ) {
	$post   = get_post( $post );
	$name   = get_the_title( $post );
	$logo   = get_post_thumbnail_id( $post );
	$may    = hcwa_meta( $post, 'hcwa_logo_permission' ) || hcwa_review_mode();
	$url    = hcwa_meta( $post, 'hcwa_website' );
	if ( $logo && $may ) {
		// Logos are company marks, not photos of children: the approval field does not apply.
		$inner = wp_get_attachment_image( $logo, 'medium', false, array( 'class' => 'partner-logo__img', 'alt' => $name, 'loading' => 'lazy' ) );
	} elseif ( hcwa_review_mode() ) {
		$inner = '<span class="ph ph--logo" role="img" aria-label="C5 · ' . esc_attr( $name ) . '"><span class="ph__code">C5</span><span class="ph__label">' . esc_html( $name ) . '</span></span>';
	} else {
		$inner = '<span class="partner-logo__name">' . esc_html( $name ) . '</span>';
	}
	$draft = ! hcwa_meta( $post, 'hcwa_logo_permission' );
	return '<li class="partner-logo"' . hcwa_draft_attr( $draft ) . '>' . ( $url ? '<a href="' . esc_url( $url ) . '" rel="noopener">' . $inner . '</a>' : $inner ) . '</li>';
}

/* ---------- page furniture ---------- */

/** $trail: list of [ label, url ]; the last item is the current page (no link). */
function hcwa_breadcrumb( $trail ) {
	$out = '<nav class="breadcrumb" aria-label="' . esc_attr( hcwa_t( 'breadcrumb' ) ) . '"><ol>';
	$out .= '<li><a href="' . esc_url( hcwa_url( 'home' ) ) . '">' . esc_html( hcwa_t( 'home' ) ) . '</a></li>';
	$last = count( $trail ) - 1;
	foreach ( array_values( $trail ) as $i => $item ) {
		$out .= '<li>' . ( $i === $last || empty( $item[1] ) ? '<span' . ( $i === $last ? ' aria-current="page"' : '' ) . '>' . esc_html( $item[0] ) . '</span>' : '<a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>' ) . '</li>';
	}
	return $out . '</ol></nav>';
}

/**
 * Inner page header. $args: trail, title, intro, intro_draft, accent (unit key), vi_name
 * (shown under the title on English pages), extra (escaped HTML).
 */
function hcwa_page_header( $args ) {
	$args = wp_parse_args( $args, array( 'trail' => array(), 'title' => '', 'intro' => '', 'intro_draft' => false, 'accent' => '', 'vi_name' => '', 'extra' => '' ) );
	$out  = '<header class="page-header"' . ( $args['accent'] ? ' style="--unit:var(--color-unit-' . esc_attr( $args['accent'] ) . ')" data-unit' : '' ) . '><div class="container">';
	$out .= hcwa_breadcrumb( $args['trail'] );
	$out .= '<h1>' . esc_html( $args['title'] ) . '</h1>';
	if ( $args['vi_name'] && 'en' === hcwa_lang() ) {
		$out .= '<p class="page-header__vi" lang="vi">(' . esc_html( $args['vi_name'] ) . ')</p>';
	}
	if ( $args['intro'] ) {
		$out .= '<p class="page-header__intro"' . hcwa_draft_attr( $args['intro_draft'] ) . '>' . esc_html( $args['intro'] ) . '</p>';
	}
	return $out . $args['extra'] . '</div></header>';
}

/** Blue CTA band. $actions is escaped HTML (buttons). */
function hcwa_cta_band( $title, $actions, $extra = '', $id = 'cta-title' ) {
	return '<section class="cta-band" aria-labelledby="' . esc_attr( $id ) . '"><div class="container cta-band__inner"><h2 id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h2><div class="cta-band__actions">' . $actions . '</div>' . $extra . '</div></section>';
}

/**
 * Language notice (05-DESIGN): shown in the visitor's language about a post that exists only
 * in the other language, with a link to read it there and one to this language's news.
 */
function hcwa_language_notice( $post ) {
	$post  = get_post( $post );
	$lang  = hcwa_lang();
	$story = 'post' === $post->post_type;
	$out   = '<aside class="lang-notice" lang="' . esc_attr( $lang ) . '">' . hcwa_icon( 'info' ) . '<div>';
	$out  .= '<p>' . esc_html( hcwa_t( $story ? 'langNotice' : 'langNoticePage' ) ) . '</p>';
	$out  .= '<p class="lang-notice__title" lang="' . esc_attr( hcwa_other_lang( $lang ) ) . '">' . esc_html( hcwa_clean_title( get_the_title( $post ) ) ) . '</p>';
	$out  .= '<p class="lang-notice__links">' . hcwa_text_link( get_permalink( $post ), hcwa_t( 'langNoticeRead' ), 'arrow', ' hreflang="' . esc_attr( hcwa_other_lang( $lang ) ) . '"' ) . ' ' . hcwa_text_link( $story ? hcwa_url( 'news' ) : hcwa_url( 'home' ), hcwa_t( $story ? 'langNoticeHub' : 'home' ) ) . '</p>';
	return $out . '</div></aside>';
}
