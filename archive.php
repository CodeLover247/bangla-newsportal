<?php
require_once __DIR__ . '/includes/header.php';

$selected_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$options = ['date' => $selected_date, 'limit' => $limit, 'offset' => $offset];
$posts = get_posts($options);
$total_posts = get_posts_count($options);
$total_pages = ceil($total_posts / $limit);

$date_formatted = date('d F, Y', strtotime($selected_date));
?>

<div class="container my-4">
    <div class="bg-light border rounded p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 shadow-sm">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-calendar3-event text-danger me-2"></i>সংবাদ আর্কাইভ</h4>
            <p class="text-muted mb-0 small"><i class="bi bi-calendar-check me-1 text-danger"></i><?= $date_formatted ?> — <strong class="text-dark"><?= $total_posts ?></strong> টি সংবাদ প্রকাশিত হয়েছে</p>
        </div>
        <div class="d-flex align-items-center gap-2 bg-white p-2 border rounded">
            <label class="form-label mb-0 fw-bold small text-nowrap text-secondary"><i class="bi bi-filter me-1"></i>তারিখ পরিবর্তন:</label>
            <input type="date" id="archivePhpPageDatePicker" class="form-control form-control-sm fw-bold border-danger" value="<?= htmlspecialchars($selected_date) ?>" onchange="if(this.value) window.location.href='archive.php?date='+this.value">
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <?php if (!empty($posts)): ?>
                <div class="row g-3">
                    <?php foreach ($posts as $p): ?>
                        <div class="col-md-6 mb-3">
                            <div class="media-news-item p-3 bg-white border rounded h-100 shadow-sm d-flex flex-column">
                                <img src="<?= !empty($p['featured_image']) ? htmlspecialchars($p['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80' ?>" class="w-100 rounded object-fit-cover mb-2" style="height: 160px;" alt="">
                                <span class="badge bg-danger mb-2 align-self-start"><?= htmlspecialchars($p['category_name']) ?></span>
                                <h6 class="fw-bold mb-2"><a href="article.php?slug=<?= $p['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($p['title']) ?></a></h6>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2"><?= htmlspecialchars($p['short_description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                                    <span><i class="bi bi-clock me-1"></i><?= time_ago($p['publish_date']) ?></span>
                                    <span><i class="bi bi-eye me-1"></i><?= number_format($p['views']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav class="my-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="archive.php?date=<?= urlencode($selected_date) ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-warning text-center py-5 shadow-sm border">
                    <i class="bi bi-calendar-x fs-1 text-warning d-block mb-2"></i>
                    <h5 class="fw-bold">এই তারিখে কোনো প্রকাশিত সংবাদ পাওয়া যায়নি।</h5>
                    <p class="mb-0 text-muted small">অনুগ্রহ করে পাশের ক্যালেন্ডার থেকে অন্য যেকোনো সময়কাল নির্বাচন করুন।</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
