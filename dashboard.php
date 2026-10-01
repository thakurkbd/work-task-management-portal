<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];
session_destroy();

flash('success', 'You have been logged out successfully.');
redirect('login.php');
