<?php
// CSRF protection helpers
// Generates and validates tokens for all state-changing forms.

require_once __DIR__ . '/config.php';

/**
 * Generate an HTML hidden field containing the current CSRF token.
 * Use inside every <form method="POST">.
 *
 * @return string HTML input element
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate the CSRF token from a POST submission.
 * Aborts with a 400 error if the token is missing or invalid.
 */
function validateCsrfPost(): void {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(400);
        die('Invalid CSRF token. Please reload the page and try again.');
    }
}

/**
 * Validate the CSRF token from a GET/URL parameter.
 * Aborts with a 400 error if the token is missing or invalid.
 */
function validateCsrfGet(): void {
    $token = $_GET[CSRF_TOKEN_NAME] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(400);
        die('Invalid CSRF token. Please reload the page and try again.');
    }
}

/**
 * Require a valid CSRF token on the current request.
 * Checks POST first, then GET. Use on any state-changing endpoint.
 */
function requireCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCsrfPost();
    } else {
        validateCsrfGet();
    }
}