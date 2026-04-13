<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ZAB_sanitize_booking_payload( $payload ) {
	$allowed_services = ZAB_get_service_options();
	$service_name     = isset( $payload['service_name'] ) ? sanitize_text_field( wp_unslash( $payload['service_name'] ) ) : '';

	if ( ! array_key_exists( $service_name, $allowed_services ) ) {
		$service_name = '';
	}

	return array(
		'patient_name'    => isset( $payload['patient_name'] ) ? sanitize_text_field( wp_unslash( $payload['patient_name'] ) ) : '',
		'patient_email'   => isset( $payload['patient_email'] ) ? sanitize_email( wp_unslash( $payload['patient_email'] ) ) : '',
		'patient_phone'   => isset( $payload['patient_phone'] ) ? sanitize_text_field( wp_unslash( $payload['patient_phone'] ) ) : '',
		'appointment_date'=> isset( $payload['appointment_date'] ) ? sanitize_text_field( wp_unslash( $payload['appointment_date'] ) ) : '',
		'appointment_time'=> isset( $payload['appointment_time'] ) ? sanitize_text_field( wp_unslash( $payload['appointment_time'] ) ) : '',
		'service_name'    => $service_name,
		'notes'           => isset( $payload['notes'] ) ? sanitize_textarea_field( wp_unslash( $payload['notes'] ) ) : '',
	);
}

function ZAB_get_default_service_list() {
	return array(
		'Behavioral Health',
		'Asthma Treatment',
		'Hemorrhoids Treatment',
		'Annual Physicals',
		'Chronic Condition Management',
		'Contraceptive Counseling',
		'Dental Clearance',
		'Osteopathic Manipulative Therapy',
		'Diabetes Management',
		'Arthritis',
		'Chronic Kidney Disease',
		'Headaches',
		'Erectile Dysfunction',
		'Weight Management',
		'Preoperative Clearance',
		'High Cholesterol Treatment',
		'Flu Shots',
		'Immunizations',
		'Laryngitis Treatment',
		'Hypertension',
		"Women's Health",
		'Transitional Care Management',
		'Medicare Annual Wellness Visits',
		"Men's Health",
		'Menopause Treatment',
		'Sports Physicals',
		'PCOS Treatment',
		'Telemedicine',
		'School, Camp & Sports Physicals',
		'Thyroid Disease',
		'Employment Physical',
	);
}

function ZAB_sanitize_service_list( $services ) {
	if ( ! is_array( $services ) ) {
		return ZAB_get_default_service_list();
	}

	$clean = array();

	foreach ( $services as $service ) {
		$service = sanitize_text_field( wp_unslash( $service ) );
		$service = trim( $service );

		if ( '' === $service ) {
			continue;
		}

		$clean[] = $service;
	}

	$clean = array_values( array_unique( $clean ) );

	if ( empty( $clean ) ) {
		return ZAB_get_default_service_list();
	}

	return $clean;
}

function ZAB_services_to_options( $services ) {
	$options = array();

	foreach ( $services as $service ) {
		$options[ $service ] = __( $service, 'appointment-system' );
	}

	return $options;
}

function ZAB_get_service_options() {
	$services = get_option( 'ZAB_services', ZAB_get_default_service_list() );
	$services = ZAB_sanitize_service_list( $services );

	return ZAB_services_to_options( $services );
}

function ZAB_get_branding_defaults() {
	return array(
		'primary_color'      => '#006A94',
		'primary_dark_color' => '#00516e',
		'support_color'      => '#ff9a2f',
	);
}

function ZAB_get_branding_settings() {
	$defaults = ZAB_get_branding_defaults();
	$stored   = get_option( 'ZAB_branding_settings', array() );

	return wp_parse_args( $stored, $defaults );
}

function ZAB_hex_to_rgb_string( $hex_color ) {
	$hex_color = ltrim( (string) $hex_color, '#' );

	if ( 3 === strlen( $hex_color ) ) {
		$hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
	}

	if ( 6 !== strlen( $hex_color ) ) {
		return '0, 106, 148';
	}

	return implode(
		', ',
		array(
			hexdec( substr( $hex_color, 0, 2 ) ),
			hexdec( substr( $hex_color, 2, 2 ) ),
			hexdec( substr( $hex_color, 4, 2 ) ),
		)
	);
}

function ZAB_get_branding_style_attribute() {
	$branding = ZAB_get_branding_settings();
	$primary  = sanitize_hex_color( $branding['primary_color'] );
	$primary_dark = sanitize_hex_color( $branding['primary_dark_color'] );
	$support  = sanitize_hex_color( $branding['support_color'] );

	$primary      = $primary ? $primary : '#006A94';
	$primary_dark = $primary_dark ? $primary_dark : '#00516e';
	$support      = $support ? $support : '#ff9a2f';

	return sprintf(
		'--ZAB-primary:%1$s;--ZAB-primary-rgb:%2$s;--ZAB-primary-dark:%3$s;--ZAB-primary-dark-rgb:%4$s;--ZAB-support:%5$s;--ZAB-support-rgb:%6$s;',
		esc_attr( $primary ),
		esc_attr( ZAB_hex_to_rgb_string( $primary ) ),
		esc_attr( $primary_dark ),
		esc_attr( ZAB_hex_to_rgb_string( $primary_dark ) ),
		esc_attr( $support ),
		esc_attr( ZAB_hex_to_rgb_string( $support ) )
	);
}

function ZAB_get_email_template_defaults() {
	return array(
		'patient_email_template' => '<h2>Appointment received</h2><p>Hi {{patient_name}},</p><p>Thank you for booking with {{site_name}}. We received your appointment request for <strong>{{service_name}}</strong> on {{appointment_date}} at {{appointment_time}}.</p><p>We will review it shortly.</p><p>If you need to update your request, please reply to this email.</p>',
		'admin_email_template'   => '<h2>New appointment request</h2><p>A new appointment was submitted on {{site_name}}.</p><ul><li><strong>Patient:</strong> {{patient_name}}</li><li><strong>Email:</strong> {{patient_email}}</li><li><strong>Phone:</strong> {{patient_phone}}</li><li><strong>Service:</strong> {{service_name}}</li><li><strong>Date:</strong> {{appointment_date}}</li><li><strong>Time:</strong> {{appointment_time}}</li><li><strong>Status:</strong> {{status_label}}</li></ul><p><strong>Notes</strong></p><p>{{notes}}</p>',
		'status_email_template'  => '<h2>Appointment status update</h2><p>Hi {{patient_name}},</p><p>Your appointment status has been updated to <strong>{{status_label}}</strong>.</p><ul><li><strong>Service:</strong> {{service_name}}</li><li><strong>Date:</strong> {{appointment_date}}</li><li><strong>Time:</strong> {{appointment_time}}</li></ul><p>If you have questions, please reply to this email.</p>',
	);
}

function ZAB_get_email_template_settings() {
	$defaults = ZAB_get_email_template_defaults();
	$stored   = get_option( 'ZAB_email_templates', array() );

	return wp_parse_args( $stored, $defaults );
}

function ZAB_sanitize_email_template( $template ) {
	$template = is_string( $template ) ? wp_unslash( $template ) : '';
	$template = str_replace( array( "\r\n", "\r" ), "\n", $template );

	return wp_kses_post( trim( $template ) );
}

function ZAB_render_css_custom_properties() {
	return ZAB_get_branding_style_attribute();
}

function ZAB_validate_booking_payload( $data ) {
	$errors = array();

	if ( '' === $data['patient_name'] ) {
		$errors[] = __( 'Please enter the patient name.', 'appointment-system' );
	}

	if ( ! is_email( $data['patient_email'] ) ) {
		$errors[] = __( 'Please enter a valid email address.', 'appointment-system' );
	}

	if ( '' === $data['patient_phone'] ) {
		$errors[] = __( 'Please enter the phone number.', 'appointment-system' );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['appointment_date'] ) ) {
		$errors[] = __( 'Please choose a valid appointment date.', 'appointment-system' );
	}

	if ( ! preg_match( '/^\d{2}:\d{2}$/', $data['appointment_time'] ) ) {
		$errors[] = __( 'Please choose a valid appointment time.', 'appointment-system' );
	}

	if ( '' === $data['service_name'] ) {
		$errors[] = __( 'Please choose a service.', 'appointment-system' );
	}

	$requested_date = strtotime( $data['appointment_date'] );
	if ( $requested_date && $requested_date < strtotime( 'today' ) ) {
		$errors[] = __( 'Appointment date cannot be in the past.', 'appointment-system' );
	}

	return $errors;
}

function ZAB_booking_response( $success, $message, $extra = array() ) {
	$response = array_merge(
		array(
			'success' => (bool) $success,
			'message' => $message,
		),
		$extra
	);

	wp_send_json( $response );
}
