<?php
// Manager team overview page
// Shows the manager's team members, their task statuses, and recent activity

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireManager();

$pageTitle = 'Team Overview';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];

// Get team members — admin sees all employees, managers see direct reports
if ($userRole === ROLE_ADMIN) {
    $teamMembers = Database::fetchAll(
        "SELECT u.*, m.name as manager_name
         FROM users u
         LEFT JOIN users m ON u.manager_id = m.id
         WHERE u.role = :role AND u.is_active = 1
         ORDER BY u.name",
        [':role' => ROLE_EMPLOYEE]
    );
} else {
    $teamMembers = Database::fetchAll(
        "SELECT u.*, m.name as manager_name
         FROM users u
         LEFT JOIN users m ON u.manager_id = m.id
         WHERE u.manager_id = :managerId AND u.is_active = 1
         ORDER BY u.name",
        [':managerId' => $userId]
    );
}

// Enrich team members with task stats
foreach ($teamMembers as &$member) {
    $stats = Database::fetchOne(
        "SELECT
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status != 'Completed' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Blocked' THEN 1 ELSE 0 END) as blocked
         FROM tasks WHERE assigned_to = :empId",
        [':empId' => $member['id']]
    );
    $member['stats'] = $stats;

    // Get last daily update date
    $lastUpdate = Database::fetchOne(
        "SELECT work_date FROM daily_updates WHERE employee_id = :empId ORDER BY work_date DESC LIMIT 1",
        [':empId' => $member['id']]
    );
    $member['last_update'] = $lastUpdate ? $lastUpdate['work_date'] : null;
}
unset($member);

// Calculate team totals
$teamTotals = [
    'total_tasks' => 0,
    'completed' => 0,
    'overdue' => 0,
    'in_progress' => 0,
    'blocked' => 0,
];
foreach ($teamMembers as $member) {
    $teamTotals['total_tasks'] += (int)($member['stats']['total_tasks'] ?? 0);
    $teamTotals['completed'] += (int)($member['stats']['completed'] ?? 0);
    $teamTotals['overdue'] += (int)($member['stats']['overdue'] ?? 0);
    $teamTotals['in_progress'] += (int)($member['stats']['in_progress'] ?? 0);
    $teamTotals['blocked'] += (int)($member['stats']['blocked'] ?? 0);
}

// Get recent team daily updates
$recentTeamUpdates = [];
if (!empty($teamMembers)) {
    $memberIds = array_column($teamMembers, 'id');
    $placeholders = [];
    $params = [];
    foreach ($memberIds as $i => $id) {
        $placeholders[] = ':m' . $i;
        $params[':m' . $i] = $id;
    }
    $inClause = implode(',', $placeholders);

    $recentTeamUpdates = Database::fetchAll(
        "SELECT du.*, u.name as employee_name
         FROM daily_updates du
         JOIN users u ON du.employee_id = u.id
         WHERE du.employee_id IN ($inClause)
         ORDER BY du.work_date DESC, du.created_at DESC
         LIMIT 10",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-tie me-2"></i>Team Overview</h2>
    <div>
        <a href="<?php echo url('tasks/create.php'); ?>" class="btn btn-primary me-2">
            <i class="fas fa-plus me-1"></i>Create Task
        </a>
        <a href="<?php echo url('manager/reports.php'); ?>" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-1"></i>Reports
        </a>
    </div>
</div>

<?php echo renderFlash(); ?>

<!-- Team Summary Cards -->
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
                <h6 class="mb-0 text-muted">Total Tasks</h6>
                <h3 class="mb-0"><?php echo $teamTotals['total_tasks']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Completed</h6>
                <h3 class="mb-0"><?php echo $teamTotals['completed']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-warning h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">In Progress</h6>
                <h3 class="mb-0"><?php echo $teamTotals['in_progress']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-danger h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Overdue</h6>
                <h3 class="mb-0 text-danger"><?php echo $teamTotals['overdue']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-secondary h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Blocked</h6>
                <h3 class="mb-0"><?php echo $teamTotals['blocked']; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Team Members Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Members</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($teamMembers)): ?>
            <div class="empty-state p-4">
                <i class="fas fa-users"></i>
                <h5>No team members</h5>
                <p class="text-muted">No employees are assigned to your team yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th class="text-center">Total Tasks</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">In Progress</th>
                            <th class="text-center">Overdue</th>
                            <th>Last Update</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMembers as $member): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($member['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td class="text-center"><?php echo (int)($member['stats']['total_tasks'] ?? 0); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo (int)($member['stats']['completed'] ?? 0); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo (int)($member['stats']['in_progress'] ?? 0); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php $ov = (int)($member['stats']['overdue'] ?? 0); ?>
                                    <span class="badge bg-<?php echo $ov > 0 ? 'danger' : 'secondary'; ?>"><?php echo $ov; ?></span>
                                </td>
                                <td>
                                    <?php if ($member['last_update']): ?>
                                        <?php echo formatDate($member['last_update']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url('tasks/index.php?employee=' . $member['id']); ?>"
                                           class="btn btn-outline-primary" title="View Tasks">
                                            <i class="fas fa-tasks"></i>
                                        </a>
                                        <a href="<?php echo url('daily-updates/index.php?employee=' . $member['id']); ?>"
                                           class="btn btn-outline-secondary" title="View Updates">
                                            <i class="fas fa-calendar-alt"></i>
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

<!-- Recent Team Updates -->
<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Recent Team Updates</h5>
        <a href="<?php echo url('daily-updates/index.php'); ?>" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentTeamUpdates)): ?>
            <div class="empty-state p-4">
                <i class="far fa-calendar-alt"></i>
                <h5>No recent updates</h5>
                <p class="text-muted">No daily updates have been submitted by your team yet.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($recentTeamUpdates as $update): ?>
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <strong><?php echo htmlspecialchars($update['employee_name']); ?></strong>
                                    <span class="text-muted">—</span>
                                    <?php echo formatDateWithDay($update['work_date']); ?>
                                </h6>
                                <?php if (!empty($update['completed_work'])): ?>
                                    <div class="small">
                                        <strong>Completed:</strong>
                                        <?php echo htmlspecialchars(substr($update['completed_work'], 0, 100)); ?>
                                        <?php if (strlen($update['completed_work']) > 100) echo '...'; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($update['blockers'])): ?>
                                    <div class="small text-danger">
                                        <strong>Blockers:</strong>
                                        <?php echo htmlspecialchars(substr($update['blockers'], 0, 80)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ms-3">
                                <a href="<?php echo url('daily-updates/view.php?update_id=' . $update['id']); ?>"
                                   class="btn btn-sm btn-outline-primary">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
