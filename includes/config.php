<?php
// Configuration file for Employee Work Tracker
// Place in C:\xampp\htdocs\employee-tracker\includes\config.php
//
// Database connection settings for local XAMPP.
// Verify these match your XAMPP setup before first use.

define('DB_HOST', 'localhost');
define('DB_NAME', 'employee_work_tracker');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// PDO connection options for MySQL/MariaDB with PHP 8+
// - ATTR_ERRMODE: Throw exceptions on errors
// - ATTR_DEFAULT_FETCH_MODE: Return associative arrays by default
// - ATTR_EMULATE_PREPARES: Disable for native prepared statements (recommended)
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Application settings
define('APP_NAME', 'Employee Daily Work Update System');
define('APP_URL', 'http://localhost/employee-tracker');
define('TIMEZONE', 'UTC'); // Use UTC consistently; adjust to your local timezone if needed

// Session settings
// Regenerate session ID on login for security
// Session cookie lifetime: 2 hours (adjust as needed)
define('SESSION_LIFETIME', 7200);

// CSRF token settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour in seconds

// Role constants for easy comparison
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_EMPLOYEE', 'employee');

// Status constants
define('STATUS_TODO', 'To Do');
define('STATUS_IN_PROGRESS', 'In Progress');
define('STATUS_BLOCKED', 'Blocked');
define('STATUS_COMPLETED', 'Completed');

// Priority constants
define('PRIORITY_LOW', 'Low');
define('PRIORITY_MEDIUM', 'Medium');
define('PRIORITY_HIGH', 'High');
define('PRIORITY_URGENT', 'Urgent');

// Overdue calculation: a task is overdue when
// - status != 'Completed'
// - due_date < current_date (using application timezone)
// Documented timezone strategy: all due_date comparisons use UTC
// Converted to local time only for display purposes.

// Error reporting: display errors in development only
// In production, set to 0 and log errors separately
if (PHP_SAPI === 'cli') {
    // CLI mode: show all errors for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // Web mode: development shows errors, production hides them
    // Set based on your environment
    ini_set('display_errors', '1'); // Change to '0' for production
    error_reporting(E_ALL);
}
?>