<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';

// Check Admin Authentication
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Perform Cache Purge
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Redirect back with notification
$redirect = $_GET['ref'] ?? ($_SERVER['HTTP_REFERER'] ?? 'settings.php');
if (strpos($redirect, '?') !== false) {
    $redirect .= '&msg=cache_cleared';
} else {
    $redirect .= '?msg=cache_cleared';
}

header('Location: ' . $redirect);
exit;
