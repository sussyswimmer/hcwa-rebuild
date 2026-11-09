/**
 * hcwa/work-hub editor: The filters, the wall of center doors and the program rows.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/work-hub', {
		title: __( 'Our Work hub', 'hcwa' ),
		description: __( 'The filters, the wall of center doors and the program rows.', 'hcwa' ),
		fields: [
			{ key: 'centersLead', label: __( 'Centers lead', 'hcwa' ) },
			{ key: 'programsLead', label: __( 'Programs lead', 'hcwa' ) },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
