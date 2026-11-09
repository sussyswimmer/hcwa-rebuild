/**
 * hcwa/news-latest editor: The three latest posts in this language, and a featured story on staging.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/news-latest', {
		title: __( 'Latest news', 'hcwa' ),
		description: __( 'The three latest posts in this language, and a featured story on staging.', 'hcwa' ),
		fields: [

		],
	} );
}( window.wp, window.hcwaBlocks ) );
