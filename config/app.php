<?php
/**
 * Application configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 1800,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Work Task Management Portal');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800);
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/..');
}
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', ROOT_PATH . '/uploads');
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', ROOT_PATH . '/assets');
}
if (!defined('PUBLIC_URL')) {
    define('PUBLIC_URL', 'http://localhost');
}

if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
}

if (!is_dir(UPLOADS_PATH . '/employees')) {
    mkdir(UPLOADS_PATH . '/employees', 0777, true);
}

if (!is_dir(UPLOADS_PATH . '/tasks')) {
    mkdir(UPLOADS_PATH . '/tasks', 0777, true);
}

date_default_timezone_set('UTC');
