<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
require_role_permission('admin');

// Auth Guard
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['download']) && $_GET['download'] === 'sql') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    $db = get_db_connection();
    
    // Dump tables to downloadable file
    $tables = ['settings', 'users', 'categories', 'posts', 'comments', 'pages', 'menus', 'ads', 'gallery_albums', 'gallery_photos', 'videos', 'media'];
    $sql_output = "-- Daily Horizon Database Backup\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $t) {
        try {
            $rows = $db->query("SELECT * FROM {$t}")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sql_output .= "-- Table: {$t}\n";
                foreach ($rows as $r) {
                    $keys = implode("`, `", array_keys($r));
                    $vals = array_map(function($v) use ($db) {
                        return $v === null ? "NULL" : $db->quote($v);
                    }, array_values($r));
                    $val_str = implode(", ", $vals);
                    $sql_output .= "INSERT INTO `{$t}` (`{$keys}`) VALUES ({$val_str});\n";
                }
                $sql_output .= "\n";
            }
        } catch (Exception $e) {
            // Ignore missing tables gracefully
        }
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="newsportal_backup_' . date('Y_m_d_His') . '.sql"');
    header('Content-Length: ' . strlen($sql_output));
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $sql_output;
    exit;
}

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Database Backup & Export</h3>
</div>

<div class="card p-4 shadow-sm border" style="max-width: 600px;">
    <h5 class="fw-bold mb-3"><i class="bi bi-database-down text-danger me-2"></i> Export MySQL Database</h5>
    <p class="text-muted">Generate a complete SQL file backup of all published news articles, user accounts, comments, category structures, and advertisement configurations.</p>
    <a href="backup.php?download=sql" class="btn btn-danger btn-lg fw-bold"><i class="bi bi-download me-2"></i> Download SQL Backup File</a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
