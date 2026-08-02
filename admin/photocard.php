<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Ultra-fast single post lookup (Scale-optimized for millions of posts)
$selected_post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$selected_post = null;

if ($selected_post_id > 0) {
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$selected_post_id]);
    $selected_post = $stmt->fetch();
}

if (!$selected_post) {
    // Grab the single latest published post if no post_id provided
    $stmt = $db->query("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.id DESC LIMIT 1");
    $selected_post = $stmt->fetch();
}

// Fallback defaults if no post or empty values
$pc_title = $selected_post['title'] ?? 'এখানে আপনার খবরের আকর্ষণীয় শিরোনামটি লিখুন';
$pc_category = $selected_post['category_name'] ?? 'সর্বশেষ সংবাদ';
$pc_date = !empty($selected_post['publish_date']) ? date('d F, Y', strtotime($selected_post['publish_date'])) : date('d F, Y');
$pc_author = !empty($selected_post['reporter_name']) ? $selected_post['reporter_name'] : 'নিজস্ব প্রতিবেদক';
$pc_image = !empty($selected_post['featured_image']) ? get_media_url($selected_post['featured_image']) : 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80';
$pc_site_name = get_setting('site_name', 'babuganjlive.com');
$pc_site_slogan = get_setting('site_tagline', 'সত্যের সন্ধানে অবিরত • দৈনিক খবর');

// Fetch website logo automatically from settings
$raw_logo_setting = get_setting('logo_url', '');
if (empty($raw_logo_setting)) $raw_logo_setting = get_setting('site_logo', '');
if (empty($raw_logo_setting)) $raw_logo_setting = get_setting('header_logo', '');
if (empty($raw_logo_setting)) $raw_logo_setting = get_setting('print_logo_url', '');
$pc_logo_img = !empty($raw_logo_setting) ? get_media_url($raw_logo_setting) : '';
?>

<!-- HTML2Canvas CDN for Instant PNG Download -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-card-image text-danger me-2"></i>Photocard Manager & Generator</h3>
        <p class="text-muted small mb-0">Create, customize, and download high-resolution news photocards for Facebook, Instagram, and Twitter.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="posts.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to All Posts</a>
        <button id="downloadCardBtn" class="btn btn-danger btn-sm fw-bold px-3"><i class="bi bi-download me-1"></i> Download Photocard (PNG)</button>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Settings & Customizer Controls -->
    <div class="col-lg-5">
        <!-- Card 1: Article Selector with Instant AJAX Search (Optimized for Millions of Posts) -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold text-dark mb-0"><i class="bi bi-search me-1 text-danger"></i> Find & Select News Article</label>
                <span class="badge bg-danger"><i class="bi bi-lightning-charge-fill me-1"></i>Super Fast Search</span>
            </div>
            
            <div class="position-relative mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-newspaper text-muted"></i></span>
                    <input type="text" id="ajaxPostSearchInput" class="form-control form-control-sm fw-semibold" placeholder="🔍 টাইপ করে খবর খুঁজুন (Search news title)..." onkeyup="searchPostsAjax(this.value)" onfocus="searchPostsAjax(this.value)">
                    <button class="btn btn-outline-secondary" type="button" onclick="searchPostsAjax('')" title="সাম্প্রতিক খবর দেখুন"><i class="bi bi-clock-history me-1"></i> Recent</button>
                </div>
                <!-- Floating Results Dropdown -->
                <div id="ajaxSearchResultsBox" class="position-absolute start-0 end-0 bg-white shadow-lg rounded border mt-1 p-2 d-none" style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                </div>
            </div>
            <div class="p-2 bg-light rounded border small d-flex align-items-center justify-content-between">
                <span class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> নির্বাচিত খবর:</span>
                <strong id="selectedNewsBadge" class="text-dark text-truncate ms-2" style="max-width: 260px;"><?= htmlspecialchars($pc_title) ?></strong>
            </div>
        </div>

        <!-- Card 2: Prebuilt Template Selector -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <label class="form-label fw-bold text-dark mb-2"><i class="bi bi-palette-fill me-1 text-danger"></i> Choose Photocard Preset Design</label>
            <div class="row g-2" id="presetButtons">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 fw-bold active preset-btn" onclick="applyPreset('preset1', this)">
                        <i class="bi bi-layout-text-window me-1"></i> Preset 1: Modern Red
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-dark btn-sm w-100 fw-bold preset-btn" onclick="applyPreset('preset2', this)">
                        <i class="bi bi-moon-stars me-1"></i> Preset 2: Dark Flash
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-warning btn-sm w-100 fw-bold text-dark preset-btn" onclick="applyPreset('preset3', this)">
                        <i class="bi bi-chat-square-quote me-1"></i> Preset 3: Editorial Quote
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold preset-btn" onclick="applyPreset('preset4', this)">
                        <i class="bi bi-instagram me-1"></i> Preset 4: Square 1080x1080
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-success btn-sm w-100 fw-bold preset-btn" onclick="applyPreset('preset5', this)">
                        <i class="bi bi-journal-text me-1"></i> Preset 5: Classic Newspaper
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 fw-bold preset-btn" onclick="applyPreset('preset6', this)" style="border-color: #d97706; color: #b45309;">
                        <i class="bi bi-gem me-1"></i> Preset 6: Gold Luxe
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 fw-bold preset-btn" onclick="applyPreset('preset7', this)">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Preset 7: Breaking Glow
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-info btn-sm w-100 fw-bold text-dark preset-btn" onclick="applyPreset('preset8', this)">
                        <i class="bi bi-card-heading me-1"></i> Preset 8: Minimal Dual
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 3: Customizer Fields -->
        <div class="card border-0 shadow-sm p-3 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-sliders me-1"></i> Customize Content & Typography</h6>
            
            <div class="mb-3">
                <label class="form-label small fw-bold">Headline Title (শিরোনাম)</label>
                <textarea id="inputTitle" class="form-control form-control-sm fw-bold" rows="3" oninput="updateCardContent()"><?= htmlspecialchars($pc_title) ?></textarea>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">Category Tag</label>
                    <input type="text" id="inputCategory" class="form-control form-control-sm" value="<?= htmlspecialchars($pc_category) ?>" oninput="updateCardContent()">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Reporter / Bureau</label>
                    <input type="text" id="inputAuthor" class="form-control form-control-sm" value="<?= htmlspecialchars($pc_author) ?>" oninput="updateCardContent()">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">Publish Date</label>
                    <input type="text" id="inputDate" class="form-control form-control-sm" value="<?= htmlspecialchars($pc_date) ?>" oninput="updateCardContent()">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Website Name (Text Fallback)</label>
                    <input type="text" id="inputSiteLogo" class="form-control form-control-sm" value="<?= htmlspecialchars($pc_site_name) ?>" oninput="updateCardContent()">
                </div>
            </div>

            <!-- Website Logo Branding Customizer Box -->
            <div class="p-3 bg-light rounded border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label small fw-bold text-danger mb-0"><i class="bi bi-card-heading me-1"></i> Website Logo Branding Controls</label>
                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Auto Settings Sync</span>
                </div>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label style-label small fw-semibold mb-1">Website Logo Image URL</label>
                        <small class="text-muted" style="font-size: 0.72rem;">(ডিফল্ট ওয়েবসাইট লোগো)</small>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="inputLogoImgUrl" class="form-control" value="<?= htmlspecialchars($pc_logo_img) ?>" placeholder="https://domain.com/uploads/logo.png" oninput="updateCardContent()">
                        <button class="btn btn-outline-secondary" type="button" title="ওয়েবসাইট সেটিংসের লোগোতে ফেরত যান" onclick="document.getElementById('inputLogoImgUrl').value='<?= htmlspecialchars($pc_logo_img) ?>'; updateCardContent();"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                    </div>
                    <?php if (empty($pc_logo_img)): ?>
                        <div class="form-text text-warning small mt-1"><i class="bi bi-exclamation-triangle me-1"></i> ওয়েবসাইট সেটিংসে লোগো সেট করা নেই। আপনি চাইলে উপরে লিংক দিতে পারেন বা Admin Settings থেকে Logo আপলোড করতে পারেন।</div>
                    <?php else: ?>
                        <div class="form-text text-success small mt-1"><i class="bi bi-check2-circle me-1"></i> ওয়েবসাইট সেটিংস থেকে আপনার ব্র্যান্ড লোগো স্বয়ংক্রিয়ভাবে লোড হয়েছে।</div>
                    <?php endif; ?>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label style-label small fw-semibold">Brand Display Mode</label>
                        <select id="inputLogoMode" class="form-select form-select-sm fw-semibold" onchange="updateCardContent()">
                            <option value="image_only" <?= !empty($pc_logo_img) ? 'selected' : '' ?>>🖼️ Logo Image Only</option>
                            <option value="image_text">🖼️ Logo Image + Text</option>
                            <option value="text_only" <?= empty($pc_logo_img) ? 'selected' : '' ?>>🔤 Brand Text Only</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label style-label small fw-semibold">Logo Position</label>
                        <select id="inputLogoPos" class="form-select form-select-sm fw-semibold" onchange="updateCardContent()">
                            <option value="top_left" selected>↖️ Top Left (ডিফল্ট)</option>
                            <option value="top_right">↗️ Top Right</option>
                            <option value="top_center">⬆️ Top Center</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label style-label small fw-semibold mb-0">Logo Height / Scale</label>
                        <span id="logoSizeVal" class="badge bg-dark">45px</span>
                    </div>
                    <input type="range" id="inputLogoSize" class="form-range" min="20" max="100" value="45" oninput="document.getElementById('logoSizeVal').innerText = this.value + 'px'; updateCardContent();">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Image URL (ছবি লিংক)</label>
                <input type="text" id="inputImgUrl" class="form-control form-control-sm" value="<?= htmlspecialchars($pc_image) ?>" oninput="updateCardContent()">
            </div>
        </div>
    </div>

    <!-- Right Column: Live Photocard Interactive Preview Stage -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm p-4 text-center bg-light">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold text-dark small"><i class="bi bi-eye-fill text-danger me-1"></i> Live Photocard Render Box</span>
                <span class="badge bg-secondary">Scale: 100% High-Res</span>
            </div>

            <div class="d-flex justify-content-center overflow-auto p-2">
                <!-- PHOTO CARD RENDER CONTAINER -->
                <div id="photocardStage" class="photocard-container preset1" style="width: 600px; min-height: 600px; background: #ffffff; color: #000; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); font-family: 'SolaimanLipi', 'Noto Sans Bengali', sans-serif; text-align: left; overflow: hidden; border-radius: 4px;">
                    
                    <!-- Preset 1: Modern Red Banner Layout -->
                    <div class="card-inner-preset1">
                        <div id="headerBar1" class="p-3 bg-danger text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #e61e25 0%, #991b1b 100%);">
                            <div>
                                <div id="cardBrandBox1" class="d-flex align-items-center text-white">
                                    <?php if (!empty($pc_logo_img)): ?>
                                        <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 45px; width: auto; object-fit: contain;" crossorigin="anonymous">
                                    <?php else: ?>
                                        <h3 id="cardBrandText1" class="fw-extrabold mb-0 fs-4 tracking-tight" style="font-family: sans-serif; text-transform: lowercase; color: #fff;"><?= htmlspecialchars($pc_site_name) ?></h3>
                                    <?php endif; ?>
                                </div>
                                <small id="cardSloganText1" class="opacity-80" style="font-size: 0.72rem;"><?= htmlspecialchars($pc_site_slogan) ?></small>
                            </div>
                            <span id="cardCategory1" class="badge bg-white text-danger fw-extrabold px-3 py-2 text-uppercase fs-6"><?= htmlspecialchars($pc_category) ?></span>
                        </div>

                        <div class="card-img-box position-relative" style="height: 320px; overflow: hidden; background: #000;">
                            <img id="cardImg1" src="<?= htmlspecialchars($pc_image) ?>" class="w-100 h-100 object-fit-cover" alt="" crossorigin="anonymous">
                            <div class="position-absolute bottom-0 start-0 end-0 p-2 text-white bg-dark bg-opacity-75 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                                <span><i class="bi bi-person-fill me-1 text-danger"></i> <span id="cardAuthor1"><?= htmlspecialchars($pc_author) ?></span></span>
                                <span><i class="bi bi-calendar3 me-1 text-danger"></i> <span id="cardDate1"><?= htmlspecialchars($pc_date) ?></span></span>
                            </div>
                        </div>

                        <div class="p-4 bg-white" style="border-top: 4px solid #e61e25;">
                            <h2 id="cardTitle1" class="fw-bold text-dark mb-3" style="font-size: 1.65rem; line-height: 1.4; color: #0f172a;">
                                <?= htmlspecialchars($pc_title) ?>
                            </h2>
                            <div class="d-flex justify-content-between align-items-center border-top pt-3 text-muted" style="font-size: 0.82rem;">
                                <span class="fw-bold text-danger"><i class="bi bi-globe me-1"></i> <span id="cardFooterDomain1">www.<?= htmlspecialchars($pc_site_name) ?></span></span>
                                <span>সর্বশেষ সংবাদের বিশ্বস্ত ঠিকানা</span>
                            </div>
                        </div>
                    </div>

                    <!-- Preset 2: Dark Flash Layout -->
                    <div class="card-inner-preset2 d-none" style="background: #0f172a; color: #fff; min-height: 600px; padding: 24px; position: relative; border: 6px solid #e61e25;">
                        <div id="headerBar2" class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2" style="border-color: #334155 !important;">
                            <span id="cardCategory2" class="badge bg-danger px-3 py-2 text-uppercase fs-6"><?= htmlspecialchars($pc_category) ?></span>
                            <div id="cardBrandBox2" class="d-flex align-items-center">
                                <?php if (!empty($pc_logo_img)): ?>
                                    <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 40px; width: auto;" crossorigin="anonymous">
                                <?php else: ?>
                                    <span id="cardBrandText2" class="fw-bold text-warning fs-5"><?= htmlspecialchars($pc_site_name) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="position-relative mb-3 rounded overflow-hidden" style="height: 300px; border: 2px solid #334155;">
                            <img id="cardImg2" src="<?= htmlspecialchars($pc_image) ?>" class="w-100 h-100 object-fit-cover" alt="" crossorigin="anonymous">
                        </div>
                        <h2 id="cardTitle2" class="fw-bold text-white mb-4" style="font-size: 1.7rem; line-height: 1.35; color: #f8fafc;">
                            <?= htmlspecialchars($pc_title) ?>
                        </h2>
                        <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-dark d-flex justify-content-between align-items-center border-top border-secondary" style="font-size: 0.82rem; color: #94a3b8;">
                            <span><i class="bi bi-person me-1 text-danger"></i> <span id="cardAuthor2"><?= htmlspecialchars($pc_author) ?></span></span>
                            <span><i class="bi bi-calendar3 me-1 text-danger"></i> <span id="cardDate2"><?= htmlspecialchars($pc_date) ?></span></span>
                        </div>
                    </div>

                    <!-- Preset 3: Editorial Quote Layout -->
                    <div class="card-inner-preset3 d-none" style="background: #fdfbf7; color: #1c1917; min-height: 600px; padding: 36px; border: 1px solid #e7e5e4;">
                        <div id="headerBar3" class="text-center mb-4 border-bottom pb-3">
                            <div id="cardBrandBox3" class="d-flex justify-content-center mb-1">
                                <?php if (!empty($pc_logo_img)): ?>
                                    <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 50px; width: auto;" crossorigin="anonymous">
                                <?php else: ?>
                                    <h3 id="cardBrandText3" class="fw-bold text-danger mb-0" style="font-family: serif;"><?= htmlspecialchars($pc_site_name) ?></h3>
                                <?php endif; ?>
                            </div>
                            <small id="cardCategory3" class="text-muted text-uppercase tracking-widest fw-bold" style="font-size: 0.75rem;"><?= htmlspecialchars($pc_category) ?></small>
                        </div>
                        <div class="text-center mb-3">
                            <i class="bi bi-quote text-danger opacity-25 display-1"></i>
                        </div>
                        <h2 id="cardTitle3" class="fw-bold text-dark text-center mb-4 px-2" style="font-family: serif; font-size: 1.75rem; line-height: 1.4; color: #1c1917;">
                            "<?= htmlspecialchars($pc_title) ?>"
                        </h2>
                        <div class="row align-items-center mt-4 pt-3 border-top">
                            <div class="col-4">
                                <img id="cardImg3" src="<?= htmlspecialchars($pc_image) ?>" class="rounded-circle object-fit-cover border border-danger p-1" style="width: 80px; height: 80px;" alt="" crossorigin="anonymous">
                            </div>
                            <div class="col-8 text-end">
                                <h6 id="cardAuthor3" class="fw-bold mb-0 text-dark"><?= htmlspecialchars($pc_author) ?></h6>
                                <small id="cardDate3" class="text-muted"><?= htmlspecialchars($pc_date) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Preset 4: Square 1080x1080 Social Layout -->
                    <div class="card-inner-preset4 d-none position-relative" style="width: 600px; height: 600px; background: #000; overflow: hidden;">
                        <img id="cardImg4" src="<?= htmlspecialchars($pc_image) ?>" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0 opacity-80" alt="" crossorigin="anonymous">
                        <div class="position-absolute top-0 start-0 end-0 bottom-0 p-4 d-flex flex-column justify-content-between" style="background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.95) 100%);">
                            <div id="headerBar4" class="d-flex justify-content-between align-items-center">
                                <span id="cardCategory4" class="badge bg-danger fs-6 text-uppercase px-3 py-2"><?= htmlspecialchars($pc_category) ?></span>
                                <div id="cardBrandBox4" class="d-flex align-items-center">
                                    <?php if (!empty($pc_logo_img)): ?>
                                        <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 45px; width: auto;" crossorigin="anonymous">
                                    <?php else: ?>
                                        <h4 id="cardBrandText4" class="fw-extrabold text-white mb-0"><?= htmlspecialchars($pc_site_name) ?></h4>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <h2 id="cardTitle4" class="fw-bold text-white mb-3" style="font-size: 1.8rem; line-height: 1.35; text-shadow: 0 2px 4px rgba(0,0,0,0.8);">
                                    <?= htmlspecialchars($pc_title) ?>
                                </h2>
                                <div class="d-flex justify-content-between align-items-center text-white-50 border-top border-secondary pt-3" style="font-size: 0.85rem;">
                                    <span><i class="bi bi-pen me-1 text-danger"></i> <span id="cardAuthor4"><?= htmlspecialchars($pc_author) ?></span></span>
                                    <span><i class="bi bi-clock me-1 text-danger"></i> <span id="cardDate4"><?= htmlspecialchars($pc_date) ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preset 5: Classic Newspaper Frontpage -->
                    <div class="card-inner-preset5 d-none" style="background: #fff; padding: 24px; border: 8px double #1e293b; color: #000; min-height: 600px;">
                        <div id="headerBar5" class="text-center border-bottom border-dark pb-2 mb-3">
                            <div id="cardBrandBox5" class="d-flex justify-content-center mb-2">
                                <?php if (!empty($pc_logo_img)): ?>
                                    <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 60px; width: auto;" crossorigin="anonymous">
                                <?php else: ?>
                                    <h1 id="cardBrandText5" class="fw-bold mb-0 text-uppercase" style="font-family: serif; letter-spacing: 2px; font-size: 2.2rem;"><?= htmlspecialchars($pc_site_name) ?></h1>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted border-top border-dark pt-1 mt-1">
                                <span id="cardDate5"><?= htmlspecialchars($pc_date) ?></span>
                                <span id="cardCategory5" class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($pc_category) ?></span>
                                <span>বিশেষ প্রতিবেদন</span>
                            </div>
                        </div>
                        <div class="mb-3 text-center">
                            <img id="cardImg5" src="<?= htmlspecialchars($pc_image) ?>" class="img-fluid border border-dark p-1" style="max-height: 260px; object-fit: cover; width: 100%;" alt="" crossorigin="anonymous">
                        </div>
                        <h2 id="cardTitle5" class="fw-bold text-dark mb-3 text-center" style="font-family: serif; font-size: 1.6rem; line-height: 1.35;">
                            <?= htmlspecialchars($pc_title) ?>
                        </h2>
                        <div class="border-top border-dark pt-2 d-flex justify-content-between align-items-center small text-muted">
                            <span>প্রতিবেদন: <strong id="cardAuthor5"><?= htmlspecialchars($pc_author) ?></strong></span>
                            <span>দৈনিক অনলাইন পত্রিকা</span>
                        </div>
                    </div>

                    <!-- Preset 6: Gold Luxe Special Edition -->
                    <div class="card-inner-preset6 d-none" style="background: linear-gradient(145deg, #18181b 0%, #09090b 100%); color: #f4f4f5; min-height: 600px; padding: 28px; border: 3px solid #d97706; position: relative;">
                        <div id="headerBar6" class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 2px solid #b45309;">
                            <span id="cardCategory6" class="badge text-dark fw-bold px-3 py-2 text-uppercase fs-6" style="background: linear-gradient(135deg, #fef08a 0%, #f59e0b 100%);"><?= htmlspecialchars($pc_category) ?></span>
                            <div id="cardBrandBox6" class="text-end">
                                <?php if (!empty($pc_logo_img)): ?>
                                    <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 45px; width: auto;" crossorigin="anonymous">
                                <?php else: ?>
                                    <h4 id="cardBrandText6" class="fw-extrabold mb-0" style="color: #fbbf24; font-family: serif; text-transform: uppercase; letter-spacing: 1px;"><?= htmlspecialchars($pc_site_name) ?></h4>
                                <?php endif; ?>
                                <small class="text-warning opacity-75 d-block" style="font-size: 0.68rem;">স্পেশাল গোল্ড লাক্স এডিশন</small>
                            </div>
                        </div>
                        <div class="position-relative mb-3 rounded overflow-hidden" style="height: 300px; border: 2px solid #b45309;">
                            <img id="cardImg6" src="<?= htmlspecialchars($pc_image) ?>" class="w-100 h-100 object-fit-cover" alt="" crossorigin="anonymous">
                            <div class="position-absolute top-0 end-0 m-2 px-2 py-1 bg-dark text-warning rounded border border-warning small fw-bold">
                                <i class="bi bi-gem me-1"></i> EXCLUSIVE
                            </div>
                        </div>
                        <h2 id="cardTitle6" class="fw-bold mb-4" style="font-size: 1.65rem; line-height: 1.38; color: #fef08a; text-shadow: 0 2px 4px rgba(0,0,0,0.9);">
                            <?= htmlspecialchars($pc_title) ?>
                        </h2>
                        <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-black d-flex justify-content-between align-items-center" style="font-size: 0.8rem; color: #fbbf24; border-top: 1px solid #b45309;">
                            <span><i class="bi bi-pen me-1"></i> <span id="cardAuthor6"><?= htmlspecialchars($pc_author) ?></span></span>
                            <span><i class="bi bi-calendar3 me-1"></i> <span id="cardDate6"><?= htmlspecialchars($pc_date) ?></span></span>
                        </div>
                    </div>

                    <!-- Preset 7: Breaking Flash Red Glow -->
                    <div class="card-inner-preset7 d-none" style="background: #000; color: #fff; min-height: 600px; padding: 0; position: relative;">
                        <!-- Glowing Header -->
                        <div id="headerBar7" class="p-3 bg-danger text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(90deg, #dc2626 0%, #991b1b 100%); box-shadow: 0 4px 15px rgba(220, 38, 38, 0.6);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark fw-extrabold px-3 py-2 text-uppercase fs-6 animate-pulse"><i class="bi bi-lightning-charge-fill me-1"></i> ব্রেকিং নিউজ</span>
                                <span id="cardCategory7" class="badge bg-black text-white px-2 py-1 small"><?= htmlspecialchars($pc_category) ?></span>
                            </div>
                            <div id="cardBrandBox7" class="d-flex align-items-center">
                                <?php if (!empty($pc_logo_img)): ?>
                                    <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 40px; width: auto;" crossorigin="anonymous">
                                <?php else: ?>
                                    <h4 id="cardBrandText7" class="fw-bold mb-0 text-white"><?= htmlspecialchars($pc_site_name) ?></h4>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Big Image -->
                        <div class="position-relative" style="height: 330px; overflow: hidden;">
                            <img id="cardImg7" src="<?= htmlspecialchars($pc_image) ?>" class="w-100 h-100 object-fit-cover" alt="" crossorigin="anonymous">
                            <div class="position-absolute top-0 start-0 end-0 bottom-0" style="background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.95) 100%);"></div>
                        </div>
                        <!-- Headline Overlay Box -->
                        <div class="p-4 position-relative" style="margin-top: -80px; z-index: 2;">
                            <div class="bg-dark p-3 rounded-3 border border-danger shadow-lg">
                                <h2 id="cardTitle7" class="fw-bold text-white mb-3" style="font-size: 1.65rem; line-height: 1.35; color: #fff;">
                                    <?= htmlspecialchars($pc_title) ?>
                                </h2>
                                <div class="d-flex justify-content-between align-items-center text-white-50 pt-2 border-top border-secondary" style="font-size: 0.8rem;">
                                    <span><i class="bi bi-person-fill text-danger me-1"></i> <span id="cardAuthor7"><?= htmlspecialchars($pc_author) ?></span></span>
                                    <span><i class="bi bi-clock-fill text-danger me-1"></i> <span id="cardDate7"><?= htmlspecialchars($pc_date) ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preset 8: Minimal Dual-Tone Card -->
                    <div class="card-inner-preset8 d-none" style="background: #f8fafc; color: #0f172a; min-height: 600px; padding: 28px; position: relative; border-left: 10px solid #ef4444;">
                        <div id="headerBar8" class="d-flex justify-content-between align-items-center mb-3">
                            <div id="cardBrandBox8" class="d-flex align-items-center">
                                <?php if (!empty($pc_logo_img)): ?>
                                    <img src="<?= htmlspecialchars($pc_logo_img) ?>" alt="Logo" style="max-height: 45px; width: auto;" crossorigin="anonymous">
                                <?php else: ?>
                                    <h3 id="cardBrandText8" class="fw-extrabold text-danger mb-0 fs-3" style="letter-spacing: -0.5px;"><?= htmlspecialchars($pc_site_name) ?></h3>
                                <?php endif; ?>
                            </div>
                            <span id="cardCategory8" class="badge bg-dark text-white px-3 py-2 text-uppercase fs-6 rounded-pill"><?= htmlspecialchars($pc_category) ?></span>
                        </div>
                        <div class="position-relative mb-4 rounded-4 overflow-hidden shadow" style="height: 290px;">
                            <img id="cardImg8" src="<?= htmlspecialchars($pc_image) ?>" class="w-100 h-100 object-fit-cover" alt="" crossorigin="anonymous">
                        </div>
                        <h2 id="cardTitle8" class="fw-bold text-slate-900 mb-4" style="font-size: 1.6rem; line-height: 1.4; color: #0f172a;">
                            <?= htmlspecialchars($pc_title) ?>
                        </h2>
                        <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-white border-top d-flex justify-content-between align-items-center" style="font-size: 0.82rem; color: #64748b;">
                            <span><i class="bi bi-person-circle text-danger me-1"></i> <span id="cardAuthor8"><?= htmlspecialchars($pc_author) ?></span></span>
                            <span><i class="bi bi-calendar2-check text-danger me-1"></i> <span id="cardDate8"><?= htmlspecialchars($pc_date) ?></span></span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="mt-3">
                <button id="downloadCardBtn2" class="btn btn-danger fw-bold px-4 py-2 shadow-sm"><i class="bi bi-file-earmark-arrow-down me-1"></i> Download High-Resolution Image</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPreset = 'preset1';
let searchDebounce = null;

function searchPostsAjax(query) {
    let resultsBox = document.getElementById('ajaxSearchResultsBox');
    if (!resultsBox) return;

    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(function() {
        resultsBox.classList.remove('d-none');
        resultsBox.innerHTML = '<div class="text-center py-3 text-muted small"><span class="spinner-border spinner-border-sm me-1 text-danger"></span> নিবন্ধ খোজা হচ্ছে...</div>';

        fetch('../api.php?action=search_photocard_posts&q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.results && data.results.length > 0) {
                    let html = '<div class="list-group list-group-flush small">';
                    data.results.forEach(post => {
                        let titleSafe = post.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        html += `
                            <a href="javascript:void(0)" onclick="selectArticleAjax(${post.id}, '${titleSafe}', '${post.category_name}', '${post.reporter_name}', '${post.publish_date}', '${post.featured_image}')" class="list-group-item list-group-item-action py-2 px-2 border-bottom d-flex align-items-center gap-2">
                                <img src="${post.featured_image}" class="rounded" style="width: 42px; height: 32px; object-fit: cover;" alt="">
                                <div class="text-truncate flex-grow-1">
                                    <div class="fw-bold text-dark text-truncate">${post.title}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">[${post.category_name}] • ${post.publish_date}</div>
                                </div>
                                <span class="btn btn-xs btn-danger px-2 py-0" style="font-size:0.7rem;">Select</span>
                            </a>
                        `;
                    });
                    html += '</div>';
                    resultsBox.innerHTML = html;
                } else {
                    resultsBox.innerHTML = '<div class="text-center py-3 text-muted small"><i class="bi bi-search me-1"></i> কোন খবর পাওয়া যায়নি।</div>';
                }
            })
            .catch(err => {
                resultsBox.innerHTML = '<div class="text-center py-2 text-danger small">সার্ভারে সংযোগ ত্রুটি।</div>';
            });
    }, 200);
}

function selectArticleAjax(id, title, cat, author, date, img) {
    document.getElementById('inputTitle').value = title;
    document.getElementById('inputCategory').value = cat;
    document.getElementById('inputAuthor').value = author;
    document.getElementById('inputDate').value = date;
    document.getElementById('inputImgUrl').value = img;

    let badge = document.getElementById('selectedNewsBadge');
    if (badge) badge.innerText = title.substring(0, 50) + '...';

    let resultsBox = document.getElementById('ajaxSearchResultsBox');
    if (resultsBox) resultsBox.classList.add('d-none');

    // Update URL quietly without full page reload
    if (window.history.pushState) {
        window.history.pushState(null, null, 'photocard.php?post_id=' + id);
    }

    updateCardContent();
}

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    let box = document.getElementById('ajaxSearchResultsBox');
    let input = document.getElementById('ajaxPostSearchInput');
    if (box && input && !box.contains(e.target) && !input.contains(e.target)) {
        box.classList.add('d-none');
    }
});

function applyPreset(presetName, btnElement) {
    currentPreset = presetName;
    
    // Toggle active button highlight
    let btns = document.querySelectorAll('.preset-btn');
    btns.forEach(b => b.classList.remove('active', 'btn-danger'));
    if (btnElement) {
        btnElement.classList.add('active', 'btn-danger');
    }

    // Toggle card inner visibility (support 8 presets)
    for (let i = 1; i <= 8; i++) {
        let el = document.querySelector('.card-inner-preset' + i);
        if (el) {
            if ('preset' + i === presetName) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        }
    }

    updateCardContent();
}

function updateCardContent() {
    let title = document.getElementById('inputTitle').value;
    let cat = document.getElementById('inputCategory').value;
    let author = document.getElementById('inputAuthor').value;
    let date = document.getElementById('inputDate').value;
    let siteName = document.getElementById('inputSiteLogo').value;
    let img = document.getElementById('inputImgUrl').value;
    let logoImg = document.getElementById('inputLogoImgUrl') ? document.getElementById('inputLogoImgUrl').value : '';
    let logoMode = document.getElementById('inputLogoMode') ? document.getElementById('inputLogoMode').value : 'image_only';
    let logoPos = document.getElementById('inputLogoPos') ? document.getElementById('inputLogoPos').value : 'top_left';
    let logoSize = document.getElementById('inputLogoSize') ? document.getElementById('inputLogoSize').value + 'px' : '45px';

    // Construct Brand Inner HTML
    let brandHtml = '';
    if (logoImg && (logoMode === 'image_only' || logoMode === 'image_text')) {
        brandHtml += `<img src="${logoImg}" alt="Logo" style="max-height: ${logoSize}; width: auto; object-fit: contain; vertical-align: middle; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));" crossorigin="anonymous">`;
    }
    if (logoMode === 'text_only' || logoMode === 'image_text' || !logoImg) {
        let margin = (logoImg && logoMode === 'image_text') ? ' ms-2' : '';
        brandHtml += `<span class="fw-extrabold ${margin}" style="font-family: sans-serif; letter-spacing: -0.5px;">${siteName}</span>`;
    }

    // Domain footer update
    let fDom = document.getElementById('cardFooterDomain1');
    if (fDom) fDom.innerText = 'www.' + siteName;

    for (let i = 1; i <= 8; i++) {
        let tEl = document.getElementById('cardTitle' + i);
        let cEl = document.getElementById('cardCategory' + i);
        let aEl = document.getElementById('cardAuthor' + i);
        let dEl = document.getElementById('cardDate' + i);
        let bBox = document.getElementById('cardBrandBox' + i);
        let iEl = document.getElementById('cardImg' + i);
        let hBar = document.getElementById('headerBar' + i);

        if (tEl) tEl.innerText = (i === 3) ? '"' + title + '"' : title;
        if (cEl) cEl.innerText = cat;
        if (aEl) aEl.innerText = author;
        if (dEl) dEl.innerText = date;
        if (iEl && img) iEl.src = img;
        if (bBox) bBox.innerHTML = brandHtml;

        // Apply Logo Position styling to header bar if present
        if (hBar) {
            if (logoPos === 'top_right') {
                if (i === 3 || i === 5) {
                    hBar.style.justifyContent = 'flex-end';
                } else {
                    hBar.classList.add('flex-row-reverse');
                }
            } else if (logoPos === 'top_center') {
                hBar.classList.remove('flex-row-reverse');
                hBar.style.justifyContent = 'center';
            } else {
                // top_left (default)
                hBar.classList.remove('flex-row-reverse');
                hBar.style.justifyContent = (i === 3 || i === 5) ? 'center' : 'space-between';
            }
        }
    }
}

function downloadPhotocard() {
    let stage = document.getElementById('photocardStage');
    if (!stage) return;

    let btn1 = document.getElementById('downloadCardBtn');
    let btn2 = document.getElementById('downloadCardBtn2');
    btn1.disabled = true;
    btn2.disabled = true;
    btn1.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Rendering...';
    btn2.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Rendering...';

    html2canvas(stage, {
        scale: 2, // High resolution output
        useCORS: true,
        allowTaint: true,
        backgroundColor: null
    }).then(canvas => {
        let link = document.createElement('a');
        let filename = 'photocard-' + Date.now() + '.png';
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();

        btn1.disabled = false;
        btn2.disabled = false;
        btn1.innerHTML = '<i class="bi bi-download me-1"></i> Download Photocard (PNG)';
        btn2.innerHTML = '<i class="bi bi-file-earmark-arrow-down me-1"></i> Download High-Resolution Image';
    }).catch(err => {
        alert('Failed to generate image download: ' + err.message);
        btn1.disabled = false;
        btn2.disabled = false;
        btn1.innerHTML = '<i class="bi bi-download me-1"></i> Download Photocard (PNG)';
        btn2.innerHTML = '<i class="bi bi-file-earmark-arrow-down me-1"></i> Download High-Resolution Image';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('downloadCardBtn').addEventListener('click', downloadPhotocard);
    document.getElementById('downloadCardBtn2').addEventListener('click', downloadPhotocard);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

