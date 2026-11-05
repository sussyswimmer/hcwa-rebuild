/**
 * hcwa/impact-strip editor: scope, center or program, number of figures, link.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/impact-strip', {
		title: __( 'Impact strip', 'hcwa' ),
		description: __( 'Verified impact figures for the organization, a center or a program. Unverified figures never show on the live site.', 'hcwa' ),
		empty: __( 'No figures to show. On the live site the strip needs at least 2 verified figures.', 'hcwa' ),
		controls: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return [
				el( c.SelectControl, {
					key: 'scope',
					label: __( 'Figures for', 'hcwa' ),
					value: a.scopeType,
					options: [
						{ value: 'organization', label: __( 'The whole organization', 'hcwa' ) },
						{ value: 'center', label: __( 'A center', 'hcwa' ) },
						{ value: 'program', label: __( 'A program', 'hcwa' ) },
					],
					onChange: function ( value ) {
						set( { scopeType: value, scopeId: 0 } );
					},
				} ),
				'organization' !== a.scopeType &&
					el( hcwa.PostSelect, {
						key: 'scope-id',
						label: 'center' === a.scopeType ? __( 'Center', 'hcwa' ) : __( 'Program', 'hcwa' ),
						postType: 'center' === a.scopeType ? 'hcwa_center' : 'hcwa_program',
						value: a.scopeId,
						zeroLabel: __( 'This page', 'hcwa' ),
						onChange: function ( value ) {
							set( { scopeId: value } );
						},
					} ),
				el( c.RangeControl, {
					key: 'limit',
					label: __( 'Number of figures', 'hcwa' ),
					value: a.limit,
					min: 1,
					max: 12,
					onChange: function ( value ) {
						set( { limit: value || 4 } );
					},
				} ),
				el( c.ToggleControl, {
					key: 'link',
					label: __( 'Show the link to the impact page', 'hcwa' ),
					checked: !! a.showLink,
					onChange: function ( value ) {
						set( { showLink: value } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
