<?php
// Handle CSV export BEFORE including admin header to prevent output buffering issues
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    require_once __DIR__ . '/../includes/functions.php';
    check_install_status();
    $db = get_db_connection();
    if ($db && function_exists('ensure_contact_messages_table')) {
        ensure_contact_messages_table($db);
    }

    $filter = $_GET['filter'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $date_from = trim($_GET['date_from'] ?? '');
    $date_to = trim($_GET['date_to'] ?? '');

    $where = ["1=1"];
    $params = [];

    if ($filter === 'unread') {
        $where[] = "is_read = 0";
    } elseif ($filter === 'read') {
        $where[] = "is_read = 1";
    }

    if (!empty($search)) {
        $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $s_term = "%$search%";
        $params = array_merge($params, [$s_term, $s_term, $s_term, $s_term, $s_term]);
    }

    if (!empty($date_from)) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $date_to;
    }

    $whereSQL = implode(" AND ", $where);
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE {$whereSQL} ORDER BY id DESC");
    $stmt->execute($params);
    $export_messages = $stmt->fetchAll() ?: [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=contact_messages_' . date('Y-m-d_H-i') . '.csv');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Bengali/Unicode Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Sender Name', 'Email Address', 'Phone Number', 'Subject', 'Message', 'Status', 'Submitted Date']);

    foreach ($export_messages as $m) {
        fputcsv($output, [
            $m['id'],
            $m['name'],
            $m['email'],
            $m['phone'] ?? '',
            $m['subject'],
            $m['message'],
            $m['is_read'] == 1 ? 'Read' : 'Unread',
            $m['created_at']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/header.php';

$db = get_db_connection();
if ($db && function_exists('ensure_contact_messages_table')) {
    ensure_contact_messages_table($db);
}

$msg = '';
$msg_type = 'success';

// Handle Single Item Actions: delete, mark_read, mark_unread
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id > 0) {
    $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
    if ($stmt->execute([$id])) {
        $msg = 'Message deleted successfully!';
    }
} elseif ($action === 'mark_read' && $id > 0) {
    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    $msg = 'Message marked as read.';
} elseif ($action === 'mark_unread' && $id > 0) {
    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?");
    $stmt->execute([$id]);
    $msg = 'Message marked as unread.';
}

// Handle Bulk Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulk_ids = $_POST['message_ids'] ?? [];
    $bulk_ids = array_map('intval', array_filter($bulk_ids));

    if (!empty($bulk_ids)) {
        $placeholders = implode(',', array_fill(0, count($bulk_ids), '?'));
        if ($_POST['bulk_action'] === 'read') {
            $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id IN ($placeholders)");
            $stmt->execute($bulk_ids);
            $msg = count($bulk_ids) . ' message(s) marked as read.';
        } elseif ($_POST['bulk_action'] === 'unread') {
            $stmt = $db->prepare("UPDATE contact_messages SET is_read = 0 WHERE id IN ($placeholders)");
            $stmt->execute($bulk_ids);
            $msg = count($bulk_ids) . ' message(s) marked as unread.';
        } elseif ($_POST['bulk_action'] === 'delete') {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
            $stmt->execute($bulk_ids);
            $msg = count($bulk_ids) . ' message(s) permanently deleted.';
        }
    } else {
        $msg = 'No messages selected for bulk action.';
        $msg_type = 'warning';
    }
}

// Filters & Search & Pagination
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($filter === 'unread') {
    $where[] = "is_read = 0";
} elseif ($filter === 'read') {
    $where[] = "is_read = 1";
}

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $s_term = "%$search%";
    $params = array_merge($params, [$s_term, $s_term, $s_term, $s_term, $s_term]);
}

if (!empty($date_from)) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$whereSQL = implode(" AND ", $where);

// Count matching total
$count_stmt = $db->prepare("SELECT COUNT(*) FROM contact_messages WHERE {$whereSQL}");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_filtered / $limit));

// Fetch paginated messages
$query = "SELECT * FROM contact_messages WHERE {$whereSQL} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll() ?: [];

// Get overall counts
$unread_count = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$total_count = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

// Build URL query string helper for pagination
$query_params = $_GET;
unset($query_params['page']);
$base_query_string = http_build_query($query_params);
$export_query_string = http_build_query(array_merge($_GET, ['export' => 'csv']));
?>

<div class="container-fluid py-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 border-bottom pb-3 gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-danger"><i class="bi bi-envelope-paper-fill me-2"></i> Contact Messages & Inquiries</h3>
            <p class="text-muted small mb-0">Manage and export visitor inquiries from website contact page.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="messages.php?<?= $export_query_string ?>" class="btn btn-outline-success btn-sm fw-bold shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download CSV / Excel
            </a>
            <span class="badge bg-danger fs-6 py-2 px-3 rounded-pill"><i class="bi bi-envelope-exclamation me-1"></i> <?= $unread_count ?> Unread</span>
            <span class="badge bg-dark fs-6 py-2 px-3 rounded-pill"><i class="bi bi-inbox me-1"></i> <?= $total_count ?> Total</span>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Advanced Filter & Search Bar -->
    <div class="card shadow-sm border-0 mb-4 bg-white rounded-3">
        <div class="card-body p-3">
            <form action="messages.php" method="GET" class="row g-2 align-items-center">
                <div class="col-lg-3 d-flex gap-1">
                    <a href="messages.php?filter=all" class="btn btn-sm flex-fill <?= $filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' ?>">All (<?= $total_count ?>)</a>
                    <a href="messages.php?filter=unread" class="btn btn-sm flex-fill <?= $filter === 'unread' ? 'btn-danger' : 'btn-outline-danger' ?>">Unread (<?= $unread_count ?>)</a>
                    <a href="messages.php?filter=read" class="btn btn-sm flex-fill <?= $filter === 'read' ? 'btn-success' : 'btn-outline-success' ?>">Read</a>
                </div>

                <div class="col-lg-3 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted small"><i class="bi bi-calendar-event me-1"></i> From</span>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                </div>

                <div class="col-lg-3 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted small"><i class="bi bi-calendar-event me-1"></i> To</span>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>

                <div class="col-lg-3 col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Search sender, email, subject..." value="<?= htmlspecialchars($search) ?>">
                        <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-search"></i></button>
                        <?php if (!empty($search) || !empty($date_from) || !empty($date_to) || $filter !== 'all'): ?>
                            <a href="messages.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions & Messages Table Form -->
    <form action="messages.php?<?= $base_query_string ?>&page=<?= $page ?>" method="POST" id="bulkForm">
        <div class="card shadow-sm border-0 rounded-3">
            <!-- Multi-Select Bulk Actions Toolbar -->
            <div class="card-header bg-light py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check me-2 mb-0">
                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                        <label class="form-check-label fw-bold small text-dark" for="selectAllCheckbox">Select All</label>
                    </div>
                    <span class="vr"></span>
                    <span class="small text-muted" id="selectedCountLabel">0 items selected</span>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="submit" name="bulk_action" value="read" class="btn btn-sm btn-outline-success fw-semibold">
                        <i class="bi bi-check2-all me-1"></i> Mark Selected Read
                    </button>
                    <button type="submit" name="bulk_action" value="unread" class="btn btn-sm btn-outline-warning fw-semibold">
                        <i class="bi bi-envelope me-1"></i> Mark Selected Unread
                    </button>
                    <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Permanently delete all selected messages?');">
                        <i class="bi bi-trash me-1"></i> Delete Selected
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 90px;" class="text-center">Status</th>
                                <th>Sender Name & Email</th>
                                <th>Subject & Preview</th>
                                <th>Phone Number</th>
                                <th>Submitted Date</th>
                                <th class="text-end" style="width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                        No contact messages found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $m): ?>
                                    <tr class="<?= $m['is_read'] == 0 ? 'table-warning fw-bold' : '' ?>">
                                        <td class="text-center">
                                            <input type="checkbox" name="message_ids[]" value="<?= $m['id'] ?>" class="form-check-input row-checkbox" onchange="updateSelectedCount()">
                                        </td>
                                        <td class="text-center">
                                            <?php if ($m['is_read'] == 0): ?>
                                                <span class="badge bg-danger rounded-pill px-2 py-1" title="Unread"><i class="bi bi-envelope-fill"></i> New</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary rounded-pill px-2 py-1" title="Read"><i class="bi bi-envelope-open"></i> Read</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                            <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($m['email']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary mb-1"><?= htmlspecialchars($m['subject']) ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 320px;">
                                                <?= htmlspecialchars(mb_strimwidth($m['message'], 0, 85, '...')) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($m['phone'])): ?>
                                                <a href="tel:<?= htmlspecialchars($m['phone']) ?>" class="text-decoration-none text-dark small"><i class="bi bi-telephone me-1 text-danger"></i><?= htmlspecialchars($m['phone']) ?></a>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($m['created_at'])) ?></small>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#viewMsgModal<?= $m['id'] ?>" title="View Message Details">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=Re: <?= urlencode($m['subject']) ?>" class="btn btn-outline-success" title="Reply via Email">
                                                    <i class="bi bi-reply-fill"></i>
                                                </a>
                                                <?php if ($m['is_read'] == 0): ?>
                                                    <a href="messages.php?action=mark_read&id=<?= $m['id'] ?>&<?= $base_query_string ?>&page=<?= $page ?>" class="btn btn-outline-secondary" title="Mark as Read">
                                                        <i class="bi bi-check2-circle"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="messages.php?action=mark_unread&id=<?= $m['id'] ?>&<?= $base_query_string ?>&page=<?= $page ?>" class="btn btn-outline-warning" title="Mark as Unread">
                                                        <i class="bi bi-envelope"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="messages.php?action=delete&id=<?= $m['id'] ?>&<?= $base_query_string ?>&page=<?= $page ?>" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this message?');" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Bar -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white py-3 px-3 d-flex flex-wrap align-items-center justify-content-between border-top gap-2">
                    <span class="small text-muted">Showing <?= count($messages) ?> of <?= $total_filtered ?> messages (Page <?= $page ?> of <?= $total_pages ?>)</span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="messages.php?<?= $base_query_string ?>&page=<?= $page - 1 ?>">&laquo; Prev</a>
                            </li>
                            <?php
                            $start_p = max(1, $page - 2);
                            $end_p = min($total_pages, $page + 2);
                            for ($p = $start_p; $p <= $end_p; $p++):
                            ?>
                                <li class="page-item <?= $p == $page ? 'active bg-danger border-danger' : '' ?>">
                                    <a class="page-link" href="messages.php?<?= $base_query_string ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="messages.php?<?= $base_query_string ?>&page=<?= $page + 1 ?>">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Modals for Viewing Message Details -->
    <?php foreach ($messages as $m): ?>
        <div class="modal fade" id="viewMsgModal<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-envelope-paper me-2 text-danger"></i> Message from <?= htmlspecialchars($m['name']) ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3 border-bottom pb-3">
                            <div class="col-md-6">
                                <strong class="text-muted d-block small">Sender Name:</strong>
                                <span class="fs-6 fw-bold"><?= htmlspecialchars($m['name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong class="text-muted d-block small">Email Address:</strong>
                                <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="fs-6 fw-bold text-danger text-decoration-none"><?= htmlspecialchars($m['email']) ?></a>
                            </div>
                            <div class="col-md-6">
                                <strong class="text-muted d-block small">Phone Number:</strong>
                                <span class="fs-6"><?= !empty($m['phone']) ? htmlspecialchars($m['phone']) : 'N/A' ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong class="text-muted d-block small">Received Date:</strong>
                                <span class="fs-6 text-muted"><?= date('F j, Y \a\t g:i A', strtotime($m['created_at'])) ?></span>
                            </div>
                            <div class="col-12">
                                <strong class="text-muted d-block small">Subject:</strong>
                                <span class="fs-5 fw-bold text-primary"><?= htmlspecialchars($m['subject']) ?></span>
                            </div>
                        </div>

                        <strong class="text-muted d-block small mb-2">Message Body:</strong>
                        <div class="p-3 bg-light border rounded text-dark fs-6" style="white-space: pre-wrap; line-height: 1.6;">
                            <?= htmlspecialchars($m['message']) ?>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <a href="messages.php?action=mark_read&id=<?= $m['id'] ?>" class="btn btn-outline-secondary btn-sm">Mark Read</a>
                        <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=Re: <?= urlencode($m['subject']) ?>" class="btn btn-success btn-sm fw-bold">
                            <i class="bi bi-reply-fill me-1"></i> Reply via Email
                        </a>
                        <button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleSelectAll(mainCheckbox) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = mainCheckbox.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const label = document.getElementById('selectedCountLabel');
    if (label) {
        label.innerText = checked.length + ' items selected';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
