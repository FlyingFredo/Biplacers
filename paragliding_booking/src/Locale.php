<?php
// src/Locale.php

// Ensure this file is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Access denied.');
}

if (session_status() == PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

class Locale {
    private static $current_language = 'en'; // Default language
    private static $supported_languages = ['en', 'fr', 'es'];
    private static $translations = [];

    public static function initialize($default_lang = 'en') {
        self::$current_language = $default_lang; // Set initial default

        if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::$supported_languages)) {
            self::$current_language = $_SESSION['lang'];
        } elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], self::$supported_languages)) {
            self::$current_language = $_COOKIE['lang'];
            $_SESSION['lang'] = self::$current_language; // Sync to session
        }
        // TODO: Add Accept-Language header detection as a fallback

        self::loadLanguageFile(self::$current_language);
    }

    public static function setLanguage($lang_code) {
        if (in_array($lang_code, self::$supported_languages)) {
            self::$current_language = $lang_code;
            $_SESSION['lang'] = $lang_code;
            // Set a cookie for persistence (e.g., for 1 year)
            setcookie('lang', $lang_code, time() + (365 * 24 * 60 * 60), '/');
            self::loadLanguageFile(self::$current_language);
            return true;
        }
        return false;
    }

    public static function getLanguage() {
        return self::$current_language;
    }

    public static function getSupportedLanguages() {
        return self::$supported_languages;
    }

    private static function loadLanguageFile($lang_code) {
        $file_path = APP_ROOT . '/locale/' . $lang_code . '/messages.php';
        if (file_exists($file_path)) {
            self::$translations = require $file_path;
        } else {
            // Fallback to English if the language file doesn't exist
            error_log("Language file not found for: {$lang_code}. Falling back to English.");
            $file_path_en = APP_ROOT . '/locale/en/messages.php';
            if (file_exists($file_path_en)) {
                 self::$translations = require $file_path_en;
            } else {
                self::$translations = []; // No translations found
                error_log("English language file not found at: {$file_path_en}.");
            }
        }
    }

    /**
     * Get a translated string.
     * Uses ICU MessageFormatter for advanced formatting if parameters are provided.
     *
     * @param string $key The key of the string to translate.
     * @param array $params Parameters to replace in the string (for ICU MessageFormatter).
     * @param string|null $default Default value to return if key not found.
     * @return string The translated string or the key/default if not found.
     */
    public static function get($key, $params = [], $default = null) {
        $message = $default ?? $key; // Default to key if no specific default is given
        if (isset(self::$translations[$key])) {
            $message = self::$translations[$key];
        } else {
             error_log("Translation key '{$key}' not found for language '" . self::$current_language . "'.");
        }

        if (!empty($params) && class_exists('MessageFormatter')) {
            try {
                // MessageFormatter might throw an exception for malformed patterns
                $formatter = new MessageFormatter(self::$current_language, $message);
                if (!$formatter) { // Check if formatter creation failed
                    error_log("Failed to create MessageFormatter for key '{$key}' in language '" . self::$current_language . "'. Error code: " . intl_get_error_code() . " Message: " . intl_get_error_message());
                    // Fallback to simple string replacement if MessageFormatter fails to initialize
                    return self::simpleReplace($message, $params);
                }
                $formatted_message = $formatter->format($params);
                if ($formatted_message === false) {
                     error_log("MessageFormatter::format failed for key '{$key}'. ICU Error: " . $formatter->getErrorMessage() . " (Code: " . $formatter->getErrorCode() . ")");
                     // Fallback to simple string replacement if format fails
                     return self::simpleReplace($message, $params);
                }
                return $formatted_message;
            } catch (Exception $e) {
                error_log("Exception in MessageFormatter for key '{$key}': " . $e->getMessage());
                // Fallback to simple string replacement on exception
                return self::simpleReplace($message, $params);
            }
        }

        // If no params or MessageFormatter not available/failed, return the direct translation or key/default
        return $message;
    }

    // Simple placeholder replacement if MessageFormatter is not used or fails.
    private static function simpleReplace($message, $params) {
        foreach ($params as $placeholder => $value) {
            $message = str_replace('{' . $placeholder . '}', $value, $message);
        }
        return $message;
    }
}

// Initialize Locale class with a default language when this file is included.
// The actual default might be set in config.php or a bootstrap file.
Locale::initialize('en');

// Global helper function for convenience (similar to common i18n libraries)
if (!function_exists('__')) {
    function __($key, $params = [], $default = null) {
        return Locale::get($key, $params, $default);
    }
}
?>
