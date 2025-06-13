<?php
define('PAGE_TITLE', 'Confirm Your Request');
require_once '../config/config.php'; // Defines APP_ROOT, BASE_URL, etc.
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Email.php';

// Start session if not already started (config should do this, but ensure)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? null;
$user_message = '';
$message_type = 'danger'; // Default to danger

if (empty($token)) {
    $user_message = 'No confirmation token provided. Please use the link from your email.';
} else {
    $db = new Database();
    try {
        $db->beginTransaction();

        // Find the flight request by token
        $db->query("SELECT fr.id, fr.status, fr.token_expires_at,
                          p.first_name AS passenger_first_name, p.email AS passenger_email,
                          fr.desired_date, fr.other_date_available, p.last_name AS passenger_last_name,
                          p.age AS passenger_age, p.weight AS passenger_weight, p.phone AS passenger_phone,
                          fr.notes_passenger
                   FROM flight_requests fr
                   JOIN passengers p ON fr.passenger_id = p.id
                   WHERE fr.confirmation_token = :token");
        $db->bind(':token', $token);
        $request = $db->single();

        if (!$request) {
            $user_message = 'Invalid or expired confirmation token. Your request could not be found.';
        } elseif ($request['status'] === 'confirmed') {
            $user_message = 'This flight request has already been confirmed.';
            $message_type = 'info';
        } elseif (new DateTime() > new DateTime($request['token_expires_at'])) {
            // Optionally: Allow regeneration or contact support
            // For now, just mark as expired. Could also delete the token here.
            // $db->query("UPDATE flight_requests SET confirmation_token = NULL, token_expires_at = NULL WHERE id = :id");
            // $db->bind(':id', $request['id']);
            // $db->execute();
            $user_message = 'This confirmation token has expired. Please submit a new request or contact support.';
        } else {
            // Token is valid, not expired, and request is not already confirmed
            $db->query("UPDATE flight_requests
                       SET status = 'confirmed', confirmation_token = NULL, token_expires_at = NULL
                       WHERE id = :id AND status = 'pending_confirmation'"); // Ensure it's still pending
            $db->bind(':id', $request['id']);

            if ($db->execute() && $db->rowCount() > 0) {
                // Successfully updated
                $user_message = 'Thank you, ' . escape_html($request['passenger_first_name']) . '! Your flight request for ' . escape_html($request['desired_date']) . ' has been confirmed.';
                $message_type = 'success';

                // --- Send confirmation email to passenger ---
                $passenger_subject = "Flight Request Confirmed - " . SITE_NAME;
                $passenger_body = "<p>Dear " . escape_html($request['passenger_first_name']) . ",</p>";
                $passenger_body .= "<p>Your flight request for " . escape_html($request['desired_date']) . " has been successfully confirmed.</p>";
                $passenger_body .= "<p><strong>Request Summary:</strong><br>";
                $passenger_body .= "Name: " . escape_html($request['passenger_first_name']) . " " . escape_html($request['passenger_last_name']) . "<br>";
                $passenger_body .= "Email: " . escape_html($request['passenger_email']) . "<br>";
                $passenger_body .= "Phone: " . escape_html($request['passenger_phone']) . "<br>";
                $passenger_body .= "Age: " . escape_html($request['passenger_age']) . "<br>";
                $passenger_body .= "Weight: " . escape_html($request['passenger_weight']) . " kg<br>";
                $passenger_body .= "Desired Date: " . escape_html($request['desired_date']) . "<br>";
                $passenger_body .= "Flexible with date: " . ($request['other_date_available'] ? 'Yes' : 'No') . "<br>";
                if(!empty($request['notes_passenger'])) {
                    $passenger_body .= "Notes: " . escape_html($request['notes_passenger']) . "<br>";
                }
                $passenger_body .= "</p>";
                $passenger_body .= "<p>A pilot will contact you regarding your flight. If you have any questions, please contact us.</p>";
                $passenger_body .= "<p>Thank you,<br>" . SITE_NAME . " Team</p>";

                if (!send_application_email($request['passenger_email'], $passenger_subject, $passenger_body)) {
                    error_log("Failed to send confirmation summary email to passenger " . $request['passenger_email'] . " for request ID " . $request['id']);
                    // Don't change $user_message here, primary action (confirmation) was successful. Maybe log for admin.
                }

                // --- Send notification email to ALL active pilots ---
                $db->query("SELECT email, first_name FROM pilots WHERE is_active = TRUE");
                $pilots = $db->resultSet();

                if ($pilots) {
                    $pilot_subject = "New Confirmed Flight Request - " . SITE_NAME;
                    $dashboard_link = BASE_URL . '/admin/dashboard.php'; // Or a specific request view link: BASE_URL . '/admin/view_request.php?id=' . $request['id']

                    $pilot_body_intro = "<p>A new tandem paragliding flight request has been confirmed and requires attention.</p>";
                    $pilot_body_intro .= "<p><a href='" . $dashboard_link . "'>View on Dashboard</a></p>";
                    $pilot_body_intro .= "<p><strong>Passenger Details:</strong><br>";
                    $pilot_body_intro .= "Name: " . escape_html($request['passenger_first_name']) . " " . escape_html($request['passenger_last_name']) . "<br>";
                    $pilot_body_intro .= "Email: " . escape_html($request['passenger_email']) . "<br>";
                    $pilot_body_intro .= "Phone: " . escape_html($request['passenger_phone']) . "<br>";
                    $pilot_body_intro .= "Age: " . escape_html($request['passenger_age']) . "<br>";
                    $pilot_body_intro .= "Weight: " . escape_html($request['passenger_weight']) . " kg<br>";
                    $pilot_body_intro .= "Desired Date: " . escape_html($request['desired_date']) . "<br>";
                    $pilot_body_intro .= "Flexible with date: " . ($request['other_date_available'] ? 'Yes' : 'No') . "<br>";
                     if(!empty($request['notes_passenger'])) {
                        $pilot_body_intro .= "Passenger Notes: " . escape_html($request['notes_passenger']) . "<br>";
                    }
                    $pilot_body_intro .= "</p>";

                    foreach ($pilots as $pilot) {
                        $pilot_email_body = "<p>Hello " . escape_html($pilot['first_name']) . ",</p>" . $pilot_body_intro;
                        if (!send_application_email($pilot['email'], $pilot_subject, $pilot_email_body)) {
                            error_log("Failed to send new request notification to pilot " . $pilot['email'] . " for request ID " . $request['id']);
                        }
                    }
                }
                $db->commit();
            } else {
                // Confirmation failed, perhaps already confirmed by another process or rowCount was 0
                $db->rollBack(); // Rollback if update didn't affect any rows as expected
                // Check current status again to provide a more accurate message
                $db->query("SELECT status FROM flight_requests WHERE id = :id");
                $db->bind(':id', $request['id']);
                $current_status_check = $db->single();
                if ($current_status_check && $current_status_check['status'] === 'confirmed') {
                     $user_message = 'This flight request has already been confirmed.';
                     $message_type = 'info';
                } else {
                    $user_message = 'Could not confirm your request due to an unexpected issue. Please try again or contact support.';
                    error_log("Failed to confirm request ID " . $request['id'] . ". Update rowCount was 0 or execute failed.");
                }
            }
        }

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Database error during request confirmation: " . $e->getMessage() . " | Token: " . $token);
        $user_message = "A database error occurred while confirming your request. Please try again later.";
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("General error during request confirmation: " . $e->getMessage() . " | Token: " . $token);
        $user_message = "An unexpected error occurred. Please try again.";
    }
}

$_SESSION['user_message'] = $user_message;
$_SESSION['message_type'] = $message_type;

// Redirect to a page that can display the message (e.g., index or a dedicated message page)
// For simplicity, redirecting to index.php which has message display logic.
redirect(BASE_URL . '/index.php');

// Fallback if redirect fails or for direct script access testing (though redirect should handle it)
// require_once APP_ROOT . '/templates/layouts/header.php';
// echo '<div class="container mt-5"><div class="alert alert-' . htmlspecialchars($message_type) . '">' . htmlspecialchars($user_message) . '</div>';
// echo '<p><a href="' . htmlspecialchars(BASE_URL) . '/index.php">Go to Homepage</a></p></div>';
// require_once APP_ROOT . '/templates/layouts/footer.php';

?>
