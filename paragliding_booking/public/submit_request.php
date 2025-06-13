<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Email.php'; // For send_application_email

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['user_message'] = 'submit_request_invalid_method'; // Key
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/request_flight.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['user_message'] = 'submit_request_csrf_invalid'; // Key
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/request_flight.php');
}

$errors = []; // Will store translation keys
$input = [];

// Sanitize and Validate input
$input['first_name'] = trim($_POST['first_name'] ?? '');
if (empty($input['first_name'])) {
    $errors['first_name'][] = 'val_err_first_name_required';
} elseif (strlen($input['first_name']) > 100) {
    $errors['first_name'][] = 'val_err_first_name_maxlength';
}

$input['last_name'] = trim($_POST['last_name'] ?? '');
if (empty($input['last_name'])) {
    $errors['last_name'][] = 'val_err_last_name_required';
} elseif (strlen($input['last_name']) > 100) {
    $errors['last_name'][] = 'val_err_last_name_maxlength';
}

$input['age'] = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
if ($input['age'] === false || is_null($input['age']) || $input['age'] < 1 || $input['age'] > 120) {
    $errors['age'][] = 'val_err_age_required_range';
}

$input['weight'] = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT, ["options" => ["decimal" => "."]]);
if ($input['weight'] === false || is_null($input['weight'])) {
    $errors['weight'][] = 'val_err_weight_required';
} elseif ($input['weight'] < 20 || $input['weight'] > 200) {
    $errors['weight'][] = 'val_err_weight_range';
} else {
    $input['weight'] = round($input['weight'], 2);
}

$input['phone'] = trim($_POST['phone'] ?? '');
if (empty($input['phone'])) {
    $errors['phone'][] = 'val_err_phone_required';
} elseif (!preg_match('/^[+]?[0-9\s\-()]{7,30}$/', $input['phone'])) {
    $errors['phone'][] = 'val_err_phone_invalid';
} elseif (strlen($input['phone']) > 30) {
     $errors['phone'][] = 'val_err_phone_maxlength';
}

$input['email'] = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (empty(trim($_POST['email'] ?? ''))) {
    $errors['email'][] = 'val_err_email_required';
} elseif ($input['email'] === false) {
    $errors['email'][] = 'val_err_email_invalid';
    $input['email'] = trim($_POST['email'] ?? '');
} elseif (strlen($input['email']) > 255) {
    $errors['email'][] = 'val_err_email_maxlength';
}

$input['desired_date'] = trim($_POST['desired_date'] ?? '');
if (empty($input['desired_date'])) {
    $errors['desired_date'][] = 'val_err_desired_date_required';
} else {
    $date_obj = DateTime::createFromFormat('Y-m-d', $input['desired_date']);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $input['desired_date']) {
        $errors['desired_date'][] = 'val_err_desired_date_invalid';
    } elseif ($date_obj < new DateTime('today')) {
        $errors['desired_date'][] = 'val_err_desired_date_past';
    }
}

$input['other_date_available'] = isset($_POST['other_date_available']) ? 1 : 0;
$input['notes_passenger'] = trim($_POST['notes_passenger'] ?? '');
if (strlen($input['notes_passenger']) > 2000) {
    $errors['notes_passenger'][] = 'val_err_notes_maxlength';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors; // These are now arrays of keys
    $_SESSION['form_data'] = $_POST;
    redirect(BASE_URL . '/request_flight.php');
}

$db = new Database();
try {
    $db->beginTransaction();
    // ... (database logic as before) ...
    $db->query("SELECT id FROM passengers WHERE email = :email");
    $db->bind(':email', $input['email']);
    $passenger = $db->single();
    $passenger_id = null;

    if ($passenger) {
        $passenger_id = $passenger['id'];
    } else {
        $db->query("INSERT INTO passengers (first_name, last_name, age, weight, phone, email) VALUES (:first_name, :last_name, :age, :weight, :phone, :email)");
        // ... bindings ...
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
        throw new Exception(__('submit_request_passenger_record_fail')); // Use translated key
    }

    $confirmation_token = bin2hex(random_bytes(32));
    $token_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $db->query("INSERT INTO flight_requests (passenger_id, desired_date, other_date_available, status, confirmation_token, token_expires_at, notes_passenger)
                VALUES (:passenger_id, :desired_date, :other_date_available, 'pending_confirmation', :confirmation_token, :token_expires_at, :notes_passenger)");
    // ... bindings ...
    $db->bind(':passenger_id', $passenger_id);
    $db->bind(':desired_date', $input['desired_date']);
    $db->bind(':other_date_available', $input['other_date_available'], PDO::PARAM_INT);
    $db->bind(':confirmation_token', $confirmation_token);
    $db->bind(':token_expires_at', $token_expires_at);
    $db->bind(':notes_passenger', $input['notes_passenger']);

    if (!$db->execute()) {
         throw new Exception(__('submit_request_flight_creation_fail_user')); // User-facing translated key
    }
    $flight_request_id = $db->lastInsertId();

    // Send Confirmation Email
    $confirmation_link = BASE_URL . "/confirm_request.php?token=" . $confirmation_token;
    // Subject and body now use translation keys
    $email_subject = __('confirm_email_summary_subject', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)]);

    $email_body_html = "<p>" . __('confirm_email_summary_body_greeting', ['passengerName' => escape_html($input['first_name'])]) . "</p>";
    $email_body_html .= "<p>" . __('submit_request_email_body_line1_pending', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)]) . "</p>";
    $email_body_html .= "<p><a href='" . $confirmation_link . "'>" . $confirmation_link . "</a></p>";
    $email_body_html .= "<p>" . __('submit_request_email_body_line2_token_expiry') . "</p>";
    $email_body_html .= "<p><strong>" . __('confirm_email_summary_body_request_details_title') . "</strong><br>";
    $email_body_html .= __('confirm_email_summary_body_passenger_name', ['passengerFullName' => escape_html($input['first_name'] . ' ' . $input['last_name'])]) . "<br>";
    $email_body_html .= __('confirm_email_summary_body_desired_date', ['desiredDate' => escape_html($input['desired_date'])]) . "<br>";
    $email_body_html .= "</p>";
    $email_body_html .= "<p>" . __('submit_request_email_body_line3_ignore') . "</p>";
    $email_body_html .= "<p>" . __('confirm_email_summary_body_thank_you') . "<br>" . __('confirm_email_summary_body_team_name', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)]) . "</p>";

    if (!send_application_email($input['email'], $email_subject, $email_body_html)) {
        error_log("Failed to send confirmation email to " . $input['email'] . " for request ID " . $flight_request_id);
        $_SESSION['user_message'] = 'submit_request_confirmation_email_send_fail_warning'; // Key
        $_SESSION['message_type'] = 'warning';
    } else {
        $_SESSION['user_message'] = 'submit_request_success_pending_confirmation'; // Key
        $_SESSION['user_message_params'] = ['passengerName' => escape_html($input['first_name']), 'passengerEmail' => escape_html($input['email'])];
        $_SESSION['message_type'] = 'success';
    }

    $db->commit();
    redirect(BASE_URL . '/request_flight.php');

} catch (PDOException $e) {
    $db->rollBack();
    error_log("Database error during flight request: " . $e->getMessage());
    $_SESSION['form_errors'] = ['database_error' => ['submit_request_db_error_user']]; // Key
    $_SESSION['form_data'] = $_POST;
    redirect(BASE_URL . '/request_flight.php');
} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("General error during flight request: " . $e->getMessage());
    // If message from exception is a key, use it. Otherwise, use generic.
    // For Locale::get to work here, Locale class must be loaded.
    // $message_is_key = Locale::get($e->getMessage(), [], null) !== $e->getMessage(); // Check if it's a known key
    // $error_key = $message_is_key ? $e->getMessage() : 'submit_request_unexpected_error_user';
    $error_key = 'submit_request_unexpected_error_user'; // Simpler: use generic key for now
    $_SESSION['form_errors'] = ['general_error' => [$error_key]]; // Key
    $_SESSION['form_data'] = $_POST;
    redirect(BASE_URL . '/request_flight.php');
}
?>
