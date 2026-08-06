<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bulk Actions for Pages
    if (isset($_POST['bulk_action']) && !empty($_POST['page_ids'])) {
        $bulk_action = $_POST['bulk_action'];
        $page_ids = array_map('intval', (array)$_POST['page_ids']);
        if (!empty($page_ids)) {
            $in_clause = implode(',', array_fill(0, count($page_ids), '?'));
            if ($bulk_action === 'delete') {
                $stmtBulk = $db->prepare("DELETE FROM pages WHERE id IN ($in_clause)");
                $stmtBulk->execute($page_ids);
                header('Location: pages.php?msg=bulk_deleted');
                exit;
            } elseif ($bulk_action === 'publish') {
                $stmtBulk = $db->prepare("UPDATE pages SET status = 1 WHERE id IN ($in_clause)");
                $stmtBulk->execute($page_ids);
                header('Location: pages.php?msg=bulk_updated');
                exit;
            } elseif ($bulk_action === 'draft') {
                $stmtBulk = $db->prepare("UPDATE pages SET status = 0 WHERE id IN ($in_clause)");
                $stmtBulk->execute($page_ids);
                header('Location: pages.php?msg=bulk_updated');
                exit;
            }
        }
    }

    $title = trim($_POST['title'] ?? '');
    $raw_slug = trim($_POST['slug'] ?? '');
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    $slug = get_unique_slug('pages', !empty($raw_slug) ? $raw_slug : $title, $edit_id);
    $content = $_POST['content'] ?? '';
    $views = max(0, (int)($_POST['views'] ?? 0));
    $created_at = !empty($_POST['created_at']) ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');

    if (!empty($title) && !empty($content)) {
        if (isset($_POST['edit_id']) && $_POST['edit_id'] > 0) {
            $stmt = $db->prepare("UPDATE pages SET title=?, slug=?, content=?, views=?, created_at=? WHERE id=?");
            $stmt->execute([$title, $slug, $content, $views, $created_at, (int)$_POST['edit_id']]);
            $msg = "Page updated successfully!";
        } else {
            $stmt = $db->prepare("INSERT INTO pages (title, slug, content, views, created_at, status) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$title, $slug, $content, $views, $created_at]);
            $msg = "Page created successfully!";
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM pages WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: pages.php?msg=deleted');
    exit;
}

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR slug LIKE ? OR content LIKE ?)";
    $sParam = "%{$search}%";
    $params[] = $sParam;
    $params[] = $sParam;
    $params[] = $sParam;
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM pages {$whereSQL}");
$countStmt->execute($params);
$total_pages_count = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_pages_count / $limit);

// Pages list
$sql = "SELECT * FROM pages {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll();

$edit_page = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmtEdit = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmtEdit->execute([(int)$_GET['id']]);
    $edit_page = $stmtEdit->fetch();
}
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3"><?= $edit_page ? 'Edit Custom Page' : 'Create Custom Page' ?></h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

            <form action="pages.php" method="POST">
                <?php if ($edit_page): ?><input type="hidden" name="edit_id" value="<?= $edit_page['id'] ?>"><?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Page Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($edit_page['title'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Page Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($edit_page['slug'] ?? '') ?>" placeholder="Auto-generated if blank">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Page Content *</label>
                    <textarea name="content" id="editor" class="form-control" rows="8"><?= htmlspecialchars($edit_page['content'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">View Count</label>
                    <input type="number" name="views" class="form-control" value="<?= (int)($edit_page['views'] ?? 0) ?>" min="0">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Published / Created Date</label>
                    <input type="datetime-local" name="created_at" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($edit_page['created_at'] ?? 'now')) ?>">
                </div>

                <button type="submit" class="btn btn-danger w-100 fw-bold"><?= $edit_page ? 'Update Page' : 'Publish Page' ?></button>
                <?php if ($edit_page): ?>
                    <a href="pages.php" class="btn btn-link text-secondary w-100 mt-2">Cancel Editing</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <!-- Search Filter -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <form method="GET" action="pages.php">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search page title or slug..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="pages.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form method="POST" action="pages.php" id="bulkPagesForm">
            <!-- Bulk Actions Toolbar -->
            <div class="card border-0 shadow-sm p-3 mb-3 bg-light">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-bold small text-uppercase text-muted"><i class="bi bi-check2-square me-1"></i> Bulk Action:</span>
                    <select name="bulk_action" id="bulkPageActionSelect" class="form-select form-select-sm" style="max-width: 220px;" required>
                        <option value="">-- Choose Action --</option>
                        <option value="publish">Publish / Activate</option>
                        <option value="draft">Move to Draft</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('Apply bulk action to selected page(s)?');"><i class="bi bi-play-fill me-1"></i> Apply Action</button>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <input type="checkbox" id="selectAllPages" class="form-check-input">
                                </th>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Views</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pages)): foreach ($pages as $pg): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="page_ids[]" value="<?= $pg['id'] ?>" class="form-check-input page-cb">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($pg['title']) ?></td>
                                    <td><code><?= htmlspecialchars($pg['slug']) ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><i class="bi bi-eye me-1"></i><?= number_format($pg['views'] ?? 0) ?></span></td>
                                    <td><small><?= date('M j, Y', strtotime($pg['created_at'])) ?></small></td>
                                    <td class="text-end">
                                        <a href="../page.php?slug=<?= $pg['slug'] ?>" target="_blank" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="pages.php?action=edit&id=<?= $pg['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="pages.php?action=delete&id=<?= $pg['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete this page?');" title="Delete"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No custom pages found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAllPages = document.getElementById('selectAllPages');
    if (selectAllPages) {
        selectAllPages.addEventListener('change', function() {
            var cbs = document.querySelectorAll('.page-cb');
            cbs.forEach(function(cb) { cb.checked = selectAllPages.checked; });
        });
    }
});
</script>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
                    <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="pages.php?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                    <a class="page-link" href="pages.php?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="pages.php?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
if (typeof CKEDITOR !== 'undefined') {
    CKEDITOR.config.versionCheck = false;
    CKEDITOR.replace('editor');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
