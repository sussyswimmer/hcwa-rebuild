/**
 * hcwa/involve-head editor: The Get Involved header (window, heading, intro, link down to the ways).
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/involve-head', {
		title: __( 'Get Involved header', 'hcwa' ),
		description: __( 'The Get Involved header: the open window onto the street, the heading, the intro and the link down to the ways.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{ key: 'intro', label: __( 'Intro', 'hcwa' ) },
			{ key: 'seeWays', label: __( 'Link to the ways', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
