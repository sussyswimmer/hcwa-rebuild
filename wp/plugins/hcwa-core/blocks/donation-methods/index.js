/**
 * hcwa/donation-methods editor: no settings here; staff edit the details (and HCWA
 * finance confirms them) in Settings → HCWA.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	hcwa.register( 'hcwa/donation-methods', {
		title: __( 'Donation methods', 'hcwa' ),
		description: __( 'Bank transfer and giving from abroad. Shows only details HCWA finance has confirmed in Settings → HCWA.', 'hcwa' ),
		empty: __( 'Donation details appear here once HCWA finance has confirmed them in Settings → HCWA.', 'hcwa' ),
		controls: function () {
			// Relative link: the editor runs inside /wp-admin/.
			return [ el( 'p', { key: 'settings' }, el( 'a', { href: 'options-general.php?page=hcwa-settings' }, __( 'Open Settings → HCWA', 'hcwa' ) ) ) ];
		},
	} );
}( window.wp, window.hcwaBlocks ) );
