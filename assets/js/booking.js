(function () {
	var timeSlots = [
		'08:00', '08:30', '09:00', '09:30',
		'10:00', '10:30', '11:00', '11:30',
		'12:00', '12:30', '13:00', '13:30',
		'14:00', '14:30', '15:00', '15:30',
		'16:00'
	];

	function formatDateLabel(value) {
		if (!value) {
			return 'Pick a date first';
		}

		var parsed = new Date(value + 'T00:00:00');

		return parsed.toLocaleDateString(undefined, {
			weekday: 'long',
			month: 'long',
			day: 'numeric',
			year: 'numeric'
		});
	}

	function formatTimeLabel(value) {
		if (!value) {
			return 'Not selected';
		}

		var parts = value.split(':');
		var hours = Number(parts[0]);
		var minutes = parts[1];
		var suffix = hours >= 12 ? 'pm' : 'am';
		var normalizedHours = hours % 12 || 12;

		return String(normalizedHours).padStart(2, '0') + ':' + minutes + ' ' + suffix;
	}

	function setMessage(form, message, isError) {
		var output = form.querySelector('.ZAB-form-message');

		if (!output) {
			return;
		}

		output.textContent = message;
		output.classList.toggle('is-error', Boolean(isError));
		output.classList.toggle('is-success', !isError);
	}

	function getField(form, selector) {
		return form.querySelector(selector);
	}

	function updateSummary(form) {
		var serviceField = getField(form, '[data-ZAB-field="service_name"]');
		var dateField = getField(form, '[data-ZAB-field="appointment_date"]');
		var timeField = getField(form, '[data-ZAB-field="appointment_time"]');
		var patientName = form.querySelector('input[name="patient_name"]');

		var serviceLabel = serviceField && serviceField.value ? serviceField.value : 'Not selected';
		var dateLabel = dateField && dateField.value ? formatDateLabel(dateField.value) : 'Not selected';
		var timeLabel = timeField && timeField.value ? formatTimeLabel(timeField.value) : 'Not selected';
		var patientLabel = patientName && patientName.value ? patientName.value : 'Not entered';

		var serviceNodes = form.querySelectorAll('[data-ZAB-summary-service], [data-ZAB-summary-service-final]');
		var dateNodes = form.querySelectorAll('[data-ZAB-summary-date]');
		var timeNodes = form.querySelectorAll('[data-ZAB-summary-time]');
		var patientNodes = form.querySelectorAll('[data-ZAB-summary-patient]');
		var finalDateTimeNodes = form.querySelectorAll('[data-ZAB-summary-datetime-final]');

		serviceNodes.forEach(function (node) {
			node.textContent = serviceLabel;
		});

		dateNodes.forEach(function (node) {
			node.textContent = dateLabel;
		});

		timeNodes.forEach(function (node) {
			node.textContent = timeLabel;
		});

		patientNodes.forEach(function (node) {
			node.textContent = patientLabel;
		});

		finalDateTimeNodes.forEach(function (node) {
			node.textContent = dateLabel + ' · ' + timeLabel;
		});
	}

	function setStep(form, stepIndex) {
		var steps = form.querySelectorAll('[data-ZAB-step]');
		var indicators = form.querySelectorAll('[data-step-indicator]');

		form.dataset.currentStep = String(stepIndex);

		steps.forEach(function (step) {
			var current = Number(step.getAttribute('data-ZAB-step'));
			step.classList.toggle('is-active', current === stepIndex);
		});

		indicators.forEach(function (indicator) {
			var current = Number(indicator.getAttribute('data-step-indicator'));
			indicator.classList.toggle('is-active', current === stepIndex);
			indicator.classList.toggle('is-complete', current < stepIndex);
		});
	}

	function validateStep(form, stepIndex) {
		var errors = [];
		var serviceField = getField(form, '[data-ZAB-field="service_name"]');
		var dateField = getField(form, '[data-ZAB-field="appointment_date"]');
		var timeField = getField(form, '[data-ZAB-field="appointment_time"]');
		var confirmField = form.querySelector('input[name="confirm_details"]');

		if (stepIndex === 0) {
			if (!serviceField || !serviceField.value) {
				errors.push('Choose a service to continue.');
			}

			if (!dateField || !dateField.value) {
				errors.push('Choose an appointment date.');
			}

			if (!timeField || !timeField.value) {
				errors.push('Choose an appointment time.');
			}
		}

		if (stepIndex === 1) {
			var nameField = form.querySelector('input[name="patient_name"]');
			var emailField = form.querySelector('input[name="patient_email"]');
			var phoneField = form.querySelector('input[name="patient_phone"]');

			if (!nameField || !nameField.value.trim()) {
				errors.push('Enter the patient name.');
			}

			if (!emailField || !emailField.value.trim() || !emailField.checkValidity()) {
				errors.push('Enter a valid email address.');
			}

			if (!phoneField || !phoneField.value.trim()) {
				errors.push('Enter the phone number.');
			}
		}

		if (stepIndex === 2 && (!confirmField || !confirmField.checked)) {
			errors.push('Confirm the appointment details before continuing.');
		}

		if (errors.length) {
			setMessage(form, errors[0], true);
			return false;
		}

		return true;
	}

	function renderSlots(form, dateValue) {
		var grid = form.querySelector('[data-ZAB-slot-grid]');
		var label = form.querySelector('[data-ZAB-selected-date-label]');
		var hiddenTime = getField(form, '[data-ZAB-field="appointment_time"]');

		if (!grid) {
			return;
		}

		grid.innerHTML = '';

		timeSlots.forEach(function (timeValue) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'ZAB-slot-pill';
			button.dataset.slotValue = timeValue;
			button.textContent = formatTimeLabel(timeValue);

			button.addEventListener('click', function () {
				grid.querySelectorAll('.ZAB-slot-pill').forEach(function (item) {
					item.classList.remove('is-selected');
				});

				button.classList.add('is-selected');
				hiddenTime.value = timeValue;
				updateSummary(form);
			});

			grid.appendChild(button);
		});

		if (label) {
			label.textContent = formatDateLabel(dateValue);
		}

		if (hiddenTime) {
			hiddenTime.value = '';
		}

		updateSummary(form);
	}

	function resetWizard(form) {
		var dateField = getField(form, '[data-ZAB-field="appointment_date"]');
		var picker = form.querySelector('[data-ZAB-date-picker]');
		var today = new Date();
		var offset = today.getTimezoneOffset() * 60000;
		var localDate = new Date(today.getTime() - offset).toISOString().split('T')[0];

		form.reset();
		setStep(form, 0);
		setMessage(form, '', false);
		updateServiceState(form, '');

		if (picker && dateField) {
			picker.min = localDate;
			picker.value = localDate;
			dateField.value = localDate;
		}

		renderSlots(form, localDate);
	}

	function updateServiceState(form, serviceValue) {
		var hiddenField = getField(form, '[data-ZAB-field="service_name"]');

		if (hiddenField) {
			hiddenField.value = serviceValue || '';
		}

		form.querySelectorAll('[data-service-value]').forEach(function (button) {
			button.classList.toggle('is-selected', button.dataset.serviceValue === serviceValue);
		});

		updateSummary(form);
	}

	function initializeDatePicker(form) {
		var picker = form.querySelector('[data-ZAB-date-picker]');
		var dateField = getField(form, '[data-ZAB-field="appointment_date"]');
		var today = new Date();
		var offset = today.getTimezoneOffset() * 60000;
		var localDate = new Date(today.getTime() - offset).toISOString().split('T')[0];

		if (!picker || !dateField) {
			return;
		}

		picker.min = localDate;
		picker.value = localDate;
		dateField.value = localDate;

		if (!picker.dataset.ZABBound) {
			picker.dataset.ZABBound = 'true';
			picker.addEventListener('change', function () {
				dateField.value = picker.value;
				renderSlots(form, picker.value);
				updateSummary(form);
			});
		}
	}

	async function submitForm(event) {
		event.preventDefault();

		var form = event.currentTarget;
		var currentStep = Number(form.dataset.currentStep || '0');
		var submitButton = form.querySelector('button[type="submit"]');

		if (currentStep < 3) {
			return;
		}

		if (!validateStep(form, 0) || !validateStep(form, 1) || !validateStep(form, 2)) {
			setStep(form, 0);
			return;
		}

		setMessage(form, 'Submitting appointment...', false);
		submitButton.disabled = true;

		try {
			var formData = new FormData(form);
			var response = await fetch(ZABBooking.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			});

			var payload = await response.json();

			if (!payload.success) {
				setMessage(form, payload.message || 'Unable to submit the booking.', true);
				return;
			}

			resetWizard(form);
			setMessage(form, payload.message || 'Booking saved.', false);
		} catch (error) {
			setMessage(form, 'A network error occurred while submitting the booking.', true);
		} finally {
			submitButton.disabled = false;
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.querySelector('.ZAB-booking-form');

		if (!form) {
			return;
		}

		form.dataset.currentStep = '0';

		initializeDatePicker(form);
		renderSlots(form, getField(form, '[data-ZAB-field="appointment_date"]')?.value || '');
		updateSummary(form);

		form.addEventListener('click', function (event) {
			var nextButton = event.target.closest('[data-ZAB-next]');
			var prevButton = event.target.closest('[data-ZAB-prev]');
			var serviceButton = event.target.closest('[data-service-value]');
			var currentStep = Number(form.dataset.currentStep || '0');

			if (serviceButton) {
				updateServiceState(form, serviceButton.dataset.serviceValue);
				return;
			}

			if (nextButton) {
				if (!validateStep(form, currentStep)) {
					return;
				}

				setStep(form, Math.min(3, currentStep + 1));
				return;
			}

			if (prevButton) {
				setStep(form, Math.max(0, currentStep - 1));
			}
		});

		form.addEventListener('input', function () {
			updateSummary(form);
		});

		form.addEventListener('submit', submitForm);
	});
})();
