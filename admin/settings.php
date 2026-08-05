<?php
require_once __DIR__ . '/header.php';
require_role_permission('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_name', 'site_tagline', 'logo_url', 'favicon_url', 'logo_position', 'logo_height', 'logo_width', 'print_logo_url',
        'header_layout_preset', 'mobile_header_preset', 'mobile_show_nav_logo', 'footer_layout_preset', 'homepage_layout_preset',
        'footer_logo_url', 'footer_logo_position', 'footer_logo_height', 'footer_logo_width',
        'header_layout_type', 'header_ad_position', 'header_ad_height', 'enable_drop_cap', 'enable_translation', 'default_language',
        'home_show_breaking', 'breaking_news_limit', 'breaking_news_title_bn', 'breaking_news_title_en', 'require_post_approval',
        'editor_name', 'publisher_name', 'chief_editor', 'address', 'phone', 'mobile', 'email',
        'office_time', 'social_facebook', 'social_twitter', 'social_youtube', 'google_map',
        'fb_widget_enabled', 'fb_page_url', 'fb_widget_height', 'fb_widget_width',
        'footer_copyright', 'footer_text',
        'share_facebook', 'share_twitter', 'share_whatsapp', 'share_linkedin',
        'share_telegram', 'share_pinterest', 'share_email', 'share_copy', 'share_print'
    ];
    $share_keys = ['share_facebook', 'share_twitter', 'share_whatsapp', 'share_linkedin', 'share_telegram', 'share_pinterest', 'share_email', 'share_copy', 'share_print', 'enable_drop_cap', 'enable_translation', 'home_show_breaking', 'fb_widget_enabled', 'require_post_approval'];
    foreach ($fields as $f) {
        if (in_array($f, $share_keys)) {
            $val = isset($_POST[$f]) ? '1' : '0';
            set_setting($f, $val);
        } else if (isset($_POST[$f])) {
            set_setting($f, trim($_POST[$f]));
        }
    }
    $msg = "Website Header, Social Share & General Settings Updated Successfully!";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="bi bi-sliders me-2"></i> Site & Header Configuration</h3>
</div>

<?php if ($msg): ?><div class="alert alert-success shadow-sm fw-bold"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form action="settings.php" method="POST" style="max-width: 1000px;">
    <!-- Card 0: Prebuilt Layout & Design Presets -->
    <div class="card p-4 shadow-sm border mb-4 bg-light">
        <h5 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-palette2 me-2"></i> Header & Footer Layout Presets</h5>
        <p class="text-muted small mb-3">Select prebuilt structural design presets for your Header and Footer. Visitors will see the selected design instantly!</p>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold"><i class="bi bi-layout-three-columns text-danger me-1"></i> Header Preset</label>
                <?php $hlp = get_setting('header_layout_preset', 'standard'); ?>
                <select name="header_layout_preset" class="form-select border-danger fw-semibold">
                    <option value="standard" <?= $hlp === 'standard' ? 'selected' : '' ?>>1. Standard Classic News Header (Sleek Compact Nav)</option>
                    <option value="centered" <?= $hlp === 'centered' ? 'selected' : '' ?>>2. Centered Brand Logo Header</option>
                    <option value="compact" <?= $hlp === 'compact' ? 'selected' : '' ?>>3. Compact Modern Bar Header</option>
                    <option value="magazine" <?= $hlp === 'magazine' ? 'selected' : '' ?>>4. Magazine Double-Border Header</option>
                    <option value="gradient_sleek" <?= $hlp === 'gradient_sleek' ? 'selected' : '' ?>>5. Premium Sleek Gradient Header</option>
                    <option value="minimal_slim" <?= $hlp === 'minimal_slim' ? 'selected' : '' ?>>6. Minimalist Ultra-Slim Header</option>
                </select>
                <div class="p-2 mt-2 bg-white border rounded text-center small shadow-sm">
                    <span class="badge bg-dark mb-1">Visual Sample</span>
                    <div class="border border-secondary p-1 rounded bg-light" style="font-size: 0.72rem;">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1">
                            <span class="fw-bold text-danger">LOGO</span>
                            <span class="bg-secondary text-white px-2 py-1 rounded">LEADERBOARD AD 728x90</span>
                        </div>
                        <div class="bg-danger text-white py-1 fw-bold">NAV BAR: Home | National | Politics | Sports</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold"><i class="bi bi-layout-sidebar-reverse text-danger me-1"></i> Footer Layout Preset</label>
                <?php $flp_preset = get_setting('footer_layout_preset', 'standard'); ?>
                <select name="footer_layout_preset" class="form-select border-danger fw-semibold">
                    <option value="standard" <?= $flp_preset === 'standard' ? 'selected' : '' ?>>1. Standard 4-Column Newspaper Footer</option>
                    <option value="centered" <?= $flp_preset === 'centered' ? 'selected' : '' ?>>2. Minimalist Centered Brand Footer</option>
                    <option value="newspaper_broad" <?= $flp_preset === 'newspaper_broad' ? 'selected' : '' ?>>3. Broad Editorial Board Footer</option>
                    <option value="dark_modern" <?= $flp_preset === 'dark_modern' ? 'selected' : '' ?>>4. Dark Modern Magazine Footer</option>
                </select>
                <div class="p-2 mt-2 bg-white border rounded text-center small shadow-sm">
                    <span class="badge bg-dark mb-1">Visual Sample</span>
                    <div class="border border-secondary p-1 rounded bg-dark text-white" style="font-size: 0.72rem;">
                        <div class="row g-1 text-start">
                            <div class="col-3 border-end"><b>BRAND</b><br>About</div>
                            <div class="col-3 border-end"><b>CATS</b><br>Links</div>
                            <div class="col-3 border-end"><b>LEGAL</b><br>Pages</div>
                            <div class="col-3"><b>CONTACT</b><br>Phone</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 border-danger bg-white shadow-sm h-100 d-flex flex-column justify-content-between">
                    <div>
                        <label class="form-label fw-bold text-danger"><i class="bi bi-window-stack me-1"></i> Homepage Layout & Sections</label>
                        <p class="small text-muted mb-2">
                            হোমপেজের ডিজাইন লেআউট ও ক্যাটাগরি সেকশন ড্র্যাগ-অ্যান্ড-ড্রপ সুবিধা হোমপেজ ম্যানেজার থেকে পরিচালনা করুন।
                        </p>
                    </div>
                    <a href="homepage.php" class="btn btn-danger btn-sm fw-bold w-100 py-2">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Go to Homepage Manager &rarr;
                    </a>
                </div>
            </div>

            <!-- Mobile Header Controls -->
            <div class="col-md-6 mt-3">
                <label class="form-label fw-bold"><i class="bi bi-phone text-danger me-1"></i> Mobile Header Preset Layout</label>
                <?php $mhp = get_setting('mobile_header_preset', 'standard'); ?>
                <select name="mobile_header_preset" class="form-select border-primary fw-semibold">
                    <option value="standard" <?= $mhp === 'standard' ? 'selected' : '' ?>>1. Standard Mobile Header (Top Main Logo + Sticky Nav Bar)</option>
                    <option value="compact_sticky" <?= $mhp === 'compact_sticky' ? 'selected' : '' ?>>2. Compact Sticky Mobile Bar (Hides top logo on mobile, shows inside navbar)</option>
                    <option value="centered_brand" <?= $mhp === 'centered_brand' ? 'selected' : '' ?>>3. Centered Brand Mobile Header (Centered Logo + Slim Action Bar)</option>
                    <option value="app_style" <?= $mhp === 'app_style' ? 'selected' : '' ?>>4. App-Style Minimal Top Bar (Logo + Search + Mobile Drawer)</option>
                </select>
                <small class="text-muted d-block mt-1">Controls how your newspaper header displays on mobile smartphones.</small>
            </div>

            <div class="col-md-6 mt-3">
                <label class="form-label fw-bold"><i class="bi bi-intersect text-danger me-1"></i> Mobile Navbar Brand Logo Display</label>
                <?php $msnl = get_setting('mobile_show_nav_logo', '0'); ?>
                <select name="mobile_show_nav_logo" class="form-select border-primary fw-semibold">
                    <option value="0" <?= $msnl === '0' ? 'selected' : '' ?>>Hide Logo Image in Sticky Navbar on Mobile (Fixes Double Logo)</option>
                    <option value="1" <?= $msnl === '1' ? 'selected' : '' ?>>Show Logo Image in Sticky Navbar on Mobile</option>
                    <option value="text_only" <?= $msnl === 'text_only' ? 'selected' : '' ?>>Show Text Site Name Only in Sticky Navbar on Mobile</option>
                </select>
                <small class="text-muted d-block mt-1">Prevents double logos stacked on mobile screens when top header logo is already visible.</small>
            </div>
        </div>
    </div>

    <!-- Card 1: Logo & Header Customization -->
    <div class="card p-4 shadow-sm border mb-4">
        <h5 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-layout-header me-2"></i> Header, Logo & Print Customization</h5>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Newspaper Name / Title *</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars(get_setting('site_name', 'দৈনিক দিগন্ত')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Site Tagline / Subtitle</label>
                <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars(get_setting('site_tagline', 'সত্যের সন্ধানে অবিরত • দৈনিক খবর')) ?>" placeholder="e.g. সত্যের সন্ধানে অবিরত">
            </div>

            <div class="col-md-8">
                <label class="form-label fw-semibold">Header Logo Image URL</label>
                <div class="input-group">
                    <input type="text" id="logo_url_input" name="logo_url" class="form-control" value="<?= htmlspecialchars(get_setting('logo_url')) ?>" placeholder="Leave blank to display text title">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#logo_url_input"><i class="bi bi-images me-1"></i> Media</button>
                </div>
                <small class="text-muted">If logo image is provided, it replaces the text headline on the header.</small>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Favicon Icon URL</label>
                <div class="input-group">
                    <input type="text" id="favicon_url_input" name="favicon_url" class="form-control" value="<?= htmlspecialchars(get_setting('favicon_url')) ?>">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#favicon_url_input"><i class="bi bi-images me-1"></i> Media</button>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Print Article Header Logo URL</label>
                <div class="input-group">
                    <input type="text" id="print_logo_url_input" name="print_logo_url" class="form-control" value="<?= htmlspecialchars(get_setting('print_logo_url')) ?>" placeholder="Optional special logo for printable news sheets">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#print_logo_url_input"><i class="bi bi-images me-1"></i> Media</button>
                </div>
                <small class="text-muted">Used exclusively when readers print an article page.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Logo Alignment / Position</label>
                <?php $lp = get_setting('logo_position', 'left'); ?>
                <select name="logo_position" class="form-select">
                    <option value="left" <?= $lp === 'left' ? 'selected' : '' ?>>Left Aligned</option>
                    <option value="center" <?= $lp === 'center' ? 'selected' : '' ?>>Centered Logo</option>
                    <option value="right" <?= $lp === 'right' ? 'selected' : '' ?>>Right Aligned</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Logo Max Height (Pixels)</label>
                <input type="number" name="logo_height" class="form-control" value="<?= htmlspecialchars(get_setting('logo_height', '70')) ?>" placeholder="70" min="15" max="400">
                <small class="text-muted">e.g. 70px, 90px, 120px.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Logo Max Width (Pixels, Optional)</label>
                <input type="number" name="logo_width" class="form-control" value="<?= htmlspecialchars(get_setting('logo_width', '0')) ?>" placeholder="0 (auto)" min="0" max="800">
                <small class="text-muted">Set 0 for auto responsive ratio scaling.</small>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Header Layout Style</label>
                <?php $hl = get_setting('header_layout_type', 'logo_left_ad_right'); ?>
                <select name="header_layout_type" class="form-select">
                    <option value="logo_left_ad_right" <?= $hl === 'logo_left_ad_right' ? 'selected' : '' ?>>Logo Left + Header Ad Right (Standard)</option>
                    <option value="logo_center_ad_below" <?= $hl === 'logo_center_ad_below' ? 'selected' : '' ?>>Centered Logo + Banner Ad Below</option>
                    <option value="logo_center_ad_top" <?= $hl === 'logo_center_ad_top' ? 'selected' : '' ?>>Banner Ad Top + Centered Logo Below</option>
                    <option value="logo_right_ad_left" <?= $hl === 'logo_right_ad_left' ? 'selected' : '' ?>>Logo Right + Header Ad Left</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Active Header Ad Slot</label>
                <?php $hap = get_setting('header_ad_position', 'header_top'); ?>
                <select name="header_ad_position" class="form-select">
                    <option value="header_top" <?= $hap === 'header_top' ? 'selected' : '' ?>>Header Top Leaderboard Banner (header_top)</option>
                    <option value="header_aside" <?= $hap === 'header_aside' ? 'selected' : '' ?>>Beside Logo Banner (header_aside)</option>
                    <option value="below_header" <?= $hap === 'below_header' ? 'selected' : '' ?>>Below Menu Header Banner (below_header)</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Header Ad Max Height (Pixels)</label>
                <input type="number" name="header_ad_height" class="form-control" value="<?= htmlspecialchars(get_setting('header_ad_height', '120')) ?>" placeholder="120">
                <small class="text-muted">Limit max height of banner image in header (e.g., 90px, 120px, 200px).</small>
            </div>

            <!-- Footer Logo Sub-section -->
            <div class="col-12 border-top pt-3 mt-3">
                <h6 class="fw-bold text-secondary"><i class="bi bi-layout-footer me-1"></i> Footer Logo Customization</h6>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Footer Logo Image URL</label>
                <div class="input-group">
                    <input type="text" id="footer_logo_url_input" name="footer_logo_url" class="form-control" value="<?= htmlspecialchars(get_setting('footer_logo_url')) ?>" placeholder="Leave blank to use Header Logo or text">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#footer_logo_url_input"><i class="bi bi-images me-1"></i> Media</button>
                </div>
                <small class="text-muted">If empty, defaults to header logo or site title text.</small>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Footer Logo Alignment</label>
                <?php $flp = get_setting('footer_logo_position', 'left'); ?>
                <select name="footer_logo_position" class="form-select">
                    <option value="left" <?= $flp === 'left' ? 'selected' : '' ?>>Left Aligned</option>
                    <option value="center" <?= $flp === 'center' ? 'selected' : '' ?>>Centered Logo</option>
                    <option value="right" <?= $flp === 'right' ? 'selected' : '' ?>>Right Aligned</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Footer Logo Height (Pixels)</label>
                <input type="number" name="footer_logo_height" class="form-control" value="<?= htmlspecialchars(get_setting('footer_logo_height', '60')) ?>" placeholder="60" min="20" max="250">
            </div>
        </div>
    </div>

    <!-- Card: Language & Dual Translation System Settings -->
    <div class="card p-4 shadow-sm border mb-4 bg-white">
        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-translate me-2"></i> Dual Language & Translation Settings (দ্বিভাষিক অনুবাদ সেটিংস)</h5>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="form-check form-switch card p-3 bg-light border h-100">
                    <input class="form-check-input" type="checkbox" name="enable_translation" id="enable_translation" value="1" <?= get_setting('enable_translation', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold fs-6" for="enable_translation">
                        <i class="bi bi-globe2 text-primary me-2"></i> Enable Dual Language Translation System (Bangla & English)
                    </label>
                    <small class="text-muted d-block mt-2">
                        <strong>অন থাকলে (ON):</strong> ফ্রন্টএন্ডে ল্যাঙ্গুয়েজ সুইচ (BN | EN) বাটন থাকবে এবং পোস্ট করার সময় বাংলা ও ইংলিশ উভয় অপশন ট্যাব থাকবে।<br>
                        <strong>অফ থাকলে (OFF):</strong> ফ্রন্টএন্ড থেকে ল্যাঙ্গুয়েজ বাটন হাইড থাকবে এবং পোস্ট ক্রিয়েট করার সময় সিঙ্গেল ল্যাঙ্গুয়েজ ফিল্ড থাকবে।
                    </small>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card p-3 bg-light border h-100">
                    <label class="form-label fw-bold"><i class="bi bi-flag-fill text-danger me-1"></i> Default Website Language (ডিফল্ট ভাষা)</label>
                    <?php $deflang = get_setting('default_language', 'en'); ?>
                    <select name="default_language" class="form-select border-primary fw-semibold mb-2">
                        <option value="en" <?= $deflang === 'en' ? 'selected' : '' ?>>English (English Default)</option>
                        <option value="bn" <?= $deflang === 'bn' ? 'selected' : '' ?>>বাংলা (Bangla Default)</option>
                    </select>
                    <small class="text-muted">
                        অনুবাদ সিস্টেম বন্ধ (OFF) থাকলে সম্পূর্ণ ওয়েবসাইট এই ডিফল্ট ভাষায় প্রদর্শিত হবে।
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Breaking News Ticker Configuration -->
    <div class="card p-4 shadow-sm border mb-4 bg-white border-start border-4 border-danger">
        <h5 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-lightning-charge-fill me-2"></i> Breaking News Ticker Settings (জরুরি খবর সেকশন সেটিংস)</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="form-check form-switch card p-3 bg-light border h-100">
                    <input class="form-check-input" type="checkbox" name="home_show_breaking" id="home_show_breaking" value="1" <?= get_setting('home_show_breaking', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="home_show_breaking">
                        <i class="bi bi-broadcast text-danger me-1"></i> Display Breaking News Ticker
                    </label>
                    <small class="text-muted d-block mt-2">হেডারের নিচে স্লাইডিং ব্রেকিং নিউজ বার প্রদর্শন করতে টিকেল মার্ক রাখুন।</small>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold"><i class="bi bi-list-ol me-1 text-danger"></i> Breaking News Post Limit (আইটেম সংখ্যা)</label>
                <input type="number" name="breaking_news_limit" class="form-control border-danger fw-semibold" value="<?= htmlspecialchars(get_setting('breaking_news_limit', '20')) ?>" min="1" max="100" placeholder="20">
                <small class="text-muted">সর্বোচ্চ কতটি সাম্প্রতিক ব্রেকিং পোস্ট স্ক্রোল করবে (ডিফল্ট: ২০টি)</small>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold"><i class="bi bi-pencil-square me-1 text-danger"></i> Bangla Label (বাংলা লেবেল)</label>
                <input type="text" name="breaking_news_title_bn" class="form-control border-danger fw-semibold" value="<?= htmlspecialchars(get_setting('breaking_news_title_bn', 'জরুরি খবর')) ?>" placeholder="e.g. জরুরি খবর, সর্বশেষ খবর">
                <small class="text-muted">বাংলা ওয়েবসাইট সংস্করণের টিকারে লেবেল</small>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold"><i class="bi bi-pencil-square me-1 text-danger"></i> English Label (ইংলিশ লেবেল)</label>
                <input type="text" name="breaking_news_title_en" class="form-control border-danger fw-semibold" value="<?= htmlspecialchars(get_setting('breaking_news_title_en', 'BREAKING NEWS')) ?>" placeholder="e.g. BREAKING NEWS">
                <small class="text-muted">ইংলিশ ওয়েবসাইট সংস্করণের টিকারে লেবেল</small>
            </div>
        </div>
    </div>

    <!-- Post Approval & Workflow Settings Card -->
    <div class="card p-4 shadow-sm border mb-4 bg-white border-start border-4 border-warning">
        <h5 class="fw-bold text-warning-emphasis border-bottom pb-2 mb-3"><i class="bi bi-shield-check me-2"></i> Post Approval & Reporter Workflow (পোস্ট অনুমোদন সেটিংস)</h5>
        <div class="row g-3">
            <div class="col-md-12">
                <div class="form-check form-switch card p-3 bg-light border">
                    <input class="form-check-input" type="checkbox" name="require_post_approval" id="require_post_approval" value="1" <?= get_setting('require_post_approval', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="require_post_approval">
                        Require Admin/Editor Approval for Reporter Posts (রিপোর্টারদের পোস্ট এডমিন দ্বারা অনুমোদিত হওয়া বাধ্যতামূলক)
                    </label>
                    <small class="text-muted d-block mt-1">চালু থাকলে রিপোর্টার/সাংবাদিকদের প্রকাশিত পোস্ট সরাসরি সাইটে দেখাবে না, পেন্ডিং স্ট্যাটাসে থাকবে। এডমিন বা এডিটর এপ্রুভ করলে তবেই তা প্রকাশিত হবে।</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Editorial & Office Contact Information -->
    <div class="card p-4 shadow-sm border mb-4">
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-building me-2"></i> Publisher & Office Details</h5>
        
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Editor Name</label>
                <input type="text" name="editor_name" class="form-control" value="<?= htmlspecialchars(get_setting('editor_name')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Publisher Name</label>
                <input type="text" name="publisher_name" class="form-control" value="<?= htmlspecialchars(get_setting('publisher_name')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Chief Editor</label>
                <input type="text" name="chief_editor" class="form-control" value="<?= htmlspecialchars(get_setting('chief_editor')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Office Address</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars(get_setting('address')) ?></textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Telephone Line</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars(get_setting('phone')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Hotline / Mobile</label>
                <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars(get_setting('mobile')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Contact Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars(get_setting('email')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Office Hours</label>
                <input type="text" name="office_time" class="form-control" value="<?= htmlspecialchars(get_setting('office_time')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Google Maps Embed Location URL</label>
                <input type="text" name="google_map" class="form-control" value="<?= htmlspecialchars(get_setting('google_map')) ?>">
            </div>
        </div>
    </div>

    <!-- Card 3: Social Links & Footer Text -->
    <div class="card p-4 shadow-sm border mb-4">
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-share me-2"></i> Social Profiles & Footer</h5>
        
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Facebook Page URL</label>
                <input type="text" name="social_facebook" class="form-control" value="<?= htmlspecialchars(get_setting('social_facebook', 'https://www.facebook.com/facebook')) ?>" placeholder="https://www.facebook.com/yourpage">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Twitter (X) Profile</label>
                <input type="text" name="social_twitter" class="form-control" value="<?= htmlspecialchars(get_setting('social_twitter')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">YouTube Channel</label>
                <input type="text" name="social_youtube" class="form-control" value="<?= htmlspecialchars(get_setting('social_youtube')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Footer About / Bio Text</label>
                <textarea name="footer_text" class="form-control" rows="2"><?= htmlspecialchars(get_setting('footer_text')) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Footer Copyright Line</label>
                <input type="text" name="footer_copyright" class="form-control" value="<?= htmlspecialchars(get_setting('footer_copyright')) ?>">
            </div>
        </div>
    </div>

    <!-- Card 3.5: Facebook Page Sidebar Widget Configuration -->
    <div class="card p-4 shadow-sm border mb-4 bg-white border-start border-4 border-primary">
        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-facebook me-2"></i> Right Sidebar Facebook Page Widget (ফেসবুক পেজ উইজেট)</h5>
        
        <div class="row g-3">
            <div class="col-md-12">
                <div class="form-check form-switch card p-3 bg-light border">
                    <input class="form-check-input" type="checkbox" name="fb_widget_enabled" id="fb_widget_enabled" value="1" <?= get_setting('fb_widget_enabled', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="fb_widget_enabled">
                        <i class="bi bi-facebook text-primary me-1"></i> Display Facebook Page Widget on Right Sidebar
                    </label>
                    <small class="text-muted d-block mt-1">ওয়েবসাইটের ডানপাশের সাইডবারে ফেসবুক পেজ বক্স/উইজেট প্রদর্শন করতে টিকেল মার্ক রাখুন।</small>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Facebook Page Link URL *</label>
                <?php $fb_link = get_setting('fb_page_url', get_setting('social_facebook', 'https://www.facebook.com/facebook')); ?>
                <input type="text" name="fb_page_url" class="form-control border-primary" value="<?= htmlspecialchars($fb_link) ?>" placeholder="https://www.facebook.com/yourpage">
                <small class="text-muted">আপনার অফিসিয়াল ফেসবুক পেজের সম্পূর্ণ লিংক দিন।</small>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Widget Height (Pixels)</label>
                <input type="number" name="fb_widget_height" class="form-control" value="<?= htmlspecialchars(get_setting('fb_widget_height', '500')) ?>" placeholder="500" min="150" max="1000">
                <small class="text-muted">উচ্চতা (কম/বেশি করতে পারেন, যেমন: 350, 500, 700)</small>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Widget Width (Pixels)</label>
                <input type="number" name="fb_widget_width" class="form-control" value="<?= htmlspecialchars(get_setting('fb_widget_width', '0')) ?>" placeholder="0 (Responsive Auto)" min="0" max="600">
                <small class="text-muted">0 দিলে সাইডবারের প্রস্থ অনুযায়ী অটো ফিট হবে।</small>
            </div>
        </div>
    </div>

    <!-- Card 4: Article Social Media Share Options & Typography -->
    <div class="card p-4 shadow-sm border mb-4">
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-type me-2 text-danger"></i> Article Reading & Social Share Settings</h5>
        
        <div class="mb-4">
            <div class="form-check form-switch card p-3 bg-light border">
                <input class="form-check-input" type="checkbox" name="enable_drop_cap" id="enable_drop_cap" value="1" <?= get_setting('enable_drop_cap', '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="enable_drop_cap"><i class="bi bi-fonts text-danger me-2"></i> Enable Paragraph First-Letter Drop Cap</label>
                <small class="text-muted d-block mt-1">When disabled (recommended for Bengali script), paragraph text starts normally with uniform font sizes.</small>
            </div>
        </div>

        <p class="text-muted small">Enable or disable specific social sharing channels on news article detail pages:</p>
        
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_facebook" id="sf_fb" value="1" <?= get_setting('share_facebook', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_fb"><i class="bi bi-facebook text-primary me-2"></i> Facebook Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_twitter" id="sf_tw" value="1" <?= get_setting('share_twitter', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_tw"><i class="bi bi-twitter-x text-dark me-2"></i> Twitter / X Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_whatsapp" id="sf_wa" value="1" <?= get_setting('share_whatsapp', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_wa"><i class="bi bi-whatsapp text-success me-2"></i> WhatsApp Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_linkedin" id="sf_li" value="1" <?= get_setting('share_linkedin', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_li"><i class="bi bi-linkedin text-info me-2"></i> LinkedIn Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_telegram" id="sf_tg" value="1" <?= get_setting('share_telegram', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_tg"><i class="bi bi-telegram text-primary me-2"></i> Telegram Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_pinterest" id="sf_pin" value="1" <?= get_setting('share_pinterest', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_pin"><i class="bi bi-pinterest text-danger me-2"></i> Pinterest Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_email" id="sf_em" value="1" <?= get_setting('share_email', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_em"><i class="bi bi-envelope-fill text-secondary me-2"></i> Email Share</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_copy" id="sf_copy" value="1" <?= get_setting('share_copy', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_copy"><i class="bi bi-link-45deg text-dark me-2"></i> Copy Article Link</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch card p-3 bg-light border-0">
                    <input class="form-check-input" type="checkbox" name="share_print" id="sf_print" value="1" <?= get_setting('share_print', '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="sf_print"><i class="bi bi-printer text-dark me-2"></i> Print Article Button</label>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <button type="submit" class="btn btn-danger btn-lg px-5 fw-bold shadow"><i class="bi bi-save me-2"></i> Save All Settings</button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
