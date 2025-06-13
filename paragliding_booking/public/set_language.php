<?php
require_once '../config/config.php'; // For APP_ROOT, BASE_URL, Locale class (via config)
require_once APP_ROOT . '/src/utils.php';   // For redirect()

if (isset($_GET['lang'])) {
    $lang_code = $_GET['lang'];
    Locale::setLanguage($lang_code); // Locale class handles validation and setting cookie/session
}

// Redirect back to the previous page or a default page
$redirect_url = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php';
redirect($redirect_url);
?>
