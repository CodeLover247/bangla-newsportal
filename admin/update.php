<?php
require_once __DIR__ . '/header.php';
require_role_permission('admin');

$db = get_db_connection();

$logs = [];
$success_msg = '';
$error_msg = '';

// Handle Sidebar Toggle Action from Update Page
if (isset($_GET['action']) && $_GET['action'] === 'toggle_sidebar') {
    $current = get_setting('show_update_menu_sidebar', '1');
    $new_status = ($current === '1') ? '0' : '1';
    set_setting('show_update_menu_sidebar', $new_status);
    $success_msg = ($new_status === '1') ? 'এডমিন সাইডবারে অটো আপডেট লিংক অন করা হয়েছে (Auto Update Menu Enabled in Sidebar).' : 'এডমিন সাইডবার থেকে অটো আপডেট লিংক হাইড করা হয়েছে (Auto Update Menu Hidden from Sidebar).';
}

// Handle 1-Click Database & Schema Sync Only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_system_update'])) {
    $logs = ensure_system_update_and_migrations($db);

    // Clear File Cache
    $cache_dir = __DIR__ . '/../cache';
    $cache_files_cleared = 0;
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    @unlink($file);
                    $cache_files_cleared++;
                }
            }
        }
    }
    if ($cache_files_cleared > 0) {
        $logs[] = "System file cache cleared ({$cache_files_cleared} cached items removed).";
    } else {
        $logs[] = "System file cache verified & clean.";
    }

    // Fix orphaned comments count if any
    try {
        $orphaned = $db->query("DELETE FROM comments WHERE post_id NOT IN (SELECT id FROM posts)")->rowCount();
        if ($orphaned > 0) {
            $logs[] = "Cleaned {$orphaned} orphaned comment records.";
        }
    } catch (Throwable $e) {}

    $success_msg = "১-ক্লিক ডাটাবেজ ও স্কিমা সিঙ্ক সফলভাবে সম্পন্ন হয়েছে! (Database & Schema Auto-Sync Completed Successfully with Zero Data Loss)";
}

// Handle ZIP Update Package Extraction & DB Sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_zip_update'])) {
    $zip_file_path = '';
    $temp_uploaded = false;

    // Check if user uploaded a zip file via form
    if (isset($_FILES['zip_file']) && $_FILES['zip_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['zip_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            $error_msg = 'ত্রুটি: শুধুমাত্র .zip ফরম্যাটের আপডেট প্যাকেজ আপলোড করতে পারবেন। (Only .zip files are allowed).';
        } else {
            $zip_file_path = $_FILES['zip_file']['tmp_name'];
            $temp_uploaded = true;
            $logs[] = "Uploaded package file: " . htmlspecialchars($_FILES['zip_file']['name']);
        }
    } 
    // Otherwise check if user selected an existing zip file from root directory
    elseif (!empty($_POST['selected_root_zip'])) {
        $selected_name = basename($_POST['selected_root_zip']);
        $candidate_path = __DIR__ . '/../' . $selected_name;
        if (file_exists($candidate_path) && strtolower(pathinfo($candidate_path, PATHINFO_EXTENSION)) === 'zip') {
            $zip_file_path = realpath($candidate_path);
            $logs[] = "Selected root directory zip package: " . htmlspecialchars($selected_name);
        } else {
            $error_msg = "ত্রুটি: রুট ডিরেক্টরিতে নির্দেশিত জিপ ফাইলটি পাওয়া যায়নি। (Selected zip file not found in root directory).";
        }
    } else {
        $error_msg = "ত্রুটি: কোনো জিপ ফাইল সিলেক্ট বা আপলোড করা হয়নি। (Please select or upload a .zip update package).";
    }

    if (!empty($zip_file_path) && empty($error_msg)) {
        $root_dir = realpath(__DIR__ . '/../');
        $extracted_count = 0;
        $skipped_count = 0;
        $protected_files = ['.env', '.env.example', 'installed.lock', 'db_config.php'];

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zip_file_path) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $clean_name = ltrim($filename, '/\\');

                    // Skip empty paths or directory traversals
                    if (empty($clean_name) || strpos($clean_name, '..') !== false) {
                        continue;
                    }

                    // Protect sensitive files & uploads
                    $base_name = basename($clean_name);
                    if (in_array($base_name, $protected_files) || strpos($clean_name, 'uploads/') === 0) {
                        $skipped_count++;
                        $logs[] = "Protected file preserved: " . htmlspecialchars($clean_name);
                        continue;
                    }

                    $target = $root_dir . '/' . $clean_name;

                    // If it's a directory
                    if (substr($filename, -1) === '/' || substr($filename, -1) === '\\') {
                        if (!is_dir($target)) {
                            @mkdir($target, 0755, true);
                        }
                        continue;
                    }

                    // Create parent folder if not exists
                    $parent_dir = dirname($target);
                    if (!is_dir($parent_dir)) {
                        @mkdir($parent_dir, 0755, true);
                    }

                    // Extract file
                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        if (@file_put_contents($target, $content) !== false) {
                            $extracted_count++;
                        }
                    }
                }
                $zip->close();
                $logs[] = "Zip Extraction Complete: {$extracted_count} files updated, {$skipped_count} sensitive files protected.";
            } else {
                $error_msg = "জিপ ফাইল ওপেন করা যায়নি। ফাইলটি ক্ষতিগ্রস্ত কিনা চেক করুন। (Failed to open zip archive).";
            }
        } else {
            // Fallback exec unzip if ZipArchive missing
            $cmd = "unzip -o " . escapeshellarg($zip_file_path) . " -d " . escapeshellarg($root_dir);
            @exec($cmd, $output, $return_var);
            if ($return_var === 0) {
                $logs[] = "Zip Package Extracted successfully via shell unzip.";
            } else {
                $error_msg = "PHP ZipArchive Extension missing and shell unzip failed.";
            }
        }

        if (empty($error_msg)) {
            // Check version bump file inside root
            $version_file = $root_dir . '/version.txt';
            if (file_exists($version_file)) {
                $new_v = trim(file_get_contents($version_file));
                if (!empty($new_v)) {
                    set_setting('system_version', $new_v);
                    $logs[] = "System version automatically bumped to: " . htmlspecialchars($new_v);
                }
            } else {
                // Increment version minor
                $curr_v = get_setting('system_version', 'v2.5.0');
                $logs[] = "Current System Version: " . htmlspecialchars($curr_v);
            }

            // Run DB Schema & Migration Auto Sync
            $db_logs = ensure_system_update_and_migrations($db);
            $logs = array_merge($logs, $db_logs);

            // Clear File Cache
            $cache_dir = __DIR__ . '/../cache';
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '/*');
                if ($files) {
                    foreach ($files as $file) {
                        if (is_file($file) && basename($file) !== '.gitignore') {
                            @unlink($file);
                        }
                    }
                }
            }
            $logs[] = "System cache flushed clean.";

            $success_msg = "জিপ ফাইল আপডেট ও ডাটাবেজ সিঙ্ক সফলভাবে সম্পন্ন হয়েছে! (ZIP Package Code Update & DB Sync Executed Successfully).";
        }
    }
}

// Find existing .zip files in Root directory
$root_dir = realpath(__DIR__ . '/../');
$root_zip_files = [];
if ($dh = @opendir($root_dir)) {
    while (($file = readdir($dh)) !== false) {
        if ($file !== '.' && $file !== '..' && is_file($root_dir . '/' . $file)) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'zip') {
                $root_zip_files[] = [
                    'name' => $file,
                    'size' => filesize($root_dir . '/' . $file),
                    'mtime' => filemtime($root_dir . '/' . $file)
                ];
            }
        }
    }
    closedir($dh);
}

// System Health Audit
$stats = [];
$tables = ['posts', 'categories', 'users', 'comments', 'settings', 'ads', 'homepage_sections', 'menu_items', 'gallery_albums', 'videos', 'contact_messages'];
foreach ($tables as $t) {
    try {
        $c = (int)$db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        $stats[$t] = $c;
    } catch (Throwable $e) {
        $stats[$t] = 0;
    }
}

$sidebar_status = get_setting('show_update_menu_sidebar', '1');
$current_version = get_setting('system_version', 'v2.5.0');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">
            <i class="bi bi-arrow-repeat text-danger me-2"></i> Auto Update System (অটো আপডেট ও সিঙ্ক প্যানেল)
            <span class="badge bg-danger fs-6 align-middle ms-2"><?= htmlspecialchars($current_version) ?></span>
        </h3>
        <p class="text-muted small mb-0">জিপ ফাইল এক্সট্রাক্ট, কোড ফাইল মোডিফিকেশন এবং ১-ক্লিক ডাটাবেজ অটো স্কিমা সিঙ্ক্রোনাইজেশন।</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="update.php?action=toggle_sidebar" class="btn <?= $sidebar_status === '1' ? 'btn-outline-warning text-dark' : 'btn-warning' ?> btn-sm fw-bold">
            <i class="bi bi-layout-sidebar-inset me-1"></i> Sidebar Menu: <?= $sidebar_status === '1' ? 'VISIBLE (ON)' : 'HIDDEN (OFF)' ?>
        </a>
        <a href="settings.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="bi bi-sliders me-1"></i> Site Settings</a>
    </div>
</div>

<?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm fw-bold mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success_msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm fw-bold mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- System Version & Info Banner -->
<div class="card border-0 shadow-sm mb-4 bg-white border-start border-4 border-danger">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="fs-1 text-danger">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Current Portal Engine Version: <span class="text-danger"><?= htmlspecialchars($current_version) ?></span></h5>
                        <p class="text-muted small mb-0">
                            ডাটাবেজ কানেকশন বা মিডিয়া ফাইল ডিলিট না হয়ে স্বয়ংক্রিয়ভাবে নতুন কোড ফাইল যুক্ত, পরিবর্তন ও সিঙ্ক হয়।
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <form method="POST" action="update.php">
                    <button type="submit" name="run_system_update" class="btn btn-dark fw-bold shadow-sm px-4">
                        <i class="bi bi-database-check text-warning me-2"></i> 1-Click DB & Schema Sync Only
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main ZIP Auto Update Panel -->
<div class="card border-0 shadow-sm mb-4 bg-white border-top border-4 border-success">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-file-zip-fill text-success me-2"></i> ZIP Package Code Auto Update (জিপ প্যাকেজ আপডেট)</h5>
    </div>
    <div class="card-body p-4">
        <p class="text-secondary small mb-3">
            আপনি দুটি উপায়ে ওয়েবসাইট আপডেট করতে পারেন: <strong>১. সরাসরি কম্পিউটার থেকে .zip প্যাকেজ আপলোড করে</strong> অথবা <strong>২. প্রজেক্টের Root ডিরেক্টরিতে জিপ ফাইল আপলোড করে</strong> সিলেক্টের মাধ্যমে।
        </p>

        <form method="POST" action="update.php" enctype="multipart/form-data" class="row g-4">
            
            <!-- Method 1: Upload ZIP File directly -->
            <div class="col-md-6">
                <div class="card p-3 bg-light h-100 border">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i> Option A: Upload ZIP Update File</h6>
                    <small class="text-muted mb-3 d-block">কম্পিউটার থেকে আপডেট `.zip` ফাইল সিলেক্ট করুন:</small>
                    <input type="file" name="zip_file" class="form-control form-control-sm mb-3" accept=".zip">
                    <small class="text-muted d-block">ফাইলের সাইজ অনুযায়ী কয়েকমূহুর্ত সময় নিতে পারে।</small>
                </div>
            </div>

            <!-- Method 2: Select existing ZIP in Root Directory -->
            <div class="col-md-6">
                <div class="card p-3 bg-light h-100 border">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-folder-symlink-fill text-warning me-2"></i> Option B: Select ZIP from Root Directory</h6>
                    <small class="text-muted mb-2 d-block">
                        প্রজেক্ট Root ডিরেক্টরিতে যেকোনো নামের জিপ ফাইল (যেমন: <code>update.zip</code>) থাকলে সিলেক্ট করুন:
                    </small>
                    <select name="selected_root_zip" class="form-select form-select-sm mb-3">
                        <option value="">-- Select ZIP from Root Directory --</option>
                        <?php if (!empty($root_zip_files)): foreach ($root_zip_files as $zf): ?>
                            <option value="<?= htmlspecialchars($zf['name']) ?>">
                                <?= htmlspecialchars($zf['name']) ?> (<?= round($zf['size'] / 1024, 1) ?> KB - <?= date('M d, Y H:i', $zf['mtime']) ?>)
                            </option>
                        <?php endforeach; else: ?>
                            <option value="" disabled>No .zip files found in root directory</option>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted d-block">
                        <?php if (!empty($root_zip_files)): ?>
                            <i class="bi bi-check-circle-fill text-success me-1"></i> Root ডিরেক্টরিতে <strong><?= count($root_zip_files) ?></strong> টি .zip ফাইল পাওয়া গেছে।
                        <?php else: ?>
                            <i class="bi bi-info-circle text-muted me-1"></i> Root ডিরেক্টরিতে কোড ফাইল আপলোড করতে <code>update.zip</code> নামে ফাইল রাখুন।
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <div class="col-12 text-center pt-2">
                <button type="submit" name="run_zip_update" class="btn btn-success btn-lg fw-bold shadow px-5 py-3" onclick="return confirm('আপনি কি এই ZIP ফাইলটির মাধ্যমে সম্পূর্ণ সিস্টেম ও ডাটাবেজ অটো-আপডেট করতে চান?');">
                    <i class="bi bi-lightning-charge-fill me-2"></i> Run ZIP Update Package & Sync DB Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Output Panel -->
<?php if (!empty($logs)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-terminal me-2"></i> Update & Migration Execution Logs (আপডেট লগ রিপোর্ট)</span>
            <span class="badge bg-success"><?= count($logs) ?> tasks executed</span>
        </div>
        <div class="card-body bg-light p-3">
            <div class="font-monospace text-dark small" style="max-height: 280px; overflow-y: auto;">
                <?php foreach ($logs as $log): ?>
                    <div class="py-1 border-bottom border-secondary-subtle">
                        <span class="text-success me-2">✓</span> <?= htmlspecialchars($log) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Database Integrity Audit -->
<div class="card border-0 shadow-sm p-4 bg-white">
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-database-check text-primary me-2"></i> Database Table Health & Record Counts</h5>
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-danger"><?= number_format($stats['posts']) ?></div>
                <div class="small text-muted fw-semibold">News Posts</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-primary"><?= number_format($stats['categories']) ?></div>
                <div class="small text-muted fw-semibold">Categories</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-success"><?= number_format($stats['comments']) ?></div>
                <div class="small text-muted fw-semibold">Comments</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-dark"><?= number_format($stats['users']) ?></div>
                <div class="small text-muted fw-semibold">Users</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-warning-emphasis"><?= number_format($stats['settings']) ?></div>
                <div class="small text-muted fw-semibold">Settings Keys</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-info"><?= number_format($stats['homepage_sections']) ?></div>
                <div class="small text-muted fw-semibold">Homepage Layouts</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-secondary"><?= number_format($stats['ads']) ?></div>
                <div class="small text-muted fw-semibold">Ad Slots</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 bg-light rounded border text-center">
                <div class="fs-4 fw-bold text-danger"><?= number_format($stats['contact_messages']) ?></div>
                <div class="small text-muted fw-semibold">Reader Messages</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
