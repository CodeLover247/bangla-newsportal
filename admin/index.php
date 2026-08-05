<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();

$total_posts = $db->query("SELECT COUNT(*) FROM posts WHERE status != 'trash'")->fetchColumn();
$total_cats = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$todays_posts = $db->query("SELECT COUNT(*) FROM posts WHERE DATE(publish_date) = CURRENT_DATE AND status = 'published'")->fetchColumn();
$total_views = $db->query("SELECT SUM(views) FROM posts")->fetchColumn() ?: 0;
$total_comments = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$pending_posts = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'")->fetchColumn();

$popular_posts = get_posts(['order_by' => 'p.views DESC', 'limit' => 5]);
$recent_posts = get_posts(['limit' => 5]);

// Category wise post breakdown for Chart
$cat_stats = $db->query("
    SELECT c.name, COUNT(p.id) as post_count, COALESCE(SUM(p.views), 0) as total_views
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published'
    GROUP BY c.id
    ORDER BY total_views DESC
    LIMIT 7
")->fetchAll();

$cat_names = array_column($cat_stats, 'name');
$cat_views = array_column($cat_stats, 'total_views');
$cat_counts = array_column($cat_stats, 'post_count');

// Past 7 days post publication trend
$daily_trend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $date_label = date('D (d M)', strtotime("-$i days"));
    $cnt = $db->prepare("SELECT COUNT(*) FROM posts WHERE DATE(publish_date) = ? AND status = 'published'");
    $cnt->execute([$date]);
    $daily_trend[$date_label] = (int)$cnt->fetchColumn();
}
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-danger alert-dismissible fade show fw-semibold mb-4">
        <i class="bi bi-shield-exclamation me-2"></i> Access Denied: Your user role does not have permission to access that section.
    </div>
<?php endif; ?>

<?php if ($pending_posts > 0 && has_role_permission(['admin', 'editor'])): ?>
    <div class="alert alert-warning border-start border-warning border-4 shadow-sm d-flex align-items-center justify-content-between p-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="fs-3 text-warning"><i class="bi bi-clock-history"></i></div>
            <div>
                <h6 class="fw-bold mb-0 text-dark">Pending Article Approvals (অনুমোদনের অপেক্ষায় <?= $pending_posts ?> টি সংবাদ)</h6>
                <small class="text-muted">রিপোর্টারদের পাঠানো <strong><?= $pending_posts ?></strong> টি পোস্ট অনুমোদনের জন্য পেন্ডিং রয়েছে। রিভিউ করে প্রকাশ করুন।</small>
            </div>
        </div>
        <a href="posts.php?status=pending" class="btn btn-warning btn-sm fw-bold px-3 text-nowrap"><i class="bi bi-check2-circle me-1"></i> Review & Approve Posts &rarr;</a>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Stat 1 -->
    <div class="col-md-6 col-lg-4">
        <div class="stat-card shadow-sm border-0">
            <div class="stat-icon bg-primary text-white"><i class="bi bi-newspaper"></i></div>
            <div>
                <div class="stat-title text-muted fw-semibold">Total Posts</div>
                <div class="stat-value fw-bold text-dark"><?= number_format($total_posts) ?></div>
            </div>
        </div>
    </div>
    <!-- Stat 2 -->
    <div class="col-md-6 col-lg-4">
        <div class="stat-card shadow-sm border-0">
            <div class="stat-icon bg-success text-white"><i class="bi bi-folder2-open"></i></div>
            <div>
                <div class="stat-title text-muted fw-semibold">Total Categories</div>
                <div class="stat-value fw-bold text-dark"><?= number_format($total_cats) ?></div>
            </div>
        </div>
    </div>
    <!-- Stat 3 -->
    <div class="col-md-6 col-lg-4">
        <div class="stat-card shadow-sm border-0">
            <div class="stat-icon bg-danger text-white"><i class="bi bi-eye"></i></div>
            <div>
                <div class="stat-title text-muted fw-semibold">Total Views</div>
                <div class="stat-value fw-bold text-dark"><?= number_format($total_views) ?></div>
            </div>
        </div>
    </div>
    <!-- Stat 4 -->
    <div class="col-md-6 col-lg-4">
        <div class="stat-card shadow-sm border-0">
            <div class="stat-icon bg-warning text-dark"><i class="bi bi-calendar-event"></i></div>
            <div>
                <div class="stat-title text-muted fw-semibold">Today's Posts</div>
                <div class="stat-value fw-bold text-dark"><?= number_format($todays_posts) ?></div>
            </div>
        </div>
    </div>
    <!-- Stat 5 -->
    <div class="col-md-6 col-lg-4">
        <div class="stat-card shadow-sm border-0">
            <div class="stat-icon bg-info text-white"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-title text-muted fw-semibold">Total Users</div>
                <div class="stat-value fw-bold text-dark"><?= number_format($total_users) ?></div>
            </div>
        </div>
    </div>
    <!-- Stat 6 -->
    <div class="col-md-6 col-lg-4">
        <div class="stat-card shadow-sm border-0">
            <div class="stat-icon bg-secondary text-white"><i class="bi bi-chat-dots"></i></div>
            <div>
                <div class="stat-title text-muted fw-semibold">Total Comments</div>
                <div class="stat-value fw-bold text-dark"><?= number_format($total_comments) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Analytics Charts Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow text-danger me-2"></i> 7-Day Article Publishing Activity</h5>
                <span class="badge bg-light text-dark border">Real-time</span>
            </div>
            <div style="position: relative; min-height: 250px;">
                <canvas id="publishTrendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Views by Category</h5>
                <span class="badge bg-light text-dark border">Top Categories</span>
            </div>
            <div style="position: relative; min-height: 250px;">
                <canvas id="categoryViewsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Popular Posts -->
    <div class="col-lg-7">
        <div class="card-table shadow-sm border">
            <div class="card-table-header d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                <h5 class="mb-0 fw-bold"><i class="bi bi-fire text-danger me-2"></i> Most Popular Articles</h5>
                <a href="posts.php" class="btn btn-sm btn-outline-secondary">All Posts</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_posts as $pop): ?>
                            <tr>
                                <td style="width: 60px;">
                                    <img src="<?= htmlspecialchars(get_media_url($pop['featured_image'])) ?>" class="rounded" style="width: 45px; height: 35px; object-fit: cover;" alt="">
                                </td>
                                <td>
                                    <a href="../article.php?slug=<?= $pop['slug'] ?>" target="_blank" class="text-dark fw-semibold text-decoration-none"><?= htmlspecialchars($pop['title']) ?></a>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($pop['category_name']) ?></span></td>
                                <td><span class="badge bg-danger"><i class="bi bi-eye me-1"></i><?= number_format($pop['views']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions & System Info -->
    <div class="col-lg-5">
        <div class="card border shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i> Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="post-add.php" class="btn btn-danger py-2 fw-bold"><i class="bi bi-plus-circle me-1"></i> Add New Article</a>
                <a href="categories.php" class="btn btn-outline-dark py-2 fw-bold"><i class="bi bi-folder-plus me-1"></i> Add Category</a>
                <a href="ads.php" class="btn btn-outline-primary py-2 fw-bold"><i class="bi bi-badge-ad me-1"></i> Manage Advertisements</a>
            </div>
        </div>

        <div class="card border shadow-sm p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i> Portal Status</h5>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                    <span><i class="bi bi-hdd-network me-2 text-muted"></i> System Engine</span>
                    <span class="badge bg-dark">PHP <?= PHP_VERSION ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                    <span><i class="bi bi-shield-lock me-2 text-muted"></i> Security Shield</span>
                    <span class="badge bg-success">Active & Protected</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                    <span><i class="bi bi-lightning me-2 text-muted"></i> Cache Performance</span>
                    <span class="badge bg-primary">Optimized</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Publish Trend Chart
    const trendCtx = document.getElementById('publishTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($daily_trend)) ?>,
                datasets: [{
                    label: 'Published Articles',
                    data: <?= json_encode(array_values($daily_trend)) ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#dc3545',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // 2. Category Views Chart
    const catCtx = document.getElementById('categoryViewsChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($cat_names) ?>,
                datasets: [{
                    data: <?= json_encode($cat_views) ?>,
                    backgroundColor: [
                        '#dc3545', '#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6c757d', '#6610f2'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 12 }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
