<?php
require_once __DIR__ . '/header.php';
require_role_permission('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seo_fields = ['site_title', 'meta_description', 'meta_keywords', 'google_verification', 'bing_verification', 'google_analytics', 'facebook_pixel', 'header_custom_code', 'footer_custom_code'];
    foreach ($seo_fields as $f) {
        if (isset($_POST[$f])) {
            set_setting($f, $_POST[$f]);
        }
    }
    $msg = "SEO Settings & Code Injections Updated Successfully!";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="bi bi-code-slash text-danger me-2"></i>SEO & Advanced Code Injection Settings</h3>
</div>

<?php if ($msg): ?><div class="alert alert-success fw-bold"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card p-4 shadow-sm border" style="max-width: 900px;">
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

        <h5 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-danger me-1"></i> Webmaster & Analytics Identifiers</h5>

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

        <hr class="my-4">

        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-code-fill text-danger me-1"></i> Header & Footer Raw Code Injections</h5>
        <p class="text-muted small mb-3">Google AdSense, Search Console verification meta tags, custom JavaScript, CSS, or schema JSON-LD code used for verification and analytics can be safely inserted here.</p>

        <!-- Header Custom Code Field -->
        <div class="col-12">
            <label class="form-label fw-bold text-dark">
                <i class="bi bi-box-arrow-in-up me-1 text-primary"></i> Head Code Injection ( dynamic &lt;head&gt;...&lt;/head&gt; )
            </label>
            <textarea name="header_custom_code" class="form-control font-monospace bg-dark text-warning small p-3" rows="6" placeholder="<script async src=&quot;https://pagead2.googlesyndication.com/...&quot;></script>&#10;<meta name=&quot;google-site-verification&quot; content=&quot;...&quot; />"><?= htmlspecialchars(get_setting('header_custom_code')) ?></textarea>
            <small class="text-muted">Injected directly inside the <code>&lt;head&gt;</code> element across all public pages.</small>
        </div>

        <!-- Footer Custom Code Field -->
        <div class="col-12 mt-3">
            <label class="form-label fw-bold text-dark">
                <i class="bi bi-box-arrow-in-down me-1 text-success"></i> Footer Code Injection ( before &lt;/body&gt; )
            </label>
            <textarea name="footer_custom_code" class="form-control font-monospace bg-dark text-info small p-3" rows="6" placeholder="<script>&#10;  // Custom analytics or live chat widget code here&#10;</script>"><?= htmlspecialchars(get_setting('footer_custom_code')) ?></textarea>
            <small class="text-muted">Injected before the closing <code>&lt;/body&gt;</code> tag on all public pages.</small>
        </div>

        <div class="col-12 mt-4">
            <button type="submit" class="btn btn-danger btn-lg px-4 fw-bold"><i class="bi bi-save me-1"></i> Save SEO & Code Injection Settings</button>
            <a href="../sitemap.php" target="_blank" class="btn btn-outline-secondary btn-lg ms-2"><i class="bi bi-box-arrow-up-right me-1"></i> View Dynamic Sitemap.xml</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
