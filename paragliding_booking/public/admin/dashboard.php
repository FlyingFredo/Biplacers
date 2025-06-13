<?php
define('PAGE_TITLE_KEY', 'page_title_dashboard');
require_once '../../config/config.php';
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';

require_login(['admin', 'pilot']); // Accessible by both admin and pilots

$db = new Database();

// Status filter
$allowed_statuses = ['pending_confirmation', 'confirmed', 'assigned', 'completed', 'cancelled_by_passenger', 'cancelled_by_pilot', 'cancelled_by_admin'];
$status_filter = $_GET['status_filter'] ?? 'all_active'; // Default filter

$sql = "SELECT fr.id, fr.desired_date, fr.status, fr.created_at AS request_submitted_at,
               p.first_name AS passenger_first_name, p.last_name AS passenger_last_name,
               p.email AS passenger_email, p.phone AS passenger_phone,
               pi.first_name AS pilot_first_name, pi.last_name AS pilot_last_name
        FROM flight_requests fr
        JOIN passengers p ON fr.passenger_id = p.id
        LEFT JOIN pilots pi ON fr.assigned_pilot_id = pi.id";

$where_clauses = [];
$bindings = [];

if ($status_filter === 'all_active') {
    $where_clauses[] = "fr.status IN ('pending_confirmation', 'confirmed', 'assigned')";
} elseif (in_array($status_filter, $allowed_statuses)) {
    $where_clauses[] = "fr.status = :status_filter";
    $bindings[':status_filter'] = $status_filter;
} elseif ($status_filter === 'all_cancelled') {
     $where_clauses[] = "fr.status LIKE 'cancelled_%'";
}
// else: no specific status filter or invalid filter, show all (or default to 'all_active' as done above)

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY fr.desired_date ASC, fr.created_at ASC";

$db->query($sql);
foreach($bindings as $key => $value) {
    $db->bind($key, $value);
}
$requests = $db->resultSet();

// Helper function to get translated status
function get_translated_status($status_key, $pilot_name = null) {
    $params = $pilot_name ? ['pilotName' => escape_html($pilot_name)] : [];
    // Construct the translation key for status
    $translation_key = 'dashboard_status_' . str_replace([' ', '(', ')'], ['_', '', ''], strtolower($status_key)); // Basic normalization
    return __($translation_key, $params, $status_key); // Fallback to the status key itself
}

require_once APP_ROOT . '/templates/layouts/header.php';
?>

<div class="container-fluid mt-4">
    <h2><?php echo escape_html(__(PAGE_TITLE_KEY)); ?></h2>

    <?php
    if (isset($_SESSION['user_message']) && !empty($_SESSION['user_message'])) {
        $message_key = $_SESSION['user_message'];
        $message_params = $_SESSION['user_message_params'] ?? [];
        $message_type = $_SESSION['message_type'] ?? 'info';
        echo '<div class="alert alert-' . htmlspecialchars($message_type) . '">' . escape_html(__($message_key, $message_params, $message_key)) . '</div>';
        unset($_SESSION['user_message'], $_SESSION['message_type'], $_SESSION['user_message_params']);
    }
    ?>

    <form method="GET" action="" class="form-inline mb-3">
        <div class="form-group mr-2">
            <label for="status_filter" class="mr-2"><?php echo escape_html(__('dashboard_filter_label_status')); ?></label>
            <select name="status_filter" id="status_filter" class="form-control">
                <option value="all_active" <?php echo ($status_filter === 'all_active') ? 'selected' : ''; ?>><?php echo escape_html(__('dashboard_filter_option_all_active')); ?></option>
                <option value="pending_confirmation" <?php echo ($status_filter === 'pending_confirmation') ? 'selected' : ''; ?>><?php echo escape_html(__('dashboard_filter_option_pending')); ?></option>
                <option value="confirmed" <?php echo ($status_filter === 'confirmed') ? 'selected' : ''; ?>><?php echo escape_html(__('dashboard_filter_option_confirmed')); ?></option>
                <option value="assigned" <?php echo ($status_filter === 'assigned') ? 'selected' : ''; ?>><?php echo escape_html(__('dashboard_filter_option_assigned')); ?></option>
                <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>><?php echo escape_html(__('dashboard_filter_option_completed')); ?></option>
                <option value="all_cancelled" <?php echo ($status_filter === 'all_cancelled') ? 'selected' : ''; ?>><?php echo escape_html(__('dashboard_filter_option_cancelled')); ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo escape_html(__('dashboard_button_filter')); ?></button>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th><?php echo escape_html(__('dashboard_table_th_id')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_passenger')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_email')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_phone')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_desired_date')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_status')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_submitted_at')); ?></th>
                    <th><?php echo escape_html(__('dashboard_table_th_actions')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center"><?php echo escape_html(__('dashboard_no_requests_found')); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo escape_html($request['id']); ?></td>
                            <td><?php echo escape_html($request['passenger_first_name'] . ' ' . $request['passenger_last_name']); ?></td>
                            <td><a href="mailto:<?php echo escape_html($request['passenger_email']); ?>"><?php echo escape_html($request['passenger_email']); ?></a></td>
                            <td><a href="tel:<?php echo escape_html($request['passenger_phone']); ?>"><?php echo escape_html($request['passenger_phone']); ?></a></td>
                            <td><?php echo escape_html(date('M j, Y', strtotime($request['desired_date']))); ?></td>
                            <td>
                                <?php
                                $pilotFullName = ($request['pilot_first_name'] && $request['pilot_last_name']) ? $request['pilot_first_name'] . ' ' . $request['pilot_last_name'] : null;
                                echo escape_html(get_translated_status($request['status'], $pilotFullName));
                                ?>
                            </td>
                            <td><?php echo escape_html(date('M j, Y H:i', strtotime($request['request_submitted_at']))); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/view_request.php?id=<?php echo escape_html($request['id']); ?>" class="btn btn-sm btn-info">
                                    <?php echo escape_html(__('dashboard_action_view_details')); ?>
                                </a>
                                <!-- Further actions like assign pilot, cancel, complete could go here based on user role and request status -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once APP_ROOT . '/templates/layouts/footer.php';
?>
