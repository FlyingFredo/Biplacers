<?php
define('PAGE_TITLE_KEY', 'page_title_admin_login');
require_once '../../config/config.php';
require_once APP_ROOT . '/src/utils.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

require_once APP_ROOT . '/templates/layouts/header.php';

$form_errors = $_SESSION['form_errors'] ?? []; // Keys for errors
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <h2 class="text-center mb-4"><?php echo escape_html(__(PAGE_TITLE_KEY)); ?></h2>

        <?php
        if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
            $message_key = $_SESSION['user_message'];
            $message_params = $_SESSION['user_message_params'] ?? [];
            $message_type = $_SESSION['message_type'] ?? 'danger';
            echo '<div class="alert alert-' . htmlspecialchars($message_type) . '">' . escape_html(__($message_key, $message_params, $message_key)) . '</div>';
            unset($_SESSION['user_message'], $_SESSION['message_type'], $_SESSION['user_message_params']);
        }
        ?>
        <?php if (!empty($form_errors['credentials'])): ?>
            <div class="alert alert-danger"><?php echo escape_html(__(implode(', ', $form_errors['credentials']))); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars(BASE_URL); ?>/admin/authenticate.php" method="POST">
            <div class="form-group">
                <label for="email"><?php echo escape_html(__('login_label_email')); ?></label>
                <input type="email" class="form-control <?php echo isset($form_errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo escape_html(old('email', $form_data)); ?>" required autofocus>
                <?php if (isset($form_errors['email'])): ?>
                    <div class="invalid-feedback"><?php foreach($form_errors['email'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password"><?php echo escape_html(__('login_label_password')); ?></label>
                <input type="password" class="form-control <?php echo isset($form_errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                 <?php if (isset($form_errors['password'])): ?>
                    <div class="invalid-feedback"><?php foreach($form_errors['password'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                <?php endif; ?>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" class="btn btn-primary btn-block"><?php echo escape_html(__('login_button_submit')); ?></button>
        </form>
    </div>
</div>
<?php require_once APP_ROOT . '/templates/layouts/footer.php'; ?>
