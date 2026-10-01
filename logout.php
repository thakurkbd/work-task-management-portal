<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        flash('danger', 'Email and password are required.');
        redirect('login.php');
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash']) && $user['status'] === 'active') {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role_id'] = (int)$user['role_id'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['user_role_slug'] = $user['role_slug'];
        $_SESSION['last_activity'] = time();
        $_SESSION['permissions'] = loadUserPermissions((int)$user['role_id']);

        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);

        $logStmt = $pdo->prepare('INSERT INTO login_logs (user_id, ip_address, user_agent, status) VALUES (:user_id, :ip_address, :user_agent, :status)');
        $logStmt->execute([
            'user_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'status' => 'success',
        ]);

        $route = match ($user['role_slug']) {
            'super_admin', 'admin' => 'admin/dashboard.php',
            'manager' => 'manager/dashboard.php',
            'employee' => 'employee/dashboard.php',
            default => 'dashboard.php',
        };

        flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirect($route);
    }

    if ($user) {
        $logStmt = $pdo->prepare('INSERT INTO login_logs (user_id, ip_address, user_agent, status) VALUES (:user_id, :ip_address, :user_agent, :status)');
        $logStmt->execute([
            'user_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'status' => 'failed',
        ]);
        flash('danger', 'Invalid credentials or account is inactive.');
    } else {
        flash('danger', 'No account found with that email address.');
    }

    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-box card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="mx-auto rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:72px;height:72px;font-size: 2rem;">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <h2 class="mt-3 fw-bold">Welcome back</h2>
                    <p class="text-muted mb-0">Sign in to your workspace</p>
                </div>

                <?php $flash = getFlash(); if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']); ?> rounded-3"><?= e($flash['message']); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="admin@company.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="text-decoration-none">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign In</button>
                </form>

                <div class="mt-4 text-center small text-muted">
                    Demo admin credentials:<br>
                    <strong>Email:</strong> admin@company.com<br>
                    <strong>Password:</strong> Admin@123
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
