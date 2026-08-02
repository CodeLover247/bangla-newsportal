<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
ensure_all_ad_positions_and_settings($db);

$msg = '';
$error = '';

// Handle Actions (Update, Create, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if ($action === 'create') {
        $position = trim($_POST['position'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $ad_type = $_POST['ad_type'] ?? 'image';
        $image_url = trim($_POST['image_url'] ?? '');
        $target_url = trim($_POST['target_url'] ?? '');
        $ad_code = $_POST['ad_code'] ?? '';
        $width = trim($_POST['width'] ?? '');
        $height = trim($_POST['height'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;

        if (isset($_FILES['ad_image_file']) && $_FILES['ad_image_file']['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES['ad_image_file'], 'ads');
            if ($upload['success']) {
                $image_url = $upload['file_path'];
            }
        }

        if (empty($position) || empty($title)) {
            $error = "Position key and Banner Title are required!";
        } else {
            // Check if position already exists
            $checkStmt = $db->prepare("SELECT id FROM ads WHERE position = ?");
            $checkStmt->execute([$position]);
            if ($checkStmt->fetch()) {
                $error = "Ad position key '{$position}' already exists!";
            } else {
                $ins = $db->prepare("INSERT INTO ads (position, title, ad_type, image_url, target_url, ad_code, width, height, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$position, $title, $ad_type, $image_url, $target_url, $ad_code, $width, $height, $status]);
                $msg = "New advertisement position '{$title}' created successfully!";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $db->prepare("DELETE FROM ads WHERE id = ?");
            $del->execute([$id]);
            $msg = "Advertisement slot deleted successfully!";
        }
    } else {
        // Update
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $ad_type = $_POST['ad_type'] ?? 'image';
        $image_url = trim($_POST['image_url'] ?? '');
        $target_url = trim($_POST['target_url'] ?? '');
        $ad_code = $_POST['ad_code'] ?? '';
        $width = trim($_POST['width'] ?? '');
        $height = trim($_POST['height'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;

        // Check file upload if provided
        $file_key = "ad_image_file_" . $id;
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES[$file_key], 'ads');
            if ($upload['success']) {
                $image_url = $upload['file_path'];
            }
        }

        $stmt = $db->prepare("UPDATE ads SET title=?, ad_type=?, image_url=?, target_url=?, ad_code=?, width=?, height=?, status=? WHERE id=?");
        $stmt->execute([$title, $ad_type, $image_url, $target_url, $ad_code, $width, $height, $status, $id]);
        $msg = "Advertisement unit updated successfully!";
    }
}

$ads = $db->query("SELECT * FROM ads ORDER BY id ASC")->fetchAll();

$position_labels = [
    'header_top' => 'Header Leaderboard Top Banner (900x120)',
    'header_aside' => 'Beside Logo Header Banner (468x60 / 728x90)',
    'below_header' => 'Below Navigation Bar Banner',
    'homepage_top' => 'Homepage Top Lead Banner',
    'homepage_middle' => 'Homepage Middle Section Banner',
    'sidebar_top' => 'Right Sidebar Top Square Banner (300x250)',
    'sidebar_bottom' => 'Right Sidebar Bottom Square Banner (300x250)',
    'article_top' => 'Article Page Above Content Banner',
    'article_middle' => 'In-Article Middle Content Banner',
    'article_bottom' => 'Article Page Below Content Banner',
    'category_top' => 'Category Page Top Header Banner',
    'footer_top' => 'Above Footer Wide Banner'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-0"><i class="bi bi-badge-ad-fill text-danger me-2"></i> Advertisement Manager</h3>
        <p class="text-muted small mb-0">Manage website ad banners, Google AdSense codes, media images, and placement positions.</p>
    </div>
    <button type="button" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#newAdModal">
        <i class="bi bi-plus-circle me-1"></i> Add Custom Ad Slot
    </button>
</div>

<?php if ($msg): ?><div class="alert alert-success shadow-sm fw-bold"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger shadow-sm fw-bold"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
    <?php foreach ($ads as $ad): 
        $pos_name = $position_labels[$ad['position']] ?? strtoupper(str_replace('_', ' ', $ad['position']));
    ?>
        <div class="col-lg-6">
            <div class="card shadow-sm border h-100 <?= $ad['status'] ? 'border-primary' : 'border-secondary opacity-75' ?>">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                    <div>
                        <span class="badge bg-dark text-uppercase me-2"><?= htmlspecialchars($ad['position']) ?></span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($ad['title']) ?></span>
                    </div>
                    <div>
                        <?php if ($ad['status']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i> Disabled</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="ads.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Banner Name / Client Tag</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($ad['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ad Format / Type</label>
                            <select name="ad_type" class="form-select ad-type-selector" data-target="#ad_inputs_<?= $ad['id'] ?>">
                                <option value="image" <?= $ad['ad_type'] === 'image' ? 'selected' : '' ?>>Custom Image Banner + Target Link</option>
                                <option value="code" <?= $ad['ad_type'] === 'code' ? 'selected' : '' ?>>Google AdSense / Custom HTML & JS Script Code</option>
                            </select>
                        </div>

                        <div id="ad_inputs_<?= $ad['id'] ?>">
                            <!-- Image Banner Options -->
                            <div class="ad-image-fields <?= $ad['ad_type'] === 'code' ? 'd-none' : '' ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Upload Image Banner File</label>
                                    <input type="file" name="ad_image_file_<?= $ad['id'] ?>" class="form-control" accept="image/*">
                                    <small class="text-muted d-block mt-1">Select an image from your computer to upload directly.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">OR Banner Image URL</label>
                                    <div class="input-group">
                                        <input type="text" id="ad_img_<?= $ad['id'] ?>" name="image_url" class="form-control" value="<?= htmlspecialchars($ad['image_url']) ?>" placeholder="https://example.com/banner.jpg">
                                        <button type="button" class="btn btn-dark btn-media-picker" data-target="#ad_img_<?= $ad['id'] ?>"><i class="bi bi-images me-1"></i> Media</button>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Custom Width</label>
                                        <input type="text" name="width" class="form-control form-control-sm" value="<?= htmlspecialchars($ad['width'] ?? '') ?>" placeholder="e.g. 728px or 100%">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Custom Height</label>
                                        <input type="text" name="height" class="form-control form-control-sm" value="<?= htmlspecialchars($ad['height'] ?? '') ?>" placeholder="e.g. 90px or auto">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Target Click URL</label>
                                    <input type="text" name="target_url" class="form-control" value="<?= htmlspecialchars($ad['target_url']) ?>" placeholder="https://advertiser-website.com">
                                </div>

                                <?php if (!empty($ad['image_url'])): ?>
                                    <div class="mb-3 p-2 bg-light rounded text-center border">
                                        <small class="text-muted d-block mb-1">Live Image Preview:</small>
                                        <img src="<?= htmlspecialchars(get_media_url($ad['image_url'])) ?>" class="img-fluid rounded" style="max-height: 90px; object-fit: contain;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Script / Code Options -->
                            <div class="ad-code-fields <?= $ad['ad_type'] === 'image' ? 'd-none' : '' ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">HTML / AdSense / JS Script Code</label>
                                    <textarea name="ad_code" class="form-control font-monospace" rows="4" placeholder="<script async src='https://pagead2.googlesyndication.com...'></script>"><?= htmlspecialchars($ad['ad_code']) ?></textarea>
                                    <small class="text-muted">Paste your Google AdSense code snippet or iframe tag here.</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" id="status_<?= $ad['id'] ?>" value="1" <?= $ad['status'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="status_<?= $ad['id'] ?>">Enable Slot</label>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-save me-1"></i> Update Slot</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="confirmDeleteAd(<?= $ad['id'] ?>, '<?= htmlspecialchars($ad['title']) ?>')"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Create New Custom Ad Unit -->
<div class="modal fade" id="newAdModal" tabindex="-1" aria-labelledby="newAdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="ads.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="newAdModalLabel"><i class="bi bi-plus-circle me-2"></i> Add New Custom Ad Placement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Position Key Identifier *</label>
                        <input type="text" name="position" class="form-control" placeholder="e.g., header_top, article_middle, custom_banner_1" required>
                        <small class="text-muted">Lowercase letters, numbers, and underscores (e.g., <code>homepage_top</code>, <code>article_middle</code>).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Banner Title / Client Name *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Middle Article Leaderboard" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ad Format / Type</label>
                        <select name="ad_type" class="form-select" id="new_ad_type">
                            <option value="image">Custom Banner Image + Target Link</option>
                            <option value="code">Google AdSense / Custom HTML & JS Script Code</option>
                        </select>
                    </div>

                    <div id="new_ad_image_group" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Image Banner File</label>
                            <input type="file" name="ad_image_file" class="form-control" accept="image/*">
                        </div>
                        <label class="form-label fw-semibold">OR Banner Image URL</label>
                        <div class="input-group mb-3">
                            <input type="text" id="new_ad_img" name="image_url" class="form-control" placeholder="https://example.com/image.png">
                            <button type="button" class="btn btn-dark btn-media-picker" data-target="#new_ad_img"><i class="bi bi-images me-1"></i> Media</button>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Custom Width</label>
                                <input type="text" name="width" class="form-control form-control-sm" placeholder="e.g. 728px or 100%">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Custom Height</label>
                                <input type="text" name="height" class="form-control form-control-sm" placeholder="e.g. 90px or auto">
                            </div>
                        </div>
                    </div>

                    <div id="new_ad_target_group" class="mb-3">
                        <label class="form-label fw-semibold">Target Click URL</label>
                        <input type="text" name="target_url" class="form-control" placeholder="https://target-website.com">
                    </div>

                    <div id="new_ad_code_group" class="mb-3 d-none">
                        <label class="form-label fw-semibold">HTML / AdSense Code Snippet</label>
                        <textarea name="ad_code" class="form-control font-monospace" rows="4" placeholder="<script ...></script>"></textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="status" id="new_status" value="1" checked>
                        <label class="form-check-label fw-bold" for="new_status">Enable Ad Slot Immediately</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-check-lg me-1"></i> Create Ad Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteAdForm" action="ads.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_ad_id" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Ad Type selector toggle in card forms
    document.querySelectorAll('.ad-type-selector').forEach(function (select) {
        select.addEventListener('change', function () {
            const targetDiv = document.querySelector(this.dataset.target);
            if (!targetDiv) return;
            const imgFields = targetDiv.querySelector('.ad-image-fields');
            const codeFields = targetDiv.querySelector('.ad-code-fields');

            if (this.value === 'code') {
                if (imgFields) imgFields.classList.add('d-none');
                if (codeFields) codeFields.classList.remove('d-none');
            } else {
                if (imgFields) imgFields.classList.remove('d-none');
                if (codeFields) codeFields.classList.add('d-none');
            }
        });
    });

    // New Ad modal toggle
    const newAdTypeSelect = document.getElementById('new_ad_type');
    if (newAdTypeSelect) {
        newAdTypeSelect.addEventListener('change', function () {
            const imgGrp = document.getElementById('new_ad_image_group');
            const tgtGrp = document.getElementById('new_ad_target_group');
            const codeGrp = document.getElementById('new_ad_code_group');

            if (this.value === 'code') {
                imgGrp.classList.add('d-none');
                tgtGrp.classList.add('d-none');
                codeGrp.classList.remove('d-none');
            } else {
                imgGrp.classList.remove('d-none');
                tgtGrp.classList.remove('d-none');
                codeGrp.classList.add('d-none');
            }
        });
    }
});

function confirmDeleteAd(id, title) {
    if (confirm("Are you sure you want to delete advertisement slot '" + title + "'?")) {
        document.getElementById('delete_ad_id').value = id;
        document.getElementById('deleteAdForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
