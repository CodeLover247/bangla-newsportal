import {
  getSetting,
  getCategories,
  getPosts,
  getPostsCount,
  getPostBySlug,
  getPostById,
  getMediaUrl,
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
  getPostDisplayAuthor
} from './db.js';

export function escapeHtml(str: any): string {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/* ============================================================
   PUBLIC LAYOUT COMPONENTS
   ============================================================ */

export function renderPublicHeader(locals: any, title = ''): string {
  const lang = locals.lang || 'bn';
  const siteName = locals.site_name || 'দৈনিক দিগন্ত';
  const pageTitle = title ? `${escapeHtml(title)} - ${escapeHtml(siteName)}` : escapeHtml(locals.site_title || siteName);
  const metaDesc = escapeHtml(locals.meta_desc || '');
  const fullDateStr = locals.full_date_str || getFullBanglaDateString(new Date());

  const categories = locals.categories || getCategories(0, true);
  const breakingNews = locals.breaking_news || getBreakingNews(6);
  const customMenus = locals.custom_header_menus || getMenus('header');

  const langSwitchUrl = (lang === 'bn') ? '?lang=en' : '?lang=bn';
  const langSwitchLabel = (lang === 'bn') ? 'English' : 'বাংলা';

  let categoriesNavHtml = '';
  categories.forEach((cat: any) => {
    const dispName = escapeHtml(getCategoryDisplayName(cat.name, lang));
    categoriesNavHtml += `<li class="nav-item">
      <a class="nav-link" href="/category.php?slug=${escapeHtml(cat.slug)}">${dispName}</a>
    </li>`;
  });

  let breakingHtml = '';
  breakingNews.forEach((bn: any) => {
    breakingHtml += `<a href="/article.php?slug=${escapeHtml(bn.slug)}" class="text-decoration-none">
      <span class="badge bg-danger me-2">${lang === 'bn' ? 'জরুরি' : 'BREAKING'}</span> ${escapeHtml(bn.title)}
    </a>`;
  });

  const logoUrl = getSetting('logo_url', '');
  const logoPos = getSetting('logo_position', 'left');
  let logoH = parseInt(getSetting('logo_height', '70'), 10);
  if (isNaN(logoH) || logoH < 15 || logoH > 400) logoH = 70;
  let logoW = parseInt(getSetting('logo_width', '0'), 10);
  if (isNaN(logoW) || logoW < 0 || logoW > 800) logoW = 0;

  let imgStyle = `max-height: ${logoH}px;`;
  if (logoW > 0) {
    imgStyle += ` max-width: ${logoW}px; width: 100%;`;
  } else {
    imgStyle += ` width: auto;`;
  }
  imgStyle += ` object-fit: contain;`;

  const siteTagline = getSetting('site_tagline', lang === 'bn' ? 'সত্যের সন্ধানে অবিরত' : 'Truth First, Always Ahead');
  const headerAdPos = getSetting('header_ad_position', 'header_top');

  const alignClass = (logoPos === 'center') ? 'text-center' : ((logoPos === 'right') ? 'text-end' : 'text-start');

  let logoBlockHtml = `<div class="site-branding ${alignClass}">
    <a href="/" class="d-inline-block text-decoration-none">
      ${logoUrl ? `<img src="${escapeHtml(logoUrl)}" alt="${escapeHtml(siteName)}" class="img-fluid site-logo-img" style="${imgStyle}">` : `<span class="site-title-logo">${escapeHtml(siteName)}</span>`}
    </a>
    ${siteTagline ? `<div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">${escapeHtml(siteTagline)}</div>` : ''}
  </div>`;

  const defaultTheme = getSetting('default_theme_mode', 'light');
  const primaryColor = getSetting('primary_color', '#e61e25');
  const primaryHover = getSetting('primary_hover_color', '#b91c1c');
  const topbarBg = getSetting('topbar_bg_color', '#0f172a');
  const topbarText = getSetting('topbar_text_color', '#f8fafc');
  const headerBg = getSetting('header_bg_color', '#ffffff');
  const headerText = getSetting('header_text_color', '#111111');
  const menuBg = getSetting('menu_bg_color', '#991b1b');
  const menuText = getSetting('menu_text_color', '#ffffff');
  const menuHoverBg = getSetting('menu_hover_bg_color', '#7f1d1d');
  const bodyBg = getSetting('body_bg_color', '#ffffff');
  const bodyText = getSetting('body_text_color', '#111111');
  const cardBg = getSetting('card_bg_color', '#ffffff');
  const cardBorder = getSetting('card_border_color', '#e5e7eb');
  const titleColor = getSetting('title_color', '#111111');
  const linkHover = getSetting('link_hover_color', '#e61e25');
  const footerBg = getSetting('footer_bg_color', '#0f172a');
  const footerText = getSetting('footer_text_color', '#94a3b8');
  const footerHeading = getSetting('footer_heading_color', '#ffffff');
  const footerLink = getSetting('footer_link_color', '#cbd5e1');
  const customCss = getSetting('custom_css', '');

  return `<!DOCTYPE html>
<html lang="${lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${pageTitle}</title>
    <meta name="description" content="${metaDesc}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.maateen.me/solaiman-lipi/font.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style id="custom-theme-vars">
    :root {
      --accent: ${primaryColor};
      --dark-accent: ${primaryHover};
      --paper: ${bodyBg};
      --ink: ${bodyText};
      --card-bg: ${cardBg};
      --border-color: ${cardBorder};
    }
    .top-bar { background-color: ${topbarBg} !important; color: ${topbarText} !important; }
    .top-bar a { color: ${topbarText} !important; }
    .main-header { background-color: ${headerBg} !important; color: ${headerText} !important; }
    .site-title-logo { color: ${titleColor} !important; }
    .main-nav { background-color: ${menuBg} !important; }
    .main-nav .nav-link { color: ${menuText} !important; }
    .main-nav .nav-link:hover, .main-nav .nav-link.active { background-color: ${menuHoverBg} !important; color: ${menuText} !important; }
    .btn-danger, .badge.bg-danger, .lang-switch-btn { background-color: ${primaryColor} !important; border-color: ${primaryColor} !important; }
    .btn-danger:hover, .lang-switch-btn:hover { background-color: ${primaryHover} !important; border-color: ${primaryHover} !important; }
    a:hover, .hover-red:hover { color: ${linkHover} !important; }
    .site-footer { background-color: ${footerBg} !important; color: ${footerText} !important; }
    .site-footer h3, .site-footer h4, .site-footer h5 { color: ${footerHeading} !important; }
    .site-footer a { color: ${footerLink} !important; }
    .article-body { text-align: justify; text-justify: inter-word; word-break: break-word; font-size: 1.15rem; line-height: 1.85; color: #1f2937; }
    .article-body p { margin-bottom: 1.25rem; text-align: justify; text-justify: inter-word; }
    .article-body img { max-width: 100%; height: auto; border-radius: 8px; margin: 1.25rem 0; shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .article-body blockquote { border-left: 4px solid ${primaryColor}; padding: 12px 18px; font-style: italic; color: #374151; background: #f8fafc; border-radius: 0 8px 8px 0; margin: 1.5rem 0; }
    .sidebar-widget { background: #ffffff; border: 1px solid ${cardBorder}; border-radius: 8px; margin-bottom: 1.25rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    .sidebar-widget-header { background: #f8fafc; border-bottom: 2px solid ${primaryColor}; padding: 10px 14px; font-weight: 700; color: #0f172a; font-size: 0.95rem; display: flex; align-items: center; justify-content: space-between; }
    .hover-shadow:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important; transition: all 0.2s ease-in-out; }
    ${customCss}
    </style>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const defaultTheme = '${defaultTheme}';
            const activeTheme = savedTheme ? savedTheme : defaultTheme;
            document.documentElement.setAttribute('data-bs-theme', activeTheme);
            if (activeTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body>
    <!-- Top Utility Bar -->
    <div class="top-bar no-print">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="small fw-semibold">
                <i class="bi bi-calendar3 me-1 text-danger"></i> ${escapeHtml(fullDateStr)}
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="/page.php?slug=about-us" class="small"><i class="bi bi-info-circle me-1"></i> ${lang === 'bn' ? 'আমাদের সম্পর্কে' : 'About Us'}</a>
                <a href="/contact.php" class="small"><i class="bi bi-envelope me-1"></i> ${lang === 'bn' ? 'যোগাযোগ' : 'Contact'}</a>
                ${getSetting('enable_translation', '1') === '1' ? `<a href="${langSwitchUrl}" class="lang-switch-btn"><i class="bi bi-translate"></i> ${langSwitchLabel}</a>` : ''}
                <button class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Dark/Light Theme">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Header Bar -->
    <header class="main-header no-print py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 col-md-6 mb-3 mb-md-0">
                    ${logoBlockHtml}
                </div>
                <div class="col-lg-7 col-md-6 text-md-end">
                    ${renderAd(headerAdPos, 'd-none d-md-block')}
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg main-nav no-print">
        <div class="container">
            <a class="navbar-brand d-lg-none fw-bold text-danger py-1" href="/">
                ${logoUrl ? `<img src="${escapeHtml(logoUrl)}" alt="${escapeHtml(siteName)}" style="max-height: 42px; max-width: 200px; width: auto; object-fit: contain;">` : `<span class="site-title-logo fs-5">${escapeHtml(siteName)}</span>`}
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="/"><i class="bi bi-house-door-fill me-1"></i> ${lang === 'bn' ? 'প্রচ্ছদ' : 'Home'}</a>
                    </li>
                    ${categoriesNavHtml}
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="/gallery.php"><i class="bi bi-images me-1"></i> ${lang === 'bn' ? 'ছবি গ্যালারি' : 'Photo Gallery'}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="/video.php"><i class="bi bi-play-btn-fill me-1"></i> ${lang === 'bn' ? 'ভিডিও খবর' : 'Video News'}</a>
                    </li>
                </ul>
                <form class="d-flex" action="/search.php" method="GET">
                    <div class="input-group input-group-sm">
                        <input class="form-control border-danger" type="search" name="q" placeholder="${lang === 'bn' ? 'সংবাদ খুঁজুন...' : 'Search news...'}" aria-label="Search" required>
                        <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- Breaking News Ticker -->
    ${breakingNews.length > 0 ? `
    <div class="breaking-ticker py-1 no-print">
        <div class="container d-flex align-items-center">
            <div class="breaking-label me-3"><i class="bi bi-lightning-charge-fill me-1"></i> ${lang === 'bn' ? escapeHtml(getSetting('breaking_news_title_bn', 'জরুরি খবর')) : escapeHtml(getSetting('breaking_news_title_en', 'BREAKING'))}</div>
            <div class="ticker-marquee flex-grow-1 overflow-hidden" style="white-space: nowrap;">
                <marquee behavior="scroll" direction="left" scrollamount="5" onmouseover="this.stop();" onmouseout="this.start();">
                    ${breakingHtml}
                </marquee>
            </div>
        </div>
    </div>
    ` : ''}
`;
}

export function renderPublicFooter(locals: any): string {
  const lang = locals.lang || 'bn';
  const siteName = locals.site_name || 'দৈনিক দিগন্ত';
  const editorName = getSetting('editor_name', '').trim();
  const publisherName = getSetting('publisher_name', '').trim();
  const chiefEditor = getSetting('chief_editor', '').trim();
  const phone = getSetting('phone', '').trim();
  const email = getSetting('email', '').trim();
  const address = getSetting('address', '').trim();
  const footerText = getSetting('footer_text', '').trim();

  const footerLogoUrl = getSetting('footer_logo_url', getSetting('logo_url', ''));
  let footerLogoH = parseInt(getSetting('footer_logo_height', '60'), 10);
  if (isNaN(footerLogoH) || footerLogoH < 20 || footerLogoH > 250) footerLogoH = 60;
  const footerLogoPos = getSetting('footer_logo_position', 'left');
  const footerAlignClass = (footerLogoPos === 'center') ? 'text-center' : ((footerLogoPos === 'right') ? 'text-end' : 'text-start');

  let footerLogoBlockHtml = `<div class="mb-3 ${footerAlignClass}">
    <a href="/" class="d-inline-block text-decoration-none">
      ${footerLogoUrl ? `<img src="${escapeHtml(footerLogoUrl)}" alt="${escapeHtml(siteName)}" class="img-fluid footer-logo-img" style="max-height: ${footerLogoH}px; width: auto; object-fit: contain;">` : `<h4 class="text-white fw-bold mb-0">${escapeHtml(siteName)}</h4>`}
    </a>
  </div>`;

  let editorialHtml = '';
  if (chiefEditor) {
    editorialHtml += `<p class="mb-1"><i class="bi bi-person-fill text-danger me-2"></i><strong>${lang === 'bn' ? 'প্রধান সম্পাদক' : 'Editor-in-Chief'}:</strong> ${escapeHtml(chiefEditor)}</p>`;
  }
  if (editorName) {
    editorialHtml += `<p class="mb-1"><i class="bi bi-person-fill text-danger me-2"></i><strong>${lang === 'bn' ? 'সম্পাদক' : 'Editor'}:</strong> ${escapeHtml(editorName)}</p>`;
  }
  if (publisherName) {
    editorialHtml += `<p class="mb-1"><i class="bi bi-building text-danger me-2"></i><strong>${lang === 'bn' ? 'প্রকাশক' : 'Publisher'}:</strong> ${escapeHtml(publisherName)}</p>`;
  }

  let contactHtml = '';
  if (address) {
    contactHtml += `<p class="mb-1"><i class="bi bi-geo-alt-fill text-danger me-2"></i> ${escapeHtml(address)}</p>`;
  }
  if (phone) {
    contactHtml += `<p class="mb-1"><i class="bi bi-telephone-fill text-danger me-2"></i> ${escapeHtml(phone)}</p>`;
  }
  if (email) {
    contactHtml += `<p class="mb-1"><i class="bi bi-envelope-fill text-danger me-2"></i> ${escapeHtml(email)}</p>`;
  }

  return `
    ${renderAd('footer_top', 'container my-4 no-print')}

    <!-- Footer -->
    <footer class="site-footer no-print">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    ${footerLogoBlockHtml}
                    ${footerText ? `<p class="small text-muted mb-3">${escapeHtml(footerText)}</p>` : ''}
                    ${editorialHtml ? `<div class="small text-light">${editorialHtml}</div>` : ''}
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-white fw-bold mb-3">${lang === 'bn' ? 'গুরুত্বপূর্ণ লিঙ্ক' : 'Important Links'}</h5>
                    <div class="row g-2 small">
                        <div class="col-6"><a href="/page.php?slug=about-us">${lang === 'bn' ? 'আমাদের সম্পর্কে' : 'About Us'}</a></div>
                        <div class="col-6"><a href="/contact.php">${lang === 'bn' ? 'যোগাযোগ' : 'Contact Us'}</a></div>
                        <div class="col-6"><a href="/page.php?slug=privacy-policy">${lang === 'bn' ? 'গোপনীয়তা নীতি' : 'Privacy Policy'}</a></div>
                        <div class="col-6"><a href="/page.php?slug=terms">${lang === 'bn' ? 'ব্যবহারের শর্তাবলী' : 'Terms of Service'}</a></div>
                        <div class="col-6"><a href="/admin/login.php" target="_blank" class="text-warning"><i class="bi bi-lock-fill me-1"></i> ${lang === 'bn' ? 'এডমিন প্যানেল' : 'Admin Panel'}</a></div>
                    </div>
                </div>
                ${contactHtml ? `
                <div class="col-lg-4 col-md-12">
                    <h5 class="text-white fw-bold mb-3">${lang === 'bn' ? 'কার্যালয়' : 'Office Contact'}</h5>
                    <div class="small text-muted">
                        ${contactHtml}
                    </div>
                </div>` : ''}
            </div>
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top border-secondary small text-muted">
                <div>${escapeHtml(lang === 'bn' ? getSetting('copyright', '© ২০২৬ ' + siteName + '। সর্বস্বত্ব সংরক্ষিত।') : '© 2026 ' + siteName + '. All rights reserved.')}</div>
                <div class="mt-2 mt-md-0">Powered & Maintained by <strong class="text-white">HosterCube Ltd</strong></div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            document.body.classList.toggle('dark-mode', newTheme === 'dark');
            localStorage.setItem('site_theme', newTheme);
            updateThemeIcon(newTheme);
        }
        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars';
            }
        }
        const savedTheme = localStorage.getItem('site_theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');
        updateThemeIcon(savedTheme);
    </script>
</body>
</html>`;
}

export function renderMiniCalendarGrid(lang: string = 'bn', dateStr?: string): string {
  let targetDate = dateStr && !isNaN(new Date(dateStr).getTime()) ? new Date(dateStr) : new Date();
  const year = targetDate.getFullYear();
  const month = targetDate.getMonth();

  const firstDayIndex = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  const prevMonthDate = new Date(year, month - 1, 1);
  const nextMonthDate = new Date(year, month + 1, 1);

  const prevMonthStr = `${prevMonthDate.getFullYear()}-${String(prevMonthDate.getMonth() + 1).padStart(2, '0')}-01`;
  const nextMonthStr = `${nextMonthDate.getFullYear()}-${String(nextMonthDate.getMonth() + 1).padStart(2, '0')}-01`;

  const monthNamesBn = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
  const monthNamesEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  const monthTitle = lang === 'bn' 
    ? `${monthNamesBn[month]} ${convertEnToBn(year)}`
    : `${monthNamesEn[month]} ${year}`;

  const weekDaysBn = ['রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র', 'শনি'];
  const weekDaysEn = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
  const weekDays = lang === 'bn' ? weekDaysBn : weekDaysEn;

  let headersHtml = weekDays.map(w => `<div class="fw-bold text-muted text-center py-1" style="width: 14.28%; font-size: 0.72rem;">${w}</div>`).join('');

  let cellsHtml = '';
  for (let i = 0; i < firstDayIndex; i++) {
    cellsHtml += `<div class="p-1" style="width: 14.28%;"></div>`;
  }

  const todayStr = new Date().toISOString().split('T')[0];

  for (let day = 1; day <= daysInMonth; day++) {
    const dayPadded = String(day).padStart(2, '0');
    const monthPadded = String(month + 1).padStart(2, '0');
    const currentCellDateStr = `${year}-${monthPadded}-${dayPadded}`;
    const isSelected = dateStr === currentCellDateStr || (!dateStr && currentCellDateStr === todayStr);

    const dayDisplay = lang === 'bn' ? convertEnToBn(day) : String(day);

    cellsHtml += `
      <div class="p-1 text-center" style="width: 14.28%;">
        <a href="/archive.php?date=${currentCellDateStr}" 
           class="btn btn-sm ${isSelected ? 'btn-danger text-white fw-bold shadow-sm' : 'btn-light text-dark border-0 hover-red'} d-inline-flex align-items-center justify-content-center w-100 p-0" 
           style="height: 26px; font-size: 0.78rem; border-radius: 4px;">
           ${dayDisplay}
        </a>
      </div>
    `;
  }

  return `
    <div class="mini-calendar-wrapper bg-light p-2 rounded border">
      <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <a href="/archive.php?date=${prevMonthStr}" class="btn btn-sm btn-outline-secondary py-0 px-2 text-decoration-none" title="${lang === 'bn' ? 'পূর্ববর্তী মাস' : 'Previous Month'}">&laquo;</a>
        <span class="fw-bold text-danger small">${monthTitle}</span>
        <a href="/archive.php?date=${nextMonthStr}" class="btn btn-sm btn-outline-secondary py-0 px-2 text-decoration-none" title="${lang === 'bn' ? 'পরবর্তী মাস' : 'Next Month'}">&raquo;</a>
      </div>
      <div class="d-flex flex-wrap border-bottom pb-1 mb-1 bg-white rounded">
        ${headersHtml}
      </div>
      <div class="d-flex flex-wrap">
        ${cellsHtml}
      </div>
    </div>
  `;
}

export function renderPublicSidebar(locals: any): string {
  const lang = locals.lang || 'bn';
  const latestPosts = locals.sidebar_latest || getPosts({ limit: 8 });
  const popularPosts = locals.sidebar_popular || getPosts({ limit: 8, order_by: 'p.views DESC' });
  const categories = locals.categories || getCategories(0, true);
  const featuredVideo = getVideos(1)[0];
  const fallbackImg = locals.fallback_img || 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&auto=format&fit=crop&q=80';

  let latestHtml = '';
  latestPosts.forEach((p: any) => {
    latestHtml += `<div class="media-news-item d-flex gap-3 mb-3 pb-3 border-bottom last-no-border">
      <img src="${escapeHtml(p.featured_image || fallbackImg)}" class="media-news-img rounded object-fit-cover" style="width: 85px; height: 65px; flex-shrink: 0;" alt="" loading="lazy">
      <div class="overflow-hidden">
        <h6 class="mb-1 fs-6 lh-sm"><a href="/article.php?slug=${escapeHtml(p.slug)}" class="text-dark text-decoration-none fw-semibold hover-red">${escapeHtml(p.title)}</a></h6>
        <small class="text-muted"><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(p.publish_date, lang))}</small>
      </div>
    </div>`;
  });

  let popularHtml = '';
  popularPosts.forEach((p: any) => {
    popularHtml += `<div class="media-news-item d-flex gap-3 mb-3 pb-3 border-bottom last-no-border">
      <img src="${escapeHtml(p.featured_image || fallbackImg)}" class="media-news-img rounded object-fit-cover" style="width: 85px; height: 65px; flex-shrink: 0;" alt="" loading="lazy">
      <div class="overflow-hidden">
        <h6 class="mb-1 fs-6 lh-sm"><a href="/article.php?slug=${escapeHtml(p.slug)}" class="text-dark text-decoration-none fw-semibold hover-red">${escapeHtml(p.title)}</a></h6>
        <small class="text-muted"><i class="bi bi-eye me-1 text-danger"></i>${p.views || 0} ${lang === 'bn' ? 'বার দেখা হয়েছে' : 'views'}</small>
      </div>
    </div>`;
  });

  let categoryPillsHtml = '';
  categories.slice(0, 12).forEach((cat: any) => {
    categoryPillsHtml += `<a href="/category.php?slug=${escapeHtml(cat.slug)}" class="badge bg-light text-dark border px-2 py-2 text-decoration-none hover-shadow me-1 mb-2 d-inline-flex align-items-center gap-1">
      <i class="bi bi-folder2-open text-danger"></i> ${escapeHtml(getCategoryDisplayName(cat.name, lang))}
    </a>`;
  });

  let videoWidgetHtml = '';
  if (featuredVideo) {
    videoWidgetHtml = `<div class="sidebar-widget mb-4">
      <div class="sidebar-widget-header">
        <span><i class="bi bi-play-circle-fill text-danger me-1"></i> ${lang === 'bn' ? 'ভিডিও বুলেটিন' : 'Video Bulletin'}</span>
        <a href="/video.php" class="small text-danger text-decoration-none fw-bold">${lang === 'bn' ? 'সব ভিডিও &rarr;' : 'All Videos &rarr;'}</a>
      </div>
      <div class="p-2">
        <div class="position-relative rounded overflow-hidden">
          <img src="${escapeHtml(featuredVideo.thumbnail || fallbackImg)}" class="w-100 object-fit-cover rounded" style="height: 180px;" alt="">
          <a href="/video.php" class="position-absolute top-50 start-50 translate-middle text-white fs-1 opacity-75 hover-opacity-100">
            <i class="bi bi-play-btn-fill text-danger bg-white rounded-circle"></i>
          </a>
        </div>
        <h6 class="fw-bold fs-6 mt-2 mb-0"><a href="/video.php" class="text-dark text-decoration-none hover-red">${escapeHtml(featuredVideo.title)}</a></h6>
      </div>
    </div>`;
  }

  const fbUrl = getSetting('facebook', 'https://facebook.com');
  const twUrl = getSetting('twitter', 'https://twitter.com');
  const ytUrl = getSetting('youtube', 'https://youtube.com');

  return `
    <div class="sticky-top" style="top: 80px; z-index: 10;">
        ${renderAd('sidebar_top', 'mb-4')}
        
        <!-- Tabbed Widget: Latest vs Popular -->
        <div class="card border shadow-sm mb-4 hero-tab-widget">
            <div class="card-header bg-dark text-white p-0">
                <ul class="nav nav-tabs nav-fill border-0" id="sidebarTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-2.5 px-3 border-0 text-white rounded-0" id="latest-tab" data-bs-toggle="tab" data-bs-target="#latest-news" type="button" role="tab">
                            <i class="bi bi-clock-history me-1"></i> ${lang === 'bn' ? 'সর্বশেষ খবর' : 'Latest News'}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-2.5 px-3 border-0 text-white-50 rounded-0" id="popular-tab" data-bs-toggle="tab" data-bs-target="#popular-news" type="button" role="tab">
                            <i class="bi bi-fire me-1 text-warning"></i> ${lang === 'bn' ? 'জনপ্রিয় খবর' : 'Popular'}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-3">
                <div class="tab-content" id="sidebarTabContent">
                    <div class="tab-pane fade show active" id="latest-news" role="tabpanel">
                        ${latestHtml}
                    </div>
                    <div class="tab-pane fade" id="popular-news" role="tabpanel">
                        ${popularHtml}
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Explorer Widget -->
        <div class="sidebar-widget mb-4">
            <div class="sidebar-widget-header">
                <span><i class="bi bi-grid-fill text-danger me-1"></i> ${lang === 'bn' ? 'বিষয়ভিত্তিক সংবাদ' : 'Browse Categories'}</span>
            </div>
            <div class="p-3">
                <div class="d-flex flex-wrap">
                    ${categoryPillsHtml}
                </div>
            </div>
        </div>

        <!-- Archive Calendar Widget -->
        <div class="sidebar-widget mb-4 card border shadow-sm">
            <div class="card-header bg-white border-bottom py-2 px-3 fw-bold text-dark d-flex align-items-center justify-content-between">
                <span><i class="bi bi-calendar3 text-danger me-1"></i> ${lang === 'bn' ? 'আর্কাইভ ক্যালেন্ডার' : 'Archive Calendar'}</span>
                <a href="/archive.php" class="small text-danger text-decoration-none fw-semibold">${lang === 'bn' ? 'সব আর্কাইভ &rarr;' : 'All Archives &rarr;'}</a>
            </div>
            <div class="card-body p-3">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white text-danger border-danger"><i class="bi bi-calendar-event"></i></span>
                    <input type="date" id="sidebarArchiveDatePicker" class="form-control form-control-sm border-danger fw-bold text-center" value="${locals.selected_date || ''}" onchange="if(this.value) window.location.href='/archive.php?date='+this.value">
                    <button class="btn btn-danger btn-sm" type="button" onclick="var d=document.getElementById('sidebarArchiveDatePicker').value; if(d) window.location.href='/archive.php?date='+d;"><i class="bi bi-search"></i></button>
                </div>
                ${renderMiniCalendarGrid(lang, locals.selected_date)}
            </div>
        </div>

        ${videoWidgetHtml}

        <!-- Social Media & Newsletter Subscription -->
        <div class="sidebar-widget mb-4 bg-light border p-3 rounded shadow-sm text-center">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-envelope-paper-fill text-danger me-1"></i> ${lang === 'bn' ? 'আমাদের সাথে থাকুন' : 'Stay Connected'}</h6>
            <p class="small text-muted mb-3">${lang === 'bn' ? 'সর্বশেষ ব্রেকিং খবরের আপডেট পেতে ইমেইল সাবস্ক্রাইব করুন' : 'Subscribe to get daily breaking news updates'}</p>
            <form action="#" method="POST" onsubmit="alert('${lang === 'bn' ? 'সাবস্ক্রিপশনের জন্য আপনাকে ধন্যবাদ!' : 'Thank you for subscribing!'}'); return false;">
                <div class="input-group input-group-sm mb-3">
                    <input type="email" class="form-control" placeholder="${lang === 'bn' ? 'আপনার ইমেইল' : 'Enter email'}" required>
                    <button class="btn btn-danger fw-bold" type="submit">${lang === 'bn' ? 'যুক্ত হোন' : 'Subscribe'}</button>
                </div>
            </form>
            <div class="d-flex justify-content-center gap-2">
                <a href="${escapeHtml(fbUrl)}" target="_blank" class="btn btn-sm btn-primary rounded-circle"><i class="bi bi-facebook"></i></a>
                <a href="${escapeHtml(twUrl)}" target="_blank" class="btn btn-sm btn-info text-white rounded-circle"><i class="bi bi-twitter-x"></i></a>
                <a href="${escapeHtml(ytUrl)}" target="_blank" class="btn btn-sm btn-danger rounded-circle"><i class="bi bi-youtube"></i></a>
            </div>
        </div>

        ${renderAd('sidebar_bottom', 'mb-4')}
    </div>
  `;
}

/* ============================================================
   ADMIN LAYOUT & PAGES
   ============================================================ */

export function renderAdminHeader(title = 'Dashboard', currentPage = 'index', adminName = 'Admin'): string {
  const menuItems = [
    { key: 'index', label: 'Dashboard', icon: 'bi-grid-1x2-fill', url: '/admin/index.php' },
    { key: 'posts', label: 'News Articles', icon: 'bi-newspaper', url: '/admin/posts.php' },
    { key: 'categories', label: 'Categories', icon: 'bi-folder2-open', url: '/admin/categories.php' },
    { key: 'homepage', label: 'Homepage Layout', icon: 'bi-window-stack', url: '/admin/homepage.php' },
    { key: 'comments', label: 'Comments', icon: 'bi-chat-dots-fill', url: '/admin/comments.php' },
    { key: 'media', label: 'Media Library', icon: 'bi-images', url: '/admin/media.php' },
    { key: 'gallery', label: 'Photo Gallery', icon: 'bi-camera-fill', url: '/admin/gallery.php' },
    { key: 'videos', label: 'Video News', icon: 'bi-play-btn-fill', url: '/admin/videos.php' },
    { key: 'ads', label: 'Banner Ads', icon: 'bi-badge-ad-fill', url: '/admin/ads.php' },
    { key: 'colors', label: 'Color & Theme', icon: 'bi-palette-fill', url: '/admin/colors.php' },
    { key: 'menus', label: 'Menus', icon: 'bi-menu-button-wide', url: '/admin/menus.php' },
    { key: 'pages', label: 'Custom Pages', icon: 'bi-file-earmark-text-fill', url: '/admin/pages.php' },
    { key: 'seo', label: 'SEO Settings', icon: 'bi-search', url: '/admin/seo.php' },
    { key: 'settings', label: 'Site Settings', icon: 'bi-gear-fill', url: '/admin/settings.php' },
    { key: 'users', label: 'Users', icon: 'bi-people-fill', url: '/admin/users.php' }
  ];

  let navLinksHtml = '';
  menuItems.forEach((m) => {
    const active = currentPage === m.key ? 'active bg-danger text-white' : 'text-dark hover-bg-light';
    navLinksHtml += `<a href="${m.url}" class="nav-link px-3 py-2 rounded mb-1 d-flex align-items-center gap-2 fw-semibold ${active}">
      <i class="bi ${m.icon}"></i> ${m.label}
    </a>`;
  });

  return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHtml(title)} - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f8fafc; }
        .sidebar { width: 260px; min-height: 100vh; background: #ffffff; border-right: 1px solid #e2e8f0; position: fixed; top: 0; left: 0; z-index: 1000; padding: 20px 15px; }
        .main-wrapper { margin-left: 260px; padding: 25px; }
        .stat-card { background: #ffffff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; }
        .stat-title { color: #64748b; font-size: 0.82rem; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; }
        .card-table { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden; }
        .card-table-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .card-table-header h5 { margin: 0; font-weight: 700; font-size: 1.1rem; }
        @media (max-width: 991.98px) {
            .sidebar { position: relative; width: 100%; min-height: auto; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="d-flex align-items-center gap-2 mb-4 px-2">
            <i class="bi bi-newspaper text-danger fs-3"></i>
            <span class="fw-bold fs-5 text-dark">${escapeHtml(getSetting('site_name', 'News CMS'))}</span>
        </div>
        <div class="nav flex-column">
            ${navLinksHtml}
        </div>
        <div class="mt-4 pt-3 border-top px-2">
            <div class="small text-muted mb-2"><i class="bi bi-person-circle me-1"></i> Logged in as <strong>${escapeHtml(adminName)}</strong></div>
            <a href="/admin/clear-cache.php" class="btn btn-sm btn-outline-warning text-dark fw-semibold w-100 mb-2"><i class="bi bi-arrow-repeat me-1"></i> Clear Cache</a>
            <a href="/" target="_blank" class="btn btn-sm btn-outline-secondary w-100 mb-2"><i class="bi bi-box-arrow-up-right me-1"></i> View Website</a>
            <a href="/admin/logout.php" class="btn btn-sm btn-danger w-100"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
        </div>
    </div>
    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark m-0">${escapeHtml(title)}</h3>
            <span class="badge bg-danger px-3 py-2 fs-6">Live Production</span>
        </div>
`;
}

export function renderAdminFooter(): string {
  return `
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>`;
}

export function renderHomeView(locals: any): string {
  const lang = locals.lang || 'bn';
  const featuredPosts = locals.featured_posts || getPosts({ is_featured: 1, limit: 5 });
  const leadPost = featuredPosts[0] || getPosts({ limit: 1 })[0];
  const sideLeadPosts = featuredPosts.slice(1, 5);
  const sections = locals.homepage_sections || getHomepageSections(true);
  const fallbackImg = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80';

  let heroHtml = '';
  if (leadPost) {
    let sideLeadHtml = '';
    sideLeadPosts.forEach((sp: any) => {
      sideLeadHtml += `<div class="col-6 mb-3">
        <div class="news-card h-100 border rounded overflow-hidden">
          <div class="news-card-img-wrapper">
            <img src="${escapeHtml(sp.featured_image || fallbackImg)}" alt="" loading="lazy">
          </div>
          <div class="p-2">
            <h6 class="fs-6 fw-bold lh-sm mb-1"><a href="/article.php?slug=${escapeHtml(sp.slug)}" class="text-dark text-decoration-none hover-red">${escapeHtml(sp.title)}</a></h6>
            <small class="text-muted"><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(sp.publish_date, lang))}</small>
          </div>
        </div>
      </div>`;
    });

    heroHtml = `<div class="row g-3 mb-4">
      <div class="col-lg-7">
        <div class="hero-lead-card position-relative rounded overflow-hidden shadow-sm h-100">
          <img src="${escapeHtml(leadPost.featured_image || fallbackImg)}" class="w-100 object-fit-cover" style="height: 420px;" alt="">
          <div class="hero-overlay">
            <span class="badge bg-danger mb-2 px-2 py-1 uppercase fw-bold">${escapeHtml(getCategoryDisplayName(leadPost.category_name || 'জাতীয়', lang))}</span>
            <h2 class="lh-sm mb-2"><a href="/article.php?slug=${escapeHtml(leadPost.slug)}">${escapeHtml(leadPost.title)}</a></h2>
            <p class="small text-light opacity-75 d-none d-md-block mb-2">${escapeHtml(leadPost.short_description || '')}</p>
            <small class="text-light opacity-75"><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(leadPost.publish_date, lang))}</small>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="row g-2">
          ${sideLeadHtml}
        </div>
      </div>
    </div>`;
  }

  let sectionsHtml = '';
  sections.forEach((sec: any) => {
    const secPosts = getPosts({ category_id: sec.category_id, limit: sec.post_limit || 5 });
    if (secPosts.length === 0) return;

    let secGridHtml = '';
    secPosts.forEach((p: any) => {
      secGridHtml += `<div class="col-md-6 col-lg-4 mb-3">
        <div class="news-card border rounded overflow-hidden h-100 shadow-sm">
          <div class="news-card-img-wrapper">
            <img src="${escapeHtml(p.featured_image || fallbackImg)}" alt="" loading="lazy">
            <span class="category-badge">${escapeHtml(getCategoryDisplayName(p.category_name || sec.title, lang))}</span>
          </div>
          <div class="p-3">
            <h5 class="news-title fs-6 fw-bold lh-sm mb-2"><a href="/article.php?slug=${escapeHtml(p.slug)}" class="text-dark text-decoration-none hover-red">${escapeHtml(p.title)}</a></h5>
            <p class="small text-muted mb-2 line-clamp-2">${escapeHtml(p.short_description || '')}</p>
            <div class="d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
              <span><i class="bi bi-person me-1"></i>${escapeHtml(getPostDisplayAuthor(p))}</span>
              <span><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(p.publish_date, lang))}</span>
            </div>
          </div>
        </div>
      </div>`;
    });

    sectionsHtml += `<section class="homepage-section mb-4">
      <div class="section-title d-flex justify-content-between align-items-center mb-3">
        <span>${escapeHtml(getCategoryDisplayName(sec.title, lang))}</span>
        ${sec.category_slug ? `<a href="/category.php?slug=${escapeHtml(sec.category_slug)}" class="btn btn-sm btn-outline-danger fw-bold rounded-pill">${lang === 'bn' ? 'সব খবর' : 'View All'} &rarr;</a>` : ''}
      </div>
      <div class="row g-3">
        ${secGridHtml}
      </div>
    </section>`;
  });

  const headerHtml = renderPublicHeader(locals);
  const footerHtml = renderPublicFooter(locals);
  const sidebarHtml = renderPublicSidebar(locals);

  return `${headerHtml}
    <main class="container my-4">
        ${renderAd('homepage_top', 'mb-4')}
        ${heroHtml}
        <div class="row g-4">
            <div class="col-lg-8">
                ${sectionsHtml}
            </div>
            <div class="col-lg-4">
                ${sidebarHtml}
            </div>
        </div>
    </main>
    ${footerHtml}`;
}

export function renderArticleView(locals: any): string {
  const lang = locals.lang || 'bn';
  const post = locals.post;
  const fallbackImg = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80';

  if (!post) {
    return `${renderPublicHeader(locals, 'Article Not Found')}
      <div class="container my-5 py-5 text-center">
        <h2 class="fw-bold text-danger">খবরটি পাওয়া যায়নি</h2>
        <p class="text-muted">আপনি যে সংবাদটি খুঁজছেন তা মুছে ফেলা হয়েছে বা স্থানান্তর করা হয়েছে।</p>
        <a href="/" class="btn btn-danger mt-3">&larr; প্রচ্ছদে ফিরে যান</a>
      </div>
      ${renderPublicFooter(locals)}`;
  }

  const author = getPostDisplayAuthor(post);
  const relatedPosts = locals.related_posts || getPosts({ category_id: post.category_id, limit: 4 });
  const comments = locals.comments || [];

  let relatedHtml = '';
  relatedPosts.forEach((rp: any) => {
    if (rp.id === post.id) return;
    relatedHtml += `<div class="col-md-6 mb-3">
      <div class="news-card border rounded overflow-hidden h-100">
        <div class="news-card-img-wrapper" style="aspect-ratio: 16/9;">
          <img src="${escapeHtml(rp.featured_image || fallbackImg)}" alt="" loading="lazy">
        </div>
        <div class="p-3">
          <h6 class="fw-bold lh-sm mb-1"><a href="/article.php?slug=${escapeHtml(rp.slug)}" class="text-dark text-decoration-none">${escapeHtml(rp.title)}</a></h6>
          <small class="text-muted"><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(rp.publish_date, lang))}</small>
        </div>
      </div>
    </div>`;
  });

  let commentsHtml = '';
  comments.forEach((c: any) => {
    commentsHtml += `<div class="p-3 border rounded mb-3 bg-light">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <strong class="text-dark"><i class="bi bi-person-circle text-danger me-1"></i>${escapeHtml(c.name)}</strong>
        <small class="text-muted">${escapeHtml(timeAgo(c.created_at, lang))}</small>
      </div>
      <p class="m-0 small text-secondary">${escapeHtml(c.comment)}</p>
    </div>`;
  });

  const headerHtml = renderPublicHeader(locals, post.title);
  const footerHtml = renderPublicFooter(locals);
  const sidebarHtml = renderPublicSidebar(locals);

  return `${headerHtml}
    <main class="container my-4">
      <nav aria-label="breadcrumb" class="mb-3 no-print">
        <ol class="breadcrumb small">
          <li class="breadcrumb-item"><a href="/">${lang === 'bn' ? 'প্রচ্ছদ' : 'Home'}</a></li>
          <li class="breadcrumb-item"><a href="/category.php?slug=${escapeHtml(post.category_slug)}">${escapeHtml(getCategoryDisplayName(post.category_name, lang))}</a></li>
          <li class="breadcrumb-item active">${escapeHtml(post.title)}</li>
        </ol>
      </nav>

      <div class="row g-4">
        <div class="col-lg-8">
          <article class="article-wrapper bg-white border p-4 rounded shadow-sm">
            <span class="badge bg-danger mb-2 px-3 py-1 uppercase fw-bold">${escapeHtml(getCategoryDisplayName(post.category_name, lang))}</span>
            <h1 class="article-title fw-bold text-dark mb-3 lh-sm fs-2">${escapeHtml(post.title)}</h1>
            
            <div class="article-meta d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 border-top border-bottom my-3">
              <div class="d-flex align-items-center gap-2">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" alt="">
                <div>
                  <div class="fw-bold text-dark small">${escapeHtml(author)}</div>
                  <div class="text-muted small"><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(post.publish_date, lang))} | <i class="bi bi-eye me-1"></i>${post.views || 1} ${lang === 'bn' ? 'পঠিত' : 'reads'}</div>
                </div>
              </div>
              <div class="share-bar d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i> ${lang === 'bn' ? 'প্রিন্ট' : 'Print'}</button>
                <a href="https://facebook.com/sharer/sharer.php?u=${encodeURIComponent('http://localhost:3000/article.php?slug=' + post.slug)}" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(post.title)}&url=${encodeURIComponent('http://localhost:3000/article.php?slug=' + post.slug)}" target="_blank" class="btn btn-sm btn-info text-white"><i class="bi bi-twitter"></i></a>
              </div>
            </div>

            ${renderAd('article_top', 'my-3')}

            ${post.featured_image ? `
            <div class="my-3 rounded overflow-hidden">
              <img src="${escapeHtml(getMediaUrl(post.featured_image))}" class="w-100 object-fit-cover rounded" style="max-height: 480px;" alt="">
              <div class="text-muted small italic text-center mt-1">${escapeHtml(post.title)}</div>
            </div>` : ''}

            <div class="article-body my-4 text-dark" style="text-align: justify; text-justify: inter-word; font-size: 1.15rem; line-height: 1.85; word-break: break-word;">
              ${post.content}
            </div>

            ${renderAd('article_bottom', 'my-4')}

            <!-- Related Articles -->
            <div class="related-section border-top pt-4 mt-5">
              <h4 class="fw-bold text-dark mb-3"><i class="bi bi-grid-fill text-danger me-2"></i>${lang === 'bn' ? 'সম্পর্কিত খবর' : 'Related Articles'}</h4>
              <div class="row g-3">
                ${relatedHtml}
              </div>
            </div>

            <!-- Comments Section -->
            <div class="comments-section border-top pt-4 mt-4" id="comments">
              <h4 class="fw-bold text-dark mb-3"><i class="bi bi-chat-left-text-fill text-danger me-2"></i>${lang === 'bn' ? 'পাঠকদের মন্তব্য' : 'Reader Comments'} (${comments.length})</h4>
              ${commentsHtml}

              <div class="card bg-light border p-3 mt-4 no-print">
                <h6 class="fw-bold mb-3 text-dark">${lang === 'bn' ? 'আপনার মন্তব্য দিন' : 'Leave a Comment'}</h6>
                <form action="/article.php" method="POST">
                  <input type="hidden" name="post_id" value="${post.id}">
                  <input type="hidden" name="slug" value="${escapeHtml(post.slug)}">
                  <div class="row g-2 mb-2">
                    <div class="col-md-6">
                      <input type="text" name="name" class="form-control form-control-sm" placeholder="${lang === 'bn' ? 'আপনার নাম' : 'Your Name'}" required>
                    </div>
                    <div class="col-md-6">
                      <input type="email" name="email" class="form-control form-control-sm" placeholder="${lang === 'bn' ? 'ইমেইল এড্রেস' : 'Your Email'}" required>
                    </div>
                  </div>
                  <div class="mb-2">
                    <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="${lang === 'bn' ? 'মন্তব্য লিখুন...' : 'Write your comment...'}" required></textarea>
                  </div>
                  <button type="submit" class="btn btn-danger btn-sm fw-bold"><i class="bi bi-send me-1"></i> ${lang === 'bn' ? 'মন্তব্য পাঠান' : 'Submit Comment'}</button>
                </form>
              </div>
            </div>
          </article>
        </div>
        <div class="col-lg-4">
          ${sidebarHtml}
        </div>
      </div>
    </main>
    ${footerHtml}`;
}

export function renderCategoryView(locals: any): string {
  const lang = locals.lang || 'bn';
  const category = locals.category;
  const posts = locals.posts || [];
  const fallbackImg = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80';

  if (!category) {
    return `${renderPublicHeader(locals, lang === 'bn' ? 'ক্যাটাগরি পাওয়া যায়নি' : 'Category Not Found')}
      <div class="container my-5 text-center">
        <h2 class="text-danger fw-bold">${lang === 'bn' ? 'ক্যাটাগরি পাওয়া যায়নি' : 'Category Not Found'}</h2>
        <a href="/" class="btn btn-danger mt-3">&larr; ${lang === 'bn' ? 'প্রচ্ছদে ফিরে যান' : 'Back to Home'}</a>
      </div>
      ${renderPublicFooter(locals)}`;
  }

  let postsGridHtml = '';
  posts.forEach((p: any) => {
    postsGridHtml += `<div class="col-md-6 mb-4">
      <div class="news-card border rounded overflow-hidden h-100 shadow-sm">
        <div class="news-card-img-wrapper" style="aspect-ratio: 16/9;">
          <img src="${escapeHtml(p.featured_image || fallbackImg)}" alt="" loading="lazy">
        </div>
        <div class="p-3">
          <h5 class="fw-bold lh-sm mb-2"><a href="/article.php?slug=${escapeHtml(p.slug)}" class="text-dark text-decoration-none hover-red">${escapeHtml(p.title)}</a></h5>
          <p class="small text-muted mb-2 line-clamp-2">${escapeHtml(p.short_description || '')}</p>
          <small class="text-muted"><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(p.publish_date, lang))}</small>
        </div>
      </div>
    </div>`;
  });

  const headerHtml = renderPublicHeader(locals, getCategoryDisplayName(category.name, lang));
  const footerHtml = renderPublicFooter(locals);
  const sidebarHtml = renderPublicSidebar(locals);

  return `${headerHtml}
    <main class="container my-4">
      <div class="section-title mb-4">
        <span>${escapeHtml(getCategoryDisplayName(category.name, lang))}</span>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="row">
            ${postsGridHtml || `<div class="col-12 py-5 text-center text-muted">${lang === 'bn' ? 'এই ক্যাটাগরিতে কোনো সংবাদ প্রকাশিত হয়নি।' : 'No news articles found in this category.'}</div>`}
          </div>
        </div>
        <div class="col-lg-4">
          ${sidebarHtml}
        </div>
      </div>
    </main>
    ${footerHtml}`;
}

export function renderGalleryView(locals: any): string {
  const lang = locals.lang || 'bn';
  const albums = locals.albums || getGalleryAlbumsWithPhotos(20);
  const headerHtml = renderPublicHeader(locals, lang === 'bn' ? 'ছবি গ্যালারি' : 'Photo Gallery');
  const footerHtml = renderPublicFooter(locals);

  let albumsHtml = '';
  albums.forEach((alb: any) => {
    albumsHtml += `<div class="col-md-6 col-lg-4 mb-4">
      <div class="card h-100 border shadow-sm">
        <img src="${escapeHtml(alb.cover_image || 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?w=800&auto=format&fit=crop&q=80')}" class="card-img-top object-fit-cover" style="height: 220px;" alt="">
        <div class="card-body">
          <h5 class="card-title fw-bold text-dark">${escapeHtml(alb.title)}</h5>
          <p class="card-text small text-muted">${escapeHtml(alb.description || '')}</p>
          <span class="badge bg-danger"><i class="bi bi-camera-fill me-1"></i> ${alb.photos ? alb.photos.length : 0} ${lang === 'bn' ? 'টি ছবি' : 'Photos'}</span>
        </div>
      </div>
    </div>`;
  });

  return `${headerHtml}
    <main class="container my-4">
      <div class="section-title mb-4">
        <span>${lang === 'bn' ? 'ছবি গ্যালারি ও ফটো অ্যালবাম' : 'Photo Gallery & Albums'}</span>
      </div>
      <div class="row">
        ${albumsHtml || `<div class="col-12 text-center py-5 text-muted">${lang === 'bn' ? 'কোনো ফটো অ্যালবাম যুক্ত করা হয়নি।' : 'No photo albums available.'}</div>`}
      </div>
    </main>
    ${footerHtml}`;
}

export function renderVideoView(locals: any): string {
  const lang = locals.lang || 'bn';
  const videos = locals.videos || getVideos(20);
  const headerHtml = renderPublicHeader(locals, lang === 'bn' ? 'ভিডিও খবর' : 'Video News');
  const footerHtml = renderPublicFooter(locals);

  let vidsHtml = '';
  videos.forEach((v: any) => {
    vidsHtml += `<div class="col-md-6 col-lg-4 mb-4">
      <div class="card h-100 border shadow-sm overflow-hidden">
        <div class="ratio ratio-16x9">
          <iframe src="${escapeHtml(v.embed_url)}" title="${escapeHtml(v.title)}" allowfullscreen></iframe>
        </div>
        <div class="card-body">
          <h6 class="fw-bold text-dark mb-1">${escapeHtml(v.title)}</h6>
          <p class="small text-muted m-0">${escapeHtml(v.description || '')}</p>
        </div>
      </div>
    </div>`;
  });

  return `${headerHtml}
    <main class="container my-4">
      <div class="section-title mb-4">
        <span>${lang === 'bn' ? 'ভিডিও খবর ও প্রেস বুলেটিন' : 'Video News & Bulletins'}</span>
      </div>
      <div class="row">
        ${vidsHtml || `<div class="col-12 text-center py-5 text-muted">${lang === 'bn' ? 'কোনো ভিডিও খবর যুক্ত করা হয়নি।' : 'No video news available.'}</div>`}
      </div>
    </main>
    ${footerHtml}`;
}

export function renderPageView(locals: any): string {
  const lang = locals.lang || 'bn';
  const page = locals.page;
  const headerHtml = renderPublicHeader(locals, page ? page.title : (lang === 'bn' ? 'পেজ পাওয়া যায়নি' : 'Page Not Found'));
  const footerHtml = renderPublicFooter(locals);

  if (!page) {
    return `${headerHtml}
      <div class="container my-5 text-center">
        <h2 class="text-danger fw-bold">${lang === 'bn' ? 'পেজ পাওয়া যায়নি' : 'Page Not Found'}</h2>
        <a href="/" class="btn btn-danger mt-3">&larr; ${lang === 'bn' ? 'প্রচ্ছদে ফিরে যান' : 'Back to Home'}</a>
      </div>
      ${footerHtml}`;
  }

  return `${headerHtml}
    <main class="container my-5">
      <div class="bg-white border p-4 rounded shadow-sm">
        <h2 class="fw-bold text-dark border-bottom pb-2 mb-4">${escapeHtml(page.title)}</h2>
        <div class="lh-lg fs-5">
          ${page.content}
        </div>
      </div>
    </main>
    ${footerHtml}`;
}

export function renderSearchView(locals: any): string {
  const lang = locals.lang || 'bn';
  const query = locals.query || '';
  const posts = locals.posts || [];
  const headerHtml = renderPublicHeader(locals, `${lang === 'bn' ? 'অনুসন্ধান' : 'Search'}: ${query}`);
  const footerHtml = renderPublicFooter(locals);
  const sidebarHtml = renderPublicSidebar(locals);
  const fallbackImg = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80';

  let postsGridHtml = '';
  posts.forEach((p: any) => {
    postsGridHtml += `<div class="col-md-6 mb-4">
      <div class="news-card border rounded overflow-hidden h-100 shadow-sm">
        <div class="news-card-img-wrapper" style="aspect-ratio: 16/9;">
          <img src="${escapeHtml(p.featured_image || fallbackImg)}" alt="" loading="lazy">
        </div>
        <div class="p-3">
          <h5 class="fw-bold lh-sm mb-2"><a href="/article.php?slug=${escapeHtml(p.slug)}" class="text-dark text-decoration-none hover-red">${escapeHtml(p.title)}</a></h5>
          <p class="small text-muted mb-2 line-clamp-2">${escapeHtml(p.short_description || '')}</p>
        </div>
      </div>
    </div>`;
  });

  return `${headerHtml}
    <main class="container my-4">
      <div class="section-title mb-4">
        <span>${lang === 'bn' ? 'অনুসন্ধান ফলাফল' : 'Search Results'}: "${escapeHtml(query)}"</span>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="row">
            ${postsGridHtml || `<div class="col-12 py-5 text-center text-muted">${lang === 'bn' ? 'আপনার অনুসন্ধান সম্পর্কিত কোনো খবর পাওয়া যায়নি।' : 'No news articles found matching your query.'}</div>`}
          </div>
        </div>
        <div class="col-lg-4">
          ${sidebarHtml}
        </div>
      </div>
    </main>
    ${footerHtml}`;
}

export function renderArchiveView(locals: any): string {
  const lang = locals.lang || 'bn';
  const dateStr = locals.selected_date || new Date().toISOString().split('T')[0];
  const posts = locals.posts || [];
  const headerHtml = renderPublicHeader(locals, `${lang === 'bn' ? 'সংবাদ আর্কাইভ' : 'News Archive'}: ${dateStr}`);
  const footerHtml = renderPublicFooter(locals);
  const sidebarHtml = renderPublicSidebar(locals);
  const fallbackImg = 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80';

  let dateFormatted = dateStr;
  if (dateStr) {
    const dObj = new Date(dateStr);
    if (!isNaN(dObj.getTime())) {
      dateFormatted = lang === 'bn'
        ? getFullBanglaDateString(dObj)
        : dObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
  }

  let postsGridHtml = '';
  if (posts.length === 0) {
    postsGridHtml = `
      <div class="col-12">
        <div class="alert alert-warning p-4 rounded text-center my-3 shadow-sm border">
          <i class="bi bi-calendar-x fs-1 text-warning d-block mb-2"></i>
          <h5 class="fw-bold">${lang === 'bn' ? 'এই তারিখে কোনো প্রকাশিত সংবাদ পাওয়া যায়নি।' : 'No news articles found for this date.'}</h5>
          <p class="mb-0 text-muted small">${lang === 'bn' ? 'অনুগ্রহ করে পাশের বা ক্যালেন্ডারের তারিখ থেকে অন্য কোনো সময়কাল নির্বাচন করুন।' : 'Please select another date from the calendar to browse news.'}</p>
        </div>
      </div>
    `;
  } else {
    posts.forEach((p: any) => {
      postsGridHtml += `
        <div class="col-md-6 mb-4">
          <div class="card h-100 border shadow-sm hover-shadow transition">
            <div class="position-relative overflow-hidden" style="height: 180px;">
              <img src="${escapeHtml(p.featured_image || fallbackImg)}" class="w-100 h-100 object-fit-cover" alt="${escapeHtml(p.title)}">
              <span class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 small fw-bold">
                ${escapeHtml(getCategoryDisplayName(p.category_name, lang))}
              </span>
            </div>
            <div class="card-body d-flex flex-column p-3">
              <h5 class="card-title fs-6 fw-bold mb-2">
                <a href="/article.php?slug=${escapeHtml(p.slug)}" class="text-dark text-decoration-none hover-red">
                  ${escapeHtml(p.title)}
                </a>
              </h5>
              <p class="card-text small text-muted mb-3 flex-grow-1 line-clamp-2">
                ${escapeHtml(p.short_description || '')}
              </p>
              <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span><i class="bi bi-clock me-1"></i>${escapeHtml(timeAgo(p.publish_date, lang))}</span>
                <span><i class="bi bi-eye me-1"></i>${p.views || 0}</span>
              </div>
            </div>
          </div>
        </div>
      `;
    });
  }

  return `${headerHtml}
    <main class="container my-4">
      <div class="bg-light border rounded p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 shadow-sm">
        <div>
          <h4 class="fw-bold text-dark mb-1"><i class="bi bi-calendar3-event text-danger me-2"></i>${lang === 'bn' ? 'সংবাদ আর্কাইভ' : 'News Archive'}</h4>
          <p class="text-muted mb-0 small"><i class="bi bi-calendar-check me-1 text-danger"></i>${dateFormatted} — <strong class="text-dark">${posts.length}</strong> ${lang === 'bn' ? 'টি সংবাদ প্রকাশিত হয়েছে' : 'articles published'}</p>
        </div>
        <div class="d-flex align-items-center gap-2 bg-white p-2 border rounded">
          <label class="form-label mb-0 fw-bold small text-nowrap text-secondary"><i class="bi bi-filter me-1"></i>${lang === 'bn' ? 'তারিখ পরিবর্তন:' : 'Change Date:'}</label>
          <input type="date" id="archiveMainDatePicker" class="form-control form-control-sm fw-bold border-danger" value="${dateStr}" onchange="if(this.value) window.location.href='/archive.php?date='+this.value">
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="row">
            ${postsGridHtml}
          </div>
        </div>
        <div class="col-lg-4">
          ${sidebarHtml}
        </div>
      </div>
    </main>
    ${footerHtml}`;
}

export function renderContactView(locals: any): string {
  const lang = locals.lang || 'bn';
  const headerHtml = renderPublicHeader(locals, lang === 'bn' ? 'যোগাযোগ' : 'Contact Us');
  const footerHtml = renderPublicFooter(locals);

  return `${headerHtml}
    <main class="container my-5">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="bg-white border p-4 rounded shadow-sm h-100">
            <h3 class="fw-bold text-dark mb-3"><i class="bi bi-envelope-at-fill text-danger me-2"></i>${lang === 'bn' ? 'আমাদের বার্তা পাঠান' : 'Send Us a Message'}</h3>
            <form action="#" method="POST" onsubmit="alert('${lang === 'bn' ? 'বার্তা পাঠানো হয়েছে! ধন্যবাদ।' : 'Message sent! Thank you.'}'); return false;">
              <div class="mb-3">
                <label class="form-label fw-bold">${lang === 'bn' ? 'আপনার নাম' : 'Your Name'}</label>
                <input type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">${lang === 'bn' ? 'ইমেইল এড্রেস' : 'Email Address'}</label>
                <input type="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">${lang === 'bn' ? 'বিষয়' : 'Subject'}</label>
                <input type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">${lang === 'bn' ? 'বার্তা' : 'Message'}</label>
                <textarea class="form-control" rows="4" required></textarea>
              </div>
              <button type="submit" class="btn btn-danger fw-bold w-100 py-2"><i class="bi bi-send me-1"></i> ${lang === 'bn' ? 'বার্তা প্রেরণ করুন' : 'Send Message'}</button>
            </form>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="bg-white border p-4 rounded shadow-sm h-100">
            <h3 class="fw-bold text-dark mb-3"><i class="bi bi-building-fill text-danger me-2"></i>${lang === 'bn' ? 'সম্পাদকীয় কার্যালয়' : 'Editorial Office'}</h3>
            <p class="text-secondary">${escapeHtml(getSetting('address', 'Level 8, Horizon Tower, Dhaka'))}</p>
            <p class="mb-2"><strong>${lang === 'bn' ? 'ফোন' : 'Phone'}:</strong> ${escapeHtml(getSetting('phone', '+880 2 98765432'))}</p>
            <p class="mb-2"><strong>${lang === 'bn' ? 'ইমেইল' : 'Email'}:</strong> ${escapeHtml(getSetting('email', 'contact@dailyhorizon.com'))}</p>
            <p class="mb-4"><strong>${lang === 'bn' ? 'কর্মঘণ্টা' : 'Office Hours'}:</strong> ${escapeHtml(getSetting('office_time', lang === 'bn' ? '২৪/৭ নিউজ রুম' : '24/7 Newsroom'))}</p>
          </div>
        </div>
      </div>
    </main>
    ${footerHtml}`;
}

/* ============================================================
   ADMIN VIEWS
   ============================================================ */

export function renderAdminDashboardView(data: any): string {
  const stats = data.stats || {};
  const recentPosts = data.recent_posts || [];

  let rowsHtml = '';
  recentPosts.forEach((p: any) => {
    rowsHtml += `<tr>
      <td>${p.id}</td>
      <td class="fw-bold">${escapeHtml(p.title)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(p.category_name || 'General')}</span></td>
      <td>${p.views || 0}</td>
      <td><a href="/admin/post-edit.php?id=${p.id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Dashboard Overview', 'index', data.adminName)}
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon bg-danger"><i class="bi bi-newspaper"></i></div>
          <div>
            <div class="stat-title">Total Articles</div>
            <div class="stat-value">${stats.total_posts || 0}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon bg-primary"><i class="bi bi-folder"></i></div>
          <div>
            <div class="stat-title">Categories</div>
            <div class="stat-value">${stats.total_categories || 0}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon bg-success"><i class="bi bi-chat-dots"></i></div>
          <div>
            <div class="stat-title">Comments</div>
            <div class="stat-value">${stats.total_comments || 0}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon bg-warning text-dark"><i class="bi bi-eye"></i></div>
          <div>
            <div class="stat-title">Total Views</div>
            <div class="stat-value">${stats.total_views || 0}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-table">
      <div class="card-table-header">
        <h5>Recent News Articles</h5>
        <a href="/admin/post-add.php" class="btn btn-sm btn-danger"><i class="bi bi-plus-lg me-1"></i> Add New Article</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Category</th>
              <th>Views</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml || '<tr><td colspan="5" class="text-center py-4 text-muted">No articles created yet.</td></tr>'}
          </tbody>
        </table>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminPostsView(data: any): string {
  const posts = data.posts || [];
  let rowsHtml = '';

  posts.forEach((p: any) => {
    rowsHtml += `<tr>
      <td>${p.id}</td>
      <td>
        <div class="fw-bold">${escapeHtml(p.title)}</div>
        <small class="text-muted">Slug: ${escapeHtml(p.slug)}</small>
      </td>
      <td><span class="badge bg-secondary">${escapeHtml(p.category_name || 'General')}</span></td>
      <td>${p.views || 0}</td>
      <td><span class="badge bg-success">${p.status || 'published'}</span></td>
      <td>
        <a href="/admin/post-edit.php?id=${p.id}" class="btn btn-sm btn-primary me-1"><i class="bi bi-pencil"></i> Edit</a>
        <a href="/admin/posts.php?action=delete&id=${p.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete this article?');"><i class="bi bi-trash"></i></a>
      </td>
    </tr>`;
  });

  return `${renderAdminHeader('News Articles Management', 'posts', data.adminName)}
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="/admin/post-add.php" class="btn btn-danger"><i class="bi bi-plus-circle me-1"></i> Publish New Article</a>
    </div>

    <div class="card-table">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Category</th>
              <th>Views</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml || '<tr><td colspan="6" class="text-center py-4 text-muted">No articles found.</td></tr>'}
          </tbody>
        </table>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminPostAddView(data: any): string {
  const categories = data.categories || [];
  let catOptsHtml = '';
  categories.forEach((c: any) => {
    catOptsHtml += `<option value="${c.id}">${escapeHtml(c.name)}</option>`;
  });

  return `${renderAdminHeader('Publish New Article', 'posts', data.adminName)}
    <div class="card border p-4 shadow-sm bg-white">
      <form action="/admin/post-add.php" method="POST">
        <div class="row g-3">
          <div class="col-md-8">
            <div class="mb-3">
              <label class="form-label fw-bold">Article Title</label>
              <input type="text" name="title" class="form-control form-control-lg" required placeholder="Enter news headline...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Short Summary / Teaser</label>
              <textarea name="short_description" class="form-control" rows="2" required placeholder="Brief introductory summary..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Full Article Content (HTML/Text)</label>
              <textarea name="content" class="form-control" rows="12" required placeholder="Write full news article content..."></textarea>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Category</label>
              <select name="category_id" class="form-select" required>
                ${catOptsHtml}
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Featured Image URL</label>
              <input type="text" name="featured_image" class="form-control" placeholder="https://..." value="https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&auto=format&fit=crop&q=80">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Reporter / Author Name</label>
              <input type="text" name="custom_author_name" class="form-control" value="Staff Reporter">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Tags (comma separated)</label>
              <input type="text" name="tags" class="form-control" placeholder="National, Breaking, Govt">
            </div>
            <div class="mb-3 border p-3 rounded bg-light">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="f1">
                <label class="form-check-label fw-bold" for="f1">Featured Lead Article</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_breaking" value="1" id="f2">
                <label class="form-check-label fw-bold text-danger" for="f2">Breaking News Ticker</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_trending" value="1" id="f3">
                <label class="form-check-label fw-bold" for="f3">Trending News</label>
              </div>
            </div>
            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold"><i class="bi bi-check-circle me-1"></i> Publish Article</button>
          </div>
        </div>
      </form>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminPostEditView(data: any): string {
  const post = data.post;
  const categories = data.categories || [];

  let catOptsHtml = '';
  categories.forEach((c: any) => {
    const sel = c.id === post.category_id ? 'selected' : '';
    catOptsHtml += `<option value="${c.id}" ${sel}>${escapeHtml(c.name)}</option>`;
  });

  return `${renderAdminHeader('Edit Article', 'posts', data.adminName)}
    <div class="card border p-4 shadow-sm bg-white">
      <form action="/admin/post-edit.php" method="POST">
        <input type="hidden" name="id" value="${post.id}">
        <div class="row g-3">
          <div class="col-md-8">
            <div class="mb-3">
              <label class="form-label fw-bold">Article Title</label>
              <input type="text" name="title" class="form-control form-control-lg" value="${escapeHtml(post.title)}" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Short Summary</label>
              <textarea name="short_description" class="form-control" rows="2" required>${escapeHtml(post.short_description)}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Full Content</label>
              <textarea name="content" class="form-control" rows="12" required>${escapeHtml(post.content)}</textarea>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Category</label>
              <select name="category_id" class="form-select" required>
                ${catOptsHtml}
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Featured Image URL</label>
              <input type="text" name="featured_image" class="form-control" value="${escapeHtml(post.featured_image)}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Reporter Name</label>
              <input type="text" name="custom_author_name" class="form-control" value="${escapeHtml(post.custom_author_name || 'Staff Reporter')}">
            </div>
            <div class="mb-3 border p-3 rounded bg-light">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="f1" ${post.is_featured ? 'checked' : ''}>
                <label class="form-check-label fw-bold" for="f1">Featured Lead Article</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_breaking" value="1" id="f2" ${post.is_breaking ? 'checked' : ''}>
                <label class="form-check-label fw-bold text-danger" for="f2">Breaking News Ticker</label>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-save me-1"></i> Save Changes</button>
          </div>
        </div>
      </form>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminCategoriesView(data: any): string {
  const categories = data.categories || [];
  let rowsHtml = '';

  categories.forEach((c: any) => {
    rowsHtml += `<tr>
      <td>${c.id}</td>
      <td class="fw-bold">${escapeHtml(c.name)}</td>
      <td><code>${escapeHtml(c.slug)}</code></td>
      <td><a href="/admin/categories.php?action=delete&id=${c.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete category?');"><i class="bi bi-trash"></i> Delete</a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Categories Management', 'categories', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 shadow-sm bg-white">
          <h5 class="fw-bold mb-3">Add New Category</h5>
          <form action="/admin/categories.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Category Name</label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Sports">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Slug (optional)</label>
              <input type="text" name="slug" class="form-control" placeholder="sports">
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-plus-lg me-1"></i> Create Category</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Slug</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminHomepageView(data: any): string {
  const sections = data.sections || [];
  const categories = data.categories || [];

  let secRowsHtml = '';
  sections.forEach((s: any) => {
    secRowsHtml += `<tr>
      <td>${s.id}</td>
      <td class="fw-bold">${escapeHtml(s.title)}</td>
      <td>${escapeHtml(s.category_name || 'All Categories')}</td>
      <td>${s.post_limit || 5}</td>
      <td><a href="/admin/homepage.php?action=delete&id=${s.id}" class="btn btn-sm btn-danger" onclick="return confirm('Remove section?');"><i class="bi bi-trash"></i></a></td>
    </tr>`;
  });

  let catOpts = '';
  categories.forEach((c: any) => {
    catOpts += `<option value="${c.id}">${escapeHtml(c.name)}</option>`;
  });

  return `${renderAdminHeader('Homepage Layout Manager', 'homepage', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 shadow-sm bg-white">
          <h5 class="fw-bold mb-3">Add Homepage Block</h5>
          <form action="/admin/homepage.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Block Heading</label>
              <input type="text" name="title" class="form-control" required placeholder="e.g. National News">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Source Category</label>
              <select name="category_id" class="form-select">
                <option value="0">All Categories</option>
                ${catOpts}
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Articles Limit</label>
              <input type="number" name="post_limit" class="form-control" value="5" min="1" max="12">
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-plus-lg me-1"></i> Add Block</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Limit</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${secRowsHtml}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminCommentsView(data: any): string {
  const comments = data.comments || [];
  let rowsHtml = '';

  comments.forEach((c: any) => {
    rowsHtml += `<tr>
      <td>${c.id}</td>
      <td class="fw-bold">${escapeHtml(c.name)}<br><small class="text-muted">${escapeHtml(c.email)}</small></td>
      <td>${escapeHtml(c.comment)}</td>
      <td><a href="/admin/comments.php?action=delete&id=${c.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete comment?');"><i class="bi bi-trash"></i></a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Reader Comments', 'comments', data.adminName)}
    <div class="card-table">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Author</th>
            <th>Comment</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml || '<tr><td colspan="4" class="text-center py-4 text-muted">No comments posted yet.</td></tr>'}
        </tbody>
      </table>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminMediaView(data: any): string {
  const media = data.media || [];
  const search = data.search || '';
  const filterType = data.filter_type || '';
  const page = data.page || 1;
  const totalPages = data.total_pages || 1;
  let gridHtml = '';

  media.forEach((m: any) => {
    gridHtml += `<div class="col-md-2 col-sm-4 mb-3">
      <div class="card h-100 border p-2 text-center shadow-sm">
        <img src="${escapeHtml(m.filepath)}" class="w-100 rounded object-fit-cover mb-2" style="height: 100px;" alt="" onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&auto=format&fit=crop&q=80'">
        <small class="text-truncate d-block fw-bold">${escapeHtml(m.filename)}</small>
        <a href="/admin/media.php?action=delete&id=${m.id}" class="text-danger small mt-1 d-block text-decoration-none" onclick="return confirm('Delete media file?');"><i class="bi bi-trash"></i> Delete</a>
      </div>
    </div>`;
  });

  return `${renderAdminHeader('Media Library', 'media', data.adminName)}
    <div class="card border p-3 mb-4 bg-white shadow-sm">
      <h6 class="fw-bold mb-3"><i class="bi bi-cloud-arrow-up text-danger me-2"></i> Upload New Media File</h6>
      <form action="/admin/api_upload.php" method="POST" enctype="multipart/form-data">
        <div class="input-group">
          <input type="file" name="file" class="form-control" required>
          <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-upload me-1"></i> Upload File</button>
        </div>
      </form>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border p-3 mb-4 bg-white shadow-sm">
      <form method="GET" action="/admin/media.php" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group input-group-sm">
            <input type="text" name="search" class="form-control" placeholder="Search filename..." value="${escapeHtml(search)}">
            <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
            ${search || filterType ? `<a href="/admin/media.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>` : ''}
          </div>
        </div>
        <div class="col-md-6 text-md-end">
          <div class="btn-group btn-group-sm">
            <a href="/admin/media.php?search=${encodeURIComponent(search)}" class="btn btn-${!filterType ? 'danger' : 'outline-secondary'}">All Files</a>
            <a href="/admin/media.php?type=image&search=${encodeURIComponent(search)}" class="btn btn-${filterType === 'image' ? 'danger' : 'outline-secondary'}"><i class="bi bi-image me-1"></i> Images</a>
            <a href="/admin/media.php?type=video&search=${encodeURIComponent(search)}" class="btn btn-${filterType === 'video' ? 'danger' : 'outline-secondary'}"><i class="bi bi-camera-reels me-1"></i> Videos</a>
          </div>
        </div>
      </form>
    </div>

    <div class="row">
      ${gridHtml || '<div class="col-12 text-center py-5 text-muted">No media files found.</div>'}
    </div>

    ${totalPages > 1 ? `<div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
      <span class="small text-muted">Page ${page} of ${totalPages}</span>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="/admin/media.php?search=${encodeURIComponent(search)}&type=${encodeURIComponent(filterType)}&page=${page - 1}">Previous</a></li>
        <li class="page-item active"><span class="page-link bg-danger border-danger">${page}</span></li>
        <li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="/admin/media.php?search=${encodeURIComponent(search)}&type=${encodeURIComponent(filterType)}&page=${page + 1}">Next</a></li>
      </ul>
    </div>` : ''}
    ${renderAdminFooter()}`;
}

export function renderAdminGalleryView(data: any): string {
  const albums = data.albums || [];
  const search = data.search || '';
  const page = data.page || 1;
  const totalPages = data.total_pages || 1;
  let rowsHtml = '';

  albums.forEach((a: any) => {
    rowsHtml += `<tr>
      <td>${a.id}</td>
      <td class="fw-bold">
        <img src="${escapeHtml(a.cover_image || 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?w=100&auto=format&fit=crop&q=80')}" class="rounded me-2" style="width: 45px; height: 32px; object-fit: cover;" alt="">
        ${escapeHtml(a.title)}
      </td>
      <td>${a.photos ? a.photos.length : 0} photos</td>
      <td><a href="/admin/gallery.php?action=delete_album&id=${a.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete album?');"><i class="bi bi-trash"></i> Delete</a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Photo Gallery Management', 'gallery', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 bg-white shadow-sm">
          <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-danger me-1"></i> Create Photo Album</h5>
          <form action="/admin/gallery.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Album Title *</label>
              <input type="text" name="title" class="form-control" required placeholder="e.g. Political Rally Coverage">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Cover Image URL</label>
              <input type="text" name="cover_image" class="form-control" value="https://images.unsplash.com/photo-1541872703-74c5e44368f9?w=800&auto=format&fit=crop&q=80">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Description</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Brief album summary..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-camera me-1"></i> Create Album</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <!-- Search Filter -->
        <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
          <form method="GET" action="/admin/gallery.php">
            <div class="input-group input-group-sm">
              <input type="text" name="search" class="form-control" placeholder="Search album by title..." value="${escapeHtml(search)}">
              <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
              ${search ? `<a href="/admin/gallery.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>` : ''}
            </div>
          </form>
        </div>

        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Album Title</th>
                <th>Photo Count</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml || '<tr><td colspan="4" class="text-center py-4 text-muted">No albums created yet.</td></tr>'}
            </tbody>
          </table>
        </div>

        ${totalPages > 1 ? `<div class="d-flex justify-content-between align-items-center mt-3">
          <span class="small text-muted">Page ${page} of ${totalPages}</span>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="/admin/gallery.php?search=${encodeURIComponent(search)}&page=${page - 1}">Previous</a></li>
            <li class="page-item active"><span class="page-link bg-danger border-danger">${page}</span></li>
            <li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="/admin/gallery.php?search=${encodeURIComponent(search)}&page=${page + 1}">Next</a></li>
          </ul>
        </div>` : ''}
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminVideosView(data: any): string {
  const videos = data.videos || [];
  const search = data.search || '';
  const page = data.page || 1;
  const totalPages = data.total_pages || 1;
  let rowsHtml = '';

  videos.forEach((v: any) => {
    rowsHtml += `<tr>
      <td>${v.id}</td>
      <td class="fw-bold">${escapeHtml(v.title)}</td>
      <td><a href="${escapeHtml(v.video_url)}" target="_blank" class="btn btn-xs btn-outline-primary py-0 small"><i class="bi bi-play-circle me-1"></i> Watch Video</a></td>
      <td><a href="/admin/videos.php?action=delete&id=${v.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete video?');"><i class="bi bi-trash"></i> Delete</a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Video News Manager', 'videos', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 bg-white shadow-sm">
          <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-danger me-1"></i> Add Video News Headline</h5>
          <form action="/admin/videos.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Video Title *</label>
              <input type="text" name="title" class="form-control" required placeholder="Headline title...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">YouTube / Embed Video URL *</label>
              <input type="text" name="video_url" class="form-control" required placeholder="https://www.youtube.com/watch?v=...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Thumbnail Image URL</label>
              <input type="text" name="thumbnail" class="form-control" placeholder="https://example.com/thumb.jpg">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Description</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Brief video summary..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-play-circle me-1"></i> Publish Video</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <!-- Search Filter -->
        <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
          <form method="GET" action="/admin/videos.php">
            <div class="input-group input-group-sm">
              <input type="text" name="search" class="form-control" placeholder="Search video by title..." value="${escapeHtml(search)}">
              <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
              ${search ? `<a href="/admin/videos.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>` : ''}
            </div>
          </form>
        </div>

        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Media Link</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml || '<tr><td colspan="4" class="text-center py-4 text-muted">No video headlines found.</td></tr>'}
            </tbody>
          </table>
        </div>

        ${totalPages > 1 ? `<div class="d-flex justify-content-between align-items-center mt-3">
          <span class="small text-muted">Page ${page} of ${totalPages}</span>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="/admin/videos.php?search=${encodeURIComponent(search)}&page=${page - 1}">Previous</a></li>
            <li class="page-item active"><span class="page-link bg-danger border-danger">${page}</span></li>
            <li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="/admin/videos.php?search=${encodeURIComponent(search)}&page=${page + 1}">Next</a></li>
          </ul>
        </div>` : ''}
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminAdsView(data: any): string {
  const ads = data.ads || [];
  let rowsHtml = '';

  ads.forEach((a: any) => {
    rowsHtml += `<tr>
      <td>${a.id}</td>
      <td class="fw-bold"><code>${escapeHtml(a.position)}</code></td>
      <td>${escapeHtml(a.title)}</td>
      <td><a href="/admin/ads.php?action=delete&id=${a.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete banner?');"><i class="bi bi-trash"></i></a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Banner Advertisement Manager', 'ads', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 bg-white shadow-sm">
          <h5 class="fw-bold mb-3">Set Banner Ad</h5>
          <form action="/admin/ads.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Position Key</label>
              <select name="position" class="form-select">
                <option value="header_top">Header Top Leaderboard</option>
                <option value="sidebar_top">Sidebar Top Square</option>
                <option value="sidebar_bottom">Sidebar Bottom Square</option>
                <option value="article_top">In-Article Top</option>
                <option value="article_bottom">In-Article Bottom</option>
                <option value="homepage_top">Homepage Top</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Banner Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Banner Image URL</label>
              <input type="text" name="image_url" class="form-control" required placeholder="https://...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Click Target Link</label>
              <input type="text" name="target_url" class="form-control" value="https://example.com">
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-save me-1"></i> Save Ad</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Position</th>
                <th>Title</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml || '<tr><td colspan="4" class="text-center py-4 text-muted">No ads configured yet.</td></tr>'}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminMenusView(data: any): string {
  const menus = data.menus || [];
  let rowsHtml = '';

  menus.forEach((m: any) => {
    rowsHtml += `<tr>
      <td>${m.id}</td>
      <td class="fw-bold">${escapeHtml(m.title)}</td>
      <td><code>${escapeHtml(m.url)}</code></td>
      <td><a href="/admin/menus.php?action=delete&id=${m.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete menu?');"><i class="bi bi-trash"></i></a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Custom Menus Manager', 'menus', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 bg-white shadow-sm">
          <h5 class="fw-bold mb-3">Add Menu Item</h5>
          <form action="/admin/menus.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Menu Label</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">URL Path</label>
              <input type="text" name="url" class="form-control" required placeholder="/category.php?slug=sports">
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-plus-lg me-1"></i> Add Menu</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>URL</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml || '<tr><td colspan="4" class="text-center py-4 text-muted">No custom menus added yet.</td></tr>'}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminPagesView(data: any): string {
  const pages = data.pages || [];
  let rowsHtml = '';

  pages.forEach((p: any) => {
    rowsHtml += `<tr>
      <td>${p.id}</td>
      <td class="fw-bold">${escapeHtml(p.title)}</td>
      <td><code>/page.php?slug=${escapeHtml(p.slug)}</code></td>
      <td><a href="/admin/pages.php?action=delete&id=${p.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete page?');"><i class="bi bi-trash"></i></a></td>
    </tr>`;
  });

  return `${renderAdminHeader('Custom Pages Manager', 'pages', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 bg-white shadow-sm">
          <h5 class="fw-bold mb-3">Create Page</h5>
          <form action="/admin/pages.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Page Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Page Content (HTML)</label>
              <textarea name="content" class="form-control" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-plus-lg me-1"></i> Save Page</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Page Title</th>
                <th>URL</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml || '<tr><td colspan="4" class="text-center py-4 text-muted">No pages created yet.</td></tr>'}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminSeoView(data: any): string {
  return `${renderAdminHeader('SEO Configuration', 'seo', data.adminName)}
    ${data.saved ? '<div class="alert alert-success py-2">SEO Settings updated successfully!</div>' : ''}
    <div class="card border p-4 bg-white shadow-sm max-w-lg">
      <form action="/admin/seo.php" method="POST">
        <div class="mb-3">
          <label class="form-label fw-bold">Site Meta Title</label>
          <input type="text" name="site_title" class="form-control" value="${escapeHtml(getSetting('site_title', 'Daily Horizon - Truth First'))}">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Meta Description</label>
          <textarea name="meta_description" class="form-control" rows="3">${escapeHtml(getSetting('meta_description', ''))}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Meta Keywords</label>
          <input type="text" name="meta_keywords" class="form-control" value="${escapeHtml(getSetting('meta_keywords', ''))}">
        </div>
        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-save me-1"></i> Save SEO Settings</button>
      </form>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminSettingsView(data: any): string {
  const hlp = getSetting('header_layout_preset', 'standard');
  const hplp = getSetting('homepage_layout_preset', 'standard');
  const flp = getSetting('footer_layout_preset', 'standard');
  const mhp = getSetting('mobile_header_preset', 'standard');
  const msnl = getSetting('mobile_show_nav_logo', '0');

  return `${renderAdminHeader('Site Settings', 'settings', data.adminName)}
    ${data.saved ? '<div class="alert alert-success py-2">Site Settings updated successfully!</div>' : ''}
    <div class="card border p-4 bg-white shadow-sm mb-4">
      <h5 class="fw-bold text-danger mb-3"><i class="bi bi-palette2 me-2"></i> Prebuilt Layout & Design Presets</h5>
      <p class="text-muted small mb-3">Select structural layout design presets for your Header, Homepage, and Footer. Public site adapts automatically!</p>
      <form action="/admin/settings.php" method="POST">
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label fw-bold">Header Preset</label>
            <select name="header_layout_preset" class="form-select border-danger">
              <option value="standard" ${hlp === 'standard' ? 'selected' : ''}>1. Standard Classic News Header</option>
              <option value="centered" ${hlp === 'centered' ? 'selected' : ''}>2. Centered Brand Logo Header</option>
              <option value="compact" ${hlp === 'compact' ? 'selected' : ''}>3. Compact Modern Bar Header</option>
              <option value="magazine" ${hlp === 'magazine' ? 'selected' : ''}>4. Magazine Double-Border Header</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Homepage Preset</label>
            <select name="homepage_layout_preset" class="form-select border-danger">
              <option value="standard" ${hplp === 'standard' ? 'selected' : ''}>1. Standard News Portal (Classic Stream)</option>
              <option value="magazine" ${hplp === 'magazine' ? 'selected' : ''}>2. Modern Magazine Grid (Hero Banners)</option>
              <option value="minimalist" ${hplp === 'minimalist' ? 'selected' : ''}>3. Clean Editorial Grid (Spacious)</option>
              <option value="portal" ${hplp === 'portal' ? 'selected' : ''}>4. High-Density News Portal (Feature Tabs)</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Footer Preset</label>
            <select name="footer_layout_preset" class="form-select border-danger">
              <option value="standard" ${flp === 'standard' ? 'selected' : ''}>1. Standard 4-Column Newspaper Footer</option>
              <option value="centered" ${flp === 'centered' ? 'selected' : ''}>2. Minimalist Centered Brand Footer</option>
              <option value="newspaper_broad" ${flp === 'newspaper_broad' ? 'selected' : ''}>3. Broad Editorial Board Footer</option>
              <option value="dark_modern" ${flp === 'dark_modern' ? 'selected' : ''}>4. Dark Modern Magazine Footer</option>
            </select>
          </div>

          <div class="col-md-6 mt-3">
            <label class="form-label fw-bold"><i class="bi bi-phone text-danger me-1"></i> Mobile Header Preset Layout</label>
            <select name="mobile_header_preset" class="form-select border-primary fw-semibold">
              <option value="standard" ${mhp === 'standard' ? 'selected' : ''}>1. Standard Mobile Header (Top Main Logo + Sticky Nav Bar)</option>
              <option value="compact_sticky" ${mhp === 'compact_sticky' ? 'selected' : ''}>2. Compact Sticky Mobile Bar (Hides top logo on mobile, shows inside navbar)</option>
              <option value="centered_brand" ${mhp === 'centered_brand' ? 'selected' : ''}>3. Centered Brand Mobile Header (Centered Logo + Slim Action Bar)</option>
              <option value="app_style" ${mhp === 'app_style' ? 'selected' : ''}>4. App-Style Minimal Top Bar (Logo + Search + Mobile Drawer)</option>
            </select>
          </div>

          <div class="col-md-6 mt-3">
            <label class="form-label fw-bold"><i class="bi bi-intersect text-danger me-1"></i> Mobile Navbar Brand Logo Display</label>
            <select name="mobile_show_nav_logo" class="form-select border-primary fw-semibold">
              <option value="0" ${msnl === '0' ? 'selected' : ''}>Hide Logo Image in Sticky Navbar on Mobile (Fixes Double Logo)</option>
              <option value="1" ${msnl === '1' ? 'selected' : ''}>Show Logo Image in Sticky Navbar on Mobile</option>
              <option value="text_only" ${msnl === 'text_only' ? 'selected' : ''}>Show Text Site Name Only in Sticky Navbar on Mobile</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"><i class="bi bi-save me-1"></i> Save Layout Presets</button>
      </form>
    </div>

    <div class="card border p-4 bg-white shadow-sm">
      <form action="/admin/settings.php" method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-bold">Newspaper Title / Name</label>
              <input type="text" name="site_name" class="form-control" value="${escapeHtml(getSetting('site_name', 'দৈনিক দিগন্ত'))}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Tagline</label>
              <input type="text" name="site_tagline" class="form-control" value="${escapeHtml(getSetting('site_tagline', 'সত্যের সন্ধানে অবিরত'))}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Header Logo Image URL</label>
              <input type="text" name="logo_url" class="form-control" value="${escapeHtml(getSetting('logo_url', ''))}" placeholder="https://example.com/logo.png">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Logo Height (px)</label>
                <input type="number" name="logo_height" class="form-control" value="${escapeHtml(getSetting('logo_height', '70'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Logo Position</label>
                <select name="logo_position" class="form-select">
                  <option value="left" ${getSetting('logo_position', 'left') === 'left' ? 'selected' : ''}>Left</option>
                  <option value="center" ${getSetting('logo_position', 'left') === 'center' ? 'selected' : ''}>Center</option>
                  <option value="right" ${getSetting('logo_position', 'left') === 'right' ? 'selected' : ''}>Right</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Editor Name</label>
              <input type="text" name="editor_name" class="form-control" value="${escapeHtml(getSetting('editor_name', 'M. A. Rahman'))}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Publisher Name</label>
              <input type="text" name="publisher_name" class="form-control" value="${escapeHtml(getSetting('publisher_name', 'হোস্টারকিউব লিমিটেড (HosterCube Ltd)'))}">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-bold">Footer Logo Image URL</label>
              <input type="text" name="footer_logo_url" class="form-control" value="${escapeHtml(getSetting('footer_logo_url', ''))}" placeholder="https://example.com/footer-logo.png">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Footer Logo Height (px)</label>
                <input type="number" name="footer_logo_height" class="form-control" value="${escapeHtml(getSetting('footer_logo_height', '60'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Footer Alignment</label>
                <select name="footer_logo_position" class="form-select">
                  <option value="left" ${getSetting('footer_logo_position', 'left') === 'left' ? 'selected' : ''}>Left</option>
                  <option value="center" ${getSetting('footer_logo_position', 'left') === 'center' ? 'selected' : ''}>Center</option>
                  <option value="right" ${getSetting('footer_logo_position', 'left') === 'right' ? 'selected' : ''}>Right</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Phone Number</label>
              <input type="text" name="phone" class="form-control" value="${escapeHtml(getSetting('phone', '+৮৮০ ২ ৯৮৭৬৫৪৩২'))}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Office Email</label>
              <input type="email" name="email" class="form-control" value="${escapeHtml(getSetting('email', 'contact@hostercube.com'))}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Office Address</label>
              <input type="text" name="address" class="form-control" value="${escapeHtml(getSetting('address', 'লেভেল ৮, হোস্টারকিউব টাওয়ার, ৪২ মতিঝিল বা/এ, ঢাকা-১০০০, বাংলাদেশ'))}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Header Ad Max Height (px)</label>
              <input type="number" name="header_ad_height" class="form-control" value="${escapeHtml(getSetting('header_ad_height', '120'))}">
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-danger fw-bold px-4 py-2 mt-2"><i class="bi bi-save me-1"></i> Save All Settings</button>
      </form>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminUsersView(data: any): string {
  const users = data.users || [];
  let rowsHtml = '';

  users.forEach((u: any) => {
    rowsHtml += `<tr>
      <td>${u.id}</td>
      <td class="fw-bold">${escapeHtml(u.full_name)}<br><small class="text-muted">@${escapeHtml(u.username)}</small></td>
      <td>${escapeHtml(u.email)}</td>
      <td><span class="badge bg-primary">${escapeHtml(u.role)}</span></td>
      <td>${u.username !== 'admin' ? `<a href="/admin/users.php?action=delete&id=${u.id}" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?');"><i class="bi bi-trash"></i></a>` : '<span class="text-muted small">Protected</span>'}</td>
    </tr>`;
  });

  return `${renderAdminHeader('System Users', 'users', data.adminName)}
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border p-3 bg-white shadow-sm">
          <h5 class="fw-bold mb-3">Add Reporter / Admin</h5>
          <form action="/admin/users.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold small">Full Name</label>
              <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">Role</label>
              <select name="role" class="form-select">
                <option value="reporter">Reporter</option>
                <option value="editor">Editor</option>
                <option value="admin">Administrator</option>
              </select>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-person-plus me-1"></i> Add User</button>
          </form>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>User Details</th>
                <th>Email</th>
                <th>Role</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    ${renderAdminFooter()}`;
}

export function renderAdminLoginView(error: string | null): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CMS Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 420px; border-radius: 12px; background: #ffffff; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="bi bi-newspaper text-danger" style="font-size: 3rem;"></i>
            <h4 class="fw-bold mt-2 text-dark">HosterCube News CMS</h4>
            <p class="text-muted small">Sign in to control panel</p>
        </div>
        ${error ? `<div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${escapeHtml(error)}</div>` : ''}
        <form action="/admin/login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold text-dark small">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" value="admin" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold text-dark small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control" value="admin123" required>
                </div>
            </div>
            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold"><i class="bi bi-box-arrow-in-right me-1"></i> Log In</button>
        </form>
        <div class="text-center mt-4">
            <a href="/" class="text-muted small text-decoration-none">&larr; Back to Public News Portal</a>
        </div>
    </div>
</body>
</html>`;
}

export function renderAdminColorsView(data: any): string {
  const dtm = getSetting('default_theme_mode', 'light');
  return `${renderAdminHeader('Color & Theme Manager', 'colors', data.adminName)}
    <div class="card border p-4 bg-white shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="fw-bold mb-1"><i class="bi bi-palette text-danger me-2"></i> Theme & Color Manager</h5>
          <p class="text-muted small mb-0">Customize website theme, dark/light default mode, headers, menus, button colors, and custom CSS.</p>
        </div>
        <form action="/admin/colors.php" method="POST" class="d-inline">
          <button type="submit" name="reset_defaults" value="1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reset all theme colors to system defaults?');">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default Colors
          </button>
        </form>
      </div>

      <form action="/admin/colors.php" method="POST">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-bold">Default Display Mode</label>
              <select name="default_theme_mode" class="form-select">
                <option value="light" ${dtm === 'light' ? 'selected' : ''}>Light Mode (Default)</option>
                <option value="dark" ${dtm === 'dark' ? 'selected' : ''}>Dark Mode (Default)</option>
              </select>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Primary Accent Color</label>
                <input type="color" name="primary_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('primary_color', '#e61e25'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Hover Accent Color</label>
                <input type="color" name="primary_hover_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('primary_hover_color', '#b91c1c'))}">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Top Bar BG</label>
                <input type="color" name="topbar_bg_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('topbar_bg_color', '#0f172a'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Top Bar Text</label>
                <input type="color" name="topbar_text_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('topbar_text_color', '#f8fafc'))}">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Header BG</label>
                <input type="color" name="header_bg_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('header_bg_color', '#ffffff'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Title Color</label>
                <input type="color" name="title_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('title_color', '#111111'))}">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Ticker BG Color</label>
                <input type="color" name="ticker_bg_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('ticker_bg_color', '#dc2626'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Ticker Text Color</label>
                <input type="color" name="ticker_text_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('ticker_text_color', '#ffffff'))}">
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Nav Menu BG</label>
                <input type="color" name="menu_bg_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('menu_bg_color', '#991b1b'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Nav Menu Text</label>
                <input type="color" name="menu_text_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('menu_text_color', '#ffffff'))}">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Nav Hover BG</label>
                <input type="color" name="menu_hover_bg_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('menu_hover_bg_color', '#7f1d1d'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Link Hover Color</label>
                <input type="color" name="link_hover_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('link_hover_color', '#e61e25'))}">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Footer BG</label>
                <input type="color" name="footer_bg_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('footer_bg_color', '#0f172a'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Footer Text</label>
                <input type="color" name="footer_text_color" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('footer_text_color', '#94a3b8'))}">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-bold small">Widget Accent</label>
                <input type="color" name="widget_header_bg" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('widget_header_bg', '#991b1b'))}">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold small">Mobile Nav BG</label>
                <input type="color" name="mobile_nav_bg" class="form-control form-control-color w-100" value="${escapeHtml(getSetting('mobile_nav_bg', '#0f172a'))}">
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label fw-bold">Custom CSS Code</label>
            <textarea name="custom_css" class="form-control font-monospace" rows="4" placeholder="/* Custom CSS */">${escapeHtml(getSetting('custom_css', ''))}</textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-danger fw-bold mt-4 px-4 py-2"><i class="bi bi-save me-1"></i> Save Color & Theme Settings</button>
      </form>
    </div>
    ${renderAdminFooter()}`;
}


