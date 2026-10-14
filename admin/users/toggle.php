<?php
// Admin user toggle (activate/deactivate)

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

requireLogin();
requireAdmin();

$user = getCurrentUser();
$userId = $user['id'];

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/users.php', 'error', 'Invalid request method.');
}

requireCsrf();

$toggleId = validateInt($_POST['user_id'] ?? 0, 1) ?? 0;
if ($toggleId < 1) {
    redirect('admin/users.php', 'error', 'Invalid user ID.');
}

// Check user being toggled
$user = Database::fetchOne(
    "SELECT u.* FROM users u
     WHERE u.id = :id",
    [':id' => $toggleId]
);

if (!$user) {
    redirect('admin/users.php', 'error', 'User not found.');
}

// Prevent self-deactivation
if ($toggleId === $userId) {
    redirect('admin/users.php', 'error', 'You cannot deactivate your own account.');
}

// Toggle the is_active status
try {
    Database::beginTransaction();

    $newStatus = !$user['is_active'];
    $action = $newStatus ? 'activate' : 'deactivate';

    Database::update('users', [
        'is_active' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id', $toggleId);

    Database::commit();

    $actionWord = $newStatus ? 'activated' : 'deactivated';
    setFlash("User {$user['name']} has been {$actionWord} successfully.", 'success');

} catch (Exception $e) {
    Database::rollBack();
    error_log('User toggle error: ' . $e->getMessage());
    setFlash("Failed to {$action} user. Please try again.", 'error');
}

header('Location: ' . url('admin/users.php'));
exit;