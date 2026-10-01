<?php
/**
 * Application configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Work Task Management Portal');
define('APP_ENV', 'development');
define('SESSION_TIMEOUT', 1800); // 30 minutes

define('ROOT_PATH', __DIR__ . '/..');
define('CONFIG_PATH', __DIR__);
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('PUBLIC_URL', isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost');

if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
}
