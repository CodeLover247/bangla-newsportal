<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Fetch Global Homepage Settings
$homepage_preset = get_setting('home_layout_preset', get_setting('homepage_layout_preset', 'classic_newspaper'));
$hero_cat_id = (int)get_setting('home_hero_cat', '0');
$hero_limit = (int)get_setting('home_hero_limit', '5');
$show_videos = get_setting('home_show_videos', '1');
$show_photos = get_setting('home_show_photos', '1');

// Fetch Hero Section Lead Stories
$hero_opts = ['limit' => $hero_limit, 'order_by' => 'p.publish_date DESC, p.id DESC'];
if ($hero_cat_id > 0) {
    $hero_opts['category_id'] = $hero_cat_id;
} else {
    $hero_opts['is_featured'] = 1;
}
$featured_posts = get_posts($hero_opts);
if (empty($featured_posts)) {
    $featured_posts = get_posts(['limit' => $hero_limit, 'order_by' => 'p.publish_date DESC, p.id DESC']);
}
$lead_post = isset($featured_posts[0]) ? $featured_posts[0] : null;
$side_featured = array_slice($featured_posts, 1, 4);

// Fetch Latest and Popular Posts for Hero Sidebar Tabs
$latest_hero_posts = get_posts(['limit' => 6, 'order_by' => 'p.publish_date DESC, p.id DESC']);
$popular_hero_posts = get_posts(['order_by' => 'p.views DESC', 'limit' => 6]);

// Fetch All Dynamic Homepage Sections
$active_sections = get_homepage_sections(true);

// Check if video/photo gallery sections are already present dynamically
$has_video_section = false;
$has_photo_section = false;
foreach ($active_sections as $sec) {
    if (($sec['layout_style'] ?? '') === 'video_gallery_theater') {
        $has_video_section = true;
    }
    if (($sec['layout_style'] ?? '') === 'photo_gallery_grid') {
        $has_photo_section = true;
    }
}

// Fetch Multimedia Content
$videos = ($show_videos === '1') ? get_videos(2) : [];
$photos = ($show_photos === '1') ? get_homepage_photos(4) : [];

$fallback_img = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80';
?>

<div class="container my-4">
    <!-- Homepage Top Leaderboard Banner Ad -->
    <?= render_ad('homepage_top', 'mb-4') ?>

    <!-- Main Top Hero Section based on selected Homepage Preset -->
    <?php if ($lead_post): ?>
        <?php if ($homepage_preset === 'magazine_spotlight' || $homepage_preset === 'magazine'): ?>
        <!-- PRESET 3: Magazine Spotlight Hero with Interactive Carousel & Tabbed Feed -->
        <div class="row g-3 mb-5">
            <div class="col-lg-7">
                <!-- Interactive News Carousel Slider -->
                <div id="heroMagazineCarousel" class="carousel slide carousel-fade h-100 shadow-sm rounded overflow-hidden position-relative" data-bs-ride="carousel" data-bs-interval="4000">
                    <div class="carousel-indicators mb-2">
                        <?php foreach (array_slice($featured_posts, 0, 5) as $idx => $fp): ?>
                            <button type="button" data-bs-target="#heroMagazineCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $idx + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner h-100">
                        <?php foreach (array_slice($featured_posts, 0, 5) as $idx => $fp): ?>
                            <div class="carousel-item h-100 <?= $idx === 0 ? 'active' : '' ?>" style="min-height: 420px;">
                                <img src="<?= !empty($fp['featured_image']) ? htmlspecialchars($fp['featured_image']) : $fallback_img ?>" alt="<?= htmlspecialchars($fp['title']) ?>" class="w-100 h-100 object-fit-cover">
                                <div class="hero-overlay p-4 p-md-5 d-flex flex-column justify-content-end bg-gradient-dark">
                                    <span class="badge bg-danger mb-2 text-uppercase px-3 py-2 w-auto me-auto fs-6"><?= htmlspecialchars($fp['category_name'] ?? 'সর্বশেষ') ?></span>
                                    <h2 class="fw-bold mb-2 fs-2 text-white"><a href="article.php?slug=<?= $fp['slug'] ?>" class="text-white text-decoration-none hover-underline"><?= htmlspecialchars($fp['title']) ?></a></h2>
                                    <div class="small text-white-50"><i class="bi bi-clock me-1 text-danger"></i><?= time_ago($fp['publish_date']) ?> &bull; <?= htmlspecialchars(get_post_display_author($fp)['name']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#heroMagazineCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-dark bg-opacity-60 p-3 rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#heroMagazineCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-dark bg-opacity-60 p-3 rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-5">
                <!-- Hero Tabbed Widget (সর্বশেষ | জনপ্রিয়) -->
                <div class="card h-100 border shadow-sm hero-tab-widget">
                    <div class="card-header bg-dark text-white p-0">
                        <ul class="nav nav-tabs nav-fill border-0" id="heroTabMag" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold py-2.5 px-3 border-0 text-white rounded-0" id="latest-mag-tab" data-bs-toggle="tab" data-bs-target="#latest-mag-pane" type="button" role="tab" aria-selected="true">
                                    <i class="bi bi-clock-history text-danger me-1"></i> <?= __('সর্বশেষ খবর', 'Latest') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold py-2.5 px-3 border-0 text-white-50 rounded-0" id="popular-mag-tab" data-bs-toggle="tab" data-bs-target="#popular-mag-pane" type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-fire text-warning me-1"></i> <?= __('জনপ্রিয় খবর', 'Popular') ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-3 tab-content overflow-hidden" id="heroTabMagContent">
                        <div class="tab-pane fade show active" id="latest-mag-pane" role="tabpanel">
                            <?php foreach ($latest_hero_posts as $lp): ?>
                                <div class="media-news-item d-flex gap-3 mb-3 pb-2 border-bottom last-no-border align-items-center">
                                    <img src="<?= !empty($lp['featured_image']) ? htmlspecialchars($lp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 80px; height: 58px; flex-shrink: 0;" alt="">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fs-6 lh-sm"><a href="article.php?slug=<?= $lp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($lp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1 text-danger"></i><?= time_ago($lp['publish_date']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tab-pane fade" id="popular-mag-pane" role="tabpanel">
                            <?php foreach ($popular_hero_posts as $pop_idx => $pp): ?>
                                <div class="media-news-item d-flex gap-2.5 mb-3 pb-2 border-bottom last-no-border align-items-center">
                                    <span class="badge bg-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; flex-shrink: 0;"><?= $pop_idx + 1 ?></span>
                                    <img src="<?= !empty($pp['featured_image']) ? htmlspecialchars($pp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 75px; height: 54px; flex-shrink: 0;" alt="">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fs-6 lh-sm"><a href="article.php?slug=<?= $pp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($pp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-eye me-1 text-primary"></i><?= number_format($pp['views'] ?? 0) ?> views</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($homepage_preset === 'compact_fast_news' || $homepage_preset === 'minimalist'): ?>
        <!-- PRESET 4: Clean Compact Fast News Hero -->
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4 border-top border-4 border-danger h-100">
                    <div class="row align-items-center g-4">
                        <div class="col-md-7">
                            <span class="badge bg-danger text-white text-uppercase px-3 py-2 mb-3 fw-bold fs-6"><?= htmlspecialchars($lead_post['category_name'] ?? 'বিশেষ সংবাদ') ?></span>
                            <h1 class="display-6 fw-extrabold text-dark mb-3 lh-tight"><a href="article.php?slug=<?= $lead_post['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($lead_post['title']) ?></a></h1>
                            <p class="text-secondary fs-5 mb-4"><?= htmlspecialchars($lead_post['short_description']) ?></p>
                            <div class="d-flex align-items-center gap-3 small text-muted">
                                <span><i class="bi bi-person-circle me-1 text-danger"></i> <?= htmlspecialchars(get_post_display_author($lead_post)['name']) ?></span>
                                <span>&bull;</span>
                                <span><i class="bi bi-clock me-1"></i> <?= time_ago($lead_post['publish_date']) ?></span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <img src="<?= !empty($lead_post['featured_image']) ? htmlspecialchars($lead_post['featured_image']) : $fallback_img ?>" alt="" class="img-fluid rounded-3 shadow">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- Hero Tabbed Widget (সর্বশেষ | জনপ্রিয়) -->
                <div class="card h-100 border shadow-sm hero-tab-widget">
                    <div class="card-header bg-dark text-white p-0">
                        <ul class="nav nav-tabs nav-fill border-0" id="heroTabMin" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold py-2.5 px-3 border-0 text-white rounded-0" id="latest-min-tab" data-bs-toggle="tab" data-bs-target="#latest-min-pane" type="button" role="tab" aria-selected="true">
                                    <i class="bi bi-clock-history text-danger me-1"></i> <?= __('সর্বশেষ', 'Latest') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold py-2.5 px-3 border-0 text-white-50 rounded-0" id="popular-min-tab" data-bs-toggle="tab" data-bs-target="#popular-min-pane" type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-fire text-warning me-1"></i> <?= __('জনপ্রিয়', 'Popular') ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-3 tab-content overflow-hidden" id="heroTabMinContent">
                        <div class="tab-pane fade show active" id="latest-min-pane" role="tabpanel">
                            <?php foreach ($latest_hero_posts as $lp): ?>
                                <div class="media-news-item d-flex gap-3 mb-3 pb-2 border-bottom last-no-border align-items-center">
                                    <img src="<?= !empty($lp['featured_image']) ? htmlspecialchars($lp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 75px; height: 54px; flex-shrink: 0;" alt="">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fs-6 lh-sm"><a href="article.php?slug=<?= $lp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($lp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1 text-danger"></i><?= time_ago($lp['publish_date']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tab-pane fade" id="popular-min-pane" role="tabpanel">
                            <?php foreach ($popular_hero_posts as $pop_idx => $pp): ?>
                                <div class="media-news-item d-flex gap-2.5 mb-3 pb-2 border-bottom last-no-border align-items-center">
                                    <span class="badge bg-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; flex-shrink: 0;"><?= $pop_idx + 1 ?></span>
                                    <img src="<?= !empty($pp['featured_image']) ? htmlspecialchars($pp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 70px; height: 50px; flex-shrink: 0;" alt="">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fs-6 lh-sm"><a href="article.php?slug=<?= $pp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($pp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-eye me-1 text-primary"></i><?= number_format($pp['views'] ?? 0) ?> views</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($homepage_preset === 'modern_portal' || $homepage_preset === 'portal'): ?>
        <!-- PRESET 2: High-Density Modern Portal Hero (Hero Carousel + Side Tabs) -->
        <div class="p-3 bg-dark text-white rounded mb-5 shadow">
            <div class="row g-3">
                <div class="col-lg-7">
                    <!-- Interactive Slider for Portal Preset -->
                    <div id="heroPortalCarousel" class="carousel slide carousel-fade rounded overflow-hidden" data-bs-ride="carousel" data-bs-interval="4000" style="height: 380px;">
                        <div class="carousel-indicators mb-2">
                            <?php foreach (array_slice($featured_posts, 0, 5) as $idx => $fp): ?>
                                <button type="button" data-bs-target="#heroPortalCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner h-100">
                            <?php foreach (array_slice($featured_posts, 0, 5) as $idx => $fp): ?>
                                <div class="carousel-item h-100 <?= $idx === 0 ? 'active' : '' ?>">
                                    <img src="<?= !empty($fp['featured_image']) ? htmlspecialchars($fp['featured_image']) : $fallback_img ?>" class="w-100 h-100 object-fit-cover" alt="">
                                    <div class="position-absolute bottom-0 start-0 end-0 p-4 bg-gradient-dark">
                                        <span class="badge bg-danger text-uppercase mb-2"><?= htmlspecialchars($fp['category_name'] ?? 'প্রচ্ছদ') ?></span>
                                        <h2 class="fw-bold fs-3 mb-1 text-white"><a href="article.php?slug=<?= $fp['slug'] ?>" class="text-white text-decoration-none hover-underline"><?= htmlspecialchars($fp['title']) ?></a></h2>
                                        <small class="text-white-50"><i class="bi bi-clock me-1"></i><?= time_ago($fp['publish_date']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <!-- Portal Tabbed Feed -->
                    <div class="bg-slate-900 p-3 rounded h-100">
                        <ul class="nav nav-pills nav-fill mb-3 bg-dark rounded p-1" id="portalHeroTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-2 btn-sm fw-bold text-white" id="portal-latest-tab" data-bs-toggle="pill" data-bs-target="#portal-latest-pane" type="button" role="tab"><i class="bi bi-clock me-1 text-danger"></i><?= __('সর্বশেষ', 'Latest') ?></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-2 btn-sm fw-bold text-white-50" id="portal-pop-tab" data-bs-toggle="pill" data-bs-target="#portal-pop-pane" type="button" role="tab"><i class="bi bi-fire me-1 text-warning"></i><?= __('জনপ্রিয়', 'Popular') ?></button>
                            </li>
                        </ul>
                        <div class="tab-content" id="portalHeroTabContent">
                            <div class="tab-pane fade show active" id="portal-latest-pane" role="tabpanel">
                                <?php foreach ($latest_hero_posts as $lp): ?>
                                    <div class="mb-2 pb-2 border-bottom border-secondary last-no-border d-flex gap-2 align-items-center">
                                        <img src="<?= !empty($lp['featured_image']) ? htmlspecialchars($lp['featured_image']) : $fallback_img ?>" class="rounded object-fit-cover" style="width: 55px; height: 42px; flex-shrink: 0;" alt="">
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 fs-6 lh-sm"><a href="article.php?slug=<?= $lp['slug'] ?>" class="text-light text-decoration-none hover-red small"><?= htmlspecialchars($lp['title']) ?></a></h6>
                                            <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i><?= time_ago($lp['publish_date']) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="tab-pane fade" id="portal-pop-pane" role="tabpanel">
                                <?php foreach ($popular_hero_posts as $pp): ?>
                                    <div class="mb-2 pb-2 border-bottom border-secondary last-no-border d-flex gap-2 align-items-center">
                                        <img src="<?= !empty($pp['featured_image']) ? htmlspecialchars($pp['featured_image']) : $fallback_img ?>" class="rounded object-fit-cover" style="width: 55px; height: 42px; flex-shrink: 0;" alt="">
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 fs-6 lh-sm"><a href="article.php?slug=<?= $pp['slug'] ?>" class="text-light text-decoration-none hover-red small"><?= htmlspecialchars($pp['title']) ?></a></h6>
                                            <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-eye me-1 text-primary"></i><?= number_format($pp['views'] ?? 0) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- PRESET 1: Standard Classic Newspaper Hero with Image Carousel & Tabbed Sidebar -->
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <!-- Top 5 Lead Stories Interactive Image Carousel Slider -->
                <div id="heroStandardCarousel" class="carousel slide carousel-fade h-100 shadow-sm rounded overflow-hidden position-relative" data-bs-ride="carousel" data-bs-interval="4500">
                    <div class="carousel-indicators mb-2">
                        <?php foreach (array_slice($featured_posts, 0, 5) as $idx => $fp): ?>
                            <button type="button" data-bs-target="#heroStandardCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner h-100">
                        <?php foreach (array_slice($featured_posts, 0, 5) as $idx => $fp): ?>
                            <div class="carousel-item h-100 <?= $idx === 0 ? 'active' : '' ?>" style="min-height: 400px;">
                                <img src="<?= !empty($fp['featured_image']) ? htmlspecialchars($fp['featured_image']) : $fallback_img ?>" alt="<?= htmlspecialchars($fp['title']) ?>" class="w-100 h-100 object-fit-cover" loading="eager">
                                <div class="hero-overlay p-4 p-md-5 d-flex flex-column justify-content-end bg-gradient-dark">
                                    <span class="badge bg-danger mb-2 text-uppercase fs-6 px-3 py-2 w-auto me-auto"><?= htmlspecialchars($fp['category_name'] ?? 'সর্বশেষ') ?></span>
                                    <h1 class="fw-bold mb-2 fs-2 text-white"><a href="article.php?slug=<?= $fp['slug'] ?>" class="text-white text-decoration-none hover-underline"><?= htmlspecialchars($fp['title']) ?></a></h1>
                                    <p class="d-none d-md-block opacity-90 mb-2 text-light" style="font-size: 0.95rem; line-height: 1.5;"><?= htmlspecialchars($fp['short_description']) ?></p>
                                    <div class="small opacity-75 text-white-50"><i class="bi bi-clock me-1"></i><?= time_ago($fp['publish_date']) ?> &bull; <?= htmlspecialchars(get_post_display_author($fp)['name']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#heroStandardCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-dark bg-opacity-60 p-3 rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#heroStandardCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-dark bg-opacity-60 p-3 rounded-circle" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- Hero Tabbed Sidebar Widget (সর্বশেষ | জনপ্রিয়) -->
                <div class="card h-100 border shadow-sm hero-tab-widget">
                    <div class="card-header bg-dark text-white p-0">
                        <ul class="nav nav-tabs nav-fill border-0" id="heroTabStd" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold py-2.5 px-3 border-0 text-white rounded-0" id="latest-std-tab" data-bs-toggle="tab" data-bs-target="#latest-std-pane" type="button" role="tab" aria-selected="true">
                                    <i class="bi bi-clock-history text-danger me-1"></i> <?= __('সর্বশেষ খবর', 'Latest') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold py-2.5 px-3 border-0 text-white-50 rounded-0" id="popular-std-tab" data-bs-toggle="tab" data-bs-target="#popular-std-pane" type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-fire text-warning me-1"></i> <?= __('জনপ্রিয় খবর', 'Popular') ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-3 tab-content overflow-hidden" id="heroTabStdContent">
                        <div class="tab-pane fade show active" id="latest-std-pane" role="tabpanel">
                            <?php foreach ($latest_hero_posts as $lp): ?>
                                <div class="media-news-item d-flex gap-3 mb-3 pb-2 border-bottom last-no-border align-items-center">
                                    <img src="<?= !empty($lp['featured_image']) ? htmlspecialchars($lp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 80px; height: 58px; flex-shrink: 0;" alt="" loading="lazy">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fs-6 lh-sm"><a href="article.php?slug=<?= $lp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($lp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1 text-danger"></i><?= time_ago($lp['publish_date']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tab-pane fade" id="popular-std-pane" role="tabpanel">
                            <?php foreach ($popular_hero_posts as $pop_idx => $pp): ?>
                                <div class="media-news-item d-flex gap-2.5 mb-3 pb-2 border-bottom last-no-border align-items-center">
                                    <span class="badge bg-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; flex-shrink: 0;"><?= $pop_idx + 1 ?></span>
                                    <img src="<?= !empty($pp['featured_image']) ? htmlspecialchars($pp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 75px; height: 54px; flex-shrink: 0;" alt="" loading="lazy">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fs-6 lh-sm"><a href="article.php?slug=<?= $pp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($pp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-eye me-1 text-primary"></i><?= number_format($pp['views'] ?? 0) ?> views</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Main Content Layout with 8-Col News Stream + 4-Col Sidebar -->
    <div class="row g-4">
        <!-- Left Column: Dynamic Sections Stream -->
        <div class="col-lg-8">
            <?php 
            $sec_count = 0;
            foreach ($active_sections as $sec): 
                $sec_count++;
                $cat_id = (int)$sec['category_id'];
                $limit = (int)$sec['post_limit'];
                $style = $sec['layout_style'];

                $view_url = ($cat_id > 0 && !empty($sec['category_slug'])) ? "category.php?slug={$sec['category_slug']}" : "category.php";

                // Handle Multimedia Dynamic Layout Styles
                if ($style === 'video_gallery_theater') {
                    $sec_videos = get_videos($limit);
                    if (empty($sec_videos)) continue;
                    $first_vid = $sec_videos[0];
                    $embed_url = get_youtube_embed_url($first_vid['video_url'] ?? '');
            ?>
                <div class="homepage-section mb-5" id="section-<?= $sec['id'] ?>">
                    <div class="p-4 bg-dark text-white rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
                            <h3 class="text-white mb-0 fs-4 fw-bold"><i class="bi bi-play-btn-fill text-danger me-2"></i> <?= htmlspecialchars($sec['title']) ?></h3>
                            <a href="video.php" class="text-warning small text-decoration-none fw-bold">সকল ভিডিও দেখুন <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                        <div class="row g-3 align-items-stretch">
                            <div class="col-lg-8">
                                <div class="ratio ratio-16x9 rounded overflow-hidden border border-secondary shadow">
                                    <iframe id="mainTheaterIframe_<?= $sec['id'] ?>" src="<?= htmlspecialchars($embed_url) ?>" title="<?= htmlspecialchars($first_vid['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                </div>
                                <h5 class="text-white mt-2 mb-0 fw-bold fs-5" id="mainTheaterTitle_<?= $sec['id'] ?>"><?= htmlspecialchars($first_vid['title']) ?></h5>
                            </div>
                            <div class="col-lg-4 d-flex flex-column">
                                <div class="text-secondary small fw-bold text-uppercase mb-2"><i class="bi bi-collection-play me-1"></i> ভিডিও প্লেলিস্ট (Playlist)</div>
                                <div class="video-playlist overflow-auto pe-1 flex-grow-1" style="max-height: 380px;">
                                    <?php foreach ($sec_videos as $v_idx => $v): 
                                        $v_thumb = get_youtube_thumbnail($v['video_url'] ?? '', $v['thumbnail'] ?? '');
                                        $v_embed = get_youtube_embed_url($v['video_url'] ?? '');
                                    ?>
                                        <div class="playlist-item d-flex gap-2 p-2 rounded mb-2 border border-secondary bg-black bg-opacity-50 cursor-pointer hover-border-danger transition <?= $v_idx === 0 ? 'border-danger' : '' ?>"
                                             onclick="switchTheaterVideo('section-<?= $sec['id'] ?>', '<?= htmlspecialchars($v_embed) ?>', '<?= htmlspecialchars(addslashes($v['title'])) ?>', this)">
                                            <div class="position-relative" style="width: 100px; height: 60px; flex-shrink: 0;">
                                                <img src="<?= htmlspecialchars($v_thumb) ?>" class="w-100 h-100 object-fit-cover rounded" alt="">
                                                <span class="position-absolute top-50 start-50 translate-middle badge bg-danger rounded-circle p-1"><i class="bi bi-play-fill fs-6"></i></span>
                                            </div>
                                            <div class="overflow-hidden">
                                                <h6 class="text-white small fw-bold mb-1 lh-sm text-truncate-2"><?= htmlspecialchars($v['title']) ?></h6>
                                                <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i><?= time_ago($v['created_at']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php 
                    continue;
                } elseif ($style === 'photo_gallery_grid') {
                    $sec_albums = get_gallery_albums_with_photos($limit);
                    if (empty($sec_albums)) continue;
            ?>
                <div class="homepage-section mb-5" id="section-<?= $sec['id'] ?>">
                    <div class="p-4 bg-light text-dark rounded border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h3 class="text-dark mb-0 fs-4 fw-bold"><i class="bi bi-images text-danger me-2"></i> <?= htmlspecialchars($sec['title']) ?></h3>
                            <a href="gallery.php" class="text-danger small text-decoration-none fw-bold">সকল ফটো অ্যালবাম &rarr;</a>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($sec_albums as $alb): 
                                $cover_img = !empty($alb['cover_image']) ? $alb['cover_image'] : ($alb['photos'][0]['photo_path'] ?? $fallback_img);
                                $photo_count = count($alb['photos']);
                            ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm overflow-hidden gallery-album-card group">
                                        <div class="position-relative overflow-hidden" style="aspect-ratio: 4/3;">
                                            <img src="<?= htmlspecialchars($cover_img) ?>" class="w-100 h-100 object-fit-cover transition transform-scale" alt="" loading="lazy">
                                            <span class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white"><i class="bi bi-camera me-1"></i><?= $photo_count ?> Photos</span>
                                            <div class="position-absolute inset-0 bg-dark bg-opacity-25 opacity-0 group-hover-opacity-100 transition d-flex align-items-center justify-content-center">
                                                <a href="gallery.php?album=<?= $alb['id'] ?>" class="btn btn-sm btn-light fw-bold shadow"><i class="bi bi-eye-fill me-1"></i> অ্যালবাম দেখুন</a>
                                            </div>
                                        </div>
                                        <div class="card-body p-2 bg-white">
                                            <h6 class="fw-bold mb-0 small text-truncate"><a href="gallery.php?album=<?= $alb['id'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($alb['title']) ?></a></h6>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            <?php 
                    continue;
                }

                // Standard News Category Fetching
                $fetch_opts = ['limit' => $limit, 'order_by' => 'p.publish_date DESC, p.id DESC'];
                if ($cat_id > 0) {
                    $fetch_opts['category_id'] = $cat_id;
                }

                $sec_posts = get_posts($fetch_opts);
                if (empty($sec_posts)) continue;
            ?>

            <div class="homepage-section mb-5" id="section-<?= $sec['id'] ?>">

                <!-- Section Header Bar -->
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h3 class="section-title mb-0 position-relative fw-bold fs-4">
                        <span class="border-danger border-3 border-bottom pb-2 d-inline-block text-dark">
                            <?= htmlspecialchars($sec['title']) ?>
                        </span>
                    </h3>
                    <a href="<?= $view_url ?>" class="text-danger fw-bold small text-decoration-none hover-underline">
                        আরও পড়ুন <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <!-- Section Layout Switcher -->
                <?php if ($style === 'lead_side_list'): ?>
                    <!-- Style 1: 1 Big Lead + Side List -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <?php $lead = $sec_posts[0]; ?>
                            <div class="news-card rounded border p-0 shadow-sm h-100 overflow-hidden">
                                <div class="news-card-img-wrapper position-relative overflow-hidden" style="aspect-ratio: 16/10;">
                                    <span class="category-badge position-absolute top-0 start-0 m-2 bg-danger text-white px-2 py-1 rounded small fw-bold z-1"><?= htmlspecialchars($lead['category_name']) ?></span>
                                    <img src="<?= !empty($lead['featured_image']) ? htmlspecialchars($lead['featured_image']) : $fallback_img ?>" alt="" class="w-100 h-100 object-fit-cover" loading="lazy">
                                </div>
                                <div class="p-3">
                                    <h5 class="news-title mb-2 fw-bold"><a href="article.php?slug=<?= $lead['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($lead['title']) ?></a></h5>
                                    <p class="text-muted small mb-2 text-truncate-2"><?= htmlspecialchars($lead['short_description']) ?></p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($lead['publish_date']) ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php foreach (array_slice($sec_posts, 1) as $sp): ?>
                                <div class="media-news-item d-flex gap-3 mb-3 pb-2 border-bottom last-no-border">
                                    <img src="<?= !empty($sp['featured_image']) ? htmlspecialchars($sp['featured_image']) : $fallback_img ?>" class="media-news-img rounded object-fit-cover" style="width: 80px; height: 60px; flex-shrink: 0;" alt="" loading="lazy">
                                    <div>
                                        <h6 class="mb-1 fs-6"><a href="article.php?slug=<?= $sp['slug'] ?>" class="text-dark text-decoration-none fw-semibold hover-red"><?= htmlspecialchars($sp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($sp['publish_date']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($style === 'two_column_grid'): ?>
                    <!-- Style 2: 2-Column Grid Cards -->
                    <div class="row g-3">
                        <?php foreach ($sec_posts as $p): ?>
                            <div class="col-md-6">
                                <div class="news-card rounded border p-0 shadow-sm h-100 overflow-hidden d-flex flex-column">
                                    <div class="position-relative overflow-hidden" style="aspect-ratio: 16/9;">
                                        <span class="category-badge position-absolute top-0 start-0 m-2 bg-dark text-white px-2 py-1 rounded small fw-bold z-1"><?= htmlspecialchars($p['category_name']) ?></span>
                                        <img src="<?= !empty($p['featured_image']) ? htmlspecialchars($p['featured_image']) : $fallback_img ?>" class="w-100 h-100 object-fit-cover" alt="" loading="lazy">
                                    </div>
                                    <div class="p-3 d-flex flex-column justify-content-between flex-grow-1">
                                        <h6 class="fw-bold mb-2 fs-6"><a href="article.php?slug=<?= $p['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($p['title']) ?></a></h6>
                                        <small class="text-muted mt-auto"><i class="bi bi-clock me-1"></i><?= time_ago($p['publish_date']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($style === 'bento_grid'): ?>
                    <!-- Style 3: 3-Card Bento Grid -->
                    <div class="row g-3">
                        <?php $b_lead = $sec_posts[0]; ?>
                        <div class="col-md-7">
                            <div class="news-card rounded border p-0 shadow-sm h-100 overflow-hidden position-relative">
                                <img src="<?= !empty($b_lead['featured_image']) ? htmlspecialchars($b_lead['featured_image']) : $fallback_img ?>" class="w-100 h-100 object-fit-cover" style="min-height: 240px;" alt="" loading="lazy">
                                <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-dark bg-opacity-75 text-white">
                                    <span class="badge bg-danger mb-1"><?= htmlspecialchars($b_lead['category_name']) ?></span>
                                    <h5 class="fw-bold mb-1"><a href="article.php?slug=<?= $b_lead['slug'] ?>" class="text-white text-decoration-none"><?= htmlspecialchars($b_lead['title']) ?></a></h5>
                                    <small class="text-light opacity-75"><i class="bi bi-clock me-1"></i><?= time_ago($b_lead['publish_date']) ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 d-flex flex-column gap-3">
                            <?php foreach (array_slice($sec_posts, 1, 2) as $bp): ?>
                                <div class="card border shadow-sm flex-grow-1 overflow-hidden">
                                    <div class="p-3">
                                        <span class="badge bg-secondary mb-1"><?= htmlspecialchars($bp['category_name']) ?></span>
                                        <h6 class="fw-bold mb-1"><a href="article.php?slug=<?= $bp['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($bp['title']) ?></a></h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($bp['publish_date']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($style === 'horizontal_cards'): ?>
                    <!-- Style 4: 4-Column Horizontal Cards Grid -->
                    <div class="row g-3">
                        <?php foreach ($sec_posts as $hp): ?>
                            <div class="col-6 col-md-3">
                                <div class="news-card rounded border p-0 shadow-sm h-100 overflow-hidden d-flex flex-column">
                                    <img src="<?= !empty($hp['featured_image']) ? htmlspecialchars($hp['featured_image']) : $fallback_img ?>" class="w-100 object-fit-cover" style="height: 120px;" alt="" loading="lazy">
                                    <div class="p-2 d-flex flex-column justify-content-between flex-grow-1">
                                        <h6 class="fw-bold mb-1 small lh-sm"><a href="article.php?slug=<?= $hp['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($hp['title']) ?></a></h6>
                                        <small class="text-muted mt-auto" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= time_ago($hp['publish_date']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($style === 'carousel_slider'): ?>
                    <!-- Style 5: Interactive Carousel Slider -->
                    <div id="carouselSec<?= $sec['id'] ?>" class="carousel slide shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($sec_posts as $c_idx => $cp): ?>
                                <div class="carousel-item <?= $c_idx === 0 ? 'active' : '' ?>">
                                    <div class="position-relative" style="height: 320px;">
                                        <img src="<?= !empty($cp['featured_image']) ? htmlspecialchars($cp['featured_image']) : $fallback_img ?>" class="d-block w-100 h-100 object-fit-cover" alt="">
                                        <div class="carousel-caption d-block p-3 text-start bg-dark bg-opacity-75 rounded start-0 end-0 bottom-0 m-3">
                                            <span class="badge bg-warning text-dark mb-1"><?= htmlspecialchars($cp['category_name']) ?></span>
                                            <h5 class="fw-bold text-white mb-1"><a href="article.php?slug=<?= $cp['slug'] ?>" class="text-white text-decoration-none"><?= htmlspecialchars($cp['title']) ?></a></h5>
                                            <small class="text-white-50"><i class="bi bi-clock me-1"></i><?= time_ago($cp['publish_date']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselSec<?= $sec['id'] ?>" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselSec<?= $sec['id'] ?>" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    </div>

                <?php elseif ($style === 'overlay_grid'): ?>
                    <!-- Style 6: 4-Card Title Overlay Grid -->
                    <div class="row g-3">
                        <?php foreach ($sec_posts as $op): ?>
                            <div class="col-6 col-md-3">
                                <div class="rounded shadow-sm overflow-hidden position-relative" style="height: 180px;">
                                    <img src="<?= !empty($op['featured_image']) ? htmlspecialchars($op['featured_image']) : $fallback_img ?>" class="w-100 h-100 object-fit-cover" alt="" loading="lazy">
                                    <div class="position-absolute top-0 start-0 end-0 bottom-0 bg-dark bg-opacity-50 p-2 d-flex flex-column justify-content-between">
                                        <span class="badge bg-danger align-self-start small"><?= htmlspecialchars($op['category_name']) ?></span>
                                        <h6 class="text-white fw-bold mb-0 small"><a href="article.php?slug=<?= $op['slug'] ?>" class="text-white text-decoration-none"><?= htmlspecialchars($op['title']) ?></a></h6>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($style === 'compact_list'): ?>
                    <!-- Style 7: Compact News List -->
                    <div class="card border shadow-sm p-2">
                        <div class="row row-cols-1 row-cols-md-2 g-2">
                            <?php foreach ($sec_posts as $cmp): ?>
                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded hover-bg-light">
                                        <img src="<?= !empty($cmp['featured_image']) ? htmlspecialchars($cmp['featured_image']) : $fallback_img ?>" class="rounded object-fit-cover" style="width: 55px; height: 45px; flex-shrink: 0;" alt="" loading="lazy">
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 text-truncate small fw-bold"><a href="article.php?slug=<?= $cmp['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($cmp['title']) ?></a></h6>
                                            <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i><?= time_ago($cmp['publish_date']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Default Fallback Grid -->
                    <div class="row g-3">
                        <?php foreach ($sec_posts as $dp): ?>
                            <div class="col-md-6">
                                <div class="card border shadow-sm p-3">
                                    <h6 class="fw-bold mb-1"><a href="article.php?slug=<?= $dp['slug'] ?>" class="text-dark text-decoration-none hover-red"><?= htmlspecialchars($dp['title']) ?></a></h6>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= time_ago($dp['publish_date']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- In-Between Middle Ad Banner every 2 sections -->
            <?php if ($sec_count % 2 === 0): ?>
                <?= render_ad('homepage_middle', 'mb-5') ?>
            <?php endif; ?>

            <?php endforeach; ?>

            <!-- Multimedia Block: Videos -->
            <?php if (!empty($videos) && !$has_video_section): ?>
            <div class="p-4 bg-dark text-white rounded mb-5 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
                    <h3 class="text-white mb-0 fs-4 fw-bold"><i class="bi bi-play-btn-fill text-danger me-2"></i> ভিডিও খবর (Video Headlines)</h3>
                    <a href="video.php" class="text-warning small text-decoration-none fw-bold">সকল ভিডিও দেখুন &rarr;</a>
                </div>
                <div class="row g-3">
                    <?php foreach ($videos as $vid): ?>
                        <div class="col-md-6">
                            <div class="ratio ratio-16x9 rounded overflow-hidden mb-2 border border-secondary">
                                <iframe src="<?= htmlspecialchars($vid['video_url']) ?>" title="<?= htmlspecialchars($vid['title']) ?>" allowfullscreen loading="lazy"></iframe>
                            </div>
                            <h6 class="text-white mb-0 fw-semibold fs-6"><?= htmlspecialchars($vid['title']) ?></h6>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Multimedia Block: Photo Gallery -->
            <?php if (!empty($photos) && !$has_photo_section): ?>
            <div class="p-4 bg-light text-dark rounded mb-5 border shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h3 class="text-dark mb-0 fs-4 fw-bold"><i class="bi bi-images text-danger me-2"></i> ছবি গ্যালারি (Photo Gallery)</h3>
                    <a href="gallery.php" class="text-danger small text-decoration-none fw-bold">সকল অ্যালবাম দেখুন &rarr;</a>
                </div>
                <div class="row g-2">
                    <?php foreach ($photos as $ph): ?>
                        <div class="col-6 col-md-3">
                            <a href="gallery.php" class="d-block rounded overflow-hidden border shadow-sm position-relative group" style="aspect-ratio: 4/3;">
                                <img src="<?= htmlspecialchars($ph['photo_path']) ?>" class="w-100 h-100 object-fit-cover" alt="" loading="lazy">
                                <div class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-75 text-white text-truncate small px-2">
                                    <?= htmlspecialchars($ph['caption'] ?? 'Photo Gallery') ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right Sidebar (Sticky & Optimized) -->
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
