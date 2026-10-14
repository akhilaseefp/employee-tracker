<?php
// Logout page - destroy session and redirect to login

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// Process logout
logoutUser();

// Redirect to login page
header('Location: ' . APP_URL . '/login.php');
exit;