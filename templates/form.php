<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$services = ZAB_get_service_options();
$widget_style = ZAB_render_css_custom_properties();
?>
<div class="ZAB-booking-widget" style="<?php echo esc_attr( $widget_style ); ?>">
	<div class="ZAB-booking-shell">
		<div class="ZAB-booking-hero">
			<div class="ZAB-hero-copy">
				<span class="ZAB-eyebrow"><?php echo esc_html__( 'Online Booking', 'appointment-system' ); ?></span>
				<h2><?php echo esc_html__( 'Schedule care in a clean, guided flow.', 'appointment-system' ); ?></h2>
				<p><?php echo esc_html__( 'Pick a service, choose a time, enter your details, review everything, then submit the appointment request.', 'appointment-system' ); ?></p>
			</div>
			<div class="ZAB-hero-badge">
				<span><?php echo esc_html__( 'Business-focused booking experience', 'appointment-system' ); ?></span>
			</div>
		</div>

		<form class="ZAB-booking-form ZAB-booking-wizard" novalidate>
			<input type="hidden" name="action" value="ZAB_submit_booking" />
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ZAB_booking_nonce' ) ); ?>" />
			<input type="hidden" name="service_name" value="" data-ZAB-field="service_name" />
			<input type="hidden" name="appointment_date" value="" data-ZAB-field="appointment_date" />
			<input type="hidden" name="appointment_time" value="" data-ZAB-field="appointment_time" />

			<div class="ZAB-progress-rail" aria-label="Booking progress">
				<div class="ZAB-progress-step is-active" data-step-indicator="0">
					<span>1</span>
					<small><?php echo esc_html__( 'Choose Service', 'appointment-system' ); ?></small>
				</div>
				<div class="ZAB-progress-step" data-step-indicator="1">
					<span>2</span>
					<small><?php echo esc_html__( 'Your Details', 'appointment-system' ); ?></small>
				</div>
				<div class="ZAB-progress-step" data-step-indicator="2">
					<span>3</span>
					<small><?php echo esc_html__( 'Review', 'appointment-system' ); ?></small>
				</div>
				<div class="ZAB-progress-step" data-step-indicator="3">
					<span>4</span>
					<small><?php echo esc_html__( 'Book', 'appointment-system' ); ?></small>
				</div>
			</div>

			<section class="ZAB-step is-active" data-ZAB-step="0">
				<div class="ZAB-step-header">
					<span class="ZAB-step-tag"><?php echo esc_html__( 'Step 1', 'appointment-system' ); ?></span>
					<h3><?php echo esc_html__( 'Select a service, date, and time', 'appointment-system' ); ?></h3>
					<p><?php echo esc_html__( 'Start with the service you need, then choose the best appointment slot.', 'appointment-system' ); ?></p>
				</div>

				<div class="ZAB-section-panel">
					<div class="ZAB-section-title-row">
						<h4><?php echo esc_html__( 'Services Offered', 'appointment-system' ); ?></h4>
						<span><?php echo esc_html__( 'Tap one option', 'appointment-system' ); ?></span>
					</div>
					<div class="ZAB-service-grid">
						<?php foreach ( $services as $value => $label ) : ?>
							<button type="button" class="ZAB-service-card" data-service-value="<?php echo esc_attr( $value ); ?>">
								<span><?php echo esc_html( $label ); ?></span>
								<i aria-hidden="true">+</i>
							</button>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="ZAB-section-panel ZAB-schedule-panel">
					<div class="ZAB-section-title-row">
						<h4><?php echo esc_html__( 'Date & Time Selection', 'appointment-system' ); ?></h4>
						<span><?php echo esc_html__( 'Choose one date and one available slot', 'appointment-system' ); ?></span>
					</div>
					<div class="ZAB-schedule-layout">
						<label class="ZAB-field ZAB-field-date">
							<span><?php echo esc_html__( 'Appointment Date', 'appointment-system' ); ?></span>
							<input type="date" name="appointment_date_visible" data-ZAB-date-picker required />
						</label>
						<div class="ZAB-slot-column">
							<div class="ZAB-slot-column-header">
								<strong><?php echo esc_html__( 'Available Times', 'appointment-system' ); ?></strong>
								<span data-ZAB-selected-date-label><?php echo esc_html__( 'Pick a date first', 'appointment-system' ); ?></span>
							</div>
							<div class="ZAB-slot-grid" data-ZAB-slot-grid></div>
						</div>
					</div>
				</div>

				<div class="ZAB-step-actions">
					<div></div>
					<button type="button" class="ZAB-button ZAB-button-primary" data-ZAB-next><?php echo esc_html__( 'Continue', 'appointment-system' ); ?></button>
				</div>
			</section>

			<section class="ZAB-step" data-ZAB-step="1">
				<div class="ZAB-step-header">
					<span class="ZAB-step-tag"><?php echo esc_html__( 'Step 2', 'appointment-system' ); ?></span>
					<h3><?php echo esc_html__( 'Tell us who the appointment is for', 'appointment-system' ); ?></h3>
					<p><?php echo esc_html__( 'Add the contact details we need to confirm the visit.', 'appointment-system' ); ?></p>
				</div>

				<div class="ZAB-section-panel">
					<div class="ZAB-field-grid">
						<label class="ZAB-field">
							<span><?php echo esc_html__( 'Full Name', 'appointment-system' ); ?></span>
							<input type="text" name="patient_name" required />
						</label>
						<label class="ZAB-field">
							<span><?php echo esc_html__( 'Email Address', 'appointment-system' ); ?></span>
							<input type="email" name="patient_email" required />
						</label>
						<label class="ZAB-field">
							<span><?php echo esc_html__( 'Phone Number', 'appointment-system' ); ?></span>
							<input type="text" name="patient_phone" required />
						</label>
						<label class="ZAB-field ZAB-field-full">
							<span><?php echo esc_html__( 'Notes', 'appointment-system' ); ?></span>
							<textarea name="notes" rows="5" placeholder="<?php echo esc_attr__( 'Add anything helpful for the appointment, such as symptoms or preferred contact method.', 'appointment-system' ); ?>"></textarea>
						</label>
					</div>
				</div>

				<div class="ZAB-step-actions">
					<button type="button" class="ZAB-button ZAB-button-secondary" data-ZAB-prev><?php echo esc_html__( 'Back', 'appointment-system' ); ?></button>
					<button type="button" class="ZAB-button ZAB-button-primary" data-ZAB-next><?php echo esc_html__( 'Review Appointment', 'appointment-system' ); ?></button>
				</div>
			</section>

			<section class="ZAB-step" data-ZAB-step="2">
				<div class="ZAB-step-header">
					<span class="ZAB-step-tag"><?php echo esc_html__( 'Step 3', 'appointment-system' ); ?></span>
					<h3><?php echo esc_html__( 'Confirm the appointment details', 'appointment-system' ); ?></h3>
					<p><?php echo esc_html__( 'Review everything before the final booking submission.', 'appointment-system' ); ?></p>
				</div>

				<div class="ZAB-review-grid">
					<div class="ZAB-review-card">
						<span><?php echo esc_html__( 'Service', 'appointment-system' ); ?></span>
						<strong data-ZAB-summary-service><?php echo esc_html__( 'Not selected', 'appointment-system' ); ?></strong>
					</div>
					<div class="ZAB-review-card">
						<span><?php echo esc_html__( 'Date', 'appointment-system' ); ?></span>
						<strong data-ZAB-summary-date><?php echo esc_html__( 'Not selected', 'appointment-system' ); ?></strong>
					</div>
					<div class="ZAB-review-card">
						<span><?php echo esc_html__( 'Time', 'appointment-system' ); ?></span>
						<strong data-ZAB-summary-time><?php echo esc_html__( 'Not selected', 'appointment-system' ); ?></strong>
					</div>
					<div class="ZAB-review-card">
						<span><?php echo esc_html__( 'Patient', 'appointment-system' ); ?></span>
						<strong data-ZAB-summary-patient><?php echo esc_html__( 'Not entered', 'appointment-system' ); ?></strong>
					</div>
				</div>

				<label class="ZAB-confirm-box">
					<input type="checkbox" name="confirm_details" value="1" required />
					<span><?php echo esc_html__( 'I confirm the details above are correct and I want to continue.', 'appointment-system' ); ?></span>
				</label>

				<div class="ZAB-step-actions">
					<button type="button" class="ZAB-button ZAB-button-secondary" data-ZAB-prev><?php echo esc_html__( 'Back', 'appointment-system' ); ?></button>
					<button type="button" class="ZAB-button ZAB-button-primary" data-ZAB-next><?php echo esc_html__( 'Continue to Book', 'appointment-system' ); ?></button>
				</div>
			</section>

			<section class="ZAB-step" data-ZAB-step="3">
				<div class="ZAB-step-header">
					<span class="ZAB-step-tag"><?php echo esc_html__( 'Step 4', 'appointment-system' ); ?></span>
					<h3><?php echo esc_html__( 'Book your appointment', 'appointment-system' ); ?></h3>
					<p><?php echo esc_html__( 'One click submits the request to the WordPress database.', 'appointment-system' ); ?></p>
				</div>

				<div class="ZAB-final-panel">
					<div class="ZAB-final-summary">
						<div>
							<span><?php echo esc_html__( 'Selected Service', 'appointment-system' ); ?></span>
							<strong data-ZAB-summary-service-final><?php echo esc_html__( 'Not selected', 'appointment-system' ); ?></strong>
						</div>
						<div>
							<span><?php echo esc_html__( 'Date & Time', 'appointment-system' ); ?></span>
							<strong data-ZAB-summary-datetime-final><?php echo esc_html__( 'Not selected', 'appointment-system' ); ?></strong>
						</div>
					</div>

					<p class="ZAB-final-note"><?php echo esc_html__( 'You can still go back to adjust any detail before final submission.', 'appointment-system' ); ?></p>
				</div>

				<div class="ZAB-step-actions ZAB-step-actions-final">
					<button type="button" class="ZAB-button ZAB-button-secondary" data-ZAB-prev><?php echo esc_html__( 'Back', 'appointment-system' ); ?></button>
					<button type="submit" class="ZAB-button ZAB-button-submit"><?php echo esc_html__( 'Book Appointment', 'appointment-system' ); ?></button>
				</div>

				<div class="ZAB-form-message" aria-live="polite"></div>
			</section>
		</form>
	</div>
</div>
