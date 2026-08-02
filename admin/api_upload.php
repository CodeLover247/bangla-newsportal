<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

// Check Admin Authentication
if (empty($_SESSION['admin_id']) && (empty($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please login.']);
    exit;
}

$db = get_db_connection();

// GET Request: List Media Items
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 18;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(filename LIKE ? OR filepath LIKE ?)";
        $sParam = "%{$search}%";
        $params[] = $sParam;
        $params[] = $sParam;
    }

    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $countStmt = $db->prepare("SELECT COUNT(*) FROM media {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT * FROM media {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'media' => $items,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $limit)
    ]);
    exit;
}

// POST Request: Direct File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_field = null;
    if (!empty($_FILES['file'])) {
        $file_field = $_FILES['file'];
    } elseif (!empty($_FILES['upload'])) {
        $file_field = $_FILES['upload'];
    } elseif (!empty($_FILES['media_file'])) {
        $file_field = $_FILES['media_file'];
    }

    if (!$file_field) {
        echo json_encode(['success' => false, 'error' => 'No file field received.']);
        exit;
    }

    $subfolder = $_POST['subfolder'] ?? 'media';
    $res = handle_file_upload($file_field, $subfolder);

    // If CKEditor upload request
    if (isset($_GET['CKEditorFuncNum'])) {
        $funcNum = $_GET['CKEditorFuncNum'];
        if ($res['success']) {
            $url = '../' . $res['filepath'];
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', 'Upload successful!');</script>";
        } else {
            $err = $res['error'];
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '', '$err');</script>";
        }
        exit;
    }

    echo json_encode($res);
    exit;
}
