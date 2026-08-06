<?php
require_once __DIR__ . '/header.php';
require_role_permission('admin');

$db = get_db_connection();
$msg = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$user_id = (int)($_GET['id'] ?? 0);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['form_action'] ?? 'create';

    if ($post_action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'reporter';

        if (!empty($username) && !empty($password)) {
            // Check existing
            $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ((int)$check->fetchColumn() > 0) {
                $error = "Username or Email already exists!";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $email, $hash, $full_name, $role]);
                $msg = "New staff user standard account created successfully!";
            }
        } else {
            $error = "Username and password are required!";
        }
    } elseif ($post_action === 'update') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'reporter';
        $status = (int)($_POST['status'] ?? 1);

        if ($target_id > 0) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $role, $status, $target_id]);
            $msg = "User profile updated successfully!";
        }
    } elseif ($post_action === 'reset_password') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $new_password = trim($_POST['new_password'] ?? '');

        if ($target_id > 0 && !empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $target_id]);
            $msg = "Password reset successfully for user ID #{$target_id}!";
        } else {
            $error = "Please provide a valid new password!";
        }
    }
}

// Handle Delete Action
if ($action === 'delete' && $user_id > 0) {
    if ($user_id !== 1) { // Protect super admin
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $msg = "User deleted successfully!";
    } else {
        $error = "Cannot delete primary Super Administrator account!";
    }
    header('Location: users.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}

// VIEW INDIVIDUAL USER PROFILE & ACTIVITY OVERVIEW
if ($action === 'view' && $user_id > 0) {
    $uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $view_user = $uStmt->fetch();

    if (!$view_user) {
        echo "<div class='alert alert-danger'>User not found.</div>";
        require_once __DIR__ . '/footer.php';
        exit;
    }

    // Post statistics for this user
    $statTotal = (int)$db->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?")->execute([$user_id]) ? $db->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?")->execute([$user_id]) : 0;
    
    $pStmt = $db->prepare("SELECT COUNT(*) as total_posts, SUM(views) as total_views, 
                          SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
                          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                          SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count
                          FROM posts WHERE author_id = ?");
    $pStmt->execute([$user_id]);
    $user_stats = $pStmt->fetch();

    $total_user_posts = (int)($user_stats['total_posts'] ?? 0);
    $total_user_views = (int)($user_stats['total_views'] ?? 0);
    $published_user_posts = (int)($user_stats['published_count'] ?? 0);
    $pending_user_posts = (int)($user_stats['pending_count'] ?? 0);
    $draft_user_posts = (int)($user_stats['draft_count'] ?? 0);

    // Fetch posts created by this user with pagination
    $post_page = max(1, (int)($_GET['post_page'] ?? 1));
    $post_limit = 10;
    $post_offset = ($post_page - 1) * $post_limit;

    $user_posts_total = $total_user_posts;
    $total_post_pages = max(1, ceil($user_posts_total / $post_limit));

    $upStmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.author_id = ? ORDER BY p.id DESC LIMIT {$post_limit} OFFSET {$post_offset}");
    $upStmt->execute([$user_id]);
    $user_posts = $upStmt->fetchAll() ?: [];
    ?>

    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
            <a href="users.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Back to User Management</a>
            <h3 class="fw-bold mb-0"><i class="bi bi-person-bounding-box text-danger me-2"></i> User Profile & Activity Overview</h3>
            <p class="text-muted small">Viewing detailed activities, articles authored, and administrative controls for <?= htmlspecialchars($view_user['full_name']) ?></p>
        </div>
        <div>
            <span class="badge bg-<?= $view_user['status'] == 1 ? 'success' : 'danger' ?> fs-6 text-uppercase px-3 py-2">
                <?= $view_user['status'] == 1 ? 'Active User' : 'Account Suspended' ?>
            </span>
        </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- User Details Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 text-center mb-4">
                <div class="mb-3">
                    <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center shadow" style="width: 80px; height: 80px; font-size: 32px; font-weight: bold;">
                        <?= strtoupper(substr($view_user['full_name'] ?: $view_user['username'], 0, 1)) ?>
                    </div>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($view_user['full_name']) ?></h4>
                <p class="text-muted mb-2">@<?= htmlspecialchars($view_user['username']) ?></p>
                <div>
                    <span class="badge bg-primary text-uppercase px-3 py-1 mb-2"><?= htmlspecialchars($view_user['role']) ?></span>
                </div>
                <hr>
                <div class="text-start small text-muted">
                    <p class="mb-2"><i class="bi bi-envelope me-2 text-danger"></i> <strong>Email:</strong> <?= htmlspecialchars($view_user['email']) ?></p>
                    <p class="mb-2"><i class="bi bi-shield-check me-2 text-primary"></i> <strong>User ID:</strong> #<?= $view_user['id'] ?></p>
                    <p class="mb-0"><i class="bi bi-calendar-event me-2 text-secondary"></i> <strong>Joined:</strong> <?= date('d M, Y', strtotime($view_user['created_at'] ?? 'now')) ?></p>
                </div>
            </div>

            <!-- Password Reset Card (Admin Only) -->
            <div class="card border-0 shadow-sm p-4 border-start border-4 border-danger">
                <h5 class="fw-bold text-danger mb-3"><i class="bi bi-key-fill me-2"></i> Reset Password (পাসওয়ার্ড পরিবর্তন)</h5>
                <form action="users.php?action=view&id=<?= $user_id ?>" method="POST">
                    <input type="hidden" name="form_action" value="reset_password">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password *</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                        <small class="text-muted">অ্যাডমিন হিসেবে আপনি সরাসরি যে কোনো ব্যবহারকারীর পাসওয়ার্ড রিসেট করতে পারেন।</small>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-check-circle me-1"></i> Update Password</button>
                </form>
            </div>
        </div>

        <!-- Activity Stats & Edit Details -->
        <div class="col-lg-8">
            <!-- Stats Cards Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 bg-white text-center">
                        <div class="fs-3 fw-bold text-dark"><?= $total_user_posts ?></div>
                        <div class="small text-muted fw-semibold">Total Posts</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 bg-white text-center border-start border-3 border-success">
                        <div class="fs-3 fw-bold text-success"><?= $published_user_posts ?></div>
                        <div class="small text-muted fw-semibold">Published</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 bg-white text-center border-start border-3 border-warning">
                        <div class="fs-3 fw-bold text-warning"><?= $pending_user_posts ?></div>
                        <div class="small text-muted fw-semibold">Pending Approval</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm p-3 bg-white text-center border-start border-3 border-info">
                        <div class="fs-3 fw-bold text-primary"><?= number_format($total_user_views) ?></div>
                        <div class="small text-muted fw-semibold">Total Views</div>
                    </div>
                </div>
            </div>

            <!-- Edit Account Details Form Card -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Account Info & Permissions</h5>
                <form action="users.php?action=view&id=<?= $user_id ?>" method="POST" class="row g-3">
                    <input type="hidden" name="form_action" value="update">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($view_user['full_name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($view_user['email']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assign Role (ভূমিকা)</label>
                        <select name="role" class="form-select">
                            <option value="admin" <?= $view_user['role'] === 'admin' ? 'selected' : '' ?>>Administrator (এডমিনিস্ট্রেটর)</option>
                            <option value="editor" <?= $view_user['role'] === 'editor' ? 'selected' : '' ?>>Editor (সম্পাদক)</option>
                            <option value="reporter" <?= $view_user['role'] === 'reporter' ? 'selected' : '' ?>>Reporter / Journalist (রিপোর্টার/সাংবাদিক)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="1" <?= $view_user['status'] == 1 ? 'selected' : '' ?>>Active (সক্রিয়)</option>
                            <option value="0" <?= $view_user['status'] == 0 ? 'selected' : '' ?>>Suspended / Inactive (স্থগিত)</option>
                        </select>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Posts List Authored by User -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-newspaper me-2 text-danger"></i> Articles Authored by <?= htmlspecialchars($view_user['full_name']) ?></h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($user_posts)): foreach ($user_posts as $up): ?>
                                <tr>
                                    <td class="fw-bold text-dark" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($up['title']) ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($up['category_name'] ?: 'Uncategorized') ?></span></td>
                                    <td>
                                        <?php if ($up['status'] === 'published'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Published</span>
                                        <?php elseif ($up['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> Pending Approval</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><i class="bi bi-eye text-muted me-1"></i> <?= number_format($up['views']) ?></td>
                                    <td class="small text-muted"><?= date('d M, Y', strtotime($up['publish_date'])) ?></td>
                                    <td class="text-end">
                                        <a href="post-edit.php?id=<?= $up['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Post"><i class="bi bi-pencil"></i> Edit</a>
                                        <a href="../post-details.php?id=<?= $up['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview"><i class="bi bi-box-arrow-up-right"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No posts found for this user.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($total_post_pages) && $total_post_pages > 1): ?>
                    <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center border-top">
                        <small class="text-muted">Showing Page <?= $post_page ?> of <?= $total_post_pages ?> (Total <?= $user_posts_total ?> posts)</small>
                        <nav aria-label="User Posts Pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $post_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="users.php?action=view&id=<?= $user_id ?>&post_page=<?= $post_page - 1 ?>">&laquo; Prev</a>
                                </li>
                                <?php for ($p = 1; $p <= $total_post_pages; $p++): ?>
                                    <li class="page-item <?= $p == $post_page ? 'active' : '' ?>">
                                        <a class="page-link" href="users.php?action=view&id=<?= $user_id ?>&post_page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $post_page >= $total_post_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="users.php?action=view&id=<?= $user_id ?>&post_page=<?= $post_page + 1 ?>">Next &raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/footer.php';
    exit;
}

// MAIN USERS LISTING & CREATION PAGE
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

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM users {$whereSQL}");
$countStmt->execute($params);
$total_users = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Fetch Users with post count
$sql = "SELECT u.*, (SELECT COUNT(*) FROM posts WHERE author_id = u.id) as posts_count 
        FROM users u {$whereSQL} ORDER BY u.id ASC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="row g-4">
    <!-- Add User Form Sidebar -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-plus-fill text-danger me-2"></i> Add Staff / Journalist</h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger py-2 small alert-dismissible fade show"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form action="users.php" method="POST">
                <input type="hidden" name="form_action" value="create">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="johndoe">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email *</label>
                    <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Role (ভূমিকা)</label>
                    <select name="role" class="form-select">
                        <option value="admin">Administrator (এডমিন - সম্পূর্ণ এক্সেস)</option>
                        <option value="editor">Editor (সম্পাদক - পোস্ট ও কন্টেন্ট নিয়ন্ত্রণ)</option>
                        <option value="reporter" selected>Reporter / Journalist (সাংবাদিক/রিপোর্টার)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-person-plus me-1"></i> Create User</button>
            </form>
        </div>
    </div>

    <!-- Users List & Management Table -->
    <div class="col-lg-8">
        <!-- Search & Filter Card -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <form method="GET" action="users.php" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
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
                            <th>User</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Posts Written</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 14px;">
                                            <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div class="small text-muted">@<?= htmlspecialchars($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-danger text-uppercase">Admin</span>
                                    <?php elseif ($u['role'] === 'editor'): ?>
                                        <span class="badge bg-primary text-uppercase">Editor</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark text-uppercase">Reporter</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-newspaper me-1"></i> <?= number_format($u['posts_count']) ?> posts</span>
                                </td>
                                <td class="text-end">
                                    <a href="users.php?action=view&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" title="View Profile & Activity Overview"><i class="bi bi-person-lines-fill me-1"></i> View Profile & Posts</a>
                                    <?php if ($u['id'] !== 1): ?>
                                        <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Are you sure you want to delete this user?');" title="Delete User"><i class="bi bi-trash"></i></a>
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
