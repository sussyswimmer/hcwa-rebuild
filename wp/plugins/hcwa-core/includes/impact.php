<?php
/**
 * Impact figures with the verified gate (contract gating rule 1).
 *
 * Production never returns an unverified figure. Staging returns them all; the block
 * shows unverified ones with the dashed "Đang xác minh / Pending verification" chip.
 * A strip with fewer than 2 verified figures hides on production.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Figures for a scope, in the current language, ordered by "Display order".
 *
 * @param array $args {
 *     @type string $scope_type organization | center | program. Default organization.
 *     @type int    $scope_id   Center or program ID (any language); 0 = the current post.
 *     @type int    $limit      Maximum number of figures; 0 = all. Default 4.
 * }
 * @return array[] Each: ['value' => ?float, 'suffix' => string, 'label' => string,
 *                 'year' => ?int, 'verified' => bool, 'id' => int].
 */
function hcwa_get_impact_figures( array $args ): array {
	$scope_type = in_array( $args['scope_type'] ?? '', array( 'organization', 'center', 'program' ), true ) ? $args['scope_type'] : 'organization';
	$scope_id   = absint( $args['scope_id'] ?? 0 );
	$limit      = isset( $args['limit'] ) ? max( 0, (int) $args['limit'] ) : 4;

	$meta_query = array(
		array(
			'key'   => 'hcwa_scope_type',
			'value' => $scope_type,
		),
	);

	if ( 'organization' !== $scope_type ) {
		if ( ! $scope_id ) {
			$scope_id = (int) get_queried_object_id();
		}
		// Shared fields store the Vietnamese original's ID.
		$scope_id = hcwa_original_id( $scope_id );
		if ( ! $scope_id ) {
			return array();
		}
		$meta_query[] = array(
			'key'   => 'hcwa_scope_id',
			'value' => $scope_id,
			'type'  => 'NUMERIC',
		);
	}

	$query = array(
		'post_type'        => 'hcwa_impact',
		'post_status'      => 'publish',
		'numberposts'      => 100,
		'meta_query'       => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'suppress_filters' => false,
		'no_found_rows'    => true,
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$query['lang'] = hcwa_lang();
	}

	$posts = get_posts( $query );

	usort(
		$posts,
		static function ( $a, $b ) {
			$order = (int) get_post_meta( $a->ID, 'hcwa_order', true ) <=> (int) get_post_meta( $b->ID, 'hcwa_order', true );
			return 0 !== $order ? $order : $a->ID <=> $b->ID;
		}
	);

	$figures = hcwa_filter_impact_figures( array_map( 'hcwa_impact_figure_from_post', $posts ), hcwa_is_production() );

	return $limit ? array_slice( $figures, 0, $limit ) : $figures;
}

/**
 * One impact post as a figure. "verified" needs the tick, a name and a date.
 *
 * @param WP_Post $post Impact figure post.
 * @return array{value: ?float, suffix: string, label: string, year: ?int, verified: bool, id: int}
 */
function hcwa_impact_figure_from_post( WP_Post $post ): array {
	$value = get_post_meta( $post->ID, 'hcwa_value', true );
	$year  = (int) get_post_meta( $post->ID, 'hcwa_year', true );

	$verified = (bool) get_post_meta( $post->ID, 'hcwa_verified', true )
		&& '' !== trim( (string) get_post_meta( $post->ID, 'hcwa_verified_by', true ) )
		&& hcwa_is_ymd( (string) get_post_meta( $post->ID, 'hcwa_verified_date', true ) );

	return array(
		'value'    => is_numeric( $value ) ? (float) $value : null,
		'suffix'   => (string) get_post_meta( $post->ID, 'hcwa_suffix', true ),
		'label'    => $post->post_title,
		'year'     => $year > 0 ? $year : null,
		'verified' => $verified,
		'id'       => $post->ID,
	);
}

/**
 * The verified gate. Production keeps only verified figures that have a value.
 * Elsewhere every figure is kept, but a "verified" figure without a value is shown as
 * pending (never as an empty number).
 *
 * @param array[] $figures    Figures as returned by hcwa_impact_figure_from_post().
 * @param bool    $production Whether this is the live site.
 * @return array[]
 */
function hcwa_filter_impact_figures( array $figures, bool $production ): array {
	$out = array();

	foreach ( $figures as $figure ) {
		if ( ! is_array( $figure ) ) {
			continue;
		}
		$has_value          = isset( $figure['value'] ) && is_numeric( $figure['value'] );
		$figure['verified'] = ! empty( $figure['verified'] ) && $has_value;

		if ( $production && ! $figure['verified'] ) {
			continue;
		}
		$out[] = $figure;
	}

	return $out;
}

/**
 * Whether an impact strip should render. Production needs at least 2 verified figures;
 * staging shows any non-empty strip (with pending chips).
 *
 * @param array[] $figures    Figures (filtered or not).
 * @param bool    $production Whether this is the live site.
 */
function hcwa_impact_strip_visible( array $figures, bool $production ): bool {
	if ( ! $production ) {
		return count( $figures ) > 0;
	}

	return count( hcwa_filter_impact_figures( $figures, true ) ) >= 2;
}
