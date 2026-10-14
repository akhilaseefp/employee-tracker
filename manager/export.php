<?php
// Manager export CSV handler
// Exports task and performance data to CSV

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireManager();

$user = getCurrentUser();
$userId = (int) $user['id'];
$userRole = $user['role'];

// Get filter parameters from query
$dateFrom = validateDate($_GET['date_from'] ?? '');
$dateTo = validateDate($_GET['date_to'] ?? '');
$employeeId = validateInt($_GET['employee'] ?? 0, 1);

// Build where clause and params
$where = 'WHERE 1=1';
$params = [];

if ($userRole === ROLE_ADMIN) {
    if (!empty($employeeId)) {
        $where .= ' AND t.assigned_to = :employeeId';
        $params[':employeeId'] = $employeeId;
    }
} else {
    // Manager scoping: tasks assigned to direct reports or managed by this manager
    $teamMembers = getTeamMembers($userId);
    if (empty($teamMembers)) {
        $where .= ' AND 1=0';
    } else {
        $teamIds = array_column($teamMembers, 'id');
        if (!empty($employeeId)) {
            if (in_array($employeeId, $teamIds)) {
                $where .= ' AND t.assigned_to = :employeeId';
                $params[':employeeId'] = $employeeId;
            } else {
                $where .= ' AND 1=0';
            }
        } else {
            $placeholders = [];
            foreach ($teamIds as $idx => $id) {
                $p = ':team_member_' . $idx;
                $placeholders[] = $p;
                $params[$p] = $id;
            }
            $params[':managerId'] = $userId;
            $where .= ' AND (t.assigned_to IN (' . implode(', ', $placeholders) . ') OR t.manager_id = :managerId)';
        }
    }
}

// Date filters
if (!empty($dateFrom) && !empty($dateTo)) {
    $where .= ' AND (t.due_date BETWEEN :dateFrom AND :dateTo OR (t.created_at >= :dateFromDt AND t.created_at <= :dateToDt))';
    $params[':dateFrom'] = $dateFrom;
    $params[':dateTo'] = $dateTo;
    $params[':dateFromDt'] = $dateFrom . ' 00:00:00';
    $params[':dateToDt'] = $dateTo . ' 23:59:59';
} elseif (!empty($dateFrom)) {
    $where .= ' AND (t.due_date >= :dateFrom OR t.created_at >= :dateFromDt)';
    $params[':dateFrom'] = $dateFrom;
    $params[':dateFromDt'] = $dateFrom . ' 00:00:00';
} elseif (!empty($dateTo)) {
    $where .= ' AND (t.due_date <= :dateTo OR t.created_at <= :dateToDt)';
    $params[':dateTo'] = $dateTo;
    $params[':dateToDt'] = $dateTo . ' 23:59:59';
}

// Get tasks data
$tasks = Database::fetchAll(
    "SELECT t.id, t.title, u.name as assigned_employee, u.email as assigned_email,
            t.priority, t.status, t.start_date, t.due_date, t.completed_at,
            c.name as creator_name
     FROM tasks t
     JOIN users u ON t.assigned_to = u.id
     LEFT JOIN users c ON t.created_by = c.id
     {$where}
     ORDER BY t.due_date ASC, t.priority DESC",
    $params
);

// Column headers
$columns = [
    'ID', 'Task Title', 'Assigned Employee', 'Employee Email', 'Priority', 'Status',
    'Start Date', 'Due Date', 'Completed Date', 'Created By'
];

// Write task data
$rows = [];
foreach ($tasks as $task) {
    $completedDate = $task['completed_at'] ? formatDateTime($task['completed_at']) : '';
    $creator = $task['creator_name'] ?? '';

    $rows[] = [
        $task['id'],
        $task['title'],
        $task['assigned_employee'],
        $task['assigned_email'],
        $task['priority'],
        $task['status'],
        formatDate($task['start_date']),
        formatDate($task['due_date']),
        $completedDate,
        $creator,
    ];
}

$filename = 'team-report-' . date('Y-m-d') . '.csv';
downloadCsv($filename, $columns, $rows);