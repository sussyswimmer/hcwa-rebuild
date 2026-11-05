/**
 * "Choose image / photos / PDF" buttons for HCWA fields (meta boxes and Settings → HCWA).
 *
 * Markup (meta.php, hcwa_render_media_control):
 *   <input type="hidden" id="ID" value="1,2">
 *   <div id="ID-preview"></div>
 *   <button data-hcwa-media="single|multiple" data-target="ID" data-type="image|application/pdf"
 *           data-title="…" data-button="…">
 *   <button data-hcwa-media-clear data-target="ID">
 * Labels come from PHP, so this file holds no visible text.
 */
( function () {
	'use strict';

	function preview( container, attachments ) {
		container.textContent = '';
		attachments.forEach( function ( item ) {
			var sizes = item.sizes || {};
			var thumb = sizes.thumbnail || sizes.medium || sizes.full;
			if ( item.type === 'image' && thumb ) {
				var img = document.createElement( 'img' );
				img.src = thumb.url;
				img.alt = item.alt || '';
				img.className = 'hcwa-media__thumb';
				container.appendChild( img );
			} else {
				var span = document.createElement( 'span' );
				span.className = 'hcwa-media__file';
				span.textContent = item.filename || item.title || String( item.id );
				container.appendChild( span );
			}
		} );
	}

	function open( button ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		var input = document.getElementById( button.dataset.target );
		var container = document.getElementById( button.dataset.target + '-preview' );
		if ( ! input ) {
			return;
		}
		var multiple = button.dataset.hcwaMedia === 'multiple';

		var frame = window.wp.media( {
			title: button.dataset.title || '',
			button: { text: button.dataset.button || '' },
			library: { type: button.dataset.type || 'image' },
			multiple: multiple ? 'add' : false,
		} );

		frame.on( 'open', function () {
			var selection = frame.state().get( 'selection' );
			input.value.split( ',' ).forEach( function ( id ) {
				id = parseInt( id, 10 );
				if ( id ) {
					var attachment = window.wp.media.attachment( id );
					attachment.fetch();
					selection.add( attachment );
				}
			} );
		} );

		frame.on( 'select', function () {
			var chosen = frame.state().get( 'selection' ).toJSON();
			if ( ! multiple ) {
				chosen = chosen.slice( 0, 1 );
			}
			input.value = chosen.map( function ( item ) {
				return item.id;
			} ).join( ',' );
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			if ( container ) {
				preview( container, chosen );
			}
		} );

		frame.open();
	}

	function clear( button ) {
		var input = document.getElementById( button.dataset.target );
		var container = document.getElementById( button.dataset.target + '-preview' );
		if ( input ) {
			input.value = '';
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
		if ( container ) {
			container.textContent = '';
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var target = event.target instanceof Element ? event.target : null;
		if ( ! target ) {
			return;
		}
		var chooser = target.closest( '[data-hcwa-media]' );
		var remover = target.closest( '[data-hcwa-media-clear]' );
		if ( chooser ) {
			event.preventDefault();
			open( chooser );
		} else if ( remover ) {
			event.preventDefault();
			clear( remover );
		}
	} );
} )();
