<?php
define('PAGE_TITLE_KEY', 'page_title_view_request');
require_once '../../config/config.php';
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';

require_login(['admin', 'pilot']); // Accessible by admin and pilots

$request_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$db = new Database();
$request_details = null;
$available_pilots = []; // For admin to assign pilot

if (!$request_id) {
    $_SESSION['user_message'] = 'view_request_error_no_id';
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/admin/dashboard.php');
}

// Fetch request details
$sql = "SELECT fr.*,
               p.first_name AS passenger_first_name, p.last_name AS passenger_last_name,
               p.email AS passenger_email, p.phone AS passenger_phone,
               p.age AS passenger_age, p.weight AS passenger_weight,
               pi.first_name AS pilot_first_name, pi.last_name AS pilot_last_name
        FROM flight_requests fr
        JOIN passengers p ON fr.passenger_id = p.id
        LEFT JOIN pilots pi ON fr.assigned_pilot_id = pi.id
        WHERE fr.id = :request_id";
$db->query($sql);
$db->bind(':request_id', $request_id);
$request_details = $db->single();

if (!$request_details) {
    $_SESSION['user_message'] = 'view_request_error_not_found';
    $_SESSION['message_type'] = 'danger';
    redirect(BASE_URL . '/admin/dashboard.php');
}

// If admin, fetch available pilots for assignment dropdown
if (is_admin()) {
    $db->query("SELECT id, first_name, last_name FROM pilots WHERE is_active = TRUE ORDER BY last_name, first_name");
    $available_pilots = $db->resultSet();
}

// Handle form submissions for actions (status update, pilot assignment, notes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
    $action_success = false;
    $action_message_key = '';
    $action_message_params = [];

    try {
        if (is_admin() && isset($_POST['assign_pilot'])) {
            $pilot_to_assign_id = filter_input(INPUT_POST, 'assigned_pilot_id', FILTER_VALIDATE_INT);
            $admin_notes_assign = trim($_POST['admin_pilot_notes_assign'] ?? $request_details['notes_admin_pilot']);

            if ($pilot_to_assign_id) {
                $db->query("UPDATE flight_requests SET assigned_pilot_id = :pilot_id, status = 'assigned', notes_admin_pilot = :notes WHERE id = :id");
                $db->bind(':pilot_id', $pilot_to_assign_id);
            } else { // Unassign
                $db->query("UPDATE flight_requests SET assigned_pilot_id = NULL, status = 'confirmed', notes_admin_pilot = :notes WHERE id = :id");
            }
            $db->bind(':notes', $admin_notes_assign);
            $db->bind(':id', $request_id);
            if ($db->execute()) {
                $action_success = true;
                $action_message_key = 'view_request_message_pilot_assigned';
                // TODO: Notify assigned pilot / unassigned pilot
            }
        } elseif (isset($_POST['update_status'])) {
            $new_status = $_POST['new_status'] ?? '';
            // Add more validation for allowed status transitions based on role
            $allowed_new_statuses = ['completed', 'cancelled_by_admin', 'cancelled_by_pilot'];
            if (is_admin() && in_array($new_status, $allowed_new_statuses)) {
                // Admin can set to completed or cancel
            } elseif (is_pilot() && $new_status === 'cancelled_by_pilot' && $request_details['assigned_pilot_id'] == $_SESSION['user_id']) {
                // Pilot can cancel their own assigned flight
            } else {
                $new_status = null; // Invalid status change attempt
            }

            if ($new_status) {
                $db->query("UPDATE flight_requests SET status = :status WHERE id = :id");
                $db->bind(':status', $new_status);
                $db->bind(':id', $request_id);
                if ($db->execute()) {
                    $action_success = true;
                    $action_message_key = 'view_request_message_status_updated';
                    // TODO: Notify passenger if cancelled/completed
                }
            }
        } elseif (isset($_POST['save_admin_notes'])) {
             $admin_notes = trim($_POST['admin_pilot_notes'] ?? '');
             $db->query("UPDATE flight_requests SET notes_admin_pilot = :notes WHERE id = :id");
             $db->bind(':notes', $admin_notes);
             $db->bind(':id', $request_id);
             if ($db->execute()) {
                $action_success = true;
                $action_message_key = 'view_request_message_notes_updated';
             }
        }

        if ($action_success) {
            $_SESSION['user_message'] = $action_message_key;
            $_SESSION['message_type'] = 'success';
        } elseif (empty($action_message_key) && $_SERVER['REQUEST_METHOD'] === 'POST') { // A post was made but no action matched or no success
             $_SESSION['user_message'] = 'view_request_message_update_failed';
             $_SESSION['message_type'] = 'danger';
        }
        // Regenerate CSRF for next action and reload data
        regenerate_csrf_token();
        redirect(BASE_URL . '/admin/view_request.php?id=' . $request_id);

    } catch (PDOException $e) {
        error_log("Error updating request {$request_id}: " . $e->getMessage());
        $_SESSION['user_message'] = 'submit_request_db_error_user'; // Re-use generic db error
        $_SESSION['message_type'] = 'danger';
        redirect(BASE_URL . '/admin/view_request.php?id=' . $request_id);
    }
}


// Helper function to get translated status (could be moved to utils if used elsewhere)
function get_translated_status_view($status_key, $pilot_name = null) {
    $params = $pilot_name ? ['pilotName' => escape_html($pilot_name)] : [];
    $translation_key = 'dashboard_status_' . str_replace([' ', '(', ')'], ['_', '', ''], strtolower($status_key));
    return __($translation_key, $params, $status_key);
}

$page_title_params_for_header = ['requestId' => $request_details['id']];
define('PAGE_TITLE_PARAMS', $page_title_params_for_header);

require_once APP_ROOT . '/templates/layouts/header.php';
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4"><?php echo escape_html(__(PAGE_TITLE_KEY, ['requestId' => $request_details['id']])); ?></h2>

    <?php
    if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
        $message_key = $_SESSION['user_message'];
        $message_params = $_SESSION['user_message_params'] ?? [];
        $message_type = $_SESSION['message_type'] ?? 'info';
        echo '<div class="alert alert-' . htmlspecialchars($message_type) . '">' . escape_html(__($message_key, $message_params, $message_key)) . '</div>';
        unset($_SESSION['user_message'], $_SESSION['message_type'], $_SESSION['user_message_params']);
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo escape_html(__('view_request_section_passenger_details')); ?></h4>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_name')); ?></dt>
                <dd class="col-sm-9"><?php echo escape_html($request_details['passenger_first_name'] . ' ' . $request_details['passenger_last_name']); ?></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_email')); ?></dt>
                <dd class="col-sm-9"><a href="mailto:<?php echo escape_html($request_details['passenger_email']); ?>"><?php echo escape_html($request_details['passenger_email']); ?></a></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_phone')); ?></dt>
                <dd class="col-sm-9"><a href="tel:<?php echo escape_html($request_details['passenger_phone']); ?>"><?php echo escape_html($request_details['passenger_phone']); ?></a></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_age')); ?></dt>
                <dd class="col-sm-9"><?php echo escape_html($request_details['passenger_age']); ?></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_weight')); ?></dt>
                <dd class="col-sm-9"><?php echo escape_html($request_details['passenger_weight']); ?> kg</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo escape_html(__('view_request_section_flight_details')); ?></h4>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_desired_date')); ?></dt>
                <dd class="col-sm-9"><?php echo escape_html(date('M j, Y', strtotime($request_details['desired_date']))); ?></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_other_date_available')); ?></dt>
                <dd class="col-sm-9"><?php echo $request_details['other_date_available'] ? escape_html(__('value_yes')) : escape_html(__('value_no')); ?></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_status')); ?></dt>
                <dd class="col-sm-9"><strong><?php echo escape_html(get_translated_status_view($request_details['status'], $request_details['pilot_first_name'] . ' ' . $request_details['pilot_last_name'])); ?></strong></dd>

                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_submitted_at')); ?></dt>
                <dd class="col-sm-9"><?php echo escape_html(date('M j, Y H:i:s', strtotime($request_details['created_at']))); ?></dd>

                <?php if(!empty($request_details['notes_passenger'])): ?>
                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_passenger_notes')); ?></dt>
                <dd class="col-sm-9"><?php echo nl2br(escape_html($request_details['notes_passenger'])); ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo escape_html(__('view_request_section_pilot_assignment')); ?></h4>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_assigned_pilot')); ?></dt>
                <dd class="col-sm-9">
                    <?php if ($request_details['assigned_pilot_id'] && $request_details['pilot_first_name']): ?>
                        <?php echo escape_html($request_details['pilot_first_name'] . ' ' . $request_details['pilot_last_name']); ?>
                    <?php else: ?>
                        <?php echo escape_html(__('view_request_label_not_assigned')); ?>
                    <?php endif; ?>
                </dd>
            </dl>

            <?php if (is_admin()): // Admin can assign/change pilot and add notes ?>
            <hr>
            <h5><?php echo escape_html(__('view_request_action_assign_pilot')); ?> / <?php echo escape_html(__('view_request_label_admin_pilot_notes')); ?></h5>
            <form method="POST" action="" class="mt-3">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group">
                    <label for="assigned_pilot_id"><?php echo escape_html(__('view_request_action_assign_pilot')); ?></label>
                    <select name="assigned_pilot_id" id="assigned_pilot_id" class="form-control">
                        <option value=""><?php echo escape_html(__('view_request_select_pilot_prompt')); ?> (<?php echo escape_html(__('Unassign')); ?>)</option>
                        <?php foreach ($available_pilots as $pilot): ?>
                            <option value="<?php echo escape_html($pilot['id']); ?>" <?php echo ($request_details['assigned_pilot_id'] == $pilot['id']) ? 'selected' : ''; ?>>
                                <?php echo escape_html($pilot['first_name'] . ' ' . $pilot['last_name']); ?> (ID: <?php echo escape_html($pilot['id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admin_pilot_notes_assign"><?php echo escape_html(__('view_request_label_admin_pilot_notes')); ?></label>
                    <textarea name="admin_pilot_notes_assign" id="admin_pilot_notes_assign" class="form-control" rows="3"><?php echo escape_html($request_details['notes_admin_pilot']); ?></textarea>
                </div>
                <button type="submit" name="assign_pilot" class="btn btn-primary"><?php echo escape_html(__('view_request_button_assign')); ?></button>
            </form>
            <?php else: // Non-admin users (pilots) can only see notes if they exist ?>
                <?php if(!empty($request_details['notes_admin_pilot'])): ?>
                <dl class="row mt-3">
                    <dt class="col-sm-3"><?php echo escape_html(__('view_request_label_admin_pilot_notes')); ?></dt>
                    <dd class="col-sm-9"><?php echo nl2br(escape_html($request_details['notes_admin_pilot'])); ?></dd>
                </dl>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_admin() || (is_pilot() && $request_details['assigned_pilot_id'] == $_SESSION['user_id'])): // Actions for admin or assigned pilot ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo escape_html(__('manage_pilots_table_th_actions')); // Reusing key ?></h4>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="d-inline mr-2">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="new_status" value="completed">
                <button type="submit" name="update_status" class="btn btn-success" onclick="return confirm('<?php echo escape_html(__('view_request_confirm_complete')); ?>');">
                    <?php echo escape_html(__('view_request_action_mark_completed')); ?>
                </button>
            </form>

            <form method="POST" action="" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="new_status" value="<?php echo is_admin() ? 'cancelled_by_admin' : 'cancelled_by_pilot'; ?>">
                <button type="submit" name="update_status" class="btn btn-danger" onclick="return confirm('<?php echo escape_html(__('view_request_confirm_cancel')); ?>');">
                    <?php echo escape_html(__('view_request_action_cancel_request')); ?>
                </button>
            </form>
             <?php if (is_admin()): // Admin can save notes separately ?>
            <form method="POST" action="" class="mt-3">
                 <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                 <div class="form-group">
                    <label for="admin_pilot_notes"><?php echo escape_html(__('view_request_label_admin_pilot_notes')); ?> (<?php echo escape_html(__('Save separately')); ?>)</label>
                    <textarea name="admin_pilot_notes" id="admin_pilot_notes" class="form-control" rows="3"><?php echo escape_html($request_details['notes_admin_pilot']); ?></textarea>
                </div>
                <button type="submit" name="save_admin_notes" class="btn btn-secondary"><?php echo escape_html(__('view_request_button_save_notes')); ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <a href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo escape_html(__('view_request_back_to_dashboard')); ?>
    </a>
</div>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
