<?php
require_once '../../config/config.php';
require_once APP_ROOT . '/src/utils.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
if (session_status() == PHP_SESSION_NONE) { session_start(); } // Restart for flash message & CSRF
regenerate_csrf_token();
$_SESSION['user_message'] = 'logout_success_message'; // Key
$_SESSION['message_type'] = 'success';
redirect(BASE_URL . '/admin/login.php');
?>
