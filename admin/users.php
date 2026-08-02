<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'reporter';

    if (!empty($username) && !empty($password)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$username, $email, $hash, $full_name, $role]);
        $msg = "New staff user added!";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if ((int)$_GET['id'] !== 1) { // Protect super admin
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$_GET['id']]);
    }
    header('Location: users.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($role_filter !== 'all') {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($search)) {
    $where[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $sParam = "%{$search}%";
    $params[] = $sParam;
    $params[] = $sParam;
    $params[] = $sParam;
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM users {$whereSQL}");
$countStmt->execute($params);
$total_users = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Data
$sql = "SELECT * FROM users {$whereSQL} ORDER BY id ASC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3">Add Staff / Journalist</h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

            <form action="users.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="johndoe">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="John Doe">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Role</label>
                    <select name="role" class="form-select">
                        <option value="admin">Administrator</option>
                        <option value="editor">Editor</option>
                        <option value="reporter" selected>Reporter / Journalist</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-person-plus me-1"></i> Create User</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Search & Filter Card -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <form method="GET" action="users.php" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="role" class="form-select form-select-sm">
                        <option value="all">All Roles</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="editor" <?= $role_filter === 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="reporter" <?= $role_filter === 'reporter' ? 'selected' : '' ?>>Reporter</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Search name, username, email..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                        <?php if (!empty($search) || $role_filter !== 'all'): ?>
                            <a href="users.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                                <td><span class="badge bg-primary text-uppercase"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td class="text-end">
                                    <?php if ($u['id'] !== 1): ?>
                                        <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete this staff user?');"><i class="bi bi-trash"></i> Delete</a>
                                    <?php else: ?>
                                        <span class="badge bg-dark">Super Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No users found.</td></tr>
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
                                <a class="page-link" href="users.php?role=<?= urlencode($role_filter) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                    <a class="page-link" href="users.php?role=<?= urlencode($role_filter) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="users.php?role=<?= urlencode($role_filter) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
