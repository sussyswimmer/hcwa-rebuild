<?php
/**
 * Settings → HCWA: contact details, announcement bar, donation details and the pages the
 * theme links to by role. Text that differs by language has a VI and an EN field side by side.
 *
 * Two groups carry a confirmation (who + date), and render on the live site only once it is
 * filled in:
 * - contact details (address, phone, email): "HCWA verified"
 * - donation details: "HCWA finance confirmed in writing" (CLAUDE.md hard rule)
 */

defined( 'ABSPATH' ) || exit;

function hcwa_settings_schema() {
	return array(
		'contact'  => array(
			'title'  => __( 'Contact details (footer, contact page)', 'hcwa-core' ),
			'fields' => array(
				'description'   => array( 'label' => __( 'One-line description (footer)', 'hcwa-core' ), 'type' => 'pair-textarea' ),
				'registration'  => array( 'label' => __( 'Legal status / registration line', 'hcwa-core' ), 'type' => 'pair' ),
				'address'       => array( 'label' => __( 'Address (post-2025 ward format)', 'hcwa-core' ), 'type' => 'pair' ),
				'phone_display' => array( 'label' => __( 'Phone as shown, for example +84 28 3840 1406', 'hcwa-core' ), 'type' => 'text' ),
				'phone_tel'     => array( 'label' => __( 'Phone for tap-to-call, digits only, for example +842838401406', 'hcwa-core' ), 'type' => 'text' ),
				'email'         => array( 'label' => __( 'General email', 'hcwa-core' ), 'type' => 'email' ),
				'email_partner' => array( 'label' => __( 'Sponsorship and partnership email', 'hcwa-core' ), 'type' => 'email' ),
				'map_url'       => array( 'label' => __( 'Google Maps link', 'hcwa-core' ), 'type' => 'url' ),
				'zalo_url'      => array( 'label' => __( 'Zalo link', 'hcwa-core' ), 'type' => 'url' ),
				'facebook_url'  => array( 'label' => __( 'Facebook page', 'hcwa-core' ), 'type' => 'url' ),
				'instagram_url' => array( 'label' => __( 'Instagram', 'hcwa-core' ), 'type' => 'url' ),
				'youtube_url'   => array( 'label' => __( 'YouTube', 'hcwa-core' ), 'type' => 'url' ),
				'contact_verified_by' => array( 'label' => __( 'Verified by (name, role)', 'hcwa-core' ), 'type' => 'text' ),
				'contact_verified_on' => array( 'label' => __( 'Verified on', 'hcwa-core' ), 'type' => 'date' ),
			),
		),
		'announce' => array(
			'title'  => __( 'Announcement bar (one line above the header)', 'hcwa-core' ),
			'fields' => array(
				'announce_on'   => array( 'label' => __( 'Show the announcement bar', 'hcwa-core' ), 'type' => 'checkbox' ),
				'announce_text' => array( 'label' => __( 'Text', 'hcwa-core' ), 'type' => 'pair' ),
				'announce_url'  => array( 'label' => __( 'Link', 'hcwa-core' ), 'type' => 'pair-url' ),
			),
		),
		'donation' => array(
			'title'  => __( 'Donation details (shown only after HCWA finance confirms them in writing)', 'hcwa-core' ),
			'fields' => array(
				'bank_name'      => array( 'label' => __( 'Bank in Vietnam', 'hcwa-core' ), 'type' => 'text' ),
				'bank_account'   => array( 'label' => __( 'Account number', 'hcwa-core' ), 'type' => 'text' ),
				'bank_holder'    => array( 'label' => __( 'Account holder', 'hcwa-core' ), 'type' => 'text' ),
				'transfer_note'  => array( 'label' => __( 'Transfer note format, for example UNGHO + phone', 'hcwa-core' ), 'type' => 'pair' ),
				'vietqr'         => array( 'label' => __( 'VietQR image (media ID)', 'hcwa-core' ), 'type' => 'number' ),
				'abroad_bank'    => array( 'label' => __( 'Bank for USD/EUR transfers', 'hcwa-core' ), 'type' => 'text' ),
				'abroad_swift'   => array( 'label' => __( 'SWIFT code', 'hcwa-core' ), 'type' => 'text' ),
				'abroad_account' => array( 'label' => __( 'USD/EUR account number', 'hcwa-core' ), 'type' => 'text' ),
				'abroad_holder'  => array( 'label' => __( 'USD/EUR account holder', 'hcwa-core' ), 'type' => 'text' ),
				'paypal_url'     => array( 'label' => __( 'PayPal donation link', 'hcwa-core' ), 'type' => 'url' ),
				'donation_confirmed_by' => array( 'label' => __( 'Confirmed by (HCWA finance, name and role)', 'hcwa-core' ), 'type' => 'text' ),
				'donation_confirmed_on' => array( 'label' => __( 'Confirmed in writing on', 'hcwa-core' ), 'type' => 'date' ),
			),
		),
	);
}

function hcwa_setting( $key, $lang = null ) {
	$settings = (array) get_option( 'hcwa_settings', array() );
	if ( null !== $lang ) {
		return isset( $settings[ $key ][ $lang ] ) ? (string) $settings[ $key ][ $lang ] : '';
	}
	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
}

/** Localized value of a VI/EN pair setting in the current language. */
function hcwa_setting_tx( $key ) {
	return hcwa_setting( $key, hcwa_lang() );
}

function hcwa_contact_verified() {
	return hcwa_setting( 'contact_verified_by' ) && hcwa_setting( 'contact_verified_on' );
}

function hcwa_donation_confirmed() {
	return hcwa_setting( 'donation_confirmed_by' ) && hcwa_setting( 'donation_confirmed_on' );
}

/**
 * Contact details for templates, or null when they may not show (unverified on production).
 * `draft` = true when they render unverified in review mode.
 */
function hcwa_contact() {
	if ( ! hcwa_contact_verified() && hcwa_is_production() ) {
		return null;
	}
	return array(
		'address'   => hcwa_setting_tx( 'address' ),
		'phone'     => hcwa_setting( 'phone_display' ),
		'tel'       => hcwa_setting( 'phone_tel' ),
		'email'     => hcwa_setting( 'email' ),
		'email_partner' => hcwa_setting( 'email_partner' ),
		'map_url'   => hcwa_setting( 'map_url' ),
		'zalo_url'  => hcwa_setting( 'zalo_url' ),
		'draft'     => ! hcwa_contact_verified(),
	);
}

/** Social profile links (public pages, not personal data, no verification needed). */
function hcwa_social_links() {
	return array_filter(
		array(
			'Facebook'  => hcwa_setting( 'facebook_url' ),
			'Instagram' => hcwa_setting( 'instagram_url' ),
			'YouTube'   => hcwa_setting( 'youtube_url' ),
		)
	);
}

/* ---------- admin page ---------- */

add_action(
	'admin_menu',
	function () {
		add_options_page( __( 'HCWA settings', 'hcwa-core' ), 'HCWA', 'manage_options', 'hcwa', 'hcwa_render_settings_page' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting( 'hcwa', 'hcwa_settings', array( 'type' => 'array', 'sanitize_callback' => 'hcwa_sanitize_settings', 'show_in_rest' => false ) );
		register_setting(
			'hcwa',
			'hcwa_pages',
			array(
				'type'              => 'array',
				'show_in_rest'      => false,
				'sanitize_callback' => function ( $value ) {
					return array_map( 'absint', array_intersect_key( (array) $value, hcwa_page_roles() ) );
				},
			)
		);
	}
);

function hcwa_sanitize_settings( $input ) {
	$clean = array();
	foreach ( hcwa_settings_schema() as $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : '';
			switch ( $field['type'] ) {
				case 'pair':
				case 'pair-textarea':
				case 'pair-url':
					$clean[ $key ] = array();
					foreach ( array( 'vi', 'en' ) as $lang ) {
						$v = isset( $value[ $lang ] ) ? $value[ $lang ] : '';
						$clean[ $key ][ $lang ] = 'pair-url' === $field['type'] ? esc_url_raw( $v ) : ( 'pair-textarea' === $field['type'] ? sanitize_textarea_field( $v ) : sanitize_text_field( $v ) );
					}
					break;
				case 'checkbox':
					$clean[ $key ] = ! empty( $value );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( $value );
					break;
				case 'email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'number':
					$clean[ $key ] = absint( $value );
					break;
				case 'date':
					$clean[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? $value : '';
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
	}
	return $clean;
}

function hcwa_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = (array) get_option( 'hcwa_settings', array() );
	$pages    = (array) get_option( 'hcwa_pages', array() );
	echo '<div class="wrap hcwa-settings"><h1>' . esc_html__( 'HCWA settings', 'hcwa-core' ) . '</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'hcwa' );

	foreach ( hcwa_settings_schema() as $group_key => $group ) {
		echo '<h2>' . esc_html( $group['title'] ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $group['fields'] as $key => $field ) {
			$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
			$name  = 'hcwa_settings[' . $key . ']';
			echo '<tr><th scope="row"><label for="hcwa-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			if ( 0 === strpos( $field['type'], 'pair' ) ) {
				foreach ( array( 'vi' => 'VI', 'en' => 'EN' ) as $lang => $tag ) {
					$v  = is_array( $value ) && isset( $value[ $lang ] ) ? $value[ $lang ] : '';
					$id = 'hcwa-' . $key . ( 'vi' === $lang ? '' : '-en' );
					echo '<p class="hcwa-pair"><span class="hcwa-pair__tag" lang="' . esc_attr( $lang ) . '">' . esc_html( $tag ) . '</span>';
					if ( 'pair-textarea' === $field['type'] ) {
						printf( '<textarea class="large-text" rows="2" id="%s" name="%s[%s]" lang="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_attr( $lang ), esc_attr( $lang ), esc_textarea( $v ) );
					} else {
						printf( '<input class="regular-text" type="%s" id="%s" name="%s[%s]" value="%s" lang="%s">', 'pair-url' === $field['type'] ? 'url' : 'text', esc_attr( $id ), esc_attr( $name ), esc_attr( $lang ), esc_attr( $v ), esc_attr( $lang ) );
					}
					echo '</p>';
				}
			} elseif ( 'checkbox' === $field['type'] ) {
				printf( '<input type="checkbox" id="hcwa-%s" name="%s" value="1" %s>', esc_attr( $key ), esc_attr( $name ), checked( (bool) $value, true, false ) );
			} else {
				$types = array( 'url' => 'url', 'email' => 'email', 'date' => 'date', 'number' => 'number' );
				printf( '<input class="regular-text" type="%s" id="hcwa-%s" name="%s" value="%s">', esc_attr( isset( $types[ $field['type'] ] ) ? $types[ $field['type'] ] : 'text' ), esc_attr( $key ), esc_attr( $name ), esc_attr( (string) $value ) );
			}
			echo '</td></tr>';
		}
		echo '</table>';
		if ( 'contact' === $group_key && ! hcwa_contact_verified() ) {
			echo '<p class="notice notice-warning inline"><strong>' . esc_html__( 'Not verified yet: contact details are hidden on the live site until "verified by" and "verified on" are filled in.', 'hcwa-core' ) . '</strong></p>';
		}
		if ( 'donation' === $group_key && ! hcwa_donation_confirmed() ) {
			echo '<p class="notice notice-warning inline"><strong>' . esc_html__( 'Not confirmed yet: no bank details, QR code or PayPal link show anywhere until HCWA finance confirms them in writing.', 'hcwa-core' ) . '</strong></p>';
		}
	}

	echo '<h2>' . esc_html__( 'Pages by role (pick the Vietnamese page; the English one is its translation)', 'hcwa-core' ) . '</h2><table class="form-table" role="presentation">';
	foreach ( hcwa_page_roles() as $role => $label ) {
		echo '<tr><th scope="row"><label for="hcwa-page-' . esc_attr( $role ) . '">' . esc_html( $label ) . '</label></th><td>';
		wp_dropdown_pages(
			array(
				'name'              => 'hcwa_pages[' . esc_attr( $role ) . ']',
				'id'                => 'hcwa-page-' . esc_attr( $role ),
				'selected'          => isset( $pages[ $role ] ) ? (int) $pages[ $role ] : 0,
				'show_option_none'  => __( '— None —', 'hcwa-core' ),
				'option_none_value' => 0,
				'lang'              => function_exists( 'pll_default_language' ) ? pll_default_language() : '',
			)
		);
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button();
	echo '</form></div>';
}
