<?php
/**
 * Custom fields for the five content types, the two media approval flags and the
 * safeguarding flag on news posts: one definition table drives register_post_meta(), the
 * meta boxes and saving.
 *
 * "Shared" fields are the same in both languages and are copied between translations
 * (polylang.php). IDs in shared fields point at the Vietnamese original.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions per post type.
 *
 * Each field: type (register_post_meta type), input (form control), label, help,
 * sync (shared by translations), default, options (select), post_type (post pickers),
 * media (image | application/pdf), box (false = no meta box), auth (edit | approve),
 * protective (a safety flag that is never copied between translations and counts when
 * any translation has it: hcwa_meta_flag_any()).
 *
 * @return array<string, array<string, array>>
 */
function hcwa_meta_fields(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$fields = hcwa_meta_fields_build();

	// Labels are translated, so cache only once translations can load.
	if ( did_action( 'init' ) ) {
		$cache = $fields;
	}

	return $fields;
}

/**
 * Builds the table for hcwa_meta_fields().
 *
 * @return array<string, array<string, array>>
 */
function hcwa_meta_fields_build(): array {
	$f = static function ( string $type, string $input, string $label, array $extra = array() ): array {
		return array_merge(
			array(
				'type'       => $type,
				'input'      => $input,
				'label'      => $label,
				'help'       => '',
				'sync'       => false,
				'box'        => true,
				'auth'       => 'edit',
				'protective' => false,
			),
			$extra
		);
	};
	$protective = static fn( string $label, array $extra = array() ) => $f( 'boolean', 'checkbox', $label, array_merge( array( 'default' => false, 'protective' => true ), $extra ) );

	$year       = static fn( string $label, array $extra = array() ) => $f( 'integer', 'year', $label, array_merge( array( 'default' => 0, 'sync' => true ), $extra ) );
	$news_tag   = $f( 'string', 'term_slug', __( 'News tag', 'hcwa' ), array( 'default' => '', 'sync' => true, 'help' => __( 'Slug of the post tag whose news appears on this page, for example tre-xanh. Use the Vietnamese tag.', 'hcwa' ) ) );
	$summary    = static fn( string $help ) => $f( 'string', 'textarea', __( 'Short summary', 'hcwa' ), array( 'default' => '', 'help' => $help, 'maxlength' => 160 ) );
	$per_lang   = static fn( string $input, string $label, string $help = '' ) => $f( 'string', $input, $label, array( 'default' => '', 'help' => $help ) );
	$shared_txt = static fn( string $input, string $label, string $help = '' ) => $f( 'string', $input, $label, array( 'default' => '', 'help' => $help, 'sync' => true ) );
	$shared_yes = static fn( string $label, string $help = '' ) => $f( 'boolean', 'checkbox', $label, array( 'default' => false, 'help' => $help, 'sync' => true ) );

	return array(
		'hcwa_center'  => array(
			'hcwa_summary'                  => $summary( __( 'One or two sentences, at most 160 characters. Shown on center cards.', 'hcwa' ) ),
			'hcwa_who_we_serve'             => $per_lang( 'textarea', __( 'Who we serve', 'hcwa' ) ),
			'hcwa_what_we_do'               => $per_lang( 'lines', __( 'What we do', 'hcwa' ), __( 'One activity per line.', 'hcwa' ) ),
			'hcwa_location'                 => $per_lang( 'text', __( 'Location', 'hcwa' ), __( 'Ward and city only. Give the full address only if HCWA has approved it.', 'hcwa' ) ),
			'hcwa_help_text'                => $per_lang( 'textarea', __( 'How to help this center', 'hcwa' ), __( 'Specific needs, shown in the "how to help" section.', 'hcwa' ) ),
			'hcwa_contact_name'             => $per_lang( 'text', __( 'Contact person', 'hcwa' ), __( 'Shown only when "Contact details approved" is ticked.', 'hcwa' ) ),
			'hcwa_since_year'               => $year( __( 'Since (year)', 'hcwa' ), array( 'help' => __( 'Leave empty until HCWA confirms the year.', 'hcwa' ) ) ),
			'hcwa_accent'                   => $f(
				'string',
				'select',
				__( 'Accent color', 'hcwa' ),
				array(
					'default' => 'blue',
					'sync'    => true,
					'options' => array(
						'green-bamboo' => __( 'Green Bamboo green', 'hcwa' ),
						'little-rose'  => __( 'Little Rose pink', 'hcwa' ),
						'cau-han'      => __( 'Cau Han blue', 'hcwa' ),
						'blue'         => __( 'HCWA blue', 'hcwa' ),
					),
					'help'    => __( 'Taken from the unit logo. Used for the color band and dot only.', 'hcwa' ),
				)
			),
			'hcwa_logo_id'                  => $f( 'integer', 'media', __( 'Unit logo', 'hcwa' ), array( 'default' => 0, 'sync' => true, 'media' => 'image' ) ),
			'hcwa_gallery_ids'              => $f( 'array', 'media_multi', __( 'Gallery', 'hcwa' ), array( 'default' => array(), 'sync' => true, 'media' => 'image', 'help' => __( 'Only photos marked "Approved for web" appear on the site.', 'hcwa' ) ) ),
			'hcwa_contact_approved'         => $shared_yes( __( 'Contact details approved', 'hcwa' ), __( 'Tick only when HCWA has approved showing the contact person, phone and email.', 'hcwa' ) ),
			'hcwa_phone'                    => $shared_txt( 'tel', __( 'Phone', 'hcwa' ) ),
			'hcwa_email'                    => $shared_txt( 'email', __( 'Email', 'hcwa' ) ),
			'hcwa_news_tag'                 => $news_tag,
			'hcwa_no_identifiable_children' => $protective( __( 'No identifiable children', 'hcwa' ), array( 'help' => __( 'Tick for Little Rose and any unit whose children must never be identifiable. Photos marked "Identifiable child" are then refused on this center, its programs and its tagged news. Ticked in either language, it applies to both; to clear it, untick it in both.', 'hcwa' ) ) ),
		),
		'hcwa_program' => array(
			'hcwa_summary'     => $summary( __( 'One or two sentences, at most 160 characters. Shown in program lists.', 'hcwa' ) ),
			'hcwa_goals'       => $per_lang( 'lines', __( 'Goals', 'hcwa' ), __( 'One goal per line.', 'hcwa' ) ),
			'hcwa_activities'  => $per_lang( 'lines', __( 'Activities', 'hcwa' ), __( 'One activity per line.', 'hcwa' ) ),
			'hcwa_status'      => $f(
				'string',
				'select',
				__( 'Status', 'hcwa' ),
				array(
					'default' => 'active',
					'sync'    => true,
					'options' => array(
						'active'    => __( 'Active', 'hcwa' ),
						'completed' => __( 'Completed', 'hcwa' ),
						'planned'   => __( 'Planned', 'hcwa' ),
					),
				)
			),
			'hcwa_start_year'  => $year( __( 'Start year', 'hcwa' ) ),
			'hcwa_end_year'    => $year( __( 'End year', 'hcwa' ), array( 'help' => __( 'Leave empty while the program is running.', 'hcwa' ) ) ),
			'hcwa_partner_ids' => $f( 'array', 'post_multi', __( 'Partners and funders', 'hcwa' ), array( 'default' => array(), 'sync' => true, 'post_type' => array( 'hcwa_partner' ) ) ),
			'hcwa_center_id'   => $f( 'integer', 'post_select', __( 'Center', 'hcwa' ), array( 'default' => 0, 'sync' => true, 'post_type' => array( 'hcwa_center' ), 'help' => __( 'Optional. The center that runs this program.', 'hcwa' ) ) ),
			'hcwa_news_tag'    => $news_tag,
		),
		'hcwa_impact'  => array(
			'hcwa_value'         => $f( 'number', 'number', __( 'Value', 'hcwa' ), array( 'sync' => true, 'help' => __( 'The number only, for example 300000. Leave empty until HCWA confirms it.', 'hcwa' ) ) ),
			'hcwa_suffix'        => $shared_txt( 'text', __( 'Suffix', 'hcwa' ), __( 'Shown right after the number, for example + or %.', 'hcwa' ) ),
			'hcwa_year'          => $year( __( 'Year the figure refers to', 'hcwa' ) ),
			'hcwa_verified'      => $shared_yes( __( 'Verified by HCWA', 'hcwa' ), __( 'Unverified figures never appear on the live site. A figure counts as verified only with a name and a date below.', 'hcwa' ) ),
			'hcwa_verified_by'   => $shared_txt( 'text', __( 'Verified by', 'hcwa' ) ),
			'hcwa_verified_date' => $f( 'string', 'date', __( 'Verified on', 'hcwa' ), array( 'default' => '', 'sync' => true ) ),
			'hcwa_order'         => $f( 'integer', 'integer', __( 'Display order', 'hcwa' ), array( 'default' => 0, 'sync' => true, 'help' => __( 'Lower numbers come first.', 'hcwa' ) ) ),
			'hcwa_scope_type'    => $f(
				'string',
				'select',
				__( 'Shown on', 'hcwa' ),
				array(
					'default' => 'organization',
					'sync'    => true,
					'options' => array(
						'organization' => __( 'Whole organization (home and impact pages)', 'hcwa' ),
						'center'       => __( 'One center', 'hcwa' ),
						'program'      => __( 'One program', 'hcwa' ),
					),
				)
			),
			'hcwa_scope_id'      => $f( 'integer', 'post_select', __( 'Center or program', 'hcwa' ), array( 'default' => 0, 'sync' => true, 'post_type' => array( 'hcwa_center', 'hcwa_program' ), 'help' => __( 'Only for "One center" or "One program".', 'hcwa' ) ) ),
			'hcwa_source_note'   => $per_lang( 'textarea', __( 'Source note (internal)', 'hcwa' ), __( 'Where the number comes from. Never shown on the site.', 'hcwa' ) ),
		),
		'hcwa_report'  => array(
			'hcwa_summary'      => $summary( __( 'Two lines at most. Shown in the report list.', 'hcwa' ) ),
			'hcwa_year'         => $year( __( 'Year', 'hcwa' ) ),
			'hcwa_report_type'  => $f(
				'string',
				'select',
				__( 'Report type', 'hcwa' ),
				array(
					'default' => 'annual',
					'sync'    => true,
					'options' => array(
						'annual'    => __( 'Annual report', 'hcwa' ),
						'financial' => __( 'Financial statement', 'hcwa' ),
						'program'   => __( 'Program report', 'hcwa' ),
						'policy'    => __( 'Policy', 'hcwa' ),
					),
				)
			),
			'hcwa_doc_language' => $f(
				'string',
				'select',
				__( 'Document language', 'hcwa' ),
				array(
					'default' => 'vi',
					'sync'    => true,
					'options' => array(
						'vi'   => __( 'Vietnamese', 'hcwa' ),
						'en'   => __( 'English', 'hcwa' ),
						'both' => __( 'Vietnamese and English', 'hcwa' ),
					),
					'help'    => __( 'Shown as a badge on every link to the file.', 'hcwa' ),
				)
			),
			'hcwa_file_id'      => $f( 'integer', 'media', __( 'PDF file', 'hcwa' ), array( 'default' => 0, 'sync' => true, 'media' => 'application/pdf' ) ),
		),
		'hcwa_partner' => array(
			'hcwa_website'         => $shared_txt( 'url', __( 'Website', 'hcwa' ) ),
			'hcwa_partner_type'    => $f(
				'string',
				'select',
				__( 'Partner type', 'hcwa' ),
				array(
					'default' => '',
					'sync'    => true,
					'options' => array(
						''           => __( 'Not set', 'hcwa' ),
						'corporate'  => __( 'Company', 'hcwa' ),
						'ngo'        => __( 'NGO', 'hcwa' ),
						'government' => __( 'Government', 'hcwa' ),
						'foundation' => __( 'Foundation', 'hcwa' ),
						'academic'   => __( 'School or university', 'hcwa' ),
					),
				)
			),
			'hcwa_active_years'    => $shared_txt( 'text', __( 'Active years', 'hcwa' ), __( 'For example 2019 to 2024.', 'hcwa' ) ),
			'hcwa_show_on_home'    => $shared_yes( __( 'Show on the home page', 'hcwa' ) ),
			'hcwa_logo_permission' => $shared_yes( __( 'Permission to show the logo', 'hcwa' ), __( 'Tick only when the partner has agreed in writing. Without it the logo never shows on the live site.', 'hcwa' ) ),
		),
		'post'         => array(
			'hcwa_no_identifiable_children' => $protective( __( 'No identifiable children', 'hcwa' ), array( 'auth' => 'approve', 'help' => __( 'Tick for news about Little Rose residents or any child who must never be identifiable. Photos marked "Identifiable child" are then refused in this post, in both languages. Only editors can change it.', 'hcwa' ) ) ),
		),
		'attachment'   => array(
			'hcwa_approved_for_web'   => $f( 'boolean', 'checkbox', __( 'Approved for web', 'hcwa' ), array( 'default' => false, 'sync' => true, 'box' => false, 'auth' => 'approve' ) ),
			'hcwa_identifiable_child' => $protective( __( 'Identifiable child', 'hcwa' ), array( 'box' => false, 'auth' => 'approve' ) ),
		),
	);
}

/**
 * Definition of one field, or null.
 *
 * @param string|false $post_type Post type.
 * @param string       $key       Meta key.
 */
function hcwa_meta_spec( $post_type, string $key ): ?array {
	$fields = hcwa_meta_fields();
	return ( is_string( $post_type ) && isset( $fields[ $post_type ][ $key ] ) ) ? $fields[ $post_type ][ $key ] : null;
}

/**
 * Meta keys shared by both languages (copied between translations).
 *
 * @return string[]
 */
function hcwa_meta_sync_keys(): array {
	$keys = array();
	foreach ( hcwa_meta_fields() as $fields ) {
		foreach ( $fields as $key => $spec ) {
			if ( $spec['sync'] ) {
				$keys[] = $key;
			}
		}
	}
	return array_values( array_unique( $keys ) );
}

/**
 * Meta keys never copied between translations: text per language, and the protective
 * flags (read across translations instead, hcwa_meta_flag_any()).
 *
 * @return string[]
 */
function hcwa_meta_per_language_keys(): array {
	$keys = array();
	foreach ( hcwa_meta_fields() as $fields ) {
		foreach ( $fields as $key => $spec ) {
			if ( ! $spec['sync'] ) {
				$keys[] = $key;
			}
		}
	}
	return array_values( array_diff( array_unique( $keys ), hcwa_meta_sync_keys() ) );
}

/**
 * Registers every field with register_post_meta().
 */
function hcwa_register_meta(): void {
	foreach ( hcwa_meta_fields() as $post_type => $fields ) {
		foreach ( $fields as $key => $spec ) {
			$args = array(
				'type'              => $spec['type'],
				'description'       => $spec['label'],
				'single'            => true,
				'sanitize_callback' => 'hcwa_sanitize_meta',
				'auth_callback'     => 'approve' === $spec['auth'] ? 'hcwa_meta_auth_approver' : 'hcwa_meta_auth',
				'show_in_rest'      => hcwa_meta_rest_schema( $spec ),
			);
			// No default for nullable numbers (impact value): "not set" must stay distinguishable from 0.
			if ( array_key_exists( 'default', $spec ) ) {
				$args['default'] = $spec['default'];
			}
			register_post_meta( $post_type, $key, $args );
		}
	}
}
add_action( 'init', 'hcwa_register_meta', 10 );

/**
 * show_in_rest value for a field: arrays need an item schema, selects an enum.
 *
 * @param array $spec Field definition.
 * @return bool|array
 */
function hcwa_meta_rest_schema( array $spec ) {
	if ( 'array' === $spec['type'] ) {
		return array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
		);
	}
	if ( 'select' === $spec['input'] ) {
		return array(
			'schema' => array(
				'type' => 'string',
				'enum' => array_map( 'strval', array_keys( $spec['options'] ) ),
			),
		);
	}
	if ( 'date' === $spec['input'] ) {
		return array(
			'schema' => array(
				'type'    => 'string',
				'pattern' => '^(\d{4}-\d{2}-\d{2})?$',
			),
		);
	}
	return true;
}

/**
 * auth_callback: anyone who may edit the post may edit its fields.
 *
 * @param bool   $allowed   Default permission.
 * @param string $meta_key  Meta key.
 * @param int    $object_id Post ID.
 * @param int    $user_id   User ID.
 */
function hcwa_meta_auth( $allowed, $meta_key, $object_id, $user_id = 0 ): bool {
	return user_can( (int) $user_id, 'edit_post', (int) $object_id );
}

/**
 * auth_callback for the photo approval flags: editors and administrators only, so an
 * author cannot approve their own upload of a child's photo.
 *
 * @param bool   $allowed   Default permission.
 * @param string $meta_key  Meta key.
 * @param int    $object_id Attachment ID.
 * @param int    $user_id   User ID.
 */
function hcwa_meta_auth_approver( $allowed, $meta_key, $object_id, $user_id = 0 ): bool {
	return user_can( (int) $user_id, 'edit_post', (int) $object_id ) && user_can( (int) $user_id, 'edit_others_posts' );
}

/**
 * sanitize_callback for every registered field.
 *
 * @param mixed  $value          Value being saved.
 * @param string $meta_key       Meta key.
 * @param string $object_type    'post'.
 * @param string $object_subtype Post type.
 * @return mixed
 */
function hcwa_sanitize_meta( $value, $meta_key, $object_type = 'post', $object_subtype = '' ) {
	$spec = hcwa_meta_spec( $object_subtype, (string) $meta_key );
	return $spec ? hcwa_sanitize_meta_value( $spec, $value ) : $value;
}

/**
 * Cleans one value according to its field definition.
 *
 * @param array $spec  Field definition.
 * @param mixed $value Raw value.
 * @return mixed
 */
function hcwa_sanitize_meta_value( array $spec, $value ) {
	switch ( $spec['input'] ) {
		case 'checkbox':
			return in_array( $value, array( true, 1, '1', 'true', 'on', 'yes' ), true );

		case 'year':
			$year = (int) $value;
			return ( $year >= 1900 && $year <= 2100 ) ? $year : 0;

		case 'integer':
			return (int) $value;

		case 'number':
			return is_numeric( $value ) ? (float) $value : '';

		case 'date':
			$value = is_string( $value ) ? trim( $value ) : '';
			return hcwa_is_ymd( $value ) ? $value : '';

		case 'select':
			$value = is_scalar( $value ) ? (string) $value : '';
			return array_key_exists( $value, $spec['options'] ) ? $value : (string) ( $spec['default'] ?? '' );

		case 'url':
			return esc_url_raw( is_string( $value ) ? trim( $value ) : '' );

		case 'email':
			return sanitize_email( is_string( $value ) ? $value : '' );

		case 'tel':
			return trim( (string) preg_replace( '/[^0-9+(). \-]/', '', is_scalar( $value ) ? (string) $value : '' ) );

		case 'term_slug':
			return sanitize_title( is_string( $value ) ? $value : '' );

		case 'lines':
			$lines = preg_split( '/\R/u', sanitize_textarea_field( is_string( $value ) ? $value : '' ) );
			return implode( "\n", array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );

		case 'textarea':
			return sanitize_textarea_field( is_string( $value ) ? $value : '' );

		case 'media':
			$id = absint( $value );
			return ( $id && 'attachment' === get_post_type( $id ) ) ? $id : 0;

		case 'media_multi':
			return array_values( array_filter( hcwa_id_list( $value ), static fn( $id ) => 'attachment' === get_post_type( $id ) ) );

		case 'post_select':
			$id = absint( $value );
			return ( $id && in_array( get_post_type( $id ), $spec['post_type'], true ) ) ? $id : 0;

		case 'post_multi':
			return array_values( array_filter( hcwa_id_list( $value ), static fn( $id ) => in_array( get_post_type( $id ), $spec['post_type'], true ) ) );

		default:
			return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
	}
}

/**
 * True for a valid 'Y-m-d' date.
 *
 * @param string $value Date string.
 */
function hcwa_is_ymd( string $value ): bool {
	return (bool) preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/**
 * Positive, unique integers from an array or a comma-separated string.
 *
 * @param mixed $value IDs.
 * @return int[]
 */
function hcwa_id_list( $value ): array {
	if ( is_string( $value ) ) {
		$value = explode( ',', $value );
	}
	if ( ! is_array( $value ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
}

/**
 * A field value with its proper PHP type: bool, int, ?float (impact value), int[] or string.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return mixed
 */
function hcwa_meta( int $post_id, string $key ) {
	$raw  = get_post_meta( $post_id, $key, true );
	$spec = hcwa_meta_spec( get_post_type( $post_id ), $key );

	return $spec ? hcwa_meta_cast( $spec, $raw ) : $raw;
}

/**
 * Casts a stored value to the field's PHP type.
 *
 * @param array $spec Field definition.
 * @param mixed $raw  Stored value.
 * @return mixed
 */
function hcwa_meta_cast( array $spec, $raw ) {
	switch ( $spec['type'] ) {
		case 'boolean':
			return in_array( $raw, array( true, 1, '1', 'true' ), true );
		case 'integer':
			return (int) $raw;
		case 'number':
			return is_numeric( $raw ) ? (float) $raw : null;
		case 'array':
			return hcwa_id_list( $raw );
		default:
			return is_scalar( $raw ) ? (string) $raw : '';
	}
}

/**
 * A "one item per line" field as a list.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return string[]
 */
function hcwa_meta_lines( int $post_id, string $key ): array {
	$text = (string) get_post_meta( $post_id, $key, true );
	return array_values( array_filter( array_map( 'trim', (array) preg_split( '/\R/u', $text ) ), 'strlen' ) );
}

/* -------------------------------------------------------------------------
 * Meta boxes
 * ---------------------------------------------------------------------- */

/**
 * Adds one "details" box per content type.
 */
function hcwa_add_meta_boxes(): void {
	$titles = array(
		'hcwa_center'  => __( 'Center details', 'hcwa' ),
		'hcwa_program' => __( 'Program details', 'hcwa' ),
		'hcwa_impact'  => __( 'Figure', 'hcwa' ),
		'hcwa_report'  => __( 'Report details', 'hcwa' ),
		'hcwa_partner' => __( 'Partner details', 'hcwa' ),
		'post'         => __( 'Child safeguarding', 'hcwa' ),
	);
	foreach ( $titles as $post_type => $title ) {
		add_meta_box( 'hcwa_details', $title, 'hcwa_render_meta_box', $post_type, 'post' === $post_type ? 'side' : 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'hcwa_add_meta_boxes' );

/**
 * Our keys are edited only in the details box and the media approval fields. Marking them
 * protected keeps them out of the core "Custom Fields" panel, where an edit would be
 * silently overwritten by the details box and a delete (an immediate AJAX call) could drop
 * "No identifiable children" on Little Rose. REST still works: the registered
 * auth_callback decides edit_post_meta for registered keys (wp-includes/capabilities.php,
 * map_meta_cap() 'edit_post_meta'), and Polylang's sync adds our shared keys back itself
 * (hcwa_pll_copy_post_metas()).
 *
 * @param bool   $protected Whether the key is protected.
 * @param string $meta_key  Meta key.
 * @param string $meta_type Object type.
 */
function hcwa_protect_meta( $protected, $meta_key, $meta_type = '' ): bool {
	return (bool) $protected || ( 'post' === $meta_type && 0 === strpos( (string) $meta_key, 'hcwa_' ) );
}
add_filter( 'is_protected_meta', 'hcwa_protect_meta', 10, 3 );

/**
 * Prints the details box.
 *
 * @param WP_Post $post Post being edited.
 */
function hcwa_render_meta_box( WP_Post $post ): void {
	$fields = array_filter( hcwa_meta_fields()[ $post->post_type ] ?? array(), static fn( $spec ) => $spec['box'] );
	if ( ! $fields ) {
		return;
	}

	wp_nonce_field( 'hcwa_meta_' . $post->ID, 'hcwa_meta_nonce' );

	if ( 'hcwa_impact' === $post->post_type ) {
		hcwa_render_impact_status( $post );
	}
	if ( 'hcwa_center' === $post->post_type ) {
		hcwa_render_center_protection_status( $post );
	}

	// Shared fields are edited on the Vietnamese original only (see hcwa_pll_seed_shared_meta()).
	$original = hcwa_original_id( $post->ID );

	$groups = array(
		'lang' => array(
			'title' => __( 'In this language', 'hcwa' ),
			'intro' => __( 'Write these separately in Vietnamese and in English.', 'hcwa' ),
		),
		'sync' => array(
			'title' => __( 'Shared by both languages', 'hcwa' ),
			'intro' => $original === $post->ID
				? __( 'Enter these once. They are copied to the translation when you save.', 'hcwa' )
				: __( 'These come from the Vietnamese version and are copied here when you save. Change them there.', 'hcwa' ),
		),
	);
	if ( ! array_filter( $fields, static fn( $spec ) => $spec['sync'] ) ) {
		// Only protective flags (news posts): they are not copied, they count in both languages.
		$groups['sync']['intro'] = __( 'Applies to every language version.', 'hcwa' );
	}

	echo '<div class="hcwa-fields">';
	foreach ( $groups as $group => $text ) {
		$in_group = array_filter( $fields, static fn( $spec ) => ( 'sync' === $group ) === ( $spec['sync'] || $spec['protective'] ) );
		if ( ! $in_group ) {
			continue;
		}
		echo '<h3 class="hcwa-fields__group">' . esc_html( $text['title'] ) . '</h3>';
		echo '<p class="hcwa-fields__intro">' . esc_html( $text['intro'] );
		if ( 'sync' === $group && $original !== $post->ID && current_user_can( 'edit_post', $original ) ) {
			printf( ' <a href="%1$s">%2$s</a>', esc_url( (string) get_edit_post_link( $original ) ), esc_html__( 'Edit the Vietnamese version', 'hcwa' ) );
		}
		echo '</p>';
		foreach ( $in_group as $key => $spec ) {
			if ( $spec['sync'] && $original !== $post->ID ) {
				hcwa_render_meta_field_readonly( $original, $key, $spec );
			} elseif ( ! hcwa_meta_user_can_edit( $spec ) ) {
				hcwa_render_meta_flag_readonly( $post, $key, $spec );
			} else {
				hcwa_render_meta_field( $post, $key, $spec );
			}
		}
	}
	echo '</div>';
}

/**
 * Whether the current user may change a field in the details box: editors only for fields
 * with auth 'approve' (the same rule as hcwa_meta_auth_approver() for REST).
 *
 * @param array $spec Field definition.
 */
function hcwa_meta_user_can_edit( array $spec ): bool {
	return 'approve' !== $spec['auth'] || current_user_can( 'edit_others_posts' );
}

/**
 * A flag only editors may change, shown to other users: its state (in any language for a
 * protective flag) and who can change it.
 *
 * @param WP_Post $post Post being edited.
 * @param string  $key  Meta key.
 * @param array   $spec Field definition.
 */
function hcwa_render_meta_flag_readonly( WP_Post $post, string $key, array $spec ): void {
	$value = $spec['protective'] ? hcwa_meta_flag_any( $post->ID, $key ) : (bool) hcwa_meta( $post->ID, $key );
	printf(
		'<div class="hcwa-field hcwa-field--readonly"><span class="hcwa-field__label">%1$s</span> <span class="hcwa-field__value">%2$s</span> <span class="description">%3$s</span></div>',
		esc_html( $spec['label'] ),
		esc_html( $value ? __( 'Yes', 'hcwa' ) : __( 'No', 'hcwa' ) ),
		esc_html__( 'Only editors can change this.', 'hcwa' )
	);
}

/**
 * Warning on a center marked "No identifiable children" without a news tag: its news posts
 * are recognised only by that tag, so none of them would be protected.
 *
 * @param WP_Post $post Center.
 */
function hcwa_render_center_protection_status( WP_Post $post ): void {
	if ( ! hcwa_center_news_unprotected( $post->ID ) ) {
		return;
	}
	printf(
		'<div class="notice inline notice-warning"><p>%s</p></div>',
		esc_html__( '"No identifiable children" is ticked but "News tag" is empty, so this center\'s news posts are not checked for identifiable children. Fill in the news tag (the Vietnamese tag slug).', 'hcwa' )
	);
}

/**
 * Whether a center is marked "No identifiable children" (in any language) but has no news
 * tag, so its news cannot be recognised as a protected context.
 *
 * @param int $center_id Center ID.
 */
function hcwa_center_news_unprotected( int $center_id ): bool {
	return hcwa_meta_flag_any( $center_id, 'hcwa_no_identifiable_children' )
		&& '' === trim( (string) get_post_meta( hcwa_original_id( $center_id ), 'hcwa_news_tag', true ) );
}

/**
 * A shared field shown read-only on a translation: the original's value.
 *
 * @param int    $original_id The Vietnamese original.
 * @param string $key         Meta key.
 * @param array  $spec        Field definition.
 */
function hcwa_render_meta_field_readonly( int $original_id, string $key, array $spec ): void {
	$value = hcwa_meta( $original_id, $key );

	switch ( $spec['input'] ) {
		case 'checkbox':
			$html = esc_html( $value ? __( 'Yes', 'hcwa' ) : __( 'No', 'hcwa' ) );
			break;
		case 'select':
			$html = esc_html( (string) ( $spec['options'][ (string) $value ] ?? $value ) );
			break;
		case 'post_select':
		case 'post_multi':
			$titles = array_map( static fn( $id ) => get_the_title( (int) $id ), array_filter( (array) $value ) );
			$html   = esc_html( implode( ', ', $titles ) );
			break;
		case 'media':
		case 'media_multi':
			$html = hcwa_media_preview_html( array_filter( array_map( 'intval', (array) $value ) ) ); // Escaped inside.
			break;
		default:
			$html = esc_html( null === $value || ( 'year' === $spec['input'] && 0 === (int) $value ) ? '' : (string) $value );
	}

	printf(
		'<div class="hcwa-field hcwa-field--readonly"><span class="hcwa-field__label">%1$s</span> <span class="hcwa-field__value">%2$s</span></div>',
		esc_html( $spec['label'] ),
		'' !== $html ? $html : '&mdash;' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	);
}

/**
 * Status line on impact figures: whether the figure can appear on the live site.
 *
 * @param WP_Post $post Impact figure.
 */
function hcwa_render_impact_status( WP_Post $post ): void {
	$figure = hcwa_impact_figure_from_post( $post );
	$flag   = (bool) hcwa_meta( $post->ID, 'hcwa_verified' );

	if ( $figure['verified'] && null !== $figure['value'] ) {
		$class   = 'notice-success';
		$message = __( 'Verified: this figure can appear on the live site.', 'hcwa' );
	} elseif ( $flag ) {
		$class   = 'notice-warning';
		$message = __( '"Verified" is ticked, but the value, the name or the date is missing, so the figure still counts as unverified and stays off the live site.', 'hcwa' );
	} else {
		$class   = 'notice-info';
		$message = __( 'Not verified: this figure shows only on staging, with a "Pending verification" chip.', 'hcwa' );
	}

	printf( '<div class="notice inline %1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

/**
 * Prints one form row.
 *
 * @param WP_Post $post Post being edited.
 * @param string  $key  Meta key.
 * @param array   $spec Field definition.
 */
function hcwa_render_meta_field( WP_Post $post, string $key, array $spec ): void {
	$id    = 'hcwa-' . str_replace( '_', '-', $key );
	$name  = 'hcwa_meta[' . $key . ']';
	$value = $spec['protective'] ? hcwa_meta_flag_any( $post->ID, $key ) : hcwa_meta( $post->ID, $key );
	$help  = '' !== $spec['help'] ? '<p class="description" id="' . esc_attr( $id ) . '-help">' . esc_html( $spec['help'] ) . '</p>' : '';
	$desc  = '' !== $spec['help'] ? ' aria-describedby="' . esc_attr( $id ) . '-help"' : '';

	echo '<div class="hcwa-field hcwa-field--' . esc_attr( $spec['input'] ) . '">';

	if ( 'checkbox' === $spec['input'] ) {
		printf(
			'<input type="hidden" name="%1$s" value="0"><label for="%2$s"><input type="checkbox" id="%2$s" name="%1$s" value="1"%3$s%4$s> %5$s</label>%6$s',
			esc_attr( $name ),
			esc_attr( $id ),
			checked( (bool) $value, true, false ),
			$desc, // Built from esc_attr() above.
			esc_html( $spec['label'] ),
			$help // Built from esc_html() above.
		);
		echo '</div>';
		return;
	}

	if ( 'post_multi' === $spec['input'] ) {
		echo '<fieldset><legend class="hcwa-field__label">' . esc_html( $spec['label'] ) . '</legend>';
		echo '<input type="hidden" name="' . esc_attr( $name ) . '[]" value="0">';
		$options = hcwa_meta_post_options( $spec['post_type'] );
		if ( ! $options ) {
			echo '<p class="description">' . esc_html__( 'Nothing to choose yet. Add partners first.', 'hcwa' ) . '</p>';
		}
		echo '<div class="hcwa-field__checklist">';
		foreach ( $options as $option_id => $option_label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[]" value="%2$d"%3$s> %4$s</label>',
				esc_attr( $name ),
				(int) $option_id,
				checked( in_array( (int) $option_id, (array) $value, true ), true, false ),
				esc_html( $option_label )
			);
		}
		echo '</div>' . $help . '</fieldset></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $help is escaped above.
		return;
	}

	printf( '<label class="hcwa-field__label" for="%1$s">%2$s</label>', esc_attr( $id ), esc_html( $spec['label'] ) );

	switch ( $spec['input'] ) {
		case 'textarea':
		case 'lines':
			printf(
				'<textarea id="%1$s" name="%2$s" rows="%3$d" class="large-text"%4$s%5$s>%6$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				'lines' === $spec['input'] ? 5 : 3,
				isset( $spec['maxlength'] ) ? ' maxlength="' . (int) $spec['maxlength'] . '"' : '',
				$desc, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_textarea( (string) $value )
			);
			break;

		case 'select':
			printf( '<select id="%1$s" name="%2$s"%3$s>', esc_attr( $id ), esc_attr( $name ), $desc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			foreach ( $spec['options'] as $option_value => $option_label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( (string) $option_value ), selected( (string) $value, (string) $option_value, false ), esc_html( $option_label ) );
			}
			echo '</select>';
			break;

		case 'post_select':
			printf( '<select id="%1$s" name="%2$s"%3$s>', esc_attr( $id ), esc_attr( $name ), $desc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<option value="0">' . esc_html__( 'None', 'hcwa' ) . '</option>';
			foreach ( $spec['post_type'] as $post_type ) {
				$options = hcwa_meta_post_options( array( $post_type ) );
				if ( ! $options ) {
					continue;
				}
				$type_object = get_post_type_object( $post_type );
				printf( '<optgroup label="%s">', esc_attr( $type_object ? $type_object->labels->name : $post_type ) );
				foreach ( $options as $option_id => $option_label ) {
					printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $option_id, selected( (int) $value, (int) $option_id, false ), esc_html( $option_label ) );
				}
				echo '</optgroup>';
			}
			echo '</select>';
			break;

		case 'media':
		case 'media_multi':
			hcwa_render_media_control( $id, $name, $spec, $value );
			break;

		default:
			$types = array(
				'url'     => 'url',
				'email'   => 'email',
				'tel'     => 'tel',
				'year'    => 'number',
				'integer' => 'number',
				'number'  => 'number',
				'date'    => 'date',
			);
			$extra = array(
				'year'    => ' min="1900" max="2100" step="1" placeholder="' . esc_attr__( 'YYYY', 'hcwa' ) . '"',
				'integer' => ' step="1"',
				'number'  => ' step="any"',
			);
			$shown = $value;
			if ( 'year' === $spec['input'] && 0 === (int) $value ) {
				$shown = '';
			}
			printf(
				'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="%5$s"%6$s%7$s>',
				esc_attr( $types[ $spec['input'] ] ?? 'text' ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( null === $shown ? '' : (string) $shown ),
				in_array( $spec['input'], array( 'year', 'integer', 'number', 'date' ), true ) ? 'small-text' : 'regular-text',
				$extra[ $spec['input'] ] ?? '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static or escaped.
				$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
	}

	echo $help . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
}

/**
 * Media picker: a hidden input with the ID(s), a preview and two buttons (assets/admin.js).
 *
 * @param string    $id    Element ID.
 * @param string    $name  Input name.
 * @param array     $spec  Field definition (media = image | application/pdf).
 * @param int|int[] $value Attachment ID or IDs.
 */
function hcwa_render_media_control( string $id, string $name, array $spec, $value ): void {
	$multiple = 'media_multi' === $spec['input'];
	$ids      = $multiple ? (array) $value : array_filter( array( (int) $value ) );
	$is_pdf   = 'application/pdf' === ( $spec['media'] ?? '' );

	printf(
		'<input type="hidden" id="%1$s" name="%2$s" value="%3$s">',
		esc_attr( $id ),
		esc_attr( $name ),
		esc_attr( implode( ',', array_map( 'intval', $ids ) ) )
	);

	echo '<div class="hcwa-media__preview" id="' . esc_attr( $id ) . '-preview">';
	echo hcwa_media_preview_html( $ids ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	echo '</div>';

	printf(
		'<p class="hcwa-media__buttons"><button type="button" class="button" data-hcwa-media="%1$s" data-target="%2$s" data-type="%3$s" data-title="%4$s" data-button="%5$s">%6$s</button> <button type="button" class="button-link button-link-delete" data-hcwa-media-clear data-target="%2$s">%7$s</button></p>',
		$multiple ? 'multiple' : 'single',
		esc_attr( $id ),
		esc_attr( $spec['media'] ?? 'image' ),
		esc_attr( $spec['label'] ),
		esc_attr__( 'Use selected', 'hcwa' ),
		$is_pdf ? esc_html__( 'Choose PDF', 'hcwa' ) : ( $multiple ? esc_html__( 'Choose photos', 'hcwa' ) : esc_html__( 'Choose image', 'hcwa' ) ),
		esc_html__( 'Remove', 'hcwa' )
	);
}

/**
 * Thumbnails (images) or file names (other files) for a list of attachments.
 *
 * @param int[] $ids Attachment IDs.
 */
function hcwa_media_preview_html( array $ids ): string {
	$html = '';
	foreach ( $ids as $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$html .= wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'class' => 'hcwa-media__thumb' ) );
		} elseif ( $attachment_id ) {
			$html .= '<span class="hcwa-media__file">' . esc_html( wp_basename( (string) get_attached_file( $attachment_id ) ) ) . '</span>';
		}
	}
	return $html;
}

/**
 * Vietnamese posts of the given types, for pickers (sync fields store VI IDs).
 *
 * @param string[] $post_types Post types.
 * @return array<int, string> ID => title.
 */
function hcwa_meta_post_options( array $post_types ): array {
	$args = array(
		'post_type'        => $post_types,
		'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'numberposts'      => 300,
		'orderby'          => 'title',
		'order'            => 'ASC',
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$args['lang'] = 'vi';
	}

	$options = array();
	foreach ( get_posts( $args ) as $option ) {
		$options[ $option->ID ] = '' !== $option->post_title ? $option->post_title : sprintf( /* translators: %d: post ID */ __( '(no title, ID %d)', 'hcwa' ), $option->ID );
	}
	return $options;
}

/**
 * Saves the details box. Runs for classic and block editor saves (the block editor
 * posts meta boxes separately); REST saves without our form are ignored here.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function hcwa_save_meta_box( $post_id, $post ): void {
	if ( ! isset( $_POST['hcwa_meta_nonce'] ) || ! $post instanceof WP_Post ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hcwa_meta_nonce'] ) ), 'hcwa_meta_' . $post_id ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$input = isset( $_POST['hcwa_meta'] ) && is_array( $_POST['hcwa_meta'] ) ? wp_unslash( $_POST['hcwa_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value goes through hcwa_sanitize_meta via update_post_meta.

	// On a translation, shared fields are read-only and come from the Vietnamese original
	// (hcwa_pll_seed_shared_meta()); writing them here would sync straight back over it.
	$is_translation = hcwa_original_id( (int) $post_id ) !== (int) $post_id;

	foreach ( hcwa_meta_fields()[ $post->post_type ] ?? array() as $key => $spec ) {
		if ( ! $spec['box'] || ! array_key_exists( $key, $input ) || ( $spec['sync'] && $is_translation ) || ! hcwa_meta_user_can_edit( $spec ) ) {
			continue;
		}

		$raw = $input[ $key ];

		if ( 'number' === $spec['input'] && ( ! is_scalar( $raw ) || '' === trim( (string) $raw ) ) ) {
			delete_post_meta( $post_id, $key );
			continue;
		}

		if ( 'checkbox' === $spec['input'] ) {
			$raw = '1' === (string) $raw;
		} elseif ( in_array( $spec['input'], array( 'media_multi', 'post_multi' ), true ) ) {
			$raw = hcwa_id_list( $raw );
		} elseif ( ! is_scalar( $raw ) ) {
			continue;
		}

		update_post_meta( $post_id, $key, wp_slash( $raw ) );
	}
}
add_action( 'save_post', 'hcwa_save_meta_box', 10, 2 );

/**
 * Media picker script and field styles on our edit screens and Settings → HCWA.
 *
 * @param string $hook_suffix Admin page.
 */
function hcwa_admin_assets( $hook_suffix ): void {
	$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$on_edit = in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) && $screen && in_array( $screen->post_type, array_merge( hcwa_post_types(), array( 'post' ) ), true );
	$on_page = 'settings_page_hcwa-settings' === $hook_suffix;

	if ( ! $on_edit && ! $on_page ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'hcwa-admin', HCWA_CORE_URL . 'assets/admin.js', array(), HCWA_CORE_VERSION, true );
	wp_enqueue_style( 'hcwa-admin', HCWA_CORE_URL . 'assets/admin.css', array(), HCWA_CORE_VERSION );
}
add_action( 'admin_enqueue_scripts', 'hcwa_admin_assets' );

/* -------------------------------------------------------------------------
 * Impact figure list: value, verified and scope at a glance
 * ---------------------------------------------------------------------- */

/**
 * Adds list columns.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function hcwa_impact_columns( $columns ): array {
	$out = array();
	foreach ( (array) $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['hcwa_value']    = __( 'Value', 'hcwa' );
			$out['hcwa_verified'] = __( 'Verified', 'hcwa' );
			$out['hcwa_scope']    = __( 'Shown on', 'hcwa' );
		}
	}
	return $out;
}
add_filter( 'manage_hcwa_impact_posts_columns', 'hcwa_impact_columns' );

/**
 * Prints list column cells.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function hcwa_impact_column_cell( $column, $post_id ): void {
	$post = get_post( (int) $post_id );
	if ( ! $post ) {
		return;
	}
	$figure = hcwa_impact_figure_from_post( $post );

	if ( 'hcwa_value' === $column ) {
		echo null === $figure['value'] ? '&mdash;' : esc_html( number_format_i18n( $figure['value'], fmod( $figure['value'], 1.0 ) ? 1 : 0 ) . $figure['suffix'] );
	} elseif ( 'hcwa_verified' === $column ) {
		echo $figure['verified'] ? esc_html__( 'Yes', 'hcwa' ) : esc_html__( 'No', 'hcwa' );
	} elseif ( 'hcwa_scope' === $column ) {
		$type     = (string) hcwa_meta( $post->ID, 'hcwa_scope_type' );
		$scope_id = (int) hcwa_meta( $post->ID, 'hcwa_scope_id' );
		$label    = hcwa_meta_fields()['hcwa_impact']['hcwa_scope_type']['options'][ $type ] ?? $type;
		echo esc_html( 'organization' === $type || ! $scope_id ? $label : get_the_title( $scope_id ) );
	}
}
add_action( 'manage_hcwa_impact_posts_custom_column', 'hcwa_impact_column_cell', 10, 2 );

/**
 * Our fields stay out of public REST responses: contact details are shown only once
 * approved, and photo approval flags are internal. Editors (edit context) still get
 * them; the site itself renders on the server and never needs them over REST.
 *
 * @param WP_REST_Response $response Response.
 * @param WP_Post          $post     Post.
 * @return WP_REST_Response
 */
function hcwa_rest_hide_meta( $response, $post ) {
	if ( ! $response instanceof WP_REST_Response || ! $post instanceof WP_Post || current_user_can( 'edit_post', $post->ID ) ) {
		return $response;
	}

	$data = $response->get_data();
	if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
		foreach ( array_keys( hcwa_meta_fields()[ $post->post_type ] ?? array() ) as $key ) {
			unset( $data['meta'][ $key ] );
		}
		$response->set_data( $data );
	}

	return $response;
}
foreach ( array( 'hcwa_center', 'hcwa_program', 'hcwa_report', 'hcwa_impact', 'hcwa_partner', 'attachment', 'post' ) as $hcwa_rest_type ) {
	add_filter( "rest_prepare_{$hcwa_rest_type}", 'hcwa_rest_hide_meta', 10, 2 );
}
unset( $hcwa_rest_type );
