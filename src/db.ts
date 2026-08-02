import initSqlJs, { Database as SqlJsDatabase } from 'sql.js';
import fs from 'fs';
import path from 'path';

let sqlDb: SqlJsDatabase | null = null;
const dbPath = path.join(process.cwd(), 'database.sqlite');

function saveDb() {
  if (!sqlDb) return;
  try {
    const data = sqlDb.export();
    const buffer = Buffer.from(data);
    const tmpPath = `${dbPath}.tmp`;
    fs.writeFileSync(tmpPath, buffer);
    fs.renameSync(tmpPath, dbPath);
  } catch (err) {
    console.error('Error writing database.sqlite file:', err);
  }
}

export async function initDatabase(): Promise<SqlJsDatabase> {
  if (sqlDb) return sqlDb;

  const SQL = await initSqlJs();

  let loaded = false;
  if (fs.existsSync(dbPath)) {
    try {
      const fileBuffer = fs.readFileSync(dbPath);
      sqlDb = new SQL.Database(fileBuffer);
      const res = sqlDb.exec("PRAGMA integrity_check;");
      if (!res || !res.length || res[0]?.values?.[0]?.[0] !== 'ok') {
        throw new Error('Database integrity check failed');
      }
      loaded = true;
    } catch (e) {
      console.warn('Existing database file is corrupt. Removing and starting fresh.', e);
      if (sqlDb) {
        try { sqlDb.close(); } catch (_) {}
        sqlDb = null;
      }
      try {
        if (fs.existsSync(dbPath)) fs.unlinkSync(dbPath);
      } catch (_) {}
    }
  }

  if (!loaded || !sqlDb) {
    sqlDb = new SQL.Database();
  }

  try {
    runSchemaAndSeeds();
  } catch (err) {
    console.warn('Error running schema/seed on loaded DB, resetting database...', err);
    if (sqlDb) {
      try { sqlDb.close(); } catch (_) {}
    }
    try {
      if (fs.existsSync(dbPath)) fs.unlinkSync(dbPath);
    } catch (_) {}
    sqlDb = new SQL.Database();
    runSchemaAndSeeds();
  }

  saveDb();
  return sqlDb;
}

function runSchemaAndSeeds() {
  sqlDb.run(`
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
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
      subcategory_id INTEGER,
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
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
      status TEXT DEFAULT 'approved',
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

    CREATE TABLE IF NOT EXISTS homepage_sections (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      title TEXT NOT NULL,
      category_id INTEGER DEFAULT 0,
      post_limit INTEGER DEFAULT 5,
      layout_style TEXT DEFAULT 'lead_side_list',
      section_order INTEGER DEFAULT 0,
      status INTEGER DEFAULT 1
    );
  `);

  try { sqlDb.run("ALTER TABLE posts ADD COLUMN title_en TEXT;"); } catch (_) {}
  try { sqlDb.run("ALTER TABLE posts ADD COLUMN short_description_en TEXT;"); } catch (_) {}
  try { sqlDb.run("ALTER TABLE posts ADD COLUMN content_en TEXT;"); } catch (_) {}

  // Seed default admin user if not exists
  const userCheck = queryOne('SELECT count(*) as count FROM users');
  if (!userCheck || userCheck.count === 0) {
    runQuery(
      `INSERT INTO users (id, username, email, password, full_name, role, avatar, bio, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [1, 'admin', 'admin@newsportal.com', '$2y$10$wN1G2lQ28A0pU/4Q8w8i/O5zG0zG0zG0zG0zG0zG0zG0zG0zG0zG0', 'Super Administrator', 'admin', 'default-avatar.jpg', 'Editor in Chief & System Administrator.', 1]
    );
  }

  // Seed default settings if empty
  const settingsCheck = queryOne('SELECT count(*) as count FROM settings');
  if (!settingsCheck || settingsCheck.count === 0) {
    const defaultSettings: Array<[string, string]> = [
      ['site_name', 'দৈনিক দিগন্ত'],
      ['site_title', 'দৈনিক দিগন্ত - সত্যের সন্ধানে অবিরত'],
      ['meta_description', 'দৈনিক দিগন্ত - বাংলাদেশের শীর্ষস্থানীয় ডিজিটাল সংবাদপত্র। হোস্টারকিউব লিমিটেড (HosterCube Ltd) পরিচালিত অনলাইন সংবাদ মাধ্যম।'],
      ['meta_keywords', 'সংবাদপত্র, খবর, পত্রিকা, দৈনিক দিগন্ত, ব্রেকিং নিউজ, HosterCube, হোস্টারকিউব'],
      ['editor_name', 'এম. এ. রহমান'],
      ['publisher_name', 'হোস্টারকিউব লিমিটেড (HosterCube Ltd)'],
      ['chief_editor', 'কে. এইচ. চৌধুরী'],
      ['address', 'লেভেল ৮, হোস্টারকিউব টাওয়ার, ৪২ মতিঝিল বা/এ, ঢাকা-১০০০, বাংলাদেশ'],
      ['phone', '+৮৮০ ২ ৯৮৭৬৫৪৩২'],
      ['mobile', '+৮৮০ ১৭১১-০০০০০০'],
      ['email', 'contact@hostercube.com'],
      ['office_time', '২৪ ঘণ্টা নিউজ রুম কার্যক্রম'],
      ['facebook', 'https://facebook.com/hostercube'],
      ['twitter', 'https://twitter.com/hostercube'],
      ['youtube', 'https://youtube.com/hostercube'],
      ['instagram', 'https://instagram.com/hostercube'],
      ['linkedin', 'https://linkedin.com/company/hostercube'],
      ['copyright', '© ২০২৬ হোস্টারকিউব লিমিটেড (HosterCube Ltd)। সর্বস্বত্ব সংরক্ষিত।'],
      ['footer_text', 'দৈনিক দিগন্ত - হোস্টারকিউব লিমিটেড (HosterCube Ltd) দ্বারা পরিচালিত ও কারিগরি সহায়তায় সমৃদ্ধ অনলাইন সংবাদপত্র।']
    ];
    for (const [k, v] of defaultSettings) {
      runQuery('INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)', [k, v]);
    }
  }

  // Seed default categories if empty
  const catCheck = queryOne('SELECT count(*) as count FROM categories');
  if (!catCheck || catCheck.count === 0) {
    const defaultCats = [
      [1, 0, 'জাতীয়', 'national', 'জাতীয় ও সমগ্র বাংলাদেশ সংবাদ', 1, 1],
      [2, 0, 'রাজনীতি', 'politics', 'রাজনৈতিক খবরাখবর, নির্বাচন ও সংসদ', 2, 1],
      [3, 0, 'আন্তর্জাতিক', 'world', 'বিশ্ব সংবাদ, বৈশ্বিক রাজনীতি ও কূটনীতি', 3, 1],
      [4, 0, 'অর্থনীতি', 'business', 'ব্যাংকিং, শেয়ারবাজার, ব্যবসা ও বাণিজ্য', 4, 1],
      [5, 0, 'খেলাধুলা', 'sports', 'ক্রিকেট, ফুটবল ও খেলাধুলার খবর', 5, 1],
      [6, 0, 'বিনোদন', 'entertainment', 'চলচ্চিত্র, নাটক, সঙ্গীত ও তারোকা সংবাদ', 6, 1],
      [7, 0, 'প্রযুক্তি', 'technology', 'স্মার্টফোন, ফ্রিল্যান্সিং, এআই ও টেকনোলজি', 7, 1],
      [8, 0, 'জীবনযাপন', 'lifestyle', 'স্বাস্থ্য, রেসিপি, ফ্যাশন ও ভ্রমণ', 8, 1],
      [9, 1, 'অপরাধ ও বিচার', 'crime-justice', 'আইনশৃঙ্খলা ও আদালতের খবর', 1, 1],
      [10, 5, 'ক্রিকেট', 'cricket', 'লাইভ ক্রিকেট স্কোর ও রিভিউ', 1, 1]
    ];
    for (const cat of defaultCats) {
      runQuery('INSERT OR REPLACE INTO categories (id, parent_id, name, slug, description, cat_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)', cat);
    }
  }

  // Seed default ads if empty
  const adCheck = queryOne('SELECT count(*) as count FROM ads');
  if (!adCheck || adCheck.count === 0) {
    runQuery(`INSERT OR REPLACE INTO ads (id, position, title, ad_type, image_url, target_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [1, 'header_top', 'Top Header Call Banner', 'image', '/uploads/header_ad.jpg', 'https://hostitbd.com', 1]
    );
    runQuery(`INSERT OR REPLACE INTO ads (id, position, title, ad_type, image_url, target_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [2, 'sidebar_top', 'Sidebar Square AdSpace', 'image', '/uploads/sidebar_ad.png', 'https://hostitbd.com', 1]
    );
    runQuery(`INSERT OR REPLACE INTO ads (id, position, title, ad_type, image_url, target_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [3, 'article_bottom', 'Article Middle Banner Ad', 'image', '/uploads/banner_ad.png', 'https://hostitbd.com', 1]
    );
    runQuery(`INSERT OR REPLACE INTO ads (id, position, title, ad_type, image_url, target_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [4, 'sidebar_bottom', 'Sidebar Bottom AdSpace', 'image', '/uploads/sidebar_ad.png', 'https://hostitbd.com', 1]
    );
  }

  // Seed sample posts if empty
  const postsCheck = queryOne('SELECT count(*) as count FROM posts');
  if (!postsCheck || postsCheck.count === 0) {
    const samplePosts = [
      [
        1,
        'স্মার্ট বাংলাদেশ গড়ার প্রত্যয়ে মেগা অবকাঠামো প্রকল্পের শুভ উদ্বোধন',
        'smart-bangladesh-infrastructure-project-launch',
        'যোগাযোগ ব্যবস্থার আধুনিকায়নে নতুন হাই-স্পিড এক্সপ্রেসওয়ে ও মেগা রেল প্রকল্পের আনুষ্ঠানিক উদ্বোধন করলেন নীতি নির্ধারকেরা।',
        '<p><strong>ঢাকা —</strong> দেশের অর্থনীতিকে গতিশীল করতে এবং আধুনিক যাতায়াত ব্যবস্থা জোরদারে আজ সকালে রাজধানীর অদূরে উদ্বোধিত হলো মেগা যোগাযোগ প্রকল্প।</p><p>প্রকল্প উদ্বোধনী অনুষ্ঠানে প্রধান অতিথিরা বলেন, এই যোগাযোগের নতুন নেটওয়ার্কের ফলে মাত্র ২ ঘণ্টায় বন্দর নগরীর সাথে সরাসরি পণ্য পরিবহন সম্ভব হবে। এতে রফতানি খাত ও স্থানীয় কর্মসংস্থানে ব্যাপক জোয়ার আসবে।</p><h3>প্রকল্পের মূল বৈশিষ্ট্যসমূহ:</h3><ul><li>৩৫০ কিলোমিটার আধুনিক চার লেনের এক্সপ্রেসওয়ে।</li><li>পরিবেশবান্ধব সৌর শক্তি চালিত স্টেশন।</li><li>স্বয়ংক্রিয় ই-টোল আদায় ব্যবস্থা।</li></ul>',
        1,
        1,
        'https://images.unsplash.com/photo-1474487548417-781cb71495f3?w=1200&auto=format&fit=crop&q=80',
        'জাতীয়, অবকাঠামো, ট্রেন, মেগাপ্রকল্প',
        1, 1, 1, 1, 1820
      ],
      [
        2,
        'এশিয়ান কাপের শ্বাসরুদ্ধকর ফাইনালে বাংলাদেশের ঐতিহাসিক জয়',
        'bangladesh-cricket-historic-victory-asian-cup-final',
        'শেষ বলে প্রয়োজন ৩ রান, দৃষ্টিনন্দন ছক্কায় ম্যাচ জিতে ট্রফি নিজেদের করে নিল জাতীয় দল।',
        '<p>মিরপুর শেরে বাংলা জাতীয় ক্রিকেট স্টেডিয়ামে গ্যালারি ভর্তি ৪৫ হাজার দর্শকের উপস্থিতিতে এক স্মরণীয় রোমাঞ্চকর জয় ছিনিয়ে নিল জাতীয় ক্রিকেট দল।</p><p>শেষ ওভারে জয়ের জন্য প্রয়োজন ছিল ১৪ রান। অসাধারণ দায়িত্বশীল ব্যাটিংয়ে দলের অলরাউন্ডার মাত্র ৪৮ বলে ৮৪ রানের অপরাজিত ইনিংস খেলে দলকে জয়ের বন্দরে পৌঁছে দেন।</p>',
        5,
        1,
        'https://images.unsplash.com/photo-1531415074968-036ba1b575da?w=1200&auto=format&fit=crop&q=80',
        'খেলাধুলা, ক্রিকেট, ট্রফি, টাইগার্স',
        1, 1, 1, 1, 2350
      ],
      [
        3,
        'রফতানি আয়ে নতুন রেকর্ড, শীর্ষ অবস্থান ধরে রেখেছে পোশাক ও আইটি খাত',
        'export-earnings-reach-new-record-high',
        'চলতি অর্থবছরের প্রথমার্ধে আগের বছরের তুলনায় ১৫ শতাংশ বেশি রফতানি আয় অর্জিত হয়েছে।',
        '<p>অর্থনৈতিক সমীক্ষায় দেখা গেছে বাণিজ্য ঘাটতি কমিয়ে আনাসহ বিশ্ববাজারে বাংলাদেশি পণ্যের নতুন বাজার তৈরিতে ব্যাপক সফলতা এসেছে।</p><p>বিশেষ করে তথ্যপ্রযুক্তি খাতে ফ্রিল্যান্সিং ও আউটসোর্সিং সেবা থেকে আসা বৈদেশিক মুদ্রা দেশের বৈদেশিক মুদ্রার রিজার্ভে ইতিবাচক প্রভাব ফেলছে।</p>',
        4,
        1,
        'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=1200&auto=format&fit=crop&q=80',
        'অর্থনীতি, রফতানি, বাণিজ্য, ব্যাংক',
        1, 0, 1, 1, 1420
      ],
      [
        4,
        'চিকিৎসা বিজ্ঞানে কৃত্রিম বুদ্ধিমত্তা: মাত্র ১০ সেকেন্ডে শনাক্ত হবে রোগ',
        'ai-in-medical-diagnostics-breakthrough',
        'আধুনিক এআই ল্যাবরেটরি উদ্ভাবন করেছে এমন অ্যালগরিদম যা অতি সূক্ষ্ম এক্স-রে ও স্ক্যান বিশ্লেষণ করতে সক্ষম।',
        '<p>প্রযুক্তি ও চিকিৎসা বিজ্ঞানের মেলবন্ধনে এবার স্বাস্থ্য খাতে বড় পরিবর্তনের আভাস মিলছে। নতুন এআই সফটওয়্যার গ্রাম ও প্রান্তিক অঞ্চলের চিকিৎসকদের রোগ নির্ণয়ে তাত্ক্ষণিক সিদ্ধান্ত নিতে সহায়তা করবে।</p>',
        7,
        1,
        'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&auto=format&fit=crop&q=80',
        'প্রযুক্তি, এআই, স্বাস্থ্য, বিজ্ঞান',
        0, 0, 1, 1, 1890
      ],
      [
        5,
        'আন্তর্জাতিক চলচ্চিত্র উৎসবে গোল্ডেন অ্যাওয়ার্ড জিতল বাংলাদেশি পূর্ণদৈর্ঘ্য ছবি',
        'bangladeshi-film-wins-golden-award-at-international-film-festival',
        'বিশ্বের ৪০টি দেশের ১৫০টি চলচ্চিত্রের মধ্যে বিচারকদের সর্বোচ্চ নম্বর অর্জন করল ছবিটি।',
        '<p>রেড কার্পেট প্রিমিয়ারে দাঁড়িয়ে বিশ্বের শীর্ষ চলচ্চিত্র সমালোচকদের করতালি আর প্রশংসা কুড়িয়েছে এই ঐতিহাসিক ড্রামা ঘরানার বাংলাদেশি সিনেমাটি।</p>',
        6,
        1,
        'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1200&auto=format&fit=crop&q=80',
        'বিনোদন, সিনেমা, পুরস্কার, সংস্কৃতি',
        0, 0, 0, 1, 1120
      ]
    ];

    for (const p of samplePosts) {
      runQuery(
        `INSERT OR REPLACE INTO posts (id, title, slug, short_description, content, category_id, author_id, featured_image, tags, is_featured, is_breaking, is_trending, is_popular, views, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')`,
        p
      );
    }
  }

  // Seed sample homepage sections if empty
  const secCheck = queryOne('SELECT count(*) as count FROM homepage_sections');
  if (!secCheck || secCheck.count === 0) {
    runQuery(`INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, ?)`, ['জাতীয় সংবাদ', 1, 5, 'lead_side_list', 1, 1]);
    runQuery(`INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, ?)`, ['খেলাধুলা', 5, 4, 'two_column_grid', 2, 1]);
    runQuery(`INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, ?)`, ['প্রযুক্তি ও জীবনযাপন', 7, 4, 'horizontal_cards', 3, 1]);
  }

  // Seed sample videos if empty
  const vidCheck = queryOne('SELECT count(*) as count FROM videos');
  if (!vidCheck || vidCheck.count === 0) {
    runQuery(`INSERT INTO videos (title, slug, video_url, thumbnail, description) VALUES (?, ?, ?, ?, ?)`, [
      'Special Report: High-Tech Smart Infrastructure Facility',
      'special-report-smart-infrastructure',
      'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
      'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800&auto=format&fit=crop&q=80',
      'Watch our special documentary'
    ]);
  }

  // Seed sample gallery if empty
  const albCheck = queryOne('SELECT count(*) as count FROM gallery_albums');
  if (!albCheck || albCheck.count === 0) {
    runQuery(`INSERT INTO gallery_albums (id, title, slug, cover_image, description) VALUES (?, ?, ?, ?, ?)`, [
      1,
      'National Heritage & Cultural Exhibition 2026',
      'national-heritage-cultural-exhibition-2026',
      'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80',
      'Moments from the National Heritage Fair'
    ]);
    runQuery(`INSERT INTO gallery_photos (album_id, photo_path, caption) VALUES (?, ?, ?)`, [
      1,
      'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=80',
      'Traditional performance on opening night'
    ]);
    runQuery(`INSERT INTO gallery_photos (album_id, photo_path, caption) VALUES (?, ?, ?)`, [
      1,
      'https://images.unsplash.com/photo-1469488865564-c2de10f69f96?w=800&auto=format&fit=crop&q=80',
      'Artisans displaying hand-woven crafts'
    ]);
  }
}

export function runQuery(sql: string, params: any[] = []): { lastInsertRowid: number; changes: number } {
  if (!sqlDb) throw new Error('Database not initialized');
  const stmt = sqlDb.prepare(sql);
  stmt.run(params);
  stmt.free();
  const lastIdRes = sqlDb.exec("SELECT last_insert_rowid() as id");
  const lastId = lastIdRes[0]?.values[0]?.[0] || 0;
  saveDb();
  return { lastInsertRowid: Number(lastId), changes: 1 };
}

export function queryAll(sql: string, params: any[] = []): any[] {
  if (!sqlDb) throw new Error('Database not initialized');
  const stmt = sqlDb.prepare(sql);
  if (params.length) stmt.bind(params);
  const results: any[] = [];
  while (stmt.step()) {
    results.push(stmt.getAsObject());
  }
  stmt.free();
  return results;
}

export function queryOne(sql: string, params: any[] = []): any | null {
  const rows = queryAll(sql, params);
  return rows.length ? rows[0] : null;
}

/* ============================================================
   Helper Data Functions for Views & Controllers
   ============================================================ */

export function getSetting(key: string, defaultValue = ''): string {
  const row = queryOne('SELECT setting_value FROM settings WHERE setting_key = ?', [key]);
  return row ? (row.setting_value ?? defaultValue) : defaultValue;
}

export function setSetting(key: string, value: string): void {
  runQuery('INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)', [key, value]);
}

export function getCategories(parentId = 0, onlyActive = true): any[] {
  let sql = 'SELECT * FROM categories WHERE parent_id = ?';
  if (onlyActive) sql += ' AND status = 1';
  sql += ' ORDER BY cat_order ASC, name ASC';
  return queryAll(sql, [parentId]);
}

export function getCategory(idOrSlug: string | number): any | null {
  if (typeof idOrSlug === 'number' || !isNaN(Number(idOrSlug))) {
    return queryOne('SELECT * FROM categories WHERE id = ?', [Number(idOrSlug)]);
  } else {
    return queryOne('SELECT * FROM categories WHERE slug = ?', [idOrSlug]);
  }
}

export function formatPostForLang(post: any, lang: string = 'bn'): any {
  if (!post) return post;
  const p = { ...post };
  if (lang === 'en') {
    if (p.title_en && p.title_en.trim() !== '') {
      p.title = p.title_en;
    }
    if (p.short_description_en && p.short_description_en.trim() !== '') {
      p.short_description = p.short_description_en;
    }
    if (p.content_en && p.content_en.trim() !== '') {
      p.content = p.content_en;
    }
  } else {
    if ((!p.title || p.title.trim() === '') && p.title_en) {
      p.title = p.title_en;
    }
    if ((!p.short_description || p.short_description.trim() === '') && p.short_description_en) {
      p.short_description = p.short_description_en;
    }
    if ((!p.content || p.content.trim() === '') && p.content_en) {
      p.content = p.content_en;
    }
  }
  return p;
}

export function formatPostsForLang(posts: any[], lang: string = 'bn'): any[] {
  if (!Array.isArray(posts)) return [];
  return posts.map(p => formatPostForLang(p, lang));
}

export function getPosts(options: any = {}, lang?: string): any[] {
  const limit = options.limit ? Number(options.limit) : 10;
  const offset = options.offset ? Number(options.offset) : 0;
  const categoryId = options.category_id ? Number(options.category_id) : 0;
  const subcategoryId = options.subcategory_id ? Number(options.subcategory_id) : 0;
  const isFeatured = options.is_featured !== undefined && options.is_featured !== null ? Number(options.is_featured) : null;
  const isBreaking = options.is_breaking !== undefined && options.is_breaking !== null ? Number(options.is_breaking) : null;
  const isTrending = options.is_trending !== undefined && options.is_trending !== null ? Number(options.is_trending) : null;
  const isPopular = options.is_popular !== undefined && options.is_popular !== null ? Number(options.is_popular) : null;
  const search = options.search ? String(options.search).trim() : '';
  const status = options.status || 'published';

  const where = ['p.status = ?'];
  const params: any[] = [status];

  if (categoryId > 0) {
    where.push('(p.category_id = ? OR c.parent_id = ?)');
    params.push(categoryId, categoryId);
  }

  if (subcategoryId > 0) {
    where.push('p.subcategory_id = ?');
    params.push(subcategoryId);
  }

  if (isFeatured !== null) {
    where.push('p.is_featured = ?');
    params.push(isFeatured);
  }

  if (isBreaking !== null) {
    where.push('p.is_breaking = ?');
    params.push(isBreaking);
  }

  if (isTrending !== null) {
    where.push('p.is_trending = ?');
    params.push(isTrending);
  }

  if (isPopular !== null) {
    where.push('p.is_popular = ?');
    params.push(isPopular);
  }

  if (options.date && String(options.date).trim() !== '') {
    where.push('DATE(p.publish_date) = ?');
    params.push(String(options.date).trim());
  }

  if (search) {
    where.push('(p.title LIKE ? OR p.title_en LIKE ? OR p.short_description LIKE ? OR p.content LIKE ? OR p.tags LIKE ?)');
    const term = `%${search}%`;
    params.push(term, term, term, term, term);
  }

  const whereClause = where.join(' AND ');
  const orderBy = options.order_by || 'p.publish_date DESC, p.id DESC';

  const sql = `
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.avatar as author_avatar
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE ${whereClause}
    ORDER BY ${orderBy}
    LIMIT ${limit} OFFSET ${offset}
  `;

  const rows = queryAll(sql, params);
  const targetLang = lang || options.lang || 'bn';
  return formatPostsForLang(rows, targetLang);
}

export function getPostsCount(options: any = {}): number {
  const categoryId = options.category_id ? Number(options.category_id) : 0;
  const search = options.search ? String(options.search).trim() : '';
  const status = options.status || 'published';

  const where = ['p.status = ?'];
  const params: any[] = [status];

  if (categoryId > 0) {
    where.push('p.category_id = ?');
    params.push(categoryId);
  }

  if (options.date && String(options.date).trim() !== '') {
    where.push('DATE(p.publish_date) = ?');
    params.push(String(options.date).trim());
  }

  if (search) {
    where.push('(p.title LIKE ? OR p.title_en LIKE ? OR p.short_description LIKE ? OR p.content LIKE ?)');
    const term = `%${search}%`;
    params.push(term, term, term, term);
  }

  const whereClause = where.join(' AND ');
  const row = queryOne(`SELECT COUNT(*) as total FROM posts p WHERE ${whereClause}`, params);
  return row ? Number(row.total) : 0;
}

export function getPostBySlug(slug: string, lang?: string): any | null {
  const sql = `
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.avatar as author_avatar, u.bio as author_bio
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.slug = ? AND p.status = 'published'
  `;
  const row = queryOne(sql, [slug]);
  return formatPostForLang(row, lang || 'bn');
}

export function getPostById(id: number, lang?: string): any | null {
  const sql = `
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.full_name as author_name, u.avatar as author_avatar, u.bio as author_bio
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.id = ? AND p.status = 'published'
  `;
  const row = queryOne(sql, [id]);
  return formatPostForLang(row, lang || 'bn');
}

export function getMediaUrl(url: string, fallback = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80'): string {
  if (!url) return fallback;
  const trimmed = url.trim();
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('//') || trimmed.startsWith('data:')) {
    return trimmed;
  }
  return '/' + trimmed.replace(/^\/+/, '');
}

export function incrementViews(postId: number): void {
  runQuery('UPDATE posts SET views = views + 1 WHERE id = ?', [postId]);
}

export function getBreakingNews(limit = 6): any[] {
  return getPosts({ is_breaking: 1, limit });
}

export function getAd(position: string): any | null {
  return queryOne('SELECT * FROM ads WHERE position = ? AND status = 1 LIMIT 1', [position]);
}

export function renderAd(position: string, extraClass = ''): string {
  const ad = getAd(position);
  if (!ad || !ad.status) return '';

  let maxHeightStyle = '';
  if (position.includes('header')) {
    const customH = getSetting('header_ad_height', '');
    if (customH && !isNaN(Number(customH))) {
      maxHeightStyle = `max-height: ${Number(customH)}px;`;
    }
  }

  const widthStyle = ad.width ? `width: ${isNaN(Number(ad.width)) ? ad.width : ad.width + 'px'};` : 'max-width: 100%;';
  const heightStyle = ad.height ? `height: ${isNaN(Number(ad.height)) ? ad.height : ad.height + 'px'};` : (maxHeightStyle || 'max-height: 140px;');

  let output = `<div class="ad-container text-center my-2 ${extraClass}" data-ad-position="${position}">`;
  if (ad.ad_type === 'code' && ad.ad_code) {
    output += `<div class="ad-code-wrapper overflow-auto d-inline-block w-100" style="max-width: 100%;">${ad.ad_code}</div>`;
  } else if (ad.ad_type === 'image' && ad.image_url) {
    const target = ad.target_url || '#';
    const imgUrl = getMediaUrl(ad.image_url);
    output += `<a href="${target}" target="_blank" rel="nofollow" class="d-inline-block" style="max-width: 100%;">
      <img src="${imgUrl}" alt="${ad.title || 'Advertisement'}" class="img-fluid rounded shadow-sm border" style="${widthStyle} ${heightStyle} object-fit: contain;" />
    </a>`;
  }
  output += `</div>`;
  return output;
}

export function getMenus(location = 'header'): any[] {
  const parents = queryAll('SELECT * FROM menus WHERE location = ? AND parent_id = 0 AND status = 1 ORDER BY item_order ASC', [location]);
  for (const parent of parents) {
    parent.children = queryAll('SELECT * FROM menus WHERE parent_id = ? AND status = 1 ORDER BY item_order ASC', [parent.id]);
  }
  return parents;
}

export function getHomepageSections(onlyActive = true): any[] {
  let sql = `
    SELECT s.*, c.name as category_name, c.slug as category_slug
    FROM homepage_sections s
    LEFT JOIN categories c ON s.category_id = c.id
  `;
  if (onlyActive) sql += ' WHERE s.status = 1';
  sql += ' ORDER BY s.section_order ASC, s.id ASC';
  return queryAll(sql);
}

export function getVideos(limit = 6): any[] {
  return queryAll('SELECT * FROM videos ORDER BY id DESC LIMIT ?', [limit]);
}

export function getGalleryAlbumsWithPhotos(limit = 6): any[] {
  const albums = queryAll('SELECT * FROM gallery_albums ORDER BY id DESC LIMIT ?', [limit]);
  for (const alb of albums) {
    alb.photos = queryAll('SELECT * FROM gallery_photos WHERE album_id = ? ORDER BY id ASC', [alb.id]);
    alb.photo_count = alb.photos.length;
  }
  return albums;
}

export function getHomepagePhotos(limit = 8): any[] {
  return queryAll(
    'SELECT p.*, a.title as album_title, a.slug as album_slug FROM gallery_photos p LEFT JOIN gallery_albums a ON p.album_id = a.id ORDER BY p.id DESC LIMIT ?',
    [limit]
  );
}

/* Date & Language Helpers */

export function convertEnToBn(str: string | number): string {
  const en = ['0','1','2','3','4','5','6','7','8','9','January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
  const bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর','জানু','ফেব্রু','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টে','অক্টো','নভে','ডিসে','শনিবার','রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার'];
  let res = String(str);
  for (let i = 0; i < en.length; i++) {
    res = res.replaceAll(en[i], bn[i]);
  }
  return res;
}

export function getBanglaEraDate(dateObj: Date = new Date()): string {
  const day = dateObj.getDate();
  const month = dateObj.getMonth() + 1;
  const year = dateObj.getFullYear();

  const bnMonths = ['বৈশাখ', 'জ্যৈষ্ঠ', 'আষাঢ়', 'শ্রাবণ', 'ভাদ্র', 'আশ্বিন', 'কার্তিক', 'অগ্রহায়ণ', 'পৌষ', 'মাঘ', 'ফাল্গুন', 'চৈত্র'];
  const bnYear = (month < 4 || (month === 4 && day < 14)) ? year - 594 : year - 593;

  const gregBoishakh = new Date(year, 3, 14).getTime();
  let timestamp = dateObj.getTime();
  if (timestamp < gregBoishakh) {
    timestamp = new Date(year - 1, 3, 14).getTime();
  }

  const daysDiff = Math.floor((dateObj.getTime() - gregBoishakh) / 86400000);
  const monthDays = [31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 30, 30];
  const isLeap = (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
  if (isLeap) monthDays[10] = 31;

  let bnMonthIndex = 0;
  let bnDay = daysDiff + 1;
  if (bnDay < 1) bnDay = 1;

  for (let i = 0; i < 12; i++) {
    if (bnDay <= monthDays[i]) {
      bnMonthIndex = i;
      break;
    }
    bnDay -= monthDays[i];
  }

  return `${convertEnToBn(bnDay)} ${bnMonths[bnMonthIndex]} ${convertEnToBn(bnYear)}`;
}

export function getFullBanglaDateString(dateObj: Date = new Date()): string {
  const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  const dayName = days[dateObj.getDay()];
  const gregDate = `${dateObj.getDate()} ${months[dateObj.getMonth()]} ${dateObj.getFullYear()}`;

  const bnDayName = convertEnToBn(dayName);
  const bnGregDate = convertEnToBn(gregDate);
  const bnEraDate = getBanglaEraDate(dateObj);

  return `${bnDayName}, ${bnGregDate} / ${bnEraDate} বঙ্গাব্দ`;
}

export function timeAgo(datetimeStr: string, lang = 'bn'): string {
  const time = new Date(datetimeStr).getTime();
  const now = Date.now();
  const diff = Math.floor((now - time) / 1000);

  if (isNaN(time) || diff < 0 || diff < 60) return lang === 'bn' ? 'এইমাত্র' : 'Just now';
  if (diff < 3600) {
    const m = Math.floor(diff / 60);
    return lang === 'bn' ? `${convertEnToBn(m)} মিনিট আগে` : `${m} mins ago`;
  }
  if (diff < 86400) {
    const h = Math.floor(diff / 3600);
    return lang === 'bn' ? `${convertEnToBn(h)} ঘন্টা আগে` : `${h} hours ago`;
  }
  if (diff < 604800) {
    const d = Math.floor(diff / 86400);
    return lang === 'bn' ? `${convertEnToBn(d)} দিন আগে` : `${d} days ago`;
  }

  const dObj = new Date(time);
  return lang === 'bn' ? convertEnToBn(`${dObj.getDate()} ${dObj.toLocaleString('en', { month: 'short' })}, ${dObj.getFullYear()}`) : dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export function getCategoryDisplayName(name: string, lang = 'bn'): string {
  if (!name) return '';

  const bnToEn: Record<string, string> = {
    'প্রচ্ছদ': 'Home',
    'জাতীয়': 'National',
    'জাতীয়': 'National',
    'জাতীয় সংবাদ': 'National News',
    'বরিশাল বিভাগ': 'Barishal Division',
    'বরিশাল নগরী': 'Barishal City',
    'রাজনীতি': 'Politics',
    'অর্থনীতি': 'Business & Economy',
    'আন্তর্জাতিক': 'World News',
    'খেলাধুলো': 'Sports',
    'খেলা': 'Sports',
    'খেলাধুলা': 'Sports',
    'বিনোদন': 'Entertainment',
    'লাইফস্টাইল': 'Lifestyle',
    'জীবনযাপন': 'Lifestyle',
    'প্রযুক্তি': 'Technology',
    'তথ্যপ্রযুক্তি': 'IT & Tech',
    'বিজ্ঞান ও প্রযুক্তি': 'Science & Tech',
    'মতামত': 'Opinion',
    'সারাদেশ': 'Countrywide',
    'চাকরি': 'Jobs',
    'শিক্ষা': 'Education',
    'ভিডিও': 'Videos',
    'ছবি': 'Photos',
    'গ্যালারি': 'Gallery',
    'ছবি গ্যালারি': 'Photo Gallery',
    'ভিডিও খবর': 'Video News',
    'আমাদের সম্পর্কে': 'About Us',
    'যোগাযোগ': 'Contact Us'
  };

  const enToBn: Record<string, string> = {
    'Home': 'প্রচ্ছদ',
    'National': 'জাতীয়',
    'National News': 'জাতীয় সংবাদ',
    'Barishal Division': 'বরিশাল বিভাগ',
    'Barisal Division': 'বরিশাল বিভাগ',
    'Barishal City': 'বরিশাল নগরী',
    'Barisal City': 'বরিশাল নগরী',
    'Politics': 'রাজনীতি',
    'Business & Economy': 'অর্থনীতি',
    'Business': 'অর্থনীতি',
    'Economy': 'অর্থনীতি',
    'World News': 'আন্তর্জাতিক',
    'World': 'আন্তর্জাতিক',
    'Sports': 'খেলাধুলা',
    'Entertainment': 'বিনোদন',
    'Lifestyle': 'জীবনযাপন',
    'Technology': 'প্রযুক্তি',
    'IT & Tech': 'তথ্যপ্রযুক্তি',
    'Science & Tech': 'বিজ্ঞান ও প্রযুক্তি',
    'Opinion': 'মতামত',
    'Countrywide': 'সারাদেশ',
    'Jobs': 'চাকরি',
    'Education': 'শিক্ষা',
    'Videos': 'ভিডিও',
    'Video': 'ভিডিও',
    'Video News': 'ভিডিও খবর',
    'Photos': 'ছবি',
    'Photo': 'ছবি',
    'Photo Gallery': 'ছবি গ্যালারি',
    'Gallery': 'গ্যালারি',
    'About Us': 'আমাদের সম্পর্কে',
    'About': 'আমাদের সম্পর্কে',
    'Contact Us': 'যোগাযোগ',
    'Contact': 'যোগাযোগ'
  };

  if (lang === 'en') {
    return bnToEn[name] || name;
  } else {
    return enToBn[name] || name;
  }
}

export function slugify(text: string): string {
  let str = text.trim().toLowerCase();
  str = str.replace(/[^\w\s-]/g, '');
  str = str.replace(/[\s_-]+/g, '-');
  str = str.replace(/^-+|-+$/g, '');
  return str || `news-${Date.now()}`;
}

export function getPostDisplayAuthor(post: any): string {
  if (!post) return 'Staff Reporter';
  if (post.custom_author_name && post.custom_author_name.trim()) {
    return post.custom_author_name;
  }
  if (post.author_name && post.author_name.trim()) {
    return post.author_name;
  }
  return 'Staff Reporter';
}

