<?php
define('PAGE_TITLE', 'Welcome');
// config.php should handle session_start()
require_once '../config/config.php';
require_once APP_ROOT . '/templates/layouts/header.php';
?>

<div class="jumbotron">
    <h1 class="display-4">Welcome to <?php echo htmlspecialchars(SITE_NAME); ?>!</h1>
    <p class="lead">Your adventure in the skies begins here. Book your tandem paragliding experience with our certified pilots.</p>
    <hr class="my-4">
    <p>Ready to fly? Click the button below to make a flight request.</p>
    <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars(BASE_URL); ?>/request_flight.php" role="button">Book a Flight</a>
</div>

<?php
// Display messages if any (e.g., after a redirect with a success/error message)
if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
    $message_type = isset($_SESSION['message_type']) && !empty($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; // success, error, warning, info
    echo '<div class="alert alert-' . htmlspecialchars($message_type) . '" role="alert">' . htmlspecialchars($_SESSION['user_message']) . '</div>';
    // Clear the message after displaying it
    unset($_SESSION['user_message']);
    unset($_SESSION['message_type']);
}
?>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
