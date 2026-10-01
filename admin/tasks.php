<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDbConnection();
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Schema file not found.');
    }

    $pdo->exec($sql);

    $rolePermissions = [
        'super_admin' => ['manage_users', 'manage_roles', 'manage_departments', 'manage_projects', 'create_tasks', 'assign_tasks', 'update_tasks', 'approve_tasks', 'view_reports', 'manage_settings', 'view_dashboard', 'manage_audit_logs', 'upload_files', 'comment_on_tasks'],
        'admin' => ['manage_departments', 'manage_projects', 'create_tasks', 'assign_tasks', 'update_tasks', 'approve_tasks', 'view_reports', 'manage_settings', 'view_dashboard', 'upload_files', 'comment_on_tasks'],
        'manager' => ['create_tasks', 'assign_tasks', 'update_tasks', 'approve_tasks', 'view_reports', 'view_dashboard', 'upload_files', 'comment_on_tasks'],
        'employee' => ['create_tasks', 'update_tasks', 'view_dashboard', 'upload_files', 'comment_on_tasks'],
        'viewer' => ['view_dashboard', 'view_reports'],
    ];

    foreach ($rolePermissions as $roleSlug => $permissions) {
        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $roleStmt->execute(['slug' => $roleSlug]);
        $roleId = $roleStmt->fetchColumn();

        if (!$roleId) {
            continue;
        }

        foreach ($permissions as $permissionSlug) {
            $permStmt = $pdo->prepare('SELECT id FROM permissions WHERE slug = :slug LIMIT 1');
            $permStmt->execute(['slug' => $permissionSlug]);
            $permissionId = $permStmt->fetchColumn();

            if ($permissionId) {
                $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)')->execute([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    $departmentQuery = $pdo->prepare('INSERT INTO departments (name, code, status) VALUES (:name, :code, :status) ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status)');
    $departmentQuery->execute(['name' => 'Information Technology', 'code' => 'IT', 'status' => 'active']);

    $defaultRole = $pdo->query("SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1")->fetchColumn();
    $departmentId = $pdo->query("SELECT id FROM departments WHERE code = 'IT' LIMIT 1")->fetchColumn();

    $adminPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
    $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, phone, role_id, department_id, status) VALUES (:full_name, :email, :password_hash, :phone, :role_id, :department_id, :status) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password_hash = VALUES(password_hash), role_id = VALUES(role_id), department_id = VALUES(department_id), status = VALUES(status)');
    $insertUser->execute([
        'full_name' => 'System Administrator',
        'email' => 'admin@company.com',
        'password_hash' => $adminPassword,
        'phone' => '+1-555-0100',
        'role_id' => $defaultRole,
        'department_id' => $departmentId,
        'status' => 'active',
    ]);

    $settings = [
        ['company_name', 'Work Task Management Portal'],
        ['company_email', 'admin@company.com'],
        ['timezone', 'UTC'],
        ['theme', 'light'],
    ];

    foreach ($settings as $setting) {
        $pdo->prepare('INSERT INTO system_settings (key_name, key_value) VALUES (:key_name, :key_value) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)')->execute([
            'key_name' => $setting[0],
            'key_value' => $setting[1],
        ]);
    }

    echo "Database setup completed successfully.\n";
} catch (Throwable $e) {
    echo 'Setup failed: ' . $e->getMessage() . "\n";
    exit(1);
}
