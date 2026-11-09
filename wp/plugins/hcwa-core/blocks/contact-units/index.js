/**
 * hcwa/contact-units editor: Each center with its way in. Little Rose has no address of its own.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/contact-units', {
		title: __( 'Contact: centers', 'hcwa' ),
		description: __( 'Each center with its way in. Little Rose has no address of its own.', 'hcwa' ),
		fields: [
			{ key: 'littleRose', label: __( 'Little Rose', 'hcwa' ) },
			{ key: 'centerPending', label: __( 'Text', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
