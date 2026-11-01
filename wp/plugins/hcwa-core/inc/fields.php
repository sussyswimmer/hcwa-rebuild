<?php
/**
 * Fields for each content type: one schema drives meta registration, the edit-screen meta
 * box, saving and Polylang sync. `sync => true` fields (years, status, verified flags,
 * files, relations) are entered once and copied to the translation; the rest are text that
 * differs per language.
 */

defined( 'ABSPATH' ) || exit;

/** Language-independent unit keys of the accent colors in tokens.css. */
function hcwa_accents() {
	return array(
		'green-bamboo' => __( 'Green Bamboo green', 'hcwa-core' ),
		'little-rose'  => __( 'Little Rose pink', 'hcwa-core' ),
		'cau-han'      => __( 'Cau Han blue', 'hcwa-core' ),
	);
}

function hcwa_report_types() {
	return array(
		'annual'    => __( 'Annual report', 'hcwa-core' ),
		'financial' => __( 'Financial statement', 'hcwa-core' ),
		'program'   => __( 'Program report', 'hcwa-core' ),
		'policy'    => __( 'Policy', 'hcwa-core' ),
	);
}

function hcwa_fields() {
	static $fields = null;
	if ( null !== $fields ) {
		return $fields;
	}
	$key_help = __( 'Short ID in Latin letters, the same in both languages, for example "green-bamboo". Figures, programs and donation links use it.', 'hcwa-core' );
	// Per language: the Vietnamese wording can be approved before the English one.
	$draft  = array( 'key' => 'hcwa_draft', 'type' => 'checkbox', 'label' => __( 'Wording is a draft, waiting for HCWA approval', 'hcwa-core' ), 'help' => __( 'Drafts show with a dashed outline on staging and are hidden on the live site.', 'hcwa-core' ) );
	$fields = array(
		'hcwa_center'  => array(
			$draft,
			array( 'key' => 'hcwa_key', 'type' => 'key', 'label' => __( 'Unit ID', 'hcwa-core' ), 'help' => $key_help, 'sync' => true ),
			array( 'key' => 'hcwa_short_name', 'type' => 'text', 'label' => __( 'Short name for headings, for example "Tre Xanh" / "Green Bamboo"', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_accent', 'type' => 'select', 'label' => __( 'Unit color (from the unit logo)', 'hcwa-core' ), 'options' => hcwa_accents(), 'sync' => true ),
			array( 'key' => 'hcwa_since', 'type' => 'year', 'label' => __( 'Since (year)', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_since_verified', 'type' => 'checkbox', 'label' => __( 'HCWA has verified this year', 'hcwa-core' ), 'help' => __( 'Unverified years never show on the live site.', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_serves', 'type' => 'text', 'label' => __( 'Who we serve (one line)', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_activities', 'type' => 'lines', 'label' => __( 'What we do (one item per line, 3 to 5)', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_location', 'type' => 'text', 'label' => __( 'Location (ward and city only)', 'hcwa-core' ), 'help' => __( 'A full street address only if HCWA approves it. Never for Little Rose.', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_contact', 'type' => 'lines', 'label' => __( 'Contact for this center', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_contact_approved', 'type' => 'checkbox', 'label' => __( 'HCWA approved showing this contact', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_help', 'type' => 'textarea', 'label' => __( 'How to help this center', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_unit_logo', 'type' => 'image', 'label' => __( 'Unit logo', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_gallery', 'type' => 'gallery', 'label' => __( 'Gallery (approved photos only)', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_illustration_only', 'type' => 'checkbox', 'label' => __( 'No identifiable children, ever (Little Rose)', 'hcwa-core' ), 'help' => __( 'Images marked "identifiable child" will not show on this page, even if approved.', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_news_tag', 'type' => 'text', 'label' => __( 'News tag (slug) for related news', 'hcwa-core' ) ),
		),
		'hcwa_program' => array(
			$draft,
			array( 'key' => 'hcwa_key', 'type' => 'key', 'label' => __( 'Program ID', 'hcwa-core' ), 'help' => $key_help, 'sync' => true ),
			array( 'key' => 'hcwa_short_name', 'type' => 'text', 'label' => __( 'Short name for lists', 'hcwa-core' ), 'help' => __( 'The title is the full name shown on the program page.', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_status', 'type' => 'select', 'label' => __( 'Status', 'hcwa-core' ), 'options' => array( 'active' => __( 'Active', 'hcwa-core' ), 'completed' => __( 'Completed', 'hcwa-core' ), 'planned' => __( 'Planned', 'hcwa-core' ) ), 'sync' => true ),
			array( 'key' => 'hcwa_start', 'type' => 'year', 'label' => __( 'Start year', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_end', 'type' => 'year', 'label' => __( 'End year (empty if ongoing)', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_years_verified', 'type' => 'checkbox', 'label' => __( 'HCWA has verified these years', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_centers', 'type' => 'centers', 'label' => __( 'Runs at these centers', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_partners', 'type' => 'partners', 'label' => __( 'Partners and funders', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_goals', 'type' => 'lines', 'label' => __( 'Goals (one per line)', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_activities', 'type' => 'lines', 'label' => __( 'Activities (one per line)', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_news_tag', 'type' => 'text', 'label' => __( 'News tag (slug) for related news', 'hcwa-core' ) ),
		),
		'hcwa_impact'  => array(
			array( 'key' => 'hcwa_value', 'type' => 'number', 'label' => __( 'Value (number only)', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_suffix', 'type' => 'text', 'label' => __( 'After the number, for example + or %', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_year', 'type' => 'year', 'label' => __( 'Year it refers to', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_scope', 'type' => 'scope', 'label' => __( 'Shown on', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_verified', 'type' => 'checkbox', 'label' => __( 'Verified by HCWA', 'hcwa-core' ), 'help' => __( 'Needs a value, "verified by" and a date. Unverified figures never show on the live site.', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_verified_by', 'type' => 'text', 'label' => __( 'Verified by (name, role)', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_verified_on', 'type' => 'date', 'label' => __( 'Verified on', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_source', 'type' => 'textarea', 'label' => __( 'Source note (internal, never shown)', 'hcwa-core' ), 'sync' => true ),
		),
		'hcwa_report'  => array(
			$draft,
			array( 'key' => 'hcwa_year', 'type' => 'year', 'label' => __( 'Year', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_type', 'type' => 'select', 'label' => __( 'Type', 'hcwa-core' ), 'options' => hcwa_report_types(), 'sync' => true ),
			array( 'key' => 'hcwa_doc_lang', 'type' => 'select', 'label' => __( 'Language of the PDF', 'hcwa-core' ), 'options' => array( 'vi' => 'Tiếng Việt', 'en' => 'English', 'both' => 'Tiếng Việt + English' ), 'sync' => true ),
			array( 'key' => 'hcwa_file', 'type' => 'file', 'label' => __( 'PDF file', 'hcwa-core' ), 'sync' => true ),
			array( 'key' => 'hcwa_program', 'type' => 'program', 'label' => __( 'Program (optional)', 'hcwa-core' ), 'sync' => true ),
		),
		'hcwa_partner' => array(
			array( 'key' => 'hcwa_website', 'type' => 'url', 'label' => __( 'Website', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_type', 'type' => 'select', 'label' => __( 'Type', 'hcwa-core' ), 'options' => array( 'corporate' => __( 'Corporate', 'hcwa-core' ), 'ngo' => __( 'NGO', 'hcwa-core' ), 'government' => __( 'Government', 'hcwa-core' ), 'foundation' => __( 'Foundation', 'hcwa-core' ), 'academic' => __( 'Academic', 'hcwa-core' ) ) ),
			array( 'key' => 'hcwa_years', 'type' => 'text', 'label' => __( 'Active years, for example 2019–2026', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_homepage', 'type' => 'checkbox', 'label' => __( 'Show on the homepage (max 12)', 'hcwa-core' ) ),
			array( 'key' => 'hcwa_logo_permission', 'type' => 'checkbox', 'label' => __( 'Partner agreed we can show their logo', 'hcwa-core' ), 'help' => __( 'Logos without permission never show on the live site.', 'hcwa-core' ) ),
		),
	);
	return $fields;
}

/** Field definition by post type and key, or null. */
function hcwa_field( $post_type, $key ) {
	foreach ( hcwa_fields()[ $post_type ] ?? array() as $field ) {
		if ( $field['key'] === $key ) {
			return $field;
		}
	}
	return null;
}

/** Meta keys that are copied between translations. */
function hcwa_synced_meta_keys( $post_type = null ) {
	$keys = array();
	foreach ( hcwa_fields() as $type => $fields ) {
		if ( $post_type && $type !== $post_type ) {
			continue;
		}
		foreach ( $fields as $field ) {
			if ( ! empty( $field['sync'] ) ) {
				$keys[] = $field['key'];
			}
		}
	}
	return array_values( array_unique( $keys ) );
}

/** Value of a field, cast to what templates expect. */
function hcwa_meta( $post, $key ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$field = hcwa_field( $post->post_type, $key );
	$value = get_post_meta( $post->ID, $key, true );
	$type  = $field ? $field['type'] : 'text';
	switch ( $type ) {
		case 'checkbox':
			return (bool) $value;
		case 'year':
		case 'image':
		case 'file':
			return $value ? (int) $value : 0;
		case 'number':
			return '' === $value || null === $value ? null : (float) $value;
		case 'gallery':
		case 'partners':
			return array_values( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
		case 'centers':
			return array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) $value ) ) ) );
		case 'lines':
			return hcwa_lines( $value );
		default:
			return (string) $value;
	}
}

/* ---------- registration ---------- */

add_action( 'init', 'hcwa_register_meta', 15 );

function hcwa_register_meta() {
	foreach ( hcwa_fields() as $post_type => $fields ) {
		foreach ( $fields as $field ) {
			$is_number = in_array( $field['type'], array( 'year', 'image', 'file' ), true );
			register_post_meta(
				$post_type,
				$field['key'],
				array(
					'type'              => 'checkbox' === $field['type'] ? 'boolean' : ( $is_number ? 'integer' : ( 'number' === $field['type'] ? 'number' : 'string' ) ),
					'single'            => true,
					'show_in_rest'      => 'hcwa_source' !== $field['key'], // internal note stays out of the REST API
					'sanitize_callback' => function ( $value ) use ( $field ) {
						return hcwa_sanitize_field( $field, $value );
					},
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
}

function hcwa_sanitize_field( $field, $value ) {
	switch ( $field['type'] ) {
		case 'checkbox':
			return (bool) $value;
		case 'year':
			$year = absint( $value );
			return ( $year >= 1900 && $year <= 2100 ) ? $year : 0;
		case 'image':
		case 'file':
			return absint( $value );
		case 'number':
			$value = str_replace( array( ' ', ',' ), '', (string) $value );
			return is_numeric( $value ) ? (float) $value : '';
		case 'key':
			return sanitize_title( $value );
		case 'select':
			return isset( $field['options'][ $value ] ) ? $value : '';
		case 'scope':
		case 'program':
			return 'organization' === $value ? $value : sanitize_title( $value );
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? $value : '';
		case 'url':
			return esc_url_raw( $value );
		case 'gallery':
		case 'partners':
			$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
			return implode( ',', array_filter( array_map( 'absint', $ids ) ) );
		case 'centers':
			$keys = is_array( $value ) ? $value : explode( ',', (string) $value );
			return implode( ',', array_filter( array_map( 'sanitize_title', $keys ) ) );
		case 'lines':
		case 'textarea':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/* ---------- page and post intro (the excerpt shown under the H1) ---------- */

add_action(
	'init',
	function () {
		foreach ( array( 'page', 'post' ) as $type ) {
			register_post_meta(
				$type,
				'hcwa_intro_draft',
				array(
					'type'          => 'boolean',
					'single'        => true,
					'default'       => false,
					'show_in_rest'  => true,
					'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	},
	15
);

function hcwa_intro_is_draft( $post = null ) {
	$post = get_post( $post );
	return $post && (bool) get_post_meta( $post->ID, 'hcwa_intro_draft', true );
}

/** The intro under the H1: the excerpt, unless it is a draft on production. */
function hcwa_page_intro( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! has_excerpt( $post ) || ( hcwa_intro_is_draft( $post ) && hcwa_is_production() ) ) {
		return '';
	}
	return wp_strip_all_tags( get_the_excerpt( $post ) );
}

/* ---------- edit screen ---------- */

// Data rows (figures, reports, partners) edit faster on the classic screen.
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $post_type ) {
		return in_array( $post_type, array( 'hcwa_impact', 'hcwa_report', 'hcwa_partner' ), true ) ? false : $use;
	},
	10,
	2
);

add_action(
	'add_meta_boxes',
	function ( $post_type ) {
		if ( ! isset( hcwa_fields()[ $post_type ] ) ) {
			return;
		}
		add_meta_box( 'hcwa-fields', __( 'HCWA details', 'hcwa-core' ), 'hcwa_render_meta_box', $post_type, 'normal', 'high' );
	}
);

/** Options for relation fields, built from the default-language posts. */
function hcwa_key_options( $post_type ) {
	$args = array(
		'post_type'      => $post_type,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => 100,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	);
	if ( function_exists( 'pll_default_language' ) ) {
		$args['lang'] = pll_default_language();
	}
	$options = array();
	foreach ( get_posts( $args ) as $post ) {
		$key = get_post_meta( $post->ID, 'hcwa_key', true );
		if ( $key ) {
			$options[ $key ] = get_the_title( $post );
		}
	}
	return $options;
}

function hcwa_render_meta_box( $post ) {
	wp_nonce_field( 'hcwa_save_fields', 'hcwa_fields_nonce' );
	$is_translation = function_exists( 'pll_get_post_language' ) && function_exists( 'pll_default_language' ) && pll_get_post_language( $post->ID ) && pll_get_post_language( $post->ID ) !== pll_default_language();
	echo '<div class="hcwa-fields">';
	if ( $is_translation ) {
		echo '<p class="hcwa-fields__note">' . esc_html__( 'Fields marked "same in both languages" are copied from and to the Vietnamese version when you save.', 'hcwa-core' ) . '</p>';
	}
	foreach ( hcwa_fields()[ $post->post_type ] as $field ) {
		hcwa_render_field( $post, $field );
	}
	echo '</div>';
}

function hcwa_render_field( $post, $field ) {
	$key   = $field['key'];
	$id    = 'hcwa-field-' . $key;
	$value = get_post_meta( $post->ID, $key, true );
	$help  = isset( $field['help'] ) ? $field['help'] : '';
	$sync  = ! empty( $field['sync'] ) ? ' <span class="hcwa-fields__sync">' . esc_html__( 'same in both languages', 'hcwa-core' ) . '</span>' : '';
	echo '<div class="hcwa-field hcwa-field--' . esc_attr( $field['type'] ) . '">';

	if ( 'checkbox' === $field['type'] ) {
		printf(
			'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>%5$s',
			esc_attr( $id ),
			esc_attr( $key ),
			checked( (bool) $value, true, false ),
			esc_html( $field['label'] ),
			wp_kses_post( $sync )
		);
	} else {
		printf( '<label for="%s">%s</label>%s', esc_attr( $id ), esc_html( $field['label'] ), wp_kses_post( $sync ) );
		switch ( $field['type'] ) {
			case 'lines':
			case 'textarea':
				printf( '<textarea id="%s" name="%s" rows="4">%s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( $value ) );
				break;
			case 'select':
				printf( '<select id="%s" name="%s"><option value="">%s</option>', esc_attr( $id ), esc_attr( $key ), esc_html__( 'Choose…', 'hcwa-core' ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case 'scope':
			case 'program':
				$groups = array();
				if ( 'scope' === $field['type'] ) {
					$groups[ __( 'Whole organization', 'hcwa-core' ) ] = array( 'organization' => __( 'Organization (homepage, Impact page)', 'hcwa-core' ) );
					$groups[ __( 'Centers', 'hcwa-core' ) ]            = hcwa_key_options( 'hcwa_center' );
				}
				$groups[ __( 'Programs', 'hcwa-core' ) ] = hcwa_key_options( 'hcwa_program' );
				printf( '<select id="%s" name="%s"><option value="">%s</option>', esc_attr( $id ), esc_attr( $key ), esc_html__( 'Choose…', 'hcwa-core' ) );
				foreach ( $groups as $group => $options ) {
					echo '<optgroup label="' . esc_attr( $group ) . '">';
					foreach ( $options as $option => $label ) {
						printf( '<option value="%s" %s>%s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
					}
					echo '</optgroup>';
				}
				echo '</select>';
				break;
			case 'centers':
			case 'partners':
				$selected = array_filter( explode( ',', (string) $value ) );
				$options  = 'centers' === $field['type'] ? hcwa_key_options( 'hcwa_center' ) : wp_list_pluck( get_posts( array( 'post_type' => 'hcwa_partner', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) ), 'post_title', 'ID' );
				echo '<fieldset id="' . esc_attr( $id ) . '">';
				if ( ! $options ) {
					echo '<p class="description">' . esc_html__( 'None yet.', 'hcwa-core' ) . '</p>';
				}
				foreach ( $options as $option => $label ) {
					printf( '<label><input type="checkbox" name="%s[]" value="%s" %s> %s</label>', esc_attr( $key ), esc_attr( $option ), checked( in_array( (string) $option, $selected, true ), true, false ), esc_html( $label ) );
				}
				echo '</fieldset>';
				break;
			case 'image':
			case 'file':
			case 'gallery':
				$multiple = 'gallery' === $field['type'];
				printf(
					'<div class="hcwa-media" data-multiple="%s" data-library="%s"><input type="hidden" id="%s" name="%s" value="%s"><div class="hcwa-media__preview">%s</div><button type="button" class="button hcwa-media__pick">%s</button> <button type="button" class="button-link hcwa-media__clear">%s</button></div>',
					$multiple ? '1' : '0',
					'file' === $field['type'] ? 'application/pdf' : 'image',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( $value ),
					hcwa_media_preview( $value ), // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts
					esc_html( $multiple ? __( 'Choose photos', 'hcwa-core' ) : __( 'Choose file', 'hcwa-core' ) ),
					esc_html__( 'Remove', 'hcwa-core' )
				);
				break;
			default:
				$types = array( 'year' => 'number', 'number' => 'text', 'date' => 'date', 'url' => 'url' );
				$extra = 'year' === $field['type'] ? ' min="1900" max="2100" step="1"' : ( 'number' === $field['type'] ? ' inputmode="decimal"' : '' );
				printf(
					'<input type="%s" id="%s" name="%s" value="%s"%s%s>',
					esc_attr( isset( $types[ $field['type'] ] ) ? $types[ $field['type'] ] : 'text' ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( 0 === $value || '0' === $value ? '' : $value ),
					$extra, // phpcs:ignore WordPress.Security.EscapeOutput -- fixed strings
					'key' === $field['type'] ? ' pattern="[a-z0-9-]+"' : ''
				);
		}
	}
	if ( $help ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
	echo '</div>';
}

/** Thumbnails (or file names) for the media fields; also used by admin.js after picking. */
function hcwa_media_preview( $ids ) {
	$out = '';
	foreach ( array_filter( array_map( 'absint', explode( ',', (string) $ids ) ) ) as $id ) {
		$out .= wp_attachment_is_image( $id ) ? wp_get_attachment_image( $id, 'thumbnail' ) : '<span>' . esc_html( basename( (string) get_attached_file( $id ) ) ) . '</span>';
	}
	return $out;
}

add_action( 'save_post', 'hcwa_save_fields', 10, 2 );

function hcwa_save_fields( $post_id, $post ) {
	if ( ! isset( $_POST['hcwa_fields_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hcwa_fields_nonce'] ), 'hcwa_save_fields' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( hcwa_fields()[ $post->post_type ] ?? array() as $field ) {
		$raw = isset( $_POST[ $field['key'] ] ) ? wp_unslash( $_POST[ $field['key'] ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field type
		$value = hcwa_sanitize_field( $field, $raw );
		if ( '' === $value || false === $value || 0 === $value ) {
			delete_post_meta( $post_id, $field['key'] );
		} else {
			update_post_meta( $post_id, $field['key'], $value );
		}
	}
	if ( 'hcwa_impact' === $post->post_type ) {
		hcwa_enforce_verified_rule( $post_id );
	}
}

/**
 * A figure is only "verified" with a value, a name and a date (04-SITEMAP). A tick without
 * them is removed again and the editor is told why.
 */
function hcwa_enforce_verified_rule( $post_id ) {
	if ( ! get_post_meta( $post_id, 'hcwa_verified', true ) ) {
		return;
	}
	$missing = array();
	if ( '' === get_post_meta( $post_id, 'hcwa_value', true ) ) {
		$missing[] = __( 'value', 'hcwa-core' );
	}
	if ( ! get_post_meta( $post_id, 'hcwa_verified_by', true ) ) {
		$missing[] = __( 'verified by', 'hcwa-core' );
	}
	if ( ! get_post_meta( $post_id, 'hcwa_verified_on', true ) ) {
		$missing[] = __( 'verified on', 'hcwa-core' );
	}
	if ( $missing ) {
		delete_post_meta( $post_id, 'hcwa_verified' );
		set_transient( 'hcwa_verify_notice_' . get_current_user_id(), $missing, 60 );
	}
}

add_action(
	'admin_notices',
	function () {
		$missing = get_transient( 'hcwa_verify_notice_' . get_current_user_id() );
		if ( ! $missing ) {
			return;
		}
		delete_transient( 'hcwa_verify_notice_' . get_current_user_id() );
		/* translators: %s: list of missing fields */
		$text = sprintf( __( 'The figure was saved as NOT verified. Fill in: %s.', 'hcwa-core' ), implode( ', ', $missing ) );
		echo '<div class="notice notice-error"><p>' . esc_html( $text ) . '</p></div>';
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ( ! isset( hcwa_fields()[ $screen->post_type ] ) && 'settings_page_hcwa' !== $hook ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'hcwa-admin', HCWA_CORE_URL . 'assets/admin.js', array(), HCWA_CORE_VERSION, true );
		wp_enqueue_style( 'hcwa-admin', HCWA_CORE_URL . 'assets/admin.css', array(), HCWA_CORE_VERSION );
	}
);

/* ---------- admin list columns: status at a glance ---------- */

add_filter(
	'manage_hcwa_impact_posts_columns',
	function ( $columns ) {
		return array_slice( $columns, 0, 2, true ) + array(
			'hcwa_value'    => __( 'Value', 'hcwa-core' ),
			'hcwa_scope'    => __( 'Shown on', 'hcwa-core' ),
			'hcwa_verified' => __( 'Verified', 'hcwa-core' ),
		) + $columns;
	}
);

add_action(
	'manage_hcwa_impact_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'hcwa_value' === $column ) {
			$value = get_post_meta( $post_id, 'hcwa_value', true );
			echo esc_html( '' === $value ? '—' : hcwa_number( $value, 'en' ) . get_post_meta( $post_id, 'hcwa_suffix', true ) );
		} elseif ( 'hcwa_scope' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, 'hcwa_scope', true ) );
		} elseif ( 'hcwa_verified' === $column ) {
			echo get_post_meta( $post_id, 'hcwa_verified', true )
				? esc_html( get_post_meta( $post_id, 'hcwa_verified_on', true ) . ' · ' . get_post_meta( $post_id, 'hcwa_verified_by', true ) )
				: '<strong style="color:#b42318">' . esc_html__( 'Not verified (hidden on the live site)', 'hcwa-core' ) . '</strong>';
		}
	},
	10,
	2
);
