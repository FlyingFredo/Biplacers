<?php
// Define a translation key for the page title for this specific page
define('PAGE_TITLE_KEY', 'page_title_welcome');
// config.php includes Locale.php and initializes it.
require_once '../config/config.php';
require_once APP_ROOT . '/templates/layouts/header.php';
?>

<div class="jumbotron">
    <h1 class="display-4"><?php echo escape_html(__('index_jumbotron_h1', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)])); ?></h1>
    <p class="lead"><?php echo escape_html(__('index_jumbotron_lead')); ?></p>
    <hr class="my-4">
    <p><?php echo escape_html(__('index_jumbotron_hr_p')); ?></p>
    <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars(BASE_URL); ?>/request_flight.php" role="button"><?php echo escape_html(__('index_jumbotron_button')); ?></a>
</div>

<?php
// Display messages if any (e.g., after a redirect with a success/error message)
// These messages should ideally be translation keys themselves, or use a dedicated messaging system.
// For now, assuming the message itself is already translated or is a key.
if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
    $message_text = $_SESSION['user_message'];
    // If $_SESSION['user_message'] is a key, translate it. Otherwise, display as is.
    // This requires a convention: are session messages raw text or translation keys?
    // Let's assume for now they might be keys, and if not found, display raw.
    // Parameters for message would need to be stored in session too if message is a key.
    $message_params = $_SESSION['user_message_params'] ?? [];
    $translated_message = __($message_text, $message_params, $message_text); // Fallback to raw message if key not found

    $message_type = $_SESSION['message_type'] ?? 'info'; // success, error, warning, info
    echo '<div class="alert alert-' . htmlspecialchars($message_type) . '" role="alert">' . escape_html($translated_message) . '</div>';

    unset($_SESSION['user_message']);
    unset($_SESSION['message_type']);
    unset($_SESSION['user_message_params']);
}
?>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
