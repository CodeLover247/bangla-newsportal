<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9; // 9 videos per page
$offset = ($page - 1) * $limit;

$total_videos = (int)$db->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$total_pages = max(1, ceil($total_videos / $limit));

$stmt = $db->query("SELECT * FROM videos ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
$videos = $stmt->fetchAll() ?: [];
?>

<div class="container my-4">
    <!-- Header Title Banner -->
    <div class="bg-dark text-white p-4 rounded-3 shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="font-serif fw-bold mb-1 text-white">
                <i class="bi bi-play-btn-fill text-danger me-2"></i> <?= __('ভিডিও খবর ও নিউজ বুলেটিন', 'Video News & Bulletins') ?>
            </h1>
            <p class="text-white-50 mb-0 small"><?= __('ঘটনাস্থলের সরাসরি ভিডিও রিপোর্ট এবং দৈনিক খবর সংক্ষেপ।', 'Live broadcast reports, investigative field coverage, and daily video news summaries.') ?></p>
        </div>
        <div>
            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill"><i class="bi bi-collection-play me-1"></i> <?= $total_videos ?> <?= __('টি ভিডিও', 'Videos') ?></span>
        </div>
    </div>

    <!-- Video Grid -->
    <div class="row g-4 mb-4">
        <?php if (empty($videos)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-play-btn fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted"><?= __('কোনো ভিডিও খবর পাওয়া যায়নি।', 'No video news found.') ?></h5>
            </div>
        <?php else: ?>
            <?php foreach ($videos as $v): 
                $embedUrl = format_video_embed_url($v['video_url']);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="ratio ratio-16x9 bg-black">
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" title="<?= htmlspecialchars($v['title']) ?>" allowfullscreen loading="lazy"></iframe>
                        </div>
                        <div class="card-body p-3">
                            <h5 class="card-title font-serif fw-bold text-dark mb-2"><?= htmlspecialchars($v['title']) ?></h5>
                            <?php if (!empty($v['description'])): ?>
                                <p class="card-text text-muted small line-clamp-2"><?= htmlspecialchars($v['description']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($v['created_at'])): ?>
                                <small class="text-muted d-block mt-2"><i class="bi bi-clock me-1 text-danger"></i> <?= date('M d, Y', strtotime($v['created_at'])) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination Bar -->
    <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center border-top pt-3 my-4">
            <span class="small text-muted"><?= __('পৃষ্ঠা', 'Page') ?> <?= $page ?> <?= __('এর', 'of') ?> <?= $total_pages ?></span>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="video.php?page=<?= $page - 1 ?>">&laquo; <?= __('পূর্ববর্তী', 'Prev') ?></a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active bg-danger border-danger' : '' ?>">
                            <a class="page-link" href="video.php?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="video.php?page=<?= $page + 1 ?>"><?= __('পরবর্তী', 'Next') ?> &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
