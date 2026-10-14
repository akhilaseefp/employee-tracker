<?php
// Shared functions used across multiple pages
// Helper functions for pagination, CSRF, URLs, formatting, and business logic.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';

/**
 * Get a flash message from the session and clear it.
 *
 * @param string $key Flash message key (optional)
 * @return string|false Flash message content or false if none
 */
function getFlash(?string $key = null): string|false {
    if (!isset($_SESSION['flash'])) {
        return false;
    }

    if ($key !== null && isset($_SESSION['flash'][$key])) {
        $content = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        // If no more messages, clear the array
        if (empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }

        return $content;
    }

    // Return all messages if no key specified
    $content = $_SESSION['flash']['message'] ?? null;
    unset($_SESSION['flash']);
    return $content ?? false;
}

/**
 * Set a flash message to be displayed on the next request.
 *
 * @param string $message Message content
 * @param string $type Message type (success, error, warning, info)
 */
function setFlash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
        'timestamp' => time(),
    ];
}

/**
 * Get all flash messages from the session and clear them.
 *
 * @return array All flash messages
 */
function getAllFlash(): array {
    if (!isset($_SESSION['flash'])) {
        return [];
    }

    $messages = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Render flash messages as Bootstrap alert components.
 *
 * Only renders if there are messages available.
 */
function renderFlash(): string {
    $flash = getAllFlash();
    $output = '';

    if (empty($flash)) {
        return $output;
    }

    // Flash is stored as ['message' => '...', 'type' => '...', 'timestamp' => ...]
    $message = $flash['message'] ?? '';
    $type = $flash['type'] ?? 'info';

    if (empty($message)) {
        return $output;
    }

    // Map 'error' type to Bootstrap's 'danger' class
    $bsType = ($type === 'error') ? 'danger' : $type;

    $output .= '<div class="alert alert-' . htmlspecialchars($bsType) . ' alert-dismissible fade show mb-3">' . "\n";
    $output .= '    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . "\n";
    $output .= '    <strong>' . htmlspecialchars(ucfirst($type)) . ':</strong> ' . htmlspecialchars($message) . "\n";
    $output .= '</div>' . "\n";

    return $output;
}

/**
 * Redirect to another page with a flash message.
 *
 * @param string $target Target URL or page name
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function redirect(string $target, string $type = 'info', string $message = ''): void {
    if (!empty($message)) {
        setFlash($message, $type);
    }
    header('Location: ' . url($target));
    exit;
}

/**
 * Check if current user is an admin.
 *
 * @return bool True if admin
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN;
}

/**
 * Check if current user is a manager or admin.
 *
 * @return bool True if manager or admin
 */
function isManager(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === ROLE_MANAGER || $role === ROLE_ADMIN;
}

/**
 * Check if current user is an employee.
 *
 * @return bool True if employee
 */
function isEmployee(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_EMPLOYEE;
}

/**
 * Get the formatted date display showing the day name.
 *
 * @param string $date Date in YYYY-MM-DD format
 * @return string Formatted date with day name
 */
function formatDateWithDay(string $date): string {
    if (empty($date)) {
        return '';
    }

    try {
        $dt = new DateTime($date);
        $dayName = ucfirst($dt->format('l')); // Full day name
        $datePart = $dt->format('m/d/Y');
        return "$dayName, $datePart";
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Get employee's full name with role abbreviation.
 *
 * @param int $userId User ID
 * @return string Name display
 */
function getEmployeeDisplayName(int $userId): string {
    $user = Database::fetchOne(
        'SELECT name, role FROM users WHERE id = :id',
        [':id' => $userId]
    );

    if (!$user) {
        return 'Unknown';
    }

    $roleAbbr = match ($user['role']) {
        ROLE_ADMIN => 'Admin',
        ROLE_MANAGER => 'Mgr',
        ROLE_EMPLOYEE => 'Emp',
        default => '',
    };

    return $roleAbbr !== ''
        ? $user['name'] . ' (' . $roleAbbr . ')'
        : $user['name'];
}

/**
 * Generate a breadcrumb trail for the current page.
 *
 * @param array $crumbs Array of ['label' => 'url'] pairs
 * @return string HTML breadcrumb
 */
function generateBrums(array $crumbs): string {
    $output = '<nav aria-label="breadcrumb">' . "\n";
    $output .= '    <ol class="breadcrumb mb-3">' . "\n";

    foreach ($crumbs as $index => $crumb) {
        $label = $crumb['label'] ?? '';
        $url = $crumb['url'] ?? '#';

        if ($index === count($crumbs) - 1) {
            // Last item: active
            $output .= '        <li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($label) . '</li>' . "\n";
        } else {
            $output .= '        <li class="breadcrumb-item"><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a></li>' . "\n";
        }
    }

    $output .= '    </ol>' . "\n";
    $output .= '</nav>' . "\n";

    return $output;
}

/**
 * Count workdays in a date range for performance calculations.
 *
 * @param string $startDate Start date YYYY-MM-DD
 * @param string $endDate End date YYYY-MM-DD
 * @return int Number of workdays (excludes weekends)
 */
function countWorkdaysInRange(string $startDate, string $endDate): int {
    if (empty($startDate) || empty($endDate)) {
        return 0;
    }

    try {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Make inclusive

        $count = 0;
        $current = clone $start;

        while ($current < $end) {
            if (isWorkday($current->format('Y-m-d'))) {
                $count++;
            }
            $current->modify('+1 day');
        }

        return $count;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get a user's name from the database.
 *
 * @param int $userId User ID
 * @return string User name or 'Unknown'
 */
function getUserName(int $userId): string {
    $user = Database::fetchOne(
        'SELECT name FROM users WHERE id = :id AND is_active = 1',
        [':id' => $userId]
    );

    return $user ? htmlspecialchars($user['name']) : 'Unknown';
}