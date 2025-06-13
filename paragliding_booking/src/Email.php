<?php
// Ensure this file is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Access denied.');
}

// For PHPMailer, you would typically use Composer's autoloader
// require_once APP_ROOT . '/vendor/autoload.php';
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Sends an email.
 *
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $body Email body (HTML or plain text).
 * @param array $additional_headers Optional additional headers.
 * @param bool $isHTML Whether the email body is HTML. Defaults to true.
 * @return bool True if email was sent successfully, false otherwise.
 */
function send_application_email($to, $subject, $body, $additional_headers = [], $isHTML = true) {
    $from_name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : SITE_NAME;
    $from_email = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@example.com';

    $headers = [];
    $headers[] = "From: \"{$from_name}\" <{$from_email}>";
    $headers[] = "Reply-To: <{$from_email}>";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "MIME-Version: 1.0";
    if ($isHTML) {
        $headers[] = "Content-type: text/html; charset=UTF-8";
    } else {
        $headers[] = "Content-type: text/plain; charset=UTF-8";
    }

    if (!empty($additional_headers)) {
        foreach ($additional_headers as $header) {
            $headers[] = $header; // e.g., "Cc: someone@example.com"
        }
    }

    $header_string = implode("\r\n", $headers);

    // For testing without sending actual emails:
    $log_email = false; // Set to true to log instead of sending
    if ($log_email) {
        $log_message = "---- EMAIL LOG ----\r\n";
        $log_message .= "To: " . $to . "\r\n";
        $log_message .= "Subject: " . $subject . "\r\n";
        $log_message .= "Headers:\r\n" . $header_string . "\r\n";
        $log_message .= "Body:\r\n" . $body . "\r\n";
        $log_message .= "---- END EMAIL LOG ----\r\n\r\n";
        file_put_contents(APP_ROOT . '/email_log.txt', $log_message, FILE_APPEND);
        return true;
    }

    // Using PHP's mail() function.
    // For production, consider using a library like PHPMailer for more features and reliability (SMTP, attachments, etc.)
    if (mail($to, $subject, $body, $header_string)) {
        return true;
    } else {
        // Basic error logging if mail() fails
        error_log("Email sending failed. To: {$to}, Subject: {$subject}");
        return false;
    }
}

/*
// Example usage with PHPMailer (requires installation and configuration)
function send_application_email_phpmailer($to_email, $to_name, $subject, $html_body, $alt_body = '') {
    if (!defined('MAIL_HOST')) {
        error_log('Email settings not configured in config.php for PHPMailer.');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->SMTPDebug = 0; // SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : PHPMailer::ENCRYPTION_STARTTLS; // PHPMailer::ENCRYPTION_SMTPS or PHPMailer::ENCRYPTION_STARTTLS
        $mail->Port       = MAIL_PORT;

        //Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        // $mail->addReplyTo('info@example.com', 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = $alt_body ?: strip_tags($html_body); // Optional: plain text version

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
*/
?>
