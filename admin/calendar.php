<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('view_dashboard');

$pdo = getDbConnection();
$statusOrder = ['new', 'assigned', 'accepted', 'in_progress', 'on_hold', 'pending_review', 'completed', 'approved', 'overdue'];

$tasksByStatus = [];
foreach ($statusOrder as $status) {
    $tasksByStatus[$status] = $pdo->prepare('SELECT t.*, u.full_name AS assignee_name FROM tasks t LEFT JOIN users u ON u.id = t.assignee_id WHERE t.status = :status ORDER BY t.due_date ASC, t.created_at DESC');
    $tasksByStatus[$status]->execute(['status' => $status]);
    $tasksByStatus[$status] = $tasksByStatus[$status]->fetchAll();
}

$taskStatusLabels = [
    'new' => 'New',
    'assigned' => 'Assigned',
    'accepted' => 'Accepted',
    'in_progress' => 'In Progress',
    'on_hold' => 'On Hold',
    'pending_review' => 'Pending Review',
    'completed' => 'Completed',
    'approved' => 'Approved',
    'overdue' => 'Overdue',
];

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Kanban Board</h3>
        <p class="text-muted mb-0">Visual workflow for task execution and delivery</p>
    </div>
</div>

<div class="row g-3 overflow-auto flex-nowrap">
    <?php foreach ($statusOrder as $status): ?>
        <div class="col-xl-2 col-lg-3 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light border-0 fw-bold d-flex justify-content-between align-items-center">
                    <span><?= e($taskStatusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status))); ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= count($tasksByStatus[$status]); ?></span>
                </div>
                <div class="card-body" style="min-height: 420px;">
                    <?php if (empty($tasksByStatus[$status])): ?>
                        <div class="text-muted small text-center mt-4">No tasks</div>
                    <?php else: ?>
                        <?php foreach ($tasksByStatus[$status] as $task): ?>
                            <div class="border rounded p-3 mb-3 bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted"><?= e($task['task_number']); ?></span>
                                    <span class="badge rounded-pill bg-<?= match($task['priority']) { 'critical' => 'danger', 'high' => 'warning text-dark', 'low' => 'success', default => 'secondary' }; ?>">
                                        <?= e(ucfirst($task['priority'])); ?>
                                    </span>
                                </div>
                                <div class="fw-semibold mb-2"><?= e($task['title']); ?></div>
                                <div class="small text-muted mb-2">
                                    <?= $task['due_date'] ? e($task['due_date']) : 'No due date'; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small><?= e($task['assignee_name'] ?? 'Unassigned'); ?></small>
                                    <a href="task-detail.php?id=<?= (int) $task['id']; ?>" class="btn btn-sm btn-outline-primary">Open</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
