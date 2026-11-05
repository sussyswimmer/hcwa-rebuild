/**
 * hcwa/announcement-bar editor. The theme prints the bar above the header, so the block
 * is hidden from the inserter; this only previews it where it already exists.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	hcwa.register( 'hcwa/announcement-bar', {
		title: __( 'Announcement bar', 'hcwa' ),
		description: __( 'The one-line announcement above the header. Edit it in Settings → HCWA.', 'hcwa' ),
		empty: __( 'The announcement bar is switched off, or has no text in this language.', 'hcwa' ),
		controls: function () {
			// Relative link: the editor runs inside /wp-admin/.
			return [ el( 'p', { key: 'settings' }, el( 'a', { href: 'options-general.php?page=hcwa-settings' }, __( 'Open Settings → HCWA', 'hcwa' ) ) ) ];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
