<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
check_install_status();

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_logged'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Newspaper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center min-vh-100">

<div class="container" style="max-width: 420px;">
    <div class="card border-0 shadow-lg rounded-3">
        <div class="card-header bg-danger text-white text-center p-4 rounded-top-3">
            <h4 class="fw-bold mb-0">DAILY HORIZON</h4>
            <small class="opacity-75">Admin Panel Authentication</small>
        </div>
        <div class="card-body p-4 p-md-5">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username or Email</label>
                    <input type="text" name="username" class="form-control form-control-lg" required placeholder="Enter username or email">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold">Sign In &rarr;</button>
            </form>
            <div class="mt-3 text-center">
                <a href="../index.php" class="text-muted small text-decoration-none">&larr; Back to Live Website</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
