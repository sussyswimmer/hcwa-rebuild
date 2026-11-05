/**
 * hcwa/program-list editor: status, center, number of programs.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/program-list', {
		title: __( 'Program list', 'hcwa' ),
		description: __( 'Rows of projects and programs, active ones first.', 'hcwa' ),
		empty: __( 'No programs match these settings.', 'hcwa' ),
		controls: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return [
				el( c.SelectControl, {
					key: 'status',
					label: __( 'Status', 'hcwa' ),
					value: a.status,
					options: [
						{ value: 'any', label: __( 'All', 'hcwa' ) },
						{ value: 'active', label: __( 'Active', 'hcwa' ) },
						{ value: 'completed', label: __( 'Completed', 'hcwa' ) },
					],
					onChange: function ( value ) {
						set( { status: value } );
					},
				} ),
				el( hcwa.PostSelect, {
					key: 'center',
					label: __( 'Center', 'hcwa' ),
					postType: 'hcwa_center',
					value: a.centerId,
					zeroLabel: __( 'All centers (on a center page: that center)', 'hcwa' ),
					onChange: function ( value ) {
						set( { centerId: value } );
					},
				} ),
				el( c.RangeControl, {
					key: 'limit',
					label: __( 'Number of programs', 'hcwa' ),
					value: a.limit,
					min: 1,
					max: 50,
					onChange: function ( value ) {
						set( { limit: value || 4 } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
