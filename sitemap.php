<?php
// Sitemap XML Generator
header("Content-Type: application/xml; charset=utf-8");
require_once __DIR__ . '/includes/functions.php';

$site_url = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://' . $_SERVER['HTTP_HOST'];
$db = get_db_connection();

// Fetch posts
$posts = [];
try {
    $posts = $db->query("SELECT slug, updated_at, publish_date FROM posts WHERE status = 'published' ORDER BY id DESC LIMIT 1000")->fetchAll();
} catch (Exception $e) {}

// Fetch categories
$categories = [];
try {
    $categories = $db->query("SELECT slug FROM categories WHERE status = 1 ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {}

// Fetch custom pages
$pages = [];
try {
    $pages = $db->query("SELECT slug, created_at FROM pages WHERE status = 1 ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {}

// Fetch gallery albums
$albums = [];
try {
    $albums = $db->query("SELECT slug, created_at FROM gallery_albums ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {}

// Output XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemap.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= htmlspecialchars($site_url) ?>/</loc>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url) ?>/index.php</loc>
        <changefreq>always</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Static Sections -->
    <url>
        <loc><?= htmlspecialchars($site_url) ?>/gallery.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url) ?>/video.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url) ?>/contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Categories -->
    <?php foreach ($categories as $c): ?>
        <url>
            <loc><?= htmlspecialchars($site_url) ?>/category.php?slug=<?= urlencode($c['slug']) ?></loc>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
    <?php endforeach; ?>

    <!-- Custom Pages -->
    <?php foreach ($pages as $pg): ?>
        <url>
            <loc><?= htmlspecialchars($site_url) ?>/page.php?slug=<?= urlencode($pg['slug']) ?></loc>
            <changefreq>weekly</changefreq>
            <priority>0.6</priority>
        </url>
    <?php endforeach; ?>

    <!-- Published Articles -->
    <?php foreach ($posts as $p): 
        $lastmod = !empty($p['updated_at']) ? $p['updated_at'] : $p['publish_date'];
        $dateStr = date('Y-m-d', strtotime($lastmod));
    ?>
        <url>
            <loc><?= htmlspecialchars($site_url) ?>/article.php?slug=<?= urlencode($p['slug']) ?></loc>
            <lastmod><?= $dateStr ?></lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    <?php endforeach; ?>
</urlset>
