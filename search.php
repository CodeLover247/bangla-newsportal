<?php
require_once __DIR__ . '/includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$posts = [];
$total_posts = 0;
if (!empty($query)) {
    $options = ['search' => $query, 'limit' => $limit, 'offset' => $offset];
    $posts = get_posts($options);
    $total_posts = get_posts_count($options);
}
$total_pages = ceil($total_posts / $limit);
?>

<div class="container my-4">
    <div class="card p-4 shadow-sm border mb-4">
        <h3 class="fw-bold mb-3"><i class="bi bi-search text-danger me-2"></i> News Search</h3>
        <form action="search.php" method="GET" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="q" class="form-control form-control-lg" value="<?= htmlspecialchars($query) ?>" placeholder="Search by news title, keyword, or tag..." required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold">Search</button>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <?php if (!empty($query)): ?>
                <h5 class="mb-3 text-muted">Showing results for "<strong><?= htmlspecialchars($query) ?></strong>" (<?= $total_posts ?> found)</h5>
                
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $p): ?>
                        <div class="media-news-item p-3 bg-white border rounded mb-3">
                            <img src="<?= !empty($p['featured_image']) ? htmlspecialchars($p['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80' ?>" class="media-news-img" style="width:140px; height:90px;" alt="">
                            <div>
                                <span class="badge bg-danger mb-1"><?= htmlspecialchars($p['category_name']) ?></span>
                                <h6><a href="article.php?slug=<?= $p['slug'] ?>"><?= htmlspecialchars($p['title']) ?></a></h6>
                                <p class="text-muted small mb-1 text-truncate"><?= htmlspecialchars($p['short_description']) ?></p>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($p['publish_date']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($total_pages > 1): ?>
                        <nav class="my-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="search.php?q=<?= urlencode($query) ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-warning text-center py-4">No results found matching "<?= htmlspecialchars($query) ?>". Try searching for another term.</div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center py-4">Please enter a search query above.</div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
