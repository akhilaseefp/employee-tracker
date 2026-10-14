<?php
// Login page with form

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$pageTitle = 'Login';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCsrf();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Attempt to login
    $user = loginUser($email, $password);

    if ($user) {
        // Redirect to intended page if set
        if (!empty($_SESSION['redirect_after_login'])) {
            $redirectUrl = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Login successful - redirect based on role
        switch ($user['role']) {
            case ROLE_ADMIN:
                redirect('admin/users.php', 'success', 'Welcome, ' . htmlspecialchars($user['name']) . '!');
            case ROLE_MANAGER:
            case ROLE_EMPLOYEE:
            default:
                redirect('dashboard.php', 'success', 'Welcome, ' . htmlspecialchars($user['name']) . '!');
        }
    } else {
        // Login failed - show error if not already set (e.g. deactivated account message)
        if (empty($_SESSION['flash'])) {
            setFlash('Invalid email or password. Please try again.', 'error');
        }
        redirect('login.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo url('assets/css/style.css'); ?>" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="col-md-5 col-lg-4 px-3">
            <div class="text-center mb-4">
                <h3><i class="fas fa-clipboard-check me-2 text-primary"></i><?php echo APP_NAME; ?></h3>
                <p class="text-muted">Sign in to access your dashboard</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Sign In</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" novalidate>
                        <?php echo csrfField(); ?>

                        <?php echo renderFlash(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text small">
                                Demo accounts:
                                <br>
                                <code>Admin : admin@local.test / password123</code>
                                <br>
                                <code>Manager : manager@local.test / password123</code>
                                <br>
                                <code>Employee : employee1@local.test / password123</code>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                            <a href="<?php echo url('setup.php'); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-key me-2"></i>Setup
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>