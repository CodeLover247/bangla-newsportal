<?php
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$db = get_db_connection();
$page_item = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND status = 1");
        $stmt->execute([$slug]);
        $page_item = $stmt->fetch();
    } catch (Throwable $e) {
        $page_item = null;
    }
}

if (!$page_item) {
    $page_title = 'পেজ পাওয়া যায়নি - Page Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='container my-5 text-center py-5'>
        <div class='card p-5 shadow-sm border-0 max-w-lg mx-auto'>
            <i class='bi bi-file-earmark-x text-danger display-3 mb-3'></i>
            <h2 class='text-danger fw-bold mb-2'>পেজ পাওয়া যায়নি (Page Not Found)</h2>
            <p class='text-muted mb-4'>আপনি যে পেজটি খুঁজছেন তা পাওয়া যায়নি অথবা মুছে ফেলা হয়েছে।</p>
            <div><a href='index.php' class='btn btn-danger px-4 py-2 fw-bold'>হোম পেজে ফিরে যান</a></div>
        </div>
    </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $page_item['title'] . ' - ' . get_setting('site_name', 'Babuganjlive.com');
$page_desc = strip_tags(mb_substr($page_item['content'] ?? '', 0, 180));
$og_url = get_full_url('page.php?slug=' . urlencode($page_item['slug']));

require_once __DIR__ . '/includes/header.php';

// Increment page views
if ($db) {
    try {
        $db->prepare("UPDATE pages SET views = views + 1 WHERE id = ?")->execute([$page_item['id']]);
    } catch (Throwable $e) {}
}
?>

<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white p-4 rounded border shadow-sm">
                <h1 class="font-serif fw-bold mb-3 pb-2 border-bottom"><?= htmlspecialchars($page_item['title']) ?></h1>
                <div class="page-content font-sans">
                    <?= $page_item['content'] ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
