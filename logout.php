<?php
// Logout page - destroy session and redirect to login

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

// Process logout and redirect with confirmation message
logoutUser();