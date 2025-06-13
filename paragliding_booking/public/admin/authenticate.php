<?php
require_once '../../config/config.php';
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/login.php'); // No message needed, just redirect
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['user_message'] = 'login_error_csrf'; // Key
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/admin/login.php');
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$errors = [];

if (!$email) { $errors['email'][] = 'login_error_email_invalid'; } // Key
if (empty($password)) { $errors['password'][] = 'login_error_password_required'; } // Key

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = ['email' => $_POST['email'] ?? ''];
    redirect(BASE_URL . '/admin/login.php');
}

$db = new Database();
$db->query("SELECT id, first_name, last_name, email, password_hash, is_active FROM pilots WHERE email = :email");
$db->bind(':email', $email);
$user = $db->single();

// Admin identification logic (placeholder, as before)
$is_admin_user = ($user && $user['email'] === 'admin@example.com'); // Example admin

if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_type'] = $is_admin_user ? 'admin' : 'pilot';

    regenerate_csrf_token();
    $_SESSION['user_message'] = 'admin_welcome_message'; // Key
    $_SESSION['message_type'] = 'success';
    redirect(BASE_URL . '/admin/dashboard.php');
} else {
    $_SESSION['form_errors'] = ['credentials' => ['login_error_credentials']]; // Key
    $_SESSION['form_data'] = ['email' => $email];
    redirect(BASE_URL . '/admin/login.php');
}
?>
