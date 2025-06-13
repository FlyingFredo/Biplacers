<?php
define('PAGE_TITLE_KEY', 'page_title_manage_pilots');
require_once '../../config/config.php';
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';

require_login('admin');
$db = new Database();
$add_pilot_errors_keys = []; // Store translation keys
$add_pilot_success_message_key = '';
$add_pilot_success_message_params = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pilot'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $add_pilot_errors_keys['csrf'] = 'manage_pilots_error_csrf';
    } else {
        // ... (validation logic as before, but $add_pilot_errors_keys stores keys) ...
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($first_name)) $add_pilot_errors_keys['first_name'] = 'val_err_first_name_required';
        if (empty($last_name)) $add_pilot_errors_keys['last_name'] = 'val_err_last_name_required';
        if (!$email) $add_pilot_errors_keys['email'] = 'val_err_email_invalid';
        if (empty($phone)) $add_pilot_errors_keys['phone'] = 'val_err_phone_required';
        // Use a generic 'validation_required' for password, or a more specific one if created
        if (empty($password)) $add_pilot_errors_keys['password'] = 'validation_required';
        if (strlen($password) < 8) $add_pilot_errors_keys['password_length'] = ['validation_password_min_length', ['minLength' => 8]]; // Key and params
        if ($password !== $confirm_password) $add_pilot_errors_keys['confirm_password'] = 'validation_passwords_do_not_match';

        if ($email) {
            $db->query("SELECT id FROM pilots WHERE email = :email");
            $db->bind(':email', $email);
            if ($db->single()) { $add_pilot_errors_keys['email_exists'] = 'manage_pilots_error_email_exists'; }
        }

        if (empty($add_pilot_errors_keys)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $db->query("INSERT INTO pilots (first_name, last_name, email, phone, password_hash, is_active) VALUES (:first_name, :last_name, :email, :phone, :password_hash, :is_active)");
                // ... bindings ...
                $db->bind(':first_name', $first_name);
                $db->bind(':last_name', $last_name);
                $db->bind(':email', $email);
                $db->bind(':phone', $phone);
                $db->bind(':password_hash', $password_hash);
                $db->bind(':is_active', $is_active, PDO::PARAM_INT);

                if ($db->execute()) {
                    $add_pilot_success_message_key = 'manage_pilots_success_pilot_added';
                    $add_pilot_success_message_params = ['pilotName' => escape_html($first_name . ' ' . $last_name)];
                    regenerate_csrf_token();
                } else {
                    $add_pilot_errors_keys['db'] = 'manage_pilots_error_db_add_fail';
                }
            } catch (PDOException $e) {
                error_log("Error adding pilot: " . $e->getMessage());
                $add_pilot_errors_keys['db'] = 'manage_pilots_error_db_generic';
            }
        }
    }
    if (!empty($add_pilot_errors_keys)) {
        $_SESSION['form_errors_add_pilot_keys'] = $add_pilot_errors_keys; // Store keys
        $_SESSION['form_data_add_pilot'] = $_POST;
    }
    if (!empty($add_pilot_success_message_key)) {
         $_SESSION['success_message_add_pilot_key'] = $add_pilot_success_message_key;
         $_SESSION['success_message_add_pilot_params'] = $add_pilot_success_message_params;
    }
    redirect(BASE_URL . '/admin/manage_pilots.php');
}

$form_errors_add_pilot_keys = $_SESSION['form_errors_add_pilot_keys'] ?? [];
$form_data_add_pilot = $_SESSION['form_data_add_pilot'] ?? [];
$success_message_add_pilot_key = $_SESSION['success_message_add_pilot_key'] ?? '';
$success_message_add_pilot_params = $_SESSION['success_message_add_pilot_params'] ?? [];
unset($_SESSION['form_errors_add_pilot_keys'], $_SESSION['form_data_add_pilot'], $_SESSION['success_message_add_pilot_key'], $_SESSION['success_message_add_pilot_params']);

$db->query("SELECT id, first_name, last_name, email, phone, is_active, created_at FROM pilots ORDER BY last_name, first_name");
$pilots = $db->resultSet();

require_once APP_ROOT . '/templates/layouts/header.php';
?>
<div class="container-fluid mt-4">
    <h2><?php echo escape_html(__(PAGE_TITLE_KEY)); ?></h2>
    <hr>
    <h3><?php echo escape_html(__('manage_pilots_add_new_pilot_h3')); ?></h3>
    <?php if (!empty($success_message_add_pilot_key)): ?>
        <div class="alert alert-success"><?php echo escape_html(__($success_message_add_pilot_key, $success_message_add_pilot_params)); ?></div>
    <?php endif; ?>
    <?php if (!empty($form_errors_add_pilot_keys)): ?>
        <div class="alert alert-danger">
            <strong><?php echo escape_html(__('error_please_correct')); ?></strong>
            <ul>
                <?php foreach ($form_errors_add_pilot_keys as $error_item): ?>
                    <?php
                       $err_key = is_array($error_item) ? $error_item[0] : $error_item;
                       $err_params = is_array($error_item) ? ($error_item[1] ?? []) : [];
                    ?>
                    <li><?php echo escape_html(__($err_key, $err_params)); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars(BASE_URL); ?>/admin/manage_pilots.php" method="POST" class="mb-5">
        <input type="hidden" name="add_pilot" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="first_name"><?php echo escape_html(__('manage_pilots_form_label_first_name')); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo escape_html(old('first_name', $form_data_add_pilot)); ?>" required>
            </div>
            <div class="form-group col-md-3">
                <label for="last_name"><?php echo escape_html(__('manage_pilots_form_label_last_name')); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo escape_html(old('last_name', $form_data_add_pilot)); ?>" required>
            </div>
            <div class="form-group col-md-3">
                <label for="email"><?php echo escape_html(__('manage_pilots_form_label_email')); ?> <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo escape_html(old('email', $form_data_add_pilot)); ?>" required>
            </div>
            <div class="form-group col-md-3">
                <label for="phone"><?php echo escape_html(__('manage_pilots_form_label_phone')); ?> <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo escape_html(old('phone', $form_data_add_pilot)); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="password"><?php echo escape_html(__('manage_pilots_form_label_password', ['minLength' => 8])); ?> <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group col-md-4">
                <label for="confirm_password"><?php echo escape_html(__('manage_pilots_form_label_confirm_password')); ?> <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group col-md-4 align-self-center pt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (old('is_active', $form_data_add_pilot, 1)) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">
                        <?php echo escape_html(__('manage_pilots_form_label_is_active_checkbox')); ?>
                    </label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo escape_html(__('manage_pilots_button_add_pilot')); ?></button>
    </form>

    <hr>
    <h3><?php echo escape_html(__('manage_pilots_existing_pilots_h3', ['count' => count($pilots)])); ?></h3>
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th><?php echo escape_html(__('manage_pilots_table_th_id')); ?></th>
                <th><?php echo escape_html(__('manage_pilots_table_th_name')); ?></th>
                <th><?php echo escape_html(__('manage_pilots_table_th_email')); ?></th>
                <th><?php echo escape_html(__('manage_pilots_table_th_phone')); ?></th>
                <th><?php echo escape_html(__('manage_pilots_table_th_status')); ?></th>
                <th><?php echo escape_html(__('manage_pilots_table_th_registered')); ?></th>
                <th><?php echo escape_html(__('manage_pilots_table_th_actions')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pilots)): ?>
                <tr>
                    <td colspan="7" class="text-center"><?php echo escape_html(__('manage_pilots_no_pilots_found')); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($pilots as $pilot): ?>
                    <tr>
                        <td><?php echo escape_html($pilot['id']); ?></td>
                        <td><?php echo escape_html($pilot['first_name'] . ' ' . $pilot['last_name']); ?></td>
                        <td><?php echo escape_html($pilot['email']); ?></td>
                        <td><?php echo escape_html($pilot['phone']); ?></td>
                        <td>
                            <?php if ($pilot['is_active']): ?>
                                <span class="badge badge-success"><?php echo escape_html(__('manage_pilots_status_active')); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo escape_html(__('manage_pilots_status_inactive')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape_html(date('Y-m-d', strtotime($pilot['created_at']))); ?></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-info disabled"><?php echo escape_html(__('manage_pilots_action_edit')); ?></a>
                            <a href="#" class="btn btn-sm btn-danger disabled"><?php echo escape_html(__('manage_pilots_action_delete')); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once APP_ROOT . '/templates/layouts/footer.php'; ?>
