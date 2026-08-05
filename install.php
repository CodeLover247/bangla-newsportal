<?php
/**
 * Interactive 3-Step GUI Installer for Newspaper Portal CMS
 * Supports MySQL / MariaDB (cPanel phpMyAdmin) and SQLite
 */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/functions.php';

if (is_installed()) {
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newspaper CMS - System Already Installed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-4">
    <div class="container" style="max-width: 600px;">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-danger text-white p-4 text-center rounded-top-4">
                <h3 class="fw-bold mb-1"><i class="bi bi-shield-lock-fill me-2"></i>Newspaper CMS is Already Installed</h3>
                <small class="opacity-75">Installation Wizard is Locked for Security</small>
            </div>
            <div class="card-body p-4 p-md-5 text-center">
                <div class="alert alert-warning py-3 mb-4 text-start">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>System Locked:</strong> Your Newspaper Portal CMS has already been configured and installed successfully.
                    <hr class="my-2">
                    <small class="text-muted">For security reasons, re-running the installation wizard is blocked. If you need to reinstall the system, manually delete the <code>installed.lock</code> file from your web server directory.</small>
                </div>
                <div class="d-grid gap-2 d-sm-flex justify-content-center">
                    <a href="index.php" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-house-door me-2"></i>Go to Website Homepage</a>
                    <a href="admin/login.php" class="btn btn-dark btn-lg fw-bold"><i class="bi bi-speedometer2 me-2"></i>Go to Admin Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// Pre-fill session from .env if present and session is empty
if (!isset($_SESSION['db_type']) && file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $env_line) {
        $env_line = trim($env_line);
        if (!empty($env_line) && strpos($env_line, '=') !== false) {
            list($k, $v) = explode('=', $env_line, 2);
            $k = trim($k);
            $v = trim($v, "\"' ");
            if ($k === 'DB_TYPE') $_SESSION['db_type'] = $v;
            if ($k === 'DB_HOST') $_SESSION['db_host'] = $v;
            if ($k === 'DB_NAME') $_SESSION['db_name'] = $v;
            if ($k === 'DB_USER') $_SESSION['db_user'] = $v;
            if ($k === 'DB_PASS') $_SESSION['db_pass'] = $v;
        }
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 3) $step = 1;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $_SESSION['db_type'] = $_POST['db_type'] ?? 'mysql';
        $_SESSION['db_host'] = trim($_POST['db_host'] ?? 'localhost');
        $_SESSION['db_name'] = trim($_POST['db_name'] ?? 'newsportal');
        $_SESSION['db_user'] = trim($_POST['db_user'] ?? 'root');
        $_SESSION['db_pass'] = $_POST['db_pass'] ?? '';

        // Test DB Connection
        try {
            if ($_SESSION['db_type'] === 'mysql') {
                $host = $_SESSION['db_host'];
                $dbname = $_SESSION['db_name'];
                $user = $_SESSION['db_user'];
                $pass = $_SESSION['db_pass'];

                try {
                    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                    $test = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                } catch (PDOException $e) {
                    // Try creating database if it doesn't exist
                    if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                        $dsnNoDb = "mysql:host={$host};charset=utf8mb4";
                        $testNoDb = new PDO($dsnNoDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        $testNoDb->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Re-test connection to newly created DB
                        $test = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    } else {
                        throw $e;
                    }
                }
            } else {
                $test = new PDO("sqlite:" . __DIR__ . "/database.sqlite", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            }
            header("Location: install.php?step=2");
            exit;
        } catch (Throwable $e) {
            $error = "Database Connection Failed: " . $e->getMessage();
        }
    } elseif ($step === 2) {
        $_SESSION['site_name'] = trim($_POST['site_name'] ?? 'দৈনিক দিগন্ত');
        $_SESSION['admin_user'] = trim($_POST['admin_user'] ?? 'admin');
        $_SESSION['admin_email'] = trim($_POST['admin_email'] ?? 'admin@newsportal.com');
        $_SESSION['admin_pass'] = trim($_POST['admin_pass'] ?? 'admin123');

        if (empty($_SESSION['admin_user']) || empty($_SESSION['admin_pass']) || empty($_SESSION['admin_email'])) {
            $error = "Please fill in all admin credentials.";
        } else {
            header("Location: install.php?step=3");
            exit;
        }
    } elseif ($step === 3) {
        // Execute Installation & Write config.php
        $db_type = $_SESSION['db_type'] ?? 'mysql';
        $db_host = $_SESSION['db_host'] ?? 'localhost';
        $db_name = $_SESSION['db_name'] ?? 'newsportal';
        $db_user = $_SESSION['db_user'] ?? 'root';
        $db_pass = $_SESSION['db_pass'] ?? '';

        $app_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $config_content = "<?php\n"
            . "// Generated by Newspaper Portal CMS Installer\n"
            . "define('DB_TYPE', '{$db_type}');\n"
            . "define('DB_HOST', '{$db_host}');\n"
            . "define('DB_NAME', '{$db_name}');\n"
            . "define('DB_USER', '{$db_user}');\n"
            . "define('DB_PASS', '{$db_pass}');\n"
            . "define('DB_SQLITE_PATH', __DIR__ . '/database.sqlite');\n"
            . "define('SITE_URL', '{$app_url}');\n"
            . "if (session_status() === PHP_SESSION_NONE) {\n"
            . "    session_start();\n"
            . "}\n";

        $env_content = "APP_URL=\"{$app_url}\"\n"
            . "DB_TYPE=\"{$db_type}\"\n"
            . "DB_HOST=\"{$db_host}\"\n"
            . "DB_NAME=\"{$db_name}\"\n"
            . "DB_USER=\"{$db_user}\"\n"
            . "DB_PASS=\"{$db_pass}\"\n";

        @file_put_contents(__DIR__ . '/.env', $env_content);

        if (@file_put_contents(__DIR__ . '/config.php', $config_content) === false) {
            $error = "Could not write config.php file. Please check folder write permissions.";
        } else {
            require_once __DIR__ . '/includes/db.php';

            try {
                if ($db_type === 'mysql') {
                    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);

                    // Auto Import Database SQL Schema & Initial Sample Data
                    $sql_file = __DIR__ . '/database.sql';
                    if (file_exists($sql_file)) {
                        import_sql_file($pdo, $sql_file);
                    }
                } else {
                    $db_file = __DIR__ . '/database.sqlite';
                    $pdo = new PDO("sqlite:" . $db_file, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    initialize_sqlite_db($pdo);
                }

                ensure_custom_author_columns($pdo);
                ensure_all_ad_positions_and_settings($pdo);
                ensure_homepage_sections_table($pdo);
                ensure_default_menus($pdo);

                // Update / Create Admin Account Credentials
                $admin_user = $_SESSION['admin_user'] ?? 'admin';
                $admin_email = $_SESSION['admin_email'] ?? 'admin@newsportal.com';
                $admin_pass = $_SESSION['admin_pass'] ?? 'admin123';
                $pass_hash = password_hash($admin_pass, PASSWORD_BCRYPT);
                $site_name = $_SESSION['site_name'] ?? 'দৈনিক দিগন্ত';

                if ($db_type === 'mysql') {
                    $stmtAdmin = $pdo->prepare("INSERT INTO users (id, username, email, password, full_name, role, status) 
                        VALUES (1, ?, ?, ?, 'Super Administrator', 'admin', 1) 
                        ON DUPLICATE KEY UPDATE username = VALUES(username), email = VALUES(email), password = VALUES(password)");
                    $stmtAdmin->execute([$admin_user, $admin_email, $pass_hash]);

                    $stmtSet = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name', ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmtSet->execute([$site_name]);
                } else {
                    $stmtAdmin = $pdo->prepare("INSERT OR REPLACE INTO users (id, username, email, password, full_name, role, status) 
                        VALUES (1, ?, ?, ?, 'Super Administrator', 'admin', 1)");
                    $stmtAdmin->execute([$admin_user, $admin_email, $pass_hash]);

                    $stmtSet = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES ('site_name', ?)");
                    $stmtSet->execute([$site_name]);
                }

                @file_put_contents(__DIR__ . '/installed.lock', 'Installed on ' . date('Y-m-d H:i:s'));

                $success = "Installation & Database Auto-Import Completed Successfully!";
            } catch (Throwable $e) {
                $error = "Database Setup Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newspaper CMS Automated Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        .installer-card { border: none; border-radius: 12px; overflow: hidden; }
        .step-pill { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-4">

<div class="container" style="max-width: 680px;">
    <div class="card installer-card shadow-lg">
        <div class="card-header bg-danger text-white p-4 text-center">
            <h3 class="fw-bold mb-1"><i class="bi bi-newspaper me-2"></i>Newspaper CMS Installation Wizard</h3>
            <p class="mb-0 small text-white-50">Automated cPanel, MySQL & phpMyAdmin Setup Assistant</p>
        </div>

        <!-- Wizard Steps Indicator -->
        <div class="bg-light p-3 border-bottom d-flex justify-content-around text-center">
            <div class="d-flex align-items-center gap-2">
                <span class="step-pill <?= $step >= 1 ? 'bg-danger text-white' : 'bg-secondary text-white' ?>">1</span>
                <span class="fw-semibold small <?= $step >= 1 ? 'text-dark' : 'text-muted' ?>">Database</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="step-pill <?= $step >= 2 ? 'bg-danger text-white' : 'bg-secondary text-white' ?>">2</span>
                <span class="fw-semibold small <?= $step >= 2 ? 'text-dark' : 'text-muted' ?>">Site & Admin</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="step-pill <?= $step >= 3 ? 'bg-danger text-white' : 'bg-secondary text-white' ?>">3</span>
                <span class="fw-semibold small <?= $step >= 3 ? 'text-dark' : 'text-muted' ?>">Finalize</span>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-database-check text-danger me-2"></i>Step 1: Database Connection Setup</h5>
                <form action="install.php?step=1" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Database Driver</label>
                        <select name="db_type" class="form-select form-select-lg">
                            <option value="mysql" <?= ($_SESSION['db_type'] ?? '') === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB (Recommended for cPanel / phpMyAdmin)</option>
                            <option value="sqlite" <?= ($_SESSION['db_type'] ?? '') === 'sqlite' ? 'selected' : '' ?>>SQLite (Embedded / Zero Configuration)</option>
                        </select>
                        <small class="text-muted">For cPanel web hosting, select MySQL / MariaDB.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_SESSION['db_host'] ?? 'localhost') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_SESSION['db_name'] ?? 'newsportal') ?>" required>
                        <small class="text-muted">Your MySQL database name created in cPanel MySQL Wizard.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Database Username</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_SESSION['db_user'] ?? 'root') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Database Password</label>
                        <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($_SESSION['db_pass'] ?? '') ?>" placeholder="Leave blank if no password">
                    </div>

                    <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm">
                        Test Connection & Continue &rarr;
                    </button>
                </form>

            <?php elseif ($step === 2): ?>
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-shield-lock text-danger me-2"></i>Step 2: Newspaper Title & Administrator Account</h5>
                <form action="install.php?step=2" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Website Name / Title</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($_SESSION['site_name'] ?? 'দৈনিক দিগন্ত') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Admin Username</label>
                        <input type="text" name="admin_user" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user'] ?? 'admin') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Admin Email Address</label>
                        <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@newsportal.com') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Admin Password</label>
                        <input type="password" name="admin_pass" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_pass'] ?? 'admin123') ?>" required>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="install.php?step=1" class="btn btn-outline-secondary btn-lg fw-bold">&larr; Back</a>
                        <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm">Save & Proceed &rarr;</button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-gear-fill text-danger me-2"></i>Step 3: Database & Installation Finalization</h5>

                <?php if ($success): ?>
                    <div class="alert alert-success p-3 mb-4 shadow-sm border-0">
                        <h5 class="fw-bold mb-2"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></h5>
                        <p class="mb-0 small">Database tables, news categories, default advertisements, and admin user account have been configured successfully!</p>
                    </div>

                    <div class="card bg-light p-3 border mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-key me-1"></i> Admin Login Summary</h6>
                        <ul class="mb-0 small text-muted list-unstyled">
                            <li><strong>Admin Username:</strong> <code><?= htmlspecialchars($_SESSION['admin_user'] ?? 'admin') ?></code></li>
                            <li><strong>Admin Email:</strong> <code><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@newsportal.com') ?></code></li>
                            <li><strong>Website Name:</strong> <?= htmlspecialchars($_SESSION['site_name'] ?? 'দৈনিক দিগন্ত') ?></li>
                        </ul>
                    </div>

                    <div class="alert alert-warning py-2 px-3 mb-4 small">
                        <i class="bi bi-shield-exclamation me-1"></i> <strong>Security Notice:</strong> Please delete or rename <code>install.php</code> from your web server for security.
                    </div>

                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-house-door me-2"></i>Go to Website Homepage</a>
                        <a href="admin/login.php" class="btn btn-dark btn-lg fw-bold"><i class="bi bi-speedometer2 me-2"></i>Go to Admin Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="card bg-light p-3 border mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-list-check me-2"></i>Installation Summary</h6>
                        <ul class="mb-0 small text-muted">
                            <li><strong>Database Driver:</strong> <?= strtoupper($_SESSION['db_type'] ?? 'mysql') ?></li>
                            <li><strong>Database Name:</strong> <?= htmlspecialchars($_SESSION['db_name'] ?? 'newsportal') ?></li>
                            <li><strong>Database Host:</strong> <?= htmlspecialchars($_SESSION['db_host'] ?? 'localhost') ?></li>
                            <li><strong>Admin Username:</strong> <?= htmlspecialchars($_SESSION['admin_user'] ?? 'admin') ?></li>
                        </ul>
                    </div>

                    <form action="install.php?step=3" method="POST">
                        <p class="text-muted small mb-3">Click the button below to auto-import database SQL schema, seed core news categories, default settings, ad slots, and initialize admin user.</p>
                        
                        <div class="d-flex gap-2">
                            <a href="install.php?step=2" class="btn btn-outline-secondary btn-lg fw-bold">&larr; Back</a>
                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm"><i class="bi bi-cloud-download me-2"></i>Finalize Installation & Import Database</button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
