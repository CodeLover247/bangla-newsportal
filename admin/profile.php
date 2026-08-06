<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$user_id = $_SESSION['admin_id'] ?? 1;
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_pass = trim($_POST['password'] ?? '');

    if (!empty($full_name) && !empty($email)) {
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $hash, $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
        }
        $_SESSION['admin_name'] = $full_name;
        $msg = "Profile updated successfully!";
        $user['full_name'] = $full_name;
        $user['email'] = $email;
    }
}

// Stats & Paginated User Articles
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

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total_pages = max(1, ceil($total_user_posts / $limit));

$upStmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.author_id = ? ORDER BY p.id DESC LIMIT {$limit} OFFSET {$offset}");
$upStmt->execute([$user_id]);
$my_posts = $upStmt->fetchAll() ?: [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0"><i class="bi bi-person-circle text-danger me-2"></i> My Account Profile & Activity Overview</h3>
        <p class="text-muted small">Manage your profile credentials and review your published news articles.</p>
    </div>
    <div>
        <span class="badge bg-primary text-uppercase px-3 py-2 fs-6"><?= htmlspecialchars($user['role'] ?? 'user') ?> Account</span>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="row g-4 mb-4">
    <!-- User Details Form Card -->
    <div class="col-lg-5">
        <div class="card p-4 shadow-sm border border-0 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-gear-fill text-danger me-2"></i> Profile Credentials</h5>
            <form action="profile.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Email Address *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Change Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                </div>

                <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold mt-2"><i class="bi bi-check-circle me-1"></i> Update Profile Info</button>
            </form>
        </div>
    </div>

    <!-- Stats & Activity Cards -->
    <div class="col-lg-7">
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
                    <div class="small text-muted fw-semibold">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm p-3 bg-white text-center border-start border-3 border-info">
                    <div class="fs-3 fw-bold text-primary"><?= number_format($total_user_views) ?></div>
                    <div class="small text-muted fw-semibold">Total Views</div>
                </div>
            </div>
        </div>

        <!-- My Authored Articles Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-newspaper me-2 text-danger"></i> Articles Authored by Me</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($my_posts)): foreach ($my_posts as $mp): ?>
                            <tr>
                                <td class="fw-bold text-dark" style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($mp['title']) ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($mp['category_name'] ?: 'General') ?></span></td>
                                <td>
                                    <?php if ($mp['status'] === 'published'): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php elseif ($mp['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="bi bi-eye text-muted me-1"></i><?= number_format($mp['views']) ?></td>
                                <td class="text-end">
                                    <a href="post-edit.php?id=<?= $mp['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No posts created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center border-top">
                    <small class="text-muted">Page <?= $page ?> of <?= $total_pages ?> (<?= $total_user_posts ?> total)</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="profile.php?page=<?= $page - 1 ?>">&laquo; Prev</a>
                            </li>
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="profile.php?page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="profile.php?page=<?= $page + 1 ?>">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
