/**
 * Block editor, live site only: after each save (including the separate meta box save),
 * ask the REST API whether the publish guard put the post back to draft, and say why.
 * Settings come from window.hcwaPublishGuard (publish-guard.php).
 */
( function ( wp, settings ) {
	'use strict';

	if ( ! wp || ! wp.data || ! wp.apiFetch || ! settings ) {
		return;
	}

	var NOTICE_ID = 'hcwa-publish-guard';
	var wasBusy = false;

	function isBusy() {
		var editor = wp.data.select( 'core/editor' );
		var editPost = wp.data.select( 'core/edit-post' );
		var saving = editor && editor.isSavingPost() && ! editor.isAutosavingPost();
		var savingBoxes = editPost && editPost.isSavingMetaBoxes ? editPost.isSavingMetaBoxes() : false;
		return Boolean( saving || savingBoxes );
	}

	function check() {
		var editor = wp.data.select( 'core/editor' );
		var postId = editor.getCurrentPostId();
		var base = settings.restBases[ editor.getCurrentPostType() ];
		if ( ! postId || ! base ) {
			return;
		}

		wp.apiFetch( { path: '/wp/v2/' + base + '/' + postId + '?context=edit&_fields=status,hcwa_publish_blocked' } )
			.then( function ( post ) {
				var notices = wp.data.dispatch( 'core/notices' );
				if ( post && post.hcwa_publish_blocked && post.status === 'draft' ) {
					notices.createWarningNotice( settings.message.replace( '%s', post.hcwa_publish_blocked ), {
						id: NOTICE_ID,
						isDismissible: true,
						actions: [
							{
								label: settings.reload,
								onClick: function () {
									window.location.reload();
								},
							},
						],
					} );
				} else {
					notices.removeNotice( NOTICE_ID );
				}
			} )
			.catch( function () {} );
	}

	wp.data.subscribe( function () {
		if ( ! wp.data.select( 'core/editor' ) ) {
			return;
		}
		var busy = isBusy();
		if ( wasBusy && ! busy ) {
			check();
		}
		wasBusy = busy;
	} );
} )( window.wp, window.hcwaPublishGuard );
