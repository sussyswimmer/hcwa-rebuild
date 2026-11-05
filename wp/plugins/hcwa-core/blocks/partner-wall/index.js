/**
 * hcwa/partner-wall editor: home page partners only, number of logos.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/partner-wall', {
		title: __( 'Partner wall', 'hcwa' ),
		description: __( 'One-tone partner logos. On the live site a logo shows only with the partner\'s written permission.', 'hcwa' ),
		empty: __( 'No partner logos to show. Check "Show on the home page" and "Permission to show the logo" on each partner.', 'hcwa' ),
		controls: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return [
				el( c.ToggleControl, {
					key: 'home',
					label: __( 'Only partners marked "Show on the home page"', 'hcwa' ),
					checked: !! a.homeOnly,
					onChange: function ( value ) {
						set( { homeOnly: value } );
					},
				} ),
				el( c.RangeControl, {
					key: 'limit',
					label: __( 'Number of logos', 'hcwa' ),
					value: a.limit,
					min: 1,
					max: 48,
					onChange: function ( value ) {
						set( { limit: value || 12 } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
