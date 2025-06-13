<?php
define('PAGE_TITLE_KEY', 'page_title_confirm_request');
require_once '../config/config.php';
require_once APP_ROOT . '/src/utils.php';
require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Email.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$token = $_GET['token'] ?? null;
$user_message_key = ''; // Will store the translation key
$user_message_params = [];
$message_type = 'danger';

if (empty($token)) {
    $user_message_key = 'confirm_token_missing';
} else {
    $db = new Database();
    try {
        $db->beginTransaction();

        $db->query("SELECT fr.id, fr.status, fr.token_expires_at, p.first_name AS passenger_first_name, p.email AS passenger_email, fr.desired_date, fr.other_date_available, p.last_name AS passenger_last_name, p.age AS passenger_age, p.weight AS passenger_weight, p.phone AS passenger_phone, fr.notes_passenger FROM flight_requests fr JOIN passengers p ON fr.passenger_id = p.id WHERE fr.confirmation_token = :token");
        $db->bind(':token', $token);
        $request = $db->single();

        if (!$request) {
            $user_message_key = 'confirm_token_invalid_or_expired';
        } elseif ($request['status'] === 'confirmed') {
            $user_message_key = 'confirm_already_confirmed';
            $message_type = 'info';
        } elseif (new DateTime() > new DateTime($request['token_expires_at'])) {
            $user_message_key = 'confirm_token_expired_new_request';
        } else {
            $db->query("UPDATE flight_requests SET status = 'confirmed', confirmation_token = NULL, token_expires_at = NULL WHERE id = :id AND status = 'pending_confirmation'");
            $db->bind(':id', $request['id']);

            if ($db->execute() && $db->rowCount() > 0) {
                $user_message_key = 'confirm_success';
                $user_message_params = ['passengerName' => escape_html($request['passenger_first_name']), 'desiredDate' => escape_html($request['desired_date'])];
                $message_type = 'success';

                // Passenger Confirmation Email
                $passenger_subject = __('confirm_email_summary_subject', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)]);
                $passenger_body = "<p>" . __('confirm_email_summary_body_greeting', ['passengerName' => escape_html($request['passenger_first_name'])]) . "</p>";
                $passenger_body .= "<p>" . __('confirm_email_summary_body_line1', ['desiredDate' => escape_html($request['desired_date'])]) . "</p>";
                $passenger_body .= "<p><strong>" . __('confirm_email_summary_body_request_details_title') . "</strong><br>";
                $passenger_body .= __('confirm_email_summary_body_passenger_name', ['passengerFullName' => escape_html($request['passenger_first_name'] . ' ' . $request['passenger_last_name'])]) . "<br>";
                $passenger_body .= __('confirm_email_summary_body_passenger_email', ['passengerEmail' => escape_html($request['passenger_email'])]) . "<br>";
                $passenger_body .= __('confirm_email_summary_body_passenger_phone', ['passengerPhone' => escape_html($request['passenger_phone'])]) . "<br>";
                $passenger_body .= __('confirm_email_summary_body_passenger_age', ['passengerAge' => escape_html($request['passenger_age'])]) . "<br>";
                $passenger_body .= __('confirm_email_summary_body_passenger_weight', ['passengerWeight' => escape_html($request['passenger_weight'])]) . "<br>";
                $passenger_body .= __('confirm_email_summary_body_desired_date', ['desiredDate' => escape_html($request['desired_date'])]) . "<br>";
                $passenger_body .= __('confirm_email_summary_body_flexible_date', ['isFlexible' => ($request['other_date_available'] ? __('value_yes') : __('value_no'))]) . "<br>";
                if(!empty($request['notes_passenger'])) {
                    $passenger_body .= __('confirm_email_summary_body_notes', ['notes' => escape_html($request['notes_passenger'])]) . "<br>";
                }
                $passenger_body .= "</p>";
                $passenger_body .= "<p>" . __('confirm_email_summary_body_line2') . "</p>";
                $passenger_body .= "<p>" . __('confirm_email_summary_body_thank_you') . "<br>" . __('confirm_email_summary_body_team_name', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)]) . "</p>";

                if (!send_application_email($request['passenger_email'], $passenger_subject, $passenger_body)) {
                    error_log("Failed to send confirmation summary email to passenger " . $request['passenger_email'] . " for request ID " . $request['id']);
                }

                // Pilot Notification Email
                $db->query("SELECT email, first_name FROM pilots WHERE is_active = TRUE");
                $pilots = $db->resultSet();
                if ($pilots) {
                    $pilot_subject = __('pilot_notification_subject_new_request', ['siteName' => __(SITE_NAME_KEY ?? 'site_name', [], SITE_NAME)]);
                    $dashboard_link = BASE_URL . '/admin/dashboard.php';

                    $pilot_body_intro = "<p>" . __('pilot_notification_body_line1') . "</p>";
                    $pilot_body_intro .= "<p><a href='" . $dashboard_link . "'>" . __('pilot_notification_body_link_dashboard') . "</a></p>";
                    $pilot_body_intro .= "<p><strong>" . __('pilot_notification_body_passenger_details_title') . "</strong><br>";
                    $pilot_body_intro .= __('confirm_email_summary_body_passenger_name', ['passengerFullName' => escape_html($request['passenger_first_name'] . ' ' . $request['passenger_last_name'])]) . "<br>";
                    $pilot_body_intro .= __('confirm_email_summary_body_passenger_email', ['passengerEmail' => escape_html($request['passenger_email'])]) . "<br>";
                    $pilot_body_intro .= __('confirm_email_summary_body_passenger_phone', ['passengerPhone' => escape_html($request['passenger_phone'])]) . "<br>";
                    $pilot_body_intro .= __('confirm_email_summary_body_desired_date', ['desiredDate' => escape_html($request['desired_date'])]) . "<br>";
                    $pilot_body_intro .= __('confirm_email_summary_body_flexible_date', ['isFlexible' => ($request['other_date_available'] ? __('value_yes') : __('value_no'))]) . "<br>";
                    if(!empty($request['notes_passenger'])) {
                        $pilot_body_intro .= __('confirm_email_summary_body_notes', ['notes' => escape_html($request['notes_passenger'])]) . "<br>";
                    }
                    $pilot_body_intro .= "</p>";

                    foreach ($pilots as $pilot) {
                        $pilot_email_body = "<p>" . __('pilot_notification_body_greeting', ['pilotName' => escape_html($pilot['first_name'])]) . "</p>" . $pilot_body_intro;
                        if (!send_application_email($pilot['email'], $pilot_subject, $pilot_email_body)) {
                            error_log("Failed to send new request notification to pilot " . $pilot['email'] . " for request ID " . $request['id']);
                        }
                    }
                }
                $db->commit();
            } else {
                $db->rollBack();
                $db->query("SELECT status FROM flight_requests WHERE id = :id");
                $db->bind(':id', $request['id']);
                $current_status_check = $db->single();
                if ($current_status_check && $current_status_check['status'] === 'confirmed') {
                     $user_message_key = 'confirm_already_confirmed';
                     $message_type = 'info';
                } else {
                    $user_message_key = 'confirm_failure_unexpected';
                    error_log("Failed to confirm request ID " . $request['id'] . ". Update rowCount was 0 or execute failed.");
                }
            }
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Database error during request confirmation: " . $e->getMessage() . " | Token: " . $token);
        $user_message_key = 'submit_request_db_error_user';
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("General error during request confirmation: " . $e->getMessage() . " | Token: " . $token);
         $user_message_key = 'submit_request_unexpected_error_user';
    }
}

$_SESSION['user_message'] = $user_message_key;
$_SESSION['user_message_params'] = $user_message_params;
$_SESSION['message_type'] = $message_type;

redirect(BASE_URL . '/index.php');
?>
