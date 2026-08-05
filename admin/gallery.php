<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';
$edit_album = null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_delete']) && !empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        if (!empty($ids)) {
            $inClause = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM gallery_photos WHERE album_id IN ({$inClause})")->execute($ids);
            $db->prepare("DELETE FROM gallery_albums WHERE id IN ({$inClause})")->execute($ids);
            $msg = count($ids) . " photo albums deleted successfully!";
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = slugify($title);
        $cover = trim($_POST['cover_image'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $edit_id = (int)($_POST['edit_id'] ?? 0);

        if (!empty($_FILES['cover_file']['name'])) {
            $up = handle_file_upload($_FILES['cover_file'], 'gallery');
            if ($up['success']) {
                $cover = $up['filepath'];
            }
        }

        if (!empty($title)) {
            if ($edit_id > 0) {
                $stmt = $db->prepare("UPDATE gallery_albums SET title = ?, slug = ?, cover_image = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $cover, $desc, $edit_id]);
                $msg = "Photo album updated successfully!";
            } else {
                $stmt = $db->prepare("INSERT INTO gallery_albums (title, slug, cover_image, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $cover, $desc]);
                $msg = "Photo album created successfully!";
            }
        } else {
            $msg = "Album title is required.";
        }
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $del_id = (int)$_GET['id'];
        $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM gallery_albums WHERE id = ?")->execute([$del_id]);
        header('Location: gallery.php?msg=deleted');
        exit;
    } elseif ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $stmtEd = $db->prepare("SELECT * FROM gallery_albums WHERE id = ?");
        $stmtEd->execute([(int)$_GET['id']]);
        $edit_album = $stmtEd->fetch();
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
$countStmt = $db->prepare("SELECT COUNT(*) FROM gallery_albums {$whereSQL}");
$countStmt->execute($params);
$total_albums = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_albums / $limit);

// Data
$sql = "SELECT a.*, (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) as photo_count FROM gallery_albums a {$whereSQL} ORDER BY a.id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$albums = $stmt->fetchAll();
?>

<div class="row g-4">
    <!-- Add / Edit Album Form -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3"><?= $edit_album ? '<i class="bi bi-pencil me-1"></i> Edit Photo Album' : '<i class="bi bi-camera me-1"></i> Add Photo Album' ?></h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success py-2 small alert-dismissible fade show">Album deleted successfully.</div><?php endif; ?>

            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <?php if ($edit_album): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_album['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Album Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Cultural Fair Coverage" value="<?= htmlspecialchars($edit_album['title'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Cover Image URL or Select Media</label>
                    <div class="input-group mb-2">
                        <input type="text" id="album_cover_input" name="cover_image" class="form-control" placeholder="uploads/gallery/cover.jpg" value="<?= htmlspecialchars($edit_album['cover_image'] ?? '') ?>">
                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#album_cover_input"><i class="bi bi-images me-1"></i> Media</button>
                    </div>
                    <input type="file" name="cover_file" class="form-control form-control-sm" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief album summary..."><?= htmlspecialchars($edit_album['description'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-camera me-1"></i> <?= $edit_album ? 'Update Album' : 'Create Photo Album' ?></button>
                <?php if ($edit_album): ?>
                    <a href="gallery.php" class="btn btn-link text-muted w-100 text-decoration-none mt-1 small">Cancel Editing</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Album Manager List / Grid Area -->
    <div class="col-lg-8">
        <!-- Toolbar & Search -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <form method="GET" action="gallery.php" class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 450px;">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Search album title..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i></button>
                        <?php if (!empty($search)): ?>
                            <a href="gallery.php?view=<?= $view ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="btn-group btn-group-sm">
                    <a href="gallery.php?view=grid&search=<?= urlencode($search) ?>" class="btn <?= $view === 'grid' ? 'btn-danger' : 'btn-outline-secondary' ?>"><i class="bi bi-grid-fill me-1"></i> Grid View</a>
                    <a href="gallery.php?view=list&search=<?= urlencode($search) ?>" class="btn <?= $view === 'list' ? 'btn-danger' : 'btn-outline-secondary' ?>"><i class="bi bi-list-ul me-1"></i> List View</a>
                </div>
            </div>
        </div>

        <form action="gallery.php" method="POST" id="bulk_gallery_form">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Photo Albums (<?= $total_albums ?> items)</h5>
                    <button type="submit" name="bulk_delete" value="1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete selected photo albums?');"><i class="bi bi-trash me-1"></i> Delete Selected</button>
                </div>

                <?php if ($view === 'grid'): ?>
                    <!-- GRID VIEW -->
                    <div class="row g-3">
                        <?php if (!empty($albums)): foreach ($albums as $alb): ?>
                            <div class="col-md-6">
                                <div class="card border shadow-sm h-100 position-relative">
                                    <div class="position-absolute top-0 start-0 p-2 z-3 bg-dark bg-opacity-50 rounded-bottom-end">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $alb['id'] ?>" class="form-check-input">
                                    </div>
                                    <img src="<?= htmlspecialchars($alb['cover_image']) ?>" class="card-img-top" style="height: 160px; object-fit: cover;" alt="" onerror="this.src='https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80'">
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($alb['title']) ?></h6>
                                                <span class="badge bg-secondary"><?= $alb['photo_count'] ?> Photos</span>
                                            </div>
                                            <p class="small text-muted mb-2"><?= htmlspecialchars($alb['description']) ?></p>
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            <a href="gallery.php?action=edit&id=<?= $alb['id'] ?>&view=<?= $view ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-pencil"></i> Edit</a>
                                            <a href="gallery.php?action=delete&id=<?= $alb['id'] ?>" class="btn btn-sm btn-outline-danger flex-fill" onclick="return confirm('Delete this album?');"><i class="bi bi-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="col-12 text-center text-muted py-4">No photo albums found.</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- LIST VIEW -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('input[name=\'selected_ids[]\']').forEach(c => c.checked = this.checked)"></th>
                                    <th width="100">Cover</th>
                                    <th>Album Title & Description</th>
                                    <th>Photo Count</th>
                                    <th width="120" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($albums)): foreach ($albums as $alb): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_ids[]" value="<?= $alb['id'] ?>" class="form-check-input"></td>
                                        <td>
                                            <img src="<?= !empty($alb['cover_image']) ? htmlspecialchars($alb['cover_image']) : 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=200&auto=format&fit=crop&q=80' ?>" style="width: 70px; height: 50px; object-fit: cover;" class="rounded border">
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($alb['title']) ?></div>
                                            <small class="text-muted d-block text-truncate" style="max-width: 280px;"><?= htmlspecialchars($alb['description']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $alb['photo_count'] ?> photos</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="gallery.php?action=edit&id=<?= $alb['id'] ?>&view=<?= $view ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <a href="gallery.php?action=delete&id=<?= $alb['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this album?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No photo albums found.</td></tr>
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
                                    <a class="page-link" href="gallery.php?search=<?= urlencode($search) ?>&view=<?= $view ?>&page=<?= $page - 1 ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                        <a class="page-link" href="gallery.php?search=<?= urlencode($search) ?>&view=<?= $view ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="gallery.php?search=<?= urlencode($search) ?>&view=<?= $view ?>&page=<?= $page + 1 ?>">Next</a>
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
