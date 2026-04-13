<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ZAB_activate_plugin() {
	ZAB_create_tables();
}

function ZAB_deactivate_plugin() {
	// Intentionally left blank. Data stays in the WordPress database.
}

function ZAB_create_tables() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'appointment_bookings';
	$charset_collate  = $wpdb->get_charset_collate();
	$max_index_length = 191;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		patient_name varchar(191) NOT NULL,
		patient_email varchar(191) NOT NULL,
		patient_phone varchar(50) NOT NULL,
		appointment_date date NOT NULL,
		appointment_time time NOT NULL,
		service_name varchar(191) NOT NULL,
		notes text NULL,
		status varchar(20) NOT NULL DEFAULT 'pending',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY appointment_date (appointment_date),
		KEY status (status)
	) {$charset_collate};";

	dbDelta( $sql );
}

function ZAB_insert_booking( $data ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'appointment_bookings';

	$result = $wpdb->insert(
		$table_name,
		array(
			'patient_name'     => $data['patient_name'],
			'patient_email'    => $data['patient_email'],
			'patient_phone'    => $data['patient_phone'],
			'appointment_date' => $data['appointment_date'],
			'appointment_time' => $data['appointment_time'] . ':00',
			'service_name'     => $data['service_name'],
			'notes'            => $data['notes'],
			'status'           => 'pending',
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		return new WP_Error( 'ZAB_insert_failed', __( 'Unable to save the booking.', 'appointment-system' ) );
	}

	return (int) $wpdb->insert_id;
}

function ZAB_get_booking_statuses() {
	return array(
		'pending'   => __( 'Pending', 'appointment-system' ),
		'approved'  => __( 'Approved', 'appointment-system' ),
		'cancelled' => __( 'Cancelled', 'appointment-system' ),
		'visited'   => __( 'Concluded / Visited', 'appointment-system' ),
	);
}

function ZAB_get_booking( $booking_id ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'appointment_bookings';

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d",
			absint( $booking_id )
		)
	);
}

function ZAB_update_booking_status( $booking_id, $status ) {
	global $wpdb;

	$allowed_statuses = array_keys( ZAB_get_booking_statuses() );

	if ( ! in_array( $status, $allowed_statuses, true ) ) {
		return new WP_Error( 'ZAB_invalid_status', __( 'Invalid booking status.', 'appointment-system' ) );
	}

	$table_name = $wpdb->prefix . 'appointment_bookings';
	$result     = $wpdb->update(
		$table_name,
		array( 'status' => $status ),
		array( 'id' => absint( $booking_id ) ),
		array( '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		return new WP_Error( 'ZAB_status_update_failed', __( 'Unable to update the booking status.', 'appointment-system' ) );
	}

	return true;
}

function ZAB_get_bookings( $args = array() ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'appointment_bookings';
	$args       = wp_parse_args(
		$args,
		array(
			'limit'  => 50,
			'search' => '',
			'status' => '',
		)
	);

	$limit  = max( 1, absint( $args['limit'] ) );
	$search = sanitize_text_field( $args['search'] );
	$status = sanitize_key( $args['status'] );

	$where   = array( '1=1' );
	$params  = array();

	if ( '' !== $status ) {
		$where[]  = 'status = %s';
		$params[] = $status;
	}

	if ( '' !== $search ) {
		$where[]  = '(patient_name LIKE %s OR patient_email LIKE %s OR patient_phone LIKE %s OR service_name LIKE %s)';
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}

	$sql = "SELECT * FROM {$table_name} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT %d';
	$params[] = $limit;

	return $wpdb->get_results(
		$wpdb->prepare( $sql, $params )
	);
}

function ZAB_get_booking_counts() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'appointment_bookings';
	$rows       = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table_name} GROUP BY status", ARRAY_A );
	$counts     = array_fill_keys( array_keys( ZAB_get_booking_statuses() ), 0 );
	$counts['total'] = 0;

	foreach ( $rows as $row ) {
		$status = isset( $row['status'] ) ? $row['status'] : '';
		$total  = isset( $row['total'] ) ? (int) $row['total'] : 0;

		if ( isset( $counts[ $status ] ) ) {
			$counts[ $status ] = $total;
		}

		$counts['total'] += $total;
	}

	return $counts;
}
