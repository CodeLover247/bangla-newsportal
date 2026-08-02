<?php
require_once __DIR__ . '/includes/header.php';

$cat_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$sub_slug = isset($_GET['sub']) ? trim($_GET['sub']) : '';

$category = get_category($cat_slug);
if (!$category) {
    echo "<div class='container my-5 text-center py-5'>
        <h2 class='text-danger'>Category Not Found</h2>
        <a href='index.php' class='btn btn-danger mt-3'>Return to Home</a>
    </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$subcategories = get_categories($category['id']);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 9;
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
$total_pages = ceil($total_posts / $limit);
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($category['name']) ?></li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="bg-light p-4 rounded border mb-4">
        <h1 class="font-serif fw-bold text-dark mb-1"><?= htmlspecialchars($category['name']) ?></h1>
        <p class="text-muted mb-0 small"><?= htmlspecialchars($category['description'] ?: 'Latest news and updates in ' . $category['name']) ?></p>

        <!-- Subcategories Tabs -->
        <?php if (!empty($subcategories)): ?>
            <div class="mt-3 pt-3 border-top d-flex gap-2 flex-wrap">
                <a href="category.php?slug=<?= $category['slug'] ?>" class="btn btn-sm <?= empty($sub_slug) ? 'btn-danger' : 'btn-outline-secondary' ?>">All</a>
                <?php foreach ($subcategories as $sc): ?>
                    <a href="category.php?slug=<?= $category['slug'] ?>&sub=<?= $sc['slug'] ?>" class="btn btn-sm <?= $sub_slug === $sc['slug'] ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($sc['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Category Top Ad Banner -->
    <?= render_ad('category_top', 'mb-4') ?>

    <div class="row g-4">
        <!-- Posts Grid -->
        <div class="col-lg-8">
            <?php if (!empty($posts)): ?>
                <div class="row g-3">
                    <?php foreach ($posts as $p): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="news-card rounded p-0 h-100">
                                <div class="news-card-img-wrapper" style="aspect-ratio: 16/10;">
                                    <span class="category-badge"><?= htmlspecialchars($p['category_name']) ?></span>
                                    <img src="<?= !empty($p['featured_image']) ? htmlspecialchars($p['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80' ?>" alt="">
                                </div>
                                <div class="p-3">
                                    <h6 class="news-title mb-2"><a href="article.php?slug=<?= $p['slug'] ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($p['title']) ?></a></h6>
                                    <p class="text-muted small mb-2 text-truncate"><?= htmlspecialchars($p['short_description']) ?></p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($p['publish_date']) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="my-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="category.php?slug=<?= $category['slug'] ?>&sub=<?= $sub_slug ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center py-4">No articles published in this category yet.</div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
