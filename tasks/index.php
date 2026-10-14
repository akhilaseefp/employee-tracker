<?php
// Tasks list page
// Shows tasks based on user role with filtering, search, and pagination

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();

$pageTitle = 'Tasks';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];
$managerId = $user['manager_id'] ?? null;

// Get filter parameters
$filters = [
    'status' => validateString($_GET['status'] ?? ''),
    'priority' => validateString($_GET['priority'] ?? ''),
    'search' => validateString($_GET['search'] ?? '', 100),
    'employee' => validateInt($_GET['employee'] ?? 0, 1),
    'date_from' => validateDate($_GET['date_from'] ?? ''),
    'date_to' => validateDate($_GET['date_to'] ?? ''),
    'page' => max(1, validateInt($_GET['page'] ?? 1, 1) ?? 1),
    'per_page' => 10,
];

// Build query based on role
$whereConditions = [];
$params = [];

if ($userRole === ROLE_EMPLOYEE) {
    // Employees see only their own tasks
    $whereConditions[] = 't.assigned_to = :userId';
    $params[':userId'] = $userId;
} elseif ($userRole === ROLE_MANAGER) {
    // Managers see tasks they own (created for their team)
    $whereConditions[] = 't.manager_id = :managerId';
    $params[':managerId'] = $userId;
} else {
    // Admin sees all tasks
    // No role restriction
}

// Apply filters
if (!empty($filters['status'])) {
    $whereConditions[] = 't.status = :status';
    $params[':status'] = $filters['status'];
}

if (!empty($filters['priority'])) {
    $whereConditions[] = 't.priority = :priority';
    $params[':priority'] = $filters['priority'];
}

if (!empty($filters['search'])) {
    $whereConditions[] = '(t.title LIKE :search_title OR t.description LIKE :search_desc)';
    $params[':search_title'] = '%' . $filters['search'] . '%';
    $params[':search_desc'] = '%' . $filters['search'] . '%';
}

if (!empty($filters['employee'])) {
    if ($userRole === ROLE_ADMIN || $userRole === ROLE_MANAGER) {
        $whereConditions[] = 't.assigned_to = :employeeId';
        $params[':employeeId'] = $filters['employee'];
    }
}

if (!empty($filters['date_from'])) {
    $whereConditions[] = 't.due_date >= :dateFrom';
    $params[':dateFrom'] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $whereConditions[] = 't.due_date <= :dateTo';
    $params[':dateTo'] = $filters['date_to'];
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get assignable employees for filter dropdown
$assignableEmployees = [];
if ($userRole === ROLE_MANAGER) {
    $assignableEmployees = getAssignableEmployees($userId);
} elseif ($userRole === ROLE_ADMIN) {
    $assignableEmployees = Database::fetchAll(
        'SELECT id, name, email FROM users WHERE role = :role AND is_active = 1 ORDER BY name',
        [':role' => ROLE_EMPLOYEE]
    );
}

// Build main query
$baseQuery = "
    SELECT t.*, u.name as assigned_name, u.email as assigned_email
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    {$whereClause}
    ORDER BY t.due_date ASC, t.priority DESC
";

// Paginate
$result = paginateQuery($baseQuery, $params, $filters['page'], $filters['per_page']);
$tasks = $result['data'];
$pagination = $result['pagination'];

// Status and priority options
$statusOptions = [STATUS_TODO, STATUS_IN_PROGRESS, STATUS_BLOCKED, STATUS_COMPLETED];
$priorityOptions = [PRIORITY_LOW, PRIORITY_MEDIUM, PRIORITY_HIGH, PRIORITY_URGENT];

include __DIR__ . '/../includes/header.php';
?>

<!-- Tasks list -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tasks me-2"></i>Tasks</h2>
    <?php if ($userRole === ROLE_MANAGER || $userRole === ROLE_ADMIN): ?>
        <a href="<?php echo url('tasks/create.php'); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Task
        </a>
    <?php endif; ?>
</div>

<!-- Flash messages -->
<?php echo renderFlash(); ?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label visually-hidden">Search</label>
                <input type="text" class="form-control" id="search" name="search"
                       placeholder="Search tasks..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
            </div>

            <div class="col-md-2">
                <label for="status" class="form-label visually-hidden">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>"
                            <?php echo ($filters['status'] === $status) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="priority" class="form-label visually-hidden">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">All Priorities</option>
                    <?php foreach ($priorityOptions as $priority): ?>
                        <option value="<?php echo htmlspecialchars($priority); ?>"
                            <?php echo ($filters['priority'] === $priority) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($priority); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($userRole === ROLE_MANAGER || $userRole === ROLE_ADMIN): ?>
                <div class="col-md-2">
                    <label for="employee" class="form-label visually-hidden">Employee</label>
                    <select class="form-select" id="employee" name="employee">
                        <option value="">All Employees</option>
                        <?php foreach ($assignableEmployees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"
                                <?php echo ($filters['employee'] == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label for="date_from" class="form-label visually-hidden">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
            </div>

            <div class="col-md-2">
                <label for="date_to" class="form-label visually-hidden">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
            </div>

            <div class="col-md-12">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="<?php echo url('tasks/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($tasks)): ?>
            <div class="empty-state p-5">
                <i class="fas fa-tasks"></i>
                <h5>No tasks found</h5>
                <p class="text-muted">No tasks match your current filters.</p>
                <?php if ($userRole === ROLE_MANAGER || $userRole === ROLE_ADMIN): ?>
                    <a href="<?php echo url('tasks/create.php'); ?>" class="btn btn-primary mt-2">Create First Task</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30%">Title</th>
                            <th style="width: 15%">Assigned To</th>
                            <th style="width: 10%">Priority</th>
                            <th style="width: 12%">Status</th>
                            <th style="width: 12%">Start Date</th>
                            <th style="width: 12%">Due Date</th>
                            <th style="width: 9%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    <?php if (!empty($task['description'])): ?>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars(substr($task['description'], 0, 80)); ?>
                                            <?php if (strlen($task['description']) > 80) echo '...'; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($task['assigned_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($task['assigned_email']); ?></small>
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
                                <td><?php echo formatDate($task['start_date']); ?></td>
                                <td>
                                    <?php echo formatDate($task['due_date']); ?>
                                    <?php if (isTaskOverdue($task['status'], $task['due_date'])): ?>
                                        <span class="text-danger small">(<strong><?php echo getOverdueDays($task['due_date']); ?></strong> days overdue)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url('tasks/view.php?task_id=' . $task['id']); ?>"
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($userRole === ROLE_ADMIN ||
                                                  ($userRole === ROLE_MANAGER && $task['manager_id'] == $userId) ||
                                                  ($userRole === ROLE_EMPLOYEE && $task['assigned_to'] == $userId)): ?>
                                            <a href="<?php echo url('tasks/edit.php?task_id=' . $task['id']); ?>"
                                               class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="card-footer bg-light">
                    <nav aria-label="Task pagination">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($pagination['has_prev']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $pagination['prev_page']])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $pagination['next_page']])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <small class="text-muted d-block text-center mt-2">
                            Showing <?php echo ($pagination['current_page'] - 1) * $filters['per_page'] + 1; ?> to
                            <?php echo min($pagination['current_page'] * $filters['per_page'], $pagination['total']); ?>
                            of <?php echo $pagination['total']; ?> tasks
                        </small>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>