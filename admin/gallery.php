<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = slugify($title);
    $cover = trim($_POST['cover_image'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!empty($_FILES['cover_file']['name'])) {
        $up = handle_file_upload($_FILES['cover_file'], 'gallery');
        if ($up['success']) {
            $cover = $up['filepath'];
        }
    }

    if (!empty($title)) {
        $stmt = $db->prepare("INSERT INTO gallery_albums (title, slug, cover_image, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $cover, $desc]);
        $msg = "Photo album created successfully!";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM gallery_albums WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: gallery.php?msg=deleted');
    exit;
}

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $sParam = "%{$search}%";
    $params[] = $sParam;
    $params[] = $sParam;
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM gallery_albums {$whereSQL}");
$countStmt->execute($params);
$total_albums = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_albums / $limit);

// Data
$sql = "SELECT * FROM gallery_albums {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$albums = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3">Add Photo Album</h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success py-2 small alert-dismissible fade show">Album deleted successfully.</div><?php endif; ?>

            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Album Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Cultural Fair Coverage">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Cover Image URL or Select Media</label>
                    <div class="input-group mb-2">
                        <input type="text" id="album_cover_input" name="cover_image" class="form-control" placeholder="uploads/gallery/cover.jpg">
                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#album_cover_input"><i class="bi bi-images me-1"></i> Media</button>
                    </div>
                    <label class="form-label fw-semibold small text-muted">Or Upload Cover File</label>
                    <input type="file" name="cover_file" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief album summary..."></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-camera me-1"></i> Create Photo Album</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Search Filter -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <form method="GET" action="gallery.php">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search album by title..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="gallery.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm p-3">
            <h5 class="fw-bold mb-3">Photo Albums List</h5>
            <div class="row g-3">
                <?php if (!empty($albums)): foreach ($albums as $alb): ?>
                    <div class="col-md-6">
                        <div class="card border shadow-sm h-100">
                            <img src="<?= htmlspecialchars($alb['cover_image']) ?>" class="card-img-top" style="height: 160px; object-fit: cover;" alt="" onerror="this.src='https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80'">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="fw-bold"><?= htmlspecialchars($alb['title']) ?></h6>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($alb['description']) ?></p>
                                </div>
                                <a href="gallery.php?action=delete&id=<?= $alb['id'] ?>" class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="return confirm('Delete this album?');"><i class="bi bi-trash"></i> Delete Album</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted py-4">No photo albums found.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="border-top d-flex justify-content-between align-items-center mt-4 pt-3">
                    <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="gallery.php?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                    <a class="page-link" href="gallery.php?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="gallery.php?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
