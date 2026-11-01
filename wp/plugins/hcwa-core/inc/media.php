<?php
/**
 * Photo approval (04-SITEMAP "Photo approval", CLAUDE.md safeguarding rules).
 *
 * Every media item gets two fields: "approved for the website" and "shows an identifiable
 * child". An image that is not approved never renders: not in templates, not inside post
 * content, not as a featured image. Pages marked "no identifiable children" (Little Rose)
 * also refuse approved images that show an identifiable child.
 */

defined( 'ABSPATH' ) || exit;

function hcwa_image_approved( $attachment_id ) {
	return (bool) get_post_meta( (int) $attachment_id, 'hcwa_approved_for_web', true );
}

function hcwa_image_identifiable( $attachment_id ) {
	// Unknown counts as identifiable: the safe answer for a shelter page.
	$value = get_post_meta( (int) $attachment_id, 'hcwa_identifiable_child', true );
	return 'no' !== $value;
}

/**
 * May this image render? $strict = the page must never show an identifiable child.
 * Enforcement can be switched off only outside production (for importing old content locally).
 */
function hcwa_image_allowed( $attachment_id, $strict = false ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id ) {
		return false;
	}
	if ( ! hcwa_is_production() && ! apply_filters( 'hcwa_enforce_image_approval', true ) ) {
		return true;
	}
	if ( ! hcwa_image_approved( $attachment_id ) ) {
		hcwa_log_refused_image( $attachment_id, 'not approved' );
		return false;
	}
	if ( $strict && hcwa_image_identifiable( $attachment_id ) ) {
		hcwa_log_refused_image( $attachment_id, 'identifiable child on a no-identifiable page' );
		return false;
	}
	return true;
}

/** Remembers refused images so Tools → HCWA checks can list them (capped, newest first). */
function hcwa_log_refused_image( $attachment_id, $reason ) {
	static $logged = array();
	if ( isset( $logged[ $attachment_id ] ) ) {
		return;
	}
	$logged[ $attachment_id ] = true;
	$log                      = (array) get_option( 'hcwa_refused_images', array() );
	if ( isset( $log[ $attachment_id ] ) ) {
		return; // already listed: no database write on every page view
	}
	$log[ $attachment_id ] = array(
		'reason' => $reason,
		'url'    => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
		'time'   => time(),
	);
	update_option( 'hcwa_refused_images', array_slice( $log, -50, null, true ), false );
}

/** Is the page being rendered a "no identifiable children" page? */
function hcwa_strict_context() {
	$id = get_queried_object_id();
	return $id && (bool) get_post_meta( $id, 'hcwa_illustration_only', true );
}

/* ---------- edit fields on every media item ---------- */

add_filter(
	'attachment_fields_to_edit',
	function ( $fields, $post ) {
		$approved     = hcwa_image_approved( $post->ID );
		$identifiable = get_post_meta( $post->ID, 'hcwa_identifiable_child', true );
		$fields['hcwa_approved_for_web'] = array(
			'label' => __( 'Approved for the website', 'hcwa-core' ),
			'input' => 'html',
			'html'  => sprintf(
				'<select name="attachments[%1$d][hcwa_approved_for_web]" id="attachments-%1$d-hcwa_approved_for_web"><option value="no" %2$s>%3$s</option><option value="yes" %4$s>%5$s</option></select>',
				$post->ID,
				selected( $approved, false, false ),
				esc_html__( 'No: will not show anywhere', 'hcwa-core' ),
				selected( $approved, true, false ),
				esc_html__( 'Yes: HCWA approved, consent on file', 'hcwa-core' )
			),
		);
		$fields['hcwa_identifiable_child'] = array(
			'label' => __( 'Shows an identifiable child', 'hcwa-core' ),
			'input' => 'html',
			'html'  => sprintf(
				'<select name="attachments[%1$d][hcwa_identifiable_child]" id="attachments-%1$d-hcwa_identifiable_child"><option value="" %2$s>%3$s</option><option value="yes" %4$s>%5$s</option><option value="no" %6$s>%7$s</option></select>',
				$post->ID,
				selected( $identifiable, '', false ),
				esc_html__( 'Not checked yet (treated as yes)', 'hcwa-core' ),
				selected( $identifiable, 'yes', false ),
				esc_html__( 'Yes: face, name or school can be seen', 'hcwa-core' ),
				selected( $identifiable, 'no', false ),
				esc_html__( 'No: hands, backs, distance, or no children', 'hcwa-core' )
			),
			'helps' => __( 'Little Rose pages never show images marked yes or not checked.', 'hcwa-core' ),
		);
		return $fields;
	},
	10,
	2
);

add_filter(
	'attachment_fields_to_save',
	function ( $post, $attachment ) {
		if ( ! current_user_can( 'edit_post', $post['ID'] ) ) {
			return $post;
		}
		$values = array();
		if ( isset( $attachment['hcwa_approved_for_web'] ) ) {
			$values['hcwa_approved_for_web'] = 'yes' === $attachment['hcwa_approved_for_web'] ? '1' : '';
		}
		if ( isset( $attachment['hcwa_identifiable_child'] ) ) {
			$values['hcwa_identifiable_child'] = in_array( $attachment['hcwa_identifiable_child'], array( 'yes', 'no' ), true ) ? $attachment['hcwa_identifiable_child'] : '';
		}
		// Polylang keeps one media item per language; approval is about the photo, so it applies to all copies.
		$ids = function_exists( 'pll_get_post_translations' ) ? array_values( pll_get_post_translations( $post['ID'] ) ) : array();
		$ids = array_unique( array_merge( array( (int) $post['ID'] ), array_map( 'intval', $ids ) ) );
		foreach ( $ids as $id ) {
			foreach ( $values as $key => $value ) {
				if ( '' === $value ) {
					delete_post_meta( $id, $key );
				} else {
					update_post_meta( $id, $key, $value );
				}
			}
		}
		// Approved now: drop it from the "refused at render" list on Tools → HCWA checks.
		if ( ! empty( $values['hcwa_approved_for_web'] ) ) {
			$log = (array) get_option( 'hcwa_refused_images', array() );
			update_option( 'hcwa_refused_images', array_diff_key( $log, array_flip( $ids ) ), false );
		}
		return $post;
	},
	10,
	2
);

/* ---------- enforcement everywhere images render ---------- */

// Images inside post content (classic and block images, galleries, media & text).
add_filter(
	'wp_content_img_tag',
	function ( $tag, $context, $attachment_id ) {
		if ( ! $attachment_id ) {
			// Images not from the media library can't carry an approval: refuse them.
			return hcwa_image_allowed_external() ? $tag : '';
		}
		return hcwa_image_allowed( $attachment_id, hcwa_strict_context() ) ? $tag : '';
	},
	10,
	3
);

/** External images (hot-linked, pasted) have no approval field; allowed only when enforcement is off locally. */
function hcwa_image_allowed_external() {
	return ! hcwa_is_production() && ! apply_filters( 'hcwa_enforce_image_approval', true );
}

// Remove image figures left empty after a refusal (runs after wp_filter_content_tags at 12),
// including their captions, which may name the child.
add_filter(
	'the_content',
	function ( $content ) {
		return preg_replace( '#<figure\b[^>]*\bwp-block-image\b[^>]*>(?:(?!<img\b).)*?</figure>#is', '', $content );
	},
	13
);

// Featured images.
add_filter(
	'post_thumbnail_html',
	function ( $html, $post_id, $thumbnail_id ) {
		return $thumbnail_id && hcwa_image_allowed( $thumbnail_id, (bool) get_post_meta( $post_id, 'hcwa_illustration_only', true ) || hcwa_strict_context() ) ? $html : '';
	},
	10,
	3
);

// Social share images follow the same rule (Yoast reads the featured image).
add_filter(
	'wpseo_opengraph_image',
	function ( $url ) {
		$id = attachment_url_to_postid( $url );
		return ( $id && ! hcwa_image_allowed( $id ) ) ? '' : $url;
	}
);
