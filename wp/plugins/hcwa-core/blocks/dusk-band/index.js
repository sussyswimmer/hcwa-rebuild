/**
 * hcwa/dusk-band editor: The dusk band at the end of a page: a heading, the contact lines and buttons.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/dusk-band', {
		title: __( 'Evening band', 'hcwa' ),
		description: __( 'The dusk band at the end of a page: a heading, the contact lines and buttons.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{ key: 'note', label: __( 'Note', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
