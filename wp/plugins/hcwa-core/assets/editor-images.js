/**
 * Block editor, on posts whose content images are gated (hcwa_content_images_gated()):
 * warns when an image block shows a photo the live site will leave out, because it is not
 * approved for web, is marked "Identifiable child" where that is not allowed, or was
 * inserted from a URL instead of the Media Library. Settings come from
 * window.hcwaImageApproval (media-approval.php).
 */
( function ( wp, settings ) {
	'use strict';

	if ( ! wp || ! wp.data || ! settings ) {
		return;
	}

	var NOTICE_ID = 'hcwa-image-approval';
	var lastKey = '';

	function collect( blocks, found ) {
		( blocks || [] ).forEach( function ( block ) {
			var a = block.attributes || {};
			var id = 0;
			if ( 'core/image' === block.name ) {
				id = a.id;
				if ( ! id && a.url ) {
					found.fromUrl += 1;
				}
			} else if ( 'core/cover' === block.name && ! a.useFeaturedImage ) {
				id = a.id;
				if ( ! id && a.url && 'video' !== a.backgroundType ) {
					found.fromUrl += 1;
				}
			} else if ( 'core/media-text' === block.name && ! a.useFeaturedImage && 'video' !== a.mediaType ) {
				id = a.mediaId;
				if ( ! id && a.mediaUrl ) {
					found.fromUrl += 1;
				}
			}
			if ( id && found.ids.indexOf( id ) === -1 ) {
				found.ids.push( id );
			}
			collect( block.innerBlocks, found );
		} );
		return found;
	}

	// '' when the image shows, else the reason. Unknown (still loading, or meta not readable
	// by this user) counts as showing, so the warning never cries wolf.
	function reason( media ) {
		var meta = media && media.meta;
		if ( ! meta || ! Object.prototype.hasOwnProperty.call( meta, 'hcwa_approved_for_web' ) ) {
			return '';
		}
		if ( ! meta.hcwa_approved_for_web ) {
			return 'notApproved';
		}
		if ( settings.noIdentifiable && meta.hcwa_identifiable_child ) {
			return 'identifiable';
		}
		return '';
	}

	wp.data.subscribe( function () {
		var blockEditor = wp.data.select( 'core/block-editor' );
		var core = wp.data.select( 'core' );
		if ( ! blockEditor || ! core || ! core.getMedia ) {
			return;
		}

		var found = collect( blockEditor.getBlocks(), { ids: [], fromUrl: 0 } );
		var names = { notApproved: [], identifiable: [] };
		found.ids.forEach( function ( id ) {
			var media = core.getMedia( id, { context: 'edit' } );
			var why = reason( media );
			if ( why ) {
				names[ why ].push( ( media.title && media.title.raw ) || '#' + id );
			}
		} );

		var key = JSON.stringify( [ names, found.fromUrl ] );
		if ( key === lastKey ) {
			return;
		}
		lastKey = key;

		var lines = [];
		if ( names.notApproved.length ) {
			lines.push( settings.notApproved.replace( '%s', names.notApproved.join( ', ' ) ) );
		}
		if ( names.identifiable.length ) {
			lines.push( settings.identifiable.replace( '%s', names.identifiable.join( ', ' ) ) );
		}
		if ( found.fromUrl ) {
			lines.push( settings.fromUrl.replace( '%d', String( found.fromUrl ) ) );
		}

		var notices = wp.data.dispatch( 'core/notices' );
		if ( lines.length ) {
			notices.createWarningNotice( lines.join( ' ' ), { id: NOTICE_ID, isDismissible: true } );
		} else {
			notices.removeNotice( NOTICE_ID );
		}
	} );
} )( window.wp, window.hcwaImageApproval );
