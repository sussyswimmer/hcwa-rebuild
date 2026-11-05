/**
 * Shared editor helpers for the hcwa/* blocks: window.hcwaBlocks.
 * Plain JS, no build step. Registered as "hcwa-blocks-editor" in includes/blocks.php and
 * listed as a dependency in every block's index.asset.php.
 *
 * Every block is dynamic: the editor shows the server-rendered markup (ServerSideRender)
 * and saves only the block comment with its attributes.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var components = wp.components;
	var blockEditor = wp.blockEditor;
	var useSelect = wp.data.useSelect;
	var decodeEntities = wp.htmlEntities.decodeEntities;
	var ServerSideRender = wp.serverSideRender;

	/**
	 * Language of the post being edited, read from Polylang's Languages box (the same
	 * field Polylang's own editor script reads). '' without Polylang.
	 *
	 * @return {string} 'vi', 'en' or ''.
	 */
	function postLanguage() {
		var field = document.querySelector( '[name="post_lang_choice"]' );
		var value = field ? field.value : '';
		return 'vi' === value || 'en' === value ? value : '';
	}

	/**
	 * Server-rendered preview in the post's language (Polylang reads ?lang= on REST
	 * requests). Disabled, so a click on a link inside it cannot leave the editor.
	 *
	 * @param {Object} props block, attributes, label, empty.
	 * @return {Object} Element.
	 */
	function Preview( props ) {
		var lang = postLanguage();
		return el(
			components.Disabled,
			null,
			el( ServerSideRender, {
				block: props.block,
				attributes: props.attributes,
				urlQueryArgs: lang ? { lang: lang } : {},
				EmptyResponsePlaceholder: function () {
					return el( components.Placeholder, { label: props.label, instructions: props.empty } );
				},
			} )
		);
	}

	/**
	 * Dropdown of published posts of one type (centers or programs), in the post's
	 * language. The first option is 0 ("This page" or "All centers").
	 *
	 * @param {Object} props label, postType, value, onChange, zeroLabel, help.
	 * @return {Object} Element.
	 */
	function PostSelect( props ) {
		var records = useSelect(
			function ( select ) {
				return select( 'core' ).getEntityRecords( 'postType', props.postType, {
					per_page: 100,
					status: 'publish',
					orderby: 'title',
					order: 'asc',
				} );
			},
			[ props.postType ]
		);

		var value = parseInt( props.value, 10 ) || 0;
		var options = [ { value: '0', label: props.zeroLabel } ];
		var found = 0 === value;

		( records || [] ).forEach( function ( record ) {
			var title = record.title && record.title.rendered ? decodeEntities( record.title.rendered ) : '';
			options.push( { value: String( record.id ), label: title || '#' + record.id } );
			if ( record.id === value ) {
				found = true;
			}
		} );
		// A saved ID from the other language (or a draft) still shows, as its number.
		if ( ! found ) {
			options.push( { value: String( value ), label: '#' + value } );
		}

		return el( components.SelectControl, {
			label: props.label,
			help: props.help,
			value: String( value ),
			options: options,
			onChange: function ( next ) {
				props.onChange( parseInt( next, 10 ) || 0 );
			},
		} );
	}

	/**
	 * Registers a dynamic block whose block.json the server already registered.
	 *
	 * @param {string} name Block name, e.g. 'hcwa/impact-strip'.
	 * @param {Object} spec title, description, empty (shown when the preview is empty),
	 *                      controls( props ) => array of sidebar controls (optional),
	 *                      panel( props ) => extra sidebar content (optional).
	 */
	function register( name, spec ) {
		wp.blocks.registerBlockType( name, {
			title: spec.title,
			description: spec.description,
			edit: function ( props ) {
				var blockProps = blockEditor.useBlockProps();
				var controls = spec.controls ? spec.controls( props ) : null;
				var sidebar = controls
					? el(
						blockEditor.InspectorControls,
						null,
						el( components.PanelBody, { title: __( 'Settings', 'hcwa' ) }, controls )
					)
					: null;

				return el(
					'div',
					blockProps,
					sidebar,
					el( Preview, {
						block: name,
						attributes: props.attributes,
						label: spec.title,
						empty: spec.empty,
					} )
				);
			},
			save: function () {
				return null;
			},
		} );
	}

	window.hcwaBlocks = {
		el: el,
		postLanguage: postLanguage,
		Preview: Preview,
		PostSelect: PostSelect,
		register: register,
	};
}( window.wp ) );
