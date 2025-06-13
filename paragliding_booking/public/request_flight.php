<?php
define('PAGE_TITLE', 'Request a Flight');
require_once '../config/config.php'; // Defines APP_ROOT, BASE_URL, etc.
require_once APP_ROOT . '/src/utils.php'; // For utility functions like old() or escape_html()
require_once APP_ROOT . '/templates/layouts/header.php';

// Initialize variables for form values and errors
$form_data = $_SESSION['form_data'] ?? [];
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']); // Clear after use

?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="mb-4"><?php echo PAGE_TITLE; ?></h1>

        <?php if (!empty($form_errors)): ?>
            <div class="alert alert-danger">
                <strong>Please correct the following errors:</strong>
                <ul>
                    <?php foreach ($form_errors as $field_errors): ?>
                        <?php foreach ($field_errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php
        // Display general success/error messages from session (e.g., if form submission has an issue not field-specific)
        if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
            $message_type = $_SESSION['message_type'] ?? 'info';
            echo '<div class="alert alert-' . htmlspecialchars($message_type) . '" role="alert">' . htmlspecialchars($_SESSION['user_message']) . '</div>';
            unset($_SESSION['user_message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <form action="<?php echo htmlspecialchars(BASE_URL); ?>/submit_request.php" method="POST" novalidate>
            <fieldset>
                <legend>Your Details</legend>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($form_errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo escape_html(old('first_name', $form_data)); ?>" required>
                        <?php if (isset($form_errors['first_name'])): ?>
                            <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['first_name'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($form_errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo escape_html(old('last_name', $form_data)); ?>" required>
                        <?php if (isset($form_errors['last_name'])): ?>
                            <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['last_name'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="age">Age <span class="text-danger">*</span></label>
                        <input type="number" class="form-control <?php echo isset($form_errors['age']) ? 'is-invalid' : ''; ?>" id="age" name="age" value="<?php echo escape_html(old('age', $form_data)); ?>" min="1" max="120" required>
                         <?php if (isset($form_errors['age'])): ?>
                            <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['age'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="weight">Weight (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" class="form-control <?php echo isset($form_errors['weight']) ? 'is-invalid' : ''; ?>" id="weight" name="weight" value="<?php echo escape_html(old('weight', $form_data)); ?>" min="20" max="200" required>
                        <?php if (isset($form_errors['weight'])): ?>
                            <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['weight'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                 <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="phone">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control <?php echo isset($form_errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo escape_html(old('phone', $form_data)); ?>" required>
                        <?php if (isset($form_errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['phone'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?php echo isset($form_errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo escape_html(old('email', $form_data)); ?>" required>
                        <?php if (isset($form_errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['email'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>

            <fieldset class="mt-4">
                <legend>Flight Preference</legend>
                <div class="form-group">
                    <label for="desired_date">Desired Flight Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control <?php echo isset($form_errors['desired_date']) ? 'is-invalid' : ''; ?>" id="desired_date" name="desired_date" value="<?php echo escape_html(old('desired_date', $form_data)); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    <?php if (isset($form_errors['desired_date'])): ?>
                        <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['desired_date'])); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="other_date_available" name="other_date_available" value="1" <?php echo old('other_date_available', $form_data) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="other_date_available">I can be available on another date if my desired date is not possible.</label>
                </div>

                <div class="form-group">
                    <label for="notes_passenger">Notes (optional)</label>
                    <textarea class="form-control <?php echo isset($form_errors['notes_passenger']) ? 'is-invalid' : ''; ?>" id="notes_passenger" name="notes_passenger" rows="3"><?php echo escape_html(old('notes_passenger', $form_data)); ?></textarea>
                    <?php if (isset($form_errors['notes_passenger'])): ?>
                        <div class="invalid-feedback"><?php echo escape_html(implode(', ', $form_errors['notes_passenger'])); ?></div>
                    <?php endif; ?>
                </div>
            </fieldset>

            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <button type="submit" class="btn btn-primary btn-lg mt-3">Submit Request</button>
        </form>
    </div>
</div>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
