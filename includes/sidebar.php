<?php
require_once __DIR__ . '/functions.php';

$popular_posts = get_posts(['order_by' => 'p.views DESC', 'limit' => 5]);
$trending_posts = get_posts(['is_trending' => 1, 'limit' => 4]);
$all_categories = get_categories(0);
?>

<div class="sidebar-col">
    <!-- Popular Posts Widget -->
    <div class="mb-4">
        <h3 class="section-title"><i class="bi bi-fire text-danger me-2"></i> Most Viewed</h3>
        <div class="list-group list-group-flush border-0">
            <?php $rank = 1; foreach ($popular_posts as $p_post): ?>
                <a href="article.php?slug=<?= $p_post['slug'] ?>" class="list-group-item list-group-item-action border-bottom py-3 px-0 bg-transparent">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-4 fw-black text-danger opacity-75" style="width: 28px; font-family: var(--font-serif);"><?= $rank++ ?></span>
                        <div>
                            <h6 class="mb-1 news-title text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($p_post['title']) ?></h6>
                            <small class="text-muted"><i class="bi bi-eye me-1"></i><?= number_format($p_post['views']) ?> views &bull; <?= time_ago($p_post['publish_date']) ?></small>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar Top Ad -->
    <?= render_ad('sidebar_top', 'mb-4') ?>

    <!-- Trending Stories Widget -->
    <?php if (!empty($trending_posts)): ?>
    <div class="mb-4">
        <h3 class="section-title"><i class="bi bi-graph-up-arrow text-danger me-2"></i> Trending Stories</h3>
        <?php foreach ($trending_posts as $t_post): ?>
            <div class="media-news-item">
                <img src="<?= !empty($t_post['featured_image']) ? htmlspecialchars($t_post['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80' ?>" class="media-news-img" alt="">
                <div>
                    <h6><a href="article.php?slug=<?= $t_post['slug'] ?>"><?= htmlspecialchars($t_post['title']) ?></a></h6>
                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($t_post['publish_date']) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Category Cloud -->
    <div class="mb-4 p-3 bg-light rounded border">
        <h5 class="fw-bold mb-3 border-bottom pb-2">Top Categories</h5>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($all_categories as $cat): ?>
                <a href="category.php?slug=<?= $cat['slug'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><?= htmlspecialchars($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Archive Calendar Widget -->
    <div class="mb-4 card border shadow-sm">
        <div class="card-header bg-white border-bottom py-2 px-3 fw-bold text-dark d-flex align-items-center justify-content-between">
            <span><i class="bi bi-calendar3 text-danger me-1"></i> আর্কাইভ ক্যালেন্ডার</span>
            <a href="archive.php" class="small text-danger text-decoration-none fw-semibold">সব আর্কাইভ &rarr;</a>
        </div>
        <div class="card-body p-3">
            <div class="input-group input-group-sm mb-3">
                <span class="input-group-text bg-white text-danger border-danger"><i class="bi bi-calendar-event"></i></span>
                <input type="date" id="phpSidebarArchiveDatePicker" class="form-control form-control-sm border-danger fw-bold text-center" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d') ?>" onchange="if(this.value) window.location.href='archive.php?date='+this.value">
                <button class="btn btn-danger btn-sm" type="button" onclick="var d=document.getElementById('phpSidebarArchiveDatePicker').value; if(d) window.location.href='archive.php?date='+d;"><i class="bi bi-search"></i></button>
            </div>
            <div class="text-center">
                <a href="archive.php?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-danger w-100 fw-bold py-1">
                    <i class="bi bi-newspaper me-1"></i> আজকের সংবাদপত্র সংস্করণ
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar Bottom Ad -->
    <?= render_ad('sidebar_bottom', 'mb-4') ?>
</div>
