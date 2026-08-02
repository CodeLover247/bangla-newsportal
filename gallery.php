<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$albums = $db->query("SELECT * FROM gallery_albums ORDER BY id DESC")->fetchAll();
?>

<div class="container my-4">
    <div class="border-bottom pb-2 mb-4">
        <h1 class="font-serif fw-bold"><i class="bi bi-images text-danger me-2"></i> Photo Gallery</h1>
        <p class="text-muted mb-0">High-resolution photographic coverage and exclusive news albums.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($albums as $alb): 
            $photos = $db->query("SELECT * FROM gallery_photos WHERE album_id = {$alb['id']}")->fetchAll();
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border">
                    <img src="<?= htmlspecialchars($alb['cover_image']) ?>" class="card-img-top" style="height: 220px; object-fit: cover;" alt="">
                    <div class="card-body">
                        <span class="badge bg-danger mb-2"><?= count($photos) ?> Photos</span>
                        <h5 class="card-title font-serif fw-bold"><?= htmlspecialchars($alb['title']) ?></h5>
                        <p class="card-text text-muted small"><?= htmlspecialchars($alb['description']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
