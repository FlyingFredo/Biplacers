<?php
require_once '../../config/config.php'; // Adjusted path
require_once APP_ROOT . '/src/utils.php'; // For regenerate_csrf_token

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Regenerate CSRF token for the login page if someone logs out and then another user logs in.
// This requires session_start() again as session_destroy() removed it.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
regenerate_csrf_token(); // For the login page
$_SESSION['user_message'] = 'You have been logged out successfully.';
$_SESSION['message_type'] = 'success';


redirect(BASE_URL . '/admin/login.php');
?>
