<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = slugify($title);
    $url = trim($_POST['video_url'] ?? '');
    $thumb = trim($_POST['thumbnail'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    // Handle Direct Video Upload
    if (!empty($_FILES['video_file']['name'])) {
        $up = handle_file_upload($_FILES['video_file'], 'videos');
        if ($up['success']) {
            $url = $up['filepath'];
        }
    }

    // Handle Direct Thumbnail Upload
    if (!empty($_FILES['thumb_file']['name'])) {
        $upT = handle_file_upload($_FILES['thumb_file'], 'videos');
        if ($upT['success']) {
            $thumb = $upT['filepath'];
        }
    }

    if (!empty($title) && !empty($url)) {
        $stmt = $db->prepare("INSERT INTO videos (title, slug, video_url, thumbnail, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $url, $thumb, $desc]);
        $msg = "Video news published successfully!";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM videos WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: videos.php?msg=deleted');
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
$countStmt = $db->prepare("SELECT COUNT(*) FROM videos {$whereSQL}");
$countStmt->execute($params);
$total_videos = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_videos / $limit);

// Data
$sql = "SELECT * FROM videos {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3">Add Video Headline</h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success py-2 small alert-dismissible fade show">Video deleted successfully.</div><?php endif; ?>

            <form action="videos.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Video Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Video headline title...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">YouTube Embed / Video URL / Select Video</label>
                    <div class="input-group mb-2">
                        <input type="text" id="video_url_input" name="video_url" class="form-control" placeholder="https://youtube.com/embed/... or uploads/videos/file.mp4">
                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#video_url_input"><i class="bi bi-images me-1"></i> Media</button>
                    </div>
                    <label class="form-label fw-semibold small text-muted">Or Upload Direct MP4/Video File</label>
                    <input type="file" name="video_file" class="form-control form-control-sm" accept="video/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Thumbnail URL or Select Media</label>
                    <div class="input-group mb-2">
                        <input type="text" id="video_thumb_input" name="thumbnail" class="form-control" placeholder="uploads/videos/thumb.jpg">
                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#video_thumb_input"><i class="bi bi-images me-1"></i> Media</button>
                    </div>
                    <label class="form-label fw-semibold small text-muted">Or Upload Thumbnail Image</label>
                    <input type="file" name="thumb_file" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief video description..."></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-play-circle me-1"></i> Publish Video</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Search Filter -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <form method="GET" action="videos.php">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search video by title..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="videos.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm p-3">
            <h5 class="fw-bold mb-3">Video List</h5>
            <div class="row g-3">
                <?php if (!empty($videos)): foreach ($videos as $v): ?>
                    <div class="col-md-6">
                        <div class="card border shadow-sm h-100">
                            <div class="ratio ratio-16x9">
                                <iframe src="<?= htmlspecialchars($v['video_url']) ?>" title="<?= htmlspecialchars($v['title']) ?>" allowfullscreen></iframe>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($v['title']) ?></h6>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($v['description']) ?></p>
                                </div>
                                <a href="videos.php?action=delete&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="return confirm('Delete this video?');"><i class="bi bi-trash"></i> Delete Video</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted py-4">No video headlines found.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="border-top d-flex justify-content-between align-items-center mt-4 pt-3">
                    <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="videos.php?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                    <a class="page-link" href="videos.php?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="videos.php?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
