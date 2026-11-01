/**
 * Editor side of the HCWA blocks. Plain browser JavaScript on purpose (no build step), so a
 * volunteer can change it with a text editor. Blocks render on the server; the editor shows
 * that render (ServerSideRender) plus sidebar controls.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var __ = wp.i18n.__;
	var ServerSideRender = wp.serverSideRender;
	var data = window.hcwaEditor || {};

	function options( list, empty ) {
		return ( empty ? [ { value: '', label: empty } ] : [] ).concat( list || [] );
	}

	/* ---------- "Draft: waiting for HCWA approval" on every block ---------- */

	wp.hooks.addFilter( 'blocks.registerBlockType', 'hcwa/draft-attribute', function ( settings ) {
		if ( settings.attributes && ! settings.attributes.hcwaDraft ) {
			settings.attributes = Object.assign( {}, settings.attributes, { hcwaDraft: { type: 'boolean', default: false } } );
		}
		return settings;
	} );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'hcwa/draft-control',
		wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
			return function ( props ) {
				if ( ! props.isSelected || ! props.attributes || props.attributes.hcwaDraft === undefined ) {
					return el( BlockEdit, props );
				}
				return el(
					Fragment,
					null,
					el( BlockEdit, props ),
					el(
						be.InspectorControls,
						null,
						el(
							c.PanelBody,
							{ title: __( 'HCWA review', 'hcwa-core' ), initialOpen: !! props.attributes.hcwaDraft },
							el( c.ToggleControl, {
								label: __( 'Draft: waiting for HCWA approval', 'hcwa-core' ),
								help: __( 'Drafts show with a dashed outline on staging and are left out of the live site.', 'hcwa-core' ),
								checked: !! props.attributes.hcwaDraft,
								onChange: function ( v ) {
									props.setAttributes( { hcwaDraft: v } );
								},
								__nextHasNoMarginBottom: true,
							} )
						)
					)
				);
			};
		}, 'withHcwaDraftControl' )
	);

	wp.hooks.addFilter(
		'editor.BlockListBlock',
		'hcwa/draft-outline',
		wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
			return function ( props ) {
				if ( props.attributes && props.attributes.hcwaDraft ) {
					return el( BlockListBlock, Object.assign( {}, props, { className: ( props.className || '' ) + ' is-hcwa-draft' } ) );
				}
				return el( BlockListBlock, props );
			};
		}, 'withHcwaDraftOutline' )
	);

	/* ---------- generic server-rendered blocks ---------- */

	// [ attribute, control type, label, choices ]
	var controls = {
		'hcwa/impact-strip': [
			[ 'scope', 'select', __( 'Figures for', 'hcwa-core' ), function () {
				return [ { value: 'organization', label: __( 'Whole organization', 'hcwa-core' ) } ].concat( data.centers || [], data.programs || [] );
			} ],
			[ 'variant', 'select', __( 'Layout', 'hcwa-core' ), [
				{ value: 'grid', label: __( 'Row of figures', 'hcwa-core' ) },
				{ value: 'strip', label: __( 'Homepage panel under the hero', 'hcwa-core' ) },
			] ],
			[ 'min', 'range', __( 'Hide unless this many figures are verified', 'hcwa-core' ), [ 1, 4 ] ],
		],
		'hcwa/center-grid': [
			[ 'area', 'select', __( 'Only this focus area', 'hcwa-core' ), function () { return options( data.areas, __( 'All', 'hcwa-core' ) ); } ],
			[ 'followFilter', 'toggle', __( 'Follow the focus area filter on this page', 'hcwa-core' ) ],
			[ 'count', 'range', __( 'How many (0 = all)', 'hcwa-core' ), [ 0, 12 ] ],
		],
		'hcwa/program-list': [
			[ 'status', 'select', __( 'Status', 'hcwa-core' ), [
				{ value: '', label: __( 'All, active first', 'hcwa-core' ) },
				{ value: 'active', label: __( 'Active', 'hcwa-core' ) },
				{ value: 'completed', label: __( 'Completed', 'hcwa-core' ) },
				{ value: 'planned', label: __( 'Planned', 'hcwa-core' ) },
			] ],
			[ 'center', 'select', __( 'Only programs at', 'hcwa-core' ), function () { return options( data.centers, __( 'Any center', 'hcwa-core' ) ); } ],
			[ 'area', 'select', __( 'Only this focus area', 'hcwa-core' ), function () { return options( data.areas, __( 'All', 'hcwa-core' ) ); } ],
			[ 'followFilter', 'toggle', __( 'Follow the focus area filter on this page', 'hcwa-core' ) ],
			[ 'count', 'range', __( 'How many (0 = all)', 'hcwa-core' ), [ 0, 20 ] ],
		],
		'hcwa/latest-news': [
			[ 'featured', 'toggle', __( 'Featured story first (latest post in Stories)', 'hcwa-core' ) ],
			[ 'count', 'range', __( 'News items', 'hcwa-core' ), [ 1, 6 ] ],
			[ 'tag', 'text', __( 'Only posts with this tag (slug)', 'hcwa-core' ) ],
		],
		'hcwa/report-list': [
			[ 'type', 'select', __( 'Type', 'hcwa-core' ), function () { return options( data.reportTypes, __( 'All types', 'hcwa-core' ) ); } ],
			[ 'program', 'select', __( 'Program', 'hcwa-core' ), function () { return options( data.programs, __( 'Any', 'hcwa-core' ) ); } ],
			[ 'year', 'number', __( 'Year (0 = all)', 'hcwa-core' ) ],
			[ 'count', 'range', __( 'How many', 'hcwa-core' ), [ 1, 20 ] ],
			[ 'showAllLink', 'toggle', __( 'Link to all reports when there are more', 'hcwa-core' ) ],
		],
		'hcwa/partner-wall': [
			[ 'homepageOnly', 'toggle', __( 'Only partners marked "show on homepage"', 'hcwa-core' ) ],
			[ 'type', 'select', __( 'Type', 'hcwa-core' ), [
				{ value: '', label: __( 'All', 'hcwa-core' ) },
				{ value: 'corporate', label: __( 'Corporate', 'hcwa-core' ) },
				{ value: 'ngo', label: __( 'NGO', 'hcwa-core' ) },
				{ value: 'government', label: __( 'Government', 'hcwa-core' ) },
				{ value: 'foundation', label: __( 'Foundation', 'hcwa-core' ) },
				{ value: 'academic', label: __( 'Academic', 'hcwa-core' ) },
			] ],
			[ 'count', 'range', __( 'How many', 'hcwa-core' ), [ 1, 48 ] ],
		],
		'hcwa/contact-details': [
			[ 'variant', 'select', __( 'Layout', 'hcwa-core' ), [
				{ value: 'band', label: __( 'Phone and email in a line (blue band)', 'hcwa-core' ) },
				{ value: 'card', label: __( 'Office card with address and map link', 'hcwa-core' ) },
			] ],
		],
		'hcwa/donation-details': [
			[ 'method', 'select', __( 'Method', 'hcwa-core' ), [
				{ value: 'vietnam', label: __( 'Bank transfer in Vietnam (with VietQR)', 'hcwa-core' ) },
				{ value: 'abroad', label: __( 'Transfer from abroad (USD/EUR, SWIFT)', 'hcwa-core' ) },
				{ value: 'paypal', label: __( 'PayPal', 'hcwa-core' ) },
			] ],
		],
		'hcwa/language-notice': [ [ 'postId', 'number', __( 'ID of the article in the other language', 'hcwa-core' ) ] ],
		'hcwa/focus-areas': [],
		'hcwa/work-filters': [],
	};

	function control( spec, props ) {
		var name = spec[ 0 ];
		var value = props.attributes[ name ];
		var set = function ( v ) {
			var next = {};
			next[ name ] = v;
			props.setAttributes( next );
		};
		var choices = typeof spec[ 3 ] === 'function' ? spec[ 3 ]() : spec[ 3 ];
		var common = { key: name, label: spec[ 2 ], __nextHasNoMarginBottom: true };
		switch ( spec[ 1 ] ) {
			case 'select':
				return el( c.SelectControl, Object.assign( common, { value: value, options: choices, onChange: set, __next40pxDefaultSize: true } ) );
			case 'toggle':
				return el( c.ToggleControl, Object.assign( common, { checked: !! value, onChange: set } ) );
			case 'range':
				return el( c.RangeControl, Object.assign( common, { value: value, min: choices[ 0 ], max: choices[ 1 ], onChange: set, __next40pxDefaultSize: true } ) );
			case 'number':
				return el( c.TextControl, Object.assign( common, { type: 'number', value: value || '', onChange: function ( v ) { set( parseInt( v, 10 ) || 0 ); }, __next40pxDefaultSize: true } ) );
			default:
				return el( c.TextControl, Object.assign( common, { value: value || '', onChange: set, __next40pxDefaultSize: true } ) );
		}
	}

	function empty() {
		return el( 'p', { className: 'hcwa-empty' }, __( 'Nothing to show here yet (for example no verified figures, or nothing matches the settings). Visitors see nothing.', 'hcwa-core' ) );
	}

	function serverEdit( name ) {
		return function ( props ) {
			var specs = controls[ name ] || [];
			return el(
				Fragment,
				null,
				specs.length
					? el( be.InspectorControls, null, el( c.PanelBody, { title: __( 'Settings', 'hcwa-core' ) }, specs.map( function ( s ) { return control( s, props ); } ) ) )
					: null,
				el( 'div', be.useBlockProps(), el( ServerSideRender, { block: name, attributes: props.attributes, EmptyResponsePlaceholder: empty } ) )
			);
		};
	}

	Object.keys( controls ).forEach( function ( name ) {
		wp.blocks.registerBlockType( name, { edit: serverEdit( name ), save: function () { return null; } } );
	} );

	/* ---------- hcwa/link: label typed in place, target picked in the sidebar ---------- */

	wp.blocks.registerBlockType( 'hcwa/link', {
		edit: function ( props ) {
			var a = props.attributes;
			var cls = a.kind === 'text' ? 'text-link' : 'btn btn--' + a.kind;
			return el(
				Fragment,
				null,
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Link', 'hcwa-core' ) },
						el( c.SelectControl, {
							label: __( 'Goes to', 'hcwa-core' ),
							help: __( 'A page role links to the right language by itself.', 'hcwa-core' ),
							value: a.target,
							options: options( data.targets, __( 'Custom URL', 'hcwa-core' ) ),
							onChange: function ( v ) { props.setAttributes( { target: v } ); },
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						a.target ? null : el( c.TextControl, {
							label: __( 'URL', 'hcwa-core' ),
							type: 'url',
							value: a.url,
							onChange: function ( v ) { props.setAttributes( { url: v } ); },
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} ),
						el( c.SelectControl, {
							label: __( 'Style', 'hcwa-core' ),
							value: a.kind,
							options: [
								{ value: 'text', label: __( 'Text link with arrow', 'hcwa-core' ) },
								{ value: 'primary', label: __( 'Yellow button (one per section)', 'hcwa-core' ) },
								{ value: 'secondary', label: __( 'Blue outline button', 'hcwa-core' ) },
								{ value: 'on-blue', label: __( 'White outline button (on blue)', 'hcwa-core' ) },
							],
							onChange: function ( v ) { props.setAttributes( { kind: v } ); },
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
						} )
					)
				),
				el(
					'div',
					be.useBlockProps( { className: 'hcwa-link-edit' } ),
					el( be.RichText, {
						tagName: 'span',
						className: cls,
						value: a.label,
						allowedFormats: [],
						withoutInteractiveFormatting: true,
						placeholder: __( 'Link text…', 'hcwa-core' ),
						onChange: function ( v ) { props.setAttributes( { label: v } ); },
					} )
				)
			);
		},
		save: function () { return null; },
	} );

	/* ---------- hcwa/image-slot: pick from the media library ---------- */

	wp.blocks.registerBlockType( 'hcwa/image-slot', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = function ( k ) { return function ( v ) { var n = {}; n[ k ] = v; props.setAttributes( n ); }; };
			return el(
				Fragment,
				null,
				el(
					be.BlockControls,
					null,
					el(
						be.MediaUploadCheck,
						null,
						el( be.MediaUpload, {
							allowedTypes: [ 'image' ],
							value: a.id,
							onSelect: function ( m ) { props.setAttributes( { id: m.id } ); },
							render: function ( o ) {
								return el( c.ToolbarGroup, null, el( c.ToolbarButton, { onClick: o.open, icon: 'format-image', label: __( 'Choose image', 'hcwa-core' ) } ) );
							},
						} )
					)
				),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Image slot', 'hcwa-core' ) },
						el( c.Notice, { status: 'info', isDismissible: false }, __( 'Only images marked "Approved for the website" in the media library show. Little Rose pages also refuse images with an identifiable child.', 'hcwa-core' ) ),
						el( c.TextControl, { label: __( 'Asset code (visuals list)', 'hcwa-core' ), value: a.code, onChange: set( 'code' ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } ),
						el( c.TextControl, { label: __( 'Placeholder label (VI)', 'hcwa-core' ), value: a.labelVi, onChange: set( 'labelVi' ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } ),
						el( c.TextControl, { label: __( 'Placeholder label (EN)', 'hcwa-core' ), value: a.labelEn, onChange: set( 'labelEn' ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } ),
						el( c.SelectControl, { label: __( 'Shape', 'hcwa-core' ), value: a.ratio, options: [ '1:1', '3:2', '4:3', '4:5', '16:9', '3:4' ].map( function ( r ) { return { value: r, label: r }; } ), onChange: set( 'ratio' ), __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } ),
						el( c.ToggleControl, { label: __( 'Illustration slot', 'hcwa-core' ), checked: a.illustration, onChange: set( 'illustration' ), __nextHasNoMarginBottom: true } ),
						el( c.ToggleControl, { label: __( 'Top of the page (load first)', 'hcwa-core' ), checked: a.eager, onChange: set( 'eager' ), __nextHasNoMarginBottom: true } ),
						a.id ? el( c.Button, { variant: 'secondary', onClick: function () { props.setAttributes( { id: 0 } ); } }, __( 'Remove image', 'hcwa-core' ) ) : null
					)
				),
				el( 'div', be.useBlockProps(), el( ServerSideRender, { block: 'hcwa/image-slot', attributes: a, EmptyResponsePlaceholder: empty } ) )
			);
		},
		save: function () { return null; },
	} );

	/* ---------- pages and posts: "intro is a draft" in the document sidebar ---------- */

	var DocPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	if ( wp.plugins && DocPanel ) {
		wp.plugins.registerPlugin( 'hcwa-intro-draft', {
			render: function () {
				var type = wp.data.useSelect( function ( s ) { return s( 'core/editor' ).getCurrentPostType(); }, [] );
				var meta = wp.data.useSelect( function ( s ) { return s( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}; }, [] );
				var edit = wp.data.useDispatch( 'core/editor' ).editPost;
				if ( type !== 'page' && type !== 'post' ) {
					return null;
				}
				return el(
					DocPanel,
					{ name: 'hcwa-intro-draft', title: __( 'HCWA review', 'hcwa-core' ) },
					el( c.ToggleControl, {
						label: __( 'Intro (excerpt) is a draft, waiting for HCWA approval', 'hcwa-core' ),
						help: __( 'The excerpt is the line under the page title. Drafts are outlined on staging and hidden on the live site.', 'hcwa-core' ),
						checked: !! meta.hcwa_intro_draft,
						onChange: function ( v ) { edit( { meta: { hcwa_intro_draft: v } } ); },
						__nextHasNoMarginBottom: true,
					} )
				);
			},
		} );
	}
} )( window.wp );
