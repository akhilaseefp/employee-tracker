<?php
// Input validation and sanitization helpers
// Centralized validation for all user inputs.

require_once __DIR__ . '/config.php';

/**
 * Validate and sanitize a string input.
 *
 * @param string $value Input value
 * @param int|null $maxLength Maximum allowed length (null for no limit)
 * @param bool $required Whether the field is required
 * @return string|false Validated string or false if validation fails
 */
function validateString(string $value, ?int $maxLength = null, bool $required = false): string|false {
    $value = trim($value);

    if ($required && empty($value)) {
        return false;
    }

    if ($value === '' && !$required) {
        return '';
    }

    if ($maxLength !== null && strlen($value) > $maxLength) {
        return false;
    }

    return $value;
}

/**
 * Validate an email address.
 *
 * @param string $value Input value
 * @param bool $required Whether the field is required
 * @return string|false Validated email or false if validation fails
 */
function validateEmail(string $value, bool $required = false): string|false {
    $value = trim($value);

    if ($required && empty($value)) {
        return false;
    }

    if ($value === '' && !$required) {
        return '';
    }

    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    return $value;
}

/**
 * Validate a date string (YYYY-MM-DD format).
 *
 * @param string $value Input value
 * @param bool $required Whether the field is required
 * @param bool $allowPast Whether past dates are allowed
 * @return string|false Validated date or false if validation fails
 */
function validateDate(string $value, bool $required = false, bool $allowPast = true): string|false {
    $value = trim($value);

    if ($required && empty($value)) {
        return false;
    }

    if ($value === '' && !$required) {
        return '';
    }

    // Validate format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    // Validate it's a real date
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return false;
    }

    // Check if past dates are allowed
    if (!$allowPast) {
        $today = new DateTime('today');
        $inputDate = new DateTime($value);
        if ($inputDate < $today) {
            return false;
        }
    }

    return $value;
}

/**
 * Validate a priority value.
 *
 * @param string $value Input value
 * @return string|false Validated priority or false if validation fails
 */
function validatePriority(string $value): string|false {
    $value = trim($value);
    $allowed = [PRIORITY_LOW, PRIORITY_MEDIUM, PRIORITY_HIGH, PRIORITY_URGENT];

    if (in_array($value, $allowed, true)) {
        return $value;
    }

    return false;
}

/**
 * Validate a status value.
 *
 * @param string $value Input value
 * @return string|false Validated status or false if validation fails
 */
function validateStatus(string $value): string|false {
    $value = trim($value);
    $allowed = [STATUS_TODO, STATUS_IN_PROGRESS, STATUS_BLOCKED, STATUS_COMPLETED];

    if (in_array($value, $allowed, true)) {
        return $value;
    }

    return false;
}

/**
 * Validate a role value.
 *
 * @param string $value Input value
 * @return string|false Validated role or false if validation fails
 */
function validateRole(string $value): string|false {
    $value = trim($value);
    $allowed = [ROLE_ADMIN, ROLE_MANAGER, ROLE_EMPLOYEE];

    if (in_array($value, $allowed, true)) {
        return $value;
    }

    return false;
}

/**
 * Validate an integer (ID, count, etc.).
 *
 * @param mixed $value Input value
 * @param int|null $min Minimum allowed value
 * @param int|null $max Maximum allowed value
 * @return int|false Validated integer or false if validation fails
 */

function validateInt($value, ?int $min = null, ?int $max = null): int|false {
    // Request values can be malformed, including arrays.
    if (!is_string($value) && !is_int($value)) {
        return false;
    }

    if (is_string($value)) {
        $value = trim($value);

        if ($value === '') {
            return false;
        }
    }

    // Only pass range limits when they have actually been supplied.
    $options = [];

    if ($min !== null) {
        $options['min_range'] = $min;
    }

    if ($max !== null) {
        $options['max_range'] = $max;
    }

    $int = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => $options,
    ]);

    if ($int === false) {
        return false;
    }

    return $int;
}

/**
 * Escape HTML special characters for safe output.
 *
 * @param string $value Input value
 * @param int $flags Flags for htmlspecialchars
 * @return string Escaped string
 */
function escapeHtml(string $value, int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401): string {
    return htmlspecialchars($value, $flags, 'UTF-8');
}

/**
 * Format a date for display.
 *
 * @param string $date Date string in YYYY-MM-DD format
 * @param string $format Display format (default: M j, Y)
 * @return string Formatted date
 */
function formatDate(string $date, string $format = 'M j, Y'): string {
    if (empty($date)) {
        return '';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Format a datetime for display.
 *
 * @param string $datetime DateTime string in YYYY-MM-DD HH:MM:SS format
 * @param string $format Display format (default: M j, Y g:i A)
 * @return string Formatted datetime
 */
function formatDateTime(string $datetime, string $format = 'M j, Y g:i A'): string {
    if (empty($datetime)) {
        return '';
    }

    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Calculate overdue days for a task.
 *
 * @param string $dueDate Due date in YYYY-MM-DD format
 * @return int Number of days overdue (0 if not overdue or in future)
 */
function getOverdueDays(string $dueDate): int {
    if (empty($dueDate)) {
        return 0;
    }

    try {
        $due = new DateTime($dueDate);
        $today = new DateTime('today');
        $diff = $due->diff($today);

        // Return days overdue (negative if future date)
        $days = (int)$diff->format('%r%a');
        return max(0, $days); // Only return positive days overdue
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Check if a task is overdue.
 *
 * @param string $status Task status
 * @param string $dueDate Due date in YYYY-MM-DD format
 * @return bool True if overdue
 */
function isTaskOverdue(string $status, string $dueDate): bool {
    if (empty($dueDate) || $status === STATUS_COMPLETED) {
        return false;
    }

    return getOverdueDays($dueDate) > 0;
}

/**
 * Check if an employee/user is active.
 *
 * @param int $userId User ID
 * @return bool True if active
 */
function isActiveEmployee(int $userId): bool {
    $user = Database::fetchOne(
        'SELECT is_active FROM users WHERE id = :id',
        [':id' => $userId]
    );

    return $user && $user['is_active'] == 1;
}

/**
 * Get direct reports of a manager.
 *
 * @param int $managerId Manager ID
 * @return array Array of user records
 */
function getDirectReports(int $managerId): array {
    return Database::fetchAll(
        'SELECT id, name, email FROM users WHERE manager_id = :managerId AND is_active = 1 ORDER BY name',
        [':managerId' => $managerId]
    );
}

/**
 * Get all team members (direct reports) of a manager.
 *
 * @param int $managerId Manager ID
 * @return array Array of user records
 */
function getTeamMembers(int $managerId): array {
    return getDirectReports($managerId);
}

/**
 * Get employees that a user can assign tasks to.
 * Admins can assign to any employee, managers to their team.
 *
 * @param int $userId Current user ID
 * @return array Array of user records
 */
function getAssignableEmployees(int $userId): array {
    $user = Database::fetchOne(
        'SELECT role, manager_id FROM users WHERE id = :id',
        [':id' => $userId]
    );

    if (!$user) {
        return [];
    }

    if ($user['role'] === ROLE_ADMIN) {
        // Admin can assign to any employee
        return Database::fetchAll(
            'SELECT id, name, email FROM users WHERE role = :role AND is_active = 1 ORDER BY name',
            [':role' => ROLE_EMPLOYEE]
        );
    } elseif ($user['role'] === ROLE_MANAGER) {
        // Manager can assign to their team
        return getTeamMembers($userId);
    }

    // Employee cannot assign tasks
    return [];
}

/**
 * Get the manager ID for a user.
 * For admins, returns null (they don't have a manager).
 *
 * @param int $userId User ID
 * @return int|null Manager ID or null
 */
function getManagerId(int $userId): ?int {
    $user = Database::fetchOne(
        'SELECT manager_id FROM users WHERE id = :id',
        [':id' => $userId]
    );

    return $user ? ($user['manager_id'] ?? null) : null;
}

/**
 * Paginate a query result.
 *
 * @param string $baseQuery Base SQL query (without LIMIT/OFFSET)
 * @param array $params Query parameters
 * @param int $page Current page number (1-based)
 * @param int $perPage Items per page
 * @return array ['data' => results, 'pagination' => pagination info]
 */
function paginateQuery(string $baseQuery, array $params, int $page, int $perPage): array {
    $offset = ($page - 1) * $perPage;

    // Get total count
    $countQuery = preg_match('/^select\s/i', $baseQuery)
        ? preg_replace('/^select\s(.+)\sfrom\s/i', 'SELECT COUNT(*) FROM ', $baseQuery)
        : 'SELECT COUNT(*) FROM (' . $baseQuery . ') AS count_table';

    $total = Database::fetchOne($countQuery, $params)['COUNT(*)'];

    // Get paginated data
    $dataQuery = $baseQuery . ' LIMIT :limit OFFSET :offset';
    $params[':limit'] = $perPage;
    $params[':offset'] = $offset;

    $data = Database::fetchAll($dataQuery, $params);

    $totalPages = (int)ceil($total / $perPage);

    return [
        'data' => $data,
        'pagination' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page - 1,
            'next_page' => $page + 1,
        ]
    ];
}

/**
 * Get Bootstrap badge class for priority.
 *
 * @param string $priority Priority level
 * @return string Bootstrap background class
 */
function getPriorityBadgeClass(string $priority): string {
    return match ($priority) {
        PRIORITY_LOW => 'success',
        PRIORITY_MEDIUM => 'info',
        PRIORITY_HIGH => 'warning',
        PRIORITY_URGENT => 'danger',
        default => 'secondary',
    };
}

/**
 * Get Bootstrap badge class for status.
 *
 * @param string $status Status value
 * @return string Bootstrap background class
 */
function getStatusBadgeClass(string $status): string {
    return match ($status) {
        STATUS_TODO => 'secondary',
        STATUS_IN_PROGRESS => 'primary',
        STATUS_BLOCKED => 'danger',
        STATUS_COMPLETED => 'success',
        default => 'secondary',
    };
}

/**
 * Get icon class for status.
 *
 @param string $status Status value
 * @return string Font Awesome icon class
 */
function getStatusIcon(string $status): string {
    return match ($status) {
        STATUS_TODO => 'fa-circle',
        STATUS_IN_PROGRESS => 'fa-spinner',
        STATUS_BLOCKED => 'fa-ban',
        STATUS_COMPLETED => 'fa-check-circle',
        default => 'fa-circle',
    };
}

/**
 * Sanitize text for CSV output (escape quotes and commas).
 *
 * @param string $value Input value
 * @return string CSV-safe string
 */
function sanitizeCsv(string $value): string {
    // Prevent spreadsheet formula injection: prepend a leading single quote
    // to any cell that begins with a formula-triggering character (=, +, -, @).
    // This neutralizes the cell in Excel/Google Sheets without altering the
    // displayed value (the quote is hidden by the spreadsheet renderer).
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        $value = "'" . $value;
    }

    // Escape quotes by doubling them
    $value = str_replace('"', '""', $value);
    // Wrap in quotes if contains comma, quote, or newline
    if (preg_match('/[",\n]/', $value)) {
        $value = '"' . $value . '"';
    }
    return $value;
}

/**
 * Convert array of data to CSV line.
 *
 * @param array $data Array of field values
 * @return string CSV line
 */
function arrayToCsv(array $data): string {
    $line = [];
    foreach ($data as $field) {
        $line[] = sanitizeCsv($field);
    }
    return implode(',', $line);
}

/**
 * Generate and download CSV file.
 *
 * @param string $filename Filename for download
 * @param array $headers Column headers
 * @param array $rows Array of data rows
 */
function downloadCsv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, $headers);

    // Write data rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Check if request is AJAX (XMLHttpRequest or fetch).
 *
 * @return bool True if AJAX request
 */
function isAjaxRequest(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get base URL of the application.
 *
 * @return string Base URL
 */
function getBaseUrl(): string {
    return rtrim(APP_URL, '/');
}

/**
 * Generate a URL for the application.
 *
 * @param string $path Path relative to base URL
 * @return string Full URL
 */
function url(string $path): string {
    $path = ltrim($path, '/');
    return getBaseUrl() . '/' . $path;
}

/**
 * Check if a date is a workday (Monday-Friday).
 *
 * @param string $date Date in YYYY-MM-DD format
 * @return bool True if workday
 */
function isWorkday(string $date): bool {
    if (empty($date)) {
        return false;
    }

    try {
        $dt = new DateTime($date);
        $dayOfWeek = (int)$dt->format('N'); // 1 = Monday, 7 = Sunday
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Count workdays between two dates (inclusive).
 *
 * @param string $startDate Start date in YYYY-MM-DD format
 * @param string $endDate End date in YYYY-MM-DD format
 * @return int Number of workdays
 */
function countWorkdays(string $startDate, string $endDate): int {
    if (empty($startDate) || empty($endDate)) {
        return 0;
    }

    try {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Make inclusive

        $workdays = 0;
        $current = clone $start;

        while ($current < $end) {
            if (isWorkday($current->format('Y-m-d'))) {
                $workdays++;
            }
            $current->modify('+1 day');
        }

        return $workdays;
    } catch (Exception $e) {
        return 0;
    }
}