<?php
// Home page - redirect based on login status
// If logged in, go to dashboard; otherwise show login form

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Not logged in - redirect to login page
redirect('login.php');