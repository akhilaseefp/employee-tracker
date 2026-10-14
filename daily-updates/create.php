<?php
// Daily update create page
// Employees can submit daily reports

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireRole(ROLE_EMPLOYEE); // Only employees can submit updates

$pageTitle = 'Submit Daily Update';
$user = getCurrentUser();
$userId = $user['id'];
$userName = $user['name'];

// Fetch user's active tasks for reference
$myTasks = Database::fetchAll(
    "SELECT t.*, u.name as assigned_name FROM tasks t
     JOIN users u ON t.assigned_to = u.id
     WHERE t.assigned_to = :userId ORDER BY t.priority DESC, t.due_date ASC",
    [':userId' => $userId]
);

$errors = [];
$old = [
    'work_date' => date('Y-m-d'),
    'completed_work' => '',
    'planned_work' => '',
    'pending_work' => '',
    'blockers' => '',
    'notes' => '',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // Validate inputs
    $old['work_date'] = validateDate($_POST['work_date'] ?? date('Y-m-d'), true) ?? date('Y-m-d');
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
    $existing = Database::fetchOne(
        "SELECT id FROM daily_updates
         WHERE employee_id = :employeeId AND work_date = :workDate",
        [':employeeId' => $userId, ':workDate' => $old['work_date']]
    );

    if ($existing) {
        $errors['duplicate'] = 'You have already submitted a daily update for ' . formatDate($old['work_date']) . '.
            <a href="' . url('daily-updates/view.php?update_id=' . $existing['id']) . '" class="alert-link">View your existing update</a>
            or <a href="javascript:history.back()">return to edit it</a>.';
    }

    if (empty($errors)) {
        try {
            Database::beginTransaction();

            $updateId = Database::insert('daily_updates', [
                'employee_id' => $userId,
                'work_date' => $old['work_date'],
                'completed_work' => $old['completed_work'],
                'planned_work' => $old['planned_work'],
                'pending_work' => $old['pending_work'],
                'blockers' => $old['blockers'],
                'notes' => $old['notes'],
            ]);

            // Link selected tasks
            foreach ($selectedTaskIds as $tid) {
                Database::insert('daily_update_tasks', [
                    'daily_update_id' => $updateId,
                    'task_id' => $tid,
                ]);
            }

            Database::commit();

            redirect('daily-updates/view.php?update_id=' . $updateId, 'success',
                'Daily update submitted successfully for ' . formatDate($old['work_date']) . '.');
        } catch (Exception $e) {
            Database::rollBack();
            error_log('Daily update creation error: ' . $e->getMessage());
            $errors['general'] = 'Failed to submit daily update. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar me-2"></i>Submit Daily Update</h2>
    <a href="<?php echo url('daily-updates/index.php'); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Updates
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
                <textarea class="form-control" id="completed_work" name="completed_work" rows="3"
                          maxlength="2000" required><?php echo htmlspecialchars($old['completed_work']); ?></textarea>
                <div class="form-text">What work did you complete today?</div>
            </div>

            <div class="mb-3">
                <label for="planned_work" class="form-label">Planned Work / To-Do <span class="text-danger">*</span></label>
                <textarea class="form-control" id="planned_work" name="planned_work" rows="3"
                          maxlength="2000" required><?php echo htmlspecialchars($old['planned_work']); ?></textarea>
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
                                           <?php echo in_array($task['id'], $selectedTaskIds ?? [], true) ? 'checked' : ''; ?>>
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
                    <div class="form-text">Select the tasks you worked on today to link them to this update.</div>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-save me-2"></i>Submit Update
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>