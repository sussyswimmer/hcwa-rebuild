/**
 * hcwa/language-notice editor. The notice shows only when the language switch sends a
 * visitor here with ?hcwa_lang_notice=, so the sidebar picks the language of the preview
 * alone: it is editor state, never saved (a saved forceLang would show the notice to
 * every reader of the post). single.php passes forceLang itself.
 */
( function ( wp, hcwa ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;

	hcwa.register( 'hcwa/language-notice', {
		title: __( 'Language notice', 'hcwa' ),
		description: __( 'Tells visitors that an article exists in one language only, with links to it and to the news page.', 'hcwa' ),
		empty: __( 'Shows only to visitors the language switch sends here because this post has no translation in their language. Choose a language under "Preview for a visitor who asked for" to see it.', 'hcwa' ),
		controls: function ( props, preview, setPreview ) {
			return [
				el( c.SelectControl, {
					key: 'preview',
					label: __( 'Preview for a visitor who asked for', 'hcwa' ),
					help: __( 'Changes only this preview. Nothing is saved with the post.', 'hcwa' ),
					value: preview,
					options: [
						{ value: '', label: __( 'No preview', 'hcwa' ) },
						{ value: 'vi', label: __( 'Vietnamese', 'hcwa' ) },
						{ value: 'en', label: __( 'English', 'hcwa' ) },
					],
					onChange: setPreview,
				} ),
			];
		},
		previewQuery: function ( preview ) {
			return preview ? { hcwa_lang_notice: preview } : {};
		},
	} );
}( window.wp, window.hcwaBlocks ) );
