<?php
// Task edit page
// Managers and assigned employees can edit task details

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();

$pageTitle = 'Edit Task';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];
$managerId = $user['manager_id'] ?? null;

$taskId = validateInt($_GET['task_id'] ?? 0, 1) ?? 0;
if ($taskId < 1) {
    redirect('tasks/index.php', 'error', 'Invalid task ID.');
}

$task = Database::fetchOne(
    "SELECT t.*, u.name as assigned_name, u.email as assigned_email
     FROM tasks t
     JOIN users u ON t.assigned_to = u.id
     WHERE t.id = :taskId",
    [':taskId' => $taskId]
);

if (!$task) {
    redirect('tasks/index.php', 'error', 'Task not found.');
}


// Check authorization
$canEdit = false;

if ($userRole === ROLE_ADMIN) {
    // Admins can edit any task.
    $canEdit = true;
} elseif ($userRole === ROLE_MANAGER) {
    // Managers can edit tasks they are responsible for.
    if ((int) $task['manager_id'] === (int) $userId) {
        $canEdit = true;
    }
}

if (!$canEdit) {
    redirect('tasks/index.php', 'error', 'You do not have permission to edit this task.');
}

// Get assignable employees for re-assignment
if ($userRole === ROLE_ADMIN) {
    $employees = Database::fetchAll(
        'SELECT id, name, email FROM users WHERE role = :role AND is_active = 1 ORDER BY name',
        [':role' => ROLE_EMPLOYEE]
    );
} else {
    $employees = getAssignableEmployees($userId);
}

$errors = [];
$old = [
    'title' => htmlspecialchars($task['title'] ?? ''),
    'description' => htmlspecialchars($task['description'] ?? ''),
    'assigned_to' => $task['assigned_to'] ?? 0,
    'priority' => $task['priority'] ?? PRIORITY_MEDIUM,
    'status' => $task['status'] ?? STATUS_TODO,
    'start_date' => $task['start_date'] ?? date('Y-m-d'),
    'due_date' => $task['due_date'] ?? date('Y-m-d', strtotime('+7 days')),
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // Validate inputs
    $old['title'] = validateString($_POST['title'] ?? $old['title'], 200) ?? '';
    $old['description'] = validateString($_POST['description'] ?? $old['description'], 5000) ?? '';
    $old['assigned_to'] = validateInt($_POST['assigned_to'] ?? $old['assigned_to'], 1) ?? 0;
    $old['priority'] = validatePriority($_POST['priority'] ?? $old['priority']) ?? PRIORITY_MEDIUM;
    $old['status'] = validateStatus($_POST['status'] ?? $old['status']) ?? STATUS_TODO;
    $old['start_date'] = validateDate($_POST['start_date'] ?? $old['start_date'], true) ?? '';
    $old['due_date'] = validateDate($_POST['due_date'] ?? $old['due_date'], true) ?? '';

    if (empty($old['title'])) {
        $errors['title'] = 'Title is required.';
    }

    if (empty($old['assigned_to'])) {
        $errors['assigned_to'] = 'Please select an employee to assign the task.';
    } elseif (!isActiveEmployee($old['assigned_to'])) {
        $errors['assigned_to'] = 'Selected employee is not active or does not exist.';
    }

    if (empty($old['start_date'])) {
        $errors['start_date'] = 'Start date is required.';
    }

    if (empty($old['due_date'])) {
        $errors['due_date'] = 'Due date is required.';
    }

    if (!empty($old['start_date']) && !empty($old['due_date'])) {
        if ($old['due_date'] < $old['start_date']) {
            $errors['due_date'] = 'Due date cannot be earlier than start date.';
        }
    }

    if (empty($errors)) {
        try {
            Database::beginTransaction();

            // Update task
            $updated = Database::update('tasks', [
                'title' => $old['title'],
                'description' => $old['description'],
                'assigned_to' => $old['assigned_to'],
                'priority' => $old['priority'],
                'status' => $old['status'],
                'start_date' => $old['start_date'],
                'due_date' => $old['due_date'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id', $taskId);

            if ($updated === 0) {
                throw new Exception('Failed to update task - no rows affected');
            }

            // Log the update in activity
            $oldStatus = $task['status'];
            Database::insert('task_activity', [
                'task_id' => $taskId,
                'changed_by' => $userId,
                'old_status' => $oldStatus,
                'new_status' => $old['status'],
                'comment' => 'Task edited',
            ]);

            Database::commit();

            redirect('tasks/view.php?task_id=' . $taskId, 'success', 'Task updated successfully.');
        } catch (Exception $e) {
            Database::rollBack();
            error_log('Task edit error: ' . $e->getMessage());
            $errors['general'] = 'Failed to update task. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2"></i>Edit Task</h2>
    <a href="<?php echo url('tasks/view.php?task_id=' . $taskId); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Task
    </a>
</div>

<?php echo renderFlash(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Task Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="csrf-protected" novalidate>
            <?php echo csrfField(); ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($errors['title']) ? 'is-invalid' : ''; ?>"
                       id="title" name="title" value="<?php echo htmlspecialchars($old['title']); ?>"
                       required maxlength="200" autofocus>
                <?php if (!empty($errors['title'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['title']); ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"
                          maxlength="5000"><?php echo htmlspecialchars($old['description']); ?></textarea>
                <div class="form-text">Optional. Describe the task requirements and expectations.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo !empty($errors['assigned_to']) ? 'is-invalid' : ''; ?>"
                            id="assigned_to" name="assigned_to" required>
                        <option value="">Select an employee...</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"
                                <?php echo $old['assigned_to'] == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['name'] . ' (' . $emp['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['assigned_to'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['assigned_to']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <?php foreach ([PRIORITY_LOW, PRIORITY_MEDIUM, PRIORITY_HIGH, PRIORITY_URGENT] as $priority): ?>
                            <option value="<?php echo htmlspecialchars($priority); ?>"
                                <?php echo $old['priority'] === $priority ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($priority); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach ([STATUS_TODO, STATUS_IN_PROGRESS, STATUS_BLOCKED, STATUS_COMPLETED] as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>"
                                <?php echo $old['status'] === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control <?php echo !empty($errors['start_date']) ? 'is-invalid' : ''; ?>"
                           id="start_date" name="start_date" value="<?php echo htmlspecialchars($old['start_date']); ?>" required>
                    <?php if (!empty($errors['start_date'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['start_date']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control <?php echo !empty($errors['due_date']) ? 'is-invalid' : ''; ?>"
                           id="due_date" name="due_date" value="<?php echo htmlspecialchars($old['due_date']); ?>"
                           required min="<?php echo htmlspecialchars($old['start_date'] ?? date('Y-m-d')); ?>">
                    <?php if (!empty($errors['due_date'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['due_date']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Task
                </button>
                <a href="<?php echo url('tasks/view.php?task_id=' . $taskId); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>