/**
 * hcwa/gifts editor: What a gift does. Staging only until HCWA sets the amounts.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/gifts', {
		title: __( 'Gift examples', 'hcwa' ),
		description: __( 'What a gift does. Staging only until HCWA sets the amounts.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{ key: 'gifts', label: __( 'Gift examples', 'hcwa' ), list: [ { key: 'amount', label: __( 'Amount', 'hcwa' ) }, { key: 'text', label: __( 'Text', 'hcwa' ) } ] },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
