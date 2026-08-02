<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['media_file']['name'])) {
    $res = handle_file_upload($_FILES['media_file'], 'media');
    if ($res['success']) {
        $msg = "Media file uploaded successfully!";
    } else {
        $err = "Upload failed: " . $res['error'];
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM media WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: media.php?msg=deleted');
    exit;
}

$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(filename LIKE ? OR filepath LIKE ?)";
    $sParam = "%{$search}%";
    $params[] = $sParam;
    $params[] = $sParam;
}

if ($filter_type === 'image') {
    $where[] = "(filetype LIKE 'image/%' OR filename LIKE '%.jpg' OR filename LIKE '%.jpeg' OR filename LIKE '%.png' OR filename LIKE '%.webp' OR filename LIKE '%.gif')";
} elseif ($filter_type === 'video') {
    $where[] = "(filetype LIKE 'video/%' OR filename LIKE '%.mp4' OR filename LIKE '%.webm' OR filename LIKE '%.mov')";
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM media {$whereSQL}");
$countStmt->execute($params);
$total_media = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_media / $limit);

// Fetch
$sql = "SELECT * FROM media {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$media_items = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-images text-danger me-2"></i> Media Manager</h3>
        <p class="text-muted small mb-0">Total <?= number_format($total_media) ?> uploaded media files (Images, Videos, Docs)</p>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success alert-dismissible fade show">Media file deleted successfully.</div><?php endif; ?>

<!-- Upload Box -->
<div class="card p-4 shadow-sm border border-0 mb-4 bg-white">
    <h5 class="fw-bold mb-2"><i class="bi bi-cloud-arrow-up text-danger me-2"></i> Upload New Media (Image / Video)</h5>
    <p class="text-muted small mb-3">Upload images (JPG, PNG, WEBP, GIF, SVG) or videos (MP4, WEBM, MOV) safely into server library.</p>
    <form action="media.php" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
        <div class="col-md-9">
            <input type="file" name="media_file" class="form-control form-control-lg" required accept="image/*,video/*,.pdf,.zip">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold"><i class="bi bi-upload me-1"></i> Upload File</button>
        </div>
    </form>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 bg-white">
    <form method="GET" action="media.php" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search filename..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                <?php if (!empty($search) || !empty($filter_type)): ?>
                    <a href="media.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="btn-group btn-group-sm">
                <a href="media.php?search=<?= urlencode($search) ?>" class="btn btn-<?= empty($filter_type) ? 'danger' : 'outline-secondary' ?>">All Files</a>
                <a href="media.php?type=image&search=<?= urlencode($search) ?>" class="btn btn-<?= $filter_type === 'image' ? 'danger' : 'outline-secondary' ?>"><i class="bi bi-image me-1"></i> Images</a>
                <a href="media.php?type=video&search=<?= urlencode($search) ?>" class="btn btn-<?= $filter_type === 'video' ? 'danger' : 'outline-secondary' ?>"><i class="bi bi-play-btn me-1"></i> Videos</a>
            </div>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm p-3 bg-white">
    <h5 class="fw-bold mb-3">Media Library Items</h5>
    <div class="row g-3">
        <?php if (!empty($media_items)): foreach ($media_items as $m): 
            $isVideo = strpos($m['filetype'], 'video') !== false || in_array(strtolower(pathinfo($m['filename'], PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov']);
            $fileUrl = $m['filepath'];
        ?>
            <div class="col-md-3 col-sm-6">
                <div class="card border shadow-sm h-100 p-2 text-center bg-light">
                    <?php if ($isVideo): ?>
                        <div class="bg-dark text-white rounded mb-2 d-flex align-items-center justify-content-center" style="height: 160px;">
                            <i class="bi bi-file-earmark-play display-4 text-danger"></i>
                        </div>
                    <?php else: ?>
                        <img src="../<?= htmlspecialchars($m['filepath']) ?>" class="card-img-top rounded mb-2" style="height: 160px; object-fit: cover;" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80'">
                    <?php endif; ?>

                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                        <div>
                            <p class="small fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($m['filename']) ?>"><?= htmlspecialchars($m['filename']) ?></p>
                            <span class="badge bg-secondary mb-2"><?= round($m['filesize'] / 1024, 1) ?> KB</span>
                        </div>
                        <div class="d-flex gap-1 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-dark w-100" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($fileUrl) ?>'); alert('URL Copied to Clipboard!');"><i class="bi bi-clipboard"></i> Copy</button>
                            <a href="media.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this media file?');"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div class="col-12 text-center text-muted py-5">No media files found.</div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="border-top d-flex justify-content-between align-items-center mt-4 pt-3">
            <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="media.php?search=<?= urlencode($search) ?>&type=<?= urlencode($filter_type) ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                            <a class="page-link" href="media.php?search=<?= urlencode($search) ?>&type=<?= urlencode($filter_type) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="media.php?search=<?= urlencode($search) ?>&type=<?= urlencode($filter_type) ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
