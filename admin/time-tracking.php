<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('view_calendar');

$pdo = getDbConnection();
$tasks = $pdo->query('SELECT t.*, u.full_name AS assignee_name FROM tasks t LEFT JOIN users u ON u.id = t.assignee_id WHERE t.due_date IS NOT NULL ORDER BY t.due_date ASC, t.created_at DESC')->fetchAll();

$bucket = [];
foreach ($tasks as $task) {
    $bucket[$task['due_date']][] = $task;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Calendar View</h3>
        <p class="text-muted mb-0">Review upcoming commitments and deadlines</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Due Date</th>
                        <th>Task</th>
                        <th>Assignee</th>
                        <th>Status</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No scheduled tasks found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bucket as $date => $dateTasks): ?>
                            <?php foreach ($dateTasks as $task): ?>
                                <tr>
                                    <td><?= e($date); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= e($task['title']); ?></div>
                                        <small class="text-muted"><?= e($task['task_number']); ?></small>
                                    </td>
                                    <td><?= e($task['assignee_name'] ?? 'Unassigned'); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $task['status']))); ?></span></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= match($task['priority']) { 'critical' => 'danger', 'high' => 'warning text-dark', 'low' => 'success', default => 'secondary' }; ?>"><?= e(ucfirst($task['priority'])); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
