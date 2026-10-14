<?php
// Manager feedback handler
// Managers and admins can add/update feedback on daily updates

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireManager(); // Only managers and admins can add feedback

$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];
$managerId = $user['manager_id'] ?? null;

// Only POST requests for state changes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('daily-updates/index.php', 'error', 'Invalid request method.');
}

requireCsrf();

$updateId = validateInt($_POST['update_id'] ?? 0, 1) ?? 0;
$feedbackText = validateString($_POST['feedback'] ?? '', 2000) ?? '';

if ($updateId < 1) {
    redirect('daily-updates/index.php', 'error', 'Invalid update ID.');
}

if (empty($feedbackText)) {
    redirect('daily-updates/view.php?update_id=' . $updateId, 'error', 'Feedback cannot be empty.');
}

// Fetch the daily update
$update = Database::fetchOne(
    "SELECT du.*, u.manager_id FROM daily_updates du JOIN users u ON du.employee_id = u.id WHERE du.id = :updateId",
    [':updateId' => $updateId]
);

if (!$update) {
    redirect('daily-updates/index.php', 'error', 'Daily update not found.');
}

// Check authorization - manager must be the employee's manager or admin
$canAddFeedback = false;
if ($userRole === ROLE_ADMIN) {
    $canAddFeedback = true;
} elseif ($userRole === ROLE_MANAGER && $update['manager_id'] == $userId) {
    // The employee's manager_id must match the logged-in manager's own ID
    $canAddFeedback = true;
}

if (!$canAddFeedback) {
    redirect('daily-updates/index.php', 'error', 'You are not authorized to add feedback to this update.');
}

// Check if feedback already exists
$existingFeedback = Database::fetchOne(
    "SELECT id FROM manager_feedback WHERE daily_update_id = :updateId",
    [':updateId' => $updateId]
);

try {
    Database::beginTransaction();

    if ($existingFeedback) {
        // Update existing feedback
        Database::update('manager_feedback', [
            'feedback' => $feedbackText,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id', $existingFeedback['id']);
    } else {
        // Create new feedback
        Database::insert('manager_feedback', [
            'daily_update_id' => $updateId,
            'manager_id' => $userId,
            'feedback' => $feedbackText,
        ]);
    }

    Database::commit();

    redirect('daily-updates/view.php?update_id=' . $updateId, 'success', 'Feedback saved successfully.');
} catch (Exception $e) {
    Database::rollBack();
    error_log('Feedback save error: ' . $e->getMessage());
    redirect('daily-updates/view.php?update_id=' . $updateId, 'error', 'Failed to save feedback.');
}