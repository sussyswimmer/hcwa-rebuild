/**
 * hcwa/faq editor: Common questions with one answer each.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/faq', {
		title: __( 'Questions', 'hcwa' ),
		description: __( 'Common questions with one answer each.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{ key: 'faq', label: __( 'Questions', 'hcwa' ), list: [ { key: 'q', label: __( 'Question', 'hcwa' ) }, { key: 'a', label: __( 'Answer', 'hcwa' ) } ] },
			{ key: 'answer', label: __( 'Answer', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
