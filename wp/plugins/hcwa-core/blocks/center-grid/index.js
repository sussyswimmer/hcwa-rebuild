/**
 * hcwa/center-grid editor: number of centers.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/center-grid', {
		title: __( 'Center grid', 'hcwa' ),
		description: __( 'Cards for the centers HCWA runs, with approved photos only.', 'hcwa' ),
		empty: __( 'No published centers yet.', 'hcwa' ),
		controls: function ( props ) {
			return [
				el( c.RangeControl, {
					key: 'limit',
					label: __( 'Number of centers', 'hcwa' ),
					value: props.attributes.limit,
					min: 1,
					max: 24,
					onChange: function ( value ) {
						props.setAttributes( { limit: value || 3 } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
