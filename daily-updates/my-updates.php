<?php
// Daily updates my updates page - redirects to index with employee filter
// But also provides a dedicated page showing employee's own updates

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireRole(ROLE_EMPLOYEE); // Only employees

$pageTitle = 'My Updates';
$user = getCurrentUser();
$userId = $user['id'];
$userName = $user['name'];

// Redirect employee to the daily updates list which shows their updates
redirect('daily-updates/index.php');