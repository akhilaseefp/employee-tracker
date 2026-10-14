<?php
// Daily updates my updates page - redirects to index with employee filter
// But also provides a dedicated page showing employee's own updates

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireRole(ROLE_EMPLOYEE); // Only employees

$pageTitle = 'My Updates';
$user = getCurrentUser();
$userId = $user['id'];
$userName = $user['name'];

// Simply redirect to index with employee filter
// This page exists for dedicated link access
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Updates - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-9ndCyUa6Y5k2M+5GEPI9KZ2uJZ6+Zt7+5E5Tq+7g0gK2h6G1G4pJ6K6G6U6G7"
          crossorigin="anonymous">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/includes/header.php'; ?>

        <div class="flex-grow-1">
            <main class="container-fluid px-4 py-3">
                <div class="row justify-content-center min-vh-100">
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>My Updates</h5>
                            </div>
                            <div class="card-body p-4 text-center">
                                <p class="lead">
                                    Your daily updates will appear here.
                                    <br>Redirecting to main updates list...
                                </p>
                                <p class="text-muted small">
                                    <?= formatDateTime(time()); ?>
                                </p>
                                <hr>
                                <p>
                                    <a href="<?php echo url('daily-updates/index.php'); ?>"
                                       class="btn btn-primary w-100">
                                        <i class="fas fa-calendar-alt me-2"></i>View All My Updates
                                    </a>
                                </p>
                                <p>
                                    <a href="<?php echo url('daily-updates/create.php'); ?>"
                                       class="btn btn-outline-secondary w-100 mt-2">
                                        <i class="fas fa-plus me-2"></i>Submit New Update
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include __DIR__ . '/includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-9ndCyUa6Y5k2M+5GEPI9KZ2uJZ6+Zt7+5E5Tq+7g0gK2h6G1G4pJ6K6G6U6G7"
            crossorigin="anonymous"></script>
</body>
</html>