<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requirePermission('track_time');

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $taskId = (int) ($_POST['task_id'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? date('Y-m-d');
    $hours = (float) ($_POST['hours'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($taskId <= 0 || $hours <= 0) {
        flash('danger', 'A valid task and positive hours are required.');
        redirect('time-tracking.php');
    }

    $pdo->prepare('INSERT INTO time_entries (task_id, user_id, entry_date, hours, note) VALUES (:task_id, :user_id, :entry_date, :hours, :note)')->execute([
        'task_id' => $taskId,
        'user_id' => $_SESSION['user_id'],
        'entry_date' => $entryDate,
        'hours' => $hours,
        'note' => $note,
    ]);

    $pdo->prepare('UPDATE tasks SET actual_hours = COALESCE(actual_hours, 0) + :hours WHERE id = :task_id')->execute([
        'hours' => $hours,
        'task_id' => $taskId,
    ]);

    flash('success', 'Time entry saved successfully.');
    redirect('time-tracking.php');
}

$tasks = $pdo->query('SELECT id, task_number, title FROM tasks ORDER BY created_at DESC')->fetchAll();
$entries = $pdo->query('SELECT te.*, t.task_number, t.title, u.full_name AS user_name FROM time_entries te LEFT JOIN tasks t ON t.id = te.task_id LEFT JOIN users u ON u.id = te.user_id ORDER BY te.entry_date DESC, te.created_at DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h3 class="fw-bold mb-1">Time Tracking</h3>
        <p class="text-muted mb-0">Log hours worked against tasks</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Log Time</h5>
                <form method="POST" action="time-tracking.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label">Task</label>
                        <select class="form-select" name="task_id" required>
                            <option value="">Select task</option>
                            <?php foreach ($tasks as $task): ?>
                                <option value="<?= (int) $task['id']; ?>"><?= e($task['task_number'] . ' - ' . $task['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="entry_date" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hours</label>
                        <input type="number" class="form-control" name="hours" step="0.25" min="0.25" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="note" rows="4" placeholder="Work summary"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Time</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Time Entries</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Task</th>
                                <th>User</th>
                                <th>Hours</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?= e($entry['entry_date']); ?></td>
                                    <td><?= e($entry['task_number'] . ' - ' . $entry['title']); ?></td>
                                    <td><?= e($entry['user_name'] ?? 'Unknown'); ?></td>
                                    <td><?= e((string) $entry['hours']); ?></td>
                                    <td><?= e($entry['note'] ?? '—'); ?></td>
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
