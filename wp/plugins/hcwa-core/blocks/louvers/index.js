/**
 * hcwa/louvers editor: The four ways to help as louver slats.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/louvers', {
		title: __( 'Ways to help', 'hcwa' ),
		description: __( 'The four ways to help as louver slats.', 'hcwa' ),
		fields: [
			{ key: 'ways', label: __( 'Ways to help', 'hcwa' ), list: [ { key: 'title', label: __( 'Title', 'hcwa' ) }, { key: 'text', label: __( 'Text', 'hcwa' ) } ] },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
