<?php
define('PAGE_TITLE_KEY', 'page_title_request_flight');
require_once '../config/config.php';
require_once APP_ROOT . '/src/utils.php'; // For escape_html, old, generate_csrf_token
require_once APP_ROOT . '/templates/layouts/header.php';

$form_data = $_SESSION['form_data'] ?? [];
$form_errors = $_SESSION['form_errors'] ?? []; // These errors are keys from submit_request.php
unset($_SESSION['form_data'], $_SESSION['form_errors']);

?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="mb-4"><?php echo escape_html(__(PAGE_TITLE_KEY)); ?></h1>

        <?php if (!empty($form_errors)): ?>
            <div class="alert alert-danger">
                <strong><?php echo escape_html(__('error_please_correct')); ?></strong>
                <ul>
                    <?php foreach ($form_errors as $field_errors): ?>
                        <?php foreach ($field_errors as $error_key): // error_key is the translation key ?>
                            <li><?php echo escape_html(__($error_key)); // Translate the error key ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
            $message_key = $_SESSION['user_message'];
            $message_params = $_SESSION['user_message_params'] ?? [];
            $message_type = $_SESSION['message_type'] ?? 'info';
            echo '<div class="alert alert-' . htmlspecialchars($message_type) . '" role="alert">' . escape_html(__($message_key, $message_params, $message_key)) . '</div>';
            unset($_SESSION['user_message'], $_SESSION['message_type'], $_SESSION['user_message_params']);
        }
        ?>

        <form action="<?php echo htmlspecialchars(BASE_URL); ?>/submit_request.php" method="POST" novalidate>
            <fieldset>
                <legend><?php echo escape_html(__('form_legend_your_details')); ?></legend>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="first_name"><?php echo escape_html(__('form_label_first_name')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                        <input type="text" class="form-control <?php echo isset($form_errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo escape_html(old('first_name', $form_data)); ?>" required>
                        <?php if (isset($form_errors['first_name'])): ?>
                            <div class="invalid-feedback"><?php foreach($form_errors['first_name'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="last_name"><?php echo escape_html(__('form_label_last_name')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                        <input type="text" class="form-control <?php echo isset($form_errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo escape_html(old('last_name', $form_data)); ?>" required>
                        <?php if (isset($form_errors['last_name'])): ?>
                            <div class="invalid-feedback"><?php foreach($form_errors['last_name'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="age"><?php echo escape_html(__('form_label_age')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                        <input type="number" class="form-control <?php echo isset($form_errors['age']) ? 'is-invalid' : ''; ?>" id="age" name="age" value="<?php echo escape_html(old('age', $form_data)); ?>" min="1" max="120" required>
                         <?php if (isset($form_errors['age'])): ?>
                            <div class="invalid-feedback"><?php foreach($form_errors['age'] as $err_key) { echo escape_html(__($err_key, ['minAge' => 1, 'maxAge' => 120])) . '<br>'; } ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="weight"><?php echo escape_html(__('form_label_weight_kg')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                        <input type="number" step="0.1" class="form-control <?php echo isset($form_errors['weight']) ? 'is-invalid' : ''; ?>" id="weight" name="weight" value="<?php echo escape_html(old('weight', $form_data)); ?>" min="20" max="200" required>
                        <?php if (isset($form_errors['weight'])): ?>
                            <div class="invalid-feedback"><?php foreach($form_errors['weight'] as $err_key) { echo escape_html(__($err_key, ['minWeight' => 20, 'maxWeight' => 200])) . '<br>'; } ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                 <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="phone"><?php echo escape_html(__('form_label_phone')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                        <input type="tel" class="form-control <?php echo isset($form_errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo escape_html(old('phone', $form_data)); ?>" required>
                        <?php if (isset($form_errors['phone'])): ?>
                            <div class="invalid-feedback"><?php foreach($form_errors['phone'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email"><?php echo escape_html(__('form_label_email')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                        <input type="email" class="form-control <?php echo isset($form_errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo escape_html(old('email', $form_data)); ?>" required>
                        <?php if (isset($form_errors['email'])): ?>
                            <div class="invalid-feedback"><?php foreach($form_errors['email'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>

            <fieldset class="mt-4">
                <legend><?php echo escape_html(__('form_legend_flight_preference')); ?></legend>
                <div class="form-group">
                    <label for="desired_date"><?php echo escape_html(__('form_label_desired_date')); ?> <span class="text-danger"><?php echo escape_html(__('form_required_indicator')); ?></span></label>
                    <input type="date" class="form-control <?php echo isset($form_errors['desired_date']) ? 'is-invalid' : ''; ?>" id="desired_date" name="desired_date" value="<?php echo escape_html(old('desired_date', $form_data)); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    <?php if (isset($form_errors['desired_date'])): ?>
                        <div class="invalid-feedback"><?php foreach($form_errors['desired_date'] as $err_key) { echo escape_html(__($err_key)) . '<br>'; } ?></div>
                        <?php endif; ?>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="other_date_available" name="other_date_available" value="1" <?php echo old('other_date_available', $form_data) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="other_date_available"><?php echo escape_html(__('form_label_other_date_available_checkbox')); ?></label>
                </div>

                <div class="form-group">
                    <label for="notes_passenger"><?php echo escape_html(__('form_label_notes_passenger')); ?></label>
                    <textarea class="form-control <?php echo isset($form_errors['notes_passenger']) ? 'is-invalid' : ''; ?>" id="notes_passenger" name="notes_passenger" rows="3"><?php echo escape_html(old('notes_passenger', $form_data)); ?></textarea>
                    <?php if (isset($form_errors['notes_passenger'])): ?>
                         <div class="invalid-feedback"><?php foreach($form_errors['notes_passenger'] as $err_key) { echo escape_html(__($err_key, ['maxLength' => 2000])) . '<br>'; } ?></div>
                    <?php endif; ?>
                </div>
            </fieldset>

            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); // Assumes utils.php is included ?>">

            <button type="submit" class="btn btn-primary btn-lg mt-3"><?php echo escape_html(__('form_button_submit_request')); ?></button>
        </form>
    </div>
</div>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
