/**
 * hcwa/passage editor: The passage into the light, with the statement read word by word.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/passage', {
		title: __( 'What we do', 'hcwa' ),
		description: __( 'The passage into the light, with the statement read word by word.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{ key: 'statement', label: __( 'Statement', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
