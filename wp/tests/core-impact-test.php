<?php
/**
 * Impact figures: the verified gate and strip visibility (contract gating rule 1).
 *
 * @package hcwa-core
 */

$fig = static fn( $id, $value, $verified ) => array(
	'value'    => $value,
	'suffix'   => '',
	'label'    => 'figure ' . $id,
	'year'     => null,
	'verified' => $verified,
	'id'       => $id,
);

$figures = array(
	$fig( 1, 300000.0, true ),
	$fig( 2, null, false ),
	$fig( 3, 3.0, false ),
	$fig( 4, null, true ), // Ticked "verified" but no value.
	$fig( 5, 12.0, true ),
);

$live = hcwa_filter_impact_figures( $figures, true );
hcwa_assert_same( array( 1, 5 ), array_column( $live, 'id' ), 'production keeps only verified figures with a value' );

$staging = hcwa_filter_impact_figures( $figures, false );
hcwa_assert_same( array( 1, 2, 3, 4, 5 ), array_column( $staging, 'id' ), 'staging keeps every figure' );
hcwa_assert_same( false, $staging[3]['verified'], 'verified without a value is shown as pending on staging' );
hcwa_assert_same( true, $staging[0]['verified'], 'verified with a value stays verified' );

// Strip visibility.
hcwa_assert_true( hcwa_impact_strip_visible( $figures, true ), 'two verified figures: strip shows on production' );
hcwa_assert_false( hcwa_impact_strip_visible( array( $fig( 1, 5.0, true ), $fig( 2, null, false ) ), true ), 'one verified figure: strip hides on production' );
hcwa_assert_false( hcwa_impact_strip_visible( array( $fig( 2, null, false ), $fig( 3, 1.0, false ) ), true ), 'no verified figures: strip hides on production' );
hcwa_assert_false( hcwa_impact_strip_visible( array(), true ), 'empty strip hides on production' );
hcwa_assert_true( hcwa_impact_strip_visible( array( $fig( 2, null, false ) ), false ), 'staging shows pending figures' );
hcwa_assert_false( hcwa_impact_strip_visible( array(), false ), 'empty strip hides on staging' );
hcwa_assert_true( hcwa_impact_strip_visible( hcwa_filter_impact_figures( $figures, true ), true ), 'already-filtered list works too' );

// A post becomes a figure: verified needs the tick, a name and a valid date.
$post = hcwa_test_post(
	array(
		'ID'         => 501,
		'post_type'  => 'hcwa_impact',
		'post_title' => 'trẻ em được hỗ trợ',
	),
	array(
		'hcwa_value'         => '300000',
		'hcwa_suffix'        => '+',
		'hcwa_year'          => 2025,
		'hcwa_verified'      => '1',
		'hcwa_verified_by'   => 'Finance team',
		'hcwa_verified_date' => '2026-10-01',
	)
);
$one = hcwa_impact_figure_from_post( $post );
hcwa_assert_same( 300000.0, $one['value'], 'value as float' );
hcwa_assert_same( '+', $one['suffix'], 'suffix' );
hcwa_assert_same( 2025, $one['year'], 'year as int' );
hcwa_assert_true( $one['verified'], 'tick + name + date = verified' );
hcwa_assert_same( 'trẻ em được hỗ trợ', $one['label'], 'label is the title' );

$GLOBALS['hcwa_test']['post_meta'][501]['hcwa_verified_date'] = '';
hcwa_assert_false( hcwa_impact_figure_from_post( $post )['verified'], 'no date: not verified' );

$GLOBALS['hcwa_test']['post_meta'][501]['hcwa_verified_date'] = '2026-10-01';
$GLOBALS['hcwa_test']['post_meta'][501]['hcwa_verified_by']   = ' ';
hcwa_assert_false( hcwa_impact_figure_from_post( $post )['verified'], 'blank name: not verified' );

unset( $GLOBALS['hcwa_test']['post_meta'][501]['hcwa_value'] );
$GLOBALS['hcwa_test']['post_meta'][501]['hcwa_year'] = 0;
$empty = hcwa_impact_figure_from_post( $post );
hcwa_assert_same( null, $empty['value'], 'missing value is null, not 0' );
hcwa_assert_same( null, $empty['year'], 'year 0 is null' );
