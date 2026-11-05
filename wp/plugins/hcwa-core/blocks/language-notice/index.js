/**
 * hcwa/language-notice editor: which language the notice speaks. Empty = only when the
 * language switch sends a visitor here with ?hcwa_lang_notice=.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/language-notice', {
		title: __( 'Language notice', 'hcwa' ),
		description: __( 'Tells visitors that an article exists in one language only, with links to it and to the news page.', 'hcwa' ),
		empty: __( 'Shows on a post when a visitor switches to a language the post is not translated into. Pick the other language to preview it.', 'hcwa' ),
		controls: function ( props ) {
			return [
				el( c.SelectControl, {
					key: 'lang',
					label: __( 'Language the visitor asked for', 'hcwa' ),
					value: props.attributes.forceLang,
					options: [
						{ value: '', label: __( 'Only when the language switch asks for it', 'hcwa' ) },
						{ value: 'vi', label: __( 'Vietnamese', 'hcwa' ) },
						{ value: 'en', label: __( 'English', 'hcwa' ) },
					],
					onChange: function ( value ) {
						props.setAttributes( { forceLang: value } );
					},
				} ),
			];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
