<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('create_tasks');

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'new';
    $dueDate = $_POST['due_date'] ?? null;
    $estimatedHours = (float) ($_POST['estimated_hours'] ?? 0);
    $assigneeIds = $_POST['assignee_ids'] ?? [];
    $subtasks = trim($_POST['subtasks'] ?? '');

    if ($title === '') {
        flash('danger', 'Task title is required.');
        redirect('tasks.php');
    }

    $taskNumber = 'TASK-' . str_pad((string) ((int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM tasks')->fetchColumn()), 5, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare('INSERT INTO tasks (task_number, title, description, department_id, created_by, priority, status, due_date, estimated_hours, completion_percent) VALUES (:task_number, :title, :description, :department_id, :created_by, :priority, :status, :due_date, :estimated_hours, :completion_percent)');
    $stmt->execute([
        'task_number' => $taskNumber,
        'title' => $title,
        'description' => $description,
        'department_id' => $departmentId > 0 ? $departmentId : null,
        'created_by' => $_SESSION['user_id'],
        'priority' => $priority,
        'status' => $status,
        'due_date' => $dueDate ?: null,
        'estimated_hours' => $estimatedHours,
        'completion_percent' => 0,
    ]);

    $taskId = (int) $pdo->lastInsertId();
    $primaryAssigneeId = null;

    if (!empty($assigneeIds) && is_array($assigneeIds)) {
        foreach ($assigneeIds as $index => $assigneeId) {
            $userId = (int) $assigneeId;
            if ($userId > 0) {
                $markPrimary = $index === 0 ? 1 : 0;
                $pdo->prepare('INSERT INTO task_assignees (task_id, user_id, assigned_by, is_primary) VALUES (:task_id, :user_id, :assigned_by, :is_primary)')->execute([
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'assigned_by' => $_SESSION['user_id'],
                    'is_primary' => $markPrimary,
                ]);

                if ($markPrimary) {
                    $primaryAssigneeId = $userId;
                    $pdo->prepare('UPDATE tasks SET assignee_id = :assignee_id WHERE id = :task_id')->execute([
                        'assignee_id' => $userId,
                        'task_id' => $taskId,
                    ]);
                }
            }
        }
    }

    if ($subtasks !== '') {
        $lines = preg_split('/\r\n|\n|\r/', $subtasks);
        foreach ($lines as $line) {
            $subtaskTitle = trim($line);
            if ($subtaskTitle !== '') {
                $pdo->prepare('INSERT INTO task_subtasks (task_id, title, created_by) VALUES (:task_id, :title, :created_by)')->execute([
                    'task_id' => $taskId,
                    'title' => $subtaskTitle,
                    'created_by' => $_SESSION['user_id'],
                ]);
            }
        }
    }

    $pdo->prepare('INSERT INTO task_status_history (task_id, user_id, old_status, new_status, note) VALUES (:task_id, :user_id, :old_status, :new_status, :note)')->execute([
        'task_id' => $taskId,
        'user_id' => $_SESSION['user_id'],
        'old_status' => null,
        'new_status' => $status,
        'note' => 'Task created',
    ]);

    flash('success', 'Task created successfully.');
    redirect('tasks.php');
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY name ASC')->fetchAll();
$users = $pdo->query('SELECT u.id, u.full_name, u.email FROM users u ORDER BY u.full_name ASC')->fetchAll();
$tasks = $pdo->query('SELECT t.*, u.full_name AS assignee_name, d.name AS department_name FROM tasks t LEFT JOIN users u ON u.id = t.assignee_id LEFT JOIN departments d ON d.id = t.department_id ORDER BY t.created_at DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Task Management</h3>
        <p class="text-muted mb-0">Create, assign, and monitor work tasks</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Create Task</h5>
                <form method="POST" action="tasks.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"></textarea>
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
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="new" selected>New</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="pending_review">Pending Review</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimated Hours</label>
                        <input type="number" step="0.5" min="0" class="form-control" name="estimated_hours" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select class="form-select" name="assignee_ids[]" multiple size="6">
                            <?php foreach ($users as $user): ?>
                                <option value="<?= (int) $user['id']; ?>"><?= e($user['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtasks (one per line)</label>
                        <textarea class="form-control" name="subtasks" rows="5" placeholder="Collect data&#10;Validate report&#10;Prepare final version"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-2"></i>Create Task</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Task List</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Assignee</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($task['title']); ?></div>
                                        <small class="text-muted"><?= e($task['task_number']); ?></small>
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
                                    <td><a href="task-detail.php?id=<?= (int) $task['id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
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
