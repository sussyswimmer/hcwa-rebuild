/* Media pickers for the HCWA details box (unit logo, gallery, report PDF). */
( function () {
	'use strict';
	document.querySelectorAll( '.hcwa-media' ).forEach( function ( box ) {
		var input = box.querySelector( 'input[type="hidden"]' );
		var preview = box.querySelector( '.hcwa-media__preview' );
		var multiple = box.dataset.multiple === '1';
		var frame = null;

		function show( items ) {
			preview.replaceChildren();
			items.forEach( function ( item ) {
				var thumb = item.sizes && ( item.sizes.thumbnail || item.sizes.full );
				if ( thumb ) {
					var img = document.createElement( 'img' );
					img.src = thumb.url;
					img.alt = '';
					preview.append( img );
				} else {
					var span = document.createElement( 'span' );
					span.textContent = item.filename || item.title || item.id;
					preview.append( span );
				}
			} );
		}

		box.querySelector( '.hcwa-media__pick' ).addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = window.wp.media( {
					multiple: multiple ? 'add' : false,
					library: { type: box.dataset.library },
				} );
				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );
					input.value.split( ',' ).filter( Boolean ).forEach( function ( id ) {
						var att = window.wp.media.attachment( id );
						att.fetch();
						selection.add( att );
					} );
				} );
				frame.on( 'select', function () {
					var items = frame.state().get( 'selection' ).toJSON();
					input.value = items.map( function ( i ) { return i.id; } ).join( ',' );
					show( items );
				} );
			}
			frame.open();
		} );

		box.querySelector( '.hcwa-media__clear' ).addEventListener( 'click', function () {
			input.value = '';
			preview.replaceChildren();
		} );
	} );
} )();
