<?php
// Setup script - creates the first admin account
// This script should be deleted or secured after use!

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

$pageTitle = 'First Admin Setup';
$errors = [];
$success = false;

// Check if admin already exists
$existingAdmin = Database::fetchOne(
    "SELECT id FROM users WHERE role = :role LIMIT 1",
    [':role' => ROLE_ADMIN]
);

if ($existingAdmin) {
    // Admin already exists - redirect to login
    redirect('login.php', 'info', 'Admin account already exists. Please log in.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $name = validateString($_POST['name'] ?? '', 100, true);
    $email = validateEmail($_POST['email'] ?? '', true);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$name) {
        $errors['name'] = 'Name is required (max 100 characters).';
    }

    if (!$email) {
        $errors['email'] = 'Valid email is required.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Check if email already exists
    if ($email) {
        $existing = Database::fetchOne(
            'SELECT id FROM users WHERE LOWER(email) = LOWER(:email)',
            [':email' => $email]
        );
        if ($existing) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $passwordHash = hashPassword($password);

            $userId = Database::insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => ROLE_ADMIN,
                'is_active' => 1,
            ]);

            $success = true;

            redirect('login.php', 'success', 'Admin account created successfully! You can now log in.');
        } catch (Exception $e) {
            error_log('Admin creation error: ' . $e->getMessage());
            $errors['general'] = 'Failed to create admin account. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-9ndCyUa6Y5k2M+5GEPI9KZ2uJZ6+Zt7+5E5Tq+7g0gK2h6G1G4pJ6K6G6U6G7"
          crossorigin="anonymous">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <nav class="sidebar">
            <div class="px-3 py-2">
                <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
                    <span class="fs-4"><?php echo APP_NAME; ?></span>
                </a>
            </div>
            <hr>
            <div class="px-3">
                <a href="<?php echo url('setup.php'); ?>" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-key me-2"></i>Create Admin Account
                </a>
                <a href="<?php echo url('login.php'); ?>" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        </nav>

        <div class="flex-grow-1">
            <header class="bg-white shadow-sm">
                <div class="container-fluid px-4 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h4 mb-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    </div>
                </div>
            </header>

            <main class="container-fluid px-4 py-3">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>First Admin Account</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if (!$success): ?>
                                    <div class="alert alert-warning mb-4">
                                        <strong>⚠️ Important:</strong> This script creates the first admin account.
                                        After use, <strong>delete or secure this file</strong> (setup.php)
                                        to prevent unauthorized admin creation.
                                    </div>

                                    <?php echo renderFlash(); ?>

                                    <form method="POST" action="" class="csrf-protected novalidate">
                                        <?php echo csrfField(); ?>

                                        <?php if (!empty($errors['general'])): ?>
                                            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?>"
                                                   id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                                   required maxlength="100" autofocus>
                                            <?php if (!empty($errors['name'])): ?>
                                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>"
                                                   id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                                   required maxlength="150">
                                            <?php if (!empty($errors['email'])): ?>
                                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>"
                                                   id="password" name="password" required minlength="8">
                                            <?php if (!empty($errors['password'])): ?>
                                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div>
                                            <?php endif; ?>
                                            <div class="form-text">At least 8 characters.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control <?php echo !empty($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                                   id="confirm_password" name="confirm_password" required>
                                            <?php if (!empty($errors['confirm_password'])): ?>
                                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-user-plus me-2"></i>Create Admin Account
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Admin account created successfully!
                                    </div>
                                    <p>You can now <a href="<?php echo url('login.php'); ?>">log in</a> with your new admin account.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-9ndCyUa6Y5k2M+5GEPI9KZ2uJZ6+Zt7+5E5Tq+7g0gK2h6G1G4pJ6K6G6U6G7"
            crossorigin="anonymous"></script>
</body>
</html>