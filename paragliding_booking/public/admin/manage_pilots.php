<?php
define('PAGE_TITLE', 'Manage Pilots');
require_once '../../config/config.php'; // Adjusted path
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';

require_login('admin'); // Only admins can manage pilots

$db = new Database();

// Handle Add Pilot Form Submission
$add_pilot_errors = [];
$add_pilot_success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pilot'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $add_pilot_errors['csrf'] = 'Invalid security token. Please try again.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (empty($first_name)) $add_pilot_errors['first_name'] = 'First name is required.';
        if (empty($last_name)) $add_pilot_errors['last_name'] = 'Last name is required.';
        if (!$email) $add_pilot_errors['email'] = 'Valid email is required.';
        if (empty($phone)) $add_pilot_errors['phone'] = 'Phone number is required.';
        if (empty($password)) $add_pilot_errors['password'] = 'Password is required.';
        if (strlen($password) < 8) $add_pilot_errors['password_length'] = 'Password must be at least 8 characters long.';
        if ($password !== $confirm_password) $add_pilot_errors['confirm_password'] = 'Passwords do not match.';

        // Check if email already exists
        if ($email) {
            $db->query("SELECT id FROM pilots WHERE email = :email");
            $db->bind(':email', $email);
            if ($db->single()) {
                $add_pilot_errors['email_exists'] = 'This email address is already registered.';
            }
        }

        if (empty($add_pilot_errors)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $db->query("INSERT INTO pilots (first_name, last_name, email, phone, password_hash, is_active)
                            VALUES (:first_name, :last_name, :email, :phone, :password_hash, :is_active)");
                $db->bind(':first_name', $first_name);
                $db->bind(':last_name', $last_name);
                $db->bind(':email', $email);
                $db->bind(':phone', $phone);
                $db->bind(':password_hash', $password_hash);
                $db->bind(':is_active', $is_active, PDO::PARAM_INT);

                if ($db->execute()) {
                    $add_pilot_success_message = 'Pilot ' . escape_html($first_name) . ' ' . escape_html($last_name) . ' added successfully!';
                    regenerate_csrf_token(); // Regenerate token after successful submission
                } else {
                    $add_pilot_errors['db'] = 'Failed to add pilot to the database. Please try again.';
                }
            } catch (PDOException $e) {
                error_log("Error adding pilot: " . $e->getMessage());
                $add_pilot_errors['db'] = 'Database error occurred while adding pilot.';
            }
        }
    }
     // Store errors/success messages and POST data in session for PRG pattern
    if (!empty($add_pilot_errors)) {
        $_SESSION['form_errors_add_pilot'] = $add_pilot_errors;
        $_SESSION['form_data_add_pilot'] = $_POST;
    }
    if (!empty($add_pilot_success_message)) {
         $_SESSION['success_message_add_pilot'] = $add_pilot_success_message;
    }
    // Redirect to self to prevent form resubmission
    redirect(BASE_URL . '/admin/manage_pilots.php');
}

// Retrieve messages and data from session for PRG pattern
$form_errors_add_pilot = $_SESSION['form_errors_add_pilot'] ?? [];
$form_data_add_pilot = $_SESSION['form_data_add_pilot'] ?? [];
$success_message_add_pilot = $_SESSION['success_message_add_pilot'] ?? '';
unset($_SESSION['form_errors_add_pilot'], $_SESSION['form_data_add_pilot'], $_SESSION['success_message_add_pilot']);


// Fetch existing pilots
$db->query("SELECT id, first_name, last_name, email, phone, is_active, created_at FROM pilots ORDER BY last_name, first_name");
$pilots = $db->resultSet();

require_once APP_ROOT . '/templates/layouts/header.php';
?>

<div class="container-fluid mt-4">
    <h2><?php echo PAGE_TITLE; ?></h2>

    <hr>
    <h3>Add New Pilot</h3>
    <?php if (!empty($success_message_add_pilot)): ?>
        <div class="alert alert-success"><?php echo escape_html($success_message_add_pilot); ?></div>
    <?php endif; ?>
    <?php if (!empty($form_errors_add_pilot)): ?>
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach ($form_errors_add_pilot as $error): ?>
                    <li><?php echo escape_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars(BASE_URL); ?>/admin/manage_pilots.php" method="POST" class="mb-5">
        <input type="hidden" name="add_pilot" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="first_name">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo escape_html(old('first_name', $form_data_add_pilot)); ?>" required>
            </div>
            <div class="form-group col-md-3">
                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo escape_html(old('last_name', $form_data_add_pilot)); ?>" required>
            </div>
            <div class="form-group col-md-3">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo escape_html(old('email', $form_data_add_pilot)); ?>" required>
            </div>
            <div class="form-group col-md-3">
                <label for="phone">Phone <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo escape_html(old('phone', $form_data_add_pilot)); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="password">Password (min 8 chars) <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group col-md-4">
                <label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group col-md-4 align-self-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (old('is_active', $form_data_add_pilot, 1)) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">
                        Pilot is Active
                    </label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Pilot</button>
    </form>

    <hr>
    <h3>Existing Pilots (<?php echo count($pilots); ?>)</h3>
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pilots)): ?>
                <tr>
                    <td colspan="7" class="text-center">No pilots found.</td>
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
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape_html(date('Y-m-d', strtotime($pilot['created_at']))); ?></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-info disabled">Edit</a> <!-- TODO: Implement Edit -->
                            <a href="#" class="btn btn-sm btn-danger disabled">Delete</a> <!-- TODO: Implement Delete -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
