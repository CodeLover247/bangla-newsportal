<?php
// PHP Built-in Server Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Special routes
if ($uri === '/sitemap.xml' || $uri === '/sitemap') {
    require __DIR__ . '/sitemap.php';
    exit;
}

if ($uri === '/robots.txt') {
    if (file_exists(__DIR__ . '/robots.txt')) {
        return false;
    }
}

// Serve existing static files directly (css, js, images, etc.)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Handle PHP scripts
$script = ltrim($uri, '/');
if (empty($script)) {
    require __DIR__ . '/index.php';
    exit;
}

if (file_exists(__DIR__ . '/' . $script)) {
    require __DIR__ . '/' . $script;
    exit;
}

if (file_exists(__DIR__ . '/' . $script . '.php')) {
    require __DIR__ . '/' . $script . '.php';
    exit;
}

// Default fallback
require __DIR__ . '/index.php';
