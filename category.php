<?php
require_once __DIR__ . '/includes/functions.php';

$cat_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$sub_slug = isset($_GET['sub']) ? trim($_GET['sub']) : '';

$category = get_category($cat_slug);

if (!$category) {
    $page_title = 'ক্যাটাগরি পাওয়া যায়নি - Category Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='container my-5 text-center py-5'>
        <div class='card p-5 shadow-sm border-0 max-w-lg mx-auto'>
            <i class='bi bi-folder-x text-danger display-3 mb-3'></i>
            <h2 class='text-danger fw-bold mb-2'>ক্যাটাগরি পাওয়া যায়নি (Category Not Found)</h2>
            <p class='text-muted mb-4'>আপনি যে ক্যাটাগরিটি খুঁজছেন তা পাওয়া যায়নি অথবা মুছে ফেলা হয়েছে।</p>
            <div><a href='index.php' class='btn btn-danger px-4 py-2 fw-bold'>হোম পেজে ফিরে যান</a></div>
        </div>
    </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $category['name'] . ' - ' . get_setting('site_name', 'Babuganjlive.com');
$page_desc = !empty($category['description']) ? $category['description'] : $category['name'] . ' সম্পর্কিত সর্বশেষ সংবাদ ও খবর।';
$og_url = get_full_url('category.php?slug=' . urlencode($category['slug']));

require_once __DIR__ . '/includes/header.php';

$subcategories = get_categories($category['id']);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12; // 12 articles per page
$offset = ($page - 1) * $limit;

$subcategory_id = 0;
if (!empty($sub_slug)) {
    $sub_cat = get_category($sub_slug);
    if ($sub_cat) $subcategory_id = $sub_cat['id'];
}

$options = [
    'category_id' => $category['id'],
    'subcategory_id' => $subcategory_id,
    'limit' => $limit,
    'offset' => $offset
];

$posts = get_posts($options);
$total_posts = get_posts_count($options);
$total_pages = max(1, ceil($total_posts / $limit));

$showing_start = $total_posts > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $limit, $total_posts);
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">প্রচ্ছদ</a></li>
            <li class="breadcrumb-item active text-danger fw-bold"><?= htmlspecialchars($category['name']) ?></li>
            <?php if (!empty($sub_slug) && !empty($sub_cat)): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($sub_cat['name']) ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="bg-light p-4 rounded border mb-4 border-start border-4 border-danger shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="font-serif fw-bold text-dark mb-1"><?= htmlspecialchars($category['name']) ?></h1>
            <p class="text-muted mb-0 small"><?= htmlspecialchars($category['description'] ?: 'সর্বশেষ সংবাদ এবং আপডেট: ' . $category['name']) ?></p>
        </div>
        <div>
            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-newspaper me-1"></i> মোট <?= number_format($total_posts) ?> টি সংবাদ
            </span>
        </div>
    </div>

    <!-- Subcategories Tabs -->
    <?php if (!empty($subcategories)): ?>
        <div class="mb-4 d-flex gap-2 flex-wrap bg-white p-3 border rounded shadow-sm align-items-center">
            <span class="small fw-bold text-muted me-2"><i class="bi bi-funnel me-1"></i>উপ-ক্যাটাগরি:</span>
            <a href="category.php?slug=<?= urlencode($category['slug']) ?>" class="btn btn-sm <?= empty($sub_slug) ? 'btn-danger' : 'btn-outline-secondary' ?>">সকল সংবাদ</a>
            <?php foreach ($subcategories as $sc): ?>
                <a href="category.php?slug=<?= urlencode($category['slug']) ?>&sub=<?= urlencode($sc['slug']) ?>" class="btn btn-sm <?= $sub_slug === $sc['slug'] ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($sc['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Category Top Ad Banner -->
    <?= render_ad('category_top', 'mb-4') ?>

    <div class="row g-4">
        <!-- Posts Grid -->
        <div class="col-lg-8">
            <?php if (!empty($posts)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small fw-semibold">
                        প্রদর্শন করা হচ্ছে: <strong><?= $showing_start ?>-<?= $showing_end ?></strong> (মোট <strong><?= $total_posts ?></strong> টি সংবাদের মধ্যে)
                    </span>
                    <span class="badge bg-light text-dark border">পৃষ্ঠা <?= $page ?>/<?= $total_pages ?></span>
                </div>

                <div class="row g-3">
                    <?php foreach ($posts as $p): ?>
                        <div class="col-md-6 mb-2">
                            <div class="card h-100 border rounded-3 shadow-sm overflow-hidden hover-shadow transition">
                                <div class="position-relative" style="aspect-ratio: 16/10; overflow: hidden; background: #f3f4f6;">
                                    <span class="position-absolute top-0 start-0 bg-danger text-white small px-2 py-1 fw-bold rounded-bottom-end z-1"><?= htmlspecialchars($p['category_name']) ?></span>
                                    <a href="article.php?slug=<?= urlencode($p['slug']) ?>">
                                        <img src="<?= !empty($p['featured_image']) ? htmlspecialchars($p['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80' ?>" class="w-100 h-100 object-fit-cover" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="card-body p-3 d-flex flex-column">
                                    <h6 class="font-serif fw-bold mb-2 line-clamp-2" style="line-height: 1.4;">
                                        <a href="article.php?slug=<?= urlencode($p['slug']) ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($p['title']) ?></a>
                                    </h6>
                                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2"><?= htmlspecialchars($p['short_description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto small text-muted">
                                        <span><i class="bi bi-clock me-1 text-danger"></i><?= time_ago($p['publish_date']) ?></span>
                                        <span><i class="bi bi-eye me-1"></i><?= number_format($p['views']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): 
                    $range = 2;
                    $start_p = max(1, $page - $range);
                    $end_p = min($total_pages, $page + $range);
                    $base_url = "category.php?slug=" . urlencode($category['slug']) . (!empty($sub_slug) ? "&sub=" . urlencode($sub_slug) : "");
                ?>
                    <nav class="my-4 pt-3 border-top">
                        <ul class="pagination justify-content-center flex-wrap gap-1">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link shadow-sm" href="<?= $base_url ?>&page=<?= $page - 1 ?>">&laquo; পূর্ববর্তী</a>
                            </li>

                            <?php if ($start_p > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= $base_url ?>&page=1">1</a></li>
                                <?php if ($start_p > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link <?= $i == $page ? 'bg-danger border-danger text-white fw-bold shadow-sm' : '' ?>" href="<?= $base_url ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_p < $total_pages): ?>
                                <?php if ($end_p < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="<?= $base_url ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
                            <?php endif; ?>

                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link shadow-sm" href="<?= $base_url ?>&page=<?= $page + 1 ?>">পরবর্তী &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center py-5 shadow-sm border rounded">
                    <i class="bi bi-newspaper fs-1 text-muted d-block mb-2"></i>
                    <h5 class="fw-bold">এই ক্যাটাগরিতে এখনও কোনো প্রকাশিত সংবাদ পাওয়া যায়নি।</h5>
                    <p class="text-muted small mb-0">নতুন সংবাদের জন্য অনুগ্রহ করে পরবর্তীতে আবার চেক করুন।</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
