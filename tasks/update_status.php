<?php
// Task status update handler
// Allows authorized users to change task status

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();

$user = getCurrentUser();
$userId = (int) $user['id'];
$userRole = $user['role'];

// Only POST requests allowed for status changes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tasks/index.php', 'error', 'Invalid request method.');
}

requireCsrf();

$taskId = validateInt($_POST['task_id'] ?? 0, 1) ?? 0;
$newStatus = validateStatus($_POST['new_status'] ?? '') ?? '';

if ($taskId < 1) {
    redirect('tasks/index.php', 'error', 'Invalid task ID.');
}

if (empty($newStatus)) {
    redirect('tasks/view.php?task_id=' . $taskId, 'error', 'Invalid status value.');
}

// Fetch the task
$task = Database::fetchOne(
    "SELECT t.*, u.name as assigned_name, u.manager_id as employee_manager_id
     FROM tasks t
     JOIN users u ON t.assigned_to = u.id
     WHERE t.id = :taskId",
    [':taskId' => $taskId]
);

if (!$task) {
    redirect('tasks/index.php', 'error', 'Task not found.');
}

$oldStatus = $task['status'];

// Check if status is actually changing
if ($oldStatus === $newStatus) {
    redirect('tasks/view.php?task_id=' . $taskId, 'info', 'Task is already in "' . htmlspecialchars($newStatus) . '" status.');
}

// Allowed state transition rules
$allowedTransitions = [
    STATUS_TODO => [STATUS_IN_PROGRESS, STATUS_BLOCKED, STATUS_COMPLETED],
    STATUS_IN_PROGRESS => [STATUS_TODO, STATUS_BLOCKED, STATUS_COMPLETED],
    STATUS_BLOCKED => [STATUS_TODO, STATUS_IN_PROGRESS, STATUS_COMPLETED],
    STATUS_COMPLETED => [STATUS_TODO, STATUS_IN_PROGRESS], // Reopening
];

if (!isset($allowedTransitions[$oldStatus]) || !in_array($newStatus, $allowedTransitions[$oldStatus], true)) {
    redirect('tasks/view.php?task_id=' . $taskId, 'error', "Status transition from '{$oldStatus}' to '{$newStatus}' is not allowed.");
}

// Check authorization
$canUpdate = false;
if ($userRole === ROLE_ADMIN) {
    $canUpdate = true;
} elseif ($userRole === ROLE_MANAGER) {
    // Manager can update tasks assigned to their team members or managed by them
    if ((int) $task['manager_id'] === $userId || (int) $task['employee_manager_id'] === $userId) {
        $canUpdate = true;
    }
} elseif ($userRole === ROLE_EMPLOYEE) {
    // Employees can only update status of their own tasks
    // Allowed statuses for employees: To Do, In Progress, Blocked
    // Employees cannot transition to Completed (requires manager review) or reopen Completed tasks
    if ((int) $task['assigned_to'] === $userId && $oldStatus !== STATUS_COMPLETED && $newStatus !== STATUS_COMPLETED) {
        $canUpdate = true;
    }
}

if (!$canUpdate) {
    redirect('tasks/view.php?task_id=' . $taskId, 'error', 'You do not have permission to perform this status update.');
}

// Update the task with new status
try {
    Database::beginTransaction();

    $updateData = [
        'status' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    // Maintain completion timestamp
    if ($newStatus === STATUS_COMPLETED) {
        $updateData['completed_at'] = date('Y-m-d H:i:s');
    } elseif ($oldStatus === STATUS_COMPLETED && $newStatus !== STATUS_COMPLETED) {
        // Task was reopened - clear completed_at
        $updateData['completed_at'] = null;
    }

    Database::update('tasks', $updateData, 'id', $taskId);

    // Sanitize comment if provided
    $rawComment = trim($_POST['comment'] ?? '');
    $sanitizedComment = !empty($rawComment) ? sanitizeInput($rawComment) : null;

    // Log status change in activity log
    Database::insert('task_activity', [
        'task_id' => $taskId,
        'changed_by' => $userId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'comment' => $sanitizedComment,
    ]);

    Database::commit();

    redirect('tasks/view.php?task_id=' . $taskId, 'success',
        'Task status updated to "' . htmlspecialchars($newStatus) . '".');
} catch (Exception $e) {
    Database::rollBack();
    error_log('Task status update error: ' . $e->getMessage());
    redirect('tasks/view.php?task_id=' . $taskId, 'error',
        'Failed to update task status. Please try again.');
}