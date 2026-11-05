/**
 * hcwa/news-latest editor: featured story on or off, number of news rows.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/news-latest', {
		title: __( 'Latest news', 'hcwa' ),
		description: __( 'A featured story and the latest posts in this language.', 'hcwa' ),
		empty: __( 'No published posts in this language yet.', 'hcwa' ),
		controls: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return [
				el( c.ToggleControl, {
					key: 'featured',
					label: __( 'Show a featured story', 'hcwa' ),
					help: __( 'A sticky post first, otherwise the latest story.', 'hcwa' ),
					checked: !! a.featured,
					onChange: function ( value ) {
						set( { featured: value } );
					},
				} ),
				el( c.RangeControl, {
					key: 'limit',
					label: __( 'Number of news items', 'hcwa' ),
					value: a.limit,
					min: 1,
					max: 12,
					onChange: function ( value ) {
						set( { limit: value || 3 } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
