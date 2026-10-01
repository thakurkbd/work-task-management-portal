<?php
require_once __DIR__ . '/../config/app.php';

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash('danger', 'Invalid CSRF token. Please refresh the page and try again.');
        redirect('login.php');
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id'] ?? null);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function loadUserPermissions(int $roleId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT p.slug FROM permissions p INNER JOIN role_permissions rp ON rp.permission_id = p.id WHERE rp.role_id = :role_id');
    $stmt->execute(['role_id' => $roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function userHasPermission(string $permission): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    $perms = $_SESSION['permissions'] ?? [];
    return in_array($permission, $perms, true);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

function requirePermission(string $permission): void
{
    requireLogin();
    if (!userHasPermission($permission)) {
        flash('danger', 'You do not have permission to access this page.');
        redirect('dashboard.php');
    }
}

function timeAgo(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'Recently';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    }

    return floor($diff / 86400) . ' days ago';
}
