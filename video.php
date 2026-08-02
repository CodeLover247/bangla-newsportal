<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$videos = $db->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll();
?>

<div class="container my-4">
    <div class="border-bottom pb-2 mb-4">
        <h1 class="font-serif fw-bold"><i class="bi bi-play-circle-fill text-danger me-2"></i> Video News Portal</h1>
        <p class="text-muted mb-0">Video reports, documentaries, and on-ground broadcast highlights.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($videos as $v): ?>
            <div class="col-md-6">
                <div class="card h-100 shadow-sm border">
                    <div class="ratio ratio-16x9">
                        <iframe src="<?= htmlspecialchars($v['video_url']) ?>" title="<?= htmlspecialchars($v['title']) ?>" allowfullscreen></iframe>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title font-serif fw-bold"><?= htmlspecialchars($v['title']) ?></h5>
                        <p class="card-text text-muted small"><?= htmlspecialchars($v['description']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
