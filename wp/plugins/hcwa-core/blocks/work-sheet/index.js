/**
 * hcwa/work-sheet editor: The four focus areas as a floor of cement tiles, the figures and the programs.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/work-sheet', {
		title: __( 'Areas of work', 'hcwa' ),
		description: __( 'The four focus areas as a floor of cement tiles, the figures and the programs.', 'hcwa' ),
		fields: [
			{ key: 'focusTitle', label: __( 'Heading', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
