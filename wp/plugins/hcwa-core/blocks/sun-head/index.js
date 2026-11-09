/**
 * hcwa/sun-head editor: The Donate page header with its jump links.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/sun-head', {
		title: __( 'Donate header', 'hcwa' ),
		description: __( 'The Donate page header with its jump links.', 'hcwa' ),
		fields: [
			{ key: 'intro', label: __( 'Intro', 'hcwa' ) },
			{ key: 'go', label: __( 'First button', 'hcwa' ) },
			{ key: 'jumpAbroad', label: __( 'Jump to giving from abroad', 'hcwa' ) },
			{ key: 'jumpTell', label: __( 'Jump to the form', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
