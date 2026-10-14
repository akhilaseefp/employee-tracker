<?php
// Admin user edit page

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

requireLogin();
requireAdmin();

$pageTitle = 'Edit User';
$currentUser = getCurrentUser();
$currentUserId = $currentUser['id'];

// Get user ID to edit
$userId = validateInt($_GET['user_id'] ?? 0, 1) ?? 0;
if ($userId < 1) {
    redirect('admin/users.php', 'error', 'Invalid user ID.');
}

// Fetch the user
$user = Database::fetchOne(
    "SELECT * FROM users WHERE id = :id",
    [':id' => $userId]
);

if (!$user) {
    redirect('admin/users.php', 'error', 'User not found.');
}

// Prevent editing yourself for certain fields (like making yourself inactive or changing your own role if it would lock you out)
$isEditingSelf = ($userId === $currentUserId);

// Get all managers for dropdown (excluding the user being edited if preventing self-assignment as manager)
$managers = Database::fetchAll(
    "SELECT id, name FROM users
     WHERE role IN ('admin', 'manager') AND is_active = 1"
     . ($isEditingSelf && $user['role'] === ROLE_EMPLOYEE ? "" : " AND id != :userId"),
    $isEditingSelf && $user['role'] === ROLE_EMPLOYEE ? [] : [':userId' => $userId]
);

$errors = [];
$old = [
    'name' => $user['name'] ?? '',
    'email' => $user['email'] ?? '',
    'role' => $user['role'] ?? ROLE_EMPLOYEE,
    'manager_id' => $user['manager_id'] ?? '',
    'is_active' => $user['is_active'] ?? 1,
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // Only allow self-edit for basic info (name, email) - not role/status changes that could lock out
    $canEditBasic = true;
    $canEditRole = !$isEditingSelf; // Only others can edit role/manager/status

    $old['name'] = validateString($_POST['name'] ?? '', 100, true) ?? '';
    $old['email'] = validateEmail($_POST['email'] ?? '', true) ?? '';

    if ($canEditRole) {
        $old['role'] = validateRole($_POST['role'] ?? $user['role']) ?? $user['role'];
        $old['manager_id'] = validateInt($_POST['manager_id'] ?? null) ?? null;
        $old['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    } else {
        // Keep current values for role/manager/status when editing self
        $old['role'] = $user['role'];
        $old['manager_id'] = $user['manager_id'];
        $old['is_active'] = $user['is_active'];
    }

    // Validation
    if (!$old['name']) {
        $errors['name'] = 'Name is required (max 100 characters).';
    }

    if (!$old['email']) {
        $errors['email'] = 'Valid email is required.';
    }

    // Check email uniqueness (excluding current user)
    if ($old['email']) {
        $emailExists = Database::fetchOne(
            'SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id',
            [':email' => $old['email'], ':id' => $userId]
        );
        if ($emailExists) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    // Additional validation for role changes
    if ($canEditRole && $old['role'] === ROLE_EMPLOYEE && empty($old['manager_id'])) {
        $errors['manager_id'] = 'Employees must have a manager assigned.';
    }

    // Prevent making yourself inactive or removing your own admin privileges
    if ($isEditingSelf) {
        if ($old['is_active'] === 0) {
            $errors['is_active'] = 'You cannot deactivate your own account.';
        }
        if ($old['role'] !== ROLE_ADMIN) {
            $errors['role'] = 'You cannot change your own role away from admin.';
        }
    }

    if (empty($errors)) {
        try {
            Database::beginTransaction();

            Database::update('users', [
                'name' => $old['name'],
                'email' => $old['email'],
                'role' => $old['role'],
                'manager_id' => $old['manager_id'],
                'is_active' => $old['is_active'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id', $userId);

            Database::commit();

            redirect('admin/users.php', 'success', 'User updated successfully.');
        } catch (Exception $e) {
            Database::rollBack();
            error_log('User edit error: ' . $e->getMessage());
            $errors['general'] = 'Failed to update user. Please try again.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-edit me-2"></i>Edit User</h2>
    <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Users
    </a>
</div>

<?php echo renderFlash(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Edit User: <?php echo htmlspecialchars($user['name']); ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="csrf-protected" novalidate>
            <?php echo csrfField(); ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?>"
                       id="name" name="name" value="<?php echo htmlspecialchars($old['name']); ?>"
                       required maxlength="100">
                <?php if (!empty($errors['name'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>"
                       id="email" name="email" value="<?php echo htmlspecialchars($old['email']); ?>"
                       required maxlength="150">
                <?php if (!empty($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <?php if (!$isEditingSelf): ?>
                <div class="mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo !empty($errors['role']) ? 'is-invalid' : ''; ?>"
                            id="role" name="role" required>
                        <option value="">Select a role...</option>
                        <option value="<?php echo ROLE_ADMIN; ?>"
                            <?php echo $old['role'] === ROLE_ADMIN ? 'selected' : ''; ?>>
                            Administrator
                        </option>
                        <option value="<?php echo ROLE_MANAGER; ?>"
                            <?php echo $old['role'] === ROLE_MANAGER ? 'selected' : ''; ?>>
                            Manager
                        </option>
                        <option value="<?php echo ROLE_EMPLOYEE; ?>"
                            <?php echo $old['role'] === ROLE_EMPLOYEE ? 'selected' : ''; ?>>
                            Employee
                        </option>
                    </select>
                    <?php if (!empty($errors['role'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['role']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="manager_id" class="form-label">Manager</label>
                    <select class="form-select" id="manager_id" name="manager_id">
                        <option value="">None (Admin/Manager)</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['id']; ?>"
                                <?php echo (!empty($old['manager_id']) && $old['manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manager['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Required for employees. Optional for managers/admins.
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                           value="1" <?php echo $old['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">
                        Account is Active
                    </label>
                    <?php if ($isEditingSelf): ?>
                        <div class="form-text text-muted">You cannot deactivate your own account.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> You are editing your own profile. Role, manager, and account status cannot be changed for security reasons.
                </div>
                <!-- Display current values as disabled/readonly -->
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($old['name']); ?>" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($old['email']); ?>" required maxlength="150">
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control-plaintext" value="<?php echo ucfirst($old['role']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Manager</label>
                    <?php if (!empty($old['manager_id'])): ?>
                        <?php
                        $manager = Database::fetchOne(
                            'SELECT name FROM users WHERE id = :id',
                            [':id' => $old['manager_id']]
                        );
                        echo '<input type="text" class="form-control-plaintext" value="' . htmlspecialchars($manager['name'] ?? 'Unknown') . '" readonly>';
                        ?>
                    <?php else: ?>
                        <input type="text" class="form-control-plaintext" value="None" readonly>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                           <?php echo $old['is_active'] ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="is_active">
                        Account is Active
                    </label>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>