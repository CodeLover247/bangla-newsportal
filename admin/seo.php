<?php
require_once __DIR__ . '/header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seo_fields = ['site_title', 'meta_description', 'meta_keywords', 'google_verification', 'bing_verification', 'google_analytics', 'facebook_pixel'];
    foreach ($seo_fields as $f) {
        if (isset($_POST[$f])) {
            set_setting($f, trim($_POST[$f]));
        }
    }
    $msg = "SEO Settings Updated Successfully!";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">SEO & Analytics Settings</h3>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card p-4 shadow-sm border" style="max-width: 800px;">
    <form action="seo.php" method="POST" class="row g-3">
        <div class="col-12">
            <label class="form-label fw-bold">Meta Title Tag</label>
            <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars(get_setting('site_title')) ?>">
        </div>

        <div class="col-12">
            <label class="form-label fw-bold">Meta Description</label>
            <textarea name="meta_description" class="form-control" rows="3"><?= htmlspecialchars(get_setting('meta_description')) ?></textarea>
        </div>

        <div class="col-12">
            <label class="form-label fw-bold">Meta Keywords</label>
            <input type="text" name="meta_keywords" class="form-control" value="<?= htmlspecialchars(get_setting('meta_keywords')) ?>">
        </div>

        <hr class="my-4">

        <div class="col-md-6">
            <label class="form-label fw-semibold">Google Webmaster Verification Code</label>
            <input type="text" name="google_verification" class="form-control" value="<?= htmlspecialchars(get_setting('google_verification')) ?>" placeholder="google-site-verification-code">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Bing Webmaster Code</label>
            <input type="text" name="bing_verification" class="form-control" value="<?= htmlspecialchars(get_setting('bing_verification')) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Google Analytics Tracking ID (GA4)</label>
            <input type="text" name="google_analytics" class="form-control" value="<?= htmlspecialchars(get_setting('google_analytics')) ?>" placeholder="G-XXXXXXXXXX">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Facebook Pixel ID</label>
            <input type="text" name="facebook_pixel" class="form-control" value="<?= htmlspecialchars(get_setting('facebook_pixel')) ?>">
        </div>

        <div class="col-12 mt-4">
            <button type="submit" class="btn btn-danger btn-lg px-4 fw-bold">Save SEO Settings</button>
            <a href="../sitemap.php" target="_blank" class="btn btn-outline-secondary btn-lg ms-2"><i class="bi bi-box-arrow-up-right me-1"></i> View Dynamic Sitemap.xml</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
