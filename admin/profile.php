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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">My Account Profile</h3>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card p-4 shadow-sm border" style="max-width: 600px;">
    <form action="profile.php" method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Username</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
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

        <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold mt-2">Update Account Details</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
