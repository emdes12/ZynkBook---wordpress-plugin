<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'ZAB_register_admin_menu' );
add_action( 'admin_enqueue_scripts', 'ZAB_enqueue_admin_assets' );
add_action( 'admin_post_ZAB_update_booking_status', 'ZAB_handle_update_booking_status' );
add_action( 'admin_post_ZAB_save_email_settings', 'ZAB_handle_save_email_settings' );

function ZAB_register_admin_menu() {
	add_menu_page(
		__( 'ZynkBook Admin', 'appointment-system' ),
		__( 'ZynkBook Admin', 'appointment-system' ),
		'manage_options',
		'ZAB-bookings',
		'ZAB_render_admin_page',
		'dashicons-calendar-alt',
		26
	);

	add_submenu_page(
		'ZAB-bookings',
		__( 'Admin/Bookings List', 'appointment-system' ),
		__( 'Admin/Bookings List', 'appointment-system' ),
		'manage_options',
		'ZAB-bookings',
		'ZAB_render_admin_page'
	);

	add_submenu_page(
		'ZAB-bookings',
		__( 'Email Settings', 'appointment-system' ),
		__( 'Email Settings', 'appointment-system' ),
		'manage_options',
		'ZAB-settings',
		'ZAB_render_email_settings_page'
	);
}

function ZAB_enqueue_admin_assets( $hook ) {
	if ( 'toplevel_page_ZAB-bookings' !== $hook && 'bookings_page_ZAB-settings' !== $hook && 'ZAB-bookings_page_ZAB-settings' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'ZAB-admin-style', ZAB_PLUGIN_URL . 'assets/css/admin.css', array(), ZAB_VERSION );
}

function ZAB_admin_notice( $message, $type = 'success' ) {
	$classes = 'notice notice-' . ( 'success' === $type ? 'success' : 'error' );
	echo '<div class="' . esc_attr( $classes ) . '"><p>' . esc_html( $message ) . '</p></div>';
}

function ZAB_admin_message_from_query() {
	if ( empty( $_GET['ZAB_message'] ) ) {
		return '';
	}

	return sanitize_text_field( wp_unslash( $_GET['ZAB_message'] ) );
}

function ZAB_handle_update_booking_status() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'appointment-system' ) );
	}

	$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
	$status     = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

	check_admin_referer( 'ZAB_update_booking_status_' . $booking_id );

	$booking = ZAB_get_booking( $booking_id );

	if ( ! $booking ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'ZAB-bookings', 'ZAB_message' => rawurlencode( 'Booking not found.' ) ), admin_url( 'admin.php' ) ) );
		exit;
	}

	$result = ZAB_update_booking_status( $booking_id, $status );

	if ( is_wp_error( $result ) ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'ZAB-bookings', 'ZAB_message' => rawurlencode( $result->get_error_message() ) ), admin_url( 'admin.php' ) ) );
		exit;
	}

	if ( $booking->status !== $status ) {
		ZAB_send_booking_status_update( $booking_id, $status );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'        => 'ZAB-bookings',
				'ZAB_message' => rawurlencode( __( 'Booking status updated.', 'appointment-system' ) ),
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

function ZAB_handle_save_email_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'appointment-system' ) );
	}

	check_admin_referer( 'ZAB_save_email_settings' );
	ZAB_save_email_settings( $_POST );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'        => 'ZAB-settings',
				'ZAB_message' => rawurlencode( __( 'Email settings saved.', 'appointment-system' ) ),
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

function ZAB_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'appointment-system' ) );
	}

	$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
	$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$bookings      = ZAB_get_bookings(
		array(
			'limit'  => 200,
			'status' => $status_filter,
			'search' => $search,
		)
	);
	$counts        = ZAB_get_booking_counts();
	$statuses      = ZAB_get_booking_statuses();
	$notice        = ZAB_admin_message_from_query();
	$page_url      = admin_url( 'admin.php?page=ZAB-bookings' );
	?>
	<div class="wrap ZAB-admin-wrap">
		<h1><?php echo esc_html__( 'Clinic Bookings', 'appointment-system' ); ?></h1>
		<p class="description"><?php echo esc_html__( 'Search appointments, filter by status, and move bookings through the approval workflow.', 'appointment-system' ); ?></p>

		<?php if ( $notice ) : ?>
			<?php ZAB_admin_notice( $notice ); ?>
		<?php endif; ?>

		<div class="ZAB-admin-action-strip">
			<a class="ZAB-admin-action-link" href="https://buymeacoffee.com/alawiye" target="_blank" rel="noopener noreferrer">
				<strong><?php echo esc_html__( 'Support the developer', 'appointment-system' ); ?></strong>
				<span><?php echo esc_html__( 'Buy me a coffee', 'appointment-system' ); ?></span>
			</a>
			<a class="ZAB-admin-action-link" href="mailto:naijabayz@gmail.com">
				<strong><?php echo esc_html__( 'Give a review / Feedback', 'appointment-system' ); ?></strong>
				<span><?php echo esc_html__( 'Send a quick email', 'appointment-system' ); ?></span>
			</a>
			<a class="ZAB-admin-action-link" href="https://alawiye.netlify.app/" target="_blank" rel="noopener noreferrer">
				<strong><?php echo esc_html__( 'Contact / Request Service', 'appointment-system' ); ?></strong>
				<span><?php echo esc_html__( 'Visit the contact page', 'appointment-system' ); ?></span>
			</a>
		</div>

		<div class="ZAB-admin-stats-grid">
			<div class="ZAB-admin-stat-card">
				<span><?php echo esc_html__( 'Total Bookings', 'appointment-system' ); ?></span>
				<strong><?php echo esc_html( $counts['total'] ); ?></strong>
			</div>
			<div class="ZAB-admin-stat-card is-blue">
				<span><?php echo esc_html__( 'Pending', 'appointment-system' ); ?></span>
				<strong><?php echo esc_html( $counts['pending'] ); ?></strong>
			</div>
			<div class="ZAB-admin-stat-card is-orange">
				<span><?php echo esc_html__( 'Approved', 'appointment-system' ); ?></span>
				<strong><?php echo esc_html( $counts['approved'] ); ?></strong>
			</div>
			<div class="ZAB-admin-stat-card is-green">
				<span><?php echo esc_html__( 'Visited', 'appointment-system' ); ?></span>
				<strong><?php echo esc_html( $counts['visited'] ); ?></strong>
			</div>
			<div class="ZAB-admin-stat-card is-red">
				<span><?php echo esc_html__( 'Cancelled', 'appointment-system' ); ?></span>
				<strong><?php echo esc_html( $counts['cancelled'] ); ?></strong>
			</div>
		</div>

		<div class="ZAB-admin-panel">
			<div class="ZAB-admin-panel-header">
				<h2><?php echo esc_html__( 'Bookings', 'appointment-system' ); ?></h2>
				<span><?php echo esc_html__( 'All appointment requests are stored locally in the WordPress database.', 'appointment-system' ); ?></span>
			</div>

			<form class="ZAB-admin-toolbar" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="ZAB-bookings" />
				<label>
					<span><?php echo esc_html__( 'Search', 'appointment-system' ); ?></span>
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr__( 'Patient, email, phone, service', 'appointment-system' ); ?>" />
				</label>
				<label>
					<span><?php echo esc_html__( 'Status', 'appointment-system' ); ?></span>
					<select name="status">
						<option value=""><?php echo esc_html__( 'All statuses', 'appointment-system' ); ?></option>
						<?php foreach ( $statuses as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status_filter, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button type="submit" class="button button-primary"><?php echo esc_html__( 'Filter', 'appointment-system' ); ?></button>
				<a class="button" href="<?php echo esc_url( $page_url ); ?>"><?php echo esc_html__( 'Reset', 'appointment-system' ); ?></a>
			</form>

			<div class="ZAB-bookings-table-wrap">
				<table class="widefat striped ZAB-bookings-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Patient', 'appointment-system' ); ?></th>
							<th><?php echo esc_html__( 'Contact', 'appointment-system' ); ?></th>
							<th><?php echo esc_html__( 'Service', 'appointment-system' ); ?></th>
							<th><?php echo esc_html__( 'Appointment', 'appointment-system' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'appointment-system' ); ?></th>
							<th><?php echo esc_html__( 'Created', 'appointment-system' ); ?></th>
							<th><?php echo esc_html__( 'Action', 'appointment-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $bookings ) ) : ?>
							<tr>
								<td colspan="7"><?php echo esc_html__( 'No bookings match the current filter.', 'appointment-system' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $bookings as $booking ) : ?>
								<?php $row_status_label = isset( $statuses[ $booking->status ] ) ? $statuses[ $booking->status ] : ucfirst( $booking->status ); ?>
								<tr>
									<td>
										<strong class="ZAB-patient-name"><?php echo esc_html( $booking->patient_name ); ?></strong>
										<div class="ZAB-secondary-text">#<?php echo esc_html( $booking->id ); ?></div>
									</td>
									<td>
										<div><?php echo esc_html( $booking->patient_email ); ?></div>
										<div class="ZAB-secondary-text"><?php echo esc_html( $booking->patient_phone ); ?></div>
									</td>
									<td><?php echo esc_html( $booking->service_name ); ?></td>
									<td>
										<div><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->appointment_date ) ) ); ?></div>
										<div class="ZAB-secondary-text"><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking->appointment_time ) ) ); ?></div>
									</td>
									<td>
										<span class="ZAB-status-badge status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( $row_status_label ); ?></span>
									</td>
									<td class="ZAB-secondary-text"><?php echo esc_html( $booking->created_at ); ?></td>
									<td>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ZAB-inline-status-form">
											<input type="hidden" name="action" value="ZAB_update_booking_status" />
											<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->id ); ?>" />
											<?php wp_nonce_field( 'ZAB_update_booking_status_' . $booking->id ); ?>
											<select name="status">
												<?php foreach ( $statuses as $value => $label ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $booking->status, $value ); ?>><?php echo esc_html( $label ); ?></option>
												<?php endforeach; ?>
											</select>
											<button type="submit" class="button button-secondary"><?php echo esc_html__( 'Update', 'appointment-system' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}

function ZAB_render_email_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'appointment-system' ) );
	}

	$settings        = ZAB_get_email_settings();
	$branding        = ZAB_get_branding_settings();
	$email_templates = ZAB_get_email_template_settings();
	$services        = get_option( 'ZAB_services', ZAB_get_default_service_list() );

	if ( ! is_array( $services ) ) {
		$services = ZAB_get_default_service_list();
	}

	$services_text = implode( "\n", $services );
	$notice        = ZAB_admin_message_from_query();
	?>
	<div class="wrap ZAB-admin-wrap ZAB-settings-wrap">
		<h1><?php echo esc_html__( 'Customization Settings', 'appointment-system' ); ?></h1>
		<p class="description"><?php echo esc_html__( 'Configure branding colors, editable services, and the email content sent to patients and admins.', 'appointment-system' ); ?></p>

		<?php if ( $notice ) : ?>
			<?php ZAB_admin_notice( $notice ); ?>
		<?php endif; ?>

		<div class="ZAB-admin-panel ZAB-admin-settings-panel">
			<div class="ZAB-settings-intro">
				<div class="ZAB-settings-pill"><?php echo esc_html__( 'Design', 'appointment-system' ); ?></div>
				<div class="ZAB-settings-pill"><?php echo esc_html__( 'Services', 'appointment-system' ); ?></div>
				<div class="ZAB-settings-pill"><?php echo esc_html__( 'Email Templates', 'appointment-system' ); ?></div>
				<div class="ZAB-settings-pill"><?php echo esc_html__( 'Plunk Integration', 'appointment-system' ); ?></div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ZAB-settings-form">
				<input type="hidden" name="action" value="ZAB_save_email_settings" />
				<?php wp_nonce_field( 'ZAB_save_email_settings' ); ?>

				<div class="ZAB-settings-section">
					<div class="ZAB-admin-panel-header">
						<h2><?php echo esc_html__( 'Brand Colors', 'appointment-system' ); ?></h2>
						<span><?php echo esc_html__( 'Set the primary colors used by the booking widget.', 'appointment-system' ); ?></span>
					</div>
					<div class="ZAB-field-grid-admin">
						<label>
							<span><?php echo esc_html__( 'Primary Color', 'appointment-system' ); ?></span>
							<input type="color" name="primary_color" value="<?php echo esc_attr( $branding['primary_color'] ); ?>" />
						</label>
						<label>
							<span><?php echo esc_html__( 'Primary Dark Color', 'appointment-system' ); ?></span>
							<input type="color" name="primary_dark_color" value="<?php echo esc_attr( $branding['primary_dark_color'] ); ?>" />
						</label>
						<label>
							<span><?php echo esc_html__( 'Support Color', 'appointment-system' ); ?></span>
							<input type="color" name="support_color" value="<?php echo esc_attr( $branding['support_color'] ); ?>" />
						</label>
					</div>
				</div>

				<div class="ZAB-settings-section">
					<div class="ZAB-admin-panel-header">
						<h2><?php echo esc_html__( 'Services', 'appointment-system' ); ?></h2>
						<span><?php echo esc_html__( 'Edit the services shown to patients. One service per line.', 'appointment-system' ); ?></span>
					</div>
					<label class="ZAB-textarea-field">
						<span><?php echo esc_html__( 'Service List', 'appointment-system' ); ?></span>
						<textarea name="services_text" rows="12" placeholder="<?php echo esc_attr__( 'Enter one service per line.', 'appointment-system' ); ?>"><?php echo esc_textarea( $services_text ); ?></textarea>
					</label>
				</div>

				<div class="ZAB-settings-section">
					<div class="ZAB-admin-panel-header">
						<h2><?php echo esc_html__( 'Plunk Credentials', 'appointment-system' ); ?></h2>
						<span><?php echo esc_html__( 'Connect your Plunk account to send confirmations and notifications.', 'appointment-system' ); ?></span>
					</div>

					<div class="ZAB-field-grid-admin">
						<label>
							<span><?php echo esc_html__( 'Plunk Public Key', 'appointment-system' ); ?></span>
							<input type="text" name="plunk_public_key" value="<?php echo esc_attr( $settings['plunk_public_key'] ); ?>" />
						</label>
						<label>
							<span><?php echo esc_html__( 'Plunk Secret Key', 'appointment-system' ); ?></span>
							<input type="password" name="plunk_secret_key" value="<?php echo esc_attr( $settings['plunk_secret_key'] ); ?>" />
						</label>
						<label>
							<span><?php echo esc_html__( 'From Name', 'appointment-system' ); ?></span>
							<input type="text" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" />
						</label>
						<label>
							<span><?php echo esc_html__( 'From Email', 'appointment-system' ); ?></span>
							<input type="email" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" />
						</label>
						<label>
							<span><?php echo esc_html__( 'Admin Notification Email', 'appointment-system' ); ?></span>
							<input type="email" name="admin_email" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" />
						</label>
					</div>

					<div class="ZAB-toggle-row">
						<label><input type="checkbox" name="patient_confirm" value="1" <?php checked( '1', $settings['patient_confirm'] ); ?> /> <?php echo esc_html__( 'Send patient confirmation email', 'appointment-system' ); ?></label>
						<label><input type="checkbox" name="admin_notify" value="1" <?php checked( '1', $settings['admin_notify'] ); ?> /> <?php echo esc_html__( 'Send admin notification email', 'appointment-system' ); ?></label>
					</div>
				</div>

				<div class="ZAB-settings-section">
					<div class="ZAB-admin-panel-header">
						<h2><?php echo esc_html__( 'Plunk Setup Help', 'appointment-system' ); ?></h2>
						<span><?php echo esc_html__( 'How to get your Plunk public key and secret key.', 'appointment-system' ); ?></span>
					</div>
					<div class="ZAB-help-card">
						<ol>
							<li><?php echo esc_html__( 'Create or log in to your Plunk account.', 'appointment-system' ); ?></li>
							<li><?php echo esc_html__( 'Go to the project or workspace where you want to send emails from.', 'appointment-system' ); ?></li>
							<li><?php echo esc_html__( 'Open the API Keys section in your Plunk dashboard.', 'appointment-system' ); ?></li>
							<li><?php echo esc_html__( 'Copy your Public Key (starts with pk_) and paste it into Plunk Public Key below.', 'appointment-system' ); ?></li>
							<li><?php echo esc_html__( 'Copy your Secret Key (starts with sk_) and paste it into Plunk Secret Key below.', 'appointment-system' ); ?></li>
							<li><?php echo esc_html__( 'Set From Email to a sender address/domain that is verified in Plunk.', 'appointment-system' ); ?></li>
						</ol>
						<p>
							<a href="https://docs.useplunk.com" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Open Plunk documentation', 'appointment-system' ); ?></a>
							<?php echo esc_html__( 'or', 'appointment-system' ); ?>
							<a href="https://app.useplunk.com" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Open Plunk dashboard', 'appointment-system' ); ?></a>
						</p>
					</div>
				</div>

				<div class="ZAB-settings-section">
					<div class="ZAB-admin-panel-header">
						<h2><?php echo esc_html__( 'Email Templates', 'appointment-system' ); ?></h2>
						<span><?php echo esc_html__( 'Use the editor toolbar to format messages. Placeholders like {{patient_name}} and {{service_name}} still work.', 'appointment-system' ); ?></span>
					</div>
					<div class="ZAB-template-grid">
						<label class="ZAB-textarea-field">
							<span><?php echo esc_html__( 'Patient Email Message', 'appointment-system' ); ?></span>
							<div class="cbs-editor-box">
								<?php
								wp_editor(
									$email_templates['patient_email_template'],
									'cbs_patient_email_template',
									array(
										'textarea_name' => 'patient_email_template',
										'textarea_rows' => 12,
										'media_buttons' => false,
										'teeny'         => false,
										'quicktags'     => true,
										'tinymce'       => array(
											'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
											'toolbar2' => '',
										),
									)
								);
								?>
							</div>
						</label>
						<label class="ZAB-textarea-field">
							<span><?php echo esc_html__( 'Admin Email Message', 'appointment-system' ); ?></span>
							<div class="cbs-editor-box">
								<?php
								wp_editor(
									$email_templates['admin_email_template'],
									'cbs_admin_email_template',
									array(
										'textarea_name' => 'admin_email_template',
										'textarea_rows' => 12,
										'media_buttons' => false,
										'teeny'         => false,
										'quicktags'     => true,
										'tinymce'       => array(
											'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
											'toolbar2' => '',
										),
									)
								);
								?>
							</div>
						</label>
						<label class="ZAB-textarea-field ZAB-textarea-field-full">
							<span><?php echo esc_html__( 'Status Update Message', 'appointment-system' ); ?></span>
							<div class="cbs-editor-box">
								<?php
								wp_editor(
									$email_templates['status_email_template'],
									'cbs_status_email_template',
									array(
										'textarea_name' => 'status_email_template',
										'textarea_rows' => 12,
										'media_buttons' => false,
										'teeny'         => false,
										'quicktags'     => true,
										'tinymce'       => array(
											'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
											'toolbar2' => '',
										),
									)
								);
								?>
							</div>
						</label>
					</div>
					<p class="description"><?php echo esc_html__( 'Plunk secret keys are required for sending emails through the API. Use a verified sender email and domain in Plunk. The editor supports formatting such as bold text, links, and lists.', 'appointment-system' ); ?></p>
				</div>

				<button type="submit" class="button button-primary button-hero"><?php echo esc_html__( 'Save Email Settings', 'appointment-system' ); ?></button>
			</form>
		</div>
	</div>
	<?php
}