<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9; // 9 albums per page
$offset = ($page - 1) * $limit;

$total_albums = (int)$db->query("SELECT COUNT(*) FROM gallery_albums")->fetchColumn();
$total_pages = max(1, ceil($total_albums / $limit));

$stmt = $db->query("SELECT * FROM gallery_albums ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
$albums = $stmt->fetchAll() ?: [];
?>

<div class="container my-4">
    <!-- Page Title & Header -->
    <div class="bg-dark text-white p-4 rounded-3 shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="font-serif fw-bold mb-1 text-white">
                <i class="bi bi-camera-fill text-danger me-2"></i> <?= __('ছবি গ্যালারি ও ফটো অ্যালবাম', 'Photo Gallery & Albums') ?>
            </h1>
            <p class="text-white-50 mb-0 small"><?= __('উচ্চ-রেজোলিউশন ছবি এবং ঘটনার সময়চিত্র কভারেজ।', 'High-resolution photography coverage and exclusive event photo albums.') ?></p>
        </div>
        <div>
            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill"><i class="bi bi-images me-1"></i> <?= $total_albums ?> <?= __('টি অ্যালবাম', 'Albums') ?></span>
        </div>
    </div>

    <!-- Albums Grid -->
    <div class="row g-4 mb-4">
        <?php if (empty($albums)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-camera fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted"><?= __('কোনো ফটো অ্যালবাম পাওয়া যায়নি।', 'No photo albums found.') ?></h5>
            </div>
        <?php else: ?>
            <?php foreach ($albums as $alb): 
                $photos = $db->query("SELECT * FROM gallery_photos WHERE album_id = {$alb['id']} ORDER BY id ASC")->fetchAll() ?: [];
                $cover_img = !empty($alb['cover_image']) ? $alb['cover_image'] : ($photos[0]['photo_url'] ?? 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?w=800&auto=format&fit=crop&q=80');
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden hover-shadow transition-all">
                        <div class="position-relative overflow-hidden group" style="height: 230px; cursor: pointer;" onclick="openAlbumModal(<?= $alb['id'] ?>)">
                            <img src="<?= htmlspecialchars($cover_img) ?>" class="w-100 h-100 object-fit-cover transition-transform" alt="<?= htmlspecialchars($alb['title']) ?>" id="cover-img-<?= $alb['id'] ?>">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-danger shadow-sm"><i class="bi bi-camera-fill me-1"></i> <?= count($photos) ?> <?= __('ছবি', 'Photos') ?></span>
                            </div>
                            <div class="position-absolute inset-0 bg-dark bg-opacity-25 opacity-0 hover-opacity-100 d-flex align-items-center justify-content-center transition-all">
                                <span class="btn btn-sm btn-light text-dark fw-bold rounded-pill px-3 shadow"><i class="bi bi-zoom-in me-1 text-danger"></i> <?= __('ছবি দেখুন', 'View Gallery') ?></span>
                            </div>
                        </div>

                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title font-serif fw-bold text-dark mb-2"><?= htmlspecialchars($alb['title']) ?></h5>
                                <?php if (!empty($alb['description'])): ?>
                                    <p class="card-text text-muted small line-clamp-2"><?= htmlspecialchars($alb['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3" onclick="openAlbumModal(<?= $alb['id'] ?>)">
                                    <i class="bi bi-arrows-fullscreen me-1"></i> <?= __('জুম করে দেখুন', 'Zoom / Open') ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-dark" onclick="openSingleZoom('<?= htmlspecialchars($cover_img) ?>', '<?= htmlspecialchars(addslashes($alb['title'])) ?>')">
                                    <i class="bi bi-search-heart text-danger fs-6"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Album Viewer & Lightbox Modal -->
                <div class="modal fade" id="albumModal<?= $alb['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content bg-dark text-white border-secondary">
                            <div class="modal-header border-secondary py-3">
                                <h5 class="modal-title font-serif fw-bold text-white"><i class="bi bi-images text-danger me-2"></i> <?= htmlspecialchars($alb['title']) ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <?php if (!empty($alb['description'])): ?>
                                    <p class="text-white-50 border-bottom border-secondary pb-3 mb-4"><?= htmlspecialchars($alb['description']) ?></p>
                                <?php endif; ?>

                                <!-- Main Feature Display for Lightbox -->
                                <div class="text-center mb-4 position-relative bg-black rounded p-3" style="min-height: 380px;">
                                    <div class="d-flex justify-content-center align-items-center overflow-hidden" style="max-height: 520px;">
                                        <img src="<?= htmlspecialchars($cover_img) ?>" id="mainPhotoDisplay<?= $alb['id'] ?>" class="img-fluid rounded transition-transform" style="max-height: 500px; object-fit: contain; transform: scale(1);" alt="">
                                    </div>

                                    <!-- Interactive Zoom Controls Overlay -->
                                    <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3 bg-dark bg-opacity-75 p-2 rounded-pill border border-secondary d-flex gap-2 shadow">
                                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="zoomImage('mainPhotoDisplay<?= $alb['id'] %>', 0.25)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="zoomImage('mainPhotoDisplay<?= $alb['id'] %>', -0.25)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="resetZoom('mainPhotoDisplay<?= $alb['id'] ?>')" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </div>
                                </div>

                                <!-- Photo Thumbnails Carousel Grid -->
                                <h6 class="fw-bold text-danger border-bottom border-secondary pb-2 mb-3"><i class="bi bi-collection-fill me-1"></i> <?= __('অ্যালবামের সকল ছবি', 'Album Photos') ?> (<?= count($photos) > 0 ? count($photos) : 1 ?>)</h6>
                                <div class="row g-2">
                                    <div class="col-3 col-md-2">
                                        <img src="<?= htmlspecialchars($cover_img) ?>" class="img-thumbnail bg-dark border-danger rounded cursor-pointer w-100 object-fit-cover" style="height: 80px;" onclick="switchMainPhoto('mainPhotoDisplay<?= $alb['id'] %>', '<?= htmlspecialchars($cover_img) ?>')" alt="">
                                    </div>
                                    <?php foreach ($photos as $ph): ?>
                                        <div class="col-3 col-md-2">
                                            <img src="<?= htmlspecialchars($ph['photo_url']) ?>" class="img-thumbnail bg-dark border-secondary rounded cursor-pointer w-100 object-fit-cover hover-border-danger" style="height: 80px;" onclick="switchMainPhoto('mainPhotoDisplay<?= $alb['id'] %>', '<?= htmlspecialchars($ph['photo_url']) ?>')" alt="<?= htmlspecialchars($ph['caption'] ?? '') ?>" title="<?= htmlspecialchars($ph['caption'] ?? '') ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary py-2">
                                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal"><?= __('বন্ধ করুন', 'Close') ?></button>
                            </div>
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
                        <a class="page-link" href="gallery.php?page=<?= $page - 1 ?>">&laquo; <?= __('পূর্ববর্তী', 'Prev') ?></a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active bg-danger border-danger' : '' ?>">
                            <a class="page-link" href="gallery.php?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="gallery.php?page=<?= $page + 1 ?>"><?= __('পরবর্তী', 'Next') ?> &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Standalone High-Res Zoom Modal -->
<div class="modal fade" id="singleZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-black text-white">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title font-serif text-white-50" id="singleZoomTitle">Photo View</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3 position-relative">
                <div class="overflow-hidden d-flex justify-content-center align-items-center" style="max-height: 80vh;">
                    <img src="" id="singleZoomImg" class="img-fluid rounded transition-transform" style="max-height: 75vh; object-fit: contain; transform: scale(1);" alt="">
                </div>
                <!-- Interactive Zoom Controls Overlay -->
                <div class="mt-3 bg-dark p-2 rounded-pill d-inline-flex gap-2 border border-secondary shadow">
                    <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="zoomImage('singleZoomImg', 0.25)"><i class="bi bi-zoom-in"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="zoomImage('singleZoomImg', -0.25)"><i class="bi bi-zoom-out"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="resetZoom('singleZoomImg')"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentScaleMap = {};

function openAlbumModal(albumId) {
    const modalEl = document.getElementById('albumModal' + albumId);
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openSingleZoom(imgUrl, title) {
    const imgEl = document.getElementById('singleZoomImg');
    const titleEl = document.getElementById('singleZoomTitle');
    if (imgEl && titleEl) {
        imgEl.src = imgUrl;
        titleEl.innerText = title || 'Photo Zoom View';
        resetZoom('singleZoomImg');
        const modal = new bootstrap.Modal(document.getElementById('singleZoomModal'));
        modal.show();
    }
}

function switchMainPhoto(targetImgId, newSrc) {
    const img = document.getElementById(targetImgId);
    if (img) {
        img.src = newSrc;
        resetZoom(targetImgId);
    }
}

function zoomImage(imgId, delta) {
    const img = document.getElementById(imgId);
    if (!img) return;
    if (!currentScaleMap[imgId]) currentScaleMap[imgId] = 1;
    currentScaleMap[imgId] = Math.max(0.5, Math.min(3.5, currentScaleMap[imgId] + delta));
    img.style.transform = `scale(${currentScaleMap[imgId]})`;
}

function resetZoom(imgId) {
    const img = document.getElementById(imgId);
    if (!img) return;
    currentScaleMap[imgId] = 1;
    img.style.transform = `scale(1)`;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
