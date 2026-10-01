<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDbConnection();

$schema = <<<'SQL'
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) DEFAULT NULL,
    head_user_id INT DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role_id INT DEFAULT NULL,
    department_id INT DEFAULT NULL,
    status ENUM('active','inactive','pending') DEFAULT 'pending',
    is_deleted TINYINT(1) DEFAULT 0,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    designation VARCHAR(150) DEFAULT NULL,
    manager_id INT DEFAULT NULL,
    department_id INT DEFAULT NULL,
    joining_date DATE DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    department_id INT DEFAULT NULL,
    project_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    assignee_id INT DEFAULT NULL,
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('new','assigned','accepted','in_progress','on_hold','pending_review','completed','approved','reopened','cancelled','overdue') DEFAULT 'new',
    start_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    estimated_hours DECIMAL(8,2) DEFAULT 0.00,
    actual_hours DECIMAL(8,2) DEFAULT 0.00,
    completion_percent INT DEFAULT 0,
    tags VARCHAR(255) DEFAULT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    status ENUM('success','failed','locked') DEFAULT 'failed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    key_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(150) NOT NULL,
    module_name VARCHAR(150) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    browser VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
SQL;

try {
    $pdo->exec($schema);

    $roles = [
        ['super_admin', 'Super Admin', 'Full system access'],
        ['admin', 'Admin', 'Manage employees and organizational workflows'],
        ['manager', 'Manager', 'Manage team members and task execution'],
        ['employee', 'Employee', 'Perform assigned work'],
        ['viewer', 'Viewer', 'Read-only access'],
    ];

    foreach ($roles as $role) {
        $stmt = $pdo->prepare('INSERT INTO roles (slug, name, description) VALUES (:slug, :name, :description) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)');
        $stmt->execute([
            'slug' => $role[0],
            'name' => $role[1],
            'description' => $role[2],
        ]);
    }

    $permissions = [
        ['manage_users', 'Manage users'],
        ['manage_roles', 'Manage roles and permissions'],
        ['manage_departments', 'Manage departments'],
        ['manage_projects', 'Manage projects'],
        ['create_tasks', 'Create tasks'],
        ['assign_tasks', 'Assign tasks'],
        ['update_tasks', 'Update tasks'],
        ['approve_tasks', 'Approve tasks'],
        ['view_reports', 'View reports'],
        ['manage_settings', 'Manage system settings'],
        ['view_dashboard', 'View dashboard'],
        ['manage_audit_logs', 'View audit logs'],
        ['upload_files', 'Upload files'],
        ['comment_on_tasks', 'Comment on tasks'],
        ['view_employee_profile', 'View employee profiles'],
    ];

    foreach ($permissions as $permission) {
        $stmt = $pdo->prepare('INSERT INTO permissions (slug, name) VALUES (:slug, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)');
        $stmt->execute([
            'slug' => $permission[0],
            'name' => $permission[1],
        ]);
    }

    $permissionMap = [
        'super_admin' => array_column($permissions, 0),
        'admin' => ['manage_users', 'manage_departments', 'manage_projects', 'create_tasks', 'assign_tasks', 'update_tasks', 'view_reports', 'manage_settings', 'view_dashboard', 'upload_files', 'comment_on_tasks', 'view_employee_profile'],
        'manager' => ['create_tasks', 'assign_tasks', 'update_tasks', 'approve_tasks', 'view_reports', 'view_dashboard', 'upload_files', 'comment_on_tasks'],
        'employee' => ['create_tasks', 'update_tasks', 'view_dashboard', 'upload_files', 'comment_on_tasks'],
        'viewer' => ['view_dashboard', 'view_reports'],
    ];

    foreach ($permissionMap as $roleSlug => $list) {
        $roleIdStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $roleIdStmt->execute(['slug' => $roleSlug]);
        $roleId = $roleIdStmt->fetchColumn();

        if ($roleId) {
            foreach ($list as $permissionSlug) {
                $permissionIdStmt = $pdo->prepare('SELECT id FROM permissions WHERE slug = :slug LIMIT 1');
                $permissionIdStmt->execute(['slug' => $permissionSlug]);
                $permissionId = $permissionIdStmt->fetchColumn();

                if ($permissionId) {
                    $insert = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
                    $insert->execute([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    $defaultDepartment = $pdo->prepare('INSERT INTO departments (name, code, status) VALUES (:name, :code, :status) ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $defaultDepartment->execute([
        'name' => 'Information Technology',
        'code' => 'IT',
        'status' => 'active',
    ]);

    $superAdminRole = $pdo->query("SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1")->fetchColumn();
    $departmentId = $pdo->query("SELECT id FROM departments WHERE code = 'IT' LIMIT 1")->fetchColumn();

    $adminPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
    $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, phone, role_id, department_id, status) VALUES (:full_name, :email, :password_hash, :phone, :role_id, :department_id, :status) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password_hash = VALUES(password_hash), role_id = VALUES(role_id), department_id = VALUES(department_id), status = VALUES(status)');
    $insertUser->execute([
        'full_name' => 'System Administrator',
        'email' => 'admin@company.com',
        'password_hash' => $adminPassword,
        'phone' => '+1-555-0100',
        'role_id' => $superAdminRole,
        'department_id' => $departmentId,
        'status' => 'active',
    ]);

    $systemSettingInit = [
        ['company_name', 'Work Task Management Portal'],
        ['company_email', 'hello@company.com'],
        ['timezone', 'UTC'],
        ['theme', 'light'],
    ];

    foreach ($systemSettingInit as $setting) {
        $stmt = $pdo->prepare('INSERT INTO system_settings (key_name, key_value) VALUES (:key_name, :key_value) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)');
        $stmt->execute([
            'key_name' => $setting[0],
            'key_value' => $setting[1],
        ]);
    }

    echo "Setup completed successfully.\n";
} catch (Throwable $e) {
    echo 'Setup failed: ' . $e->getMessage() . "\n";
    exit(1);
}
