<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();

$msg = '';
$msg_type = 'success';

// Handle Global Comment Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_global_comments') {
    $current = get_setting('enable_comments', '1');
    $new_status = ($current === '1') ? '0' : '1';
    set_setting('enable_comments', $new_status);
    $msg = ($new_status === '1') ? 'গ্লোবাল কমেন্ট সেকশন সফলভাবে চালু করা হয়েছে (Comments Enabled Globally).' : 'গ্লোবাল কমেন্ট সেকশন সফলভাবে বন্ধ করা হয়েছে (Comments Disabled Globally).';
    $msg_type = ($new_status === '1') ? 'success' : 'warning';
}

// Handle Single Comment Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $c_id = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'approve') {
        $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$c_id]);
        $msg = "কমেন্ট অনুমোদন করা হয়েছে (Comment Approved).";
    } elseif ($act === 'unapprove') {
        $db->prepare("UPDATE comments SET status = 'pending' WHERE id = ?")->execute([$c_id]);
        $msg = "কমেন্ট পেন্ডিং অবস্থায় রাখা হয়েছে (Comment Marked Pending).";
    } elseif ($act === 'spam') {
        $db->prepare("UPDATE comments SET status = 'spam' WHERE id = ?")->execute([$c_id]);
        $msg = "কমেন্ট স্প্যাম চিহ্নিত করা হয়েছে (Comment Marked Spam).";
    } elseif ($act === 'delete') {
        $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$c_id]);
        $msg = "কমেন্ট মুছে ফেলা হয়েছে (Comment Deleted).";
    }
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['selected_comments'])) {
    $bulk_act = $_POST['bulk_action'];
    $ids = array_map('intval', $_POST['selected_comments']);
    if (!empty($ids)) {
        $in_clause = implode(',', array_fill(0, count($ids), '?'));
        if ($bulk_act === 'approve') {
            $stmt = $db->prepare("UPDATE comments SET status = 'approved' WHERE id IN ($in_clause)");
            $stmt->execute($ids);
            $msg = count($ids) . " টি কমেন্ট অনুমোদন করা হয়েছে।";
        } elseif ($bulk_act === 'unapprove') {
            $stmt = $db->prepare("UPDATE comments SET status = 'pending' WHERE id IN ($in_clause)");
            $stmt->execute($ids);
            $msg = count($ids) . " টি কমেন্ট পেন্ডিং স্ট্যাটাসে পরিবর্তন করা হয়েছে।";
        } elseif ($bulk_act === 'spam') {
            $stmt = $db->prepare("UPDATE comments SET status = 'spam' WHERE id IN ($in_clause)");
            $stmt->execute($ids);
            $msg = count($ids) . " টি কমেন্ট স্প্যাম চিহ্নিত করা হয়েছে।";
        } elseif ($bulk_act === 'delete') {
            $stmt = $db->prepare("DELETE FROM comments WHERE id IN ($in_clause)");
            $stmt->execute($ids);
            $msg = count($ids) . " টি কমেন্ট সফলভাবে মুছে ফেলা হয়েছে।";
        }
    }
}

// Filter inputs
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$post_filter = (int)($_GET['post_id'] ?? 0);
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Where conditions setup
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

if ($post_filter > 0) {
    $where[] = "c.post_id = ?";
    $params[] = $post_filter;
}

if (!empty($date_from)) {
    $where[] = "DATE(c.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(c.created_at) <= ?";
    $params[] = $date_to;
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count per status for tabs
$counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'spam' => 0];
try {
    $counts['all'] = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $counts['pending'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
    $counts['approved'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
    $counts['spam'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn();
} catch (Throwable $e) {}

// Filtered Count for Pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM comments c LEFT JOIN posts p ON c.post_id = p.id {$whereSQL}");
$countStmt->execute($params);
$total_comments = (int)$countStmt->fetchColumn();
$total_pages = max(1, ceil($total_comments / $limit));

// Fetch Comments
$sql = "SELECT c.*, p.title as post_title, p.slug as post_slug FROM comments c LEFT JOIN posts p ON c.post_id = p.id {$whereSQL} ORDER BY c.id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll() ?: [];

// Fetch distinct posts that have comments for the article dropdown
$posts_with_comments = [];
try {
    $posts_with_comments = $db->query("SELECT DISTINCT p.id, p.title FROM comments c JOIN posts p ON c.post_id = p.id ORDER BY p.title ASC LIMIT 100")->fetchAll() ?: [];
} catch (Throwable $e) {}

$global_comments = get_setting('enable_comments', '1');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-chat-left-quote-fill text-danger me-2"></i> Comment Moderation Queue</h3>
        <p class="text-muted small mb-0">Total <strong><?= number_format($total_comments) ?></strong> comments found matching current filter</p>
    </div>
    <div class="d-flex gap-2">
        <a href="update.php" class="btn btn-outline-dark btn-sm fw-bold"><i class="bi bi-arrow-repeat me-1"></i> Sync DB Schema</a>
        <a href="settings.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="bi bi-gear me-1"></i> Site Settings</a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm fw-bold mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Global Comment Status Quick Banner -->
<div class="card border-0 shadow-sm mb-4 bg-white border-start border-4 <?= $global_comments === '1' ? 'border-success' : 'border-danger' ?>">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="fs-2 <?= $global_comments === '1' ? 'text-success' : 'text-danger' ?>">
                <i class="bi bi-<?= $global_comments === '1' ? 'chat-square-check-fill' : 'chat-square-x-fill' ?>"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">
                    Global Reader Comments Status: 
                    <?php if ($global_comments === '1'): ?>
                        <span class="badge bg-success fs-6 ms-1"><i class="bi bi-check-circle me-1"></i> ON / ENABLED</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6 ms-1"><i class="bi bi-x-circle me-1"></i> OFF / DISABLED</span>
                    <?php endif; ?>
                </h6>
                <small class="text-muted">
                    <?php if ($global_comments === '1'): ?>
                        পাঠকবৃন্দ সকল প্রকাশিত নিউজে কমেন্ট করতে পারছেন। (Comments are active on all public articles).
                    <?php else: ?>
                        গ্লোবাল কমেন্ট বন্ধ আছে। পাঠকরা ওয়েবসাইটে নতুন কমেন্ট সাবমিট করতে পারবেন না। (Comments disabled globally).
                    <?php endif; ?>
                </small>
            </div>
        </div>
        <div>
            <a href="comments.php?action=toggle_global_comments" class="btn <?= $global_comments === '1' ? 'btn-outline-danger' : 'btn-success' ?> fw-bold btn-sm shadow-sm" onclick="return confirm('আপনি কি গ্লোবাল কমেন্ট সেকশন অন/অফ স্ট্যাটাস পরিবর্তন করতে চান?');">
                <i class="bi bi-power me-1"></i> <?= $global_comments === '1' ? 'Turn OFF Comments Globally' : 'Turn ON Comments Globally' ?>
            </a>
        </div>
    </div>
</div>

<!-- Tabs & Advanced Filter Panel -->
<div class="card border-0 shadow-sm p-3 mb-4 bg-white">
    <!-- Status Tabs -->
    <ul class="nav nav-pills border-bottom pb-3 mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link <?= $status === 'all' ? 'active bg-danger' : 'bg-light text-dark' ?> fw-semibold" href="comments.php?status=all&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&limit=<?= $limit ?>">
                All Comments <span class="badge bg-white text-danger ms-1"><?= $counts['all'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status === 'pending' ? 'active bg-warning text-dark' : 'bg-light text-dark' ?> fw-semibold" href="comments.php?status=pending&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&limit=<?= $limit ?>">
                <i class="bi bi-clock me-1"></i> Pending Approval <span class="badge bg-dark text-white ms-1"><?= $counts['pending'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status === 'approved' ? 'active bg-success' : 'bg-light text-dark' ?> fw-semibold" href="comments.php?status=approved&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&limit=<?= $limit ?>">
                <i class="bi bi-check-circle me-1"></i> Approved <span class="badge bg-white text-success ms-1"><?= $counts['approved'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status === 'spam' ? 'active bg-secondary' : 'bg-light text-dark' ?> fw-semibold" href="comments.php?status=spam&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&limit=<?= $limit ?>">
                <i class="bi bi-shield-x me-1"></i> Spam / Flagged <span class="badge bg-white text-secondary ms-1"><?= $counts['spam'] ?></span>
            </a>
        </li>
    </ul>

    <!-- Advanced Filter Form -->
    <form method="GET" action="comments.php" class="row g-3 align-items-end">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted"><i class="bi bi-search me-1"></i> Search Query</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, Email, Comment, Title..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted"><i class="bi bi-journal-text me-1"></i> Filter by Article</label>
            <select name="post_id" class="form-select form-select-sm">
                <option value="0">-- All Articles --</option>
                <?php foreach ($posts_with_comments as $pw): ?>
                    <option value="<?= $pw['id'] ?>" <?= $post_filter == $pw['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pw['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-6">
            <label class="form-label small fw-bold text-muted"><i class="bi bi-calendar-event me-1"></i> Date From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
        </div>

        <div class="col-md-2 col-6">
            <label class="form-label small fw-bold text-muted"><i class="bi bi-calendar-event me-1"></i> Date To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted"><i class="bi bi-list-numbers me-1"></i> Per Page</label>
            <select name="limit" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 / page</option>
                <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15 / page</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 / page</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 / page</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 / page</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 pt-2 border-top">
            <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold"><i class="bi bi-funnel-fill me-1"></i> Apply Filter</button>
            <?php if (!empty($search) || $post_filter > 0 || !empty($date_from) || !empty($date_to) || $status !== 'all'): ?>
                <a href="comments.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i> Reset Filters</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bulk Actions & Table -->
<form method="POST" action="comments.php" id="bulkForm">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <select name="bulk_action" class="form-select form-select-sm fw-semibold" style="width: auto;">
                    <option value="">-- Bulk Actions --</option>
                    <option value="approve">Approve Selected</option>
                    <option value="unapprove">Unapprove / Set Pending</option>
                    <option value="spam">Mark Selected as Spam</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-dark btn-sm fw-bold" onclick="return confirm('Are you sure you want to execute bulk action on selected items?');">
                    Apply Action
                </button>
            </div>
            <span class="small text-muted">
                Showing <?= min($total_comments, $offset + 1) ?> - <?= min($total_comments, $offset + count($comments)) ?> of <?= $total_comments ?> comments
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>User & Email</th>
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
                            <td class="text-center">
                                <input type="checkbox" name="selected_comments[]" value="<?= $c['id'] ?>" class="form-check-input comment-checkbox">
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($c['name']) ?></strong>
                                <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($c['email']) ?></small>
                            </td>
                            <td style="max-width: 300px;">
                                <div class="text-dark" style="font-size: 0.92rem; line-height: 1.4; word-break: break-word;">
                                    <?= nl2br(htmlspecialchars($c['comment'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($c['post_title'])): ?>
                                    <a href="../article.php?slug=<?= $c['post_slug'] ?? '' ?>" target="_blank" class="fw-semibold text-danger text-decoration-none d-block text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($c['post_title']) ?>">
                                        <i class="bi bi-link-45deg"></i> <?= htmlspecialchars($c['post_title']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['status'] === 'approved'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                <?php elseif ($c['status'] === 'spam'): ?>
                                    <span class="badge bg-secondary"><i class="bi bi-shield-x me-1"></i> Spam</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= time_ago($c['created_at']) ?></small></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($c['status'] !== 'approved'): ?>
                                        <a href="comments.php?action=approve&id=<?= $c['id'] ?>" class="btn btn-outline-success" title="Approve Comment"><i class="bi bi-check-lg"></i></a>
                                    <?php else: ?>
                                        <a href="comments.php?action=unapprove&id=<?= $c['id'] ?>" class="btn btn-outline-warning" title="Mark Pending"><i class="bi bi-pause-circle"></i></a>
                                    <?php endif; ?>

                                    <?php if ($c['status'] !== 'spam'): ?>
                                        <a href="comments.php?action=spam&id=<?= $c['id'] ?>" class="btn btn-outline-secondary" title="Mark Spam"><i class="bi bi-shield-x"></i></a>
                                    <?php endif; ?>

                                    <a href="comments.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this comment permanently?');" title="Delete"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-square-x fs-1 d-block mb-2 text-muted"></i>
                                <h5>No comments found</h5>
                                <p class="small mb-0">No reader comments match your current filter criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Full Pagination Bar -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
                <span class="small text-muted fw-semibold">Page <?= $page ?> of <?= $total_pages ?> (Total <?= number_format($total_comments) ?> comments)</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <!-- First & Prev -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&limit=<?= $limit ?>&page=1">&laquo;&laquo; First</a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&limit=<?= $limit ?>&page=<?= $page - 1 ?>">&laquo; Prev</a>
                        </li>

                        <!-- Page Range Links -->
                        <?php 
                        $start_p = max(1, $page - 2);
                        $end_p = min($total_pages, $page + 2);
                        for ($i = $start_p; $i <= $end_p; $i++): 
                        ?>
                            <li class="page-item <?= $page == $i ? 'active bg-danger border-danger' : '' ?>">
                                <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&limit=<?= $limit ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next & Last -->
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&limit=<?= $limit ?>&page=<?= $page + 1 ?>">Next &raquo;</a>
                        </li>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="comments.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&post_id=<?= $post_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&limit=<?= $limit ?>&page=<?= $total_pages ?>">Last &raquo;&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.comment-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
