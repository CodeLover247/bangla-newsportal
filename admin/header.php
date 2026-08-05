<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
check_install_status();

// Auth Guard
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$site_name = get_setting('site_name', 'Daily Horizon');
$favicon_url = get_setting('favicon_url', get_setting('site_favicon', get_setting('favicon', '')));

$unread_messages_count = 0;
try {
    $db_conn = get_db_connection();
    if ($db_conn) {
        $cnt_stmt = $db_conn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
        if ($cnt_stmt) {
            $unread_messages_count = (int)$cnt_stmt->fetchColumn();
        }
    }
} catch (Throwable $e) {}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= htmlspecialchars($site_name) ?></title>
    <?php if (!empty($favicon_url)): ?>
        <link rel="icon" href="<?= htmlspecialchars($favicon_url) ?>">
        <link rel="shortcut icon" href="<?= htmlspecialchars($favicon_url) ?>">
    <?php endif; ?>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- Rich Text Editor (CKEditor) -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.config.versionCheck = false;
        }
    </script>
    <style>
        .cke_notification_warning, .cke_warn, .cke_notification_message { display: none !important; }
    </style>
</head>
<body>

<div id="admin-wrapper">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <!-- Sidebar -->
    <aside id="admin-sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand"><?= htmlspecialchars($site_name) ?> <span>CMS</span></a>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <?php if (has_role_permission(['admin', 'editor'])): ?>
            <li class="nav-item">
                <a href="homepage.php" class="nav-link <?= $current_page === 'homepage.php' ? 'active' : '' ?>"><i class="bi bi-layout-text-window-reverse"></i> Homepage Layout</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="posts.php" class="nav-link <?= in_array($current_page, ['posts.php', 'post-add.php', 'post-edit.php']) ? 'active' : '' ?>"><i class="bi bi-newspaper"></i> Posts</a>
            </li>
            <li class="nav-item">
                <a href="photocard.php" class="nav-link <?= $current_page === 'photocard.php' ? 'active' : '' ?>"><i class="bi bi-card-image"></i> Photocard Manager</a>
            </li>
            <?php if (has_role_permission(['admin', 'editor'])): ?>
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>"><i class="bi bi-folder2-open"></i> Categories</a>
            </li>
            <li class="nav-item">
                <a href="pages.php" class="nav-link <?= $current_page === 'pages.php' ? 'active' : '' ?>"><i class="bi bi-file-earmark-text"></i> Custom Pages</a>
            </li>
            <?php endif; ?>
            <?php if (has_role_permission('admin')): ?>
            <li class="nav-item">
                <a href="menus.php" class="nav-link <?= $current_page === 'menus.php' ? 'active' : '' ?>"><i class="bi bi-menu-button-wide"></i> Menu Builder</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="media.php" class="nav-link <?= $current_page === 'media.php' ? 'active' : '' ?>"><i class="bi bi-images"></i> Media Manager</a>
            </li>
            <?php if (has_role_permission(['admin', 'editor'])): ?>
            <li class="nav-item">
                <a href="ads.php" class="nav-link <?= $current_page === 'ads.php' ? 'active' : '' ?>"><i class="bi bi-badge-ad"></i> Advertisements</a>
            </li>
            <?php endif; ?>
            <?php if (has_role_permission('admin')): ?>
            <li class="nav-item">
                <a href="colors.php" class="nav-link <?= $current_page === 'colors.php' ? 'active' : '' ?>"><i class="bi bi-palette"></i> Color & Theme Manager</a>
            </li>
            <?php endif; ?>
            <?php if (has_role_permission(['admin', 'editor'])): ?>
            <li class="nav-item">
                <a href="comments.php" class="nav-link <?= $current_page === 'comments.php' ? 'active' : '' ?>"><i class="bi bi-chat-square-dots"></i> Comments</a>
            </li>
            <li class="nav-item">
                <a href="messages.php" class="nav-link <?= $current_page === 'messages.php' ? 'active' : '' ?>">
                    <i class="bi bi-envelope-paper"></i> Contact Messages
                    <?php if ($unread_messages_count > 0): ?>
                        <span class="badge bg-danger ms-auto rounded-pill"><?= $unread_messages_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="gallery.php" class="nav-link <?= $current_page === 'gallery.php' ? 'active' : '' ?>"><i class="bi bi-camera"></i> Photo Albums</a>
            </li>
            <li class="nav-item">
                <a href="videos.php" class="nav-link <?= $current_page === 'videos.php' ? 'active' : '' ?>"><i class="bi bi-play-btn"></i> Video Manager</a>
            </li>
            <?php endif; ?>
            <?php if (has_role_permission('admin')): ?>
            <li class="nav-item">
                <a href="seo.php" class="nav-link <?= $current_page === 'seo.php' ? 'active' : '' ?>"><i class="bi bi-search"></i> SEO Settings</a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $current_page === 'settings.php' ? 'active' : '' ?>"><i class="bi bi-gear"></i> Site Settings</a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Users & Roles</a>
            </li>
            <li class="nav-item">
                <a href="backup.php" class="nav-link <?= $current_page === 'backup.php' ? 'active' : '' ?>"><i class="bi bi-database-down"></i> Backup SQL</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="clear-cache.php" class="nav-link text-warning"><i class="bi bi-arrow-clockwise"></i> Clear Cache</a>
            </li>
            <li class="nav-item mt-3">
                <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <!-- Content Area -->
    <div id="admin-content">
        <!-- Top Nav Header -->
        <header class="admin-header">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-dark btn-sm d-lg-none" id="sidebarToggleBtn"><i class="bi bi-list fs-6"></i> Menu</button>
                <h5 class="mb-0 fw-bold text-dark d-none d-sm-inline">CMS Control Center</h5>
                <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-globe me-1"></i> Live Site</a>
                <a href="clear-cache.php" class="btn btn-sm btn-outline-warning" title="Purge System Cache"><i class="bi bi-trash-fill me-1"></i> Clear Cache</a>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($admin_role) ?></span>
                <a href="profile.php" class="text-dark fw-bold text-decoration-none"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($admin_name) ?></a>
            </div>
        </header>

        <main class="admin-main-body">
