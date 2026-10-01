<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '') ?>assets/css/style.css">
</head>
<body>
    <?php $flash = getFlash(); if ($flash): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div class="toast show align-items-center text-bg-<?= e($flash['type']) ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><?= e($flash['message']); ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '') ?>dashboard.php"><i class="fa-solid fa-briefcase me-2"></i><?= APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="#"><i class="fa-solid fa-bell"></i>
                            <span class="badge bg-warning text-dark rounded-pill position-absolute top-0 start-100 translate-middle">3</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-2"></i><?= e($_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '') ?>dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '') ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <aside class="col-lg-2 sidebar bg-white border-end min-vh-100 p-3">
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= e($_SESSION['user_name'] ?? 'User'); ?></div>
                            <small class="text-muted"><?= e($_SESSION['user_role'] ?? 'Role'); ?></small>
                        </div>
                    </div>
                </div>

                <nav class="nav flex-column gap-1">
                    <a class="nav-link active" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '') ?>dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a>
                    <a class="nav-link" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '') ?>tasks.php"><i class="fa-solid fa-list-check me-2"></i>Tasks</a>
                    <a class="nav-link" href="#"><i class="fa-solid fa-folder-tree me-2"></i>Projects</a>
                    <a class="nav-link" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '' : '../') ?>admin/users.php"><i class="fa-solid fa-users me-2"></i>Users</a>
                    <a class="nav-link" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '' : '../') ?>admin/employees.php"><i class="fa-solid fa-user-tie me-2"></i>Employees</a>
                    <a class="nav-link" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '' : '../') ?>admin/departments.php"><i class="fa-solid fa-building me-2"></i>Departments</a>
                    <a class="nav-link" href="<?= (str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '' : '../') ?>admin/roles.php"><i class="fa-solid fa-shield-halved me-2"></i>Roles</a>
                    <a class="nav-link" href="#"><i class="fa-solid fa-calendar-days me-2"></i>Calendar</a>
                    <a class="nav-link" href="#"><i class="fa-solid fa-chart-line me-2"></i>Reports</a>
                    <a class="nav-link" href="#"><i class="fa-solid fa-gear me-2"></i>Settings</a>
                </nav>
            </aside>
            <main class="col-lg-10 p-4">
