<?php
/**
 * Plugin Name: ZynkBook Appointment Booking System
 * Description: A flexible and lightweight appointment booking system designed for service-based businesses. Easily manage bookings, schedules, and customer appointments directly from your website.
 * Version: 1.0.0
 * Author: Alawiye Muritala (ShineInv Solutions)
 * Text Domain: appointment-system
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZAB_VERSION', '1.0.0' );
define( 'ZAB_PLUGIN_FILE', __FILE__ );
define( 'ZAB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZAB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ZAB_PLUGIN_DIR . 'includes/helpers.php';
require_once ZAB_PLUGIN_DIR . 'includes/db.php';
require_once ZAB_PLUGIN_DIR . 'includes/mail.php';
require_once ZAB_PLUGIN_DIR . 'includes/admin.php';
require_once ZAB_PLUGIN_DIR . 'includes/api.php';

register_activation_hook( __FILE__, 'ZAB_activate_plugin' );
register_deactivation_hook( __FILE__, 'ZAB_deactivate_plugin' );

add_action( 'plugins_loaded', 'ZAB_load_textdomain' );
add_action( 'wp_enqueue_scripts', 'ZAB_enqueue_assets' );
add_shortcode( 'appointment_booking_form', 'ZAB_render_booking_form_shortcode' );

function ZAB_load_textdomain() {
	load_plugin_textdomain( 'appointment-system', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function ZAB_enqueue_assets() {
	if ( ! is_singular() ) {
		return;
	}

	global $post;

	if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'appointment_booking_form' ) ) {
		return;
	}

	wp_enqueue_style( 'ZAB-style', ZAB_PLUGIN_URL . 'assets/css/style.css', array(), ZAB_VERSION );
	wp_enqueue_script( 'ZAB-booking', ZAB_PLUGIN_URL . 'assets/js/booking.js', array(), ZAB_VERSION, true );

	wp_localize_script(
		'ZAB-booking',
		'ZABBooking',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ZAB_booking_nonce' ),
		)
	);
}

function ZAB_render_booking_form_shortcode() {
	ZAB_enqueue_assets();
	ob_start();
	require ZAB_PLUGIN_DIR . 'templates/form.php';
	return ob_get_clean();
}
