<?php
// Home page - redirect based on login status
// If logged in, go to dashboard; otherwise show login form

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

// Not logged in - redirect to login page
header('Location: ' . APP_URL . '/login.php');
exit;