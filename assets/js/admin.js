/**
 * Admin Panel JavaScript
 */

let activeMediaTarget = null;
let activePreviewTarget = null;

document.addEventListener('DOMContentLoaded', function () {
  // Mobile Sidebar Toggle
  const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
  const sidebar = document.getElementById('admin-sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (sidebarToggleBtn && sidebar) {
    sidebarToggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('active');
      if (backdrop) backdrop.classList.toggle('active');
    });

    if (backdrop) {
      backdrop.addEventListener('click', function () {
        sidebar.classList.remove('active');
        backdrop.classList.remove('active');
      });
    }
  }

  // Auto Generate Slug from Title
  const titleInput = document.getElementById('post-title');
  const slugInput = document.getElementById('post-slug');

  if (titleInput && slugInput) {
    titleInput.addEventListener('input', function () {
      if (!slugInput.dataset.manual) {
        const title = titleInput.value;
        const slug = title
          .toLowerCase()
          .replace(/[^\w\s-]/g, '')
          .replace(/[\s_-]+/g, '-')
          .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
      }
    });

    slugInput.addEventListener('input', function () {
      slugInput.dataset.manual = 'true';
    });
  }

  // Image Preview on File Input Change
  const imgInputs = document.querySelectorAll('.custom-img-input');
  imgInputs.forEach((input) => {
    input.addEventListener('change', function () {
      const targetPreviewId = input.dataset.preview;
      const previewImg = document.getElementById(targetPreviewId);

      if (previewImg && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      }
    });
  });

  // Confirm Delete
  const deleteBtns = document.querySelectorAll('.btn-confirm-delete');
  deleteBtns.forEach((btn) => {
    btn.addEventListener('click', function (e) {
      if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  // Media Picker Trigger
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-media-picker');
    if (btn) {
      activeMediaTarget = btn.getAttribute('data-target') || btn.dataset.target;
      activePreviewTarget = btn.getAttribute('data-preview') || btn.dataset.preview;
      
      const modalEl = document.getElementById('mediaPickerModal');
      if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        loadModalMedia(1);
      }
    }
  });

  // Upload button inside modal
  const modalUploadBtn = document.getElementById('mediaModalUploadBtn');
  if (modalUploadBtn) {
    modalUploadBtn.addEventListener('click', function () {
      const fileInput = document.getElementById('mediaModalFileInput');
      const statusDiv = document.getElementById('mediaModalUploadStatus');

      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        statusDiv.innerHTML = '<div class="alert alert-warning py-2 small">Please select a file to upload.</div>';
        return;
      }

      const formData = new FormData();
      formData.append('file', fileInput.files[0]);

      statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-danger me-2"></div> Uploading file...';

      fetch('api_upload.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          statusDiv.innerHTML = '<div class="alert alert-success py-2 small">Upload successful! Applied to field.</div>';
          selectMediaUrl(data.url);
          setTimeout(() => {
            const modalEl = document.getElementById('mediaPickerModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
          }, 600);
        } else {
          statusDiv.innerHTML = '<div class="alert alert-danger py-2 small">Upload failed: ' + (data.error || 'Unknown error') + '</div>';
        }
      })
      .catch(err => {
        statusDiv.innerHTML = '<div class="alert alert-danger py-2 small">Error uploading file. Check server permissions.</div>';
      });
    });
  }
});

// Load Media Items into Modal
function loadModalMedia(page = 1) {
  const grid = document.getElementById('mediaModalGrid');
  const searchInput = document.getElementById('mediaModalSearch');
  const pagination = document.getElementById('mediaModalPagination');

  if (!grid) return;

  const search = searchInput ? searchInput.value : '';
  grid.innerHTML = '<div class="col-12 text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div> Loading media items...</div>';

  fetch(`api_upload.php?page=${page}&search=${encodeURIComponent(search)}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success || !data.media || data.media.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center text-muted py-5">No media files found. Upload a new file above!</div>';
        if (pagination) pagination.innerHTML = '';
        return;
      }

      let html = '';
      data.media.forEach(item => {
        const path = item.filepath.startsWith('http') ? item.filepath : '../' + item.filepath;
        const isVideo = item.filetype.includes('video') || item.filename.endsWith('.mp4') || item.filename.endsWith('.webm');
        
        html += `
          <div class="col-6 col-md-3 col-lg-2">
            <div class="card h-100 border text-center p-2 bg-white shadow-sm media-item-card">
              ${isVideo ? 
                `<div class="bg-dark text-white rounded mb-2 d-flex align-items-center justify-content-center" style="height:100px;"><i class="bi bi-file-play display-6"></i></div>` :
                `<img src="${path}" class="rounded mb-2 img-fluid" style="height:100px; object-fit:cover;" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=150&auto=format&fit=crop&q=80'">`
              }
              <small class="text-truncate d-block fw-semibold mb-2" title="${item.filename}">${item.filename}</small>
              <button type="button" class="btn btn-sm btn-danger w-100 fw-bold" onclick="selectMediaUrl('${item.filepath}')">Select</button>
            </div>
          </div>
        `;
      });
      grid.innerHTML = html;

      // Pagination
      if (pagination) {
        if (data.total_pages > 1) {
          let pno = `<span>Page ${data.page} of ${data.total_pages}</span><ul class="pagination pagination-sm mb-0">`;
          pno += `<li class="page-item ${data.page <= 1 ? 'disabled' : ''}"><button class="page-link" onclick="loadModalMedia(${data.page - 1})">Prev</button></li>`;
          for (let i = 1; i <= data.total_pages; i++) {
            pno += `<li class="page-item ${data.page == i ? 'active bg-danger' : ''}"><button class="page-link" onclick="loadModalMedia(${i})">${i}</button></li>`;
          }
          pno += `<li class="page-item ${data.page >= data.total_pages ? 'disabled' : ''}"><button class="page-link" onclick="loadModalMedia(${data.page + 1})">Next</button></li>`;
          pno += `</ul>`;
          pagination.innerHTML = pno;
        } else {
          pagination.innerHTML = `<span class="small text-muted">Total ${data.total} items</span>`;
        }
      }
    })
    .catch(err => {
      grid.innerHTML = '<div class="col-12 text-center text-danger py-5">Failed to load media items.</div>';
    });
}

// Assign selected media URL to active target
function selectMediaUrl(url) {
  if (activeMediaTarget) {
    const input = document.querySelector(activeMediaTarget) || document.getElementById(activeMediaTarget.replace('#',''));
    if (input) {
      input.value = url;
    }
  }

  if (activePreviewTarget) {
    const preview = document.querySelector(activePreviewTarget) || document.getElementById(activePreviewTarget.replace('#',''));
    if (preview) {
      const fullUrl = url.startsWith('http') ? url : (url.startsWith('../') ? url : '../' + url);
      preview.src = fullUrl;
      preview.style.display = 'block';
    }
  }

  const modalEl = document.getElementById('mediaPickerModal');
  if (modalEl) {
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
  }
}
