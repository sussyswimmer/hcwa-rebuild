<?php
/**
 * Settings → HCWA: announcement bar, contact details, page map and donation details.
 * Everything is stored in one option, 'hcwa_settings'.
 *
 * Donation details are shown only after HCWA finance has confirmed them (a name and a
 * date). Changing any detail without updating the confirmation clears it.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Raw value of one setting, or $default when missing or empty.
 *
 * @param string $key     Setting key (see hcwa_settings_sections()).
 * @param mixed  $default Fallback.
 * @return mixed
 */
function hcwa_setting( string $key, $default = null ) {
	$options = get_option( 'hcwa_settings', array() );
	if ( ! is_array( $options ) || ! array_key_exists( $key, $options ) || null === $options[ $key ] || '' === $options[ $key ] ) {
		return $default;
	}
	return $options[ $key ];
}

/**
 * Per-language setting: reads "{$key}_vi" or "{$key}_en". No fallback to the other
 * language, so a page never mixes languages; '' when not filled in.
 *
 * @param string      $key  Base key, e.g. 'announcement_text', 'address', 'registration'.
 * @param string|null $lang 'vi' or 'en'; defaults to the current language.
 */
function hcwa_setting_l( string $key, ?string $lang = null ): string {
	$value = hcwa_setting( $key . '_' . hcwa_normalize_lang( $lang ?? hcwa_lang() ), '' );
	return is_scalar( $value ) ? (string) $value : '';
}

/**
 * The announcement bar for the current language, or null when it is switched off, has
 * no text in this language, or (on the live site) still contains draft markers.
 *
 * @return array{text: string, url: string}|null
 */
function hcwa_announcement(): ?array {
	$options = get_option( 'hcwa_settings', array() );
	return hcwa_announcement_from( is_array( $options ) ? $options : array(), hcwa_lang(), hcwa_is_production() );
}

/**
 * Pure part of hcwa_announcement(), unit-tested.
 *
 * @param array  $settings   The hcwa_settings option.
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 * @return array{text: string, url: string}|null
 */
function hcwa_announcement_from( array $settings, string $lang, bool $production ): ?array {
	$lang = hcwa_normalize_lang( $lang );
	$text = trim( (string) ( $settings[ 'announcement_text_' . $lang ] ?? '' ) );

	if ( empty( $settings['announcement_enabled'] ) || '' === $text ) {
		return null;
	}
	if ( $production && hcwa_contains_draft( $text ) ) {
		return null;
	}

	return array(
		'text' => $text,
		'url'  => (string) ( $settings[ 'announcement_link_' . $lang ] ?? '' ),
	);
}

/**
 * Contact email addresses, first one is the general address.
 *
 * @return string[]
 */
function hcwa_contact_emails(): array {
	$lines = preg_split( '/\R/u', (string) hcwa_setting( 'emails', '' ) );
	return array_values( array_filter( array_map( 'sanitize_email', array_map( 'trim', (array) $lines ) ) ) );
}

/**
 * Donation details for the current language, or null unless HCWA finance has confirmed
 * them (both "confirmed by" and "confirmed on" are filled in).
 *
 * @return array|null See hcwa_donation_details_from().
 */
function hcwa_donation_details(): ?array {
	$options = get_option( 'hcwa_settings', array() );
	return hcwa_donation_details_from( is_array( $options ) ? $options : array(), hcwa_lang() );
}

/**
 * Pure part of hcwa_donation_details(), unit-tested.
 *
 * Rows follow the prototype (content/draft/involve.json bankFields, abroadFields);
 * rows without a value are left out.
 *
 * @param array  $settings The hcwa_settings option.
 * @param string $lang     'vi' or 'en'.
 * @return array{in_vietnam: array[], abroad: array[], vietqr_id: int, paypal_url: string, confirmed_by: string, confirmed_date: string}|null
 */
function hcwa_donation_details_from( array $settings, string $lang ): ?array {
	$get  = static fn( string $key ): string => isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
	$lang = hcwa_normalize_lang( $lang );

	$confirmed_by   = $get( 'donation_confirmed_by' );
	$confirmed_date = $get( 'donation_confirmed_date' );

	if ( '' === $confirmed_by || '' === $confirmed_date ) {
		return null;
	}

	$row = static function ( string $id, string $label_key, string $value, bool $copy, string $hint_key = '' ) use ( $lang ): array {
		return array(
			'id'    => $id,
			'label' => hcwa_t_in( $label_key, $lang ),
			'value' => $value,
			'copy'  => $copy,
			'hint'  => '' !== $hint_key ? hcwa_t_in( $hint_key, $lang ) : '',
		);
	};

	$in_vietnam = array(
		$row( 'bank', 'pay_bank', $get( 'donation_bank_' . $lang ), false ),
		$row( 'account', 'pay_account', $get( 'donation_account' ), true ),
		$row( 'holder', 'pay_holder', $get( 'donation_holder' ), false ),
		$row( 'note', 'pay_note', $get( 'donation_note_' . $lang ), true, 'pay_noteHint' ),
	);

	$abroad = array(
		$row( 'bank', 'pay_bank', $get( 'donation_abroad_bank_' . $lang ), false ),
		$row( 'swift', 'pay_swift', $get( 'donation_abroad_swift' ), true ),
		$row( 'account', 'pay_accountAbroad', $get( 'donation_abroad_account' ), true ),
		$row( 'holder', 'pay_holder', $get( 'donation_abroad_holder' ), false ),
	);

	$filled = static fn( array $rows ): array => array_values( array_filter( $rows, static fn( $r ) => '' !== $r['value'] ) );

	return array(
		'in_vietnam'     => $filled( $in_vietnam ),
		'abroad'         => $filled( $abroad ),
		'vietqr_id'      => absint( $get( 'donation_vietqr_id' ) ),
		'paypal_url'     => $get( 'donation_paypal_url' ),
		'confirmed_by'   => $confirmed_by,
		'confirmed_date' => $confirmed_date,
	);
}

/**
 * Settings sections and fields. Field types: checkbox, text, textarea, url, tel,
 * emails, zalo, date, page, media.
 *
 * @return array<string, array{title: string, intro: string, fields: array}>
 */
function hcwa_settings_sections(): array {
	$page_labels = array(
		'home'        => __( 'Home', 'hcwa' ),
		'about'       => __( 'About Us', 'hcwa' ),
		'ourStory'    => __( 'Our story', 'hcwa' ),
		'team'        => __( 'Team', 'hcwa' ),
		'partners'    => __( 'Partners and sponsors', 'hcwa' ),
		'policies'    => __( 'Policies', 'hcwa' ),
		'ourWork'     => __( 'Our Work', 'hcwa' ),
		'impact'      => __( 'Impact and Reports', 'hcwa' ),
		'news'        => __( 'News (posts page)', 'hcwa' ),
		'getInvolved' => __( 'Get Involved', 'hcwa' ),
		'donate'      => __( 'Donate', 'hcwa' ),
		'volunteer'   => __( 'Volunteer', 'hcwa' ),
		'partner'     => __( 'Partner with us', 'hcwa' ),
		'inKind'      => __( 'In-kind gifts', 'hcwa' ),
		'contact'     => __( 'Contact', 'hcwa' ),
	);

	$pages = array();
	foreach ( hcwa_page_keys() as $key ) {
		$pages[ 'page_map:' . $key ] = array(
			'type'  => 'page',
			'label' => $page_labels[ $key ] ?? $key,
		);
	}

	return array(
		'announcement' => array(
			'title'  => __( 'Announcement bar', 'hcwa' ),
			'intro'  => __( 'One short line above the header, for example a fundraising event. Fill in both languages, and turn it off when it is over.', 'hcwa' ),
			'fields' => array(
				'announcement_enabled' => array( 'type' => 'checkbox', 'label' => __( 'Show the announcement bar', 'hcwa' ) ),
				'announcement_text_vi' => array( 'type' => 'text', 'label' => __( 'Text in Vietnamese', 'hcwa' ) ),
				'announcement_text_en' => array( 'type' => 'text', 'label' => __( 'Text in English', 'hcwa' ) ),
				'announcement_link_vi' => array( 'type' => 'url', 'label' => __( 'Link on Vietnamese pages', 'hcwa' ) ),
				'announcement_link_en' => array( 'type' => 'url', 'label' => __( 'Link on English pages', 'hcwa' ) ),
			),
		),
		'contact'      => array(
			'title'  => __( 'Contact details', 'hcwa' ),
			'intro'  => __( 'Shown in the footer and on the Contact page. Use only details HCWA has checked.', 'hcwa' ),
			'fields' => array(
				'address_vi'      => array( 'type' => 'textarea', 'label' => __( 'Address in Vietnamese', 'hcwa' ) ),
				'address_en'      => array( 'type' => 'textarea', 'label' => __( 'Address in English', 'hcwa' ) ),
				'phone'           => array( 'type' => 'text', 'label' => __( 'Phone as shown', 'hcwa' ), 'help' => __( 'One format everywhere, for example +84 28 3840 1406.', 'hcwa' ) ),
				'phone_tel'       => array( 'type' => 'tel', 'label' => __( 'Phone for the call link', 'hcwa' ), 'help' => __( 'Digits only with the country code, for example +842838401406. Filled in from the phone above when empty.', 'hcwa' ) ),
				'emails'          => array( 'type' => 'emails', 'label' => __( 'Email addresses', 'hcwa' ), 'help' => __( 'One per line. The first one is the general address.', 'hcwa' ) ),
				'zalo'            => array( 'type' => 'zalo', 'label' => __( 'Zalo', 'hcwa' ), 'help' => __( 'A zalo.me link, or the phone number registered with Zalo.', 'hcwa' ) ),
				'facebook'        => array( 'type' => 'url', 'label' => __( 'Facebook page', 'hcwa' ) ),
				'map_url'         => array( 'type' => 'url', 'label' => __( 'Map link', 'hcwa' ), 'help' => __( 'Opens in the visitor\'s map app. No embedded map is loaded.', 'hcwa' ) ),
				'registration_vi' => array( 'type' => 'text', 'label' => __( 'Legal registration line in Vietnamese', 'hcwa' ) ),
				'registration_en' => array( 'type' => 'text', 'label' => __( 'Legal registration line in English', 'hcwa' ) ),
			),
		),
		'pages'        => array(
			'title'  => __( 'Page map', 'hcwa' ),
			'intro'  => __( 'Choose the Vietnamese page for each part of the site. Menus, buttons and footer links use these; English links follow each page\'s Polylang translation. Leave a row empty to use the default address.', 'hcwa' ),
			'fields' => $pages,
		),
		'donation'     => array(
			'title'  => __( 'Donation details', 'hcwa' ),
			'intro'  => __( 'Copy these only from HCWA finance\'s written confirmation. The live site shows nothing until "Confirmed by" and "Confirmed on" are filled in. Changing any detail without updating the confirmation clears it.', 'hcwa' ),
			'fields' => array(
				'donation_bank_vi'          => array( 'type' => 'text', 'label' => __( 'Bank in Vietnam, name in Vietnamese', 'hcwa' ) ),
				'donation_bank_en'          => array( 'type' => 'text', 'label' => __( 'Bank in Vietnam, name in English', 'hcwa' ) ),
				'donation_account'          => array( 'type' => 'text', 'label' => __( 'Account number', 'hcwa' ) ),
				'donation_holder'           => array( 'type' => 'text', 'label' => __( 'Account holder', 'hcwa' ) ),
				'donation_note_vi'          => array( 'type' => 'text', 'label' => __( 'Transfer note format in Vietnamese', 'hcwa' ), 'help' => __( 'For example: UNGHO + số điện thoại', 'hcwa' ) ),
				'donation_note_en'          => array( 'type' => 'text', 'label' => __( 'Transfer note format in English', 'hcwa' ), 'help' => __( 'For example: UNGHO + phone number', 'hcwa' ) ),
				'donation_vietqr_id'        => array( 'type' => 'media', 'label' => __( 'VietQR image', 'hcwa' ) ),
				'donation_abroad_bank_vi'   => array( 'type' => 'text', 'label' => __( 'Bank for gifts from abroad, name in Vietnamese', 'hcwa' ) ),
				'donation_abroad_bank_en'   => array( 'type' => 'text', 'label' => __( 'Bank for gifts from abroad, name in English', 'hcwa' ) ),
				'donation_abroad_swift'     => array( 'type' => 'text', 'label' => __( 'SWIFT code', 'hcwa' ) ),
				'donation_abroad_account'   => array( 'type' => 'text', 'label' => __( 'Account number (USD/EUR)', 'hcwa' ) ),
				'donation_abroad_holder'    => array( 'type' => 'text', 'label' => __( 'Account holder (abroad)', 'hcwa' ) ),
				'donation_paypal_url'       => array( 'type' => 'url', 'label' => __( 'PayPal link', 'hcwa' ) ),
				'donation_confirmed_by'     => array( 'type' => 'text', 'label' => __( 'Confirmed by (HCWA finance)', 'hcwa' ), 'help' => __( 'Name of the person in HCWA finance who confirmed these details in writing.', 'hcwa' ) ),
				'donation_confirmed_date'   => array( 'type' => 'date', 'label' => __( 'Confirmed on', 'hcwa' ) ),
			),
		),
	);
}

/**
 * Keys of the donation details whose change requires a new confirmation.
 *
 * @return string[]
 */
function hcwa_donation_value_keys(): array {
	return array_values( array_diff( array_keys( hcwa_settings_sections()['donation']['fields'] ), array( 'donation_confirmed_by', 'donation_confirmed_date' ) ) );
}

/**
 * Adds Settings → HCWA.
 */
function hcwa_settings_menu(): void {
	add_options_page(
		__( 'HCWA settings', 'hcwa' ),
		__( 'HCWA', 'hcwa' ),
		'manage_options',
		'hcwa-settings',
		'hcwa_render_settings_page'
	);
}
add_action( 'admin_menu', 'hcwa_settings_menu' );

/**
 * Registers the option, sections and fields.
 */
function hcwa_settings_init(): void {
	register_setting(
		'hcwa_settings',
		'hcwa_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'hcwa_sanitize_settings',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);

	foreach ( hcwa_settings_sections() as $section_id => $section ) {
		add_settings_section(
			'hcwa_' . $section_id,
			$section['title'],
			static function () use ( $section_id, $section ) {
				echo '<p>' . esc_html( $section['intro'] ) . '</p>';
				if ( 'donation' === $section_id ) {
					hcwa_render_donation_status();
				}
			},
			'hcwa-settings'
		);

		foreach ( $section['fields'] as $key => $field ) {
			add_settings_field(
				'hcwa_' . str_replace( ':', '_', $key ),
				esc_html( $field['label'] ),
				'hcwa_render_setting_field',
				'hcwa-settings',
				'hcwa_' . $section_id,
				array(
					'key'       => $key,
					'field'     => $field,
					'label_for' => 'hcwa-setting-' . str_replace( array( ':', '_' ), '-', $key ),
				)
			);
		}
	}
}
add_action( 'admin_init', 'hcwa_settings_init' );

/**
 * Stored value of a field key ('page_map:home' reads the page map).
 *
 * @param array  $options Option value.
 * @param string $key     Field key.
 * @return mixed
 */
function hcwa_settings_value( array $options, string $key ) {
	if ( 0 === strpos( $key, 'page_map:' ) ) {
		$map = isset( $options['page_map'] ) && is_array( $options['page_map'] ) ? $options['page_map'] : array();
		return absint( $map[ substr( $key, 9 ) ] ?? 0 );
	}
	return $options[ $key ] ?? '';
}

/**
 * Prints one settings field.
 *
 * @param array $args ['key' => string, 'field' => array, 'label_for' => string].
 */
function hcwa_render_setting_field( array $args ): void {
	$key     = $args['key'];
	$field   = $args['field'];
	$id      = $args['label_for'];
	$options = get_option( 'hcwa_settings', array() );
	$value   = hcwa_settings_value( is_array( $options ) ? $options : array(), $key );
	$name    = 0 === strpos( $key, 'page_map:' ) ? 'hcwa_settings[page_map][' . substr( $key, 9 ) . ']' : 'hcwa_settings[' . $key . ']';
	$help_id = $id . '-help';
	$desc    = ! empty( $field['help'] ) ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '';

	switch ( $field['type'] ) {
		case 'checkbox':
			printf(
				'<input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1"%3$s>',
				esc_attr( $name ),
				esc_attr( $id ),
				checked( ! empty( $value ), true, false )
			);
			break;

		case 'textarea':
		case 'emails':
			printf(
				'<textarea id="%1$s" name="%2$s" rows="3" class="large-text"%3$s>%4$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				$desc, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_textarea( (string) $value )
			);
			break;

		case 'page':
			$dropdown = array(
				'name'              => $name,
				'id'                => $id,
				'selected'          => (int) $value,
				'show_option_none'  => esc_html__( 'Default address', 'hcwa' ),
				'option_none_value' => '0',
				'echo'              => 0,
				'post_status'       => array( 'publish', 'draft', 'private' ),
			);
			if ( function_exists( 'pll_get_post' ) ) {
				$dropdown['lang'] = 'vi';
			}
			echo wp_dropdown_pages( $dropdown ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup, escaped by WordPress.
			$path = hcwa_page_path( substr( $key, 9 ), 'vi' );
			printf( ' <span class="description">%s</span>', esc_html( sprintf( /* translators: %s: default address */ __( 'Default: /%s', 'hcwa' ), (string) $path ) ) );
			break;

		case 'media':
			hcwa_render_media_control(
				$id,
				$name,
				array(
					'input' => 'media',
					'media' => 'image',
					'label' => $field['label'],
				),
				(int) $value
			);
			break;

		default:
			$types = array(
				'url'  => 'url',
				'tel'  => 'tel',
				'date' => 'date',
				'zalo' => 'text',
			);
			printf(
				'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text"%5$s>',
				esc_attr( $types[ $field['type'] ] ?? 'text' ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
	}

	if ( ! empty( $field['help'] ) ) {
		printf( '<p class="description" id="%1$s">%2$s</p>', esc_attr( $help_id ), esc_html( $field['help'] ) );
	}
}

/**
 * Whether the donation details are live, shown above the donation fields.
 */
function hcwa_render_donation_status(): void {
	$by   = (string) hcwa_setting( 'donation_confirmed_by', '' );
	$date = (string) hcwa_setting( 'donation_confirmed_date', '' );

	if ( '' !== $by && '' !== $date ) {
		$class   = 'notice-success';
		$message = sprintf( /* translators: 1: name, 2: date */ __( 'Confirmed by %1$s on %2$s. The live site shows these details.', 'hcwa' ), $by, $date );
	} else {
		$class   = 'notice-warning';
		$message = __( 'Not confirmed. The live site shows no donation details; staging shows a labelled placeholder.', 'hcwa' );
	}

	printf( '<div class="notice inline %1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

/**
 * Prints the settings page.
 */
function hcwa_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap hcwa-settings">';
	echo '<h1>' . esc_html__( 'HCWA settings', 'hcwa' ) . '</h1>';
	echo '<form action="options.php" method="post">';
	settings_fields( 'hcwa_settings' );
	do_settings_sections( 'hcwa-settings' );
	submit_button();
	echo '</form></div>';
}

/**
 * Cleans the submitted settings. Unknown keys are dropped.
 *
 * @param mixed $input Submitted value.
 * @return array
 */
function hcwa_sanitize_settings( $input ): array {
	$old = get_option( 'hcwa_settings', array() );
	$old = is_array( $old ) ? $old : array();

	if ( ! is_array( $input ) ) {
		return $old;
	}

	$clean = array( 'page_map' => array() );

	foreach ( hcwa_settings_sections() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			if ( 0 === strpos( $key, 'page_map:' ) ) {
				$page_key = substr( $key, 9 );
				$page_id  = absint( $input['page_map'][ $page_key ] ?? 0 );
				if ( $page_id && 'page' === get_post_type( $page_id ) ) {
					$clean['page_map'][ $page_key ] = $page_id;
				}
				continue;
			}
			$clean[ $key ] = hcwa_sanitize_setting_value( $field['type'], $input[ $key ] ?? '' );
		}
	}

	if ( '' === $clean['phone_tel'] && '' !== $clean['phone'] ) {
		$clean['phone_tel'] = hcwa_sanitize_setting_value( 'tel', $clean['phone'] );
	}

	return hcwa_guard_donation_confirmation( $old, $clean );
}

/**
 * Clears the finance confirmation when donation details changed but the confirmation
 * did not, and tells the editor why.
 *
 * @param array $old   Stored settings.
 * @param array $clean New, sanitized settings.
 * @return array
 */
function hcwa_guard_donation_confirmation( array $old, array $clean ): array {
	$changed = false;
	foreach ( hcwa_donation_value_keys() as $key ) {
		if ( (string) ( $old[ $key ] ?? '' ) !== (string) ( $clean[ $key ] ?? '' ) ) {
			$changed = true;
			break;
		}
	}

	$reconfirmed = (string) ( $old['donation_confirmed_by'] ?? '' ) !== (string) ( $clean['donation_confirmed_by'] ?? '' )
		|| (string) ( $old['donation_confirmed_date'] ?? '' ) !== (string) ( $clean['donation_confirmed_date'] ?? '' );

	if ( $changed && ! $reconfirmed && ( '' !== $clean['donation_confirmed_by'] || '' !== $clean['donation_confirmed_date'] ) ) {
		$clean['donation_confirmed_by']   = '';
		$clean['donation_confirmed_date'] = '';

		static $warned = false;
		if ( ! $warned && function_exists( 'add_settings_error' ) ) {
			$warned = true;
			add_settings_error(
				'hcwa_settings',
				'hcwa_donation_unconfirmed',
				__( 'Donation details changed, so the finance confirmation was cleared. The live site hides donation details until HCWA finance confirms the new details and you fill in "Confirmed by" and "Confirmed on" again.', 'hcwa' ),
				'warning'
			);
		}
	}

	return $clean;
}

/**
 * Cleans one settings value by field type.
 *
 * @param string $type  Field type.
 * @param mixed  $value Submitted value.
 * @return mixed
 */
function hcwa_sanitize_setting_value( string $type, $value ) {
	$value = is_scalar( $value ) ? (string) $value : '';

	switch ( $type ) {
		case 'checkbox':
			return '1' === $value;
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'url':
			return esc_url_raw( trim( $value ) );
		case 'tel':
			$digits = preg_replace( '/\D/', '', $value );
			return '' === $digits ? '' : ( 0 === strpos( trim( $value ), '+' ) ? '+' : '' ) . $digits;
		case 'emails':
			$emails = array_filter( array_map( 'sanitize_email', array_map( 'trim', (array) preg_split( '/[\s,;]+/', $value ) ) ) );
			return implode( "\n", array_unique( $emails ) );
		case 'zalo':
			$value = trim( $value );
			if ( preg_match( '/^\+?[\d .]+$/', $value ) ) {
				return 'https://zalo.me/' . preg_replace( '/\D/', '', $value );
			}
			return esc_url_raw( $value );
		case 'date':
			return hcwa_is_ymd( trim( $value ) ) ? trim( $value ) : '';
		case 'media':
			$id = absint( $value );
			return ( $id && 'attachment' === get_post_type( $id ) ) ? $id : 0;
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * On the live site, reminds administrators when donation details are not confirmed
 * (the donation block then renders nothing).
 */
function hcwa_donation_admin_notice(): void {
	if ( ! hcwa_is_production() || ! current_user_can( 'manage_options' ) || null !== hcwa_donation_details() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'settings_page_hcwa-settings' ), true ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
		esc_html__( 'Donation details are hidden on the live site until HCWA finance confirms them.', 'hcwa' ),
		esc_url( admin_url( 'options-general.php?page=hcwa-settings' ) ),
		esc_html__( 'Open HCWA settings', 'hcwa' )
	);
}
add_action( 'admin_notices', 'hcwa_donation_admin_notice' );
