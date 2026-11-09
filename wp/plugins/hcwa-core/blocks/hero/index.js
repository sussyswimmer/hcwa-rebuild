/**
 * hcwa/hero editor: The first screen of the home page: heading, intro and two buttons.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/hero', {
		title: __( 'Hero', 'hcwa' ),
		description: __( 'The first screen of the home page: heading, intro and two buttons.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{ key: 'intro', label: __( 'Intro', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
