<?php
// Authentication and session management
// Implements secure login, session handling, and authorization checks

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';


session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regenerate']) || (time() - $_SESSION['last_regenerate']) > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = time();
}

/**
 * Hash a password for storage using PHP's password_hash().
 * Default algorithm is bcrypt with cost 10 (adjust as needed).
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against its hash.
 *
 * @param string $password Plain text password
 * @param string $hash Hashed password from database
 * @return bool True if password matches hash
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Attempt to log in a user by email and password.
 *
 * @param string $email User's email address
 * @param string $password Plain text password
 * @return array|false User data on success, false on failure
 */
function loginUser(string $email, string $password): array|false {
    // Input validation
    if (empty($email) || empty($password)) {
        return false;
    }

    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Fetch user by email (case-insensitive search)
    $user = Database::fetchOne(
        'SELECT * FROM users WHERE LOWER(email) = LOWER(:email) AND is_active = 1',
        [':email' => $email]
    );

    if ($user && verifyPassword($password, $user['password_hash'])) {
        // Password verified - set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['manager_id'] = $user['manager_id'];
        $_SESSION['logged_in'] = true;

        // Regenerate session ID after successful login (mitigates session fixation)
        session_regenerate_id(true);

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();

        return $user;
    }

    // Login failed - log attempt securely
    error_log('Failed login attempt for email: ' . $email);
    return false;
}

/**
 * Check if the current user is logged in.
 *
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Require the user to have a specific role or higher.
 * Role hierarchy: admin > manager > employee
 *
 * @param string $requiredRole Role to check against (admin, manager, employee)
 * @throws RuntimeException If user lacks required role
 */
function requireRole(string $requiredRole): void {
    requireLogin();

    $roleHierarchy = [
        ROLE_EMPLOYEE => 0,
        ROLE_MANAGER => 1,
        ROLE_ADMIN => 2,
    ];

    $userRole = $_SESSION['user_role'] ?? ROLE_EMPLOYEE;
    $userLevel = $roleHierarchy[$userRole] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;

    if ($userLevel < $requiredLevel) {
        // User lacks sufficient privileges
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Require the user to be an admin.
 */
function requireAdmin(): void {
    requireRole(ROLE_ADMIN);
}

/**
 * Require the user to be a manager or admin.
 */
function requireManager(): void {
    requireRole(ROLE_MANAGER);
}

/**
 * Check if a user can access a specific resource (task, update, etc.)
 * based on their role and relationships.
 *
 * @param int $userId ID of the user requesting access
 * @param int $resourceOwnerId ID of the user who owns the resource
 * @param string $resourceType Type of resource ('task', 'update', etc.)
 * @return bool True if access is allowed
 */
function canAccessResource(int $userId, int $resourceOwnerId, string $resourceType = 'task'): bool {
    // Admin can access everything
    if ($_SESSION['user_role'] === ROLE_ADMIN) {
        return true;
    }

    // Users can always access their own resources
    if ($userId === $resourceOwnerId) {
        return true;
    }

    // Managers can access resources of their direct reports
    if ($_SESSION['user_role'] === ROLE_MANAGER) {
        $userId = $_SESSION['user_id'];
        // Check if resource owner reports to this user (directly or indirectly)
        $reportsToUser = Database::fetchOne(
            'SELECT 1 FROM users WHERE id = :resourceOwnerId AND manager_id = :userId',
            [':resourceOwnerId' => $resourceOwnerId, ':userId' => $userId]
        );

        return $reportsToUser !== null;
    }

    // Employees can only access their own resources
    return false;
}

/**
 * Log out the current user and destroy the session.
 */
function logoutUser(): void {
    // Unset all session variables
    $_SESSION = [];

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

/**
 * Get the current user's data from session.
 *
 * @return array|null User data or null if not logged in
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
        'manager_id' => $_SESSION['manager_id']
    ];
}

/**
 * Generate a CSRF token and store it in the session.
 *
 * @return string CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate a CSRF token from form submission.
 *
 * @param string $token Token to validate
 * @return bool True if token is valid
 */
function validateCsrfToken(string $token): bool {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }

    // Check token expiry (1 hour)
    if ((time() - ($_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 0)) > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        return false;
    }

    // Use hash_equals for timing-attack safe comparison
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}
?>