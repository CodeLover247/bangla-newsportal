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
  seedDefaultMenus,
  runQuery,
  queryAll,
  queryOne,
  slugify,
  formatVideoEmbedUrl
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
  renderAdminColorsView,
  renderInstallerPage
} from './src/views.js';

const app = express();
const PORT = 3000;

function isInstalled(): boolean {
  return fs.existsSync(path.join(process.cwd(), 'installed.lock'));
}

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
    res.locals.custom_top_menus = getMenus('top');
    res.locals.custom_header_menus = getMenus('header');
    res.locals.custom_footer_menus = getMenus('footer');
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
     Installation Status Guard & Wizard
     ============================================================ */
  app.use((req: any, res: any, next: any) => {
    const isStatic = req.path.startsWith('/assets') || req.path.startsWith('/uploads');
    const isInstall = req.path === '/install.php';

    if (!isInstalled() && !isStatic && !isInstall) {
      return res.redirect('/install.php');
    }
    next();
  });

  app.get('/install.php', (req: any, res: any) => {
    if (isInstalled()) {
      return res.send(renderInstallerPage({ step: 1, isInstalled: true, sessionData: req.session }));
    }
    const step = Math.max(1, Math.min(3, parseInt(req.query.step as string) || 1));
    res.send(renderInstallerPage({ step, isInstalled: false, sessionData: req.session }));
  });

  app.post('/install.php', (req: any, res: any) => {
    if (isInstalled()) {
      return res.send(renderInstallerPage({ step: 1, isInstalled: true, sessionData: req.session }));
    }
    const step = Math.max(1, Math.min(3, parseInt(req.query.step as string) || 1));

    if (step === 1) {
      req.session.db_type = req.body.db_type || 'mysql';
      req.session.db_host = req.body.db_host || 'localhost';
      req.session.db_name = req.body.db_name || 'newsportal';
      req.session.db_user = req.body.db_user || 'root';
      req.session.db_pass = req.body.db_pass || '';
      return res.redirect('/install.php?step=2');
    } else if (step === 2) {
      req.session.site_name = req.body.site_name || 'দৈনিক দিগন্ত';
      req.session.admin_user = req.body.admin_user || 'admin';
      req.session.admin_email = req.body.admin_email || 'admin@newsportal.com';
      req.session.admin_pass = req.body.admin_pass || 'admin123';
      return res.redirect('/install.php?step=3');
    } else if (step === 3) {
      try {
        fs.writeFileSync(path.join(process.cwd(), 'installed.lock'), 'Installed on ' + new Date().toISOString());
        if (req.session.site_name) {
          setSetting('site_name', req.session.site_name);
        }
        return res.send(renderInstallerPage({
          step: 3,
          isInstalled: false,
          sessionData: req.session,
          success: 'Installation & Database Auto-Import Completed Successfully!'
        }));
      } catch (e: any) {
        return res.send(renderInstallerPage({
          step: 3,
          isInstalled: false,
          sessionData: req.session,
          error: 'Failed to create installed.lock file: ' + e.message
        }));
      }
    }
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
    const id = Number(req.query.id || 0);

    if (action === 'delete' && id) {
      runQuery('DELETE FROM homepage_sections WHERE id = ?', [id]);
      return res.redirect('/admin/homepage.php?msg=deleted');
    }

    if (action === 'toggle_status' && id) {
      const sec = queryOne('SELECT * FROM homepage_sections WHERE id = ?', [id]);
      if (sec) {
        runQuery('UPDATE homepage_sections SET status = ? WHERE id = ?', [sec.status === 1 ? 0 : 1, id]);
      }
      return res.redirect('/admin/homepage.php?msg=status_updated');
    }

    if ((action === 'move_up' || action === 'move_down') && id) {
      const sections = getHomepageSections(false);
      const currIdx = sections.findIndex((s: any) => s.id === id);
      if (action === 'move_up' && currIdx > 0) {
        const prev = sections[currIdx - 1];
        const curr = sections[currIdx];
        runQuery('UPDATE homepage_sections SET section_order = ? WHERE id = ?', [prev.section_order, curr.id]);
        runQuery('UPDATE homepage_sections SET section_order = ? WHERE id = ?', [curr.section_order, prev.id]);
      } else if (action === 'move_down' && currIdx >= 0 && currIdx < sections.length - 1) {
        const next = sections[currIdx + 1];
        const curr = sections[currIdx];
        runQuery('UPDATE homepage_sections SET section_order = ? WHERE id = ?', [next.section_order, curr.id]);
        runQuery('UPDATE homepage_sections SET section_order = ? WHERE id = ?', [curr.section_order, next.id]);
      }
      return res.redirect('/admin/homepage.php?msg=reordered');
    }

    if (action === 'apply_preset' && req.query.preset) {
      const preset = String(req.query.preset);
      runQuery('DELETE FROM homepage_sections');

      const categories = getCategories(0, false);
      const findCatId = (kw: string) => {
        const found = categories.find((c: any) => c.name.toLowerCase().includes(kw.toLowerCase()));
        return found ? found.id : 0;
      };

      const catNat = findCatId('জাতীয়') || findCatId('national');
      const catBar = findCatId('বরিশাল') || findCatId('barishal');
      const catPol = findCatId('রাজনীতি') || findCatId('politics');
      const catEco = findCatId('অর্থনীতি') || findCatId('business');
      const catSpo = findCatId('খেলা') || findCatId('sports');
      const catEnt = findCatId('বিনোদন') || findCatId('entertainment');
      const catTec = findCatId('প্রযুক্তি') || findCatId('tech');

      const presetsData: Record<string, any[]> = {
        classic_newspaper: [
          { title: 'জাতীয় সংবাদ', cat: catNat, limit: 5, style: 'lead_side_list' },
          { title: 'বরিশাল বিভাগ', cat: catBar, limit: 4, style: 'two_column_grid' },
          { title: 'রাজনীতি', cat: catPol, limit: 4, style: 'bento_grid' },
          { title: 'অর্থনীতি ও বাণিজ্য', cat: catEco, limit: 3, style: 'horizontal_cards' },
          { title: 'খেলাধুলা', cat: catSpo, limit: 4, style: 'carousel_slider' },
          { title: 'ভিডিও খবর ও প্রেস বুলেটিন', cat: 0, limit: 4, style: 'video_gallery_theater' },
          { title: 'ছবি গ্যালারি', cat: 0, limit: 6, style: 'photo_gallery_grid' }
        ],
        modern_portal: [
          { title: 'প্রধান খবর', cat: 0, limit: 3, style: 'bento_grid' },
          { title: 'বরিশাল অঞ্চল', cat: catBar, limit: 4, style: 'horizontal_cards' },
          { title: 'রাজনীতি ও রাষ্ট্র ব্যবস্থা', cat: catPol, limit: 4, style: 'two_column_grid' },
          { title: 'সর্বশেষ স্পটলাইট', cat: 0, limit: 6, style: 'carousel_slider' },
          { title: 'তথ্যপ্রযুক্তি ও আধুনিক গ্যাজেট', cat: catTec, limit: 4, style: 'lead_side_list' },
          { title: 'ভিডিও থিয়েটার', cat: 0, limit: 4, style: 'video_gallery_theater' }
        ],
        magazine_spotlight: [
          { title: 'স্পটলাইট হেডলাইনস', cat: 0, limit: 6, style: 'carousel_slider' },
          { title: 'বিশেষ সংবাদ ও সাক্ষাৎকার', cat: catNat, limit: 5, style: 'lead_side_list' },
          { title: 'ফটো অ্যালবাম গ্যালারি', cat: 0, limit: 6, style: 'photo_gallery_grid' },
          { title: 'বিনোদন ও তারকার খবর', cat: catEnt, limit: 4, style: 'horizontal_cards' },
          { title: 'ভিডিও বুলেটিন', cat: 0, limit: 4, style: 'video_gallery_theater' }
        ],
        compact_fast_news: [
          { title: 'দ্রুত বুলেটিন খবর', cat: 0, limit: 8, style: 'compact_list' },
          { title: 'জাতীয় খবর', cat: catNat, limit: 4, style: 'two_column_grid' },
          { title: 'বরিশাল হাইলাইটস', cat: catBar, limit: 6, style: 'compact_list' },
          { title: 'অর্থনীতি আপডেট', cat: catEco, limit: 4, style: 'horizontal_cards' },
          { title: 'খেলাধুলার আপডেট', cat: catSpo, limit: 6, style: 'compact_list' }
        ]
      };

      const selectedItems = presetsData[preset] || presetsData.classic_newspaper;
      let ord = 1;
      for (const item of selectedItems) {
        runQuery(
          'INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, 1)',
          [item.title, item.cat, item.limit, item.style, ord++]
        );
      }
      setSetting('home_layout_preset', preset);
      return res.redirect('/admin/homepage.php?msg=preset_applied');
    }

    let editSection = null;
    if (action === 'edit' && id) {
      editSection = queryOne('SELECT * FROM homepage_sections WHERE id = ?', [id]);
    }

    const sections = getHomepageSections(false);
    const categories = getCategories(0, false);
    const msg = req.query.msg || '';

    res.send(renderAdminHomepageView({
      adminName: res.locals.admin_name,
      sections,
      categories,
      editSection,
      msg,
      preset: getSetting('home_layout_preset', 'classic_newspaper')
    }));
  });

  app.post('/admin/homepage.php', (req: any, res: any) => {
    const action = req.body.action || 'add_section';

    if (action === 'global_settings') {
      setSetting('home_hero_cat', req.body.home_hero_cat || '0');
      setSetting('home_hero_limit', req.body.home_hero_limit || '5');
      setSetting('home_show_videos', req.body.home_show_videos ? '1' : '0');
      setSetting('home_show_photos', req.body.home_show_photos ? '1' : '0');
      setSetting('home_show_breaking', req.body.home_show_breaking ? '1' : '0');
      return res.redirect('/admin/homepage.php?msg=settings_updated');
    }

    if (action === 'edit_section') {
      const id = Number(req.body.section_id || 0);
      const title = String(req.body.title || '').trim();
      const category_id = Number(req.body.category_id || 0);
      const post_limit = Number(req.body.post_limit || 5);
      const layout_style = String(req.body.layout_style || 'lead_side_list');
      const section_order = Number(req.body.section_order || 1);
      const status = req.body.status ? 1 : 0;

      if (id > 0 && title) {
        runQuery(
          'UPDATE homepage_sections SET title = ?, category_id = ?, post_limit = ?, layout_style = ?, section_order = ?, status = ? WHERE id = ?',
          [title, category_id, post_limit, layout_style, section_order, status, id]
        );
      }
      return res.redirect('/admin/homepage.php?msg=updated');
    }

    // Default: Add Section
    const title = String(req.body.title || '').trim();
    const category_id = Number(req.body.category_id || 0);
    const post_limit = Number(req.body.post_limit || 5);
    const layout_style = String(req.body.layout_style || 'lead_side_list');
    const status = req.body.status !== undefined ? (req.body.status ? 1 : 0) : 1;

    if (title) {
      const maxOrdRow = queryOne('SELECT MAX(section_order) as max_ord FROM homepage_sections');
      const maxOrd = (maxOrdRow?.max_ord || 0) + 1;
      runQuery(
        'INSERT INTO homepage_sections (title, category_id, post_limit, layout_style, section_order, status) VALUES (?, ?, ?, ?, ?, ?)',
        [title, category_id, post_limit, layout_style, maxOrd, status]
      );
    }
    res.redirect('/admin/homepage.php?msg=added');
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
    const id = Number(req.query.id || 0);

    if ((action === 'delete_album' || action === 'delete') && id) {
      runQuery('DELETE FROM gallery_albums WHERE id = ?', [id]);
      runQuery('DELETE FROM gallery_photos WHERE album_id = ?', [id]);
      return res.redirect('/admin/gallery.php?msg=deleted');
    }

    if (action === 'bulk_delete' && req.query.ids) {
      const ids = String(req.query.ids).split(',').map(Number).filter(Boolean);
      if (ids.length > 0) {
        const placeholders = ids.map(() => '?').join(',');
        runQuery(`DELETE FROM gallery_albums WHERE id IN (${placeholders})`, ids);
        runQuery(`DELETE FROM gallery_photos WHERE album_id IN (${placeholders})`, ids);
      }
      return res.redirect('/admin/gallery.php?msg=bulk_deleted');
    }

    const search = String(req.query.search || '').trim();
    const view_mode = String(req.query.view_mode || 'grid');
    const page = Math.max(1, Number(req.query.page) || 1);
    const limit = 8;
    const offset = (page - 1) * limit;

    let countSql = 'SELECT COUNT(*) as count FROM gallery_albums';
    let sql = 'SELECT * FROM gallery_albums';
    let params: any[] = [];

    if (search) {
      countSql += ' WHERE title LIKE ? OR description LIKE ?';
      sql += ' WHERE title LIKE ? OR description LIKE ?';
      params = [`%${search}%`, `%${search}%`];
    }

    const countRow = queryOne(countSql, params);
    const total = countRow ? countRow.count : 0;
    const totalPages = Math.ceil(total / limit) || 1;

    sql += ` ORDER BY id DESC LIMIT ${limit} OFFSET ${offset}`;
    const albums = queryAll(sql, params);

    for (const alb of albums) {
      alb.photos = queryAll('SELECT * FROM gallery_photos WHERE album_id = ? ORDER BY id ASC', [alb.id]);
      alb.photo_count = alb.photos.length;
    }

    let editAlbum = null;
    if (action === 'edit' && id) {
      editAlbum = queryOne('SELECT * FROM gallery_albums WHERE id = ?', [id]);
    }

    res.send(renderAdminGalleryView({
      adminName: res.locals.admin_name,
      albums,
      search,
      view_mode,
      page,
      total_pages: totalPages,
      editAlbum,
      msg: req.query.msg || ''
    }));
  });

  app.post('/admin/gallery.php', (req: any, res: any) => {
    const action = req.body.action;

    if (action === 'bulk_delete') {
      let ids: number[] = [];
      if (Array.isArray(req.body.album_ids)) {
        ids = req.body.album_ids.map(Number).filter(Boolean);
      } else if (req.body.album_ids) {
        ids = String(req.body.album_ids).split(',').map(Number).filter(Boolean);
      }
      if (ids.length > 0) {
        const placeholders = ids.map(() => '?').join(',');
        runQuery(`DELETE FROM gallery_albums WHERE id IN (${placeholders})`, ids);
        runQuery(`DELETE FROM gallery_photos WHERE album_id IN (${placeholders})`, ids);
      }
      return res.redirect('/admin/gallery.php?msg=bulk_deleted');
    }

    if (action === 'edit') {
      const id = Number(req.body.album_id || 0);
      const title = String(req.body.title || '').trim();
      const cover_image = String(req.body.cover_image || '').trim();
      const description = String(req.body.description || '').trim();

      if (id > 0 && title) {
        runQuery(
          'UPDATE gallery_albums SET title = ?, slug = ?, cover_image = ?, description = ? WHERE id = ?',
          [title, slugify(title), cover_image, description, id]
        );
      }
      return res.redirect('/admin/gallery.php?msg=updated');
    }

    const title = String(req.body.title || '').trim();
    const cover_image = String(req.body.cover_image || '').trim();
    const description = String(req.body.description || '').trim();

    if (title) {
      runQuery(
        'INSERT INTO gallery_albums (title, slug, cover_image, description) VALUES (?, ?, ?, ?)',
        [title, slugify(title), cover_image || 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?w=800&auto=format&fit=crop&q=80', description]
      );
    }
    res.redirect('/admin/gallery.php?msg=created');
  });

  // Admin Videos
  app.get('/admin/videos.php', (req: any, res: any) => {
    const action = req.query.action;
    const id = Number(req.query.id || 0);

    if (action === 'delete' && id) {
      runQuery('DELETE FROM videos WHERE id = ?', [id]);
      return res.redirect('/admin/videos.php?msg=deleted');
    }

    if (action === 'bulk_delete' && req.query.ids) {
      const ids = String(req.query.ids).split(',').map(Number).filter(Boolean);
      if (ids.length > 0) {
        const placeholders = ids.map(() => '?').join(',');
        runQuery(`DELETE FROM videos WHERE id IN (${placeholders})`, ids);
      }
      return res.redirect('/admin/videos.php?msg=bulk_deleted');
    }

    const search = String(req.query.search || '').trim();
    const view_mode = String(req.query.view_mode || 'grid');
    const page = Math.max(1, Number(req.query.page) || 1);
    const limit = 8;
    const offset = (page - 1) * limit;

    let countSql = 'SELECT COUNT(*) as count FROM videos';
    let sql = 'SELECT * FROM videos';
    let params: any[] = [];

    if (search) {
      countSql += ' WHERE title LIKE ? OR description LIKE ?';
      sql += ' WHERE title LIKE ? OR description LIKE ?';
      params = [`%${search}%`, `%${search}%`];
    }

    const countRow = queryOne(countSql, params);
    const total = countRow ? countRow.count : 0;
    const totalPages = Math.ceil(total / limit) || 1;

    sql += ` ORDER BY id DESC LIMIT ${limit} OFFSET ${offset}`;
    const videos = queryAll(sql, params);

    // Format embed URLs and auto-thumbnails
    for (const v of videos) {
      const fmt = formatVideoEmbedUrl(v.video_url);
      v.embed_url = fmt.embedUrl;
      v.is_hls = fmt.isHls;
      v.is_facebook = fmt.isFacebook;
      v.is_direct_mp4 = fmt.isDirectMp4;
      v.youtube_id = fmt.youtubeId;
      if (!v.thumbnail && fmt.youtubeId) {
        v.thumbnail = `https://img.youtube.com/vi/${fmt.youtubeId}/hqdefault.jpg`;
      }
    }

    let editVideo = null;
    if (action === 'edit' && id) {
      editVideo = queryOne('SELECT * FROM videos WHERE id = ?', [id]);
    }

    res.send(renderAdminVideosView({
      adminName: res.locals.admin_name,
      videos,
      search,
      view_mode,
      page,
      total_pages: totalPages,
      editVideo,
      msg: req.query.msg || ''
    }));
  });

  app.post('/admin/videos.php', (req: any, res: any) => {
    const action = req.body.action;

    if (action === 'bulk_delete') {
      let ids: number[] = [];
      if (Array.isArray(req.body.video_ids)) {
        ids = req.body.video_ids.map(Number).filter(Boolean);
      } else if (req.body.video_ids) {
        ids = String(req.body.video_ids).split(',').map(Number).filter(Boolean);
      }
      if (ids.length > 0) {
        const placeholders = ids.map(() => '?').join(',');
        runQuery(`DELETE FROM videos WHERE id IN (${placeholders})`, ids);
      }
      return res.redirect('/admin/videos.php?msg=bulk_deleted');
    }

    if (action === 'edit') {
      const id = Number(req.body.video_id || 0);
      const title = String(req.body.title || '').trim();
      const video_url = String(req.body.video_url || '').trim();
      let thumbnail = String(req.body.thumbnail || '').trim();
      const description = String(req.body.description || '').trim();

      const fmt = formatVideoEmbedUrl(video_url);
      if (!thumbnail && fmt.youtubeId) {
        thumbnail = `https://img.youtube.com/vi/${fmt.youtubeId}/hqdefault.jpg`;
      }

      if (id > 0 && title && video_url) {
        runQuery(
          'UPDATE videos SET title = ?, slug = ?, video_url = ?, thumbnail = ?, description = ? WHERE id = ?',
          [title, slugify(title), video_url, thumbnail, description, id]
        );
      }
      return res.redirect('/admin/videos.php?msg=updated');
    }

    const title = String(req.body.title || '').trim();
    const video_url = String(req.body.video_url || '').trim();
    let thumbnail = String(req.body.thumbnail || '').trim();
    const description = String(req.body.description || '').trim();

    const fmt = formatVideoEmbedUrl(video_url);
    if (!thumbnail && fmt.youtubeId) {
      thumbnail = `https://img.youtube.com/vi/${fmt.youtubeId}/hqdefault.jpg`;
    }

    if (title && video_url) {
      runQuery(
        'INSERT INTO videos (title, slug, video_url, thumbnail, description) VALUES (?, ?, ?, ?, ?)',
        [title, slugify(title), video_url, thumbnail, description]
      );
    }
    res.redirect('/admin/videos.php?msg=published');
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
    let msg = req.query.msg || '';

    if (action === 'delete' && id) {
      runQuery('DELETE FROM menus WHERE id = ? OR parent_id = ?', [Number(id), Number(id)]);
      return res.redirect('/admin/menus.php?msg=' + encodeURIComponent('Menu item deleted!'));
    }

    if (action === 'populate_defaults') {
      seedDefaultMenus(true);
      return res.redirect('/admin/menus.php?msg=' + encodeURIComponent('Default menus populated successfully!'));
    }

    let editMenu = null;
    if (action === 'edit' && id) {
      editMenu = queryOne('SELECT * FROM menus WHERE id = ?', [Number(id)]);
    }

    const categories = getCategories(0, false);
    const pages = queryAll('SELECT * FROM pages WHERE status = 1 ORDER BY title ASC');

    const topMenus = queryAll('SELECT * FROM menus WHERE location = "top" ORDER BY item_order ASC, id ASC');
    const headerParents = queryAll('SELECT * FROM menus WHERE location = "header" AND parent_id = 0 ORDER BY item_order ASC, id ASC');
    const headerMenus: any[] = [];
    for (const hp of headerParents) {
      headerMenus.push(hp);
      const children = queryAll('SELECT * FROM menus WHERE location = "header" AND parent_id = ? ORDER BY item_order ASC, id ASC', [hp.id]);
      for (const ch of children) {
        ch.is_child = true;
        ch.parent_title = hp.title;
        headerMenus.push(ch);
      }
    }

    const footerMenus = queryAll('SELECT * FROM menus WHERE location = "footer" ORDER BY item_order ASC, id ASC');

    res.send(renderAdminMenusView({
      adminName: res.locals.admin_name,
      categories,
      pages,
      topMenus,
      headerParents,
      headerMenus,
      footerMenus,
      editMenu,
      msg
    }));
  });

  app.post('/admin/menus.php', (req: any, res: any) => {
    const { edit_id, location, parent_id, link_type, cat_slug, page_slug, url, title, item_order, target } = req.body;
    let finalUrl = url ? url.trim() : '';
    let finalTitle = title ? title.trim() : '';

    if (link_type === 'category' && cat_slug) {
      finalUrl = `/category.php?slug=${cat_slug}`;
      if (!finalTitle) {
        const cat = getCategory(cat_slug);
        finalTitle = cat ? cat.name : 'Category';
      }
    } else if (link_type === 'page' && page_slug) {
      finalUrl = `/page.php?slug=${page_slug}`;
      if (!finalTitle) {
        const pg = queryOne('SELECT title FROM pages WHERE slug = ?', [page_slug]);
        finalTitle = pg ? pg.title : 'Page';
      }
    }

    if (!finalTitle || !finalUrl) {
      return res.redirect('/admin/menus.php?msg=' + encodeURIComponent('Title and URL are required!'));
    }

    if (edit_id && Number(edit_id) > 0) {
      runQuery(
        'UPDATE menus SET location = ?, parent_id = ?, title = ?, url = ?, item_order = ?, target = ? WHERE id = ?',
        [location || 'header', Number(parent_id || 0), finalTitle, finalUrl, Number(item_order || 0), target || '_self', Number(edit_id)]
      );
      return res.redirect('/admin/menus.php?msg=' + encodeURIComponent('Menu item updated!'));
    } else {
      runQuery(
        'INSERT INTO menus (location, parent_id, title, url, item_order, target, status) VALUES (?, ?, ?, ?, ?, ?, 1)',
        [location || 'header', Number(parent_id || 0), finalTitle, finalUrl, Number(item_order || 0), target || '_self']
      );
      return res.redirect('/admin/menus.php?msg=' + encodeURIComponent('Menu item added successfully!'));
    }
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
