<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';
$msg_type = 'success';

$categories = get_categories(0, false);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'global_settings';

    if ($action === 'global_settings') {
        set_setting('home_hero_cat', $_POST['home_hero_cat'] ?? '0');
        set_setting('home_hero_limit', $_POST['home_hero_limit'] ?? '5');
        set_setting('home_show_videos', isset($_POST['home_show_videos']) ? '1' : '0');
        set_setting('home_show_photos', isset($_POST['home_show_photos']) ? '1' : '0');
        set_setting('home_show_breaking', isset($_POST['home_show_breaking']) ? '1' : '0');
        $msg = "Global homepage settings updated successfully!";
    } elseif ($action === 'add_section') {
        $title = trim($_POST['title'] ?? '');
        if (!empty($title)) {
            save_homepage_section([
                'title' => $title,
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'post_limit' => (int)($_POST['post_limit'] ?? 5),
                'layout_style' => $_POST['layout_style'] ?? 'lead_side_list',
                'status' => isset($_POST['status']) ? 1 : 0
            ]);
            $msg = "New homepage section '{$title}' added successfully!";
        } else {
            $msg = "Section title cannot be empty.";
            $msg_type = 'danger';
        }
    } elseif ($action === 'edit_section') {
        $id = (int)($_POST['section_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if ($id > 0 && !empty($title)) {
            save_homepage_section([
                'id' => $id,
                'title' => $title,
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'post_limit' => (int)($_POST['post_limit'] ?? 5),
                'layout_style' => $_POST['layout_style'] ?? 'lead_side_list',
                'section_order' => (int)($_POST['section_order'] ?? 0),
                'status' => isset($_POST['status']) ? 1 : 0
            ]);
            $msg = "Homepage section '{$title}' updated successfully!";
        } else {
            $msg = "Invalid section or empty title.";
            $msg_type = 'danger';
        }
    } elseif ($action === 'delete_section') {
        $id = (int)($_POST['section_id'] ?? 0);
        if ($id > 0) {
            delete_homepage_section($id);
            $msg = "Section deleted successfully! You can re-add it anytime using 'Add New Section'.";
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['section_id'] ?? 0);
        $sec = get_homepage_section($id);
        if ($sec) {
            $new_status = ($sec['status'] == 1) ? 0 : 1;
            $db->prepare("UPDATE homepage_sections SET status = ? WHERE id = ?")->execute([$new_status, $id]);
            $msg = "Section visibility updated!";
        }
    } elseif ($action === 'move_up' || $action === 'move_down') {
        $id = (int)($_POST['section_id'] ?? 0);
        $sections = get_homepage_sections(false);
        $current_index = -1;
        foreach ($sections as $index => $sec) {
            if ($sec['id'] == $id) {
                $current_index = $index;
                break;
            }
        }

        if ($action === 'move_up' && $current_index > 0) {
            $prev = $sections[$current_index - 1];
            $curr = $sections[$current_index];
            $db->prepare("UPDATE homepage_sections SET section_order = ? WHERE id = ?")->execute([$prev['section_order'], $curr['id']]);
            $db->prepare("UPDATE homepage_sections SET section_order = ? WHERE id = ?")->execute([$curr['section_order'], $prev['id']]);
            $msg = "Section order moved up!";
        } elseif ($action === 'move_down' && $current_index >= 0 && $current_index < count($sections) - 1) {
            $next = $sections[$current_index + 1];
            $curr = $sections[$current_index];
            $db->prepare("UPDATE homepage_sections SET section_order = ? WHERE id = ?")->execute([$next['section_order'], $curr['id']]);
            $db->prepare("UPDATE homepage_sections SET section_order = ? WHERE id = ?")->execute([$curr['section_order'], $next['id']]);
            $msg = "Section order moved down!";
        }
    } elseif ($action === 'apply_preset') {
        $preset = $_POST['preset_name'] ?? 'classic_newspaper';
        $db->exec("DELETE FROM homepage_sections");

        // Helper to find category ID by name keyword
        $findCatId = function($nameKeyword) use ($categories) {
            foreach ($categories as $cat) {
                if (mb_strpos($cat['name'], $nameKeyword) !== false) {
                    return $cat['id'];
                }
            }
            return 0;
        };

        $catNational = $findCatId('জাতীয়') ?: $findCatId('National');
        $catBarishal = $findCatId('বরিশাল') ?: $findCatId('Barishal');
        $catPolitics = $findCatId('রাজনীতি') ?: $findCatId('Politics');
        $catBusiness = $findCatId('অর্থনীতি') ?: $findCatId('Business');
        $catSports = $findCatId('খেলা') ?: $findCatId('Sports');
        $catEnt = $findCatId('বিনোদন') ?: $findCatId('Entertainment');
        $catTech = $findCatId('প্রযুক্তি') ?: $findCatId('Technology');

        $presetsData = [
            'classic_newspaper' => [
                ['title' => 'লিডিং ইমেজ সেকশন (Leading Big Image Section)', 'cat' => $catNational, 'limit' => 5, 'style' => 'lead_side_list'],
                ['title' => '২ কলাম নিউজ গ্রিড (2-Column Grid Section)', 'cat' => $catBarishal, 'limit' => 4, 'style' => 'two_column_grid'],
                ['title' => 'ট্রেন্ডিং ওভারলে গ্রিড (Overlay Grid Section)', 'cat' => $catPolitics, 'limit' => 4, 'style' => 'overlay_grid'],
                ['title' => '৩ কার্ড বেনটো গ্রিড (3-Card Bento Grid)', 'cat' => $catBusiness, 'limit' => 3, 'style' => 'bento_grid'],
                ['title' => '৪ কলাম মিডিয়া সেকশন (4 Row Horizontal Cards)', 'cat' => $catSports, 'limit' => 4, 'style' => 'horizontal_cards'],
                ['title' => 'ভিডিও খবর থিয়েটার (Video Gallery Theater)', 'cat' => 0, 'limit' => 4, 'style' => 'video_gallery_theater'],
                ['title' => 'ছবি গ্যালারি সঙ্কলন (Photo Gallery Grid)', 'cat' => 0, 'limit' => 6, 'style' => 'photo_gallery_grid']
            ],
            'modern_portal' => [
                ['title' => 'প্রধান লিডিং নিউজলিস্ট (Lead Highlight Section)', 'cat' => 0, 'limit' => 3, 'style' => 'bento_grid'],
                ['title' => 'আঞ্চলিক স্পটলাইট গ্রিড (Regional Spotlight Grid)', 'cat' => $catBarishal, 'limit' => 4, 'style' => 'overlay_grid'],
                ['title' => '২ কলাম ফিচার্ড সেকশন (2 Column Featured Row)', 'cat' => $catPolitics, 'limit' => 4, 'style' => 'two_column_grid'],
                ['title' => 'ক্যারোজেল স্লাইডার (Carousel Slider Section)', 'cat' => 0, 'limit' => 6, 'style' => 'carousel_slider'],
                ['title' => '৪ কলাম সংবাদ কার্ড (4 Column Media Grid)', 'cat' => $catTech, 'limit' => 4, 'style' => 'horizontal_cards'],
                ['title' => 'ভিডিও গ্যালারি প্লেয়ার (Video Gallery Player)', 'cat' => 0, 'limit' => 4, 'style' => 'video_gallery_theater']
            ],
            'magazine_spotlight' => [
                ['title' => 'স্পটলাইট ক্যারোজেল (Spotlight Slider Header)', 'cat' => 0, 'limit' => 6, 'style' => 'carousel_slider'],
                ['title' => 'লিডিং বিগ ইমেজ সেকশন (Leading Big Image Section)', 'cat' => $catNational, 'limit' => 5, 'style' => 'lead_side_list'],
                ['title' => 'ছবি গ্যালারি অ্যালবامات (Photo Gallery Showcase)', 'cat' => 0, 'limit' => 6, 'style' => 'photo_gallery_grid'],
                ['title' => '৪ কলাম সংবাদ কার্ড (4 Column Media Grid)', 'cat' => $catEnt, 'limit' => 4, 'style' => 'horizontal_cards'],
                ['title' => 'ভিডিও বুলেটিন থিয়েটার (Video Bulletin Theater)', 'cat' => 0, 'limit' => 4, 'style' => 'video_gallery_theater']
            ],
            'compact_fast_news' => [
                ['title' => 'দ্রুত বুলেটিন সংবাদ (Fast News Feed)', 'cat' => 0, 'limit' => 8, 'style' => 'compact_list'],
                ['title' => '২ কলাম প্রধান সংবাদ (2 Column Main Grid)', 'cat' => $catNational, 'limit' => 4, 'style' => 'two_column_grid'],
                ['title' => 'সংক্ষিপ্ত সংবাদ তালিকা (Compact List Feed)', 'cat' => $catBarishal, 'limit' => 6, 'style' => 'compact_list'],
                ['title' => 'ওভারলে কার্ড গ্রিড (Overlay Card Grid)', 'cat' => $catBusiness, 'limit' => 4, 'style' => 'overlay_grid'],
                ['title' => '৪ কলাম কার্ড গ্রিড (4 Row Media Grid)', 'cat' => $catSports, 'limit' => 6, 'style' => 'horizontal_cards']
            ]
        ];

        $pItems = $presetsData[$preset] ?? $presetsData['classic_newspaper'];
        $order = 1;
        foreach ($pItems as $pi) {
            save_homepage_section([
                'title' => $pi['title'],
                'category_id' => $pi['cat'],
                'post_limit' => $pi['limit'],
                'layout_style' => $pi['style'],
                'section_order' => $order++,
                'status' => 1
            ]);
        }
        set_setting('home_layout_preset', $preset);
        set_setting('homepage_layout_preset', $preset);
        $msg = "Homepage preset '{$preset}' applied successfully! Live homepage now reflects this preset.";
    }
}

// Fetch Current Settings & Sections
$active_preset = get_setting('home_layout_preset', get_setting('homepage_layout_preset', 'classic_newspaper'));
$hero_cat = get_setting('home_hero_cat', '0');
$hero_limit = get_setting('home_hero_limit', '5');
$show_videos = get_setting('home_show_videos', '1');
$show_photos = get_setting('home_show_photos', '1');
$show_breaking = get_setting('home_show_breaking', '1');

$sections = get_homepage_sections(false);

$layout_styles = [
    'lead_side_list' => ['name' => '📰 1 Lead Article + Side Headline List', 'desc' => '1 big lead featured post on the left + 3 to 4 side headline items with thumbnails.'],
    'two_column_grid' => ['name' => '📰 2-Column News Grid', 'desc' => 'Clean 2-column layout displaying featured news cards.'],
    'bento_grid' => ['name' => '📰 3-Card Bento Grid', 'desc' => 'Modern bento grid with 1 tall lead card on left and 2 stacked cards on right.'],
    'horizontal_cards' => ['name' => '📰 4-Card Horizontal Media Grid', 'desc' => 'Row of 4 elegant cards with top featured image badges.'],
    'carousel_slider' => ['name' => '📰 Interactive News Slider / Carousel', 'desc' => 'Auto-sliding card carousel with previous/next controls.'],
    'overlay_grid' => ['name' => '📰 4-Card Title Overlay Grid', 'desc' => 'High-impact grid with article titles overlaid over dark gradient images.'],
    'compact_list' => ['name' => '📰 Compact News Bullet List', 'desc' => 'Dense headline news list with micro-thumbnails and publication times.'],
    'full_width_banner' => ['name' => '📰 Full-Width Hero Cover Card', 'desc' => 'Wide hero banner featuring top story with dark overlay typography.'],
    'video_gallery_theater' => ['name' => '🎬 Video Gallery Theatre (YouTube Player + Playlist)', 'desc' => 'Sleek dark video player theatre with interactive video playlist.'],
    'photo_gallery_grid' => ['name' => '📷 Photo Gallery Albums Grid (Lightbox Zoom)', 'desc' => 'Grid layout for photo albums with full-screen lightbox preview viewer.']
];
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h3 class="fw-bold mb-1 text-danger"><i class="bi bi-layout-text-window-reverse me-2"></i> Dynamic Homepage Section Manager</h3>
            <p class="text-muted small mb-0">Drag and drop sections to reorder, toggle show/hide visibility, customize category news sources and layout styles.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-danger btn-sm px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Section
            </button>
            <a href="../index.php" target="_blank" class="btn btn-outline-dark btn-sm"><i class="bi bi-eye me-1"></i> View Live Homepage</a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Global Layout Settings Accordion / Card -->
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-dark text-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-sliders me-2 text-warning"></i> Global Top Header & Hero Settings</span>
            <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#globalSettingsCollapse">
                <i class="bi bi-chevron-down"></i> Configure
            </button>
        </div>
        <div class="collapse show" id="globalSettingsCollapse">
            <div class="card-body p-4 bg-light">
                <form action="homepage.php" method="POST">
                    <input type="hidden" name="action" value="global_settings">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-white border rounded h-100">
                                <label class="form-label fw-bold"><i class="bi bi-lightning-fill text-warning me-1"></i> Breaking News Ticker</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="home_show_breaking" id="showBreaking" value="1" <?= $show_breaking === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="showBreaking">Enable Marquee Breaking Ticker at top</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white border rounded h-100">
                                <label class="form-label fw-bold"><i class="bi bi-star-fill text-danger me-1"></i> Main Top Hero Category</label>
                                <select name="home_hero_cat" class="form-select form-select-sm mb-2">
                                    <option value="0" <?= $hero_cat == '0' ? 'selected' : '' ?>>★ All Categories (Top Featured Posts)</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $hero_cat == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted text-nowrap">Hero Posts Count:</small>
                                    <input type="number" name="home_hero_limit" class="form-select form-select-sm" value="<?= htmlspecialchars($hero_limit) ?>" min="3" max="10">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Save Global Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- One-Click Homepage Layout Presets Card -->
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-danger text-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-magic me-2"></i> One-Click Homepage Design Presets (এক ক্লিকে ফুল হোমপেজ লেআউট পরিবর্তন)</span>
            <span class="badge bg-light text-dark fw-bold">Active: <?= htmlspecialchars($active_preset) ?></span>
        </div>
        <div class="card-body p-4 bg-white">
            <p class="text-muted small mb-3">নিচের যেকোনো একটি রেডিমেইড লেআউট নির্বাচন করে এক ক্লিকে সম্পূর্ণ হোমপেজের ডিজাইন পরিবর্তন করুন। বর্তমানে সক্রিয় লেআউটটি সবুজ রঙের <strong>ACTIVE PRESET</strong> ব্যাজ দ্বারা চিহ্নিত করা আছে:</p>
            <div class="row g-3">
                
                <!-- Preset 1: Classic Newspaper -->
                <?php $is_classic = ($active_preset === 'classic_newspaper'); ?>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100 text-center hover-shadow position-relative <?= $is_classic ? 'border-success border-3 bg-success-subtle shadow-sm' : 'bg-light' ?>">
                        <?php if ($is_classic): ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success text-white shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> বর্তমানে লাইভ সক্রিয়</span>
                        <?php endif; ?>
                        <i class="bi bi-newspaper fs-1 text-danger d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">দৈনিক ক্লাসিক সংবাদপত্র</h6>
                        <p class="small text-muted mb-3">Traditional Bangladesh newspaper style with lead stories, 2-column division news, and bento grids.</p>
                        <form action="homepage.php" method="POST">
                            <input type="hidden" name="action" value="apply_preset">
                            <input type="hidden" name="preset_name" value="classic_newspaper">
                            <?php if ($is_classic): ?>
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold disabled">
                                    <i class="bi bi-check2-all me-1"></i> বর্তমানে সক্রিয় (Active)
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold" onclick="return confirm('এটি হোমপেজের বর্তমান সেকশনগুলো পরিবর্তন করে ক্লাসিক লেআউটে সাজাবে। আপনি কি নিশ্চিত?');">
                                    <i class="bi bi-check2-circle me-1"></i> ক্লাসিক লেআউট সেট করুন
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Preset 2: Modern Portal -->
                <?php $is_portal = ($active_preset === 'modern_portal'); ?>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100 text-center hover-shadow position-relative <?= $is_portal ? 'border-success border-3 bg-success-subtle shadow-sm' : 'bg-light' ?>">
                        <?php if ($is_portal): ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success text-white shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> বর্তমানে লাইভ সক্রিয়</span>
                        <?php endif; ?>
                        <i class="bi bi-grid-3x3-gap-fill fs-1 text-primary d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">আধুনিক নিউজ পোর্টাল</h6>
                        <p class="small text-muted mb-3">Modern Bento grid boxes, overlay cards, tech & world news slider for interactive viewing.</p>
                        <form action="homepage.php" method="POST">
                            <input type="hidden" name="action" value="apply_preset">
                            <input type="hidden" name="preset_name" value="modern_portal">
                            <?php if ($is_portal): ?>
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold disabled">
                                    <i class="bi bi-check2-all me-1"></i> বর্তমানে সক্রিয় (Active)
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100 fw-bold" onclick="return confirm('এটি হোমপেজকে আধুনিক নিউজ পোর্টাল লেআউটে সাজাবে। আপনি কি নিশ্চিত?');">
                                    <i class="bi bi-check2-circle me-1"></i> আধুনিক লেআউট সেট করুন
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Preset 3: Magazine Spotlight -->
                <?php $is_mag = ($active_preset === 'magazine_spotlight'); ?>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100 text-center hover-shadow position-relative <?= $is_mag ? 'border-success border-3 bg-success-subtle shadow-sm' : 'bg-light' ?>">
                        <?php if ($is_mag): ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success text-white shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> বর্তমানে লাইভ সক্রিয়</span>
                        <?php endif; ?>
                        <i class="bi bi-file-earmark-slides-fill fs-1 text-warning d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">ম্যাগাজিন স্পটলাইট</h6>
                        <p class="small text-muted mb-3">High visual impact layout featuring full hero slider, photo gallery lightbox, and video theater.</p>
                        <form action="homepage.php" method="POST">
                            <input type="hidden" name="action" value="apply_preset">
                            <input type="hidden" name="preset_name" value="magazine_spotlight">
                            <?php if ($is_mag): ?>
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold disabled">
                                    <i class="bi bi-check2-all me-1"></i> বর্তমানে সক্রিয় (Active)
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-outline-warning text-dark btn-sm w-100 fw-bold" onclick="return confirm('এটি ম্যাগাজিন স্পটলাইট লেআউট সেট করবে। আপনি কি নিশ্চিত?');">
                                    <i class="bi bi-check2-circle me-1"></i> ম্যাগাজিন লেআউট সেট করুন
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Preset 4: Compact Fast News -->
                <?php $is_compact = ($active_preset === 'compact_fast_news'); ?>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100 text-center hover-shadow position-relative <?= $is_compact ? 'border-success border-3 bg-success-subtle shadow-sm' : 'bg-light' ?>">
                        <?php if ($is_compact): ?>
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success text-white shadow-sm"><i class="bi bi-check-circle-fill me-1"></i> বর্তমানে লাইভ সক্রিয়</span>
                        <?php endif; ?>
                        <i class="bi bi-lightning-charge-fill fs-1 text-success d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">দ্রুত বুলেটিন সংবাদ</h6>
                        <p class="small text-muted mb-3">Fast loading, dense bullet news feeds, high readability lists and quick category updates.</p>
                        <form action="homepage.php" method="POST">
                            <input type="hidden" name="action" value="apply_preset">
                            <input type="hidden" name="preset_name" value="compact_fast_news">
                            <?php if ($is_compact): ?>
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold disabled">
                                    <i class="bi bi-check2-all me-1"></i> বর্তমানে সক্রিয় (Active)
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-outline-success btn-sm w-100 fw-bold" onclick="return confirm('এটি দ্রুত বুলেটিন লেআউট সেট করবে। আপনি কি নিশ্চিত?');">
                                    <i class="bi bi-check2-circle me-1"></i> বুলেটিন লেআউট সেট করুন
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Dynamic Section Manager List with Drag and Drop -->
    <div class="card shadow-sm border">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark d-inline"><i class="bi bi-list-ordered me-2 text-danger"></i> Homepage Sections Layout (Drag or Arrows to Reorder)</h5>
                <span class="badge bg-danger rounded-pill ms-2"><?= count($sections) ?> Total Sections</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#addSectionModal"><i class="bi bi-plus-lg me-1"></i> Add Section</button>
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Tip: Drag rows or use ↑ ↓ buttons to rearrange sequence.</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="homepage-sections-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">Move</th>
                            <th style="width: 70px;" class="text-center">Order</th>
                            <th>Section Title</th>
                            <th>Source Category</th>
                            <th class="text-center">Posts / Items</th>
                            <th>Layout Template Style</th>
                            <th class="text-center">Show / Hide Status</th>
                            <th style="width: 260px;" class="text-end pe-4">Actions & Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sections)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x display-4 d-block mb-2 text-secondary"></i>
                                    No homepage sections created yet. Click <strong>"Add New Section"</strong> above to build your homepage layout.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sections as $index => $sec): ?>
                                <tr draggable="true" data-id="<?= $sec['id'] ?>" class="<?= $sec['status'] == 0 ? 'table-secondary bg-opacity-50 text-muted' : '' ?>">
                                    <td class="text-center cursor-grab text-muted user-select-none">
                                        <i class="bi bi-grip-vertical fs-5"></i>
                                    </td>
                                    <td class="text-center fw-bold text-muted section-order-num">
                                        #<?= $sec['section_order'] ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                            <?= htmlspecialchars($sec['title']) ?>
                                            <?php if ($sec['status'] == 0): ?>
                                                <span class="badge bg-secondary text-white small">Hidden (লুকানো)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($sec['layout_style'] === 'video_gallery_theater'): ?>
                                            <span class="badge bg-dark text-warning border px-2 py-1"><i class="bi bi-play-btn-fill me-1"></i> Video Database</span>
                                        <?php elseif ($sec['layout_style'] === 'photo_gallery_grid'): ?>
                                            <span class="badge bg-dark text-info border px-2 py-1"><i class="bi bi-camera-fill me-1"></i> Photo Albums</span>
                                        <?php elseif ($sec['category_id'] == 0): ?>
                                            <span class="badge bg-dark text-warning"><i class="bi bi-star-fill me-1"></i> All Categories (Top Featured)</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-6">
                                                <i class="bi bi-folder-fill me-1"></i> <?= htmlspecialchars($sec['category_name'] ?? 'Category #' . $sec['category_id']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fs-6"><?= (int)$sec['post_limit'] ?> Items</span>
                                    </td>
                                    <td>
                                        <small class="fw-semibold text-dark">
                                            <?= htmlspecialchars($layout_styles[$sec['layout_style']]['name'] ?? $sec['layout_style']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" data-id="<?= $sec['id'] ?>" class="btn btn-sm <?= $sec['status'] == 1 ? 'btn-success' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill small ajax-toggle-status" title="Click to Show or Hide this Section">
                                            <?= $sec['status'] == 1 ? '<i class="bi bi-check-circle-fill me-1"></i> Visible' : '<i class="bi bi-eye-slash-fill me-1"></i> Hidden' ?>
                                        </button>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm me-2">
                                            <form action="homepage.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="move_up">
                                                <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">
                                                <button type="submit" class="btn btn-outline-secondary" <?= $index === 0 ? 'disabled' : '' ?> title="Move Up">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                            </form>
                                            <form action="homepage.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="move_down">
                                                <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">
                                                <button type="submit" class="btn btn-outline-secondary" <?= $index === count($sections) - 1 ? 'disabled' : '' ?> title="Move Down">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $sec['id'] ?>" title="Edit Section">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        <form action="homepage.php" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই সেকশনটি মুছে ফেলতে চান? মুছে ফেললেও আপনি Add New Section থেকে আবার এটি যুক্ত করতে পারবেন।');">
                                            <input type="hidden" name="action" value="delete_section">
                                            <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Section">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Section Modal -->
                                <div class="modal fade" id="editModal<?= $sec['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form action="homepage.php" method="POST">
                                                <input type="hidden" name="action" value="edit_section">
                                                <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Section: <?= htmlspecialchars($sec['title']) ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3">
                                                        <div class="col-md-8">
                                                            <label class="form-label fw-bold">Section Title</label>
                                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($sec['title']) ?>" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Display Order</label>
                                                            <input type="number" name="section_order" class="form-control" value="<?= (int)$sec['section_order'] ?>" min="1">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Source Category</label>
                                                            <select name="category_id" class="form-select">
                                                                <option value="0" <?= $sec['category_id'] == 0 ? 'selected' : '' ?>>★ All Categories / Multimedia</option>
                                                                <?php foreach ($categories as $cat): ?>
                                                                    <option value="<?= $cat['id'] ?>" <?= $sec['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Post / Item Display Limit</label>
                                                            <select name="post_limit" class="form-select">
                                                                <?php foreach ([2, 3, 4, 5, 6, 8, 10, 12] as $lim): ?>
                                                                    <option value="<?= $lim ?>" <?= $sec['post_limit'] == $lim ? 'selected' : '' ?>><?= $lim ?> Items</option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label fw-bold">Layout Template Style</label>
                                                            <select name="layout_style" class="form-select">
                                                                <?php foreach ($layout_styles as $style_key => $style_info): ?>
                                                                    <option value="<?= $style_key ?>" <?= $sec['layout_style'] === $style_key ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($style_info['name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="form-check form-switch mt-2">
                                                                <input class="form-check-input" type="checkbox" name="status" value="1" id="editStatus<?= $sec['id'] ?>" <?= $sec['status'] == 1 ? 'checked' : '' ?>>
                                                                <label class="form-check-label fw-bold" for="editStatus<?= $sec['id'] ?>">Enable and Display on Live Homepage</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add New Section -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="homepage.php" method="POST">
                <input type="hidden" name="action" value="add_section">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Add / Restore Homepage Section</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-lightbulb-fill text-warning me-1"></i> দ্রুত যুক্ত করার জন্য নিচের রেডিমেড টেমপ্লেট নির্বাচন করতে পারেন অথবা আপনার নিজস্ব কাস্টম সেকশন তৈরি করতে পারেন।
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark"><i class="bi bi-magic text-danger me-1"></i> Quick Select Prebuilt Section Template:</label>
                        <select id="quickPrebuiltSelector" class="form-select border-danger">
                            <option value="">-- কাস্টম সেকশন তৈরি করুন (অথবা তালিকা থেকে বেছে নিন) --</option>
                            <option value='{"title":"লিডিং বিগ ইমেজ সেকশন", "cat":0, "limit":5, "style":"lead_side_list"}'>📰 লিডিং বিগ ইমেজ সেকশন (1 Lead + Side List)</option>
                            <option value='{"title":"২ কলাম নিউজ গ্রিড", "cat":0, "limit":4, "style":"two_column_grid"}'>📰 ২ কলাম নিউজ গ্রিড (2-Column Grid)</option>
                            <option value='{"title":"৪ কলাম মিডিয়া সেকশন", "cat":0, "limit":4, "style":"horizontal_cards"}'>📰 ৪ কলাম মিডিয়া সেকশন (4 Row Horizontal Cards)</option>
                            <option value='{"title":"৩ কার্ড বেনটো গ্রিড", "cat":0, "limit":3, "style":"bento_grid"}'>📰 ৩ কার্ড বেনটো গ্রিড (3-Card Bento Grid)</option>
                            <option value='{"title":"ট্রেন্ডিং ওভারলে গ্রিড", "cat":0, "limit":4, "style":"overlay_grid"}'>📰 ট্রেন্ডিং ওভারলে গ্রিড (Overlay Card Grid)</option>
                            <option value='{"title":"ভিডিও খবর থিয়েটার", "cat":0, "limit":4, "style":"video_gallery_theater"}'>🎬 ভিডিও খবর থিয়েটার (Video Gallery Player)</option>
                            <option value='{"title":"ফটো গ্যালারি সঙ্কলন", "cat":0, "limit":6, "style":"photo_gallery_grid"}'>📷 ফটো গ্যালারি সঙ্কলন (Photo Albums Grid)</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Section Display Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="addSectionTitle" class="form-control" placeholder="e.g., বিনোদন ও লাইফস্টাইল, ভিডিও খবর" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Items Count Limit</label>
                            <select name="post_limit" id="addSectionLimit" class="form-select">
                                <?php foreach ([2, 3, 4, 5, 6, 8, 10, 12] as $lim): ?>
                                    <option value="<?= $lim ?>" <?= $lim == 5 ? 'selected' : '' ?>><?= $lim ?> Items</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Source Category</label>
                            <select name="category_id" id="addSectionCat" class="form-select">
                                <option value="0">★ All Categories / Multimedia</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Layout Template Style</label>
                            <select name="layout_style" id="addSectionStyle" class="form-select">
                                <?php foreach ($layout_styles as $style_key => $style_info): ?>
                                    <option value="<?= $style_key ?>"><?= htmlspecialchars($style_info['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="addStatus" checked>
                                <label class="form-check-label fw-bold" for="addStatus">Active & Visible on Live Homepage</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-plus-lg me-1"></i> Add Section Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.querySelector('#homepage-sections-table tbody');
    
    // Quick template auto-fill in modal
    const quickSelector = document.getElementById('quickPrebuiltSelector');
    if (quickSelector) {
        quickSelector.addEventListener('change', function() {
            if (!this.value) return;
            try {
                const data = JSON.parse(this.value);
                if (data.title) document.getElementById('addSectionTitle').value = data.title;
                if (data.limit) document.getElementById('addSectionLimit').value = data.limit;
                if (data.style) document.getElementById('addSectionStyle').value = data.style;
                if (data.cat !== undefined) {
                    const catSelect = document.getElementById('addSectionCat');
                    // Find matching category option by name
                    let matched = false;
                    for (let i = 0; i < catSelect.options.length; i++) {
                        if (catSelect.options[i].text.includes(data.title.split(' ')[0])) {
                            catSelect.selectedIndex = i;
                            matched = true;
                            break;
                        }
                    }
                    if (!matched) catSelect.value = '0';
                }
            } catch (e) {
                console.error(e);
            }
        });
    }

    if (!tbody) return;

    // Improved Drag and Drop implementation
    let dragRow = null;

    tbody.querySelectorAll('tr[draggable="true"]').forEach(row => {
        row.addEventListener('dragstart', function(e) {
            dragRow = this;
            this.classList.add('table-warning', 'shadow-sm');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
        });

        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const targetRow = e.target.closest('tr');
            if (targetRow && targetRow !== dragRow && targetRow.parentElement === tbody) {
                const rect = targetRow.getBoundingClientRect();
                const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                tbody.insertBefore(dragRow, next ? targetRow.nextSibling : targetRow);
            }
        });

        row.addEventListener('dragend', function(e) {
            this.classList.remove('table-warning', 'shadow-sm');
            dragRow = null;
            saveSectionOrders();
        });
    });

    function saveSectionOrders() {
        const rows = tbody.querySelectorAll('tr[data-id]');
        const orderMap = {};
        rows.forEach((r, idx) => {
            const secId = r.getAttribute('data-id');
            const orderNum = idx + 1;
            orderMap[secId] = orderNum;
            const numCell = r.querySelector('.section-order-num');
            if (numCell) numCell.textContent = '#' + orderNum;
        });

        fetch('../api.php?action=reorder_sections', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderMap)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Sections reordered successfully!', 'success');
            }
        })
        .catch(err => console.error(err));
    }

    // Status toggle switch AJAX
    document.querySelectorAll('.ajax-toggle-status').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const secId = this.getAttribute('data-id');
            const formData = new FormData();
            formData.append('section_id', secId);

            fetch('../api.php?action=toggle_section_status', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = this.closest('tr');
                    if (data.new_status == 1) {
                        this.className = 'btn btn-sm btn-success py-1 px-3 rounded-pill small ajax-toggle-status';
                        this.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Visible';
                        row.classList.remove('table-secondary', 'bg-opacity-50', 'text-muted');
                    } else {
                        this.className = 'btn btn-sm btn-outline-secondary py-1 px-3 rounded-pill small ajax-toggle-status';
                        this.innerHTML = '<i class="bi bi-eye-slash-fill me-1"></i> Hidden';
                        row.classList.add('table-secondary', 'bg-opacity-50', 'text-muted');
                    }
                    showToast('Section visibility updated!', 'info');
                }
            });
        });
    });

    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `position-fixed bottom-0 end-0 p-3 z-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `<div class="toast show bg-${type === 'success' ? 'dark' : 'primary'} text-white border-0 shadow-lg"><div class="toast-body d-flex align-items-center"><i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i> ${msg}</div></div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
