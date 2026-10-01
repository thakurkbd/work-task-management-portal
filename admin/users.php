<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('view_dashboard');

$pdo = getDbConnection();
$stats = [
    'employees' => (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
    'departments' => (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    'total_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),
    'pending_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE status IN ("new","assigned","accepted","in_progress","on_hold","pending_review")')->fetchColumn(),
    'completed_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE status IN ("completed","approved")')->fetchColumn(),
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
];

$recentUsers = $pdo->query('SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id ORDER BY u.created_at DESC LIMIT 5')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Admin Dashboard</h3>
        <p class="text-muted mb-0">Central management overview</p>
    </div>
    <div class="btn-group">
        <a href="users.php" class="btn btn-primary"><i class="fa-solid fa-user-plus me-2"></i>Add User</a>
        <a href="employees.php" class="btn btn-outline-primary"><i class="fa-solid fa-user-tie me-2"></i>Add Employee</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Employees</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['employees']); ?></h3>
                </div>
                <div class="icon-box bg-primary"><i class="fa-solid fa-user-tie"></i></div>
            </div>
            <p class="text-muted mb-0">Total employee profiles</p>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Departments</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['departments']); ?></h3>
                </div>
                <div class="icon-box bg-success"><i class="fa-solid fa-building"></i></div>
            </div>
            <p class="text-muted mb-0">Organization departments</p>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Users</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['users']); ?></h3>
                </div>
                <div class="icon-box bg-warning"><i class="fa-solid fa-users"></i></div>
            </div>
            <p class="text-muted mb-0">Portal user accounts</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Recent User Accounts</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?= e($user['full_name']); ?></td>
                                    <td><?= e($user['email']); ?></td>
                                    <td><?= e($user['role_name'] ?? '—'); ?></td>
                                    <td><span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary'; ?> rounded-pill"><?= e(ucfirst($user['status'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Management Areas</h5>
                <div class="d-grid gap-2">
                    <a href="users.php" class="btn btn-outline-primary text-start"><i class="fa-solid fa-user me-2"></i>User Management</a>
                    <a href="employees.php" class="btn btn-outline-success text-start"><i class="fa-solid fa-user-tie me-2"></i>Employee Management</a>
                    <a href="departments.php" class="btn btn-outline-warning text-start"><i class="fa-solid fa-building me-2"></i>Department Management</a>
                    <a href="roles.php" class="btn btn-outline-info text-start"><i class="fa-solid fa-shield-halved me-2"></i>Role & Permission Management</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
