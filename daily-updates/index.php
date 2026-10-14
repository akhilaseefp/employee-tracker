<?php
// Daily updates list page
// Shows daily updates based on user role

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();

$pageTitle = 'Daily Updates';
$user = getCurrentUser();
$userId = (int) $user['id'];
$userRole = $user['role'];

// Use the logged-in manager's ID when looking up their team.
$managerId = $userId;

// Get filter parameters
$filters = [
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
    // Employees see only their own updates
    $whereConditions[] = 'du.employee_id = :userId';
    $params[':userId'] = $userId;
} elseif ($userRole === ROLE_MANAGER) {
    // Managers see updates from their team
    $whereConditions[] = 'du.employee_id IN (SELECT id FROM users WHERE manager_id = :managerId)';
    $params[':managerId'] = $managerId;
} else {
    // Admin sees all updates
    // No role restriction
}

// Apply filters
if (!empty($filters['employee'])) {
    if ($userRole === ROLE_ADMIN || $userRole === ROLE_MANAGER) {
        $whereConditions[] = 'du.employee_id = :employeeId';
        $params[':employeeId'] = $filters['employee'];
    }
}

if (!empty($filters['date_from'])) {
    $whereConditions[] = 'du.work_date >= :dateFrom';
    $params[':dateFrom'] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $whereConditions[] = 'du.work_date <= :dateTo';
    $params[':dateTo'] = $filters['date_to'];
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get employees for filter dropdown
$filterEmployees = [];
if ($userRole === ROLE_MANAGER) {
    $filterEmployees = getTeamMembers($managerId);
} elseif ($userRole === ROLE_ADMIN) {
    $filterEmployees = Database::fetchAll(
        'SELECT id, name, email FROM users WHERE role = :role AND is_active = 1 ORDER BY name',
        [':role' => ROLE_EMPLOYEE]
    );
}

// Build main query
$baseQuery = "
    SELECT du.*, u.name as employee_name, u.email as employee_email
    FROM daily_updates du
    JOIN users u ON du.employee_id = u.id
    {$whereClause}
    ORDER BY du.work_date DESC, du.created_at DESC
";

// Paginate
$result = paginateQuery($baseQuery, $params, $filters['page'], $filters['per_page']);
$updates = $result['data'];
$pagination = $result['pagination'];

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-alt me-2"></i>Daily Updates</h2>
    <?php if ($userRole === ROLE_EMPLOYEE): ?>
        <a href="<?php echo url('daily-updates/create.php'); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Submit Update
        </a>
    <?php endif; ?>
</div>

<?php echo renderFlash(); ?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <?php if ($userRole === ROLE_MANAGER || $userRole === ROLE_ADMIN): ?>
                <div class="col-md-3">
                    <label for="employee" class="form-label visually-hidden">Employee</label>
                    <select class="form-select" id="employee" name="employee">
                        <option value="">All Employees</option>
                        <?php foreach ($filterEmployees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"
                                <?php echo ($filters['employee'] == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label for="date_from" class="form-label visually-hidden">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
            </div>

            <div class="col-md-3">
                <label for="date_to" class="form-label visually-hidden">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2 w-100">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="<?php echo url('daily-updates/index.php'); ?>" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Updates list -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($updates)): ?>
            <div class="empty-state p-5">
                <i class="far fa-calendar-alt"></i>
                <h5>No daily updates found</h5>
                <p class="text-muted">No updates match your current filters.</p>
                <a href="<?php echo url('daily-updates/create.php'); ?>" class="btn btn-primary mt-2">Create First Update</a>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($updates as $update): ?>
                    <div class="list-group-item px-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars($update['employee_name']); ?>
                                    <small class="text-muted ms-2">
                                        <?php echo formatDate($update['work_date']); ?>
                                    </small>
                                </h6>
                                <p class="mb-1 small text-muted">
                                    Submitted: <?php echo formatDateTime($update['created_at']); ?>
                                </p>
                                <?php if (!empty($update['completed_work'])): ?>
                                    <div class="small">
                                        <strong>Completed:</strong>
                                        <?php echo htmlspecialchars(substr($update['completed_work'], 0, 120)); ?>
                                        <?php if (strlen($update['completed_work']) > 120) echo '...'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ms-3">
                                <a href="<?php echo url('daily-updates/view.php?update_id=' . $update['id']); ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['last_page'] > 1): ?>
                <div class="card-footer bg-light">
                    <nav aria-label="Updates pagination">
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
                            $endPage = min($pagination['last_page'], $pagination['current_page'] + 2);
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
                            of <?php echo $pagination['total']; ?> updates
                        </small>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>