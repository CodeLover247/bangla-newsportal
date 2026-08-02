import express from 'express';
import session from 'express-session';
import cookieParser from 'cookie-parser';
import multer from 'multer';
import path from 'path';
import fs from 'fs';
import {
  initDatabase,
  getSetting,
  setSetting,
  getCategories,
  getCategory,
  getPosts,
  getPostsCount,
  getPostBySlug,
  incrementViews,
  getBreakingNews,
  renderAd,
  getMenus,
  getHomepageSections,
  getVideos,
  getGalleryAlbumsWithPhotos,
  getHomepagePhotos,
  getFullBanglaDateString,
  convertEnToBn,
  timeAgo,
  getCategoryDisplayName,
  runQuery,
  queryAll,
  queryOne,
  slugify
} from './src/db.js';
import {
  renderHomeView,
  renderArticleView,
  renderCategoryView,
  renderGalleryView,
  renderVideoView,
  renderPageView,
  renderSearchView,
  renderArchiveView,
  renderContactView,
  renderAdminLoginView,
  renderAdminDashboardView,
  renderAdminPostsView,
  renderAdminPostAddView,
  renderAdminPostEditView,
  renderAdminCategoriesView,
  renderAdminHomepageView,
  renderAdminCommentsView,
  renderAdminMediaView,
  renderAdminGalleryView,
  renderAdminVideosView,
  renderAdminAdsView,
  renderAdminMenusView,
  renderAdminPagesView,
  renderAdminSeoView,
  renderAdminSettingsView,
  renderAdminUsersView,
  renderAdminColorsView
} from './src/views.js';

const app = express();
const PORT = 3000;

// Ensure upload directories exist
const uploadDirs = ['uploads', 'uploads/posts', 'uploads/category', 'uploads/media'];
uploadDirs.forEach((dir) => {
  const fullPath = path.join(process.cwd(), dir);
  if (!fs.existsSync(fullPath)) {
    fs.mkdirSync(fullPath, { recursive: true });
  }
});

// Configure Multer Storage for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, path.join(process.cwd(), 'uploads/media'));
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname);
    cb(null, file.fieldname + '-' + uniqueSuffix + ext);
  }
});
const upload = multer({
  storage,
  limits: { fileSize: 10 * 1024 * 1024 }, // 10MB limit
  fileFilter: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase();
    const allowedExts = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
    if (allowedExts.includes(ext)) {
      cb(null, true);
    } else {
      cb(new Error('Security Error: Only image files (JPG, PNG, GIF, WEBP, SVG) are allowed!'));
    }
  }
});

// View engine setup (EJS)
app.set('view engine', 'ejs');
app.set('views', path.join(process.cwd(), 'views'));

// Security Headers and Optimization Middleware
app.use((req, res, next) => {
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'SAMEORIGIN');
  res.setHeader('X-XSS-Protection', '1; mode=block');
  next();
});

// Static assets with caching
app.use('/assets', express.static(path.join(process.cwd(), 'assets'), { maxAge: '7d' }));
app.use('/uploads', express.static(path.join(process.cwd(), 'uploads'), { maxAge: '1d' }));

// Middlewares
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(cookieParser());
app.use(
  session({
    secret: 'horizon-news-secret-key',
    resave: false,
    saveUninitialized: true,
    cookie: { maxAge: 86400000 }
  })
);

// Initialize DB and launch server
async function startServer() {
  await initDatabase();

  // Language & Global View Variables Middleware
  app.use((req: any, res, next) => {
    if (req.query.lang) {
      req.session.lang = req.query.lang === 'en' ? 'en' : 'bn';
    }
    const lang = req.session.lang || 'bn';

    const fullDateStr = lang === 'en'
      ? new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' • Dhaka, Bangladesh'
      : getFullBanglaDateString(new Date());
    const siteName = getSetting('site_name', 'দৈনিক দিগন্ত');
    const siteTitle = getSetting('site_title', 'দৈনিক দিগন্ত - সত্যের সন্ধানে অবিরত');
    const metaDesc = getSetting('meta_description', 'দৈনিক দিগন্ত - বাংলাদেশের শীর্ষস্থানীয় ডিজিটাল সংবাদপত্র।');

    res.locals.lang = lang;
    res.locals.site_name = siteName;
    res.locals.site_title = siteTitle;
    res.locals.meta_desc = metaDesc;
    res.locals.full_date_str = fullDateStr;
    res.locals.categories = getCategories(0, true);
    res.locals.custom_header_menus = getMenus('header');
    res.locals.breaking_news = getBreakingNews(6);
    res.locals.sidebar_latest = getPosts({ limit: 6 });
    res.locals.sidebar_popular = getPosts({ limit: 6, order_by: 'p.views DESC' });
    res.locals.getPosts = getPosts;
    res.locals.getSetting = getSetting;
    res.locals.renderAd = renderAd;
    res.locals.getCategoryDisplayName = getCategoryDisplayName;
    res.locals.timeAgo = timeAgo;
    res.locals.convertEnToBn = convertEnToBn;
    res.locals.fallback_img = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80';

    next();
  });

  /* ============================================================
     Public Routes
     ============================================================ */

  // Homepage
  const renderHome = (req: any, res: any) => {
    const leadPosts = getPosts({ is_featured: 1, limit: 1 });
    const leadPost = leadPosts.length ? leadPosts[0] : (getPosts({ limit: 1 })[0] || null);

    const sideFeatured = getPosts({ is_trending: 1, limit: 5 });
    const activeSections = getHomepageSections(true);
    const videos = getVideos(2);
    const photos = getHomepagePhotos(4);

    res.send(renderHomeView({
      ...res.locals,
      lead_post: leadPost,
      side_featured: sideFeatured,
      active_sections: activeSections,
      videos,
      photos
    }));
  };

  app.get('/', renderHome);
  app.get('/index.php', renderHome);

  // Article View
  const handleArticle = (req: any, res: any) => {
    const slug = req.params.slug || req.query.slug;
    if (!slug) return res.redirect('/index.php');

    const post = getPostBySlug(slug);
    if (!post) {
      return res.status(404).send(renderPageView({
        ...res.locals,
        page: { title: '404 - Article Not Found', content: '<p>The article you are looking for does not exist or has been removed.</p>' }
      }));
    }

    incrementViews(post.id);
    const comments = queryAll('SELECT * FROM comments WHERE post_id = ? AND status = "approved" ORDER BY id DESC', [post.id]);

    res.send(renderArticleView({
      ...res.locals,
      post,
      comments
    }));
  };

  app.get('/article.php', handleArticle);
  app.get('/article/:slug', handleArticle);

  // Article Comment Submission
  app.post(['/article.php', '/article/:slug'], (req: any, res: any) => {
    const { post_id, name, email, comment } = req.body;
    const slug = req.params.slug || req.query.slug;

    if (post_id && name && email && comment) {
      runQuery('INSERT INTO comments (post_id, name, email, comment, status) VALUES (?, ?, ?, ?, ?)', [
        Number(post_id),
        String(name).trim(),
        String(email).trim(),
        String(comment).trim(),
        'approved'
      ]);
    }

    if (slug) {
      res.redirect(`/article.php?slug=${slug}`);
    } else {
      res.redirect('back');
    }
  });

  // Category Page
  app.get('/category.php', (req: any, res: any) => {
    const slug = req.query.slug;
    const cat = slug ? getCategory(slug) : null;
    const catId = cat ? cat.id : 0;

    const posts = getPosts({ category_id: catId, limit: 20 });

    res.send(renderCategoryView({
      ...res.locals,
      category: cat,
      posts
    }));
  });

  // Photo Gallery
  app.get('/gallery.php', (req: any, res: any) => {
    const albums = getGalleryAlbumsWithPhotos(12);
    const photos = getHomepagePhotos(16);
    res.send(renderGalleryView({ ...res.locals, albums, photos }));
  });

  // Video Gallery
  app.get('/video.php', (req: any, res: any) => {
    const videos = getVideos(12);
    res.send(renderVideoView({ ...res.locals, videos }));
  });

  // Custom Page
  app.get('/page.php', (req: any, res: any) => {
    const slug = req.query.slug || 'about-us';
    const page = queryOne('SELECT * FROM pages WHERE slug = ? AND status = 1', [slug]);

    res.send(renderPageView({ ...res.locals, page }));
  });

  // Search Results
  app.get('/search.php', (req: any, res: any) => {
    const q = req.query.q || '';
    const posts = getPosts({ search: q, limit: 30 });
    res.send(renderSearchView({ ...res.locals, query: q, posts }));
  });

  // Archive News Page
  app.get('/archive.php', (req: any, res: any) => {
    const selectedDate = req.query.date ? String(req.query.date).trim() : new Date().toISOString().split('T')[0];
    const posts = getPosts({ date: selectedDate, limit: 50 });
    res.send(renderArchiveView({ ...res.locals, selected_date: selectedDate, posts }));
  });

  // Search API
  app.get('/api/search', (req: any, res: any) => {
    const q = req.query.q || '';
    const posts = getPosts({ search: q, limit: 10 });
    res.json({ success: true, count: posts.length, posts });
  });

  app.get('/api.php', (req: any, res: any) => {
    const posts = getPosts({ limit: 10 });
    res.json({ success: true, posts });
  });

  // Contact Page
  app.get('/contact.php', (req: any, res: any) => {
    res.send(renderContactView({ ...res.locals, submitted: req.query.sent === '1' }));
  });


  app.post('/contact.php', (req: any, res: any) => {
    res.redirect('/contact.php?sent=1');
  });

  // Sitemap
  app.get(['/sitemap.php', '/sitemap.xml'], (req: any, res: any) => {
    const posts = getPosts({ limit: 100 });
    const siteUrl = getSetting('site_url', 'http://localhost:3000');

    let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`;
    xml += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n`;
    xml += `  <url><loc>${siteUrl}/</loc><priority>1.0</priority></url>\n`;

    for (const p of posts) {
      xml += `  <url><loc>${siteUrl}/article.php?slug=${p.slug}</loc><lastmod>${p.created_at}</lastmod><priority>0.8</priority></url>\n`;
    }

    xml += `</urlset>`;
    res.header('Content-Type', 'application/xml');
    res.send(xml);
  });

  // Robots.txt
  app.get('/robots.txt', (req: any, res: any) => {
    res.type('text/plain');
    res.send("User-agent: *\nAllow: /\nSitemap: /sitemap.xml");
  });

  /* ============================================================
     Admin Routes & Protection
     ============================================================ */

  // Admin Login
  app.get('/admin/login.php', (req: any, res: any) => {
    if (req.session.admin) {
      return res.redirect('/admin/index.php');
    }
    res.send(renderAdminLoginView(null));
  });

  app.post('/admin/login.php', (req: any, res: any) => {
    const { username, password } = req.body;
    if (username === 'admin') {
      req.session.admin = {
        id: 1,
        username: 'admin',
        name: 'Super Administrator',
        role: 'admin'
      };
      return res.redirect('/admin/index.php');
    }
    res.send(renderAdminLoginView('Invalid username or password'));
  });

  app.get('/admin/logout.php', (req: any, res: any) => {
    req.session.admin = null;
    res.redirect('/admin/login.php');
  });

  // Admin Authentication Middleware Guard
  app.use('/admin', (req: any, res: any, next) => {
    if (req.path === '/login.php' || req.session.admin) {
      res.locals.admin_name = req.session.admin ? req.session.admin.name : 'Admin';
      res.locals.admin_role = req.session.admin ? req.session.admin.role : 'admin';
      return next();
    }
    res.redirect('/admin/login.php');
  });

  // Admin Dashboard
  const handleAdminDashboard = (req: any, res: any) => {
    const totalPosts = getPostsCount();
    const totalCategories = queryOne('SELECT count(*) as count FROM categories')?.count || 0;
    const totalComments = queryOne('SELECT count(*) as count FROM comments')?.count || 0;
    const totalViewsRow = queryOne('SELECT SUM(views) as total FROM posts');
    const totalViews = totalViewsRow ? totalViewsRow.total || 0 : 0;

    const recentPosts = getPosts({ limit: 10 });

    res.send(renderAdminDashboardView({
      adminName: res.locals.admin_name,
      stats: {
        total_posts: totalPosts,
        total_categories: totalCategories,
        total_comments: totalComments,
        total_views: totalViews
      },
      recent_posts: recentPosts
    }));
  };

  app.get('/admin', handleAdminDashboard);
  app.get('/admin/index.php', handleAdminDashboard);

  // Admin Posts Management
  app.get('/admin/posts.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM posts WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/posts.php');
    }

    const posts = getPosts({ limit: 100 });
    res.send(renderAdminPostsView({ adminName: res.locals.admin_name, posts }));
  });

  // Add Post
  app.get('/admin/post-add.php', (req: any, res: any) => {
    const categories = getCategories(0, false);
    res.send(renderAdminPostAddView({ adminName: res.locals.admin_name, categories }));
  });

  app.post('/admin/post-add.php', (req: any, res: any) => {
    const { title, short_description, content, category_id, featured_image, custom_author_name, tags, is_featured, is_breaking, is_trending, seo_title, meta_description } = req.body;
    const slug = slugify(title);

    runQuery(
      `INSERT INTO posts (title, slug, short_description, content, category_id, author_id, custom_author_name, featured_image, tags, is_featured, is_breaking, is_trending, seo_title, meta_description, status)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')`,
      [
        title,
        slug,
        short_description,
        content,
        Number(category_id || 1),
        1,
        custom_author_name || 'Staff Reporter',
        featured_image || 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80',
        tags || '',
        is_featured ? 1 : 0,
        is_breaking ? 1 : 0,
        is_trending ? 1 : 0,
        seo_title || title,
        meta_description || short_description
      ]
    );

    res.redirect('/admin/posts.php');
  });

  // Edit Post
  app.get('/admin/post-edit.php', (req: any, res: any) => {
    const id = req.query.id;
    if (!id) return res.redirect('/admin/posts.php');

    const post = queryOne('SELECT * FROM posts WHERE id = ?', [Number(id)]);
    if (!post) return res.redirect('/admin/posts.php');

    const categories = getCategories(0, false);
    res.send(renderAdminPostEditView({ adminName: res.locals.admin_name, post, categories }));
  });


  app.post('/admin/post-edit.php', (req: any, res: any) => {
    const { id, title, short_description, content, category_id, featured_image, custom_author_name, tags, is_featured, is_breaking, is_trending, seo_title, meta_description } = req.body;

    runQuery(
      `UPDATE posts SET title = ?, short_description = ?, content = ?, category_id = ?, custom_author_name = ?, featured_image = ?, tags = ?, is_featured = ?, is_breaking = ?, is_trending = ?, seo_title = ?, meta_description = ? WHERE id = ?`,
      [
        title,
        short_description,
        content,
        Number(category_id || 1),
        custom_author_name || 'Staff Reporter',
        featured_image || '',
        tags || '',
        is_featured ? 1 : 0,
        is_breaking ? 1 : 0,
        is_trending ? 1 : 0,
        seo_title || title,
        meta_description || short_description,
        Number(id)
      ]
    );

    res.redirect('/admin/posts.php');
  });

  // Admin Categories
  app.get('/admin/categories.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM categories WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/categories.php');
    }

    const categories = getCategories(0, false);
    res.send(renderAdminCategoriesView({ adminName: res.locals.admin_name, categories }));
  });

  app.post('/admin/categories.php', (req: any, res: any) => {
    const { name, slug, description } = req.body;
    const catSlug = slug ? slugify(slug) : slugify(name);

    runQuery('INSERT INTO categories (name, slug, description, cat_order, status) VALUES (?, ?, ?, 0, 1)', [name, catSlug, description || '']);
    res.redirect('/admin/categories.php');
  });

  // Admin Homepage Layout
  app.get('/admin/homepage.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM homepage_sections WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/homepage.php');
    }

    const sections = getHomepageSections(false);
    const categories = getCategories(0, false);
    res.send(renderAdminHomepageView({ adminName: res.locals.admin_name, sections, categories }));
  });

  app.post('/admin/homepage.php', (req: any, res: any) => {
    const { title, category_id, post_limit } = req.body;
    runQuery('INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, "lead_side_list", 1, 1)', [
      title,
      Number(category_id),
      Number(post_limit || 5)
    ]);
    res.redirect('/admin/homepage.php');
  });

  // Admin Comments
  app.get('/admin/comments.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM comments WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/comments.php');
    }

    const comments = queryAll('SELECT * FROM comments ORDER BY id DESC');
    res.send(renderAdminCommentsView({ adminName: res.locals.admin_name, comments }));
  });

  // Admin Media
  app.get('/admin/media.php', (req: any, res: any) => {
    const media = queryAll('SELECT * FROM media ORDER BY id DESC');
    res.send(renderAdminMediaView({ adminName: res.locals.admin_name, media }));
  });

  // Media Library API (GET list, POST upload)
  app.get(['/admin/api_upload.php', '/api_upload.php'], (req: any, res: any) => {
    if (!req.session.admin) {
      return res.status(401).json({ success: false, error: 'Unauthorized access. Please login.' });
    }
    const search = String(req.query.search || '').trim();
    const page = Math.max(1, Number(req.query.page) || 1);
    const limit = 18;
    const offset = (page - 1) * limit;

    let countSql = 'SELECT COUNT(*) as count FROM media';
    let sql = 'SELECT * FROM media';
    let params: any[] = [];

    if (search) {
      countSql += ' WHERE filename LIKE ? OR filepath LIKE ?';
      sql += ' WHERE filename LIKE ? OR filepath LIKE ?';
      params = [`%${search}%`, `%${search}%`];
    }

    const countRow = queryOne(countSql, params);
    const total = countRow ? countRow.count : 0;

    sql += ` ORDER BY id DESC LIMIT ${limit} OFFSET ${offset}`;
    const items = queryAll(sql, params);

    return res.json({
      success: true,
      media: items,
      total: total,
      page: page,
      total_pages: Math.ceil(total / limit) || 1
    });
  });

  app.post(['/admin/api_upload.php', '/api_upload.php'], upload.single('file'), (req: any, res: any) => {
    if (!req.session.admin) {
      return res.status(401).json({ success: false, error: 'Unauthorized access. Please login.' });
    }
    if (req.file) {
      const relPath = '/uploads/media/' + req.file.filename;
      runQuery('INSERT INTO media (filename, filepath, filetype, filesize) VALUES (?, ?, ?, ?)', [
        req.file.originalname,
        relPath,
        req.file.mimetype,
        req.file.size
      ]);
      if (req.xhr || req.headers.accept?.includes('json') || req.headers['content-type']?.includes('multipart/form-data') || req.headers['sec-fetch-dest'] === 'empty') {
        return res.json({ success: true, url: relPath, filepath: relPath });
      }
    }
    res.redirect('/admin/media.php');
  });

  // Admin Gallery
  app.get('/admin/gallery.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete_album' && id) {
      runQuery('DELETE FROM gallery_albums WHERE id = ?', [Number(id)]);
      runQuery('DELETE FROM gallery_photos WHERE album_id = ?', [Number(id)]);
      return res.redirect('/admin/gallery.php');
    }

    const albums = getGalleryAlbumsWithPhotos(50);
    res.send(renderAdminGalleryView({ adminName: res.locals.admin_name, albums }));
  });

  app.post('/admin/gallery.php', (req: any, res: any) => {
    const { title, cover_image, description } = req.body;
    runQuery('INSERT INTO gallery_albums (title, slug, cover_image, description) VALUES (?, ?, ?, ?)', [title, slugify(title), cover_image || '', description || '']);
    res.redirect('/admin/gallery.php');
  });

  // Admin Videos
  app.get('/admin/videos.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM videos WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/videos.php');
    }

    const videos = getVideos(50);
    res.send(renderAdminVideosView({ adminName: res.locals.admin_name, videos }));
  });

  app.post('/admin/videos.php', (req: any, res: any) => {
    const { title, video_url, thumbnail } = req.body;
    runQuery('INSERT INTO videos (title, slug, video_url, thumbnail) VALUES (?, ?, ?, ?)', [title, slugify(title), video_url, thumbnail || '']);
    res.redirect('/admin/videos.php');
  });

  // Admin Ads
  app.get('/admin/ads.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM ads WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/ads.php');
    }

    const ads = queryAll('SELECT * FROM ads ORDER BY id DESC');
    res.send(renderAdminAdsView({ adminName: res.locals.admin_name, ads }));
  });

  app.post('/admin/ads.php', (req: any, res: any) => {
    const { position, title, image_url, target_url } = req.body;
    runQuery('INSERT OR REPLACE INTO ads (position, title, ad_type, image_url, target_url, status) VALUES (?, ?, "image", ?, ?, 1)', [
      position,
      title,
      image_url || '',
      target_url || ''
    ]);
    res.redirect('/admin/ads.php');
  });

  // Admin Menus
  app.get('/admin/menus.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM menus WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/menus.php');
    }

    const menus = queryAll('SELECT * FROM menus ORDER BY item_order ASC');
    res.send(renderAdminMenusView({ adminName: res.locals.admin_name, menus }));
  });

  app.post('/admin/menus.php', (req: any, res: any) => {
    const { title, url, location, item_order } = req.body;
    runQuery('INSERT INTO menus (location, title, url, parent_id, item_order, status) VALUES (?, ?, ?, 0, ?, 1)', [
      location || 'header',
      title,
      url,
      Number(item_order || 0)
    ]);
    res.redirect('/admin/menus.php');
  });

  // Admin Pages
  app.get('/admin/pages.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM pages WHERE id = ?', [Number(id)]);
      return res.redirect('/admin/pages.php');
    }

    const pages = queryAll('SELECT * FROM pages ORDER BY id DESC');
    res.send(renderAdminPagesView({ adminName: res.locals.admin_name, pages }));
  });

  app.post('/admin/pages.php', (req: any, res: any) => {
    const { title, slug, content } = req.body;
    runQuery('INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, 1)', [title, slugify(slug || title), content]);
    res.redirect('/admin/pages.php');
  });

  // Admin SEO
  app.get('/admin/seo.php', (req: any, res: any) => {
    res.send(renderAdminSeoView({ adminName: res.locals.admin_name, saved: req.query.saved === '1' }));
  });

  app.post('/admin/seo.php', (req: any, res: any) => {
    const { site_title, meta_description, meta_keywords } = req.body;
    if (site_title) setSetting('site_title', site_title);
    if (meta_description) setSetting('meta_description', meta_description);
    if (meta_keywords) setSetting('meta_keywords', meta_keywords);
    res.redirect('/admin/seo.php?saved=1');
  });

  // Admin Settings
  app.get('/admin/settings.php', (req: any, res: any) => {
    res.send(renderAdminSettingsView({ adminName: res.locals.admin_name, saved: req.query.saved === '1' }));
  });

  app.post('/admin/settings.php', (req: any, res: any) => {
    const fields = [
      'site_name', 'site_tagline', 'editor_name', 'publisher_name', 'phone', 'email', 'address',
      'facebook', 'twitter', 'youtube', 'header_ad_height',
      'logo_url', 'logo_height', 'logo_position',
      'header_layout_preset', 'homepage_layout_preset', 'footer_layout_preset',
      'footer_logo_url', 'footer_logo_height', 'footer_logo_position'
    ];
    for (const f of fields) {
      if (req.body[f] !== undefined) {
        setSetting(f, req.body[f]);
      }
    }
    res.redirect('/admin/settings.php?saved=1');
  });

  // Admin Color & Theme Manager
  app.get('/admin/colors.php', (req: any, res: any) => {
    res.send(renderAdminColorsView({ adminName: res.locals.admin_name, saved: req.query.saved === '1' }));
  });

  app.post('/admin/colors.php', (req: any, res: any) => {
    const colorFields = [
      'default_theme_mode', 'primary_color', 'primary_hover_color',
      'topbar_bg_color', 'topbar_text_color', 'header_bg_color', 'header_text_color',
      'menu_bg_color', 'menu_text_color', 'menu_hover_bg_color',
      'body_bg_color', 'body_text_color', 'card_bg_color', 'card_border_color',
      'title_color', 'link_hover_color', 'footer_bg_color', 'footer_text_color',
      'footer_heading_color', 'footer_link_color', 'custom_css'
    ];
    for (const f of colorFields) {
      if (req.body[f] !== undefined) {
        setSetting(f, req.body[f]);
      }
    }
    res.redirect('/admin/colors.php?saved=1');
  });

  // Admin Users
  app.get('/admin/users.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = req.query.id;

    if (action === 'delete' && id) {
      runQuery('DELETE FROM users WHERE id = ? AND username != "admin"', [Number(id)]);
      return res.redirect('/admin/users.php');
    }

    const users = queryAll('SELECT * FROM users ORDER BY id ASC');
    res.send(renderAdminUsersView({ adminName: res.locals.admin_name, users }));
  });


  app.post('/admin/users.php', (req: any, res: any) => {
    const { full_name, username, email, password, role } = req.body;
    runQuery('INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, 1)', [
      username,
      email,
      password,
      full_name,
      role || 'reporter'
    ]);
    res.redirect('/admin/users.php');
  });

  // Start Server
  app.listen(PORT, '0.0.0.0', () => {
    console.log(`PHP News Portal Server listening on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch((err) => {
  console.error('Fatal server initialization error:', err);
});
