<?php
require_once __DIR__ . '/functions.php';
check_install_status();

$lang = get_current_lang();
$site_name = get_setting('site_name', 'Daily Horizon');
$site_title = get_setting('site_title', 'Daily Horizon - Truth First, Always Ahead');
$meta_desc = get_setting('meta_description', '');
$categories = get_categories(0);
$custom_header_menus = get_menus('header');
$show_breaking = get_setting('home_show_breaking', '1');
$breaking_news = ($show_breaking === '1') ? get_breaking_news(6) : [];
$breaking_label = ($lang === 'bn') ? get_setting('breaking_news_title_bn', 'জরুরি খবর') : get_setting('breaking_news_title_en', 'BREAKING');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts & SolaimanLipi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.maateen.me/solaiman-lipi/font.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php
    $default_theme = get_setting('default_theme_mode', 'light');
    $primary_color = get_setting('primary_color', '#e61e25');
    $primary_hover = get_setting('primary_hover_color', '#b91c1c');
    $topbar_bg = get_setting('topbar_bg_color', '#0f172a');
    $topbar_text = get_setting('topbar_text_color', '#f8fafc');
    $header_bg = get_setting('header_bg_color', '#ffffff');
    $header_text = get_setting('header_text_color', '#111111');
    $menu_bg = get_setting('menu_bg_color', '#991b1b');
    $menu_text = get_setting('menu_text_color', '#ffffff');
    $menu_hover_bg = get_setting('menu_hover_bg_color', '#7f1d1d');
    $body_bg = get_setting('body_bg_color', '#ffffff');
    $body_text = get_setting('body_text_color', '#111111');
    $card_bg = get_setting('card_bg_color', '#ffffff');
    $card_border = get_setting('card_border_color', '#e5e7eb');
    $title_color = get_setting('title_color', '#111111');
    $link_hover = get_setting('link_hover_color', '#e61e25');
    $footer_bg = get_setting('footer_bg_color', '#0f172a');
    $footer_text = get_setting('footer_text_color', '#94a3b8');
    $footer_heading = get_setting('footer_heading_color', '#ffffff');
    $footer_link = get_setting('footer_link_color', '#cbd5e1');
    $ticker_bg = get_setting('ticker_bg_color', '#dc2626');
    $ticker_text = get_setting('ticker_text_color', '#ffffff');
    $ticker_label_bg = get_setting('ticker_label_bg_color', '#0f172a');
    $ticker_label_text = get_setting('ticker_label_text_color', '#ffffff');
    $widget_header_bg = get_setting('widget_header_bg', '#991b1b');
    $badge_bg = get_setting('badge_bg_color', '#e61e25');
    $mobile_nav_bg = get_setting('mobile_nav_bg', '#0f172a');
    $custom_css = get_setting('custom_css', '');
    ?>
    <style id="custom-theme-vars">
    :root {
      --accent: <?= htmlspecialchars($primary_color) ?>;
      --dark-accent: <?= htmlspecialchars($primary_hover) ?>;
      --paper: <?= htmlspecialchars($body_bg) ?>;
      --ink: <?= htmlspecialchars($body_text) ?>;
      --card-bg: <?= htmlspecialchars($card_bg) ?>;
      --border-color: <?= htmlspecialchars($card_border) ?>;
    }
    .top-bar { background-color: <?= htmlspecialchars($topbar_bg) ?> !important; color: <?= htmlspecialchars($topbar_text) ?> !important; }
    .top-bar a { color: <?= htmlspecialchars($topbar_text) ?> !important; }
    .main-header { background-color: <?= htmlspecialchars($header_bg) ?> !important; color: <?= htmlspecialchars($header_text) ?> !important; }
    .site-title-logo { color: <?= htmlspecialchars($title_color) ?> !important; }
    .main-nav { background-color: <?= htmlspecialchars($menu_bg) ?> !important; }
    .main-nav .nav-link { color: <?= htmlspecialchars($menu_text) ?> !important; }
    .main-nav .nav-link:hover, .main-nav .nav-link.active { background-color: <?= htmlspecialchars($menu_hover_bg) ?> !important; color: <?= htmlspecialchars($menu_text) ?> !important; }
    .btn-danger, .badge.bg-danger, .lang-switch-btn { background-color: <?= htmlspecialchars($primary_color) ?> !important; border-color: <?= htmlspecialchars($primary_color) ?> !important; }
    .btn-danger:hover, .lang-switch-btn:hover { background-color: <?= htmlspecialchars($primary_hover) ?> !important; border-color: <?= htmlspecialchars($primary_hover) ?> !important; }
    a:hover, .hover-red:hover { color: <?= htmlspecialchars($link_hover) ?> !important; }
    .site-footer { background-color: <?= htmlspecialchars($footer_bg) ?> !important; color: <?= htmlspecialchars($footer_text) ?> !important; }
    .site-footer h3, .site-footer h4, .site-footer h5 { color: <?= htmlspecialchars($footer_heading) ?> !important; }
    .site-footer a { color: <?= htmlspecialchars($footer_link) ?> !important; }
    .breaking-ticker { background-color: <?= htmlspecialchars($ticker_bg) ?> !important; color: <?= htmlspecialchars($ticker_text) ?> !important; }
    .breaking-ticker .breaking-label { background-color: <?= htmlspecialchars($ticker_label_bg) ?> !important; color: <?= htmlspecialchars($ticker_label_text) ?> !important; }
    .breaking-ticker a { color: <?= htmlspecialchars($ticker_text) ?> !important; }
    .section-title, .widget-title, .sidebar-widget-title { border-color: <?= htmlspecialchars($widget_header_bg) ?> !important; }
    .badge.bg-danger, .badge-category { background-color: <?= htmlspecialchars($badge_bg) ?> !important; }
    @media (max-width: 991.98px) {
        .navbar-collapse { background-color: <?= htmlspecialchars($mobile_nav_bg) ?> !important; padding: 15px; border-radius: 8px; margin-top: 10px; }
    }
    <?= $custom_css ?>
    </style>
    <script>
        (function() {
            // Inline theme initialization to prevent flash
            const savedTheme = localStorage.getItem('theme');
            const defaultTheme = '<?= htmlspecialchars($default_theme) ?>';
            const activeTheme = savedTheme ? savedTheme : defaultTheme;
            document.documentElement.setAttribute('data-bs-theme', activeTheme);
            if (activeTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body>

<!-- Top Utility Bar -->
<div class="top-bar no-print">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small"><i class="bi bi-calendar3 me-1 text-danger"></i> <?= ($lang === 'bn') ? get_full_bangla_date_string() : date('l, F j, Y') ?></span>
            <span class="text-white-50 d-none d-md-inline">&bull;</span>
            <span class="text-white-50 d-none d-md-inline small"><i class="bi bi-geo-alt me-1 text-warning"></i> <?= ($lang === 'bn') ? 'ঢাকা, বাংলাদেশ' : 'Dhaka, Bangladesh' ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Language Switcher -->
            <?php if (get_setting('enable_translation', '1') === '1'): ?>
                <?php
                $current_params = $_GET;
                $current_params['lang'] = 'en';
                $en_url = '?' . http_build_query($current_params);
                $current_params['lang'] = 'bn';
                $bn_url = '?' . http_build_query($current_params);
                ?>
                <?php if ($lang === 'bn'): ?>
                    <a href="<?= htmlspecialchars($en_url) ?>" class="lang-switch-btn"><i class="bi bi-translate"></i> English</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($bn_url) ?>" class="lang-switch-btn"><i class="bi bi-translate"></i> বাংলা</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Dark / Light Mode Switcher -->
            <button id="themeToggleBtn" onclick="toggleTheme()" class="theme-toggle-btn">
                <i class="bi bi-moon-stars-fill text-warning" id="themeIcon"></i> <span id="themeLabel" class="d-none d-sm-inline">Dark</span>
            </button>

            <?php
            $top_menus = get_menus('top');
            if (!empty($top_menus)):
                foreach ($top_menus as $tm):
            ?>
                <a href="<?= htmlspecialchars($tm['url']) ?>" target="<?= htmlspecialchars($tm['target'] ?? '_self') ?>" class="me-2 text-white-50 small d-none d-lg-inline"><?= htmlspecialchars($tm['title']) ?></a>
            <?php 
                endforeach;
            else: 
            ?>
                <a href="page.php?slug=about-us" class="me-1 d-none d-lg-inline text-white-50 small ms-2"><?= __('আমাদের সম্পর্কে', 'About Us') ?></a>
                <a href="contact.php" class="me-1 d-none d-lg-inline text-white-50 small"><?= __('যোগাযোগ', 'Contact Us') ?></a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars(get_setting('facebook', '#')) ?>" target="_blank" class="text-white-50"><i class="bi bi-facebook"></i></a>
            <a href="<?= htmlspecialchars(get_setting('twitter', '#')) ?>" target="_blank" class="text-white-50"><i class="bi bi-twitter-x"></i></a>
            <a href="<?= htmlspecialchars(get_setting('youtube', '#')) ?>" target="_blank" class="text-white-50"><i class="bi bi-youtube"></i></a>
        </div>
    </div>
</div>

<?php
$logo_url = get_setting('logo_url', '');
$logo_pos = get_setting('logo_position', 'left');
$logo_h = (int)get_setting('logo_height', '70');
if ($logo_h < 15 || $logo_h > 400) $logo_h = 70;
$logo_w = (int)get_setting('logo_width', '0');
if ($logo_w < 0 || $logo_w > 800) $logo_w = 0;

$header_preset = get_setting('header_layout_preset', 'standard');
$mobile_preset = get_setting('mobile_header_preset', 'standard');
$mobile_show_nav_logo = get_setting('mobile_show_nav_logo', '0');
$header_layout = get_setting('header_layout_type', 'logo_left_ad_right');
$site_tagline = get_setting('site_tagline', ($lang === 'bn') ? 'সত্যের সন্ধানে অবিরত • দৈনিক খবর' : 'Truth First • Always Ahead');
$header_ad_slot = get_setting('header_ad_position', 'header_top');

// Mobile header class modifier
$mobile_header_class = ($mobile_preset === 'compact_sticky') ? 'd-none d-lg-block' : '';

function render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, $logo_pos, $logo_w = 0) {
    $align_class = ($logo_pos === 'center') ? 'text-center' : (($logo_pos === 'right') ? 'text-lg-end text-center' : 'text-lg-start text-center');
    $style_arr = [];
    if ($logo_h > 0) $style_arr[] = "max-height: {$logo_h}px;";
    if ($logo_w > 0) $style_arr[] = "max-width: {$logo_w}px; width: 100%;";
    else $style_arr[] = "width: auto;";
    $style_arr[] = "object-fit: contain;";
    $img_style = implode(' ', $style_arr);

    ob_start();
    ?>
    <div class="site-branding <?= $align_class ?>">
        <a href="index.php" class="d-inline-block text-decoration-none">
            <?php if (!empty($logo_url)): ?>
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="img-fluid site-logo-img" style="<?= $img_style ?>">
            <?php else: ?>
                <span class="site-title-logo"><?= htmlspecialchars($site_name) ?></span>
            <?php endif; ?>
        </a>
        <?php if (!empty($site_tagline)): ?>
            <div class="text-muted small fw-semibold text-uppercase tracking-wider mt-1"><?= htmlspecialchars($site_tagline) ?></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<?php if ($header_preset === 'centered'): ?>
<!-- PRESET 2: Centered Brand Logo Header -->
<header class="main-header no-print py-4 text-center border-bottom bg-white <?= $mobile_header_class ?>">
    <div class="container">
        <div class="mb-3">
            <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, 'center') ?>
        </div>
        <div class="mt-3">
            <?= render_ad($header_ad_slot) ?>
        </div>
    </div>
</header>
<?php elseif ($header_preset === 'compact'): ?>
<!-- PRESET 3: Compact Modern Bar Header -->
<header class="main-header no-print py-2 bg-dark text-white border-bottom shadow-sm <?= $mobile_header_class ?>">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="text-decoration-none text-white fw-bold fs-4 font-serif">
                <?php if (!empty($logo_url)): ?>
                    <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($site_name) ?>" style="max-height: 40px; object-fit: contain;">
                <?php else: ?>
                    <span class="text-danger fw-extrabold"><?= htmlspecialchars($site_name) ?></span>
                <?php endif; ?>
            </a>
            <span class="text-white-50 small d-none d-md-inline">| <?= htmlspecialchars($site_tagline) ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-danger px-3 py-2 text-uppercase"><i class="bi bi-clock me-1"></i> <?= date('h:i A') ?></span>
            <button id="themeToggleBtnCompact" onclick="toggleTheme()" class="btn btn-sm btn-outline-light rounded-pill px-3">
                <i class="bi bi-moon-stars-fill text-warning"></i>
            </button>
        </div>
    </div>
</header>
<?php elseif ($header_preset === 'magazine'): ?>
<!-- PRESET 4: Magazine Double-Border Header -->
<header class="main-header no-print py-3 bg-light border-bottom border-top border-2 border-danger <?= $mobile_header_class ?>">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-3 text-center text-lg-start mb-2 mb-lg-0">
                <span class="badge bg-dark px-3 py-2 text-uppercase small"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= ($lang === 'bn') ? 'ঢাকা, বাংলাদেশ' : 'Dhaka, BD' ?></span>
            </div>
            <div class="col-lg-6 text-center">
                <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, 'center', $logo_w) ?>
            </div>
            <div class="col-lg-3 text-center text-lg-end">
                <a href="e-paper.php" class="btn btn-sm btn-danger fw-bold rounded-pill px-3"><i class="bi bi-newspaper me-1"></i> E-Paper</a>
            </div>
        </div>
        <div class="mt-3 text-center">
            <?= render_ad($header_ad_slot) ?>
        </div>
    </div>
</header>
<?php elseif ($header_preset === 'gradient_sleek'): ?>
<!-- PRESET 5: Premium Sleek Gradient Header -->
<header class="main-header no-print py-3 border-bottom shadow-sm <?= $mobile_header_class ?>" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-4 text-center text-lg-start mb-2 mb-lg-0">
                <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, 'left', $logo_w) ?>
            </div>
            <div class="col-lg-8 text-center text-lg-end">
                <?= render_ad($header_ad_slot) ?>
            </div>
        </div>
    </div>
</header>
<?php elseif ($header_preset === 'minimal_slim'): ?>
<!-- PRESET 6: Minimalist Ultra-Slim Header -->
<header class="main-header no-print py-2 border-bottom bg-white <?= $mobile_header_class ?>">
    <div class="container d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <?= render_header_logo_block($logo_url, $site_name, $site_tagline, 45, 'left', $logo_w) ?>
        </div>
        <div>
            <?= render_ad($header_ad_slot) ?>
        </div>
    </div>
</header>
<?php else: ?>
<!-- PRESET 1: Standard Classic News Header -->
<header class="main-header no-print py-3 <?= $mobile_header_class ?>">
    <div class="container">
        <?php if ($header_layout === 'logo_center_ad_below'): ?>
            <div class="row align-items-center">
                <div class="col-12 text-center mb-3">
                    <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, 'center', $logo_w) ?>
                </div>
                <div class="col-12 text-center">
                    <?= render_ad($header_ad_slot) ?>
                </div>
            </div>
        <?php elseif ($header_layout === 'logo_center_ad_top'): ?>
            <div class="row align-items-center">
                <div class="col-12 text-center mb-3">
                    <?= render_ad($header_ad_slot) ?>
                </div>
                <div class="col-12 text-center">
                    <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, 'center', $logo_w) ?>
                </div>
            </div>
        <?php elseif ($header_layout === 'logo_right_ad_left'): ?>
            <div class="row align-items-center flex-row-reverse">
                <div class="col-lg-4 text-center text-lg-end mb-3 mb-lg-0">
                    <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, 'right', $logo_w) ?>
                </div>
                <div class="col-lg-8 text-center text-lg-start">
                    <?= render_ad($header_ad_slot) ?>
                </div>
            </div>
        <?php else: // Default: logo_left_ad_right ?>
            <div class="row align-items-center">
                <div class="col-lg-4 text-center text-lg-start mb-3 mb-lg-0">
                    <?= render_header_logo_block($logo_url, $site_name, $site_tagline, $logo_h, $logo_pos, $logo_w) ?>
                </div>
                <div class="col-lg-8 text-center text-lg-end">
                    <?= render_ad($header_ad_slot) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>

<!-- Sticky Navigation Bar (Sleek & Compact) -->
<nav class="navbar navbar-expand-lg navbar-light main-nav no-print py-0">
    <div class="container py-0">
        <a class="navbar-brand d-lg-none fw-bold text-white py-1 text-decoration-none" href="index.php">
            <?php 
            if ($mobile_preset === 'compact_sticky' || $mobile_show_nav_logo === '1') {
                if (!empty($logo_url)) {
                    echo '<img src="' . htmlspecialchars($logo_url) . '" alt="' . htmlspecialchars($site_name) . '" style="max-height: 32px; max-width: 160px; width: auto; object-fit: contain;">';
                } else {
                    echo '<span class="site-title-logo fs-5 text-white">' . htmlspecialchars($site_name) . '</span>';
                }
            } else if ($mobile_show_nav_logo === 'text_only') {
                echo '<span class="text-white fw-bold fs-6"><i class="bi bi-newspaper me-1"></i> ' . htmlspecialchars($site_name) . '</span>';
            } else {
                // Default '0': Hide duplicate logo image in sticky navbar on mobile
                echo '<span class="text-white fw-bold small text-uppercase tracking-wide"><i class="bi bi-newspaper me-1 text-danger"></i> ' . htmlspecialchars($site_name) . '</span>';
            }
            ?>
        </a>
        <button class="navbar-toggler py-1 px-2 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php"><i class="bi bi-house-door-fill me-1"></i> <?= __('প্রচ্ছদ', 'Home') ?></a>
                </li>
                <?php if (!empty($custom_header_menus)): ?>
                    <?php foreach ($custom_header_menus as $cm): ?>
                        <?php if (!empty($cm['children'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="<?= htmlspecialchars($cm['url']) ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false" target="<?= htmlspecialchars($cm['target'] ?? '_self') ?>">
                                    <?= htmlspecialchars($cm['title']) ?>
                                </a>
                                <ul class="dropdown-menu border-0 shadow-lg">
                                    <?php foreach ($cm['children'] as $child): ?>
                                        <li><a class="dropdown-item py-2" href="<?= htmlspecialchars($child['url']) ?>" target="<?= htmlspecialchars($child['target'] ?? '_self') ?>"><i class="bi bi-chevron-right me-1 text-danger small"></i> <?= htmlspecialchars($child['title']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= htmlspecialchars($cm['url']) ?>" target="<?= htmlspecialchars($cm['target'] ?? '_self') ?>"><?= htmlspecialchars($cm['title']) ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($categories as $cat): 
                        $subs = get_categories($cat['id']);
                        $cat_display_name = get_category_display_name($cat['name']);
                    ?>
                        <?php if (count($subs) > 0): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="category.php?slug=<?= $cat['slug'] ?>" id="catDrop<?= $cat['id'] ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= htmlspecialchars($cat_display_name) ?>
                                </a>
                                <ul class="dropdown-menu border-0 shadow-lg">
                                    <li><a class="dropdown-item fw-bold text-danger py-2" href="category.php?slug=<?= $cat['slug'] ?>"><i class="bi bi-grid-fill me-1"></i> <?= __('সব', 'All') ?> <?= htmlspecialchars($cat_display_name) ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($subs as $sub): 
                                        $sub_display_name = get_category_display_name($sub['name']);
                                    ?>
                                        <li><a class="dropdown-item py-2" href="category.php?slug=<?= $cat['slug'] ?>&sub=<?= $sub['slug'] ?>"><i class="bi bi-chevron-right me-1 text-secondary small"></i> <?= htmlspecialchars($sub_display_name) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="category.php?slug=<?= $cat['slug'] ?>"><?= htmlspecialchars($cat_display_name) ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="gallery.php"><i class="bi bi-images me-1"></i> <?= __('ছবি', 'Photos') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="video.php"><i class="bi bi-play-circle me-1"></i> <?= __('ভিডিও', 'Videos') ?></a>
                </li>
            </ul>
            
            <!-- Live Search Bar Dropdown Trigger -->
            <div class="d-flex align-items-center position-relative my-1 my-lg-0 w-100 w-lg-auto" style="max-height: 30px;">
                <form action="search.php" method="GET" class="d-flex w-100 align-items-center">
                    <div class="input-group input-group-sm w-100" style="max-height: 28px;">
                        <input type="text" id="ajax-search-input" name="q" class="form-control rounded-start py-0 px-2" style="font-size: 0.8rem; height: 28px; line-height: 28px;" placeholder="<?= __('সংবাদ খুঁজুন...', 'Search news...') ?>" autocomplete="off">
                        <button class="btn btn-danger py-0 px-2 d-flex align-items-center justify-content-center" type="submit" style="height: 28px; width: 32px;"><i class="bi bi-search" style="font-size: 0.78rem;"></i></button>
                    </div>
                </form>
                <div id="ajax-search-results" class="position-absolute start-0 end-0 top-100 mt-1 z-3 shadow bg-body rounded border" style="display: none; max-height: 400px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</nav>

<!-- Breaking News Ticker -->
<?php if (!empty($breaking_news)): ?>
<div class="breaking-ticker no-print">
    <div class="container d-flex align-items-center p-0">
        <div class="breaking-label"><i class="bi bi-lightning-fill me-1"></i> <?= htmlspecialchars($breaking_label) ?></div>
        <div class="ticker-marquee flex-grow-1 px-3">
            <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop();" onmouseout="this.start();">
                <?php foreach ($breaking_news as $b_item): ?>
                    <a href="article.php?slug=<?= $b_item['slug'] ?>">&bull; <?= htmlspecialchars($b_item['title']) ?></a>
                <?php endforeach; ?>
            </marquee>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Below Header Ad Slot -->
<div class="container">
    <?= render_ad('below_header', 'my-2') ?>
</div>
