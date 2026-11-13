/**
 * hcwa/ways editor: Each way to help once (name, line, its page's list, links into its page, one button).
 */
( function ( wp, hcwa ) {
	'use strict';

	var __ = wp.i18n.__;

	hcwa.registerText( 'hcwa/ways', {
		title: __( 'Ways to help, one by one', 'hcwa' ),
		description: __( 'Each way to help once: its name, what it involves, links into its page and one button. Donate is the only sun button.', 'hcwa' ),
		fields: [
			{ key: 'title', label: __( 'Heading', 'hcwa' ) },
			{
				key: 'ways',
				label: __( 'Ways to help', 'hcwa' ),
				list: [
					{ key: 'title', label: __( 'Name', 'hcwa' ) },
					{ key: 'text', label: __( 'Text', 'hcwa' ) },
					{ key: 'label', label: __( 'List heading', 'hcwa' ), ifPresent: true },
					{ key: 'items', label: __( 'List item', 'hcwa' ), strings: true },
					{ key: 'note', label: __( 'Note', 'hcwa' ), ifPresent: true },
					{ key: 'links', label: __( 'Link', 'hcwa' ), strings: true },
					{ key: 'cta', label: __( 'Button', 'hcwa' ) },
				],
			},
		],
	} );
}( window.wp, window.hcwaBlocks ) );
