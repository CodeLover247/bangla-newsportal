<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$categories = get_categories(0);
$authors = get_all_authors();

$enable_translation = get_setting('enable_translation', '1');
$default_lang = get_setting('default_language', 'en');

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

    // Fallbacks if single language mode or if only one language field is filled
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

    $category_id = (int)($_POST['category_id'] ?? 0);
    $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);
    $tags = trim($_POST['tags'] ?? '');
    $featured_image = trim($_POST['featured_image'] ?? '');

    $author_id = (int)($_POST['author_id'] ?? ($_SESSION['admin_id'] ?? 1));
    $custom_author_name = trim($_POST['custom_author_name'] ?? '');
    $custom_author_image = trim($_POST['custom_author_image'] ?? '');

    // Handle Direct Author Image Upload
    if (!empty($_FILES['custom_author_image_file']['name'])) {
        $up_author = handle_file_upload($_FILES['custom_author_image_file'], 'reporters');
        if ($up_author['success']) {
            $custom_author_image = $up_author['filepath'];
        }
    }

    // Handle Image Upload securely
    if (!empty($_FILES['image_file']['name'])) {
        $up_res = handle_file_upload($_FILES['image_file'], 'posts');
        if ($up_res['success']) {
            $featured_image = $up_res['filepath'];
        } else {
            $error = "Upload error: " . $up_res['error'];
        }
    }

    if (empty($slug)) {
        $slug = slugify(!empty($title_en) ? $title_en : $title);
        if (empty($slug)) {
            $slug = 'post-' . time();
        }
    }

    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_breaking = isset($_POST['is_breaking']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';
    $views = max(0, (int)($_POST['views'] ?? 0));
    $publish_date = !empty($_POST['publish_date']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_date'])) : date('Y-m-d H:i:s');

    $seo_title = trim($_POST['seo_title'] ?? $title);
    $meta_desc = trim($_POST['meta_description'] ?? $short_desc);
    $meta_keys = trim($_POST['meta_keywords'] ?? $tags);

    if (empty($title) || empty($content) || $category_id <= 0) {
        $error = "Please fill in required fields (Article Title, Category, Content).";
    } else {
        $stmt = $db->prepare("INSERT INTO posts 
            (title, title_en, slug, short_description, short_description_en, content, content_en, category_id, subcategory_id, author_id, custom_author_name, custom_author_image, featured_image, tags, is_featured, is_breaking, is_trending, is_popular, allow_comments, views, seo_title, meta_description, meta_keywords, status, publish_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([
                $title, $title_en, $slug, $short_desc, $short_desc_en, $content, $content_en, $category_id, $subcategory_id,
                $author_id, $custom_author_name, $custom_author_image, $featured_image, $tags, $is_featured, $is_breaking,
                $is_trending, $is_popular, $allow_comments, $views, $seo_title, $meta_desc, $meta_keys, $status, $publish_date
            ]);
            $success = "Article published successfully!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Add New Post (পোস্ট তৈরি করুন)</h3>
    <a href="posts.php" class="btn btn-outline-secondary">&larr; Back to Posts</a>
</div>

<?php if ($error): ?><div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form action="post-add.php" method="POST" enctype="multipart/form-data" class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 shadow-sm border mb-4">
            <?php if ($enable_translation === '1'): ?>
                <!-- Dual Language Tabs Header -->
                <ul class="nav nav-tabs mb-3" id="postLangTabs" role="tablist">
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

                <div class="tab-content" id="postLangTabContent">
                    <!-- Bangla Tab Content -->
                    <div class="tab-pane fade <?= $default_lang === 'bn' ? 'show active' : '' ?>" id="bn-tab-pane" role="tabpanel" aria-labelledby="bn-tab" tabindex="0">
                        <div class="mb-3">
                            <label class="form-label fw-bold">বাংলা শিরোনাম (Bangla Title)</label>
                            <input type="text" id="post-title" name="title" class="form-control form-control-lg" placeholder="বাংলা সংবাদ শিরোনাম লিখুন...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">বাংলা সংক্ষিপ্ত বিবরণ (Bangla Short Subtitle)</label>
                            <textarea name="short_description" class="form-control" rows="3" placeholder="হোমপেজ ও তালিকা দেখার জন্য সংক্ষিপ্ত সারসংক্ষেপ..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">বাংলা মূল সংবাদ বিবরণ (Bangla Full Article)</label>
                            <textarea name="content" id="editor_bn" class="form-control" rows="12"></textarea>
                        </div>
                    </div>

                    <!-- English Tab Content -->
                    <div class="tab-pane fade <?= $default_lang === 'en' ? 'show active' : '' ?>" id="en-tab-pane" role="tabpanel" aria-labelledby="en-tab" tabindex="0">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">English Article Title</label>
                            <input type="text" id="post-title-en" name="title_en" class="form-control form-control-lg" placeholder="Enter English headline title...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">English Short Description / Subtitle</label>
                            <textarea name="short_description_en" class="form-control" rows="3" placeholder="Brief summary in English for readers..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">English Full Article Details</label>
                            <textarea name="content_en" id="editor_en" class="form-control" rows="12"></textarea>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Single Language Mode -->
                <?php if ($default_lang === 'bn'): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5">বাংলা সংবাদ শিরোনাম *</label>
                        <input type="text" id="post-title" name="title" class="form-control form-control-lg" placeholder="সংবাদ শিরোনাম লিখুন..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">সংক্ষিপ্ত বিবরণ (Short Subtitle)</label>
                        <textarea name="short_description" class="form-control" rows="3" placeholder="সংক্ষিপ্ত সারসংক্ষেপ..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5">মূল সংবাদ বিবরণ *</label>
                        <textarea name="content" id="editor_bn" class="form-control" rows="12"></textarea>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5 text-primary">Article Headline Title *</label>
                        <input type="text" id="post-title-en" name="title_en" class="form-control form-control-lg" placeholder="Enter article title..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Short Description / Subtitle</label>
                        <textarea name="short_description_en" class="form-control" rows="3" placeholder="Brief article summary..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5 text-primary">Full Article Details *</label>
                        <textarea name="content_en" id="editor_en" class="form-control" rows="12"></textarea>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-3 pt-3 border-top">
                <label class="form-label fw-bold">URL Slug (Auto Generated)</label>
                <input type="text" id="post-slug" name="slug" class="form-control" placeholder="article-url-slug">
            </div>
        </div>

        <!-- SEO Box -->
        <div class="card p-4 shadow-sm border">
            <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-search text-primary me-2"></i> SEO Metadata</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">SEO Title</label>
                <input type="text" name="seo_title" class="form-control" placeholder="SEO Title Tag">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Meta Description</label>
                <textarea name="meta_description" class="form-control" rows="2" placeholder="Search engine description..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Meta Keywords</label>
                <input type="text" name="meta_keywords" class="form-control" placeholder="news, breaking, national">
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
                        <option value="<?= $a['id'] ?>" <?= (($_SESSION['admin_id'] ?? 1) == $a['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['full_name']) ?> (<?= htmlspecialchars($a['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Or Custom / Guest Reporter Name</label>
                <input type="text" name="custom_author_name" class="form-control" placeholder="e.g. নিজস্ব প্রতিবেদক, জেলা প্রতিনিধি, Staff Reporter">
                <div class="form-text small">Overrides system author name if entered.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Reporter Photo URL or Select Media</label>
                <div class="input-group mb-2">
                    <input type="text" id="reporter_image_input" name="custom_author_image" class="form-control" placeholder="uploads/reporters/photo.jpg">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#reporter_image_input"><i class="bi bi-images me-1"></i> Media</button>
                </div>
                <label class="form-label fw-semibold small text-muted">Or Upload Reporter Photo File</label>
                <input type="file" name="custom_author_image_file" class="form-control form-control-sm" accept="image/*">
            </div>
        </div>

        <!-- Publish Settings -->
        <div class="card p-4 shadow-sm border mb-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3">Publish Options</h5>
            <div class="mb-3">
                <label class="form-label fw-bold">Category *</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Tags</label>
                <input type="text" name="tags" class="form-control" placeholder="National, Cricket, Election">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Manual / Initial View Count</label>
                <input type="number" name="views" class="form-control" value="0" min="0" placeholder="e.g. 500">
                <div class="form-text small">Set manual view count or keep 0 for automatic tracking.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Publish Date / Backdate</label>
                <input type="datetime-local" name="publish_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                <div class="form-text small">Allows selecting past or custom date for this article.</div>
            </div>

            <hr>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1">
                <label class="form-check-label fw-semibold" for="is_featured">Featured Lead Story</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_breaking" id="is_breaking" value="1">
                <label class="form-check-label fw-semibold" for="is_breaking">Breaking News Ticker</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" value="1">
                <label class="form-check-label fw-semibold" for="is_trending">Trending Widget</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="allow_comments" id="allow_comments" value="1" checked>
                <label class="form-check-label fw-semibold" for="allow_comments">Allow Comments</label>
            </div>

            <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold">Publish Post</button>
        </div>

        <!-- Image Upload Box -->
        <div class="card p-4 shadow-sm border">
            <h5 class="fw-bold border-bottom pb-2 mb-3">Featured Image</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">Image URL or Select from Media</label>
                <div class="input-group">
                    <input type="text" id="featured_image_input" name="featured_image" class="form-control" placeholder="uploads/posts/image.jpg or http://">
                    <button type="button" class="btn btn-dark btn-media-picker" data-target="#featured_image_input" data-preview="#img-preview"><i class="bi bi-images me-1"></i> Media</button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Or Upload Direct File</label>
                <input type="file" name="image_file" class="form-control custom-img-input" data-preview="img-preview" accept="image/*">
            </div>
            <img id="img-preview" src="" class="img-fluid rounded border mt-2" style="display:none;" alt="">
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
