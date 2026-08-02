<?php
require_once __DIR__ . '/includes/header.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND status = 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    echo "<div class='container my-5 text-center py-5'>
        <h2 class='text-danger'>Page Not Found</h2>
        <a href='index.php' class='btn btn-danger mt-3'>Return to Home</a>
    </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Increment page views
$db->prepare("UPDATE pages SET views = views + 1 WHERE id = ?")->execute([$page['id']]);
?>

<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white p-4 rounded border shadow-sm">
                <h1 class="font-serif fw-bold mb-3 pb-2 border-bottom"><?= htmlspecialchars($page['title']) ?></h1>
                <div class="page-content font-sans">
                    <?= $page['content'] ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
