<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('manage_departments');

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $userId = (int) ($_POST['user_id'] ?? 0);
    $employeeId = trim($_POST['employee_id'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $managerId = (int) ($_POST['manager_id'] ?? 0);
    $joiningDate = $_POST['joining_date'] ?? null;
    $status = $_POST['status'] ?? 'active';

    if ($userId <= 0 || $employeeId === '') {
        flash('danger', 'Employee record requires a valid user and employee ID.');
        redirect('employees.php');
    }

    $stmt = $pdo->prepare('INSERT INTO employees (user_id, employee_id, designation, department_id, manager_id, joining_date, status) VALUES (:user_id, :employee_id, :designation, :department_id, :manager_id, :joining_date, :status)');
    $stmt->execute([
        'user_id' => $userId,
        'employee_id' => $employeeId,
        'designation' => $designation,
        'department_id' => $departmentId > 0 ? $departmentId : null,
        'manager_id' => $managerId > 0 ? $managerId : null,
        'joining_date' => $joiningDate ?: null,
        'status' => $status,
    ]);

    flash('success', 'Employee profile created successfully.');
    redirect('employees.php');
}

$users = $pdo->query('SELECT u.id, u.full_name, u.email FROM users u ORDER BY u.full_name ASC')->fetchAll();
$departments = $pdo->query('SELECT * FROM departments ORDER BY name ASC')->fetchAll();
$employees = $pdo->query('SELECT e.*, u.full_name AS user_name, d.name AS department_name FROM employees e LEFT JOIN users u ON u.id = e.user_id LEFT JOIN departments d ON d.id = e.department_id ORDER BY e.created_at DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Employee Management</h3>
        <p class="text-muted mb-0">Manage employee details, assignments, and departments</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Create Employee Profile</h5>
                <form method="POST" action="employees.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Select user</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= (int) $user['id']; ?>"><?= e($user['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employee ID</label>
                        <input type="text" class="form-control" name="employee_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation</label>
                        <input type="text" class="form-control" name="designation">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department_id">
                            <option value="">Select department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= (int) $department['id']; ?>"><?= e($department['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Joining Date</label>
                        <input type="date" class="form-control" name="joining_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-save me-2"></i>Save Employee</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Employee List</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>User</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?= e($employee['employee_id']); ?></td>
                                    <td><?= e($employee['user_name'] ?? '—'); ?></td>
                                    <td><?= e($employee['designation'] ?? '—'); ?></td>
                                    <td><?= e($employee['department_name'] ?? '—'); ?></td>
                                    <td><span class="badge bg-<?= $employee['status'] === 'active' ? 'success' : 'secondary'; ?> rounded-pill"><?= e(ucfirst($employee['status'])); ?></span></td>
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
