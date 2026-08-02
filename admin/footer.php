            <!-- Admin Footer -->
            <footer class="mt-5 py-3 border-top text-center text-muted small bg-white rounded-3 shadow-sm">
                <div>Newspaper Portal CMS &bull; Powered & Maintained by <strong class="text-dark"><i class="bi bi-hdd-network text-danger me-1"></i>HosterCube Ltd</strong></div>
            </footer>
        </main>
    </div>
</div>

<!-- Global Media Picker Modal -->
<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-labelledby="mediaPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="mediaPickerModalLabel"><i class="bi bi-images me-2"></i> Media Library & File Upload</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <ul class="nav nav-tabs mb-3" id="mediaModalTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" id="tab-library-tab" data-bs-toggle="tab" data-bs-target="#tab-library" type="button"><i class="bi bi-grid-3x3-gap me-1"></i> Media Library</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="tab-upload-tab" data-bs-toggle="tab" data-bs-target="#tab-upload" type="button"><i class="bi bi-cloud-arrow-up me-1"></i> Upload File</button>
                    </li>
                </ul>

                <div class="tab-content" id="mediaModalTabContent">
                    <!-- Tab 1: Library -->
                    <div class="tab-pane fade show active" id="tab-library" role="tabpanel">
                        <div class="d-flex mb-3 gap-2">
                            <input type="text" id="mediaModalSearch" class="form-control" placeholder="Search uploaded media...">
                            <button type="button" class="btn btn-danger" onclick="loadModalMedia(1)"><i class="bi bi-search"></i></button>
                        </div>
                        <div id="mediaModalGrid" class="row g-3" style="min-height: 250px;">
                            <div class="col-12 text-center text-muted py-5"><div class="spinner-border text-danger" role="status"></div> Loading media...</div>
                        </div>
                        <div id="mediaModalPagination" class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top"></div>
                    </div>

                    <!-- Tab 2: Upload -->
                    <div class="tab-pane fade p-4 bg-white rounded border text-center" id="tab-upload" role="tabpanel">
                        <div class="py-4">
                            <i class="bi bi-cloud-upload display-3 text-danger mb-3 d-block"></i>
                            <h5 class="fw-bold">Select or Drag Image / Video / Media File</h5>
                            <p class="text-muted small">Supports JPG, PNG, WEBP, GIF, SVG, MP4, WEBM, MOV, PDF, ZIP</p>
                            <input type="file" id="mediaModalFileInput" class="form-control form-control-lg w-75 mx-auto mb-3" accept="image/*,video/*,.pdf,.zip">
                            <button type="button" class="btn btn-danger btn-lg px-5 fw-bold" id="mediaModalUploadBtn"><i class="bi bi-upload me-1"></i> Upload & Use File</button>
                            <div id="mediaModalUploadStatus" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
