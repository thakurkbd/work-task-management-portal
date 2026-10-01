<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('manage_departments');

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        flash('danger', 'Department name is required.');
        redirect('departments.php');
    }

    $stmt = $pdo->prepare('INSERT INTO departments (name, code, status) VALUES (:name, :code, :status)');
    $stmt->execute([
        'name' => $name,
        'code' => $code,
        'status' => $status,
    ]);

    flash('success', 'Department created successfully.');
    redirect('departments.php');
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Department Management</h3>
        <p class="text-muted mb-0">Create and organize departments across the organization</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Add Department</h5>
                <form method="POST" action="departments.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Code</label>
                        <input type="text" name="code" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-save me-2"></i>Save Department</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Department List</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td><?= e($department['name']); ?></td>
                                    <td><?= e($department['code'] ?? '—'); ?></td>
                                    <td><span class="badge bg-<?= $department['status'] === 'active' ? 'success' : 'secondary'; ?> rounded-pill"><?= e(ucfirst($department['status'])); ?></span></td>
                                    <td><?= e($department['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
