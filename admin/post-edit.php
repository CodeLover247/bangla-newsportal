<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmtPost = $db->prepare("SELECT * FROM posts WHERE id = ?");
$stmtPost->execute([$id]);
$post = $stmtPost->fetch();

if (!$post) {
    echo "<div class='alert alert-danger m-4'>Post not found.</div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

if ($admin_role === 'reporter' && (int)$post['author_id'] !== (int)$_SESSION['admin_id']) {
    echo "<div class='card p-5 m-4 border-0 shadow-sm text-center'>
            <div class='fs-1 text-danger mb-2'><i class='bi bi-shield-lock'></i></div>
            <h4 class='fw-bold text-dark'>Permission Denied (অনুমতি নেই)</h4>
            <p class='text-muted'>You are logged in as a Reporter. You can only view and edit posts that you authored.</p>
            <a href='posts.php' class='btn btn-outline-danger btn-sm mx-auto' style='width: max-content;'>&larr; Return to My Posts</a>
          </div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

$enable_translation = get_setting('enable_translation', '1');
$default_lang = get_setting('default_language', 'en');

$categories = get_categories(0);
$authors = get_all_authors();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $title_en = trim($_POST['title_en'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $short_desc = trim($_POST['short_description'] ?? '');
    $short_desc_en = trim($_POST['short_description_en'] ?? '');
    $content = $_POST['content'] ?? '';
    $content_en = $_POST['content_en'] ?? '';

    // Automatic fallbacks if only one language is filled
    if (empty($title) && !empty($title_en)) $title = $title_en;
    if (empty($title_en) && !empty($title)) $title_en = $title;

    if (empty($content) && !empty($content_en)) $content = $content_en;
    if (empty($content_en) && !empty($content)) $content_en = $content;

    if (empty($short_desc) && !empty($short_desc_en)) $short_desc = $short_desc_en;
    if (empty($short_desc_en) && !empty($short_desc)) $short_desc_en = $short_desc;

    // Auto generate short description if both are empty
    if (empty($short_desc)) {
        $cleanText = trim(strip_tags($content));
        $short_desc = mb_substr($cleanText, 0, 180, 'UTF-8');
        $short_desc_en = $short_desc;
    }

    if (empty($slug)) {
        $slug = slugify(!empty($title_en) ? $title_en : $title);
        if (empty($slug)) {
            $slug = 'post-' . $id;
        }
    }

    $category_id = (int)($_POST['category_id'] ?? 0);
    $tags = trim($_POST['tags'] ?? '');
    $featured_image = trim($_POST['featured_image'] ?? $post['featured_image']);

    $author_id = (int)($_POST['author_id'] ?? $post['author_id']);
    $custom_author_name = trim($_POST['custom_author_name'] ?? '');
    $custom_author_image = trim($_POST['custom_author_image'] ?? $post['custom_author_image']);

    if (!empty($_FILES['custom_author_image_file']['name'])) {
        $up_author = handle_file_upload($_FILES['custom_author_image_file'], 'reporters');
        if ($up_author['success']) {
            $custom_author_image = $up_author['filepath'];
        }
    }

    if (!empty($_FILES['image_file']['name'])) {
        $up_res = handle_file_upload($_FILES['image_file'], 'posts');
        if ($up_res['success']) {
            $featured_image = $up_res['filepath'];
        } else {
            $error = "Upload error: " . $up_res['error'];
        }
    }

    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_breaking = isset($_POST['is_breaking']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';
    $views = max(0, (int)($_POST['views'] ?? $post['views']));
    $publish_date = !empty($_POST['publish_date']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_date'])) : ($post['publish_date'] ?? date('Y-m-d H:i:s'));

    $seo_title = trim($_POST['seo_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');

    $stmtUpdate = $db->prepare("UPDATE posts SET title=?, title_en=?, slug=?, short_description=?, short_description_en=?, content=?, content_en=?, category_id=?, author_id=?, custom_author_name=?, custom_author_image=?, featured_image=?, tags=?, is_featured=?, is_breaking=?, is_trending=?, is_popular=?, allow_comments=?, seo_title=?, meta_description=?, meta_keywords=?, status=?, views=?, publish_date=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
    $stmtUpdate->execute([$title, $title_en, $slug, $short_desc, $short_desc_en, $content, $content_en, $category_id, $author_id, $custom_author_name, $custom_author_image, $featured_image, $tags, $is_featured, $is_breaking, $is_trending, $is_popular, $allow_comments, $seo_title, $meta_description, $meta_keywords, $status, $views, $publish_date, $id]);

    $success = "Article updated successfully!";
    $post = $db->query("SELECT * FROM posts WHERE id = {$id}")->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Edit Post #<?= $post['id'] ?> (পোস্ট সম্পাদনা)</h3>
    <a href="posts.php" class="btn btn-outline-secondary">&larr; Back to Posts</a>
</div>

<?php if ($success): ?><div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form action="post-edit.php?id=<?= $post['id'] ?>" method="POST" enctype="multipart/form-data" class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 shadow-sm border mb-4">
            <?php if ($enable_translation === '1'): ?>
                <!-- Dual Language Tabs Header -->
                <ul class="nav nav-tabs mb-3" id="postLangTabsEdit" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $default_lang === 'bn' ? 'active' : '' ?> fw-bold text-danger" id="bn-tab" data-bs-toggle="tab" data-bs-target="#bn-tab-pane" type="button" role="tab" aria-controls="bn-tab-pane" aria-selected="<?= $default_lang === 'bn' ? 'true' : 'false' ?>">
                            <i class="bi bi-translate me-1"></i> বাংলা (Bangla)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $default_lang === 'en' ? 'active' : '' ?> fw-bold text-primary" id="en-tab" data-bs-toggle="tab" data-bs-target="#en-tab-pane" type="button" role="tab" aria-controls="en-tab-pane" aria-selected="<?= $default_lang === 'en' ? 'true' : 'false' ?>">
                            <i class="bi bi-globe me-1"></i> English Content
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="postLangTabContentEdit">
                    <!-- Bangla Tab Content -->
                    <div class="tab-pane fade <?= $default_lang === 'bn' ? 'show active' : '' ?>" id="bn-tab-pane" role="tabpanel" aria-labelledby="bn-tab" tabindex="0">
                        <div class="mb-3">
                            <label class="form-label fw-bold">বাংলা সংবাদ শিরোনাম (Bangla Title)</label>
                            <input type="text" id="post-title" name="title" class="form-control form-control-lg" value="<?= htmlspecialchars($post['title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">বাংলা সংক্ষিপ্ত বিবরণ (Bangla Short Subtitle)</label>
                            <textarea name="short_description" class="form-control" rows="3"><?= htmlspecialchars($post['short_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">বাংলা মূল সংবাদ বিবরণ (Bangla Full Article)</label>
                            <textarea name="content" id="editor_bn" class="form-control" rows="12"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- English Tab Content -->
                    <div class="tab-pane fade <?= $default_lang === 'en' ? 'show active' : '' ?>" id="en-tab-pane" role="tabpanel" aria-labelledby="en-tab" tabindex="0">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">English Article Title</label>
                            <input type="text" id="post-title-en" name="title_en" class="form-control form-control-lg" value="<?= htmlspecialchars($post['title_en'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">English Short Description / Subtitle</label>
                            <textarea name="short_description_en" class="form-control" rows="3"><?= htmlspecialchars($post['short_description_en'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">English Full Article Details</label>
                            <textarea name="content_en" id="editor_en" class="form-control" rows="12"><?= htmlspecialchars($post['content_en'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Single Language Mode -->
                <?php if ($default_lang === 'bn'): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5">বাংলা সংবাদ শিরোনাম *</label>
                        <input type="text" id="post-title" name="title" class="form-control form-control-lg" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">সংক্ষিপ্ত বিবরণ (Short Subtitle)</label>
                        <textarea name="short_description" class="form-control" rows="3"><?= htmlspecialchars($post['short_description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5">মূল সংবাদ বিবরণ *</label>
                        <textarea name="content" id="editor_bn" class="form-control" rows="12"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5 text-primary">Article Headline Title *</label>
                        <input type="text" id="post-title-en" name="title_en" class="form-control form-control-lg" value="<?= htmlspecialchars($post['title_en'] ?? ($post['title'] ?? '')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Short Description / Subtitle</label>
                        <textarea name="short_description_en" class="form-control" rows="3"><?= htmlspecialchars($post['short_description_en'] ?? ($post['short_description'] ?? '')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5 text-primary">Full Article Details *</label>
                        <textarea name="content_en" id="editor_en" class="form-control" rows="12"><?= htmlspecialchars($post['content_en'] ?? ($post['content'] ?? '')) ?></textarea>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-3 pt-3 border-top">
                <label class="form-label fw-bold">URL Slug</label>
                <input type="text" id="post-slug" name="slug" class="form-control" value="<?= htmlspecialchars($post['slug'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Author & Reporter Box -->
        <div class="card p-4 shadow-sm border mb-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-person-badge text-danger me-2"></i> Author & Reporter</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">Select Registered System Author</label>
                <select name="author_id" class="form-select">
                    <?php foreach ($authors as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= ($post['author_id'] == $a['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['full_name']) ?> (<?= htmlspecialchars($a['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Or Custom / Guest Reporter Name</label>
                <input type="text" name="custom_author_name" class="form-control" value="<?= htmlspecialchars($post['custom_author_name'] ?? '') ?>" placeholder="e.g. নিজস্ব প্রতিবেদক, জেলা প্রতিনিধি, Staff Reporter">
                <div class="form-text small">Overrides system author name if entered.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Reporter Photo URL or Select Media</label>
                <div class="input-group mb-2">
                    <input type="text" id="reporter_image_edit" name="custom_author_image" class="form-control" value="<?= htmlspecialchars($post['custom_author_image'] ?? '') ?>" placeholder="uploads/reporters/photo.jpg">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#reporter_image_edit"><i class="bi bi-images me-1"></i> Media</button>
                </div>
                <label class="form-label fw-semibold small text-muted">Or Upload Reporter Photo File</label>
                <input type="file" name="custom_author_image_file" class="form-control form-control-sm" accept="image/*">
            </div>
        </div>

        <div class="card p-4 shadow-sm border mb-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3">Publish Options</h5>
            <div class="mb-3">
                <label class="form-label fw-bold">Category</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $post['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Status (স্ট্যাটাস)</label>
                <select name="status" class="form-select">
                    <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published (প্রকাশিত)</option>
                    <option value="pending" <?= $post['status'] === 'pending' ? 'selected' : '' ?>>Pending Review (অনুমোদনের অপেক্ষায়)</option>
                    <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft (খসড়া)</option>
                    <option value="scheduled" <?= $post['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled (তফসিল)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Tags</label>
                <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($post['tags']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Views Count</label>
                <input type="number" name="views" class="form-control" value="<?= (int)($post['views'] ?? 0) ?>" min="0">
                <div class="form-text small">Edit view counter for this article.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Publish Date / Backdate</label>
                <input type="datetime-local" name="publish_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($post['publish_date'] ?? 'now')) ?>">
                <div class="form-text small">Allows backdating or changing published timestamp.</div>
            </div>
            <hr>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="is_featured">Featured Lead Story (প্রচ্ছদ প্রধান খবর)</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_breaking" id="is_breaking" value="1" <?= !empty($post['is_breaking']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="is_breaking">Breaking News Ticker (ব্রেকিং নিউজ)</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" value="1" <?= !empty($post['is_trending']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="is_trending">Trending Widget (আলোচিত বিষয়)</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="allow_comments" id="allow_comments" value="1" <?= (!isset($post['allow_comments']) || $post['allow_comments'] == 1) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="allow_comments">Allow Comments (মন্তব্য করার অনুমতি)</label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold mt-2">Update Post</button>
        </div>

        <!-- SEO Meta Data Card -->
        <div class="card p-4 shadow-sm border mb-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-search me-2 text-primary"></i> SEO Meta Data</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">SEO Title</label>
                <input type="text" name="seo_title" class="form-control" value="<?= htmlspecialchars($post['seo_title'] ?? '') ?>" placeholder="Article Title for Google">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Meta Description</label>
                <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief summary for search engines"><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Meta Keywords</label>
                <input type="text" name="meta_keywords" class="form-control" value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>" placeholder="news, bd, cricket, election">
            </div>
        </div>
        </div>

        <div class="card p-4 shadow-sm border">
            <h5 class="fw-bold border-bottom pb-2 mb-3">Featured Image</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">Image URL or Select from Media</label>
                <div class="input-group">
                    <input type="text" id="featured_image_edit" name="featured_image" class="form-control" value="<?= htmlspecialchars($post['featured_image']) ?>">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#featured_image_edit" data-preview="#img-preview-edit"><i class="bi bi-images me-1"></i> Media</button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Or Upload Direct File</label>
                <input type="file" name="image_file" class="form-control custom-img-input" data-preview="img-preview-edit" accept="image/*">
            </div>
            <img id="img-preview-edit" src="<?= !empty($post['featured_image']) ? htmlspecialchars($post['featured_image']) : '' ?>" class="img-fluid rounded border mt-2" style="<?= empty($post['featured_image']) ? 'display:none;' : '' ?>" alt="">
        </div>
    </div>
</form>

<script>
    if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.config.versionCheck = false;
        var ckOptions = {
            height: 360,
            width: '100%',
            minHeight: 300,
            removePlugins: 'elementspath',
            resize_enabled: true
        };

        if (document.getElementById('editor_bn')) {
            CKEDITOR.replace('editor_bn', ckOptions);
        }
        if (document.getElementById('editor_en')) {
            CKEDITOR.replace('editor_en', ckOptions);
        }

        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tabBtn) {
            tabBtn.addEventListener('shown.bs.tab', function(e) {
                if (window.CKEDITOR) {
                    for (var instanceName in CKEDITOR.instances) {
                        if (CKEDITOR.instances.hasOwnProperty(instanceName)) {
                            CKEDITOR.instances[instanceName].resize('100%', 360);
                        }
                    }
                }
            });
        });
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
