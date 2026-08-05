-- ============================================================
-- Professional Newspaper Portal CMS Database Schema
-- Compatible with MySQL 5.7+ / MySQL 8.x / MariaDB / cPanel phpMyAdmin
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'reporter',
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.jpg',
  `bio` TEXT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT DEFAULT 0,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `cat_order` INT DEFAULT 0,
  `show_in_menu` TINYINT(1) DEFAULT 1,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Posts Table
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `title_en` VARCHAR(255) DEFAULT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `short_description` TEXT NOT NULL,
  `short_description_en` TEXT DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `content_en` LONGTEXT DEFAULT NULL,
  `category_id` INT NOT NULL,
  `subcategory_id` INT DEFAULT NULL,
  `author_id` INT NOT NULL,
  `custom_author_name` VARCHAR(150) DEFAULT NULL,
  `custom_author_image` VARCHAR(255) DEFAULT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `gallery_images` TEXT DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_breaking` TINYINT(1) DEFAULT 0,
  `is_trending` TINYINT(1) DEFAULT 0,
  `is_popular` TINYINT(1) DEFAULT 0,
  `allow_comments` TINYINT(1) DEFAULT 1,
  `views` INT DEFAULT 0,
  `seo_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `meta_keywords` VARCHAR(255) DEFAULT NULL,
  `og_image` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'published',
  `publish_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`category_id`),
  INDEX (`slug`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Custom Pages Table
CREATE TABLE IF NOT EXISTS `pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `content` LONGTEXT NOT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Menus Table
CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `location` VARCHAR(50) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `parent_id` INT DEFAULT 0,
  `item_order` INT DEFAULT 0,
  `target` VARCHAR(20) DEFAULT '_self',
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Advertisements Table
CREATE TABLE IF NOT EXISTS `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `position` VARCHAR(100) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `ad_type` VARCHAR(50) DEFAULT 'image',
  `ad_code` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `target_url` VARCHAR(255) DEFAULT NULL,
  `impressions` INT DEFAULT 0,
  `clicks` INT DEFAULT 0,
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Comments Table
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `comment` TEXT NOT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Media Library Table
CREATE TABLE IF NOT EXISTS `media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(255) NOT NULL,
  `filetype` VARCHAR(50) NOT NULL,
  `filesize` INT NOT NULL,
  `uploaded_by` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Photo Gallery Table
CREATE TABLE IF NOT EXISTS `gallery_albums` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `album_id` INT NOT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Videos Table
CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `video_url` VARCHAR(255) NOT NULL,
  `embed_code` TEXT DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Website Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Subscribers Table
CREATE TABLE IF NOT EXISTS `subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `status` TINYINT(1) DEFAULT 1,
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INITIAL SAMPLE DATA INSERTION
-- ============================================================

-- Default Admin User (Password: admin123)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `avatar`, `bio`, `status`) VALUES
(1, 'admin', 'admin@newsportal.com', '$2y$10$wN1G2lQ28A0pU/4Q8w8i/O5zG0zG0zG0zG0zG0zG0zG0zG0zG0zG0', 'Super Administrator', 'admin', 'default-avatar.jpg', 'Editor in Chief & System Administrator.', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Core News Categories (Bangladeshi Newspaper Layout)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `cat_order`, `status`) VALUES
(1, 0, 'জাতীয়', 'national', 'জাতীয় ও সমগ্র বাংলাদেশ সংবাদ', 1, 1),
(2, 0, 'রাজনীতি', 'politics', 'রাজনৈতিক খবরাখবর, নির্বাচন ও সংসদ', 2, 1),
(3, 0, 'আন্তর্জাতিক', 'world', 'বিশ্ব সংবাদ, বৈশ্বিক রাজনীতি ও কূটনীতি', 3, 1),
(4, 0, 'অর্থনীতি', 'business', 'ব্যাংকিং, শেয়ারবাজার, ব্যবসা ও বাণিজ্য', 4, 1),
(5, 0, 'খেলাধুলা', 'sports', 'ক্রিকেট, ফুটবল ও খেলাধুলার খবর', 5, 1),
(6, 0, 'বিনোদন', 'entertainment', 'চলচ্চিত্র, নাটক, সঙ্গীত ও তারোকা সংবাদ', 6, 1),
(7, 0, 'প্রযুক্তি', 'technology', 'স্মার্টফোন, ফ্রিল্যান্সিং, এআই ও টেকনোলজি', 7, 1),
(8, 0, 'জীবনযাপন', 'lifestyle', 'স্বাস্থ্য, রেসিপি, ফ্যাশন ও ভ্রমণ', 8, 1),
(9, 1, 'অপরাধ ও বিচার', 'crime-justice', 'আইনশৃঙ্খলা ও আদালতের খবর', 1, 1),
(10, 5, 'ক্রিকেট', 'cricket', 'লাইভ ক্রিকেট স্কোর ও রিভিউ', 1, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'দৈনিক দিগন্ত'),
('site_title', 'দৈনিক দিগন্ত - সত্যের সন্ধানে অবিরত'),
('meta_description', 'দৈনিক দিগন্ত - বাংলাদেশের শীর্ষস্থানীয় ডিজিটাল সংবাদপত্র। জাতীয়, রাজনীতি, আন্তর্জাতিক, খেলাধুলা, অর্থনীতি, বিনোদন ও প্রযুক্তির ২৪ ঘণ্টা তাজা খবর।'),
('meta_keywords', 'সংবাদপত্র, খবর, পত্রিকা, দৈনিক দিগন্ত, ব্রেকিং নিউজ, জাতীয়, রাজনীতি, খেলাধুলা, bangla news, news portal'),
('editor_name', 'এম. এ. রহমান'),
('publisher_name', 'দিগন্ত মিডিয়া মিডিয়া অ্যান্ড পাবলিকেশন্স লিমিটেড'),
('chief_editor', 'কে. এইচ. চৌধুরী'),
('address', 'লেভেল ৮, দিগন্ত টাওয়ার, ৪২ মতিঝিল বা/এ, ঢাকা-১০০০, বাংলাদেশ'),
('phone', '+৮৮০ ২ ৯৮৭৬৫ND'),
('mobile', '+৮৮০ ১৭১১-০০০০০০'),
('email', 'contact@dailyhorizon.com'),
('office_time', '২৪ ঘণ্টা নিউজ রুম কার্যক্রম'),
('facebook', 'https://facebook.com/dailyhorizon'),
('twitter', 'https://twitter.com/dailyhorizon'),
('youtube', 'https://youtube.com/dailyhorizon'),
('instagram', 'https://instagram.com/dailyhorizon'),
('linkedin', 'https://linkedin.com/company/dailyhorizon'),
('whatsapp', '+8801711000000'),
('telegram', 'https://t.me/dailyhorizonnews'),
('copyright', '© ২০২৬ দৈনিক দিগন্ত মিডিয়া লিমিটেড। সর্বস্বত্ব সংরক্ষিত।'),
('footer_text', 'দৈনিক দিগন্ত বস্তুনিষ্ঠ সাংবাদিকতা ও সর্বশেষ ব্রেকিং সংবাদের বিশ্বস্ত ঠিকানা।'),
('google_map', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.890244243013!2d90.3888!3d23.7508!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDQ1JzAyLjkiTiA5MMKwMjMnMTkuNyJF!5e0!3m2!1sen!2sbd!4v1620000000000!5m2!1sen!2sbd')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- Default Advertisements
INSERT INTO `ads` (`id`, `position`, `title`, `ad_type`, `image_url`, `target_url`, `status`) VALUES
(1, 'header_top', 'Leaderboard Header Banner', 'image', 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=900&auto=format&fit=crop&q=80', 'https://example.com', 1),
(2, 'sidebar_top', 'Sidebar Square Ad', 'image', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&auto=format&fit=crop&q=80', 'https://example.com', 1),
(3, 'article_bottom', 'In-Article Banner', 'image', 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&auto=format&fit=crop&q=80', 'https://example.com', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Custom Pages
INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `status`) VALUES
(1, 'আমাদের সম্পর্কে', 'about-us', '<h2>দৈনিক দিগন্ত-এ আপনাকে স্বাগতম</h2><p>দৈনিক দিগন্ত একটি নিরপেক্ষ ও সত্যনিষ্ঠ বস্তুনিষ্ঠ অনলাইন বাংলা সংবাদপত্র। আমাদের অভিজ্ঞ সাংবাদিক ও সম্পাদকীয় দল ২৪ ঘণ্টা নিরপেক্ষ ও বস্তুনিষ্ঠ সংবাদ পাঠকদের নিকট পৌঁছে দিতে কাজ করে যাচ্ছে।</p><h3>আমাদের লক্ষ্য ও উদ্দেশ্য</h3><ul><li><strong>বস্তুনিষ্ঠতা:</strong> তথ্যের সত্যতা যাচাই করে দ্রুত উপস্থাপন।</li><li><strong>স্বচ্ছতা:</strong> পাঠকদের আস্থা অর্জনে আপসহীন নীতি।</li><li><strong>স্বাধীনতা:</strong> সকল প্রকার প্রভাবমুক্ত নিরপেক্ষ সাংবাদিকতা।</li></ul>', 'আমাদের সম্পর্কে - দৈনিক দিগন্ত', 'দৈনিক দিগন্ত এর পরিচিতি ও বিস্তারিত।', 1),
(2, 'গোপনীয়তা নীতি', 'privacy-policy', '<h2>গোপনীয়তা নীতি</h2><p>দৈনিক দিগন্ত পাঠকদের গোপনীয়তাকে অত্যন্ত গুরুত্ব প্রদান করে। আমাদের ওয়েবসাইটে আপনার ব্যক্তিগত তথ্যের সুরক্ষা নিশ্চিতকরণে আমরা প্রতিশ্রুতিবদ্ধ।</p>', 'গোপনীয়তা নীতি - দৈনিক দিগন্ত', 'দৈনিক দিগন্ত এর গোপনীয়তা নীতি।', 1),
(3, 'যোগাযোগ', 'contact-us', '<h2>আমাদের সাথে যোগাযোগ করুন</h2><p>যেকোনো সংবাদ, প্রেস রিলিজ বা বিজ্ঞাপনের জন্য আমাদের নিউজ রুমের সাথে যোগাযোগ করুন।</p>', 'যোগাযোগ - দৈনিক দিগন্ত', 'দৈনিক দিগন্ত এর সাথে যোগাযোগের ঠিকানা।', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Posts
INSERT INTO `posts` (`id`, `title`, `slug`, `short_description`, `content`, `category_id`, `author_id`, `featured_image`, `tags`, `is_featured`, `is_breaking`, `is_trending`, `is_popular`, `views`, `status`, `publish_date`) VALUES
(1, 'স্মার্ট বাংলাদেশ গড়ার প্রত্যয়ে মেগা অবকাঠামো প্রকল্পের শুভ উদ্বোধন', 'smart-bangladesh-infrastructure-project-launch', 'যোগাযোগ ব্যবস্থার আধুনিকায়নে নতুন হাই-স্পিড এক্সপ্রেসওয়ে ও মেগা রেল প্রকল্পের আনুষ্ঠানিক উদ্বোধন করলেন নীতি নির্ধারকেরা।', '<p><strong>ঢাকা —</strong> দেশের অর্থনীতিকে গতিশীল করতে এবং আধুনিক যাতায়াত ব্যবস্থা জোরদারে আজ সকালে রাজধানীর অদূরে উদ্বোধিত হলো মেগা যোগাযোগ প্রকল্প।</p><p>প্রকল্প উদ্বোধনী অনুষ্ঠানে প্রধান অতিথিরা বলেন, এই যোগাযোগের নতুন নেটওয়ার্কের ফলে মাত্র ২ ঘণ্টায় বন্দর নগরীর সাথে সরাসরি পণ্য পরিবহন সম্ভব হবে। এতে রফতানি খাত ও স্থানীয় কর্মসংস্থানে ব্যাপক জোয়ার আসবে।</p><h3>প্রকল্পের মূল বৈশিষ্ট্যসমূহ:</h3><ul><li>৩৫০ কিলোমিটার আধুনিক চার লেনের এক্সপ্রেসওয়ে।</li><li>পরিবেশবান্ধব সৌর শক্তি চালিত স্টেশন।</li><li>স্বয়ংক্রিয় ই-টোল আদায় ব্যবস্থা।</li></ul>', 1, 1, 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=1200&auto=format&fit=crop&q=80', 'জাতীয়, অবকাঠামো, ট্রেন, মেগাপ্রকল্প', 1, 1, 1, 1, 1820, 'published', NOW()),

(2, 'এশিয়ান কাপের শ্বাসরুদ্ধকর ফাইনালে বাংলাদেশের ঐতিহাসিক জয়', 'bangladesh-cricket-historic-victory-asian-cup-final', 'শেষ বলে প্রয়োজন ৩ রান, দৃষ্টিনন্দন ছক্কায় ম্যাচ জিতে ট্রফি নিজেদের করে নিল জাতীয় দল।', '<p>মিরপুর শেরে বাংলা জাতীয় ক্রিকেট স্টেডিয়ামে গ্যালারি ভর্তি ৪৫ হাজার দর্শকের উপস্থিতিতে এক স্মরণীয় রোমাঞ্চকর জয় ছিনিয়ে নিল জাতীয় ক্রিকেট দল।</p><p>শেষ ওভারে জয়ের জন্য প্রয়োজন ছিল ১৪ রান। অসাধারণ দায়িত্বশীল ব্যাটিংয়ে দলের অলরাউন্ডার মাত্র ৪৮ বলে ৮৪ রানের অপরাজিত ইনিংস খেলে দলকে জয়ের বন্দরে পৌঁছে দেন।</p>', 5, 1, 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?w=1200&auto=format&fit=crop&q=80', 'খেলাধুলা, ক্রিকেট, ট্রফি, টাইগার্স', 1, 1, 1, 1, 2350, 'published', NOW()),

(3, 'রফতানি আয়ে নতুন রেকর্ড, শীর্ষ অবস্থান ধরে রেখেছে পোশাক ও আইটি খাত', 'export-earnings-reach-new-record-high', 'চলতি অর্থবছরের প্রথমার্ধে আগের বছরের তুলনায় ১৫ শতাংশ বেশি রফতানি আয় অর্জিত হয়েছে।', '<p>অর্থনৈতিক সমীক্ষায় দেখা গেছে বাণিজ্য ঘাটতি কমিয়ে আনাসহ বিশ্ববাজারে বাংলাদেশি পণ্যের নতুন বাজার তৈরিতে ব্যাপক সফলতা এসেছে।</p><p>বিশেষ করে তথ্যপ্রযুক্তি খাতে ফ্রিল্যান্সিং ও আউটসোর্সিং সেবা থেকে আসা বৈদেশিক মুদ্রা দেশের বৈদেশিক মুদ্রার রিজার্ভে ইতিবাচক প্রভাব ফেলছে।</p>', 4, 1, 'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=1200&auto=format&fit=crop&q=80', 'অর্থনীতি, রফতানি, বাণিজ্য, ব্যাংক', 1, 0, 1, 1, 1420, 'published', NOW()),

(4, 'চিকিৎসা বিজ্ঞানে কৃত্রিম বুদ্ধিমত্তা: মাত্র ১০ সেকেন্ডে শনাক্ত হবে রোগ', 'ai-in-medical-diagnostics-breakthrough', 'আধুনিক এআই ল্যাবরেটরি উদ্ভাবন করেছে এমন অ্যালগরিদম যা অতি সূক্ষ্ম এক্স-রে ও স্ক্যান বিশ্লেষণ করতে সক্ষম।', '<p>প্রযুক্তি ও চিকিৎসা বিজ্ঞানের মেলবন্ধনে এবার স্বাস্থ্য খাতে বড় পরিবর্তনের আভাস মিলছে। নতুন এআই সফটওয়্যার গ্রাম ও প্রান্তিক অঞ্চলের চিকিৎসকদের রোগ নির্ণয়ে তাত্ক্ষণিক সিদ্ধান্ত নিতে সহায়তা করবে।</p>', 6, 1, 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&auto=format&fit=crop&q=80', 'প্রযুক্তি, এআই, স্বাস্থ্য, বিজ্ঞান', 0, 0, 1, 1, 1890, 'published', NOW()),

(5, 'আন্তর্জাতিক চলচ্চিত্র উৎসবে গোল্ডেন অ্যাওয়ার্ড জিতল বাংলাদেশি পূর্ণদৈর্ঘ্য ছবি', 'bangladeshi-film-wins-golden-award-at-international-film-festival', 'বিশ্বের ৪০টি দেশের ১৫০টি চলচ্চিত্রের মধ্যে বিচারকদের সর্বোচ্চ নম্বর অর্জন করল ছবিটি।', '<p>রেড কার্পেট প্রিমিয়ারে দাঁড়িয়ে বিশ্বের শীর্ষ চলচ্চিত্র সমালোচকদের করতালি আর প্রশংসা কুড়িয়েছে এই ঐতিহাসিক ড্রামা ঘরানার বাংলাদেশি সিনেমাটি।</p>', 7, 1, 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1200&auto=format&fit=crop&q=80', 'বিনোদন, সিনেমা, পুরস্কার, সংস্কৃতি', 0, 0, 0, 1, 1120, 'published', NOW()),

(6, 'সুস্বাস্থ্য ধরে রাখতে প্রতিদিনের ৫টি প্রয়োজনীয় অভ্যাস', '5-essential-daily-habits-for-boosting-health', 'স্বাস্থ্য বিশেষজ্ঞদের মতে নিয়মিত সঠিক ঘুম, সুষম খাদ্য ও ব্যায়াম মানসিক প্রশান্তি দীর্ঘস্থায়ী করে।', '<p>সুস্থ জীবনযাপনের জন্য খুব জটিল নিয়মের প্রয়োজন নেই। গবেষকরা বলছেন দৈনন্দিন জীবনের কয়েকটি ছোট পরিবর্তনের মাধ্যমেই শারীরিক ও মানসিক কর্মক্ষমতা বহুগুণ বাড়ানো সম্ভব।</p>', 8, 1, 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=1200&auto=format&fit=crop&q=80', 'জীবনযাপন, স্বাস্থ্য, ফিটনেস, পরামর্শ', 0, 0, 0, 0, 750, 'published', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Video News
INSERT INTO `videos` (`id`, `title`, `slug`, `video_url`, `thumbnail`, `description`) VALUES
(1, 'Exclusive Special Report: Inside the High-Tech Smart Grid Facility', 'inside-the-high-tech-smart-grid-facility', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800&auto=format&fit=crop&q=80', 'Watch our special on-ground documentary exploring automated power distribution.'),
(2, 'Highlights: National Championship Cricket Final Thriller', 'highlights-national-championship-cricket-final-thriller', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=800&auto=format&fit=crop&q=80', 'Relive the best moments and winning sixes from yesterday’s historic final.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Gallery Albums
INSERT INTO `gallery_albums` (`id`, `title`, `slug`, `cover_image`, `description`) VALUES
(1, 'Annual Cultural & Heritage Exhibition Highlights', 'annual-cultural-heritage-exhibition-highlights', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80', 'Captivating moments from the 2026 National Arts & Cultural Fair.')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `gallery_photos` (`id`, `album_id`, `photo_path`, `caption`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80', 'Traditional performance on opening night'),
(2, 1, 'https://images.unsplash.com/photo-1469488865564-c2de10f69f96?w=800&auto=format&fit=crop&q=80', 'Artisans displaying hand-woven crafts')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
