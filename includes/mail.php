<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ZAB_get_email_settings_defaults() {
	return array(
		'plunk_public_key'  => 'pk_*******',
		'plunk_secret_key'  => 'sk_*******',
		'from_name'         => get_bloginfo( 'name' ),
		'from_email'        => get_option( 'admin_email' ),
		'admin_email'       => get_option( 'admin_email' ),
		'patient_confirm'   => '1',
		'admin_notify'      => '1',
		'patient_email_template' => ZAB_get_email_template_defaults()['patient_email_template'],
		'admin_email_template'   => ZAB_get_email_template_defaults()['admin_email_template'],
		'status_email_template'  => ZAB_get_email_template_defaults()['status_email_template'],
	);
}

function ZAB_get_email_settings() {
	return wp_parse_args( get_option( 'ZAB_email_settings', array() ), ZAB_get_email_settings_defaults() );
}

function ZAB_save_email_settings( $settings ) {
	$current = ZAB_get_email_settings();
	$merged  = wp_parse_args(
		array(
			'plunk_public_key'       => isset( $settings['plunk_public_key'] ) ? sanitize_text_field( wp_unslash( $settings['plunk_public_key'] ) ) : $current['plunk_public_key'],
			'plunk_secret_key'       => isset( $settings['plunk_secret_key'] ) ? sanitize_text_field( wp_unslash( $settings['plunk_secret_key'] ) ) : $current['plunk_secret_key'],
			'from_name'              => isset( $settings['from_name'] ) ? sanitize_text_field( wp_unslash( $settings['from_name'] ) ) : $current['from_name'],
			'from_email'             => isset( $settings['from_email'] ) ? sanitize_email( wp_unslash( $settings['from_email'] ) ) : $current['from_email'],
			'admin_email'            => isset( $settings['admin_email'] ) ? sanitize_email( wp_unslash( $settings['admin_email'] ) ) : $current['admin_email'],
			'patient_confirm'        => ! empty( $settings['patient_confirm'] ) ? '1' : '0',
			'admin_notify'           => ! empty( $settings['admin_notify'] ) ? '1' : '0',
			'patient_email_template' => isset( $settings['patient_email_template'] ) ? ZAB_sanitize_email_template( $settings['patient_email_template'] ) : $current['patient_email_template'],
			'admin_email_template'   => isset( $settings['admin_email_template'] ) ? ZAB_sanitize_email_template( $settings['admin_email_template'] ) : $current['admin_email_template'],
			'status_email_template'  => isset( $settings['status_email_template'] ) ? ZAB_sanitize_email_template( $settings['status_email_template'] ) : $current['status_email_template'],
		),
		$current
	);

	update_option( 'ZAB_email_settings', $merged, false );

	if ( isset( $settings['primary_color'] ) || isset( $settings['primary_dark_color'] ) || isset( $settings['support_color'] ) ) {
		update_option(
			'ZAB_branding_settings',
			array(
				'primary_color'      => isset( $settings['primary_color'] ) ? sanitize_hex_color( wp_unslash( $settings['primary_color'] ) ) : ZAB_get_branding_defaults()['primary_color'],
				'primary_dark_color' => isset( $settings['primary_dark_color'] ) ? sanitize_hex_color( wp_unslash( $settings['primary_dark_color'] ) ) : ZAB_get_branding_defaults()['primary_dark_color'],
				'support_color'      => isset( $settings['support_color'] ) ? sanitize_hex_color( wp_unslash( $settings['support_color'] ) ) : ZAB_get_branding_defaults()['support_color'],
			),
			false
		);
	}

	if ( isset( $settings['services'] ) ) {
		update_option( 'ZAB_services', ZAB_sanitize_service_list( $settings['services'] ), false );
	} elseif ( isset( $settings['services_text'] ) ) {
		$services_text = is_string( $settings['services_text'] ) ? wp_unslash( $settings['services_text'] ) : '';
		$services      = preg_split( '/\r\n|\r|\n/', $services_text );
		update_option( 'ZAB_services', ZAB_sanitize_service_list( $services ), false );
	}

	return $merged;
}

function ZAB_replace_email_placeholders( $template, $booking, $status_label = '' ) {
	$appointment_time = date_i18n( get_option( 'time_format' ), strtotime( $booking->appointment_time ) );
	$appointment_date = date_i18n( get_option( 'date_format' ), strtotime( $booking->appointment_date ) );
	$context          = array(
		'{{site_name}}'        => get_bloginfo( 'name' ),
		'{{patient_name}}'     => $booking->patient_name,
		'{{patient_email}}'    => $booking->patient_email,
		'{{patient_phone}}'    => $booking->patient_phone,
		'{{service_name}}'     => $booking->service_name,
		'{{appointment_date}}' => $appointment_date,
		'{{appointment_time}}' => $appointment_time,
		'{{status_label}}'     => $status_label ? $status_label : ucfirst( $booking->status ),
		'{{notes}}'            => $booking->notes ? $booking->notes : __( 'No notes provided.', 'appointment-system' ),
	);

	return strtr( (string) $template, $context );
}


function ZAB_render_email_template_html( $template, $booking, $status_label = '' ) {
	$template = ZAB_replace_email_placeholders( $template, $booking, $status_label );
	$template = trim( (string) $template );

	if ( preg_match( '/<\/?[a-z][\s\S]*>/i', $template ) ) {
		$html = wp_kses_post( $template );
	} else {
		$html = wp_kses_post( wpautop( $template ) );
	}

	return '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#163047">' . $html . '</div>';
}

function ZAB_plunk_send_email( $to, $subject, $html_body, $reply_to = '' ) {
	$settings = ZAB_get_email_settings();
	$secret   = trim( (string) $settings['plunk_secret_key'] );
	$to       = sanitize_email( $to );
	$from     = sanitize_email( $settings['from_email'] );

	if ( '' === $secret ) {
		return new WP_Error( 'ZAB_missing_plunk_key', __( 'Plunk secret key is not configured.', 'appointment-system' ) );
	}

	if ( ! is_email( $to ) ) {
		return new WP_Error( 'ZAB_invalid_recipient_email', __( 'Recipient email is invalid.', 'appointment-system' ) );
	}

	if ( ! is_email( $from ) ) {
		return new WP_Error( 'ZAB_invalid_from_email', __( 'Sender email is invalid. Please update Email Settings.', 'appointment-system' ) );
	}

	$payload = array(
		'to'      => $to,
		'subject' => $subject,
		'body'    => $html_body,
		'from'    => array(
			'name'  => $settings['from_name'] ? $settings['from_name'] : get_bloginfo( 'name' ),
			'email' => $from,
		),
	);

	if ( '' !== $reply_to ) {
		$payload['reply'] = sanitize_email( $reply_to );
	}

	$endpoints  = array(
		'https://next-api.useplunk.com/v1/send',
		'https://api.useplunk.com/v1/send',
	);
	$last_error = null;

	foreach ( $endpoints as $endpoint ) {
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$last_error = new WP_Error(
				'ZAB_plunk_http_error',
				sprintf(
					/* translators: %s is the endpoint URL. */
					__( 'Unable to connect to Plunk endpoint: %s', 'appointment-system' ),
					$endpoint
				),
				array( 'details' => $response->get_error_message() )
			);
			continue;
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body, true );

		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$error_detail = '';
		if ( is_array( $body ) ) {
			if ( ! empty( $body['message'] ) ) {
				$error_detail = (string) $body['message'];
			} elseif ( ! empty( $body['error'] ) ) {
				$error_detail = is_string( $body['error'] ) ? $body['error'] : wp_json_encode( $body['error'] );
			}
		}

		if ( '' === $error_detail && is_string( $raw_body ) ) {
			$error_detail = trim( wp_strip_all_tags( $raw_body ) );
		}

		$last_error = new WP_Error(
			'ZAB_plunk_send_failed',
			sprintf(
				/* translators: 1: HTTP status code, 2: endpoint URL. */
				__( 'Plunk email delivery failed (HTTP %1$d) via %2$s.', 'appointment-system' ),
				$code,
				$endpoint
			),
			array( 'details' => $error_detail )
		);
	}

	if ( is_wp_error( $last_error ) ) {
		return $last_error;
	}

	return new WP_Error( 'ZAB_plunk_send_failed', __( 'Plunk email delivery failed.', 'appointment-system' ) );
}

function ZAB_booking_email_subject( $booking, $type ) {
	if ( 'admin' === $type ) {
		return sprintf(
			__( 'New appointment request from %s', 'appointment-system' ),
			$booking->patient_name
		);
	}

	return __( 'Your appointment request has been received', 'appointment-system' );
}

function ZAB_booking_email_body( $booking, $type ) {
	$templates = ZAB_get_email_template_settings();

	if ( 'admin' === $type ) {
		return ZAB_render_email_template_html( $templates['admin_email_template'], $booking, ucfirst( $booking->status ) );
	}

	return ZAB_render_email_template_html( $templates['patient_email_template'], $booking, ucfirst( $booking->status ) );
}

function ZAB_send_booking_notifications( $booking_id ) {
	$settings = ZAB_get_email_settings();
	$booking  = ZAB_get_booking( $booking_id );

	if ( ! $booking ) {
		return new WP_Error( 'ZAB_missing_booking', __( 'Booking not found for email notifications.', 'appointment-system' ) );
	}

	$results  = array();
	$failures = array();

	if ( '1' === (string) $settings['patient_confirm'] ) {
		$results['patient'] = ZAB_plunk_send_email(
			$booking->patient_email,
			ZAB_booking_email_subject( $booking, 'patient' ),
			ZAB_booking_email_body( $booking, 'patient' ),
			$settings['from_email']
		);

		if ( is_wp_error( $results['patient'] ) ) {
			$failure_details = $results['patient']->get_error_data();
			$failures[]      = 'Patient: ' . $results['patient']->get_error_message() . ( ! empty( $failure_details['details'] ) ? ' - ' . $failure_details['details'] : '' );
		}
	}

	if ( '1' === (string) $settings['admin_notify'] ) {
		$results['admin'] = ZAB_plunk_send_email(
			$settings['admin_email'] ? $settings['admin_email'] : get_option( 'admin_email' ),
			ZAB_booking_email_subject( $booking, 'admin' ),
			ZAB_booking_email_body( $booking, 'admin' ),
			$settings['from_email']
		);

		if ( is_wp_error( $results['admin'] ) ) {
			$failure_details = $results['admin']->get_error_data();
			$failures[]      = 'Admin: ' . $results['admin']->get_error_message() . ( ! empty( $failure_details['details'] ) ? ' - ' . $failure_details['details'] : '' );
		}
	}

	if ( ! empty( $failures ) ) {
		return new WP_Error(
			'ZAB_email_delivery_failed',
			__( 'One or more booking emails failed to send.', 'appointment-system' ),
			array(
				'failures' => $failures,
				'results'  => $results,
			)
		);
	}

	return $results;
}

function ZAB_send_booking_status_update( $booking_id, $new_status ) {
	$booking  = ZAB_get_booking( $booking_id );
	$settings = ZAB_get_email_settings();

	if ( ! $booking ) {
		return new WP_Error( 'ZAB_missing_booking', __( 'Booking not found for status update email.', 'appointment-system' ) );
	}

	$status_labels = ZAB_get_booking_statuses();
	$status_label  = isset( $status_labels[ $new_status ] ) ? $status_labels[ $new_status ] : ucfirst( $new_status );
	$subject       = sprintf(
		__( 'Your appointment status has been updated to %s', 'appointment-system' ),
		$status_label
	);
	$templates = ZAB_get_email_template_settings();
	$body      = ZAB_render_email_template_html( $templates['status_email_template'], $booking, $status_label );

	return ZAB_plunk_send_email(
		$booking->patient_email,
		$subject,
		$body,
		$settings['from_email']
	);
}