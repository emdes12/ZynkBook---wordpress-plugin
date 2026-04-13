# Appointment Booking System

A WordPress plugin for collecting appointment requests, managing bookings in the WordPress database, and sending confirmation emails through Plunk.

## Features

- Step-by-step appointment booking form
- Booking storage in the WordPress database
- Admin dashboard for viewing and updating booking status
- Search and status filters for bookings
- Editable services list
- Custom brand colors
- Email templates with Markdown support
- Plunk integration for patient and admin notifications

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later
- A valid Plunk account
- A verified sender email/domain in Plunk

## Installation

### Option 1: Upload as a plugin ZIP in WordPress

1. Make sure the plugin folder is named `appointment-booking-system`.
2. Zip the entire plugin folder.
3. In WordPress admin, go to `Plugins > Add New > Upload Plugin`.
4. Upload the ZIP file and click `Install Now`.
5. Activate the plugin.

### Option 2: Install manually

1. Copy the `appointment-booking-system` folder into `wp-content/plugins/`.
2. Go to `Plugins` in WordPress admin.
3. Activate `Appointment Booking System`.

## How to use it in WordPress

1. Create a new page or edit an existing page.
2. Add this shortcode to the page content:

   ```text
   [appointment_booking_form]
   ```

3. Publish the page.
4. Visit the page on the front end to view the booking form.

## Admin setup

After activation, open `ZynkBook Admin` in the WordPress admin menu.

### Booking dashboard

- View all bookings
- Search by patient name, email, phone, or service
- Filter by status
- Update booking status

### Customization settings

Open `ZynkBook Admin > Email Settings` to configure:

- Brand colors
- Services shown in the booking form
- Plunk public key and secret key
- Sender name and sender email
- Admin notification email
- Patient and admin email notifications
- Email templates for patient, admin, and status updates

## Plunk setup

Use the help section on the Email Settings page to find your keys.

Quick steps:

1. Log in to Plunk.
2. Open your project or workspace.
3. Go to the API Keys section.
4. Copy the Public Key (`pk_...`) into the public key field.
5. Copy the Secret Key (`sk_...`) into the secret key field.
6. Use a verified sender email/domain in Plunk.

## Editing services

The services list is editable from `ZynkBook > Email Settings`.

- Add one service per line
- Remove any service you do not want
- Reorder services by changing the line order

## Email templates

Email templates support simple Markdown-style formatting and placeholders.

Available placeholders:

- `{{site_name}}`
- `{{patient_name}}`
- `{{patient_email}}`
- `{{patient_phone}}`
- `{{service_name}}`
- `{{appointment_date}}`
- `{{appointment_time}}`
- `{{status_label}}`
- `{{notes}}`

Example:

```text
# Appointment received

Hi {{patient_name}},

Thank you for booking with {{site_name}}.
Your appointment for {{service_name}} is on {{appointment_date}} at {{appointment_time}}.
```

## Adding it to GitHub

1. Create a new GitHub repository.
2. Add this plugin folder to the repository.
3. Commit the files and push them to GitHub.
4. Include this README so other users can install and configure the plugin.

## How to add it to WordPress

If you want to use the plugin on a WordPress site:

1. Upload the plugin ZIP through `Plugins > Add New > Upload Plugin`, or
2. Copy the plugin folder into `wp-content/plugins/` and activate it from the Plugins screen.

Then create a page with the shortcode:

```text
[appointment_booking_form]
```

## Notes

- Bookings are stored in the WordPress database.
- Email delivery depends on correct Plunk credentials and a verified sender.
- If emails do not send, recheck the Plunk secret key, sender email, and domain verification.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for the full text.
