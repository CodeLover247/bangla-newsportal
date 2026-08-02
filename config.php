<?php
// Auto-generated Configuration File with .env Sync
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $env_line) {
        $env_line = trim($env_line);
        if (!empty($env_line) && strpos($env_line, '#') !== 0 && strpos($env_line, '=') !== false) {
            list($k, $v) = explode('=', $env_line, 2);
            $k = trim($k);
            $v = trim($v, "\"' ");
            if ($k === 'DB_TYPE' && !defined('DB_TYPE')) define('DB_TYPE', $v);
            if ($k === 'DB_HOST' && !defined('DB_HOST')) define('DB_HOST', $v);
            if ($k === 'DB_NAME' && !defined('DB_NAME')) define('DB_NAME', $v);
            if ($k === 'DB_USER' && !defined('DB_USER')) define('DB_USER', $v);
            if ($k === 'DB_PASS' && !defined('DB_PASS')) define('DB_PASS', $v);
            if (($k === 'APP_URL' || $k === 'SITE_URL') && !defined('SITE_URL')) define('SITE_URL', $v);
        }
    }
}

if (!defined('DB_TYPE')) define('DB_TYPE', 'sqlite');
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'newsportal');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_SQLITE_PATH')) define('DB_SQLITE_PATH', __DIR__ . '/database.sqlite');
if (!defined('SITE_URL')) define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:3000'));
if (!defined('ADMIN_PATH')) define('ADMIN_PATH', 'admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

