<?php
// Page header with HTML structure, CSS, and navigation bar
// Shared across all pages for consistent look and feel.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/validation.php';

$pageTitle = $pageTitle ?? 'Employee Work Tracker';
$user = isset($_SESSION['user_id']) ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo url('assets/css/style.css'); ?>" rel="stylesheet">

    <!-- Custom styles for this template -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
            position: sticky;
            top: 0;
            width: 260px;
            flex-shrink: 0;
        }

        .nav-link {
            color: #495057;
        }

        .nav-link:hover, .nav-link.active {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .empty-state {
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #adb5bd;
        }

        .table thead th {
            border-bottom: 2px solid #dee2e6;
        }

        .badge {
            font-weight: 500;
        }

        .btn {
            font-weight: 500;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Mobile sidebar toggle */
        .sidebar-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: inline-block;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.4);
                z-index: 1040;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="px-3 py-2">
                <a href="<?php echo url(''); ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
                    <span class="fs-4"><?php echo APP_NAME; ?></span>
                </a>
            </div>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a href="<?php echo url('dashboard.php'); ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>

                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a href="<?php echo url('admin/users.php'); ?>" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-users-cog me-2"></i>User Management
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isManager() || isAdmin()): ?>
                        <li class="nav-item">
                            <a href="<?php echo url('manager/team.php'); ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'team.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user-tie me-2"></i>Team Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo url('manager/reports.php'); ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a href="<?php echo url('tasks/index.php'); ?>" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'tasks/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-tasks me-2"></i>Tasks
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?php echo url('daily-updates/index.php'); ?>" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'daily-updates/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt me-2"></i>Daily Updates
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <hr>
            <div class="px-3">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle w-100 text-start" type="button" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            <small class="text-muted d-block">
                                <?php echo ucfirst($_SESSION['user_role'] ?? 'guest'); ?>
                            </small>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                            <li><a class="dropdown-item" href="<?php echo url('dashboard.php'); ?>"><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <header class="bg-white shadow-sm">
                <div class="container-fluid px-4 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button class="btn btn-outline-secondary me-2 sidebar-toggle" onclick="toggleSidebar()">
                                <i class="fas fa-bars"></i>
                            </button>
                            <span class="h4 mb-0"><?php echo htmlspecialchars($pageTitle); ?></span>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <div class="text-muted small">
                                Last activity: <?php echo isset($_SESSION['last_activity']) ? date('g:i A', $_SESSION['last_activity']) : 'N/A'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <main class="container-fluid px-4 py-3">