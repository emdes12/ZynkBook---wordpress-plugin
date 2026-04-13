<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_ZAB_submit_booking', 'ZAB_handle_submit_booking' );
add_action( 'wp_ajax_nopriv_ZAB_submit_booking', 'ZAB_handle_submit_booking' );

function ZAB_handle_submit_booking() {
	check_ajax_referer( 'ZAB_booking_nonce', 'nonce' );

	$payload = ZAB_sanitize_booking_payload( $_POST );
	$errors  = ZAB_validate_booking_payload( $payload );

	if ( ! empty( $errors ) ) {
		ZAB_booking_response(
			false,
			implode( ' ', $errors )
		);
	}

	$booking_id = ZAB_insert_booking( $payload );

	if ( is_wp_error( $booking_id ) ) {
		ZAB_booking_response( false, $booking_id->get_error_message() );
	}

	$email_results = ZAB_send_booking_notifications( $booking_id );
	$email_message  = __( 'Your appointment request has been received.', 'appointment-system' );

	if ( is_wp_error( $email_results ) ) {
		$error_data    = $email_results->get_error_data();
		$failures_text = '';

		if ( is_array( $error_data ) && ! empty( $error_data['failures'] ) && is_array( $error_data['failures'] ) ) {
			$failures_text = ' ' . implode( ' | ', array_map( 'sanitize_text_field', $error_data['failures'] ) );
		}

		$email_message = __( 'Your appointment request was saved, but email delivery failed.', 'appointment-system' ) . $failures_text;
	}

	ZAB_booking_response(
		true,
		$email_message,
		array(
			'bookingId' => $booking_id,
			'emails'    => is_wp_error( $email_results ) ? array() : $email_results,
		)
	);
}
