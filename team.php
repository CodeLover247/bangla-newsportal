<?php
require_once __DIR__ . '/includes/header.php';

// Check if Our Team page is enabled by site administrator
$is_team_enabled = get_setting('show_team_page', '1') === '1';

// Filters & Search Options
$search = trim($_GET['q'] ?? $_GET['search'] ?? '');
$dept_filter = trim($_GET['department'] ?? '');
$dist_filter = trim($_GET['district'] ?? '');
$upazila_filter = trim($_GET['upazila'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$filter_options = [
    'status' => 1, // Only active/published team members on public site
    'search' => $search,
    'department' => $dept_filter,
    'district' => $dist_filter,
    'upazila' => $upazila_filter,
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

<div class="container my-4">
    <!-- Breadcrumb Nav -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-danger"><i class="bi bi-house-door-fill me-1"></i> <?= __('প্রচ্ছদ', 'Home') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= __('আমাদের টিম', 'Our Team') ?></li>
        </ol>
    </nav>

    <?php if (!$is_team_enabled): ?>
        <div class="card p-5 text-center border-0 shadow-sm bg-light my-4">
            <div class="mb-3 text-secondary"><i class="bi bi-people fs-1"></i></div>
            <h4 class="fw-bold text-dark"><?= __('আমাদের টিম পাতাটি সাময়িকভাবে বন্ধ আছে', 'Our Team Page is Currently Disabled') ?></h4>
            <p class="text-muted"><?= __('ওয়েবসাইট এডমিনিস্ট্রেটর এই পাতাটি সাময়িকভাবে বন্ধ রেখেছেন।', 'The site administrator has disabled this section.') ?></p>
            <div>
                <a href="index.php" class="btn btn-danger font-semibold px-4"><i class="bi bi-arrow-left me-1"></i> <?= __('প্রচ্ছদে ফিরে যান', 'Back to Homepage') ?></a>
            </div>
        </div>
    <?php else: ?>

        <!-- Hero Header -->
        <div class="card border-0 rounded-3 shadow-sm bg-danger text-white p-4 p-md-5 mb-4 position-relative overflow-hidden">
            <div class="position-relative z-1">
                <span class="badge bg-white text-danger fw-bold text-uppercase px-3 py-2 mb-2"><i class="bi bi-person-vcard me-1"></i> <?= __('সাংবাদিক ও কর্মী তালিকা', 'Directory & Staff') ?></span>
                <h2 class="fw-bold mb-2 display-6"><?= __('আমাদের কর্মীবৃন্দ ও জেলা-উপজেলা প্রতিনিধি', 'Our Team & Correspondents') ?></h2>
                <p class="mb-0 text-white-50 max-w-2xl fs-6">
                    <?= __('আমাদের নিবেদিতপ্রাণ সম্পাদক, বার্তা বিভাগ, আইটি এবং দেশের বিভিন্ন জেলা ও উপজেলার সত্যনিষ্ঠ সাংবাদিকদের তালিকা।', 'Our dedicated editorial board, reporting department, IT specialists, and field correspondents.') ?>
                </p>
            </div>
            <div class="position-absolute end-0 bottom-0 opacity-10 me-4 mb-2 d-none d-md-block">
                <i class="bi bi-people-fill" style="font-size: 11rem; line-height: 0.8;"></i>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 bg-white rounded-3">
            <form method="GET" action="team.php" class="row g-3 align-items-center">
                <!-- Keyword Search -->
                <div class="col-lg-4 col-md-12">
                    <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-search me-1 text-danger"></i> <?= __('অনুসন্ধান করুন', 'Search Keyword') ?></label>
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="<?= __('নাম, আইডি নং, পদবি, জেলা, মোবাইল...', 'Name, ID, Designation, Mobile...') ?>" value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-danger fw-semibold px-3" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>

                <!-- Department Filter -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-building me-1 text-danger"></i> <?= __('বিভাগ / টিম', 'Department') ?></label>
                    <select name="department" class="form-select" onchange="this.form.submit()">
                        <option value=""><?= __('সকল বিভাগ', 'All Departments') ?></option>
                        <?php foreach ($all_departments as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $dept_filter === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- District Filter -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= __('জেলা', 'District') ?></label>
                    <select name="district" class="form-select" onchange="this.form.submit()">
                        <option value=""><?= __('সকল জেলা', 'All Districts') ?></option>
                        <?php foreach ($all_districts as $dis): ?>
                            <option value="<?= htmlspecialchars($dis) ?>" <?= $dist_filter === $dis ? 'selected' : '' ?>><?= htmlspecialchars($dis) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Upazila Filter -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-map me-1 text-danger"></i> <?= __('উপজেলা / থানা', 'Upazila') ?></label>
                    <select name="upazila" class="form-select" onchange="this.form.submit()">
                        <option value=""><?= __('সকল উপজেলা', 'All Upazilas') ?></option>
                        <?php foreach ($all_upazilas as $upz): ?>
                            <option value="<?= htmlspecialchars($upz) ?>" <?= $upazila_filter === $upz ? 'selected' : '' ?>><?= htmlspecialchars($upz) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action buttons -->
                <div class="col-lg-2 col-md-12 d-flex align-items-end gap-2">
                    <?php if (!empty($search) || !empty($dept_filter) || !empty($dist_filter) || !empty($upazila_filter)): ?>
                        <a href="team.php" class="btn btn-outline-secondary w-100 fw-semibold" title="Reset Filter"><i class="bi bi-arrow-counterclockwise me-1"></i> <?= __('ফিল্টার মুছুন', 'Reset') ?></a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-dark w-100 fw-semibold"><i class="bi bi-funnel me-1"></i> <?= __('ফিল্টার', 'Filter') ?></button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Quick Department Filter Badges -->
        <?php if (!empty($all_departments)): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                <span class="small fw-bold text-muted me-2"><?= __('বিভাগসমূহ:', 'Departments:') ?></span>
                <a href="team.php?q=<?= urlencode($search) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>" class="badge rounded-pill <?= empty($dept_filter) ? 'bg-danger text-white' : 'bg-light text-dark border' ?> px-3 py-2 text-decoration-none">
                    <?= __('সকল', 'All') ?>
                </a>
                <?php foreach ($all_departments as $dept_item): ?>
                    <a href="team.php?department=<?= urlencode($dept_item) ?>&q=<?= urlencode($search) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>" class="badge rounded-pill <?= $dept_filter === $dept_item ? 'bg-danger text-white' : 'bg-light text-dark border' ?> px-3 py-2 text-decoration-none">
                        <?= htmlspecialchars($dept_item) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Team Members Grid Cards -->
        <?php if (!empty($team_members)): ?>
            <div class="row g-4 mb-5">
                <?php foreach ($team_members as $m): 
                    $photo = !empty($m['image']) ? get_media_url($m['image']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80';
                    $location_str = implode(', ', array_filter([$m['upazila'], $m['district']]));
                ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden text-center bg-white transition-hover hover-shadow-lg">
                            <!-- Card Header Image Container -->
                            <div class="bg-light p-4 text-center border-bottom position-relative">
                                <?php if (!empty($m['member_id'])): ?>
                                    <span class="position-absolute top-0 start-0 m-2 badge bg-dark font-monospace" style="font-size: 0.72rem; opacity: 0.9;"><?= htmlspecialchars($m['member_id']) ?></span>
                                <?php endif; ?>

                                <?php if (!empty($m['department'])): ?>
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-danger text-uppercase" style="font-size: 0.68rem;"><?= htmlspecialchars($m['department']) ?></span>
                                <?php endif; ?>

                                <img src="<?= htmlspecialchars($photo) ?>" class="rounded-circle border border-3 border-danger shadow mx-auto mt-2" style="width: 110px; height: 110px; object-fit: cover;" alt="<?= htmlspecialchars($m['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80'">
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-3 d-flex flex-column">
                                <h5 class="fw-bold mb-1 text-dark fs-6"><?= htmlspecialchars($m['name']) ?></h5>
                                <?php if (!empty($m['name_en'])): ?>
                                    <small class="text-muted d-block mb-1" style="font-size: 0.78rem;"><?= htmlspecialchars($m['name_en']) ?></small>
                                <?php endif; ?>

                                <div class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-semibold align-self-center my-2" style="font-size: 0.8rem;">
                                    <?= htmlspecialchars($m['position']) ?>
                                </div>

                                <?php if (!empty($location_str)): ?>
                                    <div class="small text-muted mb-2">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($location_str) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($m['bio'])): ?>
                                    <p class="small text-secondary mb-3 lh-sm text-clamp-2 px-2" style="font-size: 0.82rem;">
                                        <?= htmlspecialchars($m['bio']) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-auto pt-3 border-top w-100">
                                    <?php if (!empty($m['mobile'])): ?>
                                        <a href="tel:<?= htmlspecialchars($m['mobile']) ?>" class="btn btn-sm btn-outline-danger w-100 fw-bold mb-2">
                                            <i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($m['mobile']) ?>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Social Link Icons -->
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php if (!empty($m['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn btn-sm btn-light border text-danger" title="<?= htmlspecialchars($m['email']) ?>"><i class="bi bi-envelope-fill"></i></a>
                                        <?php endif; ?>

                                        <?php if (!empty($m['facebook'])): ?>
                                            <a href="<?= htmlspecialchars($m['facebook']) ?>" target="_blank" class="btn btn-sm btn-light border text-primary" title="Facebook Profile"><i class="bi bi-facebook"></i></a>
                                        <?php endif; ?>

                                        <?php if (!empty($m['whatsapp'])): ?>
                                            <?php 
                                            $wa_clean = preg_replace('/[^0-9]/', '', $m['whatsapp']);
                                            if (strlen($wa_clean) === 11 && strpos($wa_clean, '01') === 0) {
                                                $wa_clean = '88' . $wa_clean;
                                            }
                                            ?>
                                            <a href="https://wa.me/<?= htmlspecialchars($wa_clean) ?>" target="_blank" class="btn btn-sm btn-light border text-success" title="WhatsApp Chat"><i class="bi bi-whatsapp"></i></a>
                                        <?php endif; ?>

                                        <?php if (!empty($m['twitter'])): ?>
                                            <a href="<?= htmlspecialchars($m['twitter']) ?>" target="_blank" class="btn btn-sm btn-light border text-dark" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
                                        <?php endif; ?>

                                        <?php if (!empty($m['linkedin'])): ?>
                                            <a href="<?= htmlspecialchars($m['linkedin']) ?>" target="_blank" class="btn btn-sm btn-light border text-info" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top mb-5">
                    <span class="small text-muted"><?= __('মোট', 'Total') ?> <?= number_format($total_members) ?> <?= __('জন টিম মেম্বারের মধ্যে', 'members found, showing page') ?> <?= $page ?> / <?= $total_pages ?></span>
                    <nav aria-label="Team pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="team.php?q=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&page=<?= $page - 1 ?>"><?= __('পূর্ববর্তী', 'Previous') ?></a>
                            </li>
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?= $page == $p ? 'active bg-danger' : '' ?>">
                                    <a class="page-link" href="team.php?q=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="team.php?q=<?= urlencode($search) ?>&department=<?= urlencode($dept_filter) ?>&district=<?= urlencode($dist_filter) ?>&upazila=<?= urlencode($upazila_filter) ?>&page=<?= $page + 1 ?>"><?= __('পরবর্তী', 'Next') ?></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="card p-5 text-center border-0 shadow-sm bg-white my-4 rounded-3">
                <div class="mb-3 text-danger"><i class="bi bi-search fs-1"></i></div>
                <h4 class="fw-bold text-dark mb-2"><?= __('কোন কর্মী বা প্রতিনিধি পাওয়া যায়নি', 'No Team Members Found') ?></h4>
                <p class="text-muted mb-3"><?= __('আপনার অনুসন্ধানের সাপেক্ষে কোনো ফল পাওয়া যায়নি। ফিল্টার পরিবর্তন বা রিসেট করে চেষ্টা করুন।', 'No team members matched your search or filter options.') ?></p>
                <div>
                    <a href="team.php" class="btn btn-outline-danger fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i> <?= __('সকল সদস্য দেখুন', 'View All Members') ?></a>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
