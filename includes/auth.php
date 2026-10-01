<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('view_dashboard');

$pdo = getDbConnection();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('danger', 'Task not found.');
    redirect('tasks.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'comment' && userHasPermission('comment_on_tasks')) {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment !== '') {
            $pdo->prepare('INSERT INTO task_comments (task_id, user_id, comment) VALUES (:task_id, :user_id, :comment)')->execute([
                'task_id' => $id,
                'user_id' => $_SESSION['user_id'],
                'comment' => $comment,
            ]);
            flash('success', 'Comment added successfully.');
        }
    }

    if ($action === 'subtask' && userHasPermission('create_tasks')) {
        $title = trim($_POST['subtask_title'] ?? '');
        if ($title !== '') {
            $pdo->prepare('INSERT INTO task_subtasks (task_id, title, created_by) VALUES (:task_id, :title, :created_by)')->execute([
                'task_id' => $id,
                'title' => $title,
                'created_by' => $_SESSION['user_id'],
            ]);
            flash('success', 'Subtask added successfully.');
        }
    }

    if ($action === 'update_task' && userHasPermission('update_tasks')) {
        $status = $_POST['status'] ?? 'new';
        $progress = (int) ($_POST['completion_percent'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        $taskBefore = $pdo->prepare('SELECT status FROM tasks WHERE id = :id LIMIT 1');
        $taskBefore->execute(['id' => $id]);
        $oldStatus = $taskBefore->fetchColumn();

        $pdo->prepare('UPDATE tasks SET status = :status, completion_percent = :completion_percent, remarks = :remarks WHERE id = :id')->execute([
            'status' => $status,
            'completion_percent' => max(0, min(100, $progress)),
            'remarks' => $remarks,
            'id' => $id,
        ]);

        if ($oldStatus !== $status) {
            $pdo->prepare('INSERT INTO task_status_history (task_id, user_id, old_status, new_status, note) VALUES (:task_id, :user_id, :old_status, :new_status, :note)')->execute([
                'task_id' => $id,
                'user_id' => $_SESSION['user_id'],
                'old_status' => $oldStatus,
                'new_status' => $status,
                'note' => 'Status updated via task detail page',
            ]);
        }

        flash('success', 'Task updated successfully.');
    }

    if ($action === 'upload' && userHasPermission('upload_files')) {
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                flash('danger', 'Unsupported file type.');
                redirect('task-detail.php?id=' . $id);
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                flash('danger', 'File exceeds 5MB upload limit.');
                redirect('task-detail.php?id=' . $id);
            }

            $safeName = 'task_' . $id . '_' . time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file['name']));
            $targetDir = ROOT_PATH . '/uploads/tasks';
            $targetPath = $targetDir . '/' . $safeName;

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $pdo->prepare('INSERT INTO task_attachments (task_id, uploaded_by, file_name, original_name, mime_type, file_size, file_path) VALUES (:task_id, :uploaded_by, :file_name, :original_name, :mime_type, :file_size, :file_path)')->execute([
                    'task_id' => $id,
                    'uploaded_by' => $_SESSION['user_id'],
                    'file_name' => $safeName,
                    'original_name' => basename($file['name']),
                    'mime_type' => mime_content_type($targetPath) ?: 'application/octet-stream',
                    'file_size' => $file['size'],
                    'file_path' => '/uploads/tasks/' . $safeName,
                ]);
                flash('success', 'Attachment uploaded successfully.');
            }
        }
    }

    redirect('task-detail.php?id=' . $id);
}

$task = $pdo->prepare('SELECT t.*, u.full_name AS created_by_name, a.full_name AS assignee_name, d.name AS department_name FROM tasks t LEFT JOIN users u ON u.id = t.created_by LEFT JOIN users a ON a.id = t.assignee_id LEFT JOIN departments d ON d.id = t.department_id WHERE t.id = :id LIMIT 1');
$task->execute(['id' => $id]);
$task = $task->fetch();

if (!$task) {
    flash('danger', 'Task not found.');
    redirect('tasks.php');
}

$assignees = $pdo->prepare('SELECT ta.*, u.full_name FROM task_assignees ta LEFT JOIN users u ON u.id = ta.user_id WHERE ta.task_id = :task_id ORDER BY ta.is_primary DESC, u.full_name ASC');
$assignees->execute(['task_id' => $id]);
$assignees = $assignees->fetchAll();

$subtasks = $pdo->prepare('SELECT * FROM task_subtasks WHERE task_id = :task_id ORDER BY created_at ASC');
$subtasks->execute(['task_id' => $id]);
$subtasks = $subtasks->fetchAll();

$comments = $pdo->prepare('SELECT tc.*, u.full_name FROM task_comments tc LEFT JOIN users u ON u.id = tc.user_id WHERE tc.task_id = :task_id ORDER BY tc.created_at DESC');
$comments->execute(['task_id' => $id]);
$comments = $comments->fetchAll();

$attachments = $pdo->prepare('SELECT ta.*, u.full_name FROM task_attachments ta LEFT JOIN users u ON u.id = ta.uploaded_by WHERE ta.task_id = :task_id ORDER BY ta.created_at DESC');
$attachments->execute(['task_id' => $id]);
$attachments = $attachments->fetchAll();

$history = $pdo->prepare('SELECT th.*, u.full_name FROM task_status_history th LEFT JOIN users u ON u.id = th.user_id WHERE th.task_id = :task_id ORDER BY th.created_at DESC LIMIT 10');
$history->execute(['task_id' => $id]);
$history = $history->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1"><?= e($task['title']); ?></h3>
        <p class="text-muted mb-0"><?= e($task['task_number']); ?> • Created by <?= e($task['created_by_name'] ?? 'System'); ?></p>
    </div>
    <a href="tasks.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Back to Tasks</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Task Overview</h5>
                    <span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $task['status']))); ?></span>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Progress</span>
                        <span><?= (int) $task['completion_percent']; ?>%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= (int) $task['completion_percent']; ?>%;"></div>
                    </div>
                </div>

                <p><?= nl2br(e($task['description'] ?? 'No description provided.')); ?></p>

                <div class="row g-3 mt-2">
                    <div class="col-md-6"><strong>Department:</strong> <?= e($task['department_name'] ?? '—'); ?></div>
                    <div class="col-md-6"><strong>Priority:</strong> <?= e(ucfirst($task['priority'])); ?></div>
                    <div class="col-md-6"><strong>Due Date:</strong> <?= e($task['due_date'] ?? '—'); ?></div>
                    <div class="col-md-6"><strong>Estimated Hours:</strong> <?= e((string) $task['estimated_hours']); ?></div>
                    <div class="col-md-6"><strong>Actual Hours:</strong> <?= e((string) $task['actual_hours']); ?></div>
                    <div class="col-md-6"><strong>Created:</strong> <?= e($task['created_at']); ?></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Subtasks</h5>
                <form method="POST" action="task-detail.php?id=<?= (int) $task['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="subtask">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="subtask_title" placeholder="Add a subtask..." required>
                        <button class="btn btn-primary" type="submit">Add</button>
                    </div>
                </form>

                <div class="list-group">
                    <?php foreach ($subtasks as $subtask): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold"><?= e($subtask['title']); ?></div>
                                <small class="text-muted"><?= e($subtask['status']); ?></small>
                            </div>
                            <span class="badge bg-<?= $subtask['status'] === 'completed' ? 'success' : 'secondary'; ?> rounded-pill"><?= e(ucfirst($subtask['status'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Comments</h5>
                <form method="POST" action="task-detail.php?id=<?= (int) $task['id']; ?>" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="comment">
                    <textarea class="form-control mb-2" name="comment" rows="3" placeholder="Add a comment..." required></textarea>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>

                <?php foreach ($comments as $comment): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong><?= e($comment['full_name'] ?? 'User'); ?></strong>
                            <small class="text-muted"><?= e($comment['created_at']); ?></small>
                        </div>
                        <p class="mb-0 mt-2"><?= nl2br(e($comment['comment'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Update Task</h5>
                <form method="POST" action="task-detail.php?id=<?= (int) $task['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="update_task">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="new" <?= $task['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="assigned" <?= $task['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                            <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="pending_review" <?= $task['status'] === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="approved" <?= $task['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Completion %</label>
                        <input type="number" class="form-control" name="completion_percent" min="0" max="100" value="<?= (int) $task['completion_percent']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="4"><?= e($task['remarks'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Assignees</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($assignees as $assignee): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span><?= e($assignee['full_name']); ?></span>
                            <?php if (!empty($assignee['is_primary'])): ?><span class="badge bg-primary rounded-pill">Primary</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Attachments</h5>
                <form method="POST" action="task-detail.php?id=<?= (int) $task['id']; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="upload">
                    <input type="file" class="form-control mb-2" name="attachment" required>
                    <button type="submit" class="btn btn-outline-success w-100">Upload Attachment</button>
                </form>

                <div class="mt-3">
                    <?php foreach ($attachments as $attachment): ?>
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?= e($attachment['original_name']); ?></span>
                                <a href="<?= e($attachment['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Status History</h5>
                <?php foreach ($history as $item): ?>
                    <div class="small text-muted border-bottom pb-2 mb-2">
                        <div><strong><?= e($item['full_name'] ?? 'User'); ?></strong> changed from <strong><?= e($item['old_status'] ?? '—'); ?></strong> to <strong><?= e($item['new_status']); ?></strong></div>
                        <div><?= e($item['created_at']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
