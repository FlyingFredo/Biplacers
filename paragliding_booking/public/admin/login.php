<?php
define('PAGE_TITLE', 'Admin/Pilot Login');
require_once '../../config/config.php'; // Adjusted path
require_once APP_ROOT . '/src/utils.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

require_once APP_ROOT . '/templates/layouts/header.php'; // Standard header

$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? []; // For repopulating email
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <h2 class="text-center mb-4"><?php echo PAGE_TITLE; ?></h2>

        <?php
        if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
            $message_type = $_SESSION['message_type'] ?? 'danger';
            echo '<div class="alert alert-' . htmlspecialchars($message_type) . '">' . htmlspecialchars($_SESSION['user_message']) . '</div>';
            unset($_SESSION['user_message']);
            unset($_SESSION['message_type']);
        }
        ?>
        <?php if (!empty($form_errors['credentials'])): ?>
            <div class="alert alert-danger"><?php echo escape_html(implode(', ', $form_errors['credentials'])); ?></div>
        <?php endif; ?>


        <form action="<?php echo htmlspecialchars(BASE_URL); ?>/admin/authenticate.php" method="POST">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control <?php echo isset($form_errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo escape_html(old('email', $form_data)); ?>" required autofocus>
                <?php if (isset($form_errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['email'])); ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control <?php echo isset($form_errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                 <?php if (isset($form_errors['password'])): ?>
                    <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['password'])); ?></div>
                <?php endif; ?>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>
</div>

<?php
// No standard footer here for a cleaner login page, or a simplified one
// For consistency, let's use the standard one for now
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
