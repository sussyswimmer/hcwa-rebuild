/**
 * hcwa/contact-reach editor: The contact form on staging, and the office address and lines HCWA has confirmed.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/contact-reach', {
		title: __( 'Contact form and office', 'hcwa' ),
		description: __( 'The contact form on staging, and the office address and lines HCWA has confirmed.', 'hcwa' ),
		fields: [
			{ key: 'formTitle', label: __( 'Heading', 'hcwa' ) },
			{ key: 'officeTitle', label: __( 'Title', 'hcwa' ) },
			{ key: 'chat', label: __( 'Text', 'hcwa' ) },
			{ key: 'chatPending', label: __( 'Note', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
