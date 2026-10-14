<?php
// Daily update edit page
// Employees can edit their own update within 24 hours of submission or same work date

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireRole(ROLE_EMPLOYEE); // Only employees can edit their updates

$pageTitle = 'Edit Daily Update';
$user = getCurrentUser();
$userId = $user['id'];

$updateId = validateInt($_GET['update_id'] ?? 0, 1) ?? 0;
if ($updateId < 1) {
    redirect('daily-updates/index.php', 'error', 'Invalid update ID.');
}

// Fetch the update
$update = Database::fetchOne(
    "SELECT du.*, u.name as employee_name FROM daily_updates du
     JOIN users u ON du.employee_id = u.id
     WHERE du.id = :updateId",
    [':updateId' => $updateId]
);

if (!$update) {
    redirect('daily-updates/index.php', 'error', 'Daily update not found.');
}

// Check authorization - employee can only edit their own updates
if ($update['employee_id'] != $userId) {
    redirect('daily-updates/index.php', 'error', 'You do not have permission to edit this update.');
}

// Check edit window - within 24 hours of creation OR same work date
$createdAt = new DateTime($update['created_at']);
$now = new DateTime();
$hoursDiff = $createdAt->diff($now)->h + ($createdAt->diff($now)->days * 24);
$isSameWorkDate = $update['work_date'] === date('Y-m-d');

if ($hoursDiff > 24 && !$isSameWorkDate) {
    redirect('daily-updates/index.php', 'error',
        'You can only edit updates within 24 hours of submission or on the same work date.');
}

// Get user's active tasks for reference
$myTasks = Database::fetchAll(
    "SELECT t.* FROM tasks t
     WHERE t.assigned_to = :userId ORDER BY t.priority DESC, t.due_date ASC",
    [':userId' => $userId]
);

// Get currently linked tasks
$currentLinkedTasks = Database::fetchAll(
    "SELECT task_id FROM daily_update_tasks WHERE daily_update_id = :updateId",
    [':updateId' => $updateId]
);
$linkedTaskIds = array_map('intval', array_column($currentLinkedTasks, 'task_id'));

$errors = [];
$old = [
    'work_date' => $update['work_date'],
    'completed_work' => $update['completed_work'] ?? '',
    'planned_work' => $update['planned_work'] ?? '',
    'pending_work' => $update['pending_work'] ?? '',
    'blockers' => $update['blockers'] ?? '',
    'notes' => $update['notes'] ?? '',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // Validate inputs
    $old['work_date'] = validateDate($_POST['work_date'] ?? $update['work_date'], true) ?? $update['work_date'];
    $old['completed_work'] = validateString($_POST['completed_work'] ?? '', 2000) ?? '';
    $old['planned_work'] = validateString($_POST['planned_work'] ?? '', 2000) ?? '';
    $old['pending_work'] = validateString($_POST['pending_work'] ?? '', 500) ?? '';
    $old['blockers'] = validateString($_POST['blockers'] ?? '', 500) ?? '';
    $old['notes'] = validateString($_POST['notes'] ?? '', 1000) ?? '';

    // Selected tasks to link
    $selectedTaskIds = [];
    if (!empty($_POST['task_ids']) && is_array($_POST['task_ids'])) {
        foreach ($_POST['task_ids'] as $tid) {
            $validTid = validateInt($tid, 1);
            if ($validTid) {
                $selectedTaskIds[] = $validTid;
            }
        }
    }

    // Validate that all selected tasks actually belong to this employee
    if (!empty($selectedTaskIds)) {
        $myTaskIds = array_map('intval', array_column($myTasks, 'id'));
        foreach ($selectedTaskIds as $tid) {
            if (!in_array($tid, $myTaskIds, true)) {
                $errors['task_ids'] = 'One or more selected tasks are invalid or not assigned to you.';
                break;
            }
        }
    }

    if (empty($old['completed_work'])) {
        $errors['completed_work'] = 'Completed work is required.';
    }

    if (empty($old['planned_work'])) {
        $errors['planned_work'] = 'Planned work is required.';
    }

    // Check for duplicate daily report (one per employee per day)
    // But allow editing the same record
    $existing = Database::fetchOne(
        "SELECT id FROM daily_updates
         WHERE employee_id = :employeeId AND work_date = :workDate AND id != :updateId",
        [':employeeId' => $userId, ':workDate' => $old['work_date'], ':updateId' => $updateId]
    );

    if ($existing) {
        $errors['duplicate'] = 'You already have a daily update for ' . formatDate($old['work_date']) . '.';
    }

    if (empty($errors)) {
        try {
            Database::beginTransaction();

            Database::update('daily_updates', [
                'work_date' => $old['work_date'],
                'completed_work' => $old['completed_work'],
                'planned_work' => $old['planned_work'],
                'pending_work' => $old['pending_work'],
                'blockers' => $old['blockers'],
                'notes' => $old['notes'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id', $updateId);

            // Sync linked tasks
            Database::query("DELETE FROM daily_update_tasks WHERE daily_update_id = :updateId", [':updateId' => $updateId]);
            foreach ($selectedTaskIds as $tid) {
                Database::insert('daily_update_tasks', [
                    'daily_update_id' => $updateId,
                    'task_id' => $tid,
                ]);
            }

            Database::commit();

            redirect('daily-updates/view.php?update_id=' . $updateId, 'success',
                'Daily update updated successfully for ' . formatDate($old['work_date']) . '.');
        } catch (Exception $e) {
            Database::rollBack();
            error_log('Daily update edit error: ' . $e->getMessage());
            $errors['general'] = 'Failed to update daily update. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2"></i>Edit Daily Update</h2>
    <a href="<?php echo url('daily-updates/view.php?update_id=' . $updateId); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<?php echo renderFlash(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Daily Work Report</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="csrf-protected" novalidate>
            <?php echo csrfField(); ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors['duplicate'])): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($errors['duplicate']); ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="work_date" class="form-label">Work Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="work_date" name="work_date"
                       value="<?php echo htmlspecialchars($old['work_date']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="completed_work" class="form-label">Completed Work <span class="text-danger">*</span></label>
                <textarea class="form-control <?php echo !empty($errors['completed_work']) ? 'is-invalid' : ''; ?>"
                          id="completed_work" name="completed_work" rows="3"
                          maxlength="2000" required><?php echo htmlspecialchars($old['completed_work']); ?></textarea>
                <?php if (!empty($errors['completed_work'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['completed_work']); ?></div>
                <?php endif; ?>
                <div class="form-text">What work did you complete today?</div>
            </div>

            <div class="mb-3">
                <label for="planned_work" class="form-label">Planned Work / To-Do <span class="text-danger">*</span></label>
                <textarea class="form-control <?php echo !empty($errors['planned_work']) ? 'is-invalid' : ''; ?>"
                          id="planned_work" name="planned_work" rows="3"
                          maxlength="2000" required><?php echo htmlspecialchars($old['planned_work']); ?></textarea>
                <?php if (!empty($errors['planned_work'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['planned_work']); ?></div>
                <?php endif; ?>
                <div class="form-text">What do you plan to do next?</div>
            </div>

            <div class="mb-3">
                <label for="pending_work" class="form-label">Pending Work</label>
                <textarea class="form-control" id="pending_work" name="pending_work" rows="2"
                          maxlength="500"><?php echo htmlspecialchars($old['pending_work']); ?></textarea>
                <div class="form-text">Work pending and reasons for delay.</div>
            </div>

            <div class="mb-3">
                <label for="blockers" class="form-label">Blockers / Support Required</label>
                <textarea class="form-control" id="blockers" name="blockers" rows="2"
                          maxlength="500"><?php echo htmlspecialchars($old['blockers']); ?></textarea>
                <div class="form-text">Any obstacles preventing progress.</div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2"
                          maxlength="1000"><?php echo htmlspecialchars($old['notes']); ?></textarea>
                <div class="form-text">Additional notes.</div>
            </div>

            <!-- Link assigned tasks worked on -->
            <?php if (!empty($myTasks)): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-tasks me-2"></i>Link Assigned Tasks Worked On</label>
                    <?php if (!empty($errors['task_ids'])): ?>
                        <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($errors['task_ids']); ?></div>
                    <?php endif; ?>
                    <div class="row g-2 border rounded p-3 bg-light">
                        <?php foreach ($myTasks as $task): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="task_ids[]"
                                           value="<?php echo (int) $task['id']; ?>"
                                           id="task_<?php echo (int) $task['id']; ?>"
                                           <?php
                                               $checked = isset($selectedTaskIds)
                                                   ? in_array($task['id'], $selectedTaskIds, true)
                                                   : in_array($task['id'], $linkedTaskIds, true);
                                               echo $checked ? 'checked' : '';
                                           ?>>
                                    <label class="form-check-label d-flex justify-content-between align-items-center" for="task_<?php echo (int) $task['id']; ?>">
                                        <span><?php echo htmlspecialchars($task['title']); ?></span>
                                        <span class="badge bg-<?php echo getPriorityBadgeClass($task['priority']); ?> ms-1">
                                            <?php echo htmlspecialchars($task['priority']); ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text">Select the tasks you worked on to link them to this update.</div>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="<?php echo url('daily-updates/view.php?update_id=' . $updateId); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>