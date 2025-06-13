<?php
// This check should be done by config.php, but as a fallback:
if (session_status() == PHP_SESSION_NONE && php_sapi_name() !== 'cli') { session_start(); }
if (!defined('BASE_URL')) { require_once dirname(__DIR__, 2) . '/config/config.php'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(defined('PAGE_TITLE') ? PAGE_TITLE : (defined('SITE_NAME') ? SITE_NAME : 'Paragliding Booking')); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo htmlspecialchars(BASE_URL); ?>/index.php"><?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'Paragliding'); ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/request_flight.php">Book a Flight</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/dashboard.php">Dashboard</a>
                    </li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/manage_pilots.php">Manage Pilots</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>)</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL); ?>/admin/login.php">Admin/Pilot Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main role="main" class="container">
