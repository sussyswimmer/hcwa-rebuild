/**
 * hcwa/lit-header editor: Page header with a picture from the world, the breadcrumb, the heading and an intro.
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;
	var reach = function ( a ) {
		return 'reach' === a.actions;
	};

	hcwa.registerText( 'hcwa/lit-header', {
		title: __( 'Lit header', 'hcwa' ),
		description: __( 'Page header with a picture from the world, the breadcrumb, the heading and an intro.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Title', 'hcwa' ) },
			{ key: 'intro', label: __( 'Intro', 'hcwa' ) },
			{ key: 'callUs', label: __( 'Call us', 'hcwa' ), when: reach },
			{ key: 'writeUs', label: __( 'Write to us', 'hcwa' ), when: reach },
		],
	} );
}( window.wp, window.hcwaBlocks ) );
