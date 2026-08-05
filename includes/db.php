<?php
/**
 * Database Connection & PDO Instance Helper
 * Supports both MySQL and SQLite databases.
 */

if (!defined('DB_TYPE')) {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } elseif (file_exists(__DIR__ . '/../.env')) {
        $env_path = __DIR__ . '/../.env';
        $env_lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env_vars = [];
        foreach ($env_lines as $line) {
            $line = trim($line);
            if (!empty($line) && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
                list($k, $v) = explode('=', $line, 2);
                $env_vars[trim($k)] = trim($v, "\"' ");
            }
        }
        define('DB_TYPE', $env_vars['DB_TYPE'] ?? 'sqlite');
        define('DB_HOST', $env_vars['DB_HOST'] ?? 'localhost');
        define('DB_NAME', $env_vars['DB_NAME'] ?? 'newsportal');
        define('DB_USER', $env_vars['DB_USER'] ?? 'root');
        define('DB_PASS', $env_vars['DB_PASS'] ?? '');
        define('DB_SQLITE_PATH', __DIR__ . '/../database.sqlite');
    } else {
        define('DB_TYPE', 'sqlite');
        define('DB_SQLITE_PATH', __DIR__ . '/../database.sqlite');
    }
}

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $connected = false;
        if (defined('DB_TYPE') && DB_TYPE === 'mysql' && extension_loaded('pdo_mysql')) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Auto-check if MySQL tables exist, if empty auto-import database.sql or schema.sql
                try {
                    $check = $pdo->query("SHOW TABLES LIKE 'users'");
                    if (!$check || $check->rowCount() === 0) {
                        $sql_file = __DIR__ . '/../database.sql';
                        if (!file_exists($sql_file)) {
                            $sql_file = __DIR__ . '/../schema.sql';
                        }
                        if (file_exists($sql_file)) {
                            import_sql_file($pdo, $sql_file);
                        }
                    }
                } catch (Throwable $e) {}

                ensure_custom_author_columns($pdo);
                ensure_all_ad_positions_and_settings($pdo);
                ensure_homepage_sections_table($pdo);
                ensure_contact_messages_table($pdo);
                ensure_views_and_date_columns($pdo);
                ensure_default_menus($pdo);
                $connected = true;
            } catch (Throwable $e) {
                // Fallback to SQLite if enabled
                $pdo = null;
            }
        }

        if (!$connected) {
            if (extension_loaded('pdo_sqlite')) {
                // Default to SQLite
                $db_file = defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : __DIR__ . '/../database.sqlite';
                $pdo = new PDO("sqlite:" . $db_file, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                try {
                    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                    if (!$check || !$check->fetch()) {
                        initialize_sqlite_db($pdo);
                    }
                } catch (Throwable $e) {
                    initialize_sqlite_db($pdo);
                }

                ensure_custom_author_columns($pdo);
                ensure_all_ad_positions_and_settings($pdo);
                ensure_homepage_sections_table($pdo);
                ensure_contact_messages_table($pdo);
                ensure_database_indexes($pdo);
                ensure_default_menus($pdo);
                $connected = true;
            } else {
                if (!file_exists(__DIR__ . '/../installed.lock') && basename($_SERVER['PHP_SELF'] ?? '') === 'install.php') {
                    return null;
                }
            }
        }
        return $pdo;
    } catch (Throwable $e) {
        if (!file_exists(__DIR__ . '/../installed.lock') && basename($_SERVER['PHP_SELF'] ?? '') === 'install.php') {
            return null;
        }
        die("<div style='font-family:sans-serif; padding:20px; background:#fef2f2; color:#991b1b; border:1px solid #f87171; border-radius:8px; margin:20px;'>
            <h3>Database Connection Failed</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please check your <code>config.php</code> or run <a href='install.php'>install.php</a>.</p>
        </div>");
    }
}

function initialize_sqlite_db($pdo) {
    // Basic SQLite tables compatible with schema
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT DEFAULT 'reporter',
        avatar TEXT DEFAULT 'default-avatar.jpg',
        bio TEXT,
        status INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parent_id INTEGER DEFAULT 0,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT,
        image TEXT,
        cat_order INTEGER DEFAULT 0,
        show_in_menu INTEGER DEFAULT 1,
        status INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        title_en TEXT,
        slug TEXT UNIQUE NOT NULL,
        short_description TEXT NOT NULL,
        short_description_en TEXT,
        content TEXT NOT NULL,
        content_en TEXT,
        category_id INTEGER NOT NULL,
        subcategory_id INTEGER DEFAULT 0,
        author_id INTEGER NOT NULL,
        custom_author_name TEXT,
        custom_author_image TEXT,
        featured_image TEXT,
        gallery_images TEXT,
        tags TEXT,
        is_featured INTEGER DEFAULT 0,
        is_breaking INTEGER DEFAULT 0,
        is_trending INTEGER DEFAULT 0,
        is_popular INTEGER DEFAULT 0,
        allow_comments INTEGER DEFAULT 1,
        views INTEGER DEFAULT 0,
        seo_title TEXT,
        meta_description TEXT,
        meta_keywords TEXT,
        og_image TEXT,
        status TEXT DEFAULT 'published',
        publish_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        content TEXT NOT NULL,
        meta_title TEXT,
        meta_description TEXT,
        status INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS menus (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        location TEXT NOT NULL,
        title TEXT NOT NULL,
        url TEXT NOT NULL,
        parent_id INTEGER DEFAULT 0,
        item_order INTEGER DEFAULT 0,
        target TEXT DEFAULT '_self',
        status INTEGER DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS ads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        position TEXT NOT NULL,
        title TEXT NOT NULL,
        ad_type TEXT DEFAULT 'image',
        ad_code TEXT,
        image_url TEXT,
        target_url TEXT,
        impressions INTEGER DEFAULT 0,
        clicks INTEGER DEFAULT 0,
        status INTEGER DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        comment TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS media (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        filepath TEXT NOT NULL,
        filetype TEXT NOT NULL,
        filesize INTEGER NOT NULL,
        uploaded_by INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS gallery_albums (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        cover_image TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS gallery_photos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        album_id INTEGER NOT NULL,
        photo_path TEXT NOT NULL,
        caption TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS videos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        video_url TEXT NOT NULL,
        embed_code TEXT,
        thumbnail TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT
    );

    CREATE TABLE IF NOT EXISTS subscribers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        status INTEGER DEFAULT 1,
        subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $pdo->exec($sql);

    // Seed default admin user (password: admin123)
    $passHash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT OR IGNORE INTO users (id, username, email, password, full_name, role) VALUES (1, 'admin', 'admin@newsportal.com', '$passHash', 'Super Administrator', 'admin')");

    // Seed categories
    $pdo->exec("INSERT OR IGNORE INTO categories (id, parent_id, name, slug, cat_order, status) VALUES 
        (1, 0, 'National', 'national', 1, 1),
        (2, 0, 'Politics', 'politics', 2, 1),
        (3, 0, 'World', 'world', 3, 1),
        (4, 0, 'Business', 'business', 4, 1),
        (5, 0, 'Sports', 'sports', 5, 1),
        (6, 0, 'Technology', 'technology', 6, 1),
        (7, 0, 'Entertainment', 'entertainment', 7, 1),
        (8, 0, 'Lifestyle', 'lifestyle', 8, 1)");

    // Seed default settings
    $settings = [
        'site_name' => 'Daily Horizon News Portal',
        'site_title' => 'Daily Horizon - Truth First, Always Ahead',
        'meta_description' => 'Daily Horizon is a leading digital newspaper powered and operated by HosterCube Ltd, delivering breaking news, national updates, politics, sports, business, tech, and entertainment news 24/7.',
        'meta_keywords' => 'news portal, breaking news, national news, sports news, technology, world news, daily horizon, hostercube',
        'editor_name' => 'M. A. Rahman',
        'publisher_name' => 'HosterCube Ltd.',
        'chief_editor' => 'K. H. Chowdhury',
        'address' => 'Level 8, HosterCube Tower, 42 Commercial Area, Dhaka, Bangladesh',
        'phone' => '+880 2 98765432',
        'mobile' => '+880 1711 000000',
        'email' => 'contact@hostercube.com',
        'office_time' => '24/7 Newsroom Operations',
        'facebook' => 'https://facebook.com/hostercube',
        'twitter' => 'https://twitter.com/hostercube',
        'youtube' => 'https://youtube.com/hostercube',
        'instagram' => 'https://instagram.com/hostercube',
        'linkedin' => 'https://linkedin.com/company/hostercube',
        'whatsapp' => '+8801711000000',
        'telegram' => 'https://t.me/hostercube',
        'copyright' => '© 2026 HosterCube Ltd. All rights reserved.',
        'footer_text' => 'Daily Horizon - Powered and hosted by HosterCube Ltd. Your trusted source for unbiased reporting and breaking news.',
        'google_map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.890244243013!2d90.3888!3d23.7508!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDQ1JzAyLjkiTiA5MMKwMjMnMTkuNyJF!5e0!3m2!1sen!2sbd!4v1620000000000!5m2!1sen!2sbd'
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    // Seed sample posts
    $stmtPost = $pdo->prepare("INSERT OR IGNORE INTO posts 
        (id, title, slug, short_description, content, category_id, author_id, featured_image, tags, is_featured, is_breaking, is_trending, is_popular, views, status, publish_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', datetime('now'))");

    $stmtPost->execute([
        1,
        'Global Economic Summit Reaches Landmark Climate & Green Finance Agreement',
        'global-economic-summit-reaches-landmark-climate-green-finance-agreement',
        'World leaders and financial ministers assemble to approve a $500 billion renewable transition fund for emerging markets.',
        '<p><strong>GENEVA —</strong> Delegates from over 120 nations at the Global Economic Accord have unanimously voted to enact a historical green finance policy aimed at accelerating clean energy infrastructure across developing economies.</p><p>The landmark treaty mobilizes $500 billion over the next decade, focusing heavily on solar power grids, off-shore wind installations, and climate resilience projects along vulnerable coastal regions.</p><h3>Key Highlights of the Accord</h3><ul><li>$500 Billion committed fund through multilateral development banks.</li><li>Zero-tariff policies on cross-border green technology components.</li><li>Carbon offsetting frameworks standardized across international exchanges.</li></ul>',
        4, 1,
        'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=1200&auto=format&fit=crop&q=80',
        'Economy, Climate, Finance, Global',
        1, 1, 1, 1, 1420
    ]);

    $stmtPost->execute([
        2,
        'National High-Speed Rail Network Project Phase 2 Commences Mega Construction',
        'national-high-speed-rail-network-project-phase-2-commences-mega-construction',
        'Infrastructure authority launches construction of the 350 km bullet train corridor linking major commercial ports and industrial hubs.',
        '<p><strong>DHAKA —</strong> High-level government officials today inaugurated Phase 2 of the Mega Express Railway line, promising to cut travel duration between key economic hubs from seven hours down to just 90 minutes.</p><p>The $8.2 billion project features advanced magnetic levitation technology, elevated viaduct bridges, and eco-friendly smart train stations equipped with solar rooftops.</p>',
        1, 1,
        'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=1200&auto=format&fit=crop&q=80',
        'National, Infrastructure, Transport, MegaProject',
        1, 1, 1, 1, 980
    ]);

    $stmtPost->execute([
        3,
        'National Cricket Team Secures Thrilling Last-Over Victory in International Cup',
        'national-cricket-team-secures-thrilling-last-over-victory-in-international-cup',
        'A sensational final over six seals a memorable 3-wicket win in front of a roaring home crowd of 45,000 fans.',
        '<p>In one of the most tense finishes in recent sporting history, the national cricket team pulled off an unbelievable chase, scoring 14 runs off the final four deliveries to claim the championship trophy.</p><p>Star all-rounder Rahat Chowdhury played a masterclass unbeaten knock of 84 runs off 48 balls, hitting six boundaries and four mammoth sixes.</p>',
        5, 1,
        'https://images.unsplash.com/photo-1531415074968-036ba1b575da?w=1200&auto=format&fit=crop&q=80',
        'Sports, Cricket, Trophy, Victory',
        1, 0, 1, 1, 2350
    ]);

    $stmtPost->execute([
        4,
        'Next-Generation AI Model Breaks World Records in Medical Diagnostics',
        'next-generation-ai-model-breaks-world-records-in-medical-diagnostics',
        'New artificial intelligence system achieves 99.4% accuracy in detecting early-stage diseases from imaging scans in seconds.',
        '<p>Medical researchers and computer scientists have unveiled a groundbreaking AI diagnostic platform that can analyze MRI, CT scans, and X-rays with unprecedented precision.</p><p>The technology is set to assist doctors in remote hospitals by providing instant, highly accurate diagnostic evaluations.</p>',
        6, 1,
        'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&auto=format&fit=crop&q=80',
        'Technology, AI, HealthTech, Innovation',
        0, 0, 1, 1, 1890
    ]);

    $stmtPost->execute([
        5,
        'Annual International Film Festival Opens with Gala Premiere of Epic Historical Drama',
        'annual-international-film-festival-opens-with-gala-premiere-of-epic-historical-drama',
        'Renowned directors, actors, and critics gather as over 150 feature films from 40 countries showcase over the week.',
        '<p>The city turned into a cinematic celebration last evening as the 12th International Film Festival officially opened its red carpet ceremony.</p><p>Critically acclaimed director A. Khan presented his masterpiece historical drama to a standing ovation at the grand theater auditorium.</p>',
        7, 1,
        'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1200&auto=format&fit=crop&q=80',
        'Entertainment, Cinema, FilmFestival, Culture',
        0, 0, 0, 1, 1120
    ]);

    $stmtPost->execute([
        6,
        '5 Essential Daily Habits for Boosting Longevity & Mental Clarity',
        '5-essential-daily-habits-for-boosting-longevity-mental-clarity',
        'Health experts share simple science-backed routine tweaks that improve cardiovascular wellness and cognitive stamina.',
        '<p>Living a longer, healthier life doesn’t require extreme lifestyle overhauls. According to leading wellness researchers, consistent small daily habits produce the most profound long-term physical and mental benefits.</p>',
        8, 1,
        'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=1200&auto=format&fit=crop&q=80',
        'Lifestyle, Health, Fitness, Wellness',
        0, 0, 0, 0, 750
    ]);

    // Seed sample pages
    $pdo->exec("INSERT OR IGNORE INTO pages (id, title, slug, content) VALUES 
        (1, 'About Us', 'about-us', '<h2>Welcome to Daily Horizon</h2><p>Daily Horizon is a premier independent news organization dedicated to providing fast, factual, and unbiased news. Our team of experienced investigative journalists and editors work tirelessly around the clock to bring you verified reports from every corner of the nation and the world.</p>'),
        (2, 'Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>At Daily Horizon, accessible from dailyhorizon.com, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by Daily Horizon and how we use it.</p>'),
        (3, 'Terms & Conditions', 'terms', '<h2>Terms and Conditions</h2><p>Welcome to Daily Horizon! These terms and conditions outline the rules and regulations for the use of Daily Horizon Website.</p>'),
        (4, 'Contact Us', 'contact-us', '<h2>Get in Touch</h2><p>Have a news tip, press release, or inquiry? Reach out to our editorial team or administrative staff using the details below or via our online contact form.</p>')");

    // Seed sample videos
    $pdo->exec("INSERT OR IGNORE INTO videos (id, title, slug, video_url, thumbnail, description) VALUES
        (1, 'Exclusive Special Report: Inside the High-Tech Smart Grid Facility', 'inside-the-high-tech-smart-grid-facility', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800&auto=format&fit=crop&q=80', 'Watch our special on-ground documentary exploring automated power distribution.')");

    // Seed ads
    $pdo->exec("INSERT OR IGNORE INTO ads (id, position, title, ad_type, image_url, target_url, status) VALUES
        (1, 'header_top', 'Header Leaderboard Banner', 'image', 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=900&auto=format&fit=crop&q=80', 'https://example.com', 1),
        (2, 'sidebar_top', 'Sidebar Top Square Banner', 'image', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&auto=format&fit=crop&q=80', 'https://example.com', 1),
        (3, 'article_bottom', 'Article Bottom Banner', 'image', 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&auto=format&fit=crop&q=80', 'https://example.com', 1)");
}

function ensure_all_ad_positions_and_settings($pdo) {
    ensure_custom_author_columns($pdo);
    ensure_ad_width_height_columns($pdo);

    $default_positions = [
        'header_top' => 'Header Top Leaderboard Banner',
        'header_aside' => 'Beside Logo Banner',
        'below_header' => 'Below Navigation Bar Banner',
        'homepage_top' => 'Homepage Top Banner',
        'homepage_middle' => 'Homepage Middle Section Banner',
        'sidebar_top' => 'Sidebar Top Square Banner',
        'sidebar_bottom' => 'Sidebar Bottom Square Banner',
        'article_top' => 'Article Top Header Banner',
        'article_middle' => 'In-Article Middle Content Banner',
        'article_bottom' => 'Article Bottom Banner',
        'category_top' => 'Category Page Top Banner',
        'footer_top' => 'Above Footer Top Banner'
    ];

    foreach ($default_positions as $pos => $title) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM ads WHERE position = ?");
            $stmt->execute([$pos]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("INSERT INTO ads (position, title, ad_type, image_url, target_url, ad_code, status) VALUES (?, ?, 'image', '', '', '', 0)");
                $ins->execute([$pos, $title]);
            }
        } catch (Exception $e) {}
    }
}

function ensure_custom_author_columns($pdo) {
    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE posts ADD COLUMN custom_author_name VARCHAR(150) DEFAULT NULL");
        } else {
            $pdo->exec("ALTER TABLE posts ADD COLUMN custom_author_name TEXT DEFAULT NULL");
        }
    } catch (Exception $e) {}

    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE posts ADD COLUMN custom_author_image VARCHAR(255) DEFAULT NULL");
        } else {
            $pdo->exec("ALTER TABLE posts ADD COLUMN custom_author_image TEXT DEFAULT NULL");
        }
    } catch (Exception $e) {}

    ensure_english_post_columns($pdo);
}

function ensure_english_post_columns($pdo) {
    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE posts ADD COLUMN title_en VARCHAR(255) DEFAULT NULL");
        } else {
            $pdo->exec("ALTER TABLE posts ADD COLUMN title_en TEXT DEFAULT NULL");
        }
    } catch (Exception $e) {}

    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE posts ADD COLUMN short_description_en TEXT DEFAULT NULL");
        } else {
            $pdo->exec("ALTER TABLE posts ADD COLUMN short_description_en TEXT DEFAULT NULL");
        }
    } catch (Exception $e) {}

    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE posts ADD COLUMN content_en LONGTEXT DEFAULT NULL");
        } else {
            $pdo->exec("ALTER TABLE posts ADD COLUMN content_en TEXT DEFAULT NULL");
        }
    } catch (Exception $e) {}
}

function ensure_ad_width_height_columns($pdo) {
    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE ads ADD COLUMN width VARCHAR(50) DEFAULT ''");
        } else {
            $pdo->exec("ALTER TABLE ads ADD COLUMN width TEXT DEFAULT ''");
        }
    } catch (Exception $e) {}

    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("ALTER TABLE ads ADD COLUMN height VARCHAR(50) DEFAULT ''");
        } else {
            $pdo->exec("ALTER TABLE ads ADD COLUMN height TEXT DEFAULT ''");
        }
    } catch (Exception $e) {}
}

function import_sql_file($pdo, $sql_filepath) {
    if (!file_exists($sql_filepath)) {
        return false;
    }

    $content = file_get_contents($sql_filepath);
    if (empty(trim($content))) {
        return false;
    }

    $lines = explode("\n", $content);
    $query = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '#') === 0) {
            continue;
        }

        $query .= $line . "\n";

        if (substr($trimmed, -1) === ';') {
            try {
                $pdo->exec($query);
            } catch (Exception $e) {
                // Ignore duplicate errors or statement conflicts
            }
            $query = '';
        }
    }

    if (!empty(trim($query))) {
        try {
            $pdo->exec($query);
        } catch (Exception $e) {}
    }

    return true;
}

function ensure_homepage_sections_table($pdo) {
    try {
        if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `homepage_sections` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `title` VARCHAR(200) NOT NULL,
              `category_id` INT DEFAULT 0,
              `post_limit` INT DEFAULT 5,
              `layout_style` VARCHAR(50) DEFAULT 'lead_side_list',
              `section_order` INT DEFAULT 0,
              `status` TINYINT(1) DEFAULT 1,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `videos` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `title` VARCHAR(255) NOT NULL,
              `slug` VARCHAR(255) UNIQUE NOT NULL,
              `video_url` TEXT NOT NULL,
              `embed_code` TEXT,
              `thumbnail` VARCHAR(255),
              `description` TEXT,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery_albums` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `title` VARCHAR(255) NOT NULL,
              `slug` VARCHAR(255) UNIQUE NOT NULL,
              `cover_image` VARCHAR(255),
              `description` TEXT,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery_photos` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `album_id` INT NOT NULL,
              `photo_path` VARCHAR(255) NOT NULL,
              `caption` VARCHAR(255),
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_sections (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              title TEXT NOT NULL,
              category_id INTEGER DEFAULT 0,
              post_limit INTEGER DEFAULT 5,
              layout_style TEXT DEFAULT 'lead_side_list',
              section_order INTEGER DEFAULT 0,
              status INTEGER DEFAULT 1,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              title TEXT NOT NULL,
              slug TEXT UNIQUE NOT NULL,
              video_url TEXT NOT NULL,
              embed_code TEXT,
              thumbnail TEXT,
              description TEXT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_albums (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              title TEXT NOT NULL,
              slug TEXT UNIQUE NOT NULL,
              cover_image TEXT,
              description TEXT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_photos (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              album_id INTEGER NOT NULL,
              photo_path TEXT NOT NULL,
              caption TEXT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }

        // Seed default sections if empty
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM homepage_sections");
        $row = $stmt ? $stmt->fetch() : null;
        if ($row && (int)$row['count'] === 0) {
            $defaults = [
                ['title' => 'জাতীয় সংবাদ', 'category_id' => 1, 'post_limit' => 5, 'layout_style' => 'lead_side_list', 'section_order' => 1],
                ['title' => 'রাজনীতি', 'category_id' => 2, 'post_limit' => 4, 'layout_style' => 'two_column_grid', 'section_order' => 2],
                ['title' => 'ভিডিও খবর ও লাইভ স্ট্রিমিং', 'category_id' => 0, 'post_limit' => 6, 'layout_style' => 'video_gallery_theater', 'section_order' => 3],
                ['title' => 'আন্তর্জাতিক', 'category_id' => 3, 'post_limit' => 3, 'layout_style' => 'bento_grid', 'section_order' => 4],
                ['title' => 'ছবি গ্যালারি ও ফটো অ্যালবাম', 'category_id' => 0, 'post_limit' => 6, 'layout_style' => 'photo_gallery_grid', 'section_order' => 5],
                ['title' => 'অর্থনীতি ও ব্যবসা', 'category_id' => 4, 'post_limit' => 4, 'layout_style' => 'horizontal_cards', 'section_order' => 6],
                ['title' => 'খেলাধুলা', 'category_id' => 5, 'post_limit' => 4, 'layout_style' => 'overlay_grid', 'section_order' => 7],
                ['title' => 'বিনোদন ও সংস্কৃতি', 'category_id' => 6, 'post_limit' => 6, 'layout_style' => 'carousel_slider', 'section_order' => 8],
                ['title' => 'প্রযুক্তি ও জীবনযাপন', 'category_id' => 7, 'post_limit' => 5, 'layout_style' => 'compact_list', 'section_order' => 9]
            ];

            $ins = $pdo->prepare("INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, 1)");
            foreach ($defaults as $sec) {
                $ins->execute([$sec['title'], $sec['category_id'], $sec['post_limit'], $sec['layout_style'], $sec['section_order']]);
            }
        }

        ensure_sample_multimedia_data($pdo);
    } catch (Exception $e) {
        // Table exists or DB error
    }
}

function ensure_sample_multimedia_data($pdo) {
    try {
        // Check videos count
        $vStmt = $pdo->query("SELECT COUNT(*) as count FROM videos");
        $vRow = $vStmt ? $vStmt->fetch() : null;
        if ($vRow && (int)$vRow['count'] === 0) {
            $sample_videos = [
                [
                    'title' => 'পারিবারিক বিরোধের জেরে নাতির লাথিতে প্রাণ গেল নানীর। BHS NEWS',
                    'slug' => 'nani-killed-in-family-dispute-bhs-news',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
                    'description' => 'শের-ই-বাংলা মেডিকেল কলেজ হাসপাতাল মর্গে স্বজনদের আহাজারি। বিস্তারিত প্রতিবেদনে BHS NEWS।'
                ],
                [
                    'title' => 'সাংবাদিকতাকে দুজন দুই দিকে শেখে একজন নৈতিক অন্যজন বস্তুনিষ্ঠ',
                    'slug' => 'journalism-ethics-and-values-special-report',
                    'video_url' => 'https://www.youtube.com/watch?v=9No-FiEInLA',
                    'thumbnail' => 'https://img.youtube.com/vi/9No-FiEInLA/hqdefault.jpg',
                    'description' => 'গণমাধ্যমের স্বাধীনতা ও পেশাদারিত্ব নিয়ে প্রবীণ সাংবাদিকদের বিশেষ আলোচনা।'
                ],
                [
                    'title' => 'রাষ্ট্র, সমাজ, দেশ ও মানুষের জন্য দায়িত্বশীল সাংবাদিকতাকে জীবন্ত রাখা আমাদের লক্ষ্য',
                    'slug' => 'responsible-journalism-country-first',
                    'video_url' => 'https://www.youtube.com/watch?v=L_LUpnjgPso',
                    'thumbnail' => 'https://img.youtube.com/vi/L_LUpnjgPso/hqdefault.jpg',
                    'description' => 'জাতীয় প্রেসক্লাবে আয়োজিত বিশেষ আলোচনা সভার লাইভ ভিডিও।'
                ],
                [
                    'title' => 'সিভিল সার্জনের কক্ষে তালা ঝুলিয়ে দিলো বৈষম্য বিরোধী শিক্ষার্থীরা',
                    'slug' => 'students-lockdown-civil-surgeon-office',
                    'video_url' => 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ',
                    'thumbnail' => 'https://img.youtube.com/vi/fJ9rUzIMcZQ/hqdefault.jpg',
                    'description' => 'দুর্নীতির প্রতিবাদে শিক্ষার্থীদের কঠোর অবস্থান ও প্রশাসনিক তোলপাড়।'
                ],
                [
                    'title' => 'সদর উপজেলা কমিটি বাতিলের দাবিতে বরিশালে নেতা-কর্মীদের বিক্ষোভ মিছিল',
                    'slug' => 'protest-march-in-barishal-for-committee-cancellation',
                    'video_url' => 'https://www.youtube.com/watch?v=3JZ_D3ELwOQ',
                    'thumbnail' => 'https://img.youtube.com/vi/3JZ_D3ELwOQ/hqdefault.jpg',
                    'description' => 'বরিশাল প্রেস ক্লাব চত্বরে তৃণমূল নেতাকর্মীদের তীব্র ক্ষোভ ও স্লোগান।'
                ],
                [
                    'title' => 'রুটি পিঠা দিয়ে শিয়ালের মাংস খাওয়ায়, ১০জনের বিরুদ্ধে আদালতে মামলা',
                    'slug' => 'court-case-against-10-for-fox-meat-feeding',
                    'video_url' => 'https://www.youtube.com/watch?v=2Vv-BfVoq4g',
                    'thumbnail' => 'https://img.youtube.com/vi/2Vv-BfVoq4g/hqdefault.jpg',
                    'description' => 'চাঞ্চল্যকর এই ঘটনার জেরে এলাকায় তুমুল তোলপাড় ও আইনি পদক্ষেপ।'
                ]
            ];

            $vIns = $pdo->prepare("INSERT INTO videos (title, slug, video_url, thumbnail, description) VALUES (?, ?, ?, ?, ?)");
            foreach ($sample_videos as $sv) {
                $vIns->execute([$sv['title'], $sv['slug'], $sv['video_url'], $sv['thumbnail'], $sv['description']]);
            }
        }

        // Check gallery_albums count
        $gStmt = $pdo->query("SELECT COUNT(*) as count FROM gallery_albums");
        $gRow = $gStmt ? $gStmt->fetch() : null;
        if ($gRow && (int)$gRow['count'] === 0) {
            $sample_albums = [
                [
                    'title' => 'জাতীয় স্মৃতিসৌধে শ্রদ্ধার্ঘ্য নিবেদন ও বিজয় মেলা',
                    'slug' => 'national-memorial-tribute-and-victory-fair',
                    'cover' => 'https://images.unsplash.com/photo-1578894381163-e72c17f2d45f?w=800&auto=format&fit=crop&q=80',
                    'desc' => 'সাভারে জাতীয় স্মৃতিসৌধে পুষ্পস্তবক অর্পণ ও নানা আয়োজনের ছবি।',
                    'photos' => [
                        ['path' => 'https://images.unsplash.com/photo-1578894381163-e72c17f2d45f?w=1200&auto=format&fit=crop&q=80', 'caption' => 'স্মৃতিসৌধে ফুল দিয়ে শ্রদ্ধা জানাচ্ছেন সর্বস্তরের সাধারণ মানুষ।'],
                        ['path' => 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?w=1200&auto=format&fit=crop&q=80', 'caption' => 'লাল-সবুজের পতাকায় রঙিন তরুণ সমাজ।'],
                        ['path' => 'https://images.unsplash.com/photo-1532375810709-75b1da00537c?w=1200&auto=format&fit=crop&q=80', 'caption' => 'উৎসবমুখর পরিবেশে বিজয় দিবসের কুচকাওয়াজ।']
                    ]
                ],
                [
                    'title' => 'বর্ষবরণ ও ঐতিহাসিক মঙ্গল শোভাযাত্রা',
                    'slug' => 'pohela-boishakh-mongol-shobhajatra',
                    'cover' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80',
                    'desc' => 'ঢাকা বিশ্ববিদ্যালয় চারুকলা অনুষদের বিশেষ মঙ্গল শোভাযাত্রা।',
                    'photos' => [
                        ['path' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1200&auto=format&fit=crop&q=80', 'caption' => 'রঙিন লোকজ শিল্প কাঠামোর শোভাযাত্রা।'],
                        ['path' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&auto=format&fit=crop&q=80', 'caption' => 'পহেলা বৈশাখে রমনার বটমূলে ছায়ানটের সংগীতানুষ্ঠান।']
                    ]
                ],
                [
                    'title' => 'আন্তর্জাতিক ফুটবল ম্যাচের রোমাঞ্চকর ক্ষণ',
                    'slug' => 'international-football-match-highlights',
                    'cover' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop&q=80',
                    'desc' => 'বঙ্গবন্ধু জাতীয় স্টেডিয়ামে আয়োজিত বাংলাদেশ বনাম ভারত ম্যাচের হাইলাইটস।',
                    'photos' => [
                        ['path' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=1200&auto=format&fit=crop&q=80', 'caption' => 'গোল করার পর লাল-সবুজ জার্সিধারীদের উল্লাস।'],
                        ['path' => 'https://images.unsplash.com/photo-1518091043644-c1d4457512c6?w=1200&auto=format&fit=crop&q=80', 'caption' => 'গ্যালারিতে হাজারো দর্শকের গর্জন।']
                    ]
                ],
                [
                    'title' => 'প্রাকৃতিক সৌন্দর্য ও গ্রামবাংলার চিরন্তন রূপ',
                    'slug' => 'natural-beauty-of-rural-bangladesh',
                    'cover' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&auto=format&fit=crop&q=80',
                    'desc' => 'সবুজ শ্যামল প্রান্তর, সূর্যাস্ত ও মেঠোপথের মনমুগ্ধকর কোলাজ।',
                    'photos' => [
                        ['path' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&auto=format&fit=crop&q=80', 'caption' => 'নদীতে খেয়া পারাপারের মনোরম সূর্যাস্ত দৃশ্য।'],
                        ['path' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1200&auto=format&fit=crop&q=80', 'caption' => 'সবুজ চা বাগানে ভোরের কুয়াশা ও শিশির।']
                    ]
                ]
            ];

            $aIns = $pdo->prepare("INSERT INTO gallery_albums (title, slug, cover_image, description) VALUES (?, ?, ?, ?)");
            $pIns = $pdo->prepare("INSERT INTO gallery_photos (album_id, photo_path, caption) VALUES (?, ?, ?)");

            foreach ($sample_albums as $sa) {
                $aIns->execute([$sa['title'], $sa['slug'], $sa['cover'], $sa['desc']]);
                $alb_id = $pdo->lastInsertId();
                foreach ($sa['photos'] as $sp) {
                    $pIns->execute([$alb_id, $sp['path'], $sp['caption']]);
                }
            }
        }
    } catch (Exception $e) {
        // ignore error
    }
}

function ensure_views_and_date_columns($pdo) {
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN views INTEGER DEFAULT 0");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN views INTEGER DEFAULT 0");
    } catch (Exception $e) {}
}

function ensure_default_menus($pdo) {
    try {
        $check = $pdo->query("SELECT COUNT(*) FROM menus");
        if ($check && (int)$check->fetchColumn() === 0) {
            // Seed Top Bar
            $topMenus = [
                ['আমাদের সম্পর্কে', '/page.php?slug=about-us', 1],
                ['যোগাযোগ', '/contact.php', 2],
                ['গোপনীয়তা নীতি', '/page.php?slug=privacy-policy', 3],
                ['ব্যবহারের শর্তাবলী', '/page.php?slug=terms', 4]
            ];
            $stmt = $pdo->prepare("INSERT INTO menus (location, parent_id, title, url, item_order, target, status) VALUES ('top', 0, ?, ?, ?, '_self', 1)");
            foreach ($topMenus as $tm) {
                $stmt->execute([$tm[0], $tm[1], $tm[2]]);
            }

            // Seed Header Nav
            $stmtH = $pdo->prepare("INSERT INTO menus (location, parent_id, title, url, item_order, target, status) VALUES ('header', ?, ?, ?, ?, '_self', 1)");
            $stmtH->execute([0, 'প্রচ্ছদ', '/', 1]);

            $cats = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY cat_order ASC, id ASC")->fetchAll();
            $order = 2;
            $catMap = [];
            foreach ($cats as $cat) {
                if ($cat['parent_id'] == 0) {
                    $stmtH->execute([0, $cat['name'], '/category.php?slug=' . $cat['slug'], $order++]);
                    $lastId = $pdo->lastInsertId();
                    if ($lastId) $catMap[$cat['id']] = $lastId;
                }
            }
            foreach ($cats as $cat) {
                if ($cat['parent_id'] > 0 && isset($catMap[$cat['parent_id']])) {
                    $stmtH->execute([$catMap[$cat['parent_id']], $cat['name'], '/category.php?slug=' . $cat['slug'], 1]);
                }
            }

            $stmtH->execute([0, 'ছবি গ্যালারি', '/gallery.php', $order++]);
            $stmtH->execute([0, 'ভিডিও খবর', '/video.php', $order++]);

            // Seed Footer
            $footerMenus = [
                ['আমাদের সম্পর্কে', '/page.php?slug=about-us', 1],
                ['যোগাযোগ', '/contact.php', 2],
                ['গোপনীয়তা নীতি', '/page.php?slug=privacy-policy', 3],
                ['ব্যবহারের শর্তাবলী', '/page.php?slug=terms', 4],
                ['বিজ্ঞাপন দিন', '/page.php?slug=advertising', 5]
            ];
            $stmtF = $pdo->prepare("INSERT INTO menus (location, parent_id, title, url, item_order, target, status) VALUES ('footer', 0, ?, ?, ?, '_self', 1)");
            foreach ($footerMenus as $fm) {
                $stmtF->execute([$fm[0], $fm[1], $fm[2]]);
            }
        }
    } catch (Exception $e) {}
}

function ensure_database_indexes($pdo) {
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)",
        "CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug)",
        "CREATE INDEX IF NOT EXISTS idx_posts_category ON posts(category_id, status)",
        "CREATE INDEX IF NOT EXISTS idx_posts_pubdate ON posts(publish_date, id)",
        "CREATE INDEX IF NOT EXISTS idx_posts_views ON posts(views)",
        "CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug)",
        "CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_id, status)"
    ];

    foreach ($indexes as $idx_sql) {
        try {
            $pdo->exec($idx_sql);
        } catch (Exception $e) {}
    }
}

function ensure_contact_messages_table($pdo) {
    try {
        $driver = '';
        try { $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME); } catch (Throwable $e) {}
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (Throwable $e) {}
}

