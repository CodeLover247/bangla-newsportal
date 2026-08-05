<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Approve Pending Post Action
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    if (has_role_permission(['admin', 'editor'])) {
        $app_id = (int)$_GET['id'];
        $stmtApp = $db->prepare("UPDATE posts SET status = 'published', publish_date = CURRENT_TIMESTAMP WHERE id = ?");
        $stmtApp->execute([$app_id]);
        header('Location: posts.php?status=pending&msg=approved');
        exit;
    }
}

// Delete Single Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    if ($admin_role === 'reporter') {
        $chk = $db->prepare("SELECT author_id FROM posts WHERE id = ?");
        $chk->execute([$del_id]);
        $row = $chk->fetch();
        if ($row && (int)$row['author_id'] !== (int)$_SESSION['admin_id']) {
            header('Location: posts.php?error=unauthorized');
            exit;
        }
    }
    $stmtDel = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmtDel->execute([$del_id]);
    header('Location: posts.php?msg=deleted');
    exit;
}

// Bulk Actions Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['post_ids'])) {
    $bulk_action = $_POST['bulk_action'];
    $post_ids = array_map('intval', (array)$_POST['post_ids']);
    if (!empty($post_ids)) {
        $in_clause = implode(',', array_fill(0, count($post_ids), '?'));

        if ($bulk_action === 'publish') {
            $stmtBulk = $db->prepare("UPDATE posts SET status = 'published' WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_updated');
            exit;
        } elseif ($bulk_action === 'draft') {
            $stmtBulk = $db->prepare("UPDATE posts SET status = 'draft' WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_updated');
            exit;
        } elseif ($bulk_action === 'delete') {
            $stmtBulk = $db->prepare("DELETE FROM posts WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_deleted');
            exit;
        } elseif ($bulk_action === 'change_category' && !empty($_POST['bulk_category_id'])) {
            $target_cat = (int)$_POST['bulk_category_id'];
            if ($target_cat > 0) {
                $params = array_merge([$target_cat], $post_ids);
                $stmtBulk = $db->prepare("UPDATE posts SET category_id = ? WHERE id IN ($in_clause)");
                $stmtBulk->execute($params);
                header('Location: posts.php?msg=bulk_updated');
                exit;
            }
        } elseif ($bulk_action === 'mark_featured') {
            $stmtBulk = $db->prepare("UPDATE posts SET is_featured = 1 WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_updated');
            exit;
        } elseif ($bulk_action === 'unmark_featured') {
            $stmtBulk = $db->prepare("UPDATE posts SET is_featured = 0 WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_updated');
            exit;
        } elseif ($bulk_action === 'mark_breaking') {
            $stmtBulk = $db->prepare("UPDATE posts SET is_breaking = 1 WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_updated');
            exit;
        } elseif ($bulk_action === 'unmark_breaking') {
            $stmtBulk = $db->prepare("UPDATE posts SET is_breaking = 0 WHERE id IN ($in_clause)");
            $stmtBulk->execute($post_ids);
            header('Location: posts.php?msg=bulk_updated');
            exit;
        }
    }
}

$status = isset($_GET['status']) ? $_GET['status'] : 'published';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$author_id = isset($_GET['author_id']) ? (int)$_GET['author_id'] : 0;
$flag = isset($_GET['flag']) ? trim($_GET['flag']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$order_by = isset($_GET['order_by']) ? trim($_GET['order_by']) : 'p.publish_date DESC, p.id DESC';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$categories = get_categories();
$users = $db->query("SELECT id, full_name, username FROM users ORDER BY full_name ASC")->fetchAll() ?: [];

$options = [
    'status' => $status,
    'search' => $search,
    'category_id' => $category_id,
    'author_id' => $author_id,
    'flag' => $flag,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'order_by' => $order_by,
    'limit' => $limit,
    'offset' => $offset
];

$pending_count = 0;
try {
    $pending_count = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {}

$posts = get_posts($options);
$total_posts = get_posts_count(['status' => $status, 'search' => $search, 'category_id' => $category_id, 'author_id' => $author_id, 'flag' => $flag, 'date_from' => $date_from, 'date_to' => $date_to]);
$total_pages = max(1, ceil($total_posts / $limit));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-newspaper text-danger me-2"></i>Post Management</h3>
        <p class="text-muted small mb-0">Total <?= number_format($total_posts) ?> posts found matching filter criteria</p>
    </div>
    <a href="post-add.php" class="btn btn-danger fw-bold"><i class="bi bi-plus-lg me-1"></i> Add New Post</a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i> Post approved and published successfully!</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show">Post deleted successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'bulk_updated'): ?>
    <div class="alert alert-success alert-dismissible fade show">Selected articles updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'bulk_deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show">Selected articles deleted successfully.</div>
<?php endif; ?>

<!-- Status Tabs & Advanced Search / Filter Bar -->
<div class="card border-0 shadow-sm p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= $status === 'published' ? 'active bg-danger' : '' ?>" href="posts.php?status=published">Published</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $status === 'pending' ? 'active bg-warning text-dark fw-bold' : '' ?>" href="posts.php?status=pending">
                    Pending Approval (অনুমোদনের অপেক্ষায়)
                    <?php if ($pending_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-1"><?= $pending_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $status === 'draft' ? 'active bg-danger' : '' ?>" href="posts.php?status=draft">Drafts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $status === 'scheduled' ? 'active bg-danger' : '' ?>" href="posts.php?status=scheduled">Scheduled</a>
            </li>
        </ul>
        <span class="badge bg-light text-dark border"><i class="bi bi-funnel-fill text-danger me-1"></i> Advanced Filter Panel</span>
    </div>

    <!-- Advanced Filter Form -->
    <form method="GET" action="posts.php">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        
        <div class="row g-2 mb-2">
            <!-- Keyword / Search Input -->
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-search me-1"></i> Search Title / Tags / Reporter</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="খবরের শিরোনাম বা কিওয়ার্ড টাইপ করুন..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Category Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-folder me-1"></i> Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Author / User Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-person me-1"></i> Author / Reporter</label>
                <select name="author_id" class="form-select form-select-sm">
                    <option value="0">All Authors</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $author_id == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Flag Filter -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-star me-1"></i> Special Badge</label>
                <select name="flag" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="featured" <?= $flag === 'featured' ? 'selected' : '' ?>>Featured (নির্বাচিত)</option>
                    <option value="breaking" <?= $flag === 'breaking' ? 'selected' : '' ?>>Breaking (জরুরি)</option>
                    <option value="trending" <?= $flag === 'trending' ? 'selected' : '' ?>>Trending</option>
                    <option value="popular" <?= $flag === 'popular' ? 'selected' : '' ?>>Popular</option>
                </select>
            </div>
        </div>

        <div class="row g-2 align-items-end">
            <!-- Date From -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-calendar-event me-1"></i> From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
            </div>

            <!-- Date To -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-calendar-check me-1"></i> To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
            </div>

            <!-- Sort By -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-sort-down me-1"></i> Sort By</label>
                <select name="order_by" class="form-select form-select-sm">
                    <option value="p.publish_date DESC, p.id DESC" <?= $order_by === 'p.publish_date DESC, p.id DESC' ? 'selected' : '' ?>>Newest First (সর্বশেষ)</option>
                    <option value="p.publish_date ASC" <?= $order_by === 'p.publish_date ASC' ? 'selected' : '' ?>>Oldest First (পুরাতন)</option>
                    <option value="p.views DESC" <?= $order_by === 'p.views DESC' ? 'selected' : '' ?>>Most Viewed (জনপ্রিয়)</option>
                    <option value="p.title ASC" <?= $order_by === 'p.title ASC' ? 'selected' : '' ?>>Title A-Z</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-danger btn-sm w-100 fw-bold" type="submit"><i class="bi bi-filter me-1"></i> Apply Filter</button>
                <?php if (!empty($search) || $category_id > 0 || $author_id > 0 || !empty($flag) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="posts.php?status=<?= urlencode($status) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<form method="POST" action="posts.php" id="bulkPostsForm">
    <!-- Bulk Actions Toolbar -->
    <div class="card border-0 shadow-sm p-3 mb-3 bg-light">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold small text-uppercase text-muted"><i class="bi bi-check2-square me-1"></i> Bulk Action:</span>
            <select name="bulk_action" id="bulkActionSelect" class="form-select form-select-sm style-select" style="max-width: 220px;" required>
                <option value="">-- Choose Action --</option>
                <option value="publish">Publish Selected</option>
                <option value="draft">Move to Drafts</option>
                <option value="change_category">Change Category</option>
                <option value="mark_featured">Mark as Featured</option>
                <option value="unmark_featured">Remove Featured</option>
                <option value="mark_breaking">Mark as Breaking Ticker</option>
                <option value="unmark_breaking">Remove Breaking Ticker</option>
                <option value="delete">Delete Selected</option>
            </select>

            <select name="bulk_category_id" id="bulkCategorySelect" class="form-select form-select-sm" style="max-width: 200px; display: none;">
                <option value="">Select Target Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-sm btn-danger fw-bold" onclick="return confirmBulkAction();"><i class="bi bi-play-fill me-1"></i> Apply Action</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="selectAllPosts" class="form-check-input">
                        </th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Flags</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): foreach ($posts as $p): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="post_ids[]" value="<?= $p['id'] ?>" class="form-check-input post-cb">
                            </td>
                            <td>
                                <img src="<?= htmlspecialchars(get_media_url($p['featured_image'] ?? '')) ?>" class="rounded" style="width: 55px; height: 40px; object-fit: cover;" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=100&auto=format&fit=crop&q=80'">
                            </td>
                            <td>
                                <a href="../article.php?slug=<?= $p['slug'] ?>" target="_blank" class="text-dark fw-bold text-decoration-none"><?= htmlspecialchars($p['title']) ?></a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['category_name']) ?></span></td>
                            <td><?= htmlspecialchars($p['author_name']) ?></td>
                            <td>
                                <?php if ($p['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> Pending</span>
                                <?php elseif ($p['status'] === 'draft'): ?>
                                    <span class="badge bg-secondary">Draft</span>
                                <?php endif; ?>
                                <?php if ($p['is_featured']): ?><span class="badge bg-danger">Featured</span><?php endif; ?>
                                <?php if ($p['is_breaking']): ?><span class="badge bg-warning text-dark">Breaking</span><?php endif; ?>
                                <?php if ($p['is_trending']): ?><span class="badge bg-info text-dark">Trending</span><?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-eye me-1"></i><?= number_format($p['views']) ?></span></td>
                            <td><small><?= date('M j, Y', strtotime($p['publish_date'])) ?></small></td>
                            <td class="text-end text-nowrap">
                                <?php if ($p['status'] === 'pending' && has_role_permission(['admin', 'editor'])): ?>
                                    <a href="posts.php?action=approve&id=<?= $p['id'] ?>" class="btn btn-sm btn-success fw-bold me-1" onclick="return confirm('Approve and publish this post?');"><i class="bi bi-check-circle me-1"></i> Approve</a>
                                <?php endif; ?>
                                <a href="photocard.php?post_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger me-1" title="Generate Photocard"><i class="bi bi-card-image me-1"></i> Photocard</a>
                                <?php if ($admin_role !== 'reporter' || (int)$p['author_id'] === (int)$_SESSION['admin_id']): ?>
                                    <a href="post-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="posts.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Are you sure you want to delete this post?');" title="Delete"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No posts found matching your search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('selectAllPosts');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var cbs = document.querySelectorAll('.post-cb');
            cbs.forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }

    var bulkSelect = document.getElementById('bulkActionSelect');
    var catSelect = document.getElementById('bulkCategorySelect');
    if (bulkSelect && catSelect) {
        bulkSelect.addEventListener('change', function() {
            if (this.value === 'change_category') {
                catSelect.style.display = 'inline-block';
                catSelect.required = true;
            } else {
                catSelect.style.display = 'none';
                catSelect.required = false;
            }
        });
    }
});

function confirmBulkAction() {
    var selected = document.querySelectorAll('.post-cb:checked');
    if (selected.length === 0) {
        alert('Please select at least one article first.');
        return false;
    }
    var action = document.getElementById('bulkActionSelect').value;
    if (!action) {
        alert('Please choose an action from the dropdown.');
        return false;
    }
    if (action === 'delete') {
        return confirm('Are you sure you want to DELETE ' + selected.length + ' selected post(s)?');
    }
    return true;
}
</script>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
            <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-item page-link" href="posts.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&category_id=<?= $category_id ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                            <a class="page-link" href="posts.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&category_id=<?= $category_id ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="posts.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&category_id=<?= $category_id ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
