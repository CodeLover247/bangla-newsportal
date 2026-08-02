<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $c_id = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'approve') {
        $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$c_id]);
    } elseif ($act === 'unapprove') {
        $db->prepare("UPDATE comments SET status = 'pending' WHERE id = ?")->execute([$c_id]);
    } elseif ($act === 'delete') {
        $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$c_id]);
    }
    header('Location: comments.php');
    exit;
}

$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "c.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.comment LIKE ? OR p.title LIKE ?)";
    $sParam = "%{$search}%";
    $params[] = $sParam;
    $params[] = $sParam;
    $params[] = $sParam;
    $params[] = $sParam;
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM comments c LEFT JOIN posts p ON c.post_id = p.id {$whereSQL}");
$countStmt->execute($params);
$total_comments = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_comments / $limit);

// Data
$sql = "SELECT c.*, p.title as post_title FROM comments c LEFT JOIN posts p ON c.post_id = p.id {$whereSQL} ORDER BY c.id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Comment Moderation Queue</h3>
        <p class="text-muted small mb-0">Total <?= number_format($total_comments) ?> comments found</p>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm p-3 mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-md-6">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?= $status === 'all' ? 'active bg-danger' : '' ?>" href="comments.php?status=all&search=<?= urlencode($search) ?>">All</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status === 'pending' ? 'active bg-danger' : '' ?>" href="comments.php?status=pending&search=<?= urlencode($search) ?>">Pending</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status === 'approved' ? 'active bg-danger' : '' ?>" href="comments.php?status=approved&search=<?= urlencode($search) ?>">Approved</a>
                </li>
            </ul>
        </div>
        <div class="col-md-6">
            <form method="GET" action="comments.php" class="d-flex gap-2">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, or article..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="comments.php?status=<?= urlencode($status) ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User / Email</th>
                    <th>Comment Text</th>
                    <th>Article</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($comments)): foreach ($comments as $c): ?>
                    <tr>
                        <td>
                            <strong class="text-dark"><?= htmlspecialchars($c['name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($c['email']) ?></small>
                        </td>
                        <td style="max-width: 280px;"><?= htmlspecialchars($c['comment']) ?></td>
                        <td><small class="fw-semibold text-truncate d-block" style="max-width: 180px;"><?= htmlspecialchars($c['post_title'] ?? 'N/A') ?></small></td>
                        <td>
                            <?php if ($c['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= time_ago($c['created_at']) ?></small></td>
                        <td class="text-end">
                            <?php if ($c['status'] === 'pending'): ?>
                                <a href="comments.php?action=approve&id=<?= $c['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</a>
                            <?php else: ?>
                                <a href="comments.php?action=unapprove&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning" title="Unapprove"><i class="bi bi-pause-circle"></i></a>
                            <?php endif; ?>
                            <a href="comments.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete this comment?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No comments found matching filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
            <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                            <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
