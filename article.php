<?php
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$post = null;
if (!empty($slug)) {
    $post = get_post_by_slug($slug);
} elseif ($id > 0) {
    $post = get_post_by_id($id);
}

if (!$post) {
    $page_title = 'সংবাদ পাওয়া যায়নি - Article Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='container my-5 text-center py-5'>
        <div class='card p-5 shadow-sm border-0 max-w-lg mx-auto'>
            <i class='bi bi-exclamation-triangle text-danger display-3 mb-3'></i>
            <h2 class='text-danger fw-bold mb-2'>সংবাদটি পাওয়া যায়নি (Article Not Found)</h2>
            <p class='text-muted mb-4'>আপনি যে সংবাদটি খুঁজছেন তা মুছে ফেলা হয়েছে অথবা ভুল ইউআরএল প্রদান করা হয়েছে।</p>
            <div><a href='index.php' class='btn btn-danger px-4 py-2 fw-bold'>হোম পেজে ফিরে যান</a></div>
        </div>
    </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Prepare Open Graph and Social Share Metadata
$page_title = !empty($post['seo_title']) ? $post['seo_title'] : $post['title'];
$page_desc = !empty($post['meta_description']) ? $post['meta_description'] : (!empty($post['short_description']) ? $post['short_description'] : strip_tags(mb_substr($post['content'] ?? '', 0, 180)));
$page_keywords = !empty($post['meta_keywords']) ? $post['meta_keywords'] : ($post['tags'] ?? '');
$page_image = !empty($post['featured_image']) ? $post['featured_image'] : '';
$og_type = 'article';
$og_url = get_full_url('article.php?slug=' . urlencode($post['slug']));

require_once __DIR__ . '/includes/header.php';

// Increment Views Counter
increment_views($post['id']);

// Related Posts in same category
$related_posts = get_posts(['category_id' => $post['category_id'], 'limit' => 3]);

// Comments
$comments = [];
$db = get_db_connection();
if ($db) {
    try {
        $stmtComm = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY id DESC");
        $stmtComm->execute([$post['id']]);
        $comments = $stmtComm->fetchAll() ?: [];
    } catch (Throwable $e) {
        $comments = [];
    }
}
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="category.php?slug=<?= $post['category_slug'] ?>"><?= htmlspecialchars($post['category_name']) ?></a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 300px;"><?= htmlspecialchars($post['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Article Content -->
        <div class="col-lg-8">
            <!-- Print Newspaper Header (Only visible when printing) -->
            <div class="d-none d-print-block mb-4 text-center border-bottom pb-3 border-2 border-dark">
                <?php 
                $print_logo = get_setting('print_logo_url', get_setting('logo_url', ''));
                if (!empty($print_logo)): 
                ?>
                    <div class="mb-2 text-center">
                        <img src="<?= htmlspecialchars($print_logo) ?>" alt="<?= htmlspecialchars($site_name ?? 'Logo') ?>" style="max-height: 95px; width: auto; object-fit: contain;">
                    </div>
                <?php else: ?>
                    <h1 class="fw-bold font-serif mb-1" style="font-size: 26pt; letter-spacing: -1px; text-transform: uppercase;"><?= htmlspecialchars(get_setting('site_name', 'News Portal')) ?></h1>
                <?php endif; ?>
                <div class="small fw-bold text-uppercase border-top border-bottom py-1 my-2 border-dark">
                    <?= get_full_bangla_date_string() ?> &bull; <?= __('সম্পাদক:', 'Editor:') ?> <?= htmlspecialchars(get_setting('editor_name', 'Editorial Team')) ?><?php if ($pub_name = get_setting('publisher_name', '')): ?> &bull; <?= __('প্রকাশক:', 'Publisher:') ?> <?= htmlspecialchars($pub_name) ?><?php endif; ?>
                </div>
            </div>

            <article class="article-wrapper bg-white p-3 p-md-4 rounded border">
                
                <!-- Category Badge & Title -->
                <a href="category.php?slug=<?= $post['category_slug'] ?>" class="badge bg-danger text-uppercase mb-2 text-decoration-none"><?= htmlspecialchars($post['category_name']) ?></a>
                <h1 class="font-serif fw-bold mb-3" style="font-size: 2.2rem;"><?= htmlspecialchars($post['title']) ?></h1>
                
                <!-- Short Description Subheading -->
                <?php if (!empty($post['short_description'])): ?>
                    <p class="lead text-muted border-start border-4 border-danger ps-3 my-3" style="font-size: 1.15rem;"><?= htmlspecialchars($post['short_description']) ?></p>
                <?php endif; ?>

                <!-- Meta Row & Action Buttons -->
                <?php $display_author = get_post_display_author($post); ?>
                <div class="article-meta d-flex flex-wrap align-items-center justify-content-between gap-2 my-3 py-2 border-top border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <img src="<?= htmlspecialchars($display_author['avatar']) ?>" class="rounded-circle border" style="width: 42px; height: 42px; object-fit: cover;" alt="<?= htmlspecialchars($display_author['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80'">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($display_author['name']) ?></div>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('F j, Y - g:i A', strtotime($post['publish_date'])) ?> &bull; <i class="bi bi-eye me-1"></i><?= number_format($post['views'] + 1) ?> Views</small>
                        </div>
                    </div>

                    <!-- Share & Print Bar -->
                    <?php
                    $sf_fb = get_setting('share_facebook', '1');
                    $sf_tw = get_setting('share_twitter', '1');
                    $sf_wa = get_setting('share_whatsapp', '1');
                    $sf_li = get_setting('share_linkedin', '1');
                    $sf_tg = get_setting('share_telegram', '1');
                    $sf_pin = get_setting('share_pinterest', '1');
                    $sf_em = get_setting('share_email', '1');
                    $sf_copy = get_setting('share_copy', '1');
                    $sf_print = get_setting('share_print', '1');

                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                    $current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    $share_title = $post['title'];
                    $share_img = $post['featured_image'] ?? '';
                    ?>
                    <div class="share-bar d-flex flex-wrap gap-2 align-items-center">
                        <span class="fw-bold small text-muted me-1"><i class="bi bi-share-fill me-1 text-danger"></i><?= __('শেয়ার করুন:', 'Share:') ?></span>
                        <?php if ($sf_fb === '1'): ?>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($current_url) ?>" target="_blank" class="btn btn-sm btn-primary py-1 px-2" title="Share on Facebook"><i class="bi bi-facebook"></i> Facebook</a>
                        <?php endif; ?>
                        <?php if ($sf_tw === '1'): ?>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($current_url) ?>&text=<?= urlencode($share_title) ?>" target="_blank" class="btn btn-sm btn-dark py-1 px-2" title="Share on X / Twitter"><i class="bi bi-twitter-x"></i> Twitter</a>
                        <?php endif; ?>
                        <?php if ($sf_wa === '1'): ?>
                            <a href="https://api.whatsapp.com/send?text=<?= urlencode($share_title . ' - ' . $current_url) ?>" target="_blank" class="btn btn-sm btn-success py-1 px-2" title="Share on WhatsApp"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                        <?php endif; ?>
                        <?php if ($sf_li === '1'): ?>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($current_url) ?>" target="_blank" class="btn btn-sm text-white py-1 px-2" style="background-color: #0077b5;" title="Share on LinkedIn"><i class="bi bi-linkedin"></i> LinkedIn</a>
                        <?php endif; ?>
                        <?php if ($sf_tg === '1'): ?>
                            <a href="https://t.me/share/url?url=<?= urlencode($current_url) ?>&text=<?= urlencode($share_title) ?>" target="_blank" class="btn btn-sm text-white py-1 px-2" style="background-color: #229ED9;" title="Share on Telegram"><i class="bi bi-telegram"></i> Telegram</a>
                        <?php endif; ?>
                        <?php if ($sf_pin === '1'): ?>
                            <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($current_url) ?>&media=<?= urlencode($share_img) ?>&description=<?= urlencode($share_title) ?>" target="_blank" class="btn btn-sm text-white py-1 px-2" style="background-color: #E60023;" title="Share on Pinterest"><i class="bi bi-pinterest"></i> Pinterest</a>
                        <?php endif; ?>
                        <?php if ($sf_em === '1'): ?>
                            <a href="mailto:?subject=<?= urlencode($share_title) ?>&body=<?= urlencode('Check out this article: ' . $current_url) ?>" class="btn btn-sm btn-secondary py-1 px-2" title="Share via Email"><i class="bi bi-envelope-fill"></i> Email</a>
                        <?php endif; ?>
                        <?php if ($sf_copy === '1'): ?>
                            <button id="btn-copy-link" class="btn btn-sm btn-outline-secondary py-1 px-2" data-url="<?= htmlspecialchars($current_url) ?>"><i class="bi bi-link-45deg"></i> <?= __('লিঙ্ক কপি', 'Copy') ?></button>
                        <?php endif; ?>
                        <?php if ($sf_print === '1'): ?>
                            <button id="btn-print-article" class="btn btn-sm btn-outline-dark py-1 px-2"><i class="bi bi-printer"></i> <?= __('প্রিন্ট', 'Print') ?></button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Featured Image -->
                <?php if (!empty($post['featured_image'])): ?>
                    <div class="my-4">
                        <img src="<?= htmlspecialchars(get_media_url($post['featured_image'])) ?>" class="img-fluid rounded w-100 shadow-sm" alt="<?= htmlspecialchars($post['title']) ?>">
                    </div>
                <?php endif; ?>

                <!-- Inside Article Top Ad -->
                <?= render_ad('article_top', 'my-3') ?>

                <!-- Article Body Content -->
                <?php $dropcap_class = (get_setting('enable_drop_cap', '0') === '1') ? 'enable-dropcap' : ''; ?>
                <div class="article-body font-sans text-dark my-4 <?= $dropcap_class ?>" style="font-size: 1.1rem; line-height: 1.8; text-align: justify; text-justify: inter-word;">
                    <?= $post['content'] ?>
                </div>

                <!-- Inside Article Middle Ad -->
                <?= render_ad('article_middle', 'my-3') ?>

                <!-- Inside Article Bottom Ad -->
                <?= render_ad('article_bottom', 'my-4') ?>

                <!-- Tags Pill List -->
                <?php if (!empty($post['tags'])): 
                    $tags = explode(',', $post['tags']);
                ?>
                    <div class="my-4 pt-3 border-top">
                        <strong class="me-2"><i class="bi bi-tags-fill text-danger me-1"></i> Tags:</strong>
                        <?php foreach ($tags as $tag): ?>
                            <a href="search.php?q=<?= urlencode(trim($tag)) ?>" class="badge bg-light text-dark border me-1 text-decoration-none py-2 px-3"><?= htmlspecialchars(trim($tag)) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Author Bio Card -->
                <div class="p-3 bg-light rounded border my-4 d-flex gap-3 align-items-center">
                    <img src="<?= htmlspecialchars($display_author['avatar']) ?>" class="rounded-circle border flex-shrink-0" style="width: 60px; height: 60px; object-fit: cover;" alt="<?= htmlspecialchars($display_author['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80'">
                    <div>
                        <h6 class="fw-bold mb-1"><i class="bi bi-pen me-1 text-danger"></i> <?= htmlspecialchars($display_author['name']) ?></h6>
                        <?php if (!empty($display_author['bio'])): ?>
                            <p class="small text-muted mb-0"><?= htmlspecialchars($display_author['bio']) ?></p>
                        <?php else: ?>
                            <p class="small text-muted mb-0">Journalist & Reporter, Daily Horizon News Team.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Related News Grid -->
                <div class="my-5">
                    <h4 class="section-title">Related Stories</h4>
                    <div class="row g-3">
                        <?php foreach ($related_posts as $rp): if ($rp['id'] == $post['id']) continue; ?>
                            <div class="col-md-4">
                                <div class="news-card rounded p-0 h-100">
                                    <div class="news-card-img-wrapper" style="aspect-ratio: 16/10;">
                                        <img src="<?= htmlspecialchars($rp['featured_image']) ?>" alt="">
                                    </div>
                                    <div class="p-2">
                                        <h6 class="news-title mb-1" style="font-size: 0.9rem;"><a href="article.php?slug=<?= $rp['slug'] ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($rp['title']) ?></a></h6>
                                        <small class="text-muted"><?= time_ago($rp['publish_date']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Comments Section -->
                <?php 
                $global_comments_enabled = (get_setting('enable_comments', '1') === '1');
                if ($global_comments_enabled && !empty($post['allow_comments'])): 
                ?>
                <div class="my-5 pt-4 border-top" id="comments">
                    <h4 class="fw-bold mb-4"><i class="bi bi-chat-left-text-fill text-danger me-2"></i> Reader Comments (<?= count($comments) ?>)</h4>
                    
                    <!-- Approved Comments List -->
                    <?php if (!empty($comments)): ?>
                        <div class="mb-4">
                            <?php foreach ($comments as $c): ?>
                                <div class="p-3 bg-light rounded border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($c['name']) ?></strong>
                                        <small class="text-muted"><?= time_ago($c['created_at']) ?></small>
                                    </div>
                                    <p class="mb-0 text-secondary" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Post Comment Form -->
                    <div class="card p-4 border shadow-sm">
                        <h5 class="fw-bold mb-3">Leave a Reply</h5>
                        <form id="comment-form">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Your Name *</label>
                                    <input type="text" name="name" class="form-control" required placeholder="John Doe">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Comment *</label>
                                    <textarea name="comment" class="form-control" rows="4" required placeholder="Type your comment here..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-danger px-4 fw-bold">Post Comment</button>
                                </div>
                            </div>
                        </form>
                        <div id="comment-alert" class="alert d-none mt-3"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Print Newspaper Footer (Only visible when printing) -->
                <div class="d-none d-print-block mt-5 pt-3 border-top border-dark text-center small">
                    <p class="mb-1 fw-bold"><?= htmlspecialchars(get_setting('site_name', 'Daily Horizon')) ?> &bull; Online Digital Print Edition</p>
                    <p class="mb-0 text-muted">Printed on: <?= date('d M Y, h:i A') ?> &bull; Article URL: http://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?></p>
                </div>

            </article>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
