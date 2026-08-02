<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/functions.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'search') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if (strlen($q) < 2) {
        echo json_encode(['status' => 'error', 'results' => []]);
        exit;
    }

    $posts = get_posts(['search' => $q, 'limit' => 5]);
    $results = [];
    foreach ($posts as $p) {
        $results[] = [
            'title' => $p['title'],
            'slug' => $p['slug'],
            'category_name' => $p['category_name'],
            'featured_image' => $p['featured_image'],
            'date' => time_ago($p['publish_date'])
        ];
    }
    echo json_encode(['status' => 'success', 'results' => $results]);
    exit;
}

if ($action === 'add_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = (int)($_POST['post_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $comment = sanitize($_POST['comment'] ?? '');

    if ($post_id <= 0 || empty($name) || !$email || empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields with a valid email.']);
        exit;
    }

    $db = get_db_connection();
    $stmt = $db->prepare("INSERT INTO comments (post_id, name, email, comment, status) VALUES (?, ?, ?, ?, 'pending')");
    if ($stmt->execute([$post_id, $name, $email, $comment])) {
        echo json_encode(['status' => 'success', 'message' => 'Thank you! Your comment has been submitted and is awaiting admin approval.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to record comment. Please try again.']);
    }
    exit;
}

if ($action === 'reorder_sections' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id']) && (empty($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true)) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit;
    }
    $rawInput = file_get_contents('php://input');
    $order_data = json_decode($rawInput, true);
    if (!is_array($order_data)) {
        $order_data = $_POST['order'] ?? [];
    }
    
    if (is_array($order_data) && !empty($order_data)) {
        reorder_homepage_sections($order_data);
        echo json_encode(['status' => 'success', 'message' => 'Homepage sections reordered successfully!']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid reorder data received.']);
        exit;
    }
}

if ($action === 'toggle_section_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id']) && (empty($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true)) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit;
    }
    $sec_id = (int)($_POST['section_id'] ?? 0);
    $sec = get_homepage_section($sec_id);
    if ($sec) {
        $db = get_db_connection();
        $new_status = ($sec['status'] == 1) ? 0 : 1;
        $db->prepare("UPDATE homepage_sections SET status = ? WHERE id = ?")->execute([$new_status, $sec_id]);
        echo json_encode(['status' => 'success', 'new_status' => $new_status]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Section not found.']);
        exit;
    }
}

if ($action === 'search_photocard_posts') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $db = get_db_connection();
    if (empty($q)) {
        $stmt = $db->query("SELECT p.id, p.title, p.publish_date, p.featured_image, p.reporter_name, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.id DESC LIMIT 8");
        $posts = $stmt->fetchAll();
    } else {
        $stmt = $db->prepare("SELECT p.id, p.title, p.publish_date, p.featured_image, p.reporter_name, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' AND (p.title LIKE ? OR p.tags LIKE ?) ORDER BY p.id DESC LIMIT 12");
        $term = '%' . $q . '%';
        $stmt->execute([$term, $term]);
        $posts = $stmt->fetchAll();
    }
    $results = [];
    foreach ($posts as $p) {
        $results[] = [
            'id' => $p['id'],
            'title' => $p['title'],
            'category_name' => $p['category_name'] ?? 'খবর',
            'publish_date' => !empty($p['publish_date']) ? date('d F, Y', strtotime($p['publish_date'])) : date('d F, Y'),
            'reporter_name' => !empty($p['reporter_name']) ? $p['reporter_name'] : 'নিজস্ব প্রতিবেদক',
            'featured_image' => !empty($p['featured_image']) ? get_media_url($p['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80'
        ];
    }
    echo json_encode(['status' => 'success', 'results' => $results]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

