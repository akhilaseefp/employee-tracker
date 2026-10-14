<?php
// Daily update view page
// Shows update details and manager feedback

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pageTitle = 'Daily Update Details';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];
$managerId = $user['manager_id'] ?? null;

$updateId = validateInt($_GET['update_id'] ?? 0, 1) ?? 0;
if ($updateId < 1) {
    redirect('daily-updates/index.php', 'error', 'Invalid update ID.');
}


$update = Database::fetchOne(
    "SELECT
        du.*,
        u.name AS employee_name,
        u.email AS employee_email,
        u.manager_id AS employee_manager_id,
        m.name AS manager_name
     FROM daily_updates du
     JOIN users u ON du.employee_id = u.id
     LEFT JOIN users m ON u.manager_id = m.id
     WHERE du.id = :updateId",
    [':updateId' => $updateId]
);

if (!$update) {
    redirect('daily-updates/index.php', 'error', 'Daily update not found.');
}

// Check authorization
$canView = false;
$canEdit = false;
$canAddFeedback = false;

if ($userRole === ROLE_ADMIN) {
    $canView = true;
    $canEdit = true;
    $canAddFeedback = true;
} elseif ($userRole === ROLE_MANAGER) {
    // Managers can access updates submitted by their own team.
    if ((int) $update['employee_manager_id'] === (int) $userId) {
        $canView = true;
        $canEdit = false;
        $canAddFeedback = true;
    }
} elseif ($userRole === ROLE_EMPLOYEE) {
    if ($update['employee_id'] == $userId) {
        $canView = true;
        $canEdit = true;
    }
}

if (!$canView) {
    redirect('daily-updates/index.php', 'error', 'You do not have permission to view this update.');
}

// Get manager feedback
$feedback = Database::fetchOne(
    "SELECT mf.*, u.name as manager_name
     FROM manager_feedback mf
     JOIN users u ON mf.manager_id = u.id
     WHERE mf.daily_update_id = :updateId",
    [':updateId' => $updateId]
);

// Get tasks linked to this daily update
$linkedTasks = Database::fetchAll(
    "SELECT t.*, u.name as assigned_name FROM tasks t
     JOIN daily_update_tasks dut ON t.id = dut.task_id
     JOIN users u ON t.assigned_to = u.id
     WHERE dut.daily_update_id = :updateId
     ORDER BY t.priority DESC, t.due_date ASC",
    [':updateId' => $updateId]
);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-calendar me-2"></i>
        Daily Update: <?php echo formatDate($update['work_date']); ?>
    </h2>
    <div>
        <?php if ($canEdit): ?>
            <a href="<?php echo url('daily-updates/edit.php?update_id=' . $updateId); ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
        <?php endif; ?>
        <a href="<?php echo url('daily-updates/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php echo renderFlash(); ?>

<div class="row">
    <div class="col-md-8">
        <!-- Employee info -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-user me-2"></i>Employee</h6>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong><?php echo htmlspecialchars($update['employee_name']); ?></strong></p>
                <p class="mb-0 small text-muted">
                    <a href="mailto:<?php echo htmlspecialchars($update['employee_email']); ?>">
                        <?php echo htmlspecialchars($update['employee_email']); ?>
                    </a>
                    <?php if (!empty($update['manager_name'])): ?>
                        | Manager: <?php echo htmlspecialchars($update['manager_name']); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Completed work -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-check-circle me-2 text-success"></i>Completed Work</h6>
            </div>
            <div class="card-body">
                <p class="card-text"><?php echo nl2br(htmlspecialchars($update['completed_work'] ?? '')); ?></p>
            </div>
        </div>

        <!-- Planned work -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-calendar-plus me-2 text-primary"></i>Planned Work / To-Do</h6>
            </div>
            <div class="card-body">
                <p class="card-text"><?php echo nl2br(htmlspecialchars($update['planned_work'] ?? '')); ?></p>
            </div>
        </div>

        <!-- Pending work -->
        <?php if (!empty($update['pending_work'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-hourglass-half me-2 text-warning"></i>Pending Work</h6>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($update['pending_work'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Blockers -->
        <?php if (!empty($update['blockers'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-ban me-2 text-danger"></i>Blockers / Support Required</h6>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($update['blockers'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if (!empty($update['notes'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-sticky-note me-2 text-info"></i>Notes</h6>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($update['notes'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Linked Tasks -->
        <?php if (!empty($linkedTasks)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-link me-2 text-primary"></i>Linked Tasks</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($linkedTasks as $task): ?>
                            <a href="<?php echo url('tasks/view.php?task_id=' . $task['id']); ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">Assigned to: <?php echo htmlspecialchars($task['assigned_name']); ?></small>
                                    </div>
                                    <div class="ms-3">
                                        <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo getPriorityBadgeClass($task['priority']); ?> ms-1">
                                            <?php echo htmlspecialchars($task['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <!-- Feedback section (manager/admin only) -->
        <?php if ($canAddFeedback): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Manager Feedback</h6>
                </div>
                <div class="card-body">
                    <?php if ($feedback): ?>
                        <div class="alert alert-info mb-3">
                            <p class="mb-1"><strong><?php echo htmlspecialchars($feedback['manager_name']); ?></strong></p>
                            <p class="mb-0 small">
                                <?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?>
                            </p>
                            <small class="text-muted">
                                Added: <?php echo formatDateTime($feedback['created_at']); ?>
                                <?php if (!empty($feedback['updated_at']) && $feedback['updated_at'] !== $feedback['created_at']): ?>
                                    (Updated: <?php echo formatDateTime($feedback['updated_at']); ?>)
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo url('manager/feedback.php'); ?>" class="csrf-protected">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="update_id" value="<?php echo $updateId; ?>">

                        <div class="mb-3">
                            <label for="feedback" class="form-label">Feedback</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="3" required
                                      placeholder="Enter your feedback for the employee..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i><?php echo $feedback ? 'Update Feedback' : 'Add Feedback'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($feedback): ?>
            <!-- Show feedback to employee if available -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Manager Feedback</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <p class="mb-1"><strong><?php echo htmlspecialchars($feedback['manager_name']); ?></strong></p>
                        <p class="mb-0 small">
                            <?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?>
                        </p>
                        <small class="text-muted">
                            Added: <?php echo formatDateTime($feedback['created_at']); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Timestamps -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Timestamps</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <p class="mb-1"><strong>Submitted:</strong> <?php echo formatDateTime($update['created_at']); ?></p>
                    <p class="mb-0"><strong>Last Updated:</strong> <?php echo formatDateTime($update['updated_at']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>