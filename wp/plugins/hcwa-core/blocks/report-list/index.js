/**
 * hcwa/report-list editor: report type, number of reports.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/report-list', {
		title: __( 'Report list', 'hcwa' ),
		description: __( 'Reports with a language badge and file size, newest year first.', 'hcwa' ),
		empty: __( 'No published reports match these settings.', 'hcwa' ),
		controls: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return [
				el( c.SelectControl, {
					key: 'type',
					label: __( 'Report type', 'hcwa' ),
					value: a.reportType,
					options: [
						{ value: 'any', label: __( 'All types', 'hcwa' ) },
						{ value: 'annual', label: __( 'Annual report', 'hcwa' ) },
						{ value: 'financial', label: __( 'Financial statement', 'hcwa' ) },
						{ value: 'program', label: __( 'Program report', 'hcwa' ) },
						{ value: 'policy', label: __( 'Policy', 'hcwa' ) },
					],
					onChange: function ( value ) {
						set( { reportType: value } );
					},
				} ),
				el( c.RangeControl, {
					key: 'limit',
					label: __( 'Number of reports', 'hcwa' ),
					value: a.limit,
					min: 1,
					max: 50,
					onChange: function ( value ) {
						set( { limit: value || 6 } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
