<?php
/**
 * Global Helper & Database Query Functions
 */

require_once __DIR__ . '/db.php';

// Installation status check
function is_installed() {
    return file_exists(__DIR__ . '/../installed.lock');
}

function check_install_status() {
    $script_name = basename($_SERVER['PHP_SELF'] ?? '');
    $is_installer = ($script_name === 'install.php');
    $installed = is_installed();

    if (!$installed && !$is_installer) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/admin/') !== false) {
            header('Location: ../install.php');
        } else {
            header('Location: install.php');
        }
        exit;
    }
}

// Fetch setting value
function get_setting($key, $default = '') {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

// Update setting
function set_setting($key, $value) {
    $db = get_db_connection();
    if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    } else {
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    }
    return $stmt->execute([$key, $value]);
}

// Sanitize inputs
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Convert image/media URL to root-relative URL if it's a local upload
function get_media_url($url, $fallback = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80') {
    if (empty($url)) return $fallback;
    $url = trim($url);
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0 || strpos($url, '//') === 0 || strpos($url, 'data:') === 0) {
        return $url;
    }
    return '/' . ltrim($url, '/');
}

// Generate URL slug
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'news-' . time() : $text;
}

// Get categories
function get_categories($parent_id = 0, $only_active = true) {
    $db = get_db_connection();
    $sql = "SELECT * FROM categories WHERE parent_id = ?";
    if ($only_active) {
        $sql .= " AND status = 1";
    }
    $sql .= " ORDER BY cat_order ASC, name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$parent_id]);
    return $stmt->fetchAll();
}

// Get single category by ID or Slug
function get_category($id_or_slug) {
    $db = get_db_connection();
    if (is_numeric($id_or_slug)) {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    } else {
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    }
    $stmt->execute([$id_or_slug]);
    return $stmt->fetch();
}

function format_post_for_lang($post, $lang = null) {
    if (!$post) return $post;
    if ($lang === null) {
        $lang = get_current_lang();
    }
    if ($lang === 'en') {
        if (!empty($post['title_en'])) {
            $post['title'] = $post['title_en'];
        }
        if (!empty($post['short_description_en'])) {
            $post['short_description'] = $post['short_description_en'];
        }
        if (!empty($post['content_en'])) {
            $post['content'] = $post['content_en'];
        }
    } else {
        if (empty($post['title']) && !empty($post['title_en'])) {
            $post['title'] = $post['title_en'];
        }
        if (empty($post['short_description']) && !empty($post['short_description_en'])) {
            $post['short_description'] = $post['short_description_en'];
        }
        if (empty($post['content']) && !empty($post['content_en'])) {
            $post['content'] = $post['content_en'];
        }
    }
    return $post;
}

// Fetch posts with flexible options
function get_posts($options = []) {
    $db = get_db_connection();
    $limit = isset($options['limit']) ? (int)$options['limit'] : 10;
    $offset = isset($options['offset']) ? (int)$options['offset'] : 0;
    $category_id = isset($options['category_id']) ? (int)$options['category_id'] : 0;
    $subcategory_id = isset($options['subcategory_id']) ? (int)$options['subcategory_id'] : 0;
    $is_featured = isset($options['is_featured']) ? (int)$options['is_featured'] : null;
    $is_breaking = isset($options['is_breaking']) ? (int)$options['is_breaking'] : null;
    $is_trending = isset($options['is_trending']) ? (int)$options['is_trending'] : null;
    $is_popular = isset($options['is_popular']) ? (int)$options['is_popular'] : null;
    $search = isset($options['search']) ? trim($options['search']) : '';
    $status = isset($options['status']) ? $options['status'] : 'published';

    $where = ["p.status = ?"];
    $params = [$status];

    if ($category_id > 0) {
        $where[] = "(p.category_id = ? OR c.parent_id = ?)";
        $params[] = $category_id;
        $params[] = $category_id;
    }

    if ($subcategory_id > 0) {
        $where[] = "p.subcategory_id = ?";
        $params[] = $subcategory_id;
    }

    if ($is_featured !== null) {
        $where[] = "p.is_featured = ?";
        $params[] = $is_featured;
    }

    if ($is_breaking !== null) {
        $where[] = "p.is_breaking = ?";
        $params[] = $is_breaking;
    }

    if ($is_trending !== null) {
        $where[] = "p.is_trending = ?";
        $params[] = $is_trending;
    }

    if ($is_popular !== null) {
        $where[] = "p.is_popular = ?";
        $params[] = $is_popular;
    }

    if (!empty($options['date'])) {
        $where[] = "DATE(p.publish_date) = ?";
        $params[] = trim($options['date']);
    }

    if (!empty($search)) {
        $where[] = "(p.title LIKE ? OR p.short_description LIKE ? OR p.content LIKE ? OR p.tags LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = implode(" AND ", $where);
    $allowedOrders = [
        'p.publish_date DESC, p.id DESC' => 'p.publish_date DESC, p.id DESC',
        'p.publish_date ASC' => 'p.publish_date ASC',
        'p.views DESC' => 'p.views DESC',
        'p.views ASC' => 'p.views ASC',
        'p.id DESC' => 'p.id DESC',
        'p.id ASC' => 'p.id ASC',
        'p.title ASC' => 'p.title ASC',
        'p.title DESC' => 'p.title DESC'
    ];
    $rawOrder = isset($options['order_by']) ? $options['order_by'] : 'p.publish_date DESC, p.id DESC';
    $orderBy = isset($allowedOrders[$rawOrder]) ? $allowedOrders[$rawOrder] : 'p.publish_date DESC, p.id DESC';

    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.avatar as author_avatar
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    $lang = get_current_lang();
    foreach ($posts as &$p) {
        $p = format_post_for_lang($p, $lang);
    }
    return $posts;
}

// Get total post count
function get_posts_count($options = []) {
    $db = get_db_connection();
    $category_id = isset($options['category_id']) ? (int)$options['category_id'] : 0;
    $search = isset($options['search']) ? trim($options['search']) : '';
    $status = isset($options['status']) ? $options['status'] : 'published';

    $where = ["p.status = ?"];
    $params = [$status];

    if ($category_id > 0) {
        $where[] = "p.category_id = ?";
        $params[] = $category_id;
    }

    if (!empty($search)) {
        $where[] = "(p.title LIKE ? OR p.title_en LIKE ? OR p.short_description LIKE ? OR p.content LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = implode(" AND ", $where);
    $sql = "SELECT COUNT(*) as total FROM posts p WHERE {$whereClause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ? (int)$row['total'] : 0;
}

// Get post by slug
function get_post_by_slug($slug) {
    $db = get_db_connection();
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.avatar as author_avatar, u.bio as author_bio
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.slug = ? AND p.status = 'published'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    return format_post_for_lang($post);
}

// Get post by ID
function get_post_by_id($id) {
    $db = get_db_connection();
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.avatar as author_avatar, u.bio as author_bio
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.id = ? AND p.status = 'published'";
    $stmt = $db->prepare($sql);
    $stmt->execute([(int)$id]);
    $post = $stmt->fetch();
    return format_post_for_lang($post);
}

// Increment post view counter
function increment_views($post_id) {
    $db = get_db_connection();
    $stmt = $db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
}

// Get breaking news ticker
function get_breaking_news($limit = 6) {
    return get_posts(['is_breaking' => 1, 'limit' => $limit]);
}

// Get advertisement by position
function get_ad($position) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM ads WHERE position = ? AND status = 1 LIMIT 1");
    $stmt->execute([$position]);
    return $stmt->fetch();
}

// Render advertisement banner
function render_ad($position, $extra_class = '') {
    $ad = get_ad($position);
    if (!$ad || empty($ad['status'])) return '';

    // Check custom header ad height setting if header ad
    $max_height_style = '';
    if (strpos($position, 'header') !== false) {
        $custom_h = get_setting('header_ad_height', '');
        if (!empty($custom_h) && is_numeric($custom_h)) {
            $max_height_style = "max-height: " . (int)$custom_h . "px;";
        }
    }

    // Custom Width & Height support from ad record
    $width_style = !empty($ad['width']) ? "width: " . (is_numeric($ad['width']) ? $ad['width'] . "px" : $ad['width']) . ";" : "max-width: 100%;";
    $height_style = !empty($ad['height']) ? "height: " . (is_numeric($ad['height']) ? $ad['height'] . "px" : $ad['height']) . ";" : (!empty($max_height_style) ? $max_height_style : "max-height: 140px;");

    $output = "<div class='ad-container text-center my-2 {$extra_class}' data-ad-position='" . htmlspecialchars($position) . "'>";
    if ($ad['ad_type'] === 'code' && !empty($ad['ad_code'])) {
        $output .= "<div class='ad-code-wrapper overflow-auto d-inline-block w-100' style='max-width: 100%;'>" . $ad['ad_code'] . "</div>";
    } elseif ($ad['ad_type'] === 'image' && !empty($ad['image_url'])) {
        $target = !empty($ad['target_url']) ? $ad['target_url'] : '#';
        $img_url = get_media_url($ad['image_url']);
        $output .= "<a href='" . htmlspecialchars($target) . "' target='_blank' rel='nofollow' class='d-inline-block' style='max-width: 100%;'>
            <img src='" . htmlspecialchars($img_url) . "' alt='" . htmlspecialchars($ad['title'] ?: 'Advertisement') . "' class='img-fluid rounded shadow-sm border' style='{$width_style} {$height_style} object-fit: contain;' />
        </a>";
    }
    $output .= "</div>";
    return $output;
}

// Get navigation menus
function get_menus($location = 'header') {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM menus WHERE location = ? AND parent_id = 0 AND status = 1 ORDER BY item_order ASC");
    $stmt->execute([$location]);
    $parents = $stmt->fetchAll();

    foreach ($parents as &$parent) {
        $stmtChild = $db->prepare("SELECT * FROM menus WHERE parent_id = ? AND status = 1 ORDER BY item_order ASC");
        $stmtChild->execute([$parent['id']]);
        $parent['children'] = $stmtChild->fetchAll();
    }
    return $parents;
}

// Time Ago Formatter
function time_ago($datetime) {
    $lang = get_current_lang();
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return $lang === 'bn' ? "এইমাত্র" : "Just now";
    if ($diff < 3600) {
        $m = floor($diff / 60);
        return $lang === 'bn' ? convert_en_to_bn($m) . " মিনিট আগে" : $m . " mins ago";
    }
    if ($diff < 86400) {
        $h = floor($diff / 3600);
        return $lang === 'bn' ? convert_en_to_bn($h) . " ঘন্টা আগে" : $h . " hours ago";
    }
    if ($diff < 604800) {
        $d = floor($diff / 86400);
        return $lang === 'bn' ? convert_en_to_bn($d) . " দিন আগে" : $d . " days ago";
    }
    
    return $lang === 'bn' ? convert_en_to_bn(date("j M, Y", $time)) : date("M j, Y", $time);
}

// Language helper
function get_current_lang() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $enable_trans = get_setting('enable_translation', '1');
    $default_lang = get_setting('default_language', 'en');

    if ($enable_trans === '0') {
        return in_array($default_lang, ['en', 'bn']) ? $default_lang : 'en';
    }

    if (isset($_GET['lang']) && in_array($_GET['lang'], ['bn', 'en'])) {
        $_SESSION['site_lang'] = $_GET['lang'];
    }
    return isset($_SESSION['site_lang']) ? $_SESSION['site_lang'] : (in_array($default_lang, ['en', 'bn']) ? $default_lang : 'en');
}

function translate_ui_text($str) {
    if (empty($str)) return '';
    $lang = get_current_lang();
    
    $bnToEn = [
        'প্রচ্ছদ' => 'Home',
        'জরুরি খবর' => 'Breaking News',
        'সর্বশেষ' => 'Latest News',
        'সর্বশেষ সংবাদ' => 'Latest News',
        'জনপ্রিয়' => 'Popular News',
        'জাতীয়' => 'National',
        'জাতীয়' => 'National',
        'জাতীয় সংবাদ' => 'National News',
        'বরিশাল বিভাগ' => 'Barishal Division',
        'বরিশাল নগরী' => 'Barishal City',
        'রাজনীতি' => 'Politics',
        'অর্থনীতি' => 'Business & Economy',
        'আন্তর্জাতিক' => 'World News',
        'খেলাধুলো' => 'Sports',
        'খেলা' => 'Sports',
        'খেলাধুলা' => 'Sports',
        'বিনোদন' => 'Entertainment',
        'লাইফস্টাইল' => 'Lifestyle',
        'জীবনযাপন' => 'Lifestyle',
        'প্রযুক্তি' => 'Technology',
        'তথ্যপ্রযুক্তি' => 'IT & Tech',
        'বিজ্ঞান ও প্রযুক্তি' => 'Science & Tech',
        'সারাদেশ' => 'Countrywide',
        'চাকরি' => 'Jobs',
        'শিক্ষা' => 'Education',
        'ভিডিও' => 'Videos',
        'ছবি' => 'Photos',
        'গ্যালারি' => 'Gallery',
        'ফটো গ্যালারি' => 'Photo Gallery',
        'ভিডিও গ্যালারি' => 'Video Gallery',
        'ই-পেপার' => 'E-Paper',
        'দৈনিক ই-পেপার' => 'Daily E-Paper',
        'যোগাযোগ' => 'Contact Us',
        'যোগাযোগ করুন' => 'Get in Touch',
        'আমাদের সম্পর্কে' => 'About Us',
        'গোপনীয়তা নীতি' => 'Privacy Policy',
        'ব্যবহারের শর্তাবলী' => 'Terms of Service',
        'সম্পাদক' => 'Editor',
        'প্রধান সম্পাদক' => 'Editor-in-Chief',
        'প্রকাশক' => 'Publisher',
        'বিজ্ঞাপন' => 'Advertisement',
        'অনুসন্ধান' => 'Search',
        'সংবাদ খুঁজুন...' => 'Search news...',
        'খবর খুঁজুন' => 'Search News',
        'বিস্তারিত' => 'Read More',
        'পড়ুন' => 'Read More',
        'আরও পড়ুন' => 'Read More',
        'আরও খবর' => 'More News',
        'সম্পর্কিত খবর' => 'Related News',
        'মন্তব্য করুন' => 'Leave a Comment',
        'আপনার মতামত দিন' => 'Share Your Opinion',
        'নাম' => 'Name',
        'ইমেইল' => 'Email',
        'বিষয়' => 'Subject',
        'বার্তা' => 'Message',
        'পাঠান' => 'Send Message',
        'বার্তা পাঠান' => 'Send Message',
        'শেয়ার করুন' => 'Share',
        'শেয়ার করুন' => 'Share',
        'প্রিন্ট' => 'Print Article',
        'কপি লিঙ্ক' => 'Copy Link',
        'প্রকাশিত' => 'Published',
        'আপডেট' => 'Updated',
        'ভিউ' => 'Views',
        'পাঠক সংখ্যা' => 'Total Reads',
        'সকল বিভাগ' => 'All Categories',
        'সব' => 'All',
        'অফিস' => 'Office',
        'ঠিকানা' => 'Address',
        'ফোন' => 'Phone',
        'মোবাইল' => 'Mobile',
        'হটলাইন' => 'Hotline'
    ];

    $enToBn = [
        'Home' => 'প্রচ্ছদ',
        'National' => 'জাতীয়',
        'National News' => 'জাতীয় সংবাদ',
        'Barishal Division' => 'বরিশাল বিভাগ',
        'Barisal Division' => 'বরিশাল বিভাগ',
        'Barishal City' => 'বরিশাল নগরী',
        'Barisal City' => 'বরিশাল নগরী',
        'Politics' => 'রাজনীতি',
        'Business & Economy' => 'অর্থনীতি',
        'Business' => 'অর্থনীতি',
        'Economy' => 'অর্থনীতি',
        'World News' => 'আন্তর্জাতিক',
        'World' => 'আন্তর্জাতিক',
        'Sports' => 'খেলাধুলা',
        'Entertainment' => 'বিনোদন',
        'Lifestyle' => 'জীবনযাপন',
        'Technology' => 'প্রযুক্তি',
        'IT & Tech' => 'তথ্যপ্রযুক্তি',
        'Science & Tech' => 'বিজ্ঞান ও প্রযুক্তি',
        'Opinion' => 'মতামত',
        'Countrywide' => 'সারাদেশ',
        'Jobs' => 'চাকরি',
        'Education' => 'শিক্ষা',
        'Videos' => 'ভিডিও',
        'Video' => 'ভিডিও',
        'Video News' => 'ভিডিও খবর',
        'Photos' => 'ছবি',
        'Photo' => 'ছবি',
        'Photo Gallery' => 'ছবি গ্যালারি',
        'Gallery' => 'গ্যালারি',
        'About Us' => 'আমাদের সম্পর্কে',
        'About' => 'আমাদের সম্পর্কে',
        'Contact Us' => 'যোগাযোগ',
        'Contact' => 'যোগাযোগ'
    ];

    if ($lang === 'en') {
        return isset($bnToEn[$str]) ? $bnToEn[$str] : $str;
    } else {
        return isset($enToBn[$str]) ? $enToBn[$str] : $str;
    }
}

function __($bn, $en = null) {
    $lang = get_current_lang();
    if ($lang === 'en') {
        if ($en !== null) {
            return $en;
        }
        return translate_ui_text($bn);
    }
    if (isset($en)) {
        return translate_ui_text($bn);
    }
    return $bn;
}

function get_category_display_name($name) {
    return translate_ui_text($name);
}

// Convert English numerals & month/day names to Bengali
function convert_en_to_bn($str) {
    $en = ['0','1','2','3','4','5','6','7','8','9','January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর','জানু','ফেব্রু','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টে','অক্টো','নভে','ডিসে','শনিবার','রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার'];
    return str_replace($en, $bn, (string)$str);
}

// Convert Gregorian date to Bengali Era (বঙ্গাব্দ) date
function get_bangla_era_date($timestamp = null) {
    if (!$timestamp) $timestamp = time();
    $day = (int)date('j', $timestamp);
    $month = (int)date('n', $timestamp);
    $year = (int)date('Y', $timestamp);

    $bn_months = ['বৈশাখ', 'জ্যৈষ্ঠ', 'আষাঢ়', 'শ্রাবণ', 'ভাদ্র', 'আশ্বিন', 'কার্তিক', 'অগ্রহায়ণ', 'পৌষ', 'মাঘ', 'ফাল্গুন', 'চৈত্র'];
    $bn_year = ($month < 4 || ($month == 4 && $day < 14)) ? $year - 594 : $year - 593;

    $greg_boishakh = strtotime("{$year}-04-14");
    if ($timestamp < $greg_boishakh) {
        $prev_year = $year - 1;
        $greg_boishakh = strtotime("{$prev_year}-04-14");
    }

    $days_diff = floor(($timestamp - $greg_boishakh) / 86400);
    $month_days = [31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 30, 30];
    
    $is_leap = ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
    if ($is_leap) {
        $month_days[10] = 31; // Falgun in leap year
    }

    $bn_month_index = 0;
    $bn_day = $days_diff + 1;

    for ($i = 0; $i < 12; $i++) {
        if ($bn_day <= $month_days[$i]) {
            $bn_month_index = $i;
            break;
        }
        $bn_day -= $month_days[$i];
    }

    return convert_en_to_bn($bn_day) . ' ' . $bn_months[$bn_month_index] . ' ' . convert_en_to_bn($bn_year);
}

// Full Bangladeshi Newspaper Header Date String
function get_full_bangla_date_string($timestamp = null) {
    if (!$timestamp) $timestamp = time();
    $day_name = date('l', $timestamp);
    $greg_date = date('j F Y', $timestamp);
    
    $bn_day_name = convert_en_to_bn($day_name);
    $bn_greg_date = convert_en_to_bn($greg_date);
    $bn_era_date = get_bangla_era_date($timestamp);

    return "{$bn_day_name}, {$bn_greg_date} / {$bn_era_date} বঙ্গাব্দ";
}

// CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Secure File Upload Handler
function handle_file_upload($file, $subfolder = 'media') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameters.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file sent.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'Exceeded filesize limit (check php.ini upload_max_filesize / post_max_size).'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error.'];
    }

    $allowed_extensions = [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg',
        'mp4', 'webm', 'mov', 'avi', 'mkv',
        'mp3', 'wav', 'ogg',
        'pdf', 'doc', 'docx', 'txt', 'zip'
    ];

    $filename_raw = basename($file['name']);
    $ext = strtolower(pathinfo($filename_raw, PATHINFO_EXTENSION));

    // Security: Strictly ban executable / script extensions
    $forbidden = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar', 'cgi', 'pl', 'py', 'asp', 'aspx', 'exe', 'sh', 'bash', 'htaccess'];
    if (in_array($ext, $forbidden) || !in_array($ext, $allowed_extensions)) {
        return ['success' => false, 'error' => 'Security Warning: Unallowed or dangerous file extension (.' . htmlspecialchars($ext) . ').'];
    }

    $upload_base = __DIR__ . '/../uploads/' . $subfolder . '/';
    if (!is_dir($upload_base)) {
        @mkdir($upload_base, 0755, true);
    }

    // Create protective .htaccess in uploads directory
    $htaccess_file = __DIR__ . '/../uploads/.htaccess';
    if (!file_exists($htaccess_file)) {
        @file_put_contents($htaccess_file, "<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps|phar|cgi|pl|py|exe|sh)$\">\n    Order allow,deny\n    Deny from all\n</FilesMatch>\nOptions -ExecCGI\n");
    }

    $safe_basename = preg_replace('/[^a-zA-Z0-0_-]/', '_', pathinfo($filename_raw, PATHINFO_FILENAME));
    $new_filename = $safe_basename . '_' . date('Ymd_His') . '_' . rand(100, 999) . '.' . $ext;
    $target_file = $upload_base . $new_filename;
    $relative_path = 'uploads/' . $subfolder . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Log to database media table
        try {
            $db = get_db_connection();
            $stmt = $db->prepare("INSERT INTO media (filename, filepath, filetype, filesize, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $new_filename,
                $relative_path,
                $file['type'] ?? ('application/' . $ext),
                (int)$file['size'],
                $_SESSION['admin_id'] ?? 1
            ]);
        } catch (Exception $e) {
            // Ignore DB log error if table differs
        }

        return [
            'success' => true,
            'filename' => $new_filename,
            'filepath' => $relative_path,
            'url' => $relative_path
        ];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file. Check directory permissions.'];
}

// Get all authors / reporters for dropdowns
function get_all_authors() {
    $db = get_db_connection();
    try {
        $stmt = $db->query("SELECT id, username, full_name, role, avatar, bio FROM users ORDER BY full_name ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Get effective display author details for a post
function get_post_display_author($post) {
    $name = !empty($post['custom_author_name']) ? $post['custom_author_name'] : (!empty($post['author_name']) ? $post['author_name'] : 'Staff Reporter');
    $avatar = !empty($post['custom_author_image']) ? $post['custom_author_image'] : (!empty($post['author_avatar']) ? $post['author_avatar'] : '');
    $bio = !empty($post['author_bio']) ? $post['author_bio'] : '';

    if (empty($avatar) || $avatar === 'default-avatar.jpg') {
        $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80';
    }

    return [
        'name' => $name,
        'avatar' => $avatar,
        'bio' => $bio
    ];
}

// Homepage Section Management Functions
function get_homepage_sections($only_active = true) {
    $db = get_db_connection();
    try {
        $sql = "SELECT s.*, c.name as category_name, c.slug as category_slug 
                FROM homepage_sections s 
                LEFT JOIN categories c ON s.category_id = c.id";
        if ($only_active) {
            $sql .= " WHERE s.status = 1";
        }
        $sql .= " ORDER BY s.section_order ASC, s.id ASC";
        $stmt = $db->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    } catch (Exception $e) {
        if (function_exists('ensure_homepage_sections_table')) {
            ensure_homepage_sections_table($db);
        }
        try {
            $sql = "SELECT s.*, c.name as category_name, c.slug as category_slug 
                    FROM homepage_sections s 
                    LEFT JOIN categories c ON s.category_id = c.id";
            if ($only_active) {
                $sql .= " WHERE s.status = 1";
            }
            $sql .= " ORDER BY s.section_order ASC, s.id ASC";
            $stmt = $db->query($sql);
            return $stmt ? $stmt->fetchAll() : [];
        } catch (Exception $e2) {
            return [];
        }
    }
}

function get_homepage_section($id) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT * FROM homepage_sections WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

function save_homepage_section($data) {
    $db = get_db_connection();
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $title = trim($data['title']);
    $category_id = (int)($data['category_id'] ?? 0);
    $post_limit = (int)($data['post_limit'] ?? 5);
    $layout_style = trim($data['layout_style'] ?? 'lead_side_list');
    $status = isset($data['status']) ? (int)$data['status'] : 1;
    $section_order = isset($data['section_order']) ? (int)$data['section_order'] : 0;

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE homepage_sections SET title = ?, category_id = ?, post_limit = ?, layout_style = ?, status = ?, section_order = ? WHERE id = ?");
        return $stmt->execute([$title, $category_id, $post_limit, $layout_style, $status, $section_order, $id]);
    } else {
        if ($section_order == 0) {
            $maxStmt = $db->query("SELECT MAX(section_order) as max_ord FROM homepage_sections");
            $maxRow = $maxStmt ? $maxStmt->fetch() : null;
            $section_order = ($maxRow && isset($maxRow['max_ord'])) ? ((int)$maxRow['max_ord'] + 1) : 1;
        }
        $stmt = $db->prepare("INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$title, $category_id, $post_limit, $layout_style, $section_order, $status]);
    }
}

function delete_homepage_section($id) {
    $db = get_db_connection();
    $stmt = $db->prepare("DELETE FROM homepage_sections WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

function reorder_homepage_sections($order_map) {
    $db = get_db_connection();
    $stmt = $db->prepare("UPDATE homepage_sections SET section_order = ? WHERE id = ?");
    foreach ($order_map as $id => $order) {
        $stmt->execute([(int)$order, (int)$id]);
    }
    return true;
}

// YouTube and Video Helper Functions
function parse_youtube_id($url) {
    if (empty($url)) return false;
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
        return $matches[1];
    }
    return false;
}

function get_youtube_embed_url($url) {
    $id = parse_youtube_id($url);
    if ($id) {
        return "https://www.youtube.com/embed/" . $id . "?autoplay=0&rel=0";
    }
    return $url;
}

function get_youtube_thumbnail($url, $custom_thumb = '') {
    if (!empty($custom_thumb)) {
        return $custom_thumb;
    }
    $id = parse_youtube_id($url);
    if ($id) {
        return "https://img.youtube.com/vi/" . $id . "/hqdefault.jpg";
    }
    return 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80';
}

function get_videos($limit = 6) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT * FROM videos ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $vids = $stmt->fetchAll();
        foreach ($vids as &$v) {
            $v['embed_url'] = get_youtube_embed_url($v['video_url']);
            $v['thumb_url'] = get_youtube_thumbnail($v['video_url'], $v['thumbnail'] ?? '');
        }
        return $vids;
    } catch (Exception $e) {
        return [];
    }
}

function get_gallery_albums_with_photos($limit = 6) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT * FROM gallery_albums ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $albums = $stmt->fetchAll();

        foreach ($albums as &$alb) {
            $pStmt = $db->prepare("SELECT * FROM gallery_photos WHERE album_id = ? ORDER BY id ASC");
            $pStmt->execute([$alb['id']]);
            $alb['photos'] = $pStmt->fetchAll();
            $alb['photo_count'] = count($alb['photos']);
        }
        return $albums;
    } catch (Exception $e) {
        return [];
    }
}

function get_homepage_photos($limit = 8) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT p.*, a.title as album_title, a.slug as album_slug FROM gallery_photos p LEFT JOIN gallery_albums a ON p.album_id = a.id ORDER BY p.id DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}



