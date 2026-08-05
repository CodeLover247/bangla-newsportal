<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';
$err = '';

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['media_file']['name'])) {
    $res = handle_file_upload($_FILES['media_file'], 'media');
    if ($res['success']) {
        $msg = "Media file uploaded successfully!";
    } else {
        $err = "Upload failed: " . $res['error'];
    }
}

// Handle Single Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $m_id = (int)$_GET['id'];
    $stmtM = $db->prepare("SELECT filepath FROM media WHERE id = ?");
    $stmtM->execute([$m_id]);
    $mRow = $stmtM->fetch();
    if ($mRow && !empty($mRow['filepath'])) {
        $realPath = __DIR__ . '/../' . ltrim($mRow['filepath'], '/');
        if (file_exists($realPath)) {
            @unlink($realPath);
        }
    }
    $db->prepare("DELETE FROM media WHERE id = ?")->execute([$m_id]);
    header('Location: media.php?msg=deleted');
    exit;
}

// Handle Bulk Multi-Select Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_media'])) {
    $bulk_ids = $_POST['media_ids'] ?? [];
    $bulk_ids = array_map('intval', array_filter($bulk_ids));

    if (!empty($bulk_ids)) {
        $placeholders = implode(',', array_fill(0, count($bulk_ids), '?'));
        // Find file paths to unlink
        $stmtM = $db->prepare("SELECT filepath FROM media WHERE id IN ($placeholders)");
        $stmtM->execute($bulk_ids);
        $fileRows = $stmtM->fetchAll() ?: [];
        foreach ($fileRows as $fr) {
            if (!empty($fr['filepath'])) {
                $realPath = __DIR__ . '/../' . ltrim($fr['filepath'], '/');
                if (file_exists($realPath)) {
                    @unlink($realPath);
                }
            }
        }
        $db->prepare("DELETE FROM media WHERE id IN ($placeholders)")->execute($bulk_ids);
        $msg = count($bulk_ids) . " media files deleted successfully.";
    } else {
        $err = "No media files selected for deletion.";
    }
}

// Parameters
$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['type'] ?? '');
$view_mode = ($_GET['view'] ?? 'grid') === 'list' ? 'list' : 'grid';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 24;
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
    $where[] = "(filetype LIKE 'image/%' OR filename LIKE '%.jpg' OR filename LIKE '%.jpeg' OR filename LIKE '%.png' OR filename LIKE '%.webp' OR filename LIKE '%.gif' OR filename LIKE '%.svg')";
} elseif ($filter_type === 'video') {
    $where[] = "(filetype LIKE 'video/%' OR filename LIKE '%.mp4' OR filename LIKE '%.webm' OR filename LIKE '%.mov')";
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count Total Items
$countStmt = $db->prepare("SELECT COUNT(*) FROM media {$whereSQL}");
$countStmt->execute($params);
$total_media = (int)$countStmt->fetchColumn();
$total_pages = max(1, ceil($total_media / $limit));

// Fetch Paginated Items
$sql = "SELECT * FROM media {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$media_items = $stmt->fetchAll() ?: [];

// URL query string helpers
$query_params = $_GET;
unset($query_params['page']);
$base_qs = http_build_query($query_params);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-images text-danger me-2"></i> Media Manager</h3>
        <p class="text-muted small mb-0">Total <?= number_format($total_media) ?> uploaded media files (Images, Videos, Documents)</p>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i> Media file deleted successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Upload Box -->
<div class="card p-4 shadow-sm border-0 mb-4 bg-white rounded-3">
    <h5 class="fw-bold mb-2"><i class="bi bi-cloud-arrow-up text-danger me-2"></i> Upload New Media File</h5>
    <p class="text-muted small mb-3">Upload images (JPG, PNG, WEBP, GIF, SVG) or videos (MP4, WEBM, MOV) into the central site storage.</p>
    <form action="media.php" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
        <div class="col-md-9">
            <input type="file" name="media_file" class="form-control form-control-lg" required accept="image/*,video/*,.pdf,.zip">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold"><i class="bi bi-upload me-1"></i> Upload File</button>
        </div>
    </form>
</div>

<!-- Search, Filter & View Mode Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-3">
    <form method="GET" action="media.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search filename or path..." value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($filter_type)): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>"><?php endif; ?>
                <?php if ($view_mode !== 'grid'): ?><input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>"><?php endif; ?>
                <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                <?php if (!empty($search) || !empty($filter_type)): ?>
                    <a href="media.php?view=<?= $view_mode ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4 text-center">
            <div class="btn-group btn-group-sm">
                <a href="media.php?search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" class="btn btn-<?= empty($filter_type) ? 'danger' : 'outline-secondary' ?>">All Files</a>
                <a href="media.php?type=image&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" class="btn btn-<?= $filter_type === 'image' ? 'danger' : 'outline-secondary' ?>"><i class="bi bi-image me-1"></i> Images</a>
                <a href="media.php?type=video&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" class="btn btn-<?= $filter_type === 'video' ? 'danger' : 'outline-secondary' ?>"><i class="bi bi-play-btn me-1"></i> Videos</a>
            </div>
        </div>

        <div class="col-md-3 text-md-end">
            <!-- Grid vs List View Toggle -->
            <div class="btn-group btn-group-sm">
                <?php
                $grid_url = 'media.php?' . http_build_query(array_merge($_GET, ['view' => 'grid']));
                $list_url = 'media.php?' . http_build_query(array_merge($_GET, ['view' => 'list']));
                ?>
                <a href="<?= $grid_url ?>" class="btn btn-<?= $view_mode === 'grid' ? 'dark' : 'outline-dark' ?>" title="Grid View">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Grid
                </a>
                <a href="<?= $list_url ?>" class="btn btn-<?= $view_mode === 'list' ? 'dark' : 'outline-dark' ?>" title="List View">
                    <i class="bi bi-list-ul me-1"></i> List
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Multi-Select Form & Media Gallery Container -->
<form action="media.php?<?= $base_qs ?>&page=<?= $page ?>" method="POST" id="mediaBulkForm">
    <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
        <!-- Multi-Select Toolbar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="selectAllMedia" onclick="toggleSelectAllMedia(this)">
                    <label class="form-check-label fw-bold small text-dark" for="selectAllMedia">Select All</label>
                </div>
                <span class="vr"></span>
                <span class="small text-muted" id="selectedMediaCountLabel">0 files selected</span>
            </div>
            <div>
                <button type="submit" name="bulk_delete_media" value="1" class="btn btn-sm btn-outline-danger fw-bold" onclick="return confirm('Delete all selected media files permanently?');">
                    <i class="bi bi-trash me-1"></i> Delete Selected Files
                </button>
            </div>
        </div>

        <?php if ($view_mode === 'grid'): ?>
            <!-- GRID VIEW -->
            <div class="row g-3">
                <?php if (!empty($media_items)): foreach ($media_items as $m): 
                    $isVideo = strpos($m['filetype'], 'video') !== false || in_array(strtolower(pathinfo($m['filename'], PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov']);
                    $fileUrl = $m['filepath'];
                ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <div class="card border shadow-sm h-100 p-2 text-center bg-light position-relative media-card">
                            <div class="position-absolute top-0 start-0 m-2 z-1">
                                <input type="checkbox" name="media_ids[]" value="<?= $m['id'] ?>" class="form-check-input media-row-checkbox" onchange="updateSelectedMediaCount()">
                            </div>

                            <?php if ($isVideo): ?>
                                <div class="bg-dark text-white rounded mb-2 d-flex align-items-center justify-content-center" style="height: 140px;">
                                    <i class="bi bi-file-earmark-play display-4 text-danger"></i>
                                </div>
                            <?php else: ?>
                                <img src="../<?= htmlspecialchars($m['filepath']) ?>" class="card-img-top rounded mb-2 object-fit-cover" style="height: 140px;" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&auto=format&fit=crop&q=80'">
                            <?php endif; ?>

                            <div class="card-body p-2 d-flex flex-column justify-content-between">
                                <div>
                                    <p class="small fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($m['filename']) ?>"><?= htmlspecialchars($m['filename']) ?></p>
                                    <span class="badge bg-secondary mb-2"><?= round($m['filesize'] / 1024, 1) ?> KB</span>
                                </div>
                                <div class="d-flex gap-1 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-dark w-100 py-1" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($fileUrl) ?>'); alert('URL Copied to Clipboard!');" title="Copy URL">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                    <a href="media.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Delete this media file?');" title="Delete File">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i class="bi bi-images fs-1 text-secondary d-block mb-2"></i>
                        No media files found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- LIST VIEW -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 80px;">Preview</th>
                            <th>Filename</th>
                            <th>File Path</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Upload Date</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($media_items)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No media files found matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($media_items as $m): 
                                $isVideo = strpos($m['filetype'], 'video') !== false || in_array(strtolower(pathinfo($m['filename'], PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov']);
                                $fileUrl = $m['filepath'];
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="media_ids[]" value="<?= $m['id'] ?>" class="form-check-input media-row-checkbox" onchange="updateSelectedMediaCount()">
                                    </td>
                                    <td>
                                        <?php if ($isVideo): ?>
                                            <div class="bg-dark text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="bi bi-play-circle text-danger fs-5"></i>
                                            </div>
                                        <?php else: ?>
                                            <img src="../<?= htmlspecialchars($m['filepath']) ?>" class="rounded object-fit-cover" style="width: 50px; height: 50px;" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=100&auto=format&fit=crop&q=80'">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($m['filename']) ?>"><?= htmlspecialchars($m['filename']) ?></div>
                                    </td>
                                    <td>
                                        <code class="small text-muted text-truncate d-inline-block" style="max-width: 260px;"><?= htmlspecialchars($m['filepath']) ?></code>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= round($m['filesize'] / 1024, 1) ?> KB</span></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['filetype'] ?: 'File') ?></span></td>
                                    <td><small class="text-muted"><?= !empty($m['created_at']) ? date('M d, Y', strtotime($m['created_at'])) : 'N/A' ?></small></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-dark me-1" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($fileUrl) ?>'); alert('URL Copied to Clipboard!');" title="Copy URL">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                        <a href="media.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this media file?');" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="border-top d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 gap-2">
                <span class="small text-muted">Showing <?= count($media_items) ?> of <?= $total_media ?> files (Page <?= $page ?> of <?= $total_pages ?>)</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="media.php?<?= $base_qs ?>&page=<?= $page - 1 ?>">&laquo; Prev</a>
                        </li>
                        <?php
                        $sp = max(1, $page - 2);
                        $ep = min($total_pages, $page + 2);
                        for ($i = $sp; $i <= $ep; $i++):
                        ?>
                            <li class="page-item <?= $page == $i ? 'active bg-danger border-danger' : '' ?>">
                                <a class="page-link" href="media.php?<?= $base_qs ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="media.php?<?= $base_qs ?>&page=<?= $page + 1 ?>">Next &raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</form>

<script>
function toggleSelectAllMedia(mainCheckbox) {
    const checkboxes = document.querySelectorAll('.media-row-checkbox');
    checkboxes.forEach(cb => cb.checked = mainCheckbox.checked);
    updateSelectedMediaCount();
}

function updateSelectedMediaCount() {
    const checked = document.querySelectorAll('.media-row-checkbox:checked');
    const label = document.getElementById('selectedMediaCountLabel');
    if (label) {
        label.innerText = checked.length + ' files selected';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
