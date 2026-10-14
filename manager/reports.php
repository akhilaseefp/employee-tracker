<?php
// Manager reports page - performance metrics and CSV export

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireManager();

$pageTitle = 'Team Reports';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];

// Get filter parameters
$filters = [
    'date_from' => validateDate($_GET['date_from'] ?? ''),
    'date_to' => validateDate($_GET['date_to'] ?? ''),
    'employee' => validateInt($_GET['employee'] ?? 0, 1),
    'export' => $_GET['export'] ?? '',
];

// Default to last 30 days if no dates provided
if (empty($filters['date_from']) && empty($filters['date_to'])) {
    $filters['date_to'] = date('Y-m-d');
    $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
}

// Get team members for filter dropdown
// Admins see all employees, managers see their direct reports
if ($userRole === ROLE_ADMIN) {
    $teamMembers = Database::fetchAll(
        'SELECT id, name, email FROM users WHERE role = :role AND is_active = 1 ORDER BY name',
        [':role' => ROLE_EMPLOYEE]
    );
} else {
    $teamMembers = getTeamMembers($userId);
}

// Build team members WHERE clause
$teamWhereClause = '';
$teamParams = [];
$employeeIds = [];

if (!empty($teamMembers)) {
    $employeeIds = array_column($teamMembers, 'id');
    $placeholders = [];
    foreach ($employeeIds as $index => $id) {
        $placeholders[] = ':team_member_' . $index;
        $teamParams[':team_member_' . $index] = $id;
    }
    $teamWhereClause = ' AND t.assigned_to IN (' . implode(', ', $placeholders) . ')';
}

// Apply employee filter if specified and authorized
if (!empty($filters['employee'])) {
    if ($userRole === ROLE_ADMIN || in_array($filters['employee'], $employeeIds)) {
        // Clear team params and filter to specific employee
        $teamParams = [':specific_employee' => $filters['employee']];
        $teamWhereClause = ' AND t.assigned_to = :specific_employee';
    }
}

// Build date range conditions
$dateConditions = '';
if (!empty($filters['date_from'])) {
    $dateConditions .= ' AND t.created_at >= :date_from';
    $teamParams[':date_from'] = $filters['date_from'] . ' 00:00:00';
}
if (!empty($filters['date_to'])) {
    $dateConditions .= ' AND t.created_at <= :date_to';
    $teamParams[':date_to'] = $filters['date_to'] . ' 23:59:59';
}

// Build main query for tasks
$taskQuery = "
    SELECT t.*, u.name as employee_name
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE 1=1 {$teamWhereClause} {$dateConditions}
    ORDER BY t.created_at DESC
";

// Handle CSV export
if (!empty($filters['export']) && $filters['export'] === 'csv') {
    // Fetch data for export
    $exportData = Database::fetchAll($taskQuery, $teamParams);

    // Prepare CSV data
    $csvHeaders = ['ID', 'Title', 'Employee', 'Status', 'Priority', 'Start Date', 'Due Date', 'Completed At', 'Created At'];
    $csvRows = [];

    foreach ($exportData as $task) {
        $csvRows[] = [
            $task['id'],
            $task['title'],
            $task['employee_name'],
            $task['status'],
            $task['priority'],
            $task['start_date'],
            $task['due_date'],
            $task['completed_at'] ?? '',
            $task['created_at']
        ];
    }

    // Generate filename
    $filename = 'team_report_' . date('Y-m-d_H-i-s') . '.csv';
    downloadCsv($filename, $csvHeaders, $csvRows);
    exit;
}

// Get performance summary
$summaryParams = $teamParams;
if (empty($summaryParams[':date_from'])) {
    $summaryParams[':date_from'] = $filters['date_from'] . ' 00:00:00';
}
if (empty($summaryParams[':date_to'])) {
    $summaryParams[':date_to'] = $filters['date_to'] . ' 23:59:59';
}

$summaryQuery = "
    SELECT
        COUNT(*) as total_tasks,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN t.status = 'Blocked' THEN 1 ELSE 0 END) as blocked_tasks,
        SUM(CASE WHEN t.status != 'Completed' AND t.due_date < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    WHERE 1=1 {$teamWhereClause} {$dateConditions}
";

$summary = Database::fetchOne($summaryQuery, $teamParams);

$totalTasks = (int)($summary['total_tasks'] ?? 0);
$completedTasks = (int)($summary['completed_tasks'] ?? 0);
$overdueTasks = (int)($summary['overdue_tasks'] ?? 0);

// Calculate rates with zero protection
$completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$onTimeRate = $totalTasks > 0 ? round((($totalTasks - $overdueTasks) / $totalTasks) * 100) : 0;

// Get task details for table (limited for display)
$displayQuery = $taskQuery . " LIMIT 50";
$tasks = Database::fetchAll($displayQuery, $teamParams);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-bar me-2"></i>Team Reports</h2>
    <div>
        <a href="<?php echo url('manager/reports.php?export=csv&' . http_build_query(array_filter($filters))); ?>"
           class="btn btn-outline-success me-2">
            <i class="fas fa-file-export me-1"></i>Export CSV
        </a>
        <a href="<?php echo url('manager/team.php'); ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to Team
        </a>
    </div>
</div>

<?php echo renderFlash(); ?>

<!-- Date Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
            </div>

            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
            </div>

            <div class="col-md-3">
                <?php if (!empty($teamMembers)): ?>
                    <label for="employee" class="form-label">Employee</label>
                    <select class="form-select" id="employee" name="employee">
                        <option value="">All Team Members</option>
                        <?php foreach ($teamMembers as $member): ?>
                            <option value="<?php echo $member['id']; ?>"
                                <?php echo (!empty($filters['employee']) && $filters['employee'] == $member['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Apply Filters
                </button>
                <a href="<?php echo url('manager/reports.php'); ?>" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="fas fa-times me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Total Tasks</h6>
                <h3 class="mb-0"><?php echo $totalTasks; ?></h3>
                <small class="text-muted">In selected period</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Completed</h6>
                <h3 class="mb-0"><?php echo $completedTasks; ?></h3>
                <small class="text-muted"><?php echo $completionRate; ?>% completion rate</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-warning h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">In Progress</h6>
                <h3 class="mb-0"><?php echo (int)($summary['in_progress_tasks'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-danger h-100">
            <div class="card-body">
                <h6 class="mb-0 text-muted">Overdue</h6>
                <h3 class="mb-0 text-danger"><?php echo $overdueTasks; ?></h3>
                <small class="text-muted"><?php echo $onTimeRate; ?>% on-time rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Task Details</h5>
        <small class="text-muted">Showing most recent 50 tasks</small>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tasks)): ?>
            <div class="empty-state p-4">
                <i class="fas fa-tasks"></i>
                <h5>No tasks found</h5>
                <p class="text-muted">No tasks match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th style="width: 30%">Title</th>
                            <th style="width: 12%">Status</th>
                            <th style="width: 12%">Priority</th>
                            <th style="width: 12%">Due Date</th>
                            <th style="width: 12%">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url('tasks/view.php?task_id=' . $task['id']); ?>">
                                        <?php echo htmlspecialchars($task['employee_name']); ?>
                                    </a>
                                </td>
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
                                    <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                    <?php if (isTaskOverdue($task['status'], $task['due_date'])): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getPriorityBadgeClass($task['priority']); ?>">
                                        <?php echo htmlspecialchars($task['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($task['due_date']); ?></td>
                                <td><?php echo formatDateTime($task['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>