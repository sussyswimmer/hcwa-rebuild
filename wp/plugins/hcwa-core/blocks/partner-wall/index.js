/**
 * hcwa/partner-wall editor: Approved one-tone partner logos, shown once at least four are allowed.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/partner-wall', {
		title: __( 'Partner wall', 'hcwa' ),
		description: __( 'Approved one-tone partner logos, shown once at least four are allowed.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
