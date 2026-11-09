/**
 * hcwa/day editor: The day gradient behind the sections of an inner page.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerContainer( 'hcwa/day', {
		title: __( 'Day', 'hcwa' ),
		description: __( 'The day gradient behind the sections of an inner page.', 'hcwa' ),
	} );
}( window.wp, window.hcwaBlocks ) );
