<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$pdo = getDbConnection();
$stats = [
    'employees' => (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
    'active_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
    'total_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),
    'pending_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE status IN ("new","assigned","accepted","in_progress","on_hold","pending_review")')->fetchColumn(),
    'completed_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE status IN ("completed","approved")')->fetchColumn(),
    'overdue_tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status NOT IN ("completed","approved","cancelled")')->fetchColumn(),
];

$recentTasks = $pdo->query('SELECT t.*, u.full_name AS assignee_name FROM tasks t LEFT JOIN users u ON u.id = t.assignee_id ORDER BY t.created_at DESC LIMIT 5')->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Dashboard Overview</h3>
        <p class="text-muted mb-0">Welcome, <?= e($_SESSION['user_name'] ?? 'User'); ?></p>
    </div>
    <a href="#" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Create Task</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Employees</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['employees']); ?></h3>
                </div>
                <div class="icon-box bg-primary"><i class="fa-solid fa-users"></i></div>
            </div>
            <p class="text-muted mb-0">Registered employees</p>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Active Users</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['active_users']); ?></h3>
                </div>
                <div class="icon-box bg-success"><i class="fa-solid fa-user-check"></i></div>
            </div>
            <p class="text-muted mb-0">Currently active users</p>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Pending</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['pending_tasks']); ?></h3>
                </div>
                <div class="icon-box bg-warning"><i class="fa-solid fa-clock"></i></div>
            </div>
            <p class="text-muted mb-0">Tasks in progress or pending review</p>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small">Overdue</div>
                    <h3 class="fw-bold mt-1 mb-0"><?= e((string) $stats['overdue_tasks']); ?></h3>
                </div>
                <div class="icon-box bg-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <p class="text-muted mb-0">Work items past due date</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Recent Tasks</h5>
                    <a href="#" class="btn btn-link btn-sm text-decoration-none">View all</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Assigned</th>
                                <th>Status</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($task['title']); ?></div>
                                        <small class="text-muted">#<?= e($task['task_number']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                            $priorityClass = match ($task['priority']) {
                                                'critical' => 'bg-danger',
                                                'high' => 'bg-warning text-dark',
                                                'low' => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        ?>
                                        <span class="badge <?= $priorityClass ?> rounded-pill"><?= e(ucfirst($task['priority'])); ?></span>
                                    </td>
                                    <td><?= e($task['assignee_name'] ?? 'Unassigned'); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $task['status']))); ?></span></td>
                                    <td><?= $task['due_date'] ? e($task['due_date']) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-outline-primary text-start"><i class="fa-solid fa-plus me-2"></i>Create Task</a>
                    <a href="#" class="btn btn-outline-success text-start"><i class="fa-solid fa-users me-2"></i>Assign Task</a>
                    <a href="#" class="btn btn-outline-warning text-start"><i class="fa-solid fa-calendar-days me-2"></i>Schedule</a>
                    <a href="#" class="btn btn-outline-info text-start"><i class="fa-solid fa-chart-pie me-2"></i>Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
