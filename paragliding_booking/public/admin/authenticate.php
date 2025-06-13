<?php
require_once '../../config/config.php'; // Adjusted path
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/login.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['user_message'] = 'Invalid security token. Please try logging in again.';
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/admin/login.php');
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? ''; // Password will be verified after fetching user

$errors = [];
if (!$email) {
    $errors['email'][] = 'Invalid email format.';
}
if (empty($password)) {
    $errors['password'][] = 'Password is required.';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = ['email' => $_POST['email'] ?? '']; // Send back email
    redirect(BASE_URL . '/admin/login.php');
}

$db = new Database();

// Check pilots table first
$db->query("SELECT id, first_name, last_name, email, password_hash, is_active FROM pilots WHERE email = :email");
$db->bind(':email', $email);
$user = $db->single();
$user_type = 'pilot';

// TODO: Implement a separate admin users table if needed, or a role system for pilots.
// For now, we'll assume the first pilot created (ID 1) is the 'super admin'
// or any pilot can log in to see the dashboard (permissions will be checked per action).
// A more robust system would have a dedicated 'roles' table or an 'is_admin' flag in the pilots table.

if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
    // Password is correct
    session_regenerate_id(true); // Prevent session fixation

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

    // Simplified role: if pilot ID is 1, they are admin, otherwise pilot.
    // This is a placeholder. A proper role system is needed for a real application.
    // For the issue description, an admin creates pilots, and pilots can see the dashboard.
    // So, we need a way to distinguish. Let's assume an admin role for now.
    // We'll assume 'admin@example.com' is the main admin for now.
    if ($user['email'] === 'admin@example.com') { // Hardcoded admin email for initial setup
         $_SESSION['user_type'] = 'admin';
    } else {
        $_SESSION['user_type'] = 'pilot';
    }

    regenerate_csrf_token(); // Generate a new CSRF token after login

    $_SESSION['user_message'] = 'Login successful. Welcome back!';
    $_SESSION['message_type'] = 'success';
    redirect(BASE_URL . '/admin/dashboard.php');

} else {
    // Login failed
    $_SESSION['form_errors'] = ['credentials' => ['Invalid email or password, or account is inactive.']];
    $_SESSION['form_data'] = ['email' => $email];
    redirect(BASE_URL . '/admin/login.php');
}
?>
