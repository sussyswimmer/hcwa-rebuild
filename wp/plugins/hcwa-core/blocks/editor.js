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
	var useState = wp.element.useState;
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
	 * @param {Object} props block, attributes, label, empty, query (extra query arguments
	 *                       of the preview request, optional).
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
				urlQueryArgs: Object.assign( lang ? { lang: lang } : {}, props.query || {} ),
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
	 *                      controls( props, preview, setPreview ) => array of sidebar
	 *                      controls (optional),
	 *                      previewQuery( preview ) => extra query arguments of the preview
	 *                      request (optional).
	 *                      preview is editor-only state (default ''): it changes what the
	 *                      preview shows and is never saved with the block.
	 */
	function register( name, spec ) {
		wp.blocks.registerBlockType( name, {
			title: spec.title,
			description: spec.description,
			edit: function ( props ) {
				var blockProps = blockEditor.useBlockProps();
				var previewState = useState( '' );
				var controls = spec.controls ? spec.controls( props, previewState[ 0 ], previewState[ 1 ] ) : null;
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
						query: spec.previewQuery ? spec.previewQuery( previewState[ 0 ] ) : null,
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

	/**
	 * One plain-text field edited in the canvas (11-WP-PORT.md 4.1): RichText with no formats,
	 * no line breaks and no links, so the stored attribute stays plain text. The server still
	 * cleans it with hcwa_attr_text() before printing.
	 *
	 * @param {Object} props label, value, onChange.
	 * @return {Object} Element.
	 */
	function TextField( props ) {
		return el(
			'div',
			{ className: 'hcwa-text-field' },
			el( 'span', { className: 'hcwa-text-field__label' }, props.label ),
			el( blockEditor.RichText, {
				tagName: 'div',
				className: 'hcwa-text-field__value',
				value: props.value || '',
				allowedFormats: [],
				disableLineBreaks: true,
				withoutInteractiveFormatting: true,
				placeholder: props.label,
				onChange: props.onChange,
			} )
		);
	}

	/**
	 * The text fields of a block: plain fields, and lists of items (ways[], faq[], gifts[])
	 * whose items keep their order and count (the pattern sets them; staff change words only).
	 *
	 * @param {Object} props  Block props.
	 * @param {Array}  fields [{ key, label, when? }] or [{ key, label, list: [{ key, label }] }].
	 * @return {Array} Elements.
	 */
	function textFields( props, fields ) {
		var a = props.attributes;
		// A field with `when` shows only while the block prints it (Call us and Write to us
		// belong to the Contact header).
		return fields.filter( function ( field ) {
			return ! field.when || field.when( a );
		} ).map( function ( field ) {
			if ( ! field.list ) {
				return el( TextField, {
					key: field.key,
					label: field.label,
					value: a[ field.key ],
					onChange: function ( value ) {
						var next = {};
						next[ field.key ] = value;
						props.setAttributes( next );
					},
				} );
			}
			var items = Array.isArray( a[ field.key ] ) ? a[ field.key ] : [];
			return el(
				'fieldset',
				{ key: field.key, className: 'hcwa-text-list' },
				el( 'legend', null, field.label ),
				items.map( function ( item, i ) {
					return el(
						'div',
						{ key: i, className: 'hcwa-text-list__item' },
						el( 'span', { className: 'hcwa-text-list__n' }, wp.i18n.sprintf( __( 'Item %d', 'hcwa' ), i + 1 ) ),
						field.list.map( function ( sub ) {
							return el( TextField, {
								key: sub.key,
								label: sub.label,
								value: item ? item[ sub.key ] : '',
								onChange: function ( value ) {
									var copy = items.map( function ( x ) {
										return Object.assign( {}, x );
									} );
									copy[ i ][ sub.key ] = value;
									var next = {};
									next[ field.key ] = copy;
									props.setAttributes( next );
								},
							} );
						} )
					);
				} )
			);
		} );
	}

	/**
	 * Registers a section block whose words are edited in the canvas, above the
	 * server-rendered preview.
	 *
	 * @param {string} name Block name.
	 * @param {Object} spec title, description, fields (see textFields()), empty.
	 */
	function registerText( name, spec ) {
		wp.blocks.registerBlockType( name, {
			title: spec.title,
			description: spec.description,
			edit: function ( props ) {
				var blockProps = blockEditor.useBlockProps( { className: 'hcwa-section-edit' } );
				return el(
					'div',
					blockProps,
					spec.fields && spec.fields.length
						? el(
							'div',
							{ className: 'hcwa-section-edit__words' },
							el( 'p', { className: 'hcwa-section-edit__title' }, spec.title ),
							textFields( props, spec.fields ),
							el( 'p', { className: 'hcwa-section-edit__help' }, __( 'Draft text shows only on staging. On the live site it is left out.', 'hcwa' ) )
						)
						: null,
					el( Preview, {
						block: name,
						attributes: props.attributes,
						label: spec.title,
						empty: spec.empty || __( 'Nothing to show here yet.', 'hcwa' ),
					} )
				);
			},
			save: function () {
				return null;
			},
		} );
	}

	/**
	 * Registers a container block (hcwa/sections, hcwa/day): its inner blocks are locked with
	 * the block's templateLock attribute and saved without any wrapper markup.
	 *
	 * @param {string} name Block name.
	 * @param {Object} spec title, description.
	 */
	function registerContainer( name, spec ) {
		wp.blocks.registerBlockType( name, {
			title: spec.title,
			description: spec.description,
			edit: function ( props ) {
				var lock = props.attributes.templateLock;
				var blockProps = blockEditor.useBlockProps( { className: 'hcwa-container-edit' } );
				var innerProps = blockEditor.useInnerBlocksProps( blockProps, {
					templateLock: lock === 'all' || lock === 'contentOnly' ? lock : false,
					renderAppender: false,
				} );
				return el( 'div', innerProps );
			},
			save: function () {
				return el( blockEditor.InnerBlocks.Content );
			},
		} );
	}

	window.hcwaBlocks = {
		el: el,
		postLanguage: postLanguage,
		Preview: Preview,
		PostSelect: PostSelect,
		register: register,
		registerText: registerText,
		registerContainer: registerContainer,
	};
}( window.wp ) );
