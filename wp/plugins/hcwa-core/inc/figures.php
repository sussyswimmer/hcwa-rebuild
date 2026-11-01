<?php
/**
 * Impact figures (04-SITEMAP "Impact figure"). The rule that matters:
 * on production, a figure renders only when it is verified (value + who + date); a section
 * with too few verified figures hides itself. In review mode unverified figures render as a
 * token with a dashed "Pending verification" chip, like the prototype.
 */

defined( 'ABSPATH' ) || exit;

function hcwa_figure_is_verified( $post ) {
	return hcwa_meta( $post, 'hcwa_verified' )
		&& null !== hcwa_meta( $post, 'hcwa_value' )
		&& hcwa_meta( $post, 'hcwa_verified_by' )
		&& hcwa_meta( $post, 'hcwa_verified_on' );
}

/**
 * Figures that may render for a scope ('organization', a center key or a program key),
 * in display order, in the current language.
 *
 * @return WP_Post[]
 */
function hcwa_visible_figures( $scope, $lang = null ) {
	$args = array(
		'post_type'      => 'hcwa_impact',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		'meta_key'       => 'hcwa_scope', // phpcs:ignore WordPress.DB.SlowDBQuery -- small table
		'meta_value'     => $scope, // phpcs:ignore WordPress.DB.SlowDBQuery
		'no_found_rows'  => true,
	);
	if ( function_exists( 'pll_current_language' ) ) {
		$args['lang'] = $lang ? $lang : hcwa_lang();
	}
	$figures = get_posts( $args );
	if ( hcwa_review_mode() ) {
		return $figures;
	}
	return array_values( array_filter( $figures, 'hcwa_figure_is_verified' ) );
}

/** Markup for one figure. */
function hcwa_figure_html( $post ) {
	$verified = hcwa_figure_is_verified( $post );
	$year     = hcwa_meta( $post, 'hcwa_year' );
	$value    = $verified ? hcwa_number( hcwa_meta( $post, 'hcwa_value' ) ) . hcwa_meta( $post, 'hcwa_suffix' ) : '[N]';
	$label    = get_the_title( $post );
	// In review mode an unverified figure is a draft, so it gets the dashed outline too.
	$out  = '<div class="figure"' . ( $verified ? '' : hcwa_draft_attr( true ) ) . '>';
	$out .= '<p class="figure__value">' . esc_html( $value ) . '</p>';
	$out .= '<p class="figure__label">' . esc_html( $label );
	if ( $year && $verified ) {
		$out .= ' <span class="figure__year">(' . esc_html( (string) $year ) . ')</span>';
	}
	$out .= '</p>';
	if ( ! $verified ) {
		$out .= '<span class="chip-pending" data-pending>' . esc_html( hcwa_t( 'pending' ) ) . '</span>';
	}
	return $out . '</div>';
}

/**
 * The figure row for a scope, or '' when fewer than $min figures may show
 * (06-PAGES: the homepage strip needs at least 2, else it hides).
 */
function hcwa_figure_strip( $scope, $args = array() ) {
	$args    = wp_parse_args( $args, array( 'min' => 1, 'class' => '' ) );
	$figures = hcwa_visible_figures( $scope );
	if ( count( $figures ) < max( 1, (int) $args['min'] ) ) {
		return '';
	}
	$out = '<div class="figures' . ( $args['class'] ? ' ' . esc_attr( $args['class'] ) : '' ) . '">';
	foreach ( $figures as $figure ) {
		$out .= hcwa_figure_html( $figure );
	}
	return $out . '</div>';
}

/* ---------- years with a verified flag (center "since", program years) ---------- */

/** "Since" year of a center: the year when verified, a token in review mode, '' otherwise. */
function hcwa_since_year( $post ) {
	$year = hcwa_meta( $post, 'hcwa_since' );
	if ( $year && hcwa_meta( $post, 'hcwa_since_verified' ) ) {
		return array( 'text' => (string) $year, 'draft' => false );
	}
	return hcwa_review_mode() ? array( 'text' => 'en' === hcwa_lang() ? '[year]' : '[năm]', 'draft' => true ) : null;
}

/** Program years ("2024–2026", "since 2019"), same rule. */
function hcwa_program_years( $post ) {
	$start = hcwa_meta( $post, 'hcwa_start' );
	$end   = hcwa_meta( $post, 'hcwa_end' );
	if ( $start && hcwa_meta( $post, 'hcwa_years_verified' ) ) {
		$text = $end ? $start . '–' . $end : hcwa_t( 'sinceYear', array( 'year' => $start ) );
		return array( 'text' => $text, 'draft' => false );
	}
	if ( ! hcwa_review_mode() ) {
		return null;
	}
	return array( 'text' => 'en' === hcwa_lang() ? '[start]–[end]' : '[năm bắt đầu]–[năm kết thúc]', 'draft' => true );
}
