/**
 * hcwa/sections editor: The locked sections of a page built from the prototype. Change the words inside each section; the sections themselves stay in place.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerContainer( 'hcwa/sections', {
		title: __( 'Page sections', 'hcwa' ),
		description: __( 'The locked sections of a page built from the prototype. Change the words inside each section; the sections themselves stay in place.', 'hcwa' ),
	} );
}( window.wp, window.hcwaBlocks ) );
