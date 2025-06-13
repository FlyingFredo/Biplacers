<?php
require_once '../config/config.php'; // Defines APP_ROOT, BASE_URL, etc.
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Email.php';

// Start session if not already started (config should do this, but ensure)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to form if not a POST request
    $_SESSION['user_message'] = 'Invalid request method.';
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/request_flight.php');
}

// CSRF Token Validation
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['user_message'] = 'Invalid security token. Please try again.';
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/request_flight.php');
}

$errors = [];
$input = [];

// Sanitize and Validate input
// First Name
$input['first_name'] = trim($_POST['first_name'] ?? '');
if (empty($input['first_name'])) {
    $errors['first_name'][] = 'First name is required.';
} elseif (strlen($input['first_name']) > 100) {
    $errors['first_name'][] = 'First name cannot exceed 100 characters.';
}

// Last Name
$input['last_name'] = trim($_POST['last_name'] ?? '');
if (empty($input['last_name'])) {
    $errors['last_name'][] = 'Last name is required.';
} elseif (strlen($input['last_name']) > 100) {
    $errors['last_name'][] = 'Last name cannot exceed 100 characters.';
}

// Age
$input['age'] = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 120]]);
if ($input['age'] === false || is_null($input['age'])) { // filter_input returns false on failure, null if not set
    $errors['age'][] = 'Age is required and must be a valid number (1-120).';
}

// Weight
$input['weight'] = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT, ["options" => ["decimal" => "."]]);
if ($input['weight'] === false || is_null($input['weight'])) {
    $errors['weight'][] = 'Weight is required.';
} elseif ($input['weight'] < 20 || $input['weight'] > 200) { // Assuming kg
    $errors['weight'][] = 'Weight must be between 20kg and 200kg.';
} else {
    $input['weight'] = round($input['weight'], 2); // Ensure two decimal places
}

// Phone
$input['phone'] = trim($_POST['phone'] ?? '');
if (empty($input['phone'])) {
    $errors['phone'][] = 'Phone number is required.';
} elseif (!preg_match('/^[+]?[0-9\s\-()]{7,30}$/', $input['phone'])) { // Basic phone validation
    $errors['phone'][] = 'Phone number is not valid.';
} elseif (strlen($input['phone']) > 30) {
     $errors['phone'][] = 'Phone number cannot exceed 30 characters.';
}

// Email
$input['email'] = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (empty(trim($_POST['email'] ?? ''))) {
    $errors['email'][] = 'Email is required.';
} elseif ($input['email'] === false) {
    $errors['email'][] = 'Email address is not valid.';
    $input['email'] = trim($_POST['email'] ?? ''); // Keep original invalid email for re-display
} elseif (strlen($input['email']) > 255) {
    $errors['email'][] = 'Email address cannot exceed 255 characters.';
}


// Desired Date
$input['desired_date'] = trim($_POST['desired_date'] ?? '');
if (empty($input['desired_date'])) {
    $errors['desired_date'][] = 'Desired flight date is required.';
} else {
    $date_obj = DateTime::createFromFormat('Y-m-d', $input['desired_date']);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $input['desired_date']) {
        $errors['desired_date'][] = 'Desired flight date is not a valid date format (YYYY-MM-DD).';
    } elseif ($date_obj < new DateTime('today')) {
        $errors['desired_date'][] = 'Desired flight date cannot be in the past.';
    }
}

// Other Date Available
$input['other_date_available'] = isset($_POST['other_date_available']) ? 1 : 0;

// Notes (optional)
$input['notes_passenger'] = trim($_POST['notes_passenger'] ?? '');
if (strlen($input['notes_passenger']) > 2000) { // Example limit
    $errors['notes_passenger'][] = 'Notes cannot exceed 2000 characters.';
}


if (!empty($errors)) {
    // Store errors and form data in session and redirect back to form
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST; // Send back all POST data
    redirect(BASE_URL . '/request_flight.php');
}

// If validation passes, proceed to database interaction and email sending
$db = new Database();

try {
    $db->beginTransaction();

    // 1. Check if passenger email already exists. If so, use that passenger_id.
    $db->query("SELECT id FROM passengers WHERE email = :email");
    $db->bind(':email', $input['email']);
    $passenger = $db->single();
    $passenger_id = null;

    if ($passenger) {
        $passenger_id = $passenger['id'];
        // Optionally: Update passenger details if they changed? For now, just use existing.
        // $db->query("UPDATE passengers SET first_name = :first_name, last_name = :last_name, age = :age, weight = :weight, phone = :phone WHERE id = :id");
        // ... bindings ... $db->execute();
    } else {
        // Insert new passenger
        $db->query("INSERT INTO passengers (first_name, last_name, age, weight, phone, email) VALUES (:first_name, :last_name, :age, :weight, :phone, :email)");
        $db->bind(':first_name', $input['first_name']);
        $db->bind(':last_name', $input['last_name']);
        $db->bind(':age', $input['age']);
        $db->bind(':weight', $input['weight']);
        $db->bind(':phone', $input['phone']);
        $db->bind(':email', $input['email']);
        $db->execute();
        $passenger_id = $db->lastInsertId();
    }

    if (!$passenger_id) {
        throw new Exception("Failed to create or retrieve passenger record.");
    }

    // 2. Create Flight Request
    $confirmation_token = bin2hex(random_bytes(32)); // Generate a secure token
    $token_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token valid for 24 hours

    $db->query("INSERT INTO flight_requests (passenger_id, desired_date, other_date_available, status, confirmation_token, token_expires_at, notes_passenger)
                VALUES (:passenger_id, :desired_date, :other_date_available, 'pending_confirmation', :confirmation_token, :token_expires_at, :notes_passenger)");
    $db->bind(':passenger_id', $passenger_id);
    $db->bind(':desired_date', $input['desired_date']);
    $db->bind(':other_date_available', $input['other_date_available'], PDO::PARAM_INT); // Ensure it's treated as int
    $db->bind(':confirmation_token', $confirmation_token);
    $db->bind(':token_expires_at', $token_expires_at);
    $db->bind(':notes_passenger', $input['notes_passenger']);

    if (!$db->execute()) {
         throw new Exception("Failed to create flight request. SQL Error: " . ($db->getError() ? $db->getError()['message'] : 'Unknown error'));
    }

    $flight_request_id = $db->lastInsertId();

    // 3. Send Confirmation Email to Passenger
    $confirmation_link = BASE_URL . "/confirm_request.php?token=" . $confirmation_token;
    $email_subject = "Confirm Your Flight Request - " . SITE_NAME;

    // Basic Email Template (Consider using a templating engine or loading from a file for complex emails)
    $email_body_html = "<p>Dear " . escape_html($input['first_name']) . ",</p>";
    $email_body_html .= "<p>Thank you for your flight request with " . SITE_NAME . ". Please click the link below to confirm your request:</p>";
    $email_body_html .= "<p><a href='" . $confirmation_link . "'>" . $confirmation_link . "</a></p>";
    $email_body_html .= "<p>This link will expire in 24 hours.</p>";
    $email_body_html .= "<p><strong>Request Details:</strong><br>";
    $email_body_html .= "Name: " . escape_html($input['first_name']) . " " . escape_html($input['last_name']) . "<br>";
    $email_body_html .= "Desired Date: " . escape_html($input['desired_date']) . "<br>";
    // ... add other relevant details ...
    $email_body_html .= "</p>";
    $email_body_html .= "<p>If you did not make this request, please ignore this email.</p>";
    $email_body_html .= "<p>Best regards,<br>" . SITE_NAME . " Team</p>";

    if (!send_application_email($input['email'], $email_subject, $email_body_html)) {
        // Log email failure but don't necessarily roll back transaction if DB part was successful.
        // Admin might need to manually confirm or resend.
        error_log("Failed to send confirmation email to " . $input['email'] . " for request ID " . $flight_request_id);
        // For now, let's inform the user there might be an issue with the email.
         $_SESSION['user_message'] = "Your request has been submitted! However, there was an issue sending the confirmation email. Please contact support if you don't receive it shortly.";
         $_SESSION['message_type'] = 'warning';
    } else {
        $_SESSION['user_message'] = "Thank you, " . escape_html($input['first_name']) . "! Your flight request has been submitted. Please check your email (" . escape_html($input['email']) . ") for a confirmation link to finalize your request.";
        $_SESSION['message_type'] = 'success';
    }

    $db->commit();

    redirect(BASE_URL . '/request_flight.php'); // Redirect to form page to show success/warning message

} catch (PDOException $e) {
    $db->rollBack();
    error_log("Database error during flight request: " . $e->getMessage() . " | Input: " . print_r($input, true));
    $_SESSION['form_errors'] = ['database' => ['A database error occurred: ' . $e->getMessage()]]; // More detailed for dev
    // $_SESSION['form_errors'] = ['database' => ['A database error occurred while processing your request. Please try again later.']]; // User-friendly
    $_SESSION['form_data'] = $_POST;
    redirect(BASE_URL . '/request_flight.php');
} catch (Exception $e) {
    // Catch other general exceptions
    if ($db->inTransaction()) { // Check if transaction is active before trying to rollback
       $db->rollBack();
    }
    error_log("General error during flight request: " . $e->getMessage() . " | Input: " . print_r($input, true));
    $_SESSION['form_errors'] = ['general' => ['An unexpected error occurred: ' . $e->getMessage()]]; // More detailed for dev
    // $_SESSION['form_errors'] = ['general' => ['An unexpected error occurred. Please try again.']]; // User-friendly
    $_SESSION['form_data'] = $_POST;
    redirect(BASE_URL . '/request_flight.php');
}

?>
