/**
 * hcwa/corridor editor: One door per published center, from the center pages. Little Rose follows the safeguarding rules.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/corridor', {
		title: __( 'Centers corridor', 'hcwa' ),
		description: __( 'One door per published center, from the center pages. Little Rose follows the safeguarding rules.', 'hcwa' ),
		fields: [
			{ key: 'intro', label: __( 'Intro', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
