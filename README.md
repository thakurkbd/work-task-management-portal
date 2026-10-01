<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('manage_roles');

$pdo = getDbConnection();
$roles = $pdo->query('SELECT r.*, COUNT(rp.permission_id) AS permission_count FROM roles r LEFT JOIN role_permissions rp ON rp.role_id = r.id GROUP BY r.id ORDER BY r.name ASC')->fetchAll();
$permissions = $pdo->query('SELECT * FROM permissions ORDER BY name ASC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Roles & Permissions</h3>
        <p class="text-muted mb-0">Manage access roles and permission allocation</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Role Directory</h5>
                <div class="list-group">
                    <?php foreach ($roles as $role): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold"><?= e($role['name']); ?></div>
                                <small class="text-muted"><?= e($role['slug']); ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= (int) $role['permission_count']; ?> perms</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Available Permissions</h5>
                <div class="row g-2">
                    <?php foreach ($permissions as $permission): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-2 small"><?= e($permission['name']); ?><br><span class="text-muted"><?= e($permission['slug']); ?></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
