<?php
// Ensure this file is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Access denied.');
}

// Start session if not already started (config should do this, but ensure)
if (session_status() == PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

/**
 * Escape HTML special characters for safe output.
 *
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function escape_html($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get old form input from session data or a default value.
 *
 * @param string $key The key for the form input.
 * @param array $data The array containing form data (usually $_SESSION['form_data'] or $_POST).
 * @param mixed $default The default value if key not found.
 * @return mixed The value from session or default.
 */
function old($key, $data, $default = '') {
    return $data[$key] ?? $default;
}

/**
 * Redirect to a given URL.
 *
 * @param string $url The URL to redirect to.
 * @return void
 */
function redirect($url) {
    if (headers_sent()) {
        echo "<script>window.location.href='" . addslashes($url) . "';</script>";
        exit;
    } else {
        header("Location: " . $url);
        exit;
    }
}

/**
 * Generate a CSRF token and store it in the session.
 * If a token already exists, it returns the existing one.
 *
 * @return string The CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token against the one stored in the session.
 * To prevent timing attacks, uses hash_equals.
 *
 * @param string $token The token to verify.
 * @return bool True if the token is valid, false otherwise.
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    $result = hash_equals($_SESSION['csrf_token'], $token);
    // Optionally: remove the token after first use for stricter security,
    // but this means forms can't be re-submitted on error without a new token.
    // if ($result) { unset($_SESSION['csrf_token']); }
    return $result;
}

/**
 * Regenerate CSRF token. Useful after successful form submission or login/logout.
 */
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<?php
    // ... (existing content of utils.php) ...

    /**
     * Check if a user is logged in.
     *
     * @return bool True if logged in, false otherwise.
     */
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require login for accessing a page. Redirects to login if not logged in.
     * Optionally checks for a specific user type (role).
     *
     * @param string|array|null $required_type The required user type(s) (e.g., 'admin', 'pilot', or ['admin', 'pilot']).
     *                                       If null, just checks for login.
     * @return void
     */
    function require_login($required_type = null) {
        if (!is_logged_in()) {
            $_SESSION['user_message'] = 'You need to log in to access this page.';
            $_SESSION['message_type'] = 'warning';
            // Store the intended URL to redirect back after login
            // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            redirect(BASE_URL . '/admin/login.php');
        }

        if ($required_type) {
            $user_type = $_SESSION['user_type'] ?? null;
            $is_authorized = false;
            if (is_array($required_type)) {
                if (in_array($user_type, $required_type, true)) {
                    $is_authorized = true;
                }
            } else {
                if ($user_type === $required_type) {
                    $is_authorized = true;
                }
            }

            if (!$is_authorized) {
                $_SESSION['user_message'] = 'You are not authorized to access this page.';
                $_SESSION['message_type'] = 'danger';
                // Redirect to a less privileged page or dashboard, or show an error page
                redirect(BASE_URL . '/admin/dashboard.php'); // Or index.php if dashboard is also restricted
            }
        }
    }

    /**
     * Check if the logged-in user is an admin.
     *
     * @return bool True if user is admin, false otherwise.
     */
    function is_admin() {
        return is_logged_in() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }

    /**
     * Check if the logged-in user is a pilot.
     * (Could also be an admin, if admins have pilot capabilities)
     * @return bool True if user is pilot, false otherwise.
     */
    function is_pilot() {
        return is_logged_in() && isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'pilot' || $_SESSION['user_type'] === 'admin');
    }
    ?>
