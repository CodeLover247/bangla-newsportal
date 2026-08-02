/**
 * Frontend JavaScript - Daily Horizon News Portal
 */

// Theme Toggle Function
function toggleTheme() {
  const html = document.documentElement;
  const currentTheme = html.getAttribute('data-bs-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

  html.setAttribute('data-bs-theme', newTheme);
  localStorage.setItem('theme', newTheme);

  const themeIcon = document.getElementById('themeIcon');
  const themeLabel = document.getElementById('themeLabel');

  if (themeIcon) {
    themeIcon.className = newTheme === 'dark' ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars-fill text-warning';
  }
  if (themeLabel) {
    themeLabel.textContent = newTheme === 'dark' ? 'Light' : 'Dark';
  }
}

// Switch video theater main player
function switchTheaterVideo(sectionId, embedUrl, title, el) {
  const section = document.getElementById(sectionId);
  if (!section) return;
  const iframe = section.querySelector('iframe');
  const titleEl = section.querySelector('h5[id^="mainTheaterTitle_"]');
  if (iframe) iframe.src = embedUrl;
  if (titleEl) titleEl.textContent = title;

  section.querySelectorAll('.playlist-item').forEach((item) => {
    item.classList.remove('border-danger');
  });
  if (el) el.classList.add('border-danger');
}

document.addEventListener('DOMContentLoaded', function () {

  // Live AJAX Search
  const searchInput = document.getElementById('ajax-search-input');
  const searchResultsContainer = document.getElementById('ajax-search-results');

  if (searchInput && searchResultsContainer) {
    let debounceTimer;
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      const query = searchInput.value.trim();

      if (query.length < 2) {
        searchResultsContainer.style.display = 'none';
        searchResultsContainer.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(() => {
        fetch(`api.php?action=search&q=${encodeURIComponent(query)}`)
          .then((res) => res.json())
          .then((data) => {
            if (data.status === 'success' && data.results.length > 0) {
              let html = '<div class="list-group shadow-lg border-0 rounded-0">';
              data.results.forEach((item) => {
                html += `
                  <a href="article.php?slug=${item.slug}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2">
                    <img src="${item.featured_image || 'assets/images/placeholder.jpg'}" class="rounded" style="width: 50px; height: 40px; object-fit: cover;" alt="" />
                    <div>
                      <h6 class="mb-0 text-truncate" style="max-width: 320px; font-size: 0.9rem;">${item.title}</h6>
                      <small class="text-muted">${item.category_name} &bull; ${item.date}</small>
                    </div>
                  </a>
                `;
              });
              html += '</div>';
              searchResultsContainer.innerHTML = html;
              searchResultsContainer.style.display = 'block';
            } else {
              searchResultsContainer.innerHTML =
                '<div class="p-3 text-muted text-center small bg-white border">No news found matching your query.</div>';
              searchResultsContainer.style.display = 'block';
            }
          })
          .catch((err) => console.error('Search error:', err));
      }, 300);
    });

    // Close search dropdown on click outside
    document.addEventListener('click', function (e) {
      if (!searchInput.contains(e.target) && !searchResultsContainer.contains(e.target)) {
        searchResultsContainer.style.display = 'none';
      }
    });
  }

  // Copy Link to Clipboard
  const copyBtn = document.getElementById('btn-copy-link');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      const url = this.getAttribute('data-url') || window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        copyBtn.classList.remove('btn-outline-secondary');
        copyBtn.classList.add('btn-success');
        setTimeout(() => {
          copyBtn.innerHTML = originalText;
          copyBtn.classList.remove('btn-success');
          copyBtn.classList.add('btn-outline-secondary');
        }, 2000);
      });
    });
  }

  // Desktop Header Navbar Dropdown Link Handling
  if (window.innerWidth >= 992) {
    document.querySelectorAll('.navbar-nav .dropdown > a.dropdown-toggle').forEach((dropdownToggle) => {
      dropdownToggle.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href && href !== '#' && !href.startsWith('javascript:')) {
          window.location.href = href;
        }
      });
    });
  }

  // Print Article Button
  const printBtn = document.getElementById('btn-print-article');
  if (printBtn) {
    printBtn.addEventListener('click', function () {
      window.print();
    });
  }

  // AJAX Comment Form Submission
  const commentForm = document.getElementById('comment-form');
  if (commentForm) {
    commentForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(commentForm);
      const submitBtn = commentForm.querySelector('button[type="submit"]');
      const alertBox = document.getElementById('comment-alert');

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

      fetch('api.php?action=add_comment', {
        method: 'POST',
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Post Comment';
          if (data.status === 'success') {
            alertBox.className = 'alert alert-success mt-3';
            alertBox.textContent = data.message;
            alertBox.classList.remove('d-none');
            commentForm.reset();
          } else {
            alertBox.className = 'alert alert-danger mt-3';
            alertBox.textContent = data.message || 'Error submitting comment.';
            alertBox.classList.remove('d-none');
          }
        })
        .catch((err) => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Post Comment';
          alertBox.className = 'alert alert-danger mt-3';
          alertBox.textContent = 'Server error occurred.';
          alertBox.classList.remove('d-none');
        });
    });
  }
});
