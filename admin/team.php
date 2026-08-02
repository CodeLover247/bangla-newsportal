<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
check_install_status();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = get_db_connection();
if (function_exists('ensure_team_members_table')) {
    ensure_team_members_table($db);
}

$msg = '';
$err = '';

// Handle Add / Edit Team Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_member'])) {
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    $member_id = trim($_POST['member_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? 'Editorial');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $upazila = trim($_POST['upazila'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    // Handle Image Upload if provided
    $image = trim($_POST['image_url'] ?? '');
    if (!empty($_FILES['image_file']['name'])) {
        $up = handle_file_upload($_FILES['image_file'], 'team');
        if ($up['success']) {
            $image = $up['filepath'];
        }
    }

    if (empty($member_id)) {
        $member_id = 'TMP-' . rand(100, 999);
    }

    if (!empty($name) && !empty($position)) {
        if ($edit_id > 0) {
            $stmt = $db->prepare("UPDATE team_members SET member_id=?, name=?, name_en=?, position=?, department=?, mobile=?, email=?, district=?, upazila=?, image=?, bio=?, facebook=?, twitter=?, linkedin=?, whatsapp=?, display_order=?, status=? WHERE id=?");
            $stmt->execute([$member_id, $name, $name_en, $position, $department, $mobile, $email, $district, $upazila, $image, $bio, $facebook, $twitter, $linkedin, $whatsapp, $display_order, $status, $edit_id]);
            $msg = "Team member updated successfully!";
        } else {
            $stmt = $db->prepare("INSERT INTO team_members (member_id, name, name_en, position, department, mobile, email, district, upazila, image, bio, facebook, twitter, linkedin, whatsapp, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $name, $name_en, $position, $department, $mobile, $email, $district, $upazila, $image, $bio, $facebook, $twitter, $linkedin, $whatsapp, $display_order, $status]);
            $msg = "Team member added successfully!";
        }
    } else {
        $err = "Please enter Member Name and Position/Designation.";
    }
}

// Single Delete Handler
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $stmt = $db->prepare("DELETE FROM team_members WHERE id = ?");
    $stmt->execute([$del_id]);
    header('Location: team.php?msg=deleted');
    exit;
}

// Bulk Actions Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action_submit'])) {
    $bulk_action = $_POST['bulk_action'] ?? '';
    $selected_ids = $_POST['selected_ids'] ?? [];
    if (!empty($selected_ids) && is_array($selected_ids)) {
        $sanitized_ids = array_map('intval', $selected_ids);
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));

        if ($bulk_action === 'delete') {
            $stmt = $db->prepare("DELETE FROM team_members WHERE id IN ($placeholders)");
            $stmt->execute($sanitized_ids);
            $msg = count($sanitized_ids) . " members deleted successfully!";
        } elseif ($bulk_action === 'activate') {
            $stmt = $db->prepare("UPDATE team_members SET status = 1 WHERE id IN ($placeholders)");
            $stmt->execute($sanitized_ids);
            $msg = count($sanitized_ids) . " members activated!";
        } elseif ($bulk_action === 'hide') {
            $stmt = $db->prepare("UPDATE team_members SET status = 0 WHERE id IN ($placeholders)");
            $stmt->execute($sanitized_ids);
            $msg = count($sanitized_ids) . " members hidden!";
        }
    }
}

require_once __DIR__ . '/header.php';

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Team member deleted successfully!";
}

// Editing item fetch
$edit_member = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_member = $stmt->fetch();
}

// Filters & Search Options
$search = trim($_GET['search'] ?? '');
$dept_filter = trim($_GET['department'] ?? '');
$dist_filter = trim($_GET['district'] ?? '');
$upazila_filter = trim($_GET['upazila'] ?? '');
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$view_mode = trim($_GET['view'] ?? 'list');
$limit = max(6, (int)($_GET['limit'] ?? 12));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$filter_options = [
    'search' => $search,
    'department' => $dept_filter,
    'district' => $dist_filter,
    'upazila' => $upazila_filter,
    'status' => $status_filter,
    'limit' => $limit,
    'offset' => $offset,
    'order_by' => 'display_order ASC, id ASC'
];

$team_members = get_team_members($filter_options);
$total_members = get_team_members_count($filter_options);
$total_pages = ceil($total_members / $limit);

$all_departments = get_team_departments();
$all_districts = get_team_districts();
$all_upazilas = get_team_upazilas();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-people-fill text-danger me-2"></i> Our Team Management</h3>
        <p class="text-muted small mb-0">Manage reporters, editors, district/upazila correspondents, staff & IT team</p>
    </div>
    <div>
        <a href="../team.php" target="_blank" class="btn btn-outline-danger btn-sm me-2 fw-semibold">
            <i class="bi bi-box-arrow-up-right me-1"></i> Preview Team Page
        </a>
        <button class="btn btn-danger btn-sm fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#teamFormCollapse" aria-expanded="<?= $edit_member ? 'true' : 'false' ?>">
            <i class="bi bi-plus-circle me-1"></i> <?= $edit_member ? 'Edit Member' : 'Add New Team Member' ?>
        </button>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show fw-semibold"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show fw-semibold"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Add / Edit Collapsible Form -->
<div class="collapse <?= ($edit_member || !empty($err)) ? 'show' : '' ?> mb-4" id="teamFormCollapse">
    <div class="card card-body shadow-sm border-0 bg-light p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-person-lines-fill me-2"></i><?= $edit_member ? 'Edit Team Member Details' : 'Add New Member to Our Team' ?></h5>
            <?php if ($edit_member): ?>
                <a href="team.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i> Cancel Edit</a>
            <?php endif; ?>
        </div>

        <form action="team.php?view=<?= urlencode($view_mode) ?>&limit=<?= $limit ?>" method="POST" enctype="multipart/form-data">
            <?php if ($edit_member): ?>
                <input type="hidden" name="edit_id" value="<?= $edit_member['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Member / ID No</label>
                    <input type="text" name="member_id" class="form-control font-monospace" placeholder="e.g. TMP-101" value="<?= htmlspecialchars($edit_member['member_id'] ?? '') ?>">
                    <small class="text-muted">Unique staff/member ID code</small>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold">Member Name (Bengali) *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. মুহা. আব্দুল হান্নান" value="<?= htmlspecialchars($edit_member['name'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Member Name (English)</label>
                    <input type="text" name="name_en" class="form-control" placeholder="e.g. Muha. Abdul Hannan" value="<?= htmlspecialchars($edit_member['name_en'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Position / Designation *</label>
                    <input type="text" name="position" class="form-control" required placeholder="e.g. সম্পাদক, প্রধান বার্তা সম্পাদক, উপজেলা প্রতিনিধি" value="<?= htmlspecialchars($edit_member['position'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Department / Group</label>
                    <select name="department" class="form-select">
                        <?php $dept = $edit_member['department'] ?? 'Editorial'; ?>
                        <option value="Editorial" <?= $dept === 'Editorial' ? 'selected' : '' ?>>Editorial (সম্পাদকীয়)</option>
                        <option value="Reporting" <?= $dept === 'Reporting' ? 'selected' : '' ?>>Reporting (রিপোর্টিং)</option>
                        <option value="Management" <?= $dept === 'Management' ? 'selected' : '' ?>>Management (ব্যবস্থাপনা)</option>
                        <option value="Technical" <?= $dept === 'Technical' ? 'selected' : '' ?>>Technical & IT (আইটি ও কারিগরি)</option>
                        <option value="Photojournalism" <?= $dept === 'Photojournalism' ? 'selected' : '' ?>>Photojournalism (ছবি ও ভিডিও)</option>
                        <option value="Circulation" <?= $dept === 'Circulation' ? 'selected' : '' ?>>Circulation & Ads (প্রচার ও বিজ্ঞাপন)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Mobile / Phone No</label>
                    <input type="text" name="mobile" class="form-control font-monospace" placeholder="e.g. 01711000000" value="<?= htmlspecialchars($edit_member['mobile'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. reporter@babuganjlive.com" value="<?= htmlspecialchars($edit_member['email'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">District (জেলা)</label>
                    <input type="text" name="district" class="form-control" placeholder="e.g. বরিশাল, ঢাকা" value="<?= htmlspecialchars($edit_member['district'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Upazila / Thana (উপজেলা/থানা)</label>
                    <input type="text" name="upazila" class="form-control" placeholder="e.g. বাবুগঞ্জ, উজিরপুর, সদর" value="<?= htmlspecialchars($edit_member['upazila'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Upload Photo File</label>
                    <input type="file" name="image_file" class="form-control" accept="image/*">
                    <small class="text-muted">Or enter URL below</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Photo Image URL</label>
                    <input type="text" name="image_url" class="form-control font-monospace" placeholder="https://..." value="<?= htmlspecialchars($edit_member['image'] ?? '') ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Short Bio / Profile Summary</label>
                    <textarea name="bio" class="form-control" rows="2" placeholder="Brief details about the member's background or responsibilities..."><?= htmlspecialchars($edit_member['bio'] ?? '') ?></textarea>
                </div>

                <!-- Social Links -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="bi bi-facebook text-primary me-1"></i> Facebook URL</label>
                    <input type="text" name="facebook" class="form-control form-control-sm" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($edit_member['facebook'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="bi bi-whatsapp text-success me-1"></i> WhatsApp No</label>
                    <input type="text" name="whatsapp" class="form-control form-control-sm font-monospace" placeholder="017..." value="<?= htmlspecialchars($edit_member['whatsapp'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="bi bi-twitter-x text-dark me-1"></i> Twitter / X</label>
                    <input type="text" name="twitter" class="form-control form-control-sm" placeholder="https://x.com/..." value="<?= htmlspecialchars($edit_member['twitter'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small"><i class="bi bi-linkedin text-info me-1"></i> LinkedIn URL</label>
                    <input type="text" name="linkedin" class="form-control form-control-sm" placeholder="https://linkedin.com/..." value="<?= htmlspecialchars($edit_member['linkedin'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= (int)($edit_member['display_order'] ?? 0) ?>">
                    <small class="text-muted">Lower numbers appear first</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Visibility Status</label>
                    <?php $st = (int)($edit_member['status'] ?? 1); ?>
                    <select name="status" class="form-select">
                        <option value="1" <?= $st === 1 ? 'selected' : '' ?>>Active / Show on Website</option>
                        <option value="0" <?= $st === 0 ? 'selected' : '' ?>>Hidden / Draft</option>
                    </select>
                </div>

                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" name="save_member" class="btn btn-danger fw-bold w-100 py-2"><i class="bi bi-save me-1"></i> <?= $edit_member ? 'Update Team Member' : 'Save Team Member' ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search, Filter & Toolbar -->
<div class="card border-0 shadow-sm p-3 mb-4 bg-white">
    <form method="GET" action="team.php" class="row g-2 align-items-center">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">

        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search name, ID, phone..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
            </div>
        </div>

        <div class="col-md-2">
            <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($all_departments as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>" <?= $dept_filter === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="district" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Districts</option>
                <?php foreach ($all_districts as $dis): ?>
                    <option value="<?= htmlspecialchars($dis) ?>" <?= $dist_filter === $dis ? 'selected' : '' ?>><?= htmlspecialchars($dis) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="upazila" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Upazilas</option>
                <?php foreach ($all_upazilas as $upz): ?>
                    <option value="<?= htmlspecialchars($upz) ?>" <?= $upazila_filter === $upz ? 'selected' : '' ?>><?= htmlspecialchars($upz) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-1">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="1" <?= $status_filter === 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $status_filter === 0 ? 'selected' : '' ?>>Hidden</option>
            </select>
        </div>

        <div class="col-md-2 text-end d-flex gap-1 justify-content-end">
            <?php if (!empty($search) || !empty($dept_filter) || !empty($dist_filter) || !empty($upazila_filter) || $status_filter !== null): ?>
                <a href="team.php?view=<?= urlencode($view_mode) ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i> Reset</a>
            <?php endif; ?>

            <div class="btn-group btn-group-sm">
                <a href="team.php?search=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&status=<?= $status_filter ?>&limit=<?= $limit ?>&view=list" class="btn btn-<?= $view_mode === 'list' ? 'danger' : 'outline-secondary' ?>" title="List View"><i class="bi bi-list-task"></i></a>
                <a href="team.php?search=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&status=<?= $status_filter ?>&limit=<?= $limit ?>&view=grid" class="btn btn-<?= $view_mode === 'grid' ? 'danger' : 'outline-secondary' ?>" title="Grid View"><i class="bi bi-grid-fill"></i></a>
            </div>
        </div>
    </form>
</div>

<!-- Team Members Main Table / Grid Container -->
<form method="POST" action="team.php?view=<?= urlencode($view_mode) ?>&limit=<?= $limit ?>">
    <div class="card border-0 shadow-sm p-3 bg-white mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllTeam" onclick="toggleSelectAllTeam(this)">
                    <label class="form-check-label fw-bold small" for="selectAllTeam">Select All</label>
                </div>
                <select name="bulk_action" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate / Show Selected</option>
                    <option value="hide">Hide Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" name="bulk_action_submit" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Apply selected action?');">
                    Apply Action
                </button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Per Page:</span>
                <select name="limit" class="form-select form-select-sm" style="width: auto;" onchange="location.href='team.php?view=<?= urlencode($view_mode) ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&status=<?= $status_filter ?>&limit=' + this.value">
                    <option value="12" <?= $limit == 12 ? 'selected' : '' ?>>12</option>
                    <option value="24" <?= $limit == 24 ? 'selected' : '' ?>>24</option>
                    <option value="48" <?= $limit == 48 ? 'selected' : '' ?>>48</option>
                </select>
                <span class="small text-muted border-start ps-2">Total: <?= number_format($total_members) ?> members</span>
            </div>
        </div>

        <?php if ($view_mode === 'grid'): ?>
            <!-- GRID VIEW -->
            <div class="row g-3">
                <?php if (!empty($team_members)): foreach ($team_members as $m): 
                    $photo = !empty($m['image']) ? get_media_url($m['image']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80';
                ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="card h-100 border shadow-sm p-3 text-center bg-light position-relative">
                            <div class="position-absolute top-0 start-0 m-2">
                                <input class="form-check-input team-checkbox shadow-sm" type="checkbox" name="selected_ids[]" value="<?= $m['id'] ?>">
                            </div>
                            <div class="position-absolute top-0 end-0 m-2">
                                <?php if ($m['status'] == 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Hidden</span>
                                <?php endif; ?>
                            </div>

                            <img src="<?= htmlspecialchars($photo) ?>" class="rounded-circle mx-auto mb-2 border border-2 border-danger shadow-sm" style="width: 80px; height: 80px; object-fit: cover;" alt="<?= htmlspecialchars($m['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80'">

                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($m['name']) ?></h6>
                            <small class="text-danger fw-semibold d-block mb-1"><?= htmlspecialchars($m['position']) ?></small>
                            <span class="badge bg-dark mb-2 font-monospace" style="font-size: 0.7rem;"><?= htmlspecialchars($m['member_id'] ?: 'ID: ' . $m['id']) ?></span>

                            <div class="small text-muted text-truncate mb-1">
                                <i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars(implode(', ', array_filter([$m['upazila'], $m['district']]))) ?: 'N/A' ?>
                            </div>

                            <?php if (!empty($m['mobile'])): ?>
                                <small class="text-muted font-monospace d-block mb-2"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($m['mobile']) ?></small>
                            <?php endif; ?>

                            <div class="mt-auto pt-2 border-top d-flex gap-1 justify-content-center">
                                <a href="team.php?action=edit&id=<?= $m['id'] ?>&view=grid" class="btn btn-sm btn-outline-primary w-50"><i class="bi bi-pencil me-1"></i> Edit</a>
                                <a href="team.php?action=delete&id=<?= $m['id'] ?>&view=grid" class="btn btn-sm btn-outline-danger w-50" onclick="return confirm('Delete this team member?');"><i class="bi bi-trash me-1"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted py-5">No team members found matching criteria.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- LIST VIEW -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 30px;"></th>
                            <th style="width: 60px;">Photo</th>
                            <th>ID & Name</th>
                            <th>Position & Dept</th>
                            <th>District / Upazila</th>
                            <th>Mobile & Email</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($team_members)): foreach ($team_members as $m): 
                            $photo = !empty($m['image']) ? get_media_url($m['image']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80';
                        ?>
                            <tr>
                                <td>
                                    <input class="form-check-input team-checkbox" type="checkbox" name="selected_ids[]" value="<?= $m['id'] ?>">
                                </td>
                                <td>
                                    <img src="<?= htmlspecialchars($photo) ?>" class="rounded-circle border" style="width: 45px; height: 45px; object-fit: cover;" alt="" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80'">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                    <small class="text-muted font-monospace"><span class="badge bg-secondary me-1"><?= htmlspecialchars($m['member_id'] ?: 'ID: ' . $m['id']) ?></span> <?= htmlspecialchars($m['name_en'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-danger d-block small"><?= htmlspecialchars($m['position']) ?></span>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['department']) ?></span>
                                </td>
                                <td>
                                    <small class="fw-semibold text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars(implode(', ', array_filter([$m['upazila'], $m['district']]))) ?: 'N/A' ?></small>
                                </td>
                                <td>
                                    <div class="small font-monospace"><?= htmlspecialchars($m['mobile'] ?: '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['email'] ?: '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark font-monospace"><?= (int)$m['display_order'] ?></span>
                                </td>
                                <td>
                                    <?php if ($m['status'] == 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="team.php?action=edit&id=<?= $m['id'] ?>&view=list" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <a href="team.php?action=delete&id=<?= $m['id'] ?>&view=list" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this team member?');"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No team members found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="border-top d-flex justify-content-between align-items-center mt-4 pt-3">
                <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="team.php?search=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&status=<?= $status_filter ?>&limit=<?= $limit ?>&view=<?= urlencode($view_mode) ?>&page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active bg-danger' : '' ?>">
                                <a class="page-link" href="team.php?search=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&status=<?= $status_filter ?>&limit=<?= $limit ?>&view=<?= urlencode($view_mode) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="team.php?search=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&status=<?= $status_filter ?>&limit=<?= $limit ?>&view=<?= urlencode($view_mode) ?>&page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</form>

<script>
function toggleSelectAllTeam(master) {
    let checkboxes = document.querySelectorAll('.team-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
