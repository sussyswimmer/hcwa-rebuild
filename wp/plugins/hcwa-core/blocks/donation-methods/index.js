/**
 * hcwa/donation-methods editor: The transfer slip and the "tell us you gave" form. Only details HCWA finance has confirmed ever show.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/donation-methods', {
		title: __( 'Donation methods', 'hcwa' ),
		description: __( 'The transfer slip and the "tell us you gave" form. Only details HCWA finance has confirmed ever show.', 'hcwa' ),
		fields: [
			{ key: 'howTitle', label: __( 'Text', 'hcwa' ) },
			{ key: 'pendingNote', label: __( 'Text', 'hcwa' ) },
			{ key: 'tellTitle', label: __( 'Text', 'hcwa' ) },
			{ key: 'tellLead', label: __( 'Text', 'hcwa' ) },
			{ key: 'thanks', label: __( 'Text', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
