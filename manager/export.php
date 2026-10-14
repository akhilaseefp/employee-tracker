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
$userId = $user['id'];
$managerId = $user['manager_id'] ?? null;

// Get filter parameters from query
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$employeeId = validateInt($_GET['employee'] ?? 0, 1);

// Build where clause
$where = "WHERE t.manager_id = :managerId AND t.due_date BETWEEN :dateFrom AND :dateTo";
$params = [':managerId' => $managerId, ':dateFrom' => $dateFrom, ':dateTo' => $dateTo];

if (!empty($employeeId)) {
    $where .= ' AND t.assigned_to = :employeeId';
    $params[':employeeId'] = $employeeId;
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

// Get employees for reference
$employees = Database::fetchAll(
    "SELECT id, name, email FROM users WHERE manager_id = :managerId AND role = :employeeRole AND is_active = 1",
    [':managerId' => $managerId, ':employeeRole' => ROLE_EMPLOYEE]
);

// CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="team-report-' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Output BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// Column headers
$columns = ['ID', 'Task Title', 'Assigned Employee', 'Employee Email', 'Priority', 'Status',
            'Start Date', 'Due Date', 'Completed Date', 'Created By'];
fputcsv($output = fopen('php://memory', 'w'), $columns);

// Write task data
foreach ($tasks as $task) {
    $completedDate = $task['completed_at'] ? formatDateTime($task['completed_at']) : '';
    $creator = $task['creator_name'] ? htmlspecialchars($task['creator_name']) : '';

    $row = [
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

    // CSV-safe string conversion (prevents spreadsheet formula injection)
    $safeRow = array_map(function($cell) {
        return sanitizeCsv((string) $cell);
    }, $row);

    fputcsv($output, $safeRow);
}

rewind($output);
$csv = stream_get_contents($output);
fclose($output);

echo $csv;
exit;