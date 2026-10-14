<?php
// Dashboard - role-aware: shows different views for employees, managers, and admins

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

requireLogin();

$pageTitle = 'Dashboard';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];

// ---- Common: get user's own tasks ----
$myTasks = Database::fetchAll(
    "SELECT t.*, u.name as assigned_name FROM tasks t
     JOIN users u ON t.assigned_to = u.id
     WHERE t.assigned_to = :userId
     ORDER BY CASE t.status
         WHEN 'To Do' THEN 1
         WHEN 'In Progress' THEN 2
         WHEN 'Blocked' THEN 3
         WHEN 'Completed' THEN 4
     END, t.priority DESC, t.due_date ASC",
    [':userId' => $userId]
);

// Get user's recent updates
$recentUpdates = Database::fetchAll(
    "SELECT du.*, u.name as employee_name FROM daily_updates du
     JOIN users u ON du.employee_id = u.id
     WHERE du.employee_id = :userId
     ORDER BY du.work_date DESC
     LIMIT 5",
    [':userId' => $userId]
);

// Get overdue tasks count
$overdueTasks = Database::fetchOne(
    "SELECT COUNT(*) as count FROM tasks
     WHERE assigned_to = :userId
     AND status != 'Completed'
     AND due_date < CURDATE()",
    [':userId' => $userId]
);
$overdueCount = $overdueTasks ? (int)$overdueTasks['count'] : 0;

// Get tasks due today
$dueTodayTasks = Database::fetchOne(
    "SELECT COUNT(*) as count FROM tasks
     WHERE assigned_to = :userId
     AND due_date = CURDATE()",
    [':userId' => $userId]
);
$dueTodayCount = $dueTodayTasks ? (int)$dueTodayTasks['count'] : 0;

// Get task status counts
$taskCounts = Database::fetchAll(
    "SELECT status, COUNT(*) as count FROM tasks
     WHERE assigned_to = :userId
     GROUP BY status",
    [':userId' => $userId]
);
$statusCounts = [];
foreach ($taskCounts as $tc) {
    $statusCounts[$tc['status']] = (int)$tc['count'];
}

// Default all statuses to 0 if not present
$statusCounts[STATUS_TODO] = $statusCounts[STATUS_TODO] ?? 0;
$statusCounts[STATUS_IN_PROGRESS] = $statusCounts[STATUS_IN_PROGRESS] ?? 0;
$statusCounts[STATUS_BLOCKED] = $statusCounts[STATUS_BLOCKED] ?? 0;
$statusCounts[STATUS_COMPLETED] = $statusCounts[STATUS_COMPLETED] ?? 0;

// Calculate completion rate
$totalTasks = array_sum($statusCounts);
$completedTasks = $statusCounts[STATUS_COMPLETED] ?? 0;
$completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

// ---- Manager/Admin extra data: team overview ----
$teamStats = null;
$teamMembers = [];
if ($userRole === ROLE_MANAGER) {
    $teamMembers = getTeamMembers($userId);
} elseif ($userRole === ROLE_ADMIN) {
    $teamMembers = Database::fetchAll(
        'SELECT id, name, email FROM users WHERE role = :role AND is_active = 1 ORDER BY name',
        [':role' => ROLE_EMPLOYEE]
    );
}

if (!empty($teamMembers)) {
    $teamIds = array_column($teamMembers, 'id');
    $placeholders = [];
    $teamParams = [];
    foreach ($teamIds as $i => $id) {
        $placeholders[] = ':t' . $i;
        $teamParams[':t' . $i] = $id;
    }
    $inClause = implode(',', $placeholders);

    $teamStats = Database::fetchOne(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status != 'Completed' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN due_date = CURDATE() THEN 1 ELSE 0 END) as due_today,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Blocked' THEN 1 ELSE 0 END) as blocked
         FROM tasks WHERE assigned_to IN ($inClause)",
        $teamParams
    );
}

include __DIR__ . '/includes/header.php';
?>

<!-- Welcome message -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tachometer-alt me-2"></i>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
    <div class="btn-group">
        <a href="<?php echo url('tasks/index.php'); ?>" class="btn btn-outline-primary">
            <i class="fas fa-tasks me-1"></i>View All Tasks
        </a>
        <a href="<?php echo url('daily-updates/create.php'); ?>" class="btn btn-outline-primary">
            <i class="fas fa-plus me-1"></i>Submit Update
        </a>
    </div>
</div>

<?php echo renderFlash(); ?>

<?php if ($userRole === ROLE_MANAGER || $userRole === ROLE_ADMIN): ?>
<!-- Manager/Admin Team Overview Cards -->
<h5 class="mb-3"><i class="fas fa-users me-2"></i>Team Overview</h5>
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-info h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Team Members</h6>
                <h3 class="mb-0"><?php echo count($teamMembers); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-primary h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Team Tasks</h6>
                <h3 class="mb-0"><?php echo (int)($teamStats['total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Completed</h6>
                <h3 class="mb-0"><?php echo (int)($teamStats['completed'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-danger h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Overdue</h6>
                <h3 class="mb-0 text-danger"><?php echo (int)($teamStats['overdue'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-warning h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Due Today</h6>
                <h3 class="mb-0"><?php echo (int)($teamStats['due_today'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-secondary h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Blocked</h6>
                <h3 class="mb-0"><?php echo (int)($teamStats['blocked'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
</div>
<div class="mb-4">
    <a href="<?php echo url('manager/team.php'); ?>" class="btn btn-outline-primary me-2">
        <i class="fas fa-user-tie me-1"></i>Team Details
    </a>
    <a href="<?php echo url('manager/reports.php'); ?>" class="btn btn-outline-primary">
        <i class="fas fa-chart-bar me-1"></i>Reports
    </a>
</div>
<?php endif; ?>

<!-- Personal Dashboard cards -->
<h5 class="mb-3"><i class="fas fa-user me-2"></i>My Tasks</h5>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-muted">My Tasks</h6>
                        <h3 class="mb-0"><?php echo $totalTasks; ?></h3>
                    </div>
                    <div class="fs-4 text-primary"><i class="fas fa-tasks"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-muted">Completed</h6>
                        <h3 class="mb-0"><?php echo $statusCounts[STATUS_COMPLETED]; ?></h3>
                    </div>
                    <div class="fs-4 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-muted">Overdue</h6>
                        <h3 class="mb-0 text-danger"><?php echo $overdueCount; ?></h3>
                    </div>
                    <div class="fs-4 text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-muted">Due Today</h6>
                        <h3 class="mb-0"><?php echo $dueTodayCount; ?></h3>
                    </div>
                    <div class="fs-4 text-warning"><i class="fas fa-calendar-day"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Updates -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Recent Updates</h5>
                <a href="<?php echo url('daily-updates/index.php'); ?>" class="btn btn-sm btn-outline-secondary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentUpdates)): ?>
                    <div class="empty-state p-4">
                        <i class="far fa-calendar-alt"></i>
                        <h5>No recent updates</h5>
                        <p class="text-muted">You haven't submitted any daily updates yet.</p>
                        <a href="<?php echo url('daily-updates/create.php'); ?>" class="btn btn-primary mt-2">Create First Update</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentUpdates as $update): ?>
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo formatDateWithDay($update['work_date']); ?>
                                            <small class="text-muted ms-2">
                                                Submitted: <?php echo formatDateTime($update['created_at']); ?>
                                            </small>
                                        </h6>
                                        <?php if (!empty($update['completed_work'])): ?>
                                            <div class="small">
                                                <strong>Completed:</strong>
                                                <?php echo htmlspecialchars(substr($update['completed_work'], 0, 80)); ?>
                                                <?php if (strlen($update['completed_work']) > 80) echo '...'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-3">
                                        <a href="<?php echo url('daily-updates/view.php?update_id=' . $update['id']); ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- My Tasks table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>My Assigned Tasks</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($myTasks)): ?>
                    <div class="empty-state p-4">
                        <i class="fas fa-tasks"></i>
                        <h5>No tasks assigned</h5>
                        <p class="text-muted">You don't have any tasks assigned yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Title</th>
                                    <th style="width: 15%">Priority</th>
                                    <th style="width: 15%">Status</th>
                                    <th style="width: 15%">Due Date</th>
                                    <th style="width: 15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myTasks as $task): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                            <?php if (!empty($task['description'])): ?>
                                                <div class="text-muted small">
                                                    <?php echo htmlspecialchars(substr($task['description'], 0, 60)); ?>
                                                    <?php if (strlen($task['description']) > 60) echo '...'; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getPriorityBadgeClass($task['priority']); ?>">
                                                <?php echo htmlspecialchars($task['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </span>
                                            <?php if (isTaskOverdue($task['status'], $task['due_date'])): ?>
                                                <span class="badge bg-danger ms-1">Overdue</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($task['due_date']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo url('tasks/view.php?task_id=' . $task['id']); ?>"
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>