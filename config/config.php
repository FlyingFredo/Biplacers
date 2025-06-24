<?php

// This is a placeholder for configuration settings.
// Add your configuration variables here.

// ** SITE CONFIGURATION ** //
// Defines the root directory of the application.
// Used for including files.
define('APP_ROOT', dirname(__DIR__) . '/paragliding_booking');

// Defines the base URL of the application.
// Used for generating links and redirects.
// IMPORTANT: Update this to your actual domain and path.
define('BASE_URL', 'http://localhost/paragliding_booking/public');

// The name of your site, used in page titles and emails.
define('SITE_NAME', 'Paragliding Booking');

// ** DATABASE CONFIGURATION ** //
// Replace with your actual database credentials.
define('DB_HOST', 'localhost');         // Your database host (e.g., '127.0.0.1' or 'localhost')
define('DB_NAME', 'paragliding_db');    // Your database name
define('DB_USER', 'db_user');           // Your database username
define('DB_PASS', 'db_password');       // Your database password

// ** EMAIL CONFIGURATION ** //
// Used for sending emails from the application (e.g., booking confirmations).
define('MAIL_FROM_NAME', SITE_NAME);                // Default sender name for emails
define('MAIL_FROM_ADDRESS', 'noreply@example.com'); // Default sender email address

// ** Optional: PHPMailer Configuration (if you choose to use PHPMailer for sending emails) ** //
// Uncomment and configure these if you integrate PHPMailer.
// define('MAIL_HOST', 'smtp.example.com');        // SMTP server hostname
// define('MAIL_USERNAME', 'your_smtp_username');   // SMTP username
// define('MAIL_PASSWORD', 'your_smtp_password');   // SMTP password
// define('MAIL_ENCRYPTION', 'tls');                // Enable TLS encryption; `ssl` also accepted (e.g., PHPMailer::ENCRYPTION_STARTTLS or PHPMailer::ENCRYPTION_SMTPS)
// define('MAIL_PORT', 587);                        // TCP port to connect to (587 for TLS, 465 for SSL)


// ** LOCALE AND INTERNATIONALIZATION ** //
// Default language for the application. Ensure this matches one of the keys in SUPPORTED_LANGUAGES.
define('DEFAULT_LANGUAGE', 'en');

// Supported languages. Array format: 'language_code' => 'Language Name (in that language)'
// These codes should match directory names in paragliding_booking/locale/
define('SUPPORTED_LANGUAGES', [
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
]);

// ** SESSION MANAGEMENT ** //
// Start session if not already started. Essential for user login, CSRF tokens, messages.
if (session_status() == PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

// ** ERROR REPORTING ** //
// For development: display all errors.
// For production: log errors and display a generic message.
// Consider using a more sophisticated error handling mechanism.
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);     // Set to E_ALL & ~E_DEPRECATED & ~E_STRICT in production, or handle errors via a framework

// ** INCLUDE CORE FILES ** //
// Make sure paths are correct based on APP_ROOT.

// Utility functions (CSRF, redirects, etc.)
require_once APP_ROOT . '/src/utils.php';

// Locale functions for internationalization
require_once APP_ROOT . '/src/Locale.php';

// Initialize the localization system after defining languages
Locale::initialize(DEFAULT_LANGUAGE, SUPPORTED_LANGUAGES, APP_ROOT . '/locale');

// Example of how SITE_NAME_KEY might be used with localization, if you prefer dynamic site names.
// If you define SITE_NAME_KEY, ensure the corresponding key exists in your language files.
// define('SITE_NAME_KEY', 'site_name_default'); // e.g., 'site_name_default' => "Paragliding Booking System" in en/messages.php

?>
