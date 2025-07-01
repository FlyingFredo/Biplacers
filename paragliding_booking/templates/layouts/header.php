<?php
// ... (existing header PHP code from previous step) ...
if (session_status() == PHP_SESSION_NONE && php_sapi_name() !== 'cli') { session_start(); }

// Check if the config file has already been loaded.
// This prevents redefining constants and re-declaring classes if header.php is included
// by a script that has already loaded config.php.
if (!defined('CONFIG_LOADED')) {
    $config_path = dirname(__DIR__, 2) . '/config/config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    } else {
        // If config.php is essential and not found, terminate.
        // This is a fallback for cases where header.php might be included standalone
        // or by a script that doesn't load config.php first.
        die("Critical configuration file not found. Ensure config.php is loaded before this header.");
    }
}

// Proceed with header setup, assuming config (and Locale class) is now available.
$page_title_key = defined('PAGE_TITLE_KEY') ? PAGE_TITLE_KEY : 'page_title_default';
$page_title_params = defined('PAGE_TITLE_PARAMS') ? PAGE_TITLE_PARAMS : [];
$current_lang = Locale::getLanguage();
$supported_langs = Locale::getSupportedLanguages();
?>
<!DOCTYPE html>
<html lang="<?php echo escape_html($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape_html(__($page_title_key, $page_title_params, defined('PAGE_TITLE') ? PAGE_TITLE : 'Paragliding Booking')); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>/css/style.css">
    <style>
        /* Basic styling for language switcher */
        .language-switcher .nav-link { padding-right: .5rem; padding-left: .5rem; }
        .language-switcher .nav-item.active .nav-link { font-weight: bold; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo htmlspecialchars(BASE_URL); ?>/index.php"><?php echo escape_html(__(defined('SITE_NAME_KEY') ? SITE_NAME_KEY : 'site_name', [], SITE_NAME)); ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="<?php echo escape_html(__('Toggle navigation')); // Add key if not present ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/index.php"><?php echo escape_html(__('nav_home')); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/request_flight.php"><?php echo escape_html(__('nav_book_flight')); ?></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto language-switcher">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo escape_html(__('language_switcher_label')); ?> <?php echo escape_html(__( 'lang_' . $current_lang )); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageDropdown">
                        <?php foreach ($supported_langs as $lang_code): ?>
                            <a class="dropdown-item <?php echo ($current_lang === $lang_code) ? 'active' : ''; ?>"
                               href="<?php echo htmlspecialchars(BASE_URL); ?>/set_language.php?lang=<?php echo escape_html($lang_code); ?>">
                               <?php echo escape_html(__('lang_' . $lang_code)); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>
            </ul>
            <ul class="navbar-nav"> <!-- Separate from language switcher for clarity -->
                <?php if(is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/dashboard.php"><?php echo escape_html(__('nav_dashboard')); ?></a>
                    </li>
                    <?php if (is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/manage_pilots.php"><?php echo escape_html(__('nav_manage_pilots')); ?></a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/logout.php"><?php echo escape_html(__('nav_logout', ['userName' => escape_html($_SESSION['user_name'] ?? 'User')])); ?></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/login.php"><?php echo escape_html(__('nav_login_admin_pilot')); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main role="main" class="container">
