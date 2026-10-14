<?php
// Task view page
// Shows task details and activity log

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();

$pageTitle = 'Task Details';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];
$managerId = $user['manager_id'] ?? null;

$taskId = validateInt($_GET['task_id'] ?? 0, 1) ?? 0;
if ($taskId < 1) {
    redirect('tasks/index.php', 'error', 'Invalid task ID.');
}

$task = Database::fetchOne(
    "SELECT t.*, u.name as assigned_name, u.email as assigned_email, u.manager_id as employee_manager_id, c.name as creator_name
     FROM tasks t
     JOIN users u ON t.assigned_to = u.id
     LEFT JOIN users c ON t.created_by = c.id
     WHERE t.id = :taskId",
    [':taskId' => $taskId]
);

if (!$task) {
    redirect('tasks/index.php', 'error', 'Task not found.');
}

// Check authorization
$canView = false;
$canEdit = false;
$canUpdateStatus = false;

if ($userRole === ROLE_ADMIN) {
    $canView = true;
    $canEdit = true;
    $canUpdateStatus = true;
} elseif ($userRole === ROLE_MANAGER) {
    if ((int) $task['manager_id'] === (int) $userId || (int) $task['employee_manager_id'] === (int) $userId) {
        $canView = true;
        $canEdit = true;
        $canUpdateStatus = true;
    }
} elseif ($userRole === ROLE_EMPLOYEE) {
    if ((int) $task['assigned_to'] === (int) $userId) {
        $canView = true;
        $canEdit = false; // Employees cannot edit task details
        $canUpdateStatus = ($task['status'] !== STATUS_COMPLETED);
    }
}

if (!$canView) {
    redirect('tasks/index.php', 'error', 'You do not have permission to view this task.');
}

// Get task activity log
$activity = Database::fetchAll(
    "SELECT ta.*, u.name as changed_by_name
     FROM task_activity ta
     JOIN users u ON ta.changed_by = u.id
     WHERE ta.task_id = :taskId
     ORDER BY ta.created_at DESC",
    [':taskId' => $taskId]
);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tasks me-2"></i>Task Details</h2>
    <div>
        <?php if ($canEdit): ?>
            <a href="<?php echo url('tasks/edit.php?task_id=' . $taskId); ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-edit me-1"></i>Edit Task
            </a>
        <?php endif; ?>
        <a href="<?php echo url('tasks/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Tasks
        </a>
    </div>
</div>

<?php echo renderFlash(); ?>

<div class="row">
    <div class="col-12">


                <!-- Task details -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h1 class="mb-3">
                            <span class="bg-primary text-white px-3 py-1 rounded">
                                <?php echo htmlspecialchars($task['priority']); ?>
                            </span>
                            <?php echo htmlspecialchars($task['title']); ?>
                        </h1>
                        <p class="text-muted small">
                            <i class="fas fa-user-tie me-2"></i>
                            Created by: <?php echo htmlspecialchars($task['creator_name']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>
                        <?php if (isTaskOverdue($task['status'], $task['due_date'])): ?>
                            <span class="badge bg-danger ms-1 small">Overdue</span>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">
                            Due: <?php echo formatDate($task['due_date']); ?> |
                            Assigned: <?php echo formatDate($task['start_date']); ?>
                        </small>
                    </div>
                </div>

                <!-- Task description -->
                <?php if (!empty($task['description'])): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-align-left me-2"></i>Description</h6>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Task assignment -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Assignment</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Assigned to:</strong> <?php echo htmlspecialchars($task['assigned_name']); ?></p>
                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($task['assigned_email']); ?>"><?php echo htmlspecialchars($task['assigned_email']); ?></a></p>
                        <p><strong>Manager:</strong> View task activity for manager information.</p>
                    </div>
                </div>

                <!-- Status update form (for assigned employee or manager) -->
                <?php if ($canUpdateStatus): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-arrow-down me-2"></i>Update Task Status</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?php echo url('tasks/update_status.php'); ?>" class="csrf-protected">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">

                                <div class="mb-3">
                                    <label for="new_status" class="form-label">New Status</label>
                                    <select class="form-select" id="new_status" name="new_status" required>
                                        <option value="">Select status...</option>
                                        <option value="<?php echo STATUS_TODO; ?>"
                                            <?php if ($task['status'] === STATUS_TODO) echo 'selected'; ?>>
                                            To Do</option>
                                        <option value="<?php echo STATUS_IN_PROGRESS; ?>"
                                            <?php if ($task['status'] === STATUS_IN_PROGRESS) echo 'selected'; ?>>
                                            In Progress</option>
                                        <option value="<?php echo STATUS_BLOCKED; ?>"
                                            <?php if ($task['status'] === STATUS_BLOCKED) echo 'selected'; ?>>
                                            Blocked</option>
                                        <?php if ($userRole !== ROLE_EMPLOYEE): ?>
                                            <option value="<?php echo STATUS_COMPLETED; ?>"
                                                <?php if ($task['status'] === STATUS_COMPLETED) echo 'selected'; ?>>
                                                Completed</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label">Comment (optional)</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3"
                                              placeholder="Add a comment about this status change"></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Status
                                    </button>
                                    <a href="<?php echo url('tasks/view.php?task_id=' . $taskId); ?>" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Activity log -->
                <?php if (!empty($activity)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Activity Log</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 small">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%">Date</th>
                                            <th style="width: 25%">Changed By</th>
                                            <th style="width: 30%">Status Change</th>
                                            <th style="width: 30%">Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activity as $act): ?>
                                            <tr>
                                                <td><?php echo formatDateTime($act['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($act['changed_by_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusBadgeClass($act['new_status']); ?>">
                                        <?php echo htmlspecialchars($act['old_status'] ?? '—') . ' → ' . htmlspecialchars($act['new_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($act['comment'])): ?>
                                                        <?php echo htmlspecialchars($act['comment']); ?>
                                                    <?php else: ?>
                                                        <small class="text-muted">No comment</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="fas fa-history"></i>
                        <p>No activity recorded yet.</p>
                    </div>
                <?php endif; ?>


    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
