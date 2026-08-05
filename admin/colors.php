<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
require_role_permission('admin');

// Auth Guard
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_defaults'])) {
        $defaults = [
            'default_theme_mode' => 'light',
            'primary_color' => '#e61e25',
            'primary_hover_color' => '#b91c1c',
            'topbar_bg_color' => '#0f172a',
            'topbar_text_color' => '#f8fafc',
            'header_bg_color' => '#ffffff',
            'header_text_color' => '#111111',
            'menu_bg_color' => '#991b1b',
            'menu_text_color' => '#ffffff',
            'menu_hover_bg_color' => '#7f1d1d',
            'body_bg_color' => '#ffffff',
            'body_text_color' => '#111111',
            'card_bg_color' => '#ffffff',
            'card_border_color' => '#e5e7eb',
            'title_color' => '#111111',
            'link_hover_color' => '#e61e25',
            'footer_bg_color' => '#0f172a',
            'footer_text_color' => '#94a3b8',
            'footer_heading_color' => '#ffffff',
            'footer_link_color' => '#cbd5e1',
            'ticker_bg_color' => '#dc2626',
            'ticker_text_color' => '#ffffff',
            'ticker_label_bg_color' => '#0f172a',
            'ticker_label_text_color' => '#ffffff',
            'widget_header_bg' => '#991b1b',
            'badge_bg_color' => '#e61e25',
            'mobile_nav_bg' => '#0f172a',
            'custom_css' => ''
        ];
        foreach ($defaults as $k => $v) {
            set_setting($k, $v);
        }
        $message = 'All theme colors have been successfully reset to system defaults!';
    } else {
        $color_settings = [
            'default_theme_mode',
            'primary_color',
            'primary_hover_color',
            'topbar_bg_color',
            'topbar_text_color',
            'header_bg_color',
            'header_text_color',
            'menu_bg_color',
            'menu_text_color',
            'menu_hover_bg_color',
            'body_bg_color',
            'body_text_color',
            'card_bg_color',
            'card_border_color',
            'title_color',
            'link_hover_color',
            'footer_bg_color',
            'footer_text_color',
            'footer_heading_color',
            'footer_link_color',
            'ticker_bg_color',
            'ticker_text_color',
            'ticker_label_bg_color',
            'ticker_label_text_color',
            'widget_header_bg',
            'badge_bg_color',
            'mobile_nav_bg',
            'custom_css'
        ];

        foreach ($color_settings as $key) {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            set_setting($key, $value);
        }

        $message = 'Theme and color preferences saved successfully!';
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-palette text-danger me-2"></i> Theme & Color Manager</h4>
        <p class="text-muted small mb-0">Customize all website colors, dark/light mode defaults, and header/footer styling dynamically.</p>
    </div>
    <div class="d-flex gap-2">
        <form action="colors.php" method="POST" class="d-inline">
            <button type="submit" name="reset_defaults" value="1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to reset all theme colors to system defaults?');">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default Colors
            </button>
        </form>
        <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye me-1"></i> Preview Live Site</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="colors.php" method="POST">
    <div class="row g-4">
        <!-- System Theme Defaults -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-sun-moon text-danger me-2"></i> Default Appearance & Core Accent</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Default Display Mode</label>
                    <?php $dtm = get_setting('default_theme_mode', 'light'); ?>
                    <select name="default_theme_mode" class="form-select">
                        <option value="light" <?= $dtm === 'light' ? 'selected' : '' ?>>Light Mode (Default)</option>
                        <option value="dark" <?= $dtm === 'dark' ? 'selected' : '' ?>>Dark Mode (Default)</option>
                    </select>
                    <small class="text-muted">Visitor can still toggle their personal mode preference using topbar button.</small>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Primary Brand Accent</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('primary_color', '#e61e25')) ?>" id="primary_color_picker">
                            <input type="text" name="primary_color" class="form-control" value="<?= htmlspecialchars(get_setting('primary_color', '#e61e25')) ?>" id="primary_color_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Primary Hover Accent</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('primary_hover_color', '#b91c1c')) ?>" id="primary_hover_picker">
                            <input type="text" name="primary_hover_color" class="form-control" value="<?= htmlspecialchars(get_setting('primary_hover_color', '#b91c1c')) ?>" id="primary_hover_text">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Main Heading / Title Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('title_color', '#111111')) ?>" id="title_color_picker">
                            <input type="text" name="title_color" class="form-control" value="<?= htmlspecialchars(get_setting('title_color', '#111111')) ?>" id="title_color_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Link Hover Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('link_hover_color', '#e61e25')) ?>" id="link_hover_picker">
                            <input type="text" name="link_hover_color" class="form-control" value="<?= htmlspecialchars(get_setting('link_hover_color', '#e61e25')) ?>" id="link_hover_text">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Body Background Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('body_bg_color', '#ffffff')) ?>" id="body_bg_picker">
                            <input type="text" name="body_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('body_bg_color', '#ffffff')) ?>" id="body_bg_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Body Text Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('body_text_color', '#111111')) ?>" id="body_text_picker">
                            <input type="text" name="body_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('body_text_color', '#111111')) ?>" id="body_text_text">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Card Background Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('card_bg_color', '#ffffff')) ?>" id="card_bg_picker">
                            <input type="text" name="card_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('card_bg_color', '#ffffff')) ?>" id="card_bg_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Card Border Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('card_border_color', '#e5e7eb')) ?>" id="card_border_picker">
                            <input type="text" name="card_border_color" class="form-control" value="<?= htmlspecialchars(get_setting('card_border_color', '#e5e7eb')) ?>" id="card_border_text">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topbar & Main Menu Navbar Colors -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-menu-button-wide text-danger me-2"></i> Header Topbar & Menu Navigation</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Top Bar Background</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('topbar_bg_color', '#0f172a')) ?>" id="topbar_bg_picker">
                            <input type="text" name="topbar_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('topbar_bg_color', '#0f172a')) ?>" id="topbar_bg_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Top Bar Text & Icons</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('topbar_text_color', '#f8fafc')) ?>" id="topbar_text_picker">
                            <input type="text" name="topbar_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('topbar_text_color', '#f8fafc')) ?>" id="topbar_text_text">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Main Header Banner BG</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('header_bg_color', '#ffffff')) ?>" id="header_bg_picker">
                            <input type="text" name="header_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('header_bg_color', '#ffffff')) ?>" id="header_bg_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Header Title Text</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('header_text_color', '#111111')) ?>" id="header_text_picker">
                            <input type="text" name="header_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('header_text_color', '#111111')) ?>" id="header_text_text">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nav Menu Background</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('menu_bg_color', '#991b1b')) ?>" id="menu_bg_picker">
                            <input type="text" name="menu_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('menu_bg_color', '#991b1b')) ?>" id="menu_bg_text">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nav Menu Link Text</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('menu_text_color', '#ffffff')) ?>" id="menu_text_picker">
                            <input type="text" name="menu_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('menu_text_color', '#ffffff')) ?>" id="menu_text_text">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-bold">Nav Menu Link Hover Background</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('menu_hover_bg_color', '#7f1d1d')) ?>" id="menu_hover_bg_picker">
                        <input type="text" name="menu_hover_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('menu_hover_bg_color', '#7f1d1d')) ?>" id="menu_hover_bg_text">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Breaking Ticker Bar BG</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('ticker_bg_color', '#dc2626')) ?>" id="ticker_bg_picker">
                            <input type="text" name="ticker_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('ticker_bg_color', '#dc2626')) ?>" id="ticker_bg_text">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Breaking Ticker Text</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('ticker_text_color', '#ffffff')) ?>" id="ticker_text_picker">
                            <input type="text" name="ticker_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('ticker_text_color', '#ffffff')) ?>" id="ticker_text_text">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Breaking Badge Label BG (জরুরি খবর)</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('ticker_label_bg_color', '#0f172a')) ?>" id="ticker_label_bg_picker">
                            <input type="text" name="ticker_label_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('ticker_label_bg_color', '#0f172a')) ?>" id="ticker_label_bg_text">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Breaking Badge Text Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('ticker_label_text_color', '#ffffff')) ?>" id="ticker_label_text_picker">
                            <input type="text" name="ticker_label_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('ticker_label_text_color', '#ffffff')) ?>" id="ticker_label_text_text">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Widget Header Accent</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('widget_header_bg', '#991b1b')) ?>" id="widget_header_bg_picker">
                            <input type="text" name="widget_header_bg" class="form-control" value="<?= htmlspecialchars(get_setting('widget_header_bg', '#991b1b')) ?>" id="widget_header_bg_text">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Category Badge Accent</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('badge_bg_color', '#e61e25')) ?>" id="badge_bg_picker">
                            <input type="text" name="badge_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('badge_bg_color', '#e61e25')) ?>" id="badge_bg_text">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mobile Nav BG</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('mobile_nav_bg', '#0f172a')) ?>" id="mobile_nav_bg_picker">
                            <input type="text" name="mobile_nav_bg" class="form-control" value="<?= htmlspecialchars(get_setting('mobile_nav_bg', '#0f172a')) ?>" id="mobile_nav_bg_text">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Colors & Custom CSS -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-layout-footer text-danger me-2"></i> Footer Section Customization & CSS Override</h5>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Footer Background</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('footer_bg_color', '#0f172a')) ?>" id="footer_bg_picker">
                            <input type="text" name="footer_bg_color" class="form-control" value="<?= htmlspecialchars(get_setting('footer_bg_color', '#0f172a')) ?>" id="footer_bg_text">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Footer Headings</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('footer_heading_color', '#ffffff')) ?>" id="footer_heading_picker">
                            <input type="text" name="footer_heading_color" class="form-control" value="<?= htmlspecialchars(get_setting('footer_heading_color', '#ffffff')) ?>" id="footer_heading_text">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Footer Text</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('footer_text_color', '#94a3b8')) ?>" id="footer_text_picker">
                            <input type="text" name="footer_text_color" class="form-control" value="<?= htmlspecialchars(get_setting('footer_text_color', '#94a3b8')) ?>" id="footer_text_text">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Footer Links</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="<?= htmlspecialchars(get_setting('footer_link_color', '#cbd5e1')) ?>" id="footer_link_picker">
                            <input type="text" name="footer_link_color" class="form-control" value="<?= htmlspecialchars(get_setting('footer_link_color', '#cbd5e1')) ?>" id="footer_link_text">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label fw-bold"><i class="bi bi-code-slash text-danger me-1"></i> Custom CSS Code Injection</label>
                    <textarea name="custom_css" class="form-control font-monospace" rows="4" placeholder="/* Add custom CSS rules here. Example: .site-title-logo { font-size: 3rem; } */"><?= htmlspecialchars(get_setting('custom_css', '')) ?></textarea>
                    <small class="text-muted">This CSS will be automatically included in the public website header.</small>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-danger px-5 py-2 fw-bold"><i class="bi bi-save me-1"></i> Save All Colors & Theme Settings</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Synchronize color picker inputs with text inputs
document.querySelectorAll('input[type="color"]').forEach(picker => {
    picker.addEventListener('input', function() {
        const textInput = document.getElementById(this.id.replace('_picker', '_text'));
        if (textInput) textInput.value = this.value;
    });
});
document.querySelectorAll('input[type="text"]').forEach(textInput => {
    if (textInput.id.endsWith('_text')) {
        textInput.addEventListener('input', function() {
            const picker = document.getElementById(this.id.replace('_text', '_picker'));
            if (picker && /^#[0-9A-F]{6}$/i.test(this.value)) picker.value = this.value;
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
