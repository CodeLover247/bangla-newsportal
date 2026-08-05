<?php
require_once __DIR__ . '/functions.php';

$editor_name = trim(get_setting('editor_name', ''));
$publisher_name = trim(get_setting('publisher_name', ''));
$chief_editor = trim(get_setting('chief_editor', ''));
$address = trim(get_setting('address', ''));
$phone = trim(get_setting('phone', ''));
$email = trim(get_setting('email', ''));
$footer_text = trim(get_setting('footer_text', ''));
$copyright = trim(get_setting('footer_copyright', get_setting('copyright', '© ' . date('Y') . ' ' . get_setting('site_name', 'News Portal'))));
$categories = get_categories(0);

$fb = trim(get_setting('social_facebook', get_setting('facebook', '')));
$tw = trim(get_setting('social_twitter', get_setting('twitter', '')));
$yt = trim(get_setting('social_youtube', get_setting('youtube', '')));
$tg = trim(get_setting('telegram', ''));

$site_name = get_setting('site_name', 'DAILY HORIZON');
$footer_preset = get_setting('footer_layout_preset', 'standard');
$footer_logo_url = get_setting('footer_logo_url', get_setting('logo_url', ''));
$footer_logo_h = (int)get_setting('footer_logo_height', '60');
if ($footer_logo_h < 20 || $footer_logo_h > 250) $footer_logo_h = 60;
$footer_logo_pos = get_setting('footer_logo_position', 'left');
$align_class = ($footer_logo_pos === 'center') ? 'text-center' : (($footer_logo_pos === 'right') ? 'text-end' : 'text-start');
?>

<?= render_ad('footer_top', 'container my-4 no-print') ?>

<footer class="site-footer no-print">
    <div class="container">
        <?php if ($footer_preset === 'centered'): ?>
            <!-- PRESET 2: Minimalist Centered Brand Footer -->
            <div class="text-center py-4 border-bottom border-secondary mb-4">
                <a href="index.php" class="d-inline-block text-decoration-none mb-3">
                    <?php if (!empty($footer_logo_url)): ?>
                        <img src="<?= htmlspecialchars($footer_logo_url) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="img-fluid" style="max-height: <?= $footer_logo_h ?>px;">
                    <?php else: ?>
                        <h2 class="font-serif fw-bold text-white mb-0"><?= htmlspecialchars($site_name) ?></h2>
                    <?php endif; ?>
                </a>
                <?php if (!empty($footer_text)): ?>
                    <p class="small text-slate-400 max-w-xl mx-auto mb-3"><?= htmlspecialchars($footer_text) ?></p>
                <?php endif; ?>
                <div class="d-flex flex-wrap justify-content-center gap-3 small mb-4">
                    <a href="index.php" class="text-light text-decoration-none hover-red"><?= __('প্রচ্ছদ', 'Home') ?></a>
                    <?php foreach (array_slice($categories, 0, 6) as $c): ?>
                        <a href="category.php?slug=<?= $c['slug'] ?>" class="text-light text-decoration-none hover-red"><?= htmlspecialchars(get_category_display_name($c['name'])) ?></a>
                    <?php endforeach; ?>
                    <a href="contact.php" class="text-light text-decoration-none hover-red"><?= __('যোগাযোগ', 'Contact') ?></a>
                </div>
                <?php if ((!empty($fb) && $fb !== '#') || (!empty($tw) && $tw !== '#') || (!empty($yt) && $yt !== '#') || (!empty($tg) && $tg !== '#')): ?>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <?php if (!empty($fb) && $fb !== '#'): ?><a href="<?= htmlspecialchars($fb) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-facebook"></i></a><?php endif; ?>
                        <?php if (!empty($tw) && $tw !== '#'): ?><a href="<?= htmlspecialchars($tw) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
                        <?php if (!empty($yt) && $yt !== '#'): ?><a href="<?= htmlspecialchars($yt) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-youtube"></i></a><?php endif; ?>
                        <?php if (!empty($tg) && $tg !== '#'): ?><a href="<?= htmlspecialchars($tg) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-telegram"></i></a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center small text-muted">
                <?= htmlspecialchars($copyright) ?> &bull; Powered & Maintained by <strong class="text-white">HosterCube Ltd</strong>
            </div>

        <?php elseif ($footer_preset === 'newspaper_broad'): ?>
            <!-- PRESET 3: Broad Editorial Board Footer -->
            <div class="row g-4 pb-4">
                <?php if (!empty($chief_editor) || !empty($editor_name) || !empty($publisher_name) || !empty($footer_text)): ?>
                    <div class="col-lg-4 border-end border-secondary pe-lg-4">
                        <h5 class="fw-bold text-white border-bottom border-danger pb-2 mb-3"><i class="bi bi-journal-text me-2 text-danger"></i> <?= __('সম্পাদকীয় ও প্রকাশক', 'Editorial & Publisher') ?></h5>
                        <?php if (!empty($chief_editor)): ?>
                            <div class="small text-slate-300 mb-2"><strong><?= __('প্রধান সম্পাদক:', 'Editor-in-Chief:') ?></strong> <?= htmlspecialchars($chief_editor) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($editor_name)): ?>
                            <div class="small text-slate-300 mb-2"><strong><?= __('সম্পাদক:', 'Editor:') ?></strong> <?= htmlspecialchars($editor_name) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($publisher_name)): ?>
                            <div class="small text-slate-300 mb-3"><strong><?= __('প্রকাশক:', 'Publisher:') ?></strong> <?= htmlspecialchars($publisher_name) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($footer_text)): ?>
                            <div class="small text-muted mb-3"><?= htmlspecialchars($footer_text) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="col-lg-5 px-lg-4">
                    <h5 class="fw-bold text-white border-bottom border-danger pb-2 mb-3"><i class="bi bi-grid-fill me-2 text-danger"></i> <?= __('বিভাগসমূহ', 'Categories') ?></h5>
                    <div class="row g-2 small">
                        <?php foreach (array_slice($categories, 0, 8) as $c): ?>
                            <div class="col-6 mb-2">
                                <a href="category.php?slug=<?= $c['slug'] ?>" class="text-slate-300 text-decoration-none"><i class="bi bi-arrow-right-short text-danger"></i> <?= htmlspecialchars(get_category_display_name($c['name'])) ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-3 ps-lg-4">
                    <h5 class="fw-bold text-white border-bottom border-danger pb-2 mb-3"><i class="bi bi-geo-alt-fill me-2 text-danger"></i> <?= __('কার্যালয়', 'Registered Office') ?></h5>
                    <?php if (!empty($address)): ?><p class="small text-slate-300 mb-2"><?= htmlspecialchars($address) ?></p><?php endif; ?>
                    <?php if (!empty($phone)): ?><p class="small text-slate-300 mb-2"><i class="bi bi-telephone text-danger me-1"></i> <?= htmlspecialchars($phone) ?></p><?php endif; ?>
                    <?php if (!empty($email)): ?><p class="small text-slate-300 mb-3"><i class="bi bi-envelope text-danger me-1"></i> <?= htmlspecialchars($email) ?></p><?php endif; ?>
                    <a href="admin/login.php" class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-shield-lock me-1"></i> <?= __('এডমিন প্যানেল', 'Admin Portal') ?></a>
                </div>
            </div>
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center border-top border-secondary pt-3">
                <div><?= htmlspecialchars($copyright) ?></div>
                <div class="mt-2 mt-md-0">Powered & Maintained by <strong class="text-white">HosterCube Ltd</strong></div>
            </div>

        <?php elseif ($footer_preset === 'dark_modern'): ?>
            <!-- PRESET 4: Dark Modern Magazine Footer -->
            <div class="row g-4 pb-4">
                <div class="col-lg-5">
                    <div class="p-4 bg-dark rounded border border-secondary">
                        <a href="index.php" class="d-inline-block text-decoration-none mb-3">
                            <?php if (!empty($footer_logo_url)): ?>
                                <img src="<?= htmlspecialchars($footer_logo_url) ?>" alt="<?= htmlspecialchars($site_name) ?>" style="max-height: 50px;">
                            <?php else: ?>
                                <h3 class="font-serif fw-bold text-danger mb-0"><?= htmlspecialchars($site_name) ?></h3>
                            <?php endif; ?>
                        </a>
                        <?php if (!empty($footer_text)): ?>
                            <p class="small text-slate-400 mb-3"><?= htmlspecialchars($footer_text) ?></p>
                        <?php endif; ?>
                        <?php if ((!empty($fb) && $fb !== '#') || (!empty($tw) && $tw !== '#') || (!empty($yt) && $yt !== '#') || (!empty($tg) && $tg !== '#')): ?>
                            <div class="d-flex gap-2">
                                <?php if (!empty($fb) && $fb !== '#'): ?><a href="<?= htmlspecialchars($fb) ?>" target="_blank" class="btn btn-sm btn-danger"><i class="bi bi-facebook"></i></a><?php endif; ?>
                                <?php if (!empty($tw) && $tw !== '#'): ?><a href="<?= htmlspecialchars($tw) ?>" target="_blank" class="btn btn-sm btn-dark"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
                                <?php if (!empty($yt) && $yt !== '#'): ?><a href="<?= htmlspecialchars($yt) ?>" target="_blank" class="btn btn-sm btn-danger"><i class="bi bi-youtube"></i></a><?php endif; ?>
                                <?php if (!empty($tg) && $tg !== '#'): ?><a href="<?= htmlspecialchars($tg) ?>" target="_blank" class="btn btn-sm btn-info text-white"><i class="bi bi-telegram"></i></a><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <h5 class="fw-bold text-white mb-3"><?= __('দ্রুত লিঙ্ক', 'Quick Navigation') ?></h5>
                            <ul class="list-unstyled small mb-0">
                                <li class="mb-2"><a href="index.php" class="text-slate-300"><?= __('প্রচ্ছদ', 'Home Portal') ?></a></li>
                                <li class="mb-2"><a href="page.php?slug=about-us" class="text-slate-300"><?= __('আমাদের সম্পর্কে', 'About Us') ?></a></li>
                                <li class="mb-2"><a href="contact.php" class="text-slate-300"><?= __('যোগাযোগ', 'Contact Us') ?></a></li>
                                <li class="mb-2"><a href="e-paper.php" class="text-slate-300"><?= __('ই-পেপার', 'Daily E-Paper') ?></a></li>
                            </ul>
                        </div>
                        <div class="col-sm-6">
                            <h5 class="fw-bold text-white mb-3"><?= __('শর্তাবলী ও নীতিমালা', 'Legal & Terms') ?></h5>
                            <ul class="list-unstyled small mb-0">
                                <li class="mb-2"><a href="page.php?slug=privacy-policy" class="text-slate-300"><?= __('গোপনীয়তা নীতি', 'Privacy Policy') ?></a></li>
                                <li class="mb-2"><a href="page.php?slug=terms" class="text-slate-300"><?= __('ব্যবহারের শর্তাবলী', 'Terms of Service') ?></a></li>
                                <li class="mb-2"><a href="sitemap.php" class="text-slate-300"><?= __('সাইটম্যাপ', 'XML Sitemap') ?></a></li>
                                <li class="mb-2"><a href="admin/login.php" class="text-warning"><i class="bi bi-shield-lock me-1"></i> <?= __('এডমিন লগইন', 'Admin Login') ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center border-top border-secondary pt-3">
                <div><?= htmlspecialchars($copyright) ?></div>
                <div class="mt-2 mt-md-0">Powered & Maintained by <strong class="text-white">HosterCube Ltd</strong></div>
            </div>

        <?php else: ?>
            <!-- PRESET 1: Standard 4-Column Newspaper Footer -->
            <div class="row g-4">
                <!-- Brand Column -->
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3 <?= $align_class ?>">
                        <a href="index.php" class="d-inline-block text-decoration-none">
                            <?php if (!empty($footer_logo_url)): ?>
                                <img src="<?= htmlspecialchars($footer_logo_url) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="img-fluid footer-logo-img" style="max-height: <?= $footer_logo_h ?>px; width: auto; object-fit: contain;">
                            <?php else: ?>
                                <h3 class="font-serif fw-bold text-white mb-0"><?= htmlspecialchars($site_name) ?></h3>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php if (!empty($footer_text)): ?>
                        <p class="small text-slate-400 mb-3"><?= htmlspecialchars($footer_text) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($chief_editor)): ?>
                        <div class="small mb-1"><strong class="text-white"><?= __('প্রধান সম্পাদক:', 'Editor-in-Chief:') ?></strong> <?= htmlspecialchars($chief_editor) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($editor_name)): ?>
                        <div class="small mb-1"><strong class="text-white"><?= __('সম্পাদক:', 'Editor:') ?></strong> <?= htmlspecialchars($editor_name) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($publisher_name)): ?>
                        <div class="small mb-3"><strong class="text-white"><?= __('প্রকাশক:', 'Publisher:') ?></strong> <?= htmlspecialchars($publisher_name) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Categories -->
                <div class="col-lg-3 col-md-6">
                    <h5><?= __('বিভাগসমূহ', 'Categories') ?></h5>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach (array_slice($categories, 0, 6) as $c): 
                            $c_display = get_category_display_name($c['name']);
                        ?>
                            <li class="mb-2"><a href="category.php?slug=<?= $c['slug'] ?>"><i class="bi bi-chevron-right text-danger me-1"></i> <?= htmlspecialchars($c_display) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Important Links -->
                <div class="col-lg-2 col-md-6">
                    <h5><?= __('গুরুত্বপূর্ণ লিঙ্ক', 'Pages & Links') ?></h5>
                    <ul class="list-unstyled small mb-0">
                        <?php 
                        $footer_menus = get_menus('footer');
                        if (!empty($footer_menus)): 
                            foreach ($footer_menus as $fm):
                        ?>
                            <li class="mb-2"><a href="<?= htmlspecialchars($fm['url']) ?>" target="<?= htmlspecialchars($fm['target'] ?? '_self') ?>"><?= htmlspecialchars($fm['title']) ?></a></li>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                            <li class="mb-2"><a href="page.php?slug=about-us"><?= __('আমাদের সম্পর্কে', 'About Us') ?></a></li>
                            <li class="mb-2"><a href="page.php?slug=privacy-policy"><?= __('গোপনীয়তা নীতি', 'Privacy Policy') ?></a></li>
                            <li class="mb-2"><a href="page.php?slug=terms"><?= __('শর্তাবলী', 'Terms & Conditions') ?></a></li>
                            <li class="mb-2"><a href="contact.php"><?= __('যোগাযোগ', 'Contact Us') ?></a></li>
                        <?php endif; ?>
                        <li class="mb-2"><a href="sitemap.php"><?= __('সাইটম্যাপ', 'Sitemap XML') ?></a></li>
                        <li class="mb-2"><a href="admin/login.php" class="text-warning"><i class="bi bi-shield-lock me-1"></i> <?= __('এডমিন প্যানেল', 'Admin Login') ?></a></li>
                    </ul>
                </div>

                <!-- Contact Column -->
                <?php if (!empty($address) || !empty($phone) || !empty($email) || (!empty($fb) && $fb !== '#') || (!empty($tw) && $tw !== '#') || (!empty($yt) && $yt !== '#') || (!empty($tg) && $tg !== '#')): ?>
                    <div class="col-lg-3 col-md-6">
                        <h5><?= __('যোগাযোগের ঠিকানা', 'Contact Office') ?></h5>
                        <?php if (!empty($address)): ?><p class="small mb-2"><i class="bi bi-geo-alt text-danger me-2"></i> <?= htmlspecialchars($address) ?></p><?php endif; ?>
                        <?php if (!empty($phone)): ?><p class="small mb-2"><i class="bi bi-telephone text-danger me-2"></i> <?= htmlspecialchars($phone) ?></p><?php endif; ?>
                        <?php if (!empty($email)): ?><p class="small mb-3"><i class="bi bi-envelope text-danger me-2"></i> <?= htmlspecialchars($email) ?></p><?php endif; ?>
                        
                        <?php if ((!empty($fb) && $fb !== '#') || (!empty($tw) && $tw !== '#') || (!empty($yt) && $yt !== '#') || (!empty($tg) && $tg !== '#')): ?>
                            <div class="d-flex gap-2">
                                <?php if (!empty($fb) && $fb !== '#'): ?><a href="<?= htmlspecialchars($fb) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-facebook"></i></a><?php endif; ?>
                                <?php if (!empty($tw) && $tw !== '#'): ?><a href="<?= htmlspecialchars($tw) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
                                <?php if (!empty($yt) && $yt !== '#'): ?><a href="<?= htmlspecialchars($yt) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-youtube"></i></a><?php endif; ?>
                                <?php if (!empty($tg) && $tg !== '#'): ?><a href="<?= htmlspecialchars($tg) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-telegram"></i></a><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div><?= htmlspecialchars($copyright) ?></div>
                <div class="mt-2 mt-md-0">Powered & Maintained by <strong class="text-white">HosterCube Ltd</strong></div>
            </div>
        <?php endif; ?>
    </div>
</footer>

<!-- Bootstrap JS Bundle & Main Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<!-- Theme Toggle Script -->
<script>
function updateThemeUI() {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    const icon = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');
    if (icon && label) {
        if (currentTheme === 'dark') {
            icon.className = 'bi bi-sun-fill text-warning';
            label.textContent = 'Light';
        } else {
            icon.className = 'bi bi-moon-stars-fill text-warning';
            label.textContent = 'Dark';
        }
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeUI();
}

document.addEventListener('DOMContentLoaded', updateThemeUI);
</script>
<?php
$footer_custom_code = get_setting('footer_custom_code', get_setting('custom_footer_code', ''));
if (!empty($footer_custom_code)) {
    echo $footer_custom_code;
}
?>
</body>
</html>


