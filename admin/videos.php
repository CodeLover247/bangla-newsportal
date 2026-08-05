<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';
$edit_video = null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_delete']) && !empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        if (!empty($ids)) {
            $inClause = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM videos WHERE id IN ({$inClause})")->execute($ids);
            $msg = count($ids) . " videos deleted successfully!";
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = slugify($title);
        $url = trim($_POST['video_url'] ?? '');
        $thumb = trim($_POST['thumbnail'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $edit_id = (int)($_POST['edit_id'] ?? 0);

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
            if ($edit_id > 0) {
                $stmt = $db->prepare("UPDATE videos SET title = ?, slug = ?, video_url = ?, thumbnail = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $url, $thumb, $desc, $edit_id]);
                $msg = "Video item updated successfully!";
            } else {
                $stmt = $db->prepare("INSERT INTO videos (title, slug, video_url, thumbnail, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $url, $thumb, $desc]);
                $msg = "Video news published successfully!";
            }
        } else {
            $msg = "Please provide both Video Title and Video URL.";
        }
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $db->prepare("DELETE FROM videos WHERE id = ?")->execute([(int)$_GET['id']]);
        header('Location: videos.php?msg=deleted');
        exit;
    } elseif ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $stmtEd = $db->prepare("SELECT * FROM videos WHERE id = ?");
        $stmtEd->execute([(int)$_GET['id']]);
        $edit_video = $stmtEd->fetch();
    }
}

$search = trim($_GET['search'] ?? '');
$view = $_GET['view'] ?? 'grid';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
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
    <!-- Add / Edit Video Form -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3"><?= $edit_video ? '<i class="bi bi-pencil me-1"></i> Edit Video' : '<i class="bi bi-plus-circle me-1"></i> Add Video Headline' ?></h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success py-2 small alert-dismissible fade show">Video deleted successfully.</div><?php endif; ?>

            <form action="videos.php" method="POST" enctype="multipart/form-data">
                <?php if ($edit_video): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_video['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Video Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Video headline title..." value="<?= htmlspecialchars($edit_video['title'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Video URL / YouTube / M3U Stream / Facebook *</label>
                    <div class="input-group mb-2">
                        <input type="text" id="video_url_input" name="video_url" class="form-control" placeholder="https://youtube.com/watch?v=... or .m3u8 stream" value="<?= htmlspecialchars($edit_video['video_url'] ?? '') ?>" required>
                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#video_url_input"><i class="bi bi-images me-1"></i> Media</button>
                    </div>
                    <small class="text-muted d-block mb-1">Supports YouTube Watch/Shorts, Facebook Videos, M3U/M3U8 Live Streams & MP4 files.</small>
                    <input type="file" name="video_file" class="form-control form-control-sm" accept="video/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Thumbnail Cover Image URL</label>
                    <div class="input-group mb-2">
                        <input type="text" id="video_thumb_input" name="thumbnail" class="form-control" placeholder="uploads/videos/thumb.jpg" value="<?= htmlspecialchars($edit_video['thumbnail'] ?? '') ?>">
                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#video_thumb_input"><i class="bi bi-images me-1"></i> Media</button>
                    </div>
                    <input type="file" name="thumb_file" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief video description..."><?= htmlspecialchars($edit_video['description'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-play-circle me-1"></i> <?= $edit_video ? 'Update Video Item' : 'Publish Video' ?></button>
                <?php if ($edit_video): ?>
                    <a href="videos.php" class="btn btn-link text-muted w-100 text-decoration-none mt-1 small">Cancel Editing</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Video Manager List / Grid Area -->
    <div class="col-lg-8">
        <!-- Search & Toolbar -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <form method="GET" action="videos.php" class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 450px;">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Search video by title or description..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i></button>
                        <?php if (!empty($search)): ?>
                            <a href="videos.php?view=<?= $view ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="btn-group btn-group-sm">
                    <a href="videos.php?view=grid&search=<?= urlencode($search) ?>" class="btn <?= $view === 'grid' ? 'btn-danger' : 'btn-outline-secondary' ?>"><i class="bi bi-grid-fill me-1"></i> Grid View</a>
                    <a href="videos.php?view=list&search=<?= urlencode($search) ?>" class="btn <?= $view === 'list' ? 'btn-danger' : 'btn-outline-secondary' ?>"><i class="bi bi-list-ul me-1"></i> List View</a>
                </div>
            </div>
        </div>

        <form action="videos.php" method="POST" id="bulk_video_form">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Video News Gallery (<?= $total_videos ?> items)</h5>
                    <button type="submit" name="bulk_delete" value="1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete selected videos?');"><i class="bi bi-trash me-1"></i> Delete Selected</button>
                </div>

                <?php if ($view === 'grid'): ?>
                    <!-- GRID VIEW -->
                    <div class="row g-3">
                        <?php if (!empty($videos)): foreach ($videos as $v): 
                            $vFormat = format_video_embed_url($v['video_url']);
                        ?>
                            <div class="col-md-6">
                                <div class="card border shadow-sm h-100 position-relative">
                                    <div class="position-absolute top-0 start-0 p-2 z-3 bg-dark bg-opacity-50 rounded-bottom-end">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $v['id'] ?>" class="form-check-input">
                                    </div>
                                    <div class="ratio ratio-16x9">
                                        <?php if ($vFormat['isHls'] || $vFormat['isDirectMp4']): ?>
                                            <video src="<?= htmlspecialchars($vFormat['embedUrl']) ?>" controls poster="<?= htmlspecialchars($v['thumbnail']) ?>" style="max-height:220px; width:100%; object-fit:cover;"></video>
                                        <?php else: ?>
                                            <iframe src="<?= htmlspecialchars($vFormat['embedUrl']) ?>" title="<?= htmlspecialchars($v['title']) ?>" allowfullscreen></iframe>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($v['title']) ?></h6>
                                            <p class="small text-muted mb-2"><?= htmlspecialchars($v['description']) ?></p>
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            <a href="videos.php?action=edit&id=<?= $v['id'] ?>&view=<?= $view ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-pencil"></i> Edit</a>
                                            <a href="videos.php?action=delete&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger flex-fill" onclick="return confirm('Delete this video?');"><i class="bi bi-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="col-12 text-center text-muted py-4">No video headlines found.</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- LIST VIEW -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('input[name=\'selected_ids[]\']').forEach(c => c.checked = this.checked)"></th>
                                    <th width="100">Media</th>
                                    <th>Title & Description</th>
                                    <th>Video URL</th>
                                    <th width="120" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($videos)): foreach ($videos as $v): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_ids[]" value="<?= $v['id'] ?>" class="form-check-input"></td>
                                        <td>
                                            <img src="<?= !empty($v['thumbnail']) ? htmlspecialchars($v['thumbnail']) : 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=200&auto=format&fit=crop&q=80' ?>" style="width: 80px; height: 50px; object-fit: cover;" class="rounded border">
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($v['title']) ?></div>
                                            <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($v['description']) ?></small>
                                        </td>
                                        <td>
                                            <small class="text-primary text-truncate d-block" style="max-width: 200px;"><?= htmlspecialchars($v['video_url']) ?></small>
                                        </td>
                                        <td class="text-end">
                                            <a href="videos.php?action=edit&id=<?= $v['id'] ?>&view=<?= $view ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <a href="videos.php?action=delete&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this video?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No video headlines found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="border-top d-flex justify-content-between align-items-center mt-4 pt-3">
                        <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="videos.php?search=<?= urlencode($search) ?>&view=<?= $view ?>&page=<?= $page - 1 ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                        <a class="page-link" href="videos.php?search=<?= urlencode($search) ?>&view=<?= $view ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="videos.php?search=<?= urlencode($search) ?>&view=<?= $view ?>&page=<?= $page + 1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
