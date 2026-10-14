<?php
// Admin users management page

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/validation.php';

requireLogin();
requireAdmin();

$pageTitle = 'User Management';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['role'];

// Get all users
$users = Database::fetchAll(
    "SELECT u.*, m.name as manager_name
     FROM users u
     LEFT JOIN users m ON u.manager_id = m.id
     ORDER BY u.role, u.name"
);

// Get all managers for dropdown
$managers = Database::fetchAll(
    "SELECT id, name, email FROM users
     WHERE role IN ('admin', 'manager') AND is_active = 1
     ORDER BY name"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog me-2"></i>User Management</h2>
</div>

<?php echo renderFlash(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Users</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="empty-state p-4">
                <i class="fas fa-users"></i>
                <h5>No users found</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%">Name</th>
                            <th style="width: 20%">Email</th>
                            <th style="width: 12%">Role</th>
                            <th style="width: 15%">Manager</th>
                            <th style="width: 12%">Status</th>
                            <th style="width: 12%">Created</th>
                            <th style="width: 9%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                    <?php if ($u['id'] === $userId): ?>
                                        <span class="badge bg-primary ms-2">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $u['role'] === ROLE_ADMIN ? 'danger' : ($u['role'] === ROLE_MANAGER ? 'warning text-dark' : 'info');
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $u['manager_name'] ? htmlspecialchars($u['manager_name']) : '<span class="text-muted">—</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($u['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url('admin/users/edit.php?user_id=' . $u['id']); ?>"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <?php if ($u['id'] !== $userId): ?>
                                            <form method="POST" action="<?php echo url('admin/users/toggle.php'); ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to <?php echo $u['is_active'] ? 'deactivate' : 'activate'; ?> this user?');">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <?php echo csrfField(); ?>
                                                <button type="submit" class="btn btn-outline-<?php echo $u['is_active'] ? 'danger' : 'success'; ?>" title="<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $u['is_active'] ? 'user-slash' : 'user-check'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">(Cannot change own status)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>