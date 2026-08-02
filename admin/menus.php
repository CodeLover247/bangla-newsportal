<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';
$edit_menu = null;

// Fetch categories and custom pages for quick selection dropdowns
$categories = get_categories(0, false);
$pages = $db->query("SELECT * FROM pages WHERE status = 1 ORDER BY title ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax_reorder'])) {
        header('Content-Type: application/json');
        $orderData = json_decode($_POST['orders_json'] ?? '[]', true);
        if (is_array($orderData)) {
            $stmtUpd = $db->prepare("UPDATE menus SET item_order = ? WHERE id = ?");
            foreach ($orderData as $index => $m_id) {
                $stmtUpd->execute([$index + 1, (int)$m_id]);
            }
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    if (isset($_POST['update_order'])) {
        // Bulk update orders
        if (isset($_POST['orders']) && is_array($_POST['orders'])) {
            $stmtUpd = $db->prepare("UPDATE menus SET item_order = ? WHERE id = ?");
            foreach ($_POST['orders'] as $m_id => $ord) {
                $stmtUpd->execute([(int)$ord, (int)$m_id]);
            }
            $msg = "Menu display sequence updated successfully!";
        }
    } else {
        $location = $_POST['location'] ?? 'header';
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        $link_type = $_POST['link_type'] ?? 'custom';
        $title = trim($_POST['title'] ?? '');
        $order = (int)($_POST['item_order'] ?? 0);
        $target = $_POST['target'] ?? '_self';
        $url = '';

        if ($link_type === 'category' && !empty($_POST['cat_slug'])) {
            $url = 'category.php?slug=' . trim($_POST['cat_slug']);
            if (empty($title)) {
                $cat = get_category(trim($_POST['cat_slug']));
                $title = $cat ? $cat['name'] : 'Category';
            }
        } elseif ($link_type === 'page' && !empty($_POST['page_slug'])) {
            $url = 'page.php?slug=' . trim($_POST['page_slug']);
            if (empty($title)) {
                $stmtP = $db->prepare("SELECT title FROM pages WHERE slug = ?");
                $stmtP->execute([trim($_POST['page_slug'])]);
                $pRow = $stmtP->fetch();
                $title = $pRow ? $pRow['title'] : 'Page';
            }
        } else {
            $url = trim($_POST['url'] ?? '#');
        }

        if (!empty($title) && !empty($url)) {
            if (isset($_POST['edit_id']) && $_POST['edit_id'] > 0) {
                $stmt = $db->prepare("UPDATE menus SET location=?, parent_id=?, title=?, url=?, item_order=?, target=? WHERE id=?");
                $stmt->execute([$location, $parent_id, $title, $url, $order, $target, (int)$_POST['edit_id']]);
                $msg = "Menu item updated!";
            } else {
                $stmt = $db->prepare("INSERT INTO menus (location, parent_id, title, url, item_order, target, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$location, $parent_id, $title, $url, $order, $target]);
                $msg = "Menu item added successfully!";
            }
        } else {
            $msg = "Please provide both Menu Title and Target URL.";
        }
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $del_id = (int)$_GET['id'];
        $db->prepare("DELETE FROM menus WHERE id = ? OR parent_id = ?")->execute([$del_id, $del_id]);
        header('Location: menus.php?msg=deleted');
        exit;
    } elseif ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $stmtEd = $db->prepare("SELECT * FROM menus WHERE id = ?");
        $stmtEd->execute([(int)$_GET['id']]);
        $edit_menu = $stmtEd->fetch();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Menu item deleted!";
}

// Fetch header top parents and children
$header_parents = $db->query("SELECT * FROM menus WHERE location = 'header' AND parent_id = 0 ORDER BY item_order ASC, id ASC")->fetchAll();
$header_menus = [];
foreach ($header_parents as $hp) {
    $header_menus[] = $hp;
    $children = $db->query("SELECT * FROM menus WHERE location = 'header' AND parent_id = {$hp['id']} ORDER BY item_order ASC, id ASC")->fetchAll();
    foreach ($children as $ch) {
        $ch['is_child'] = true;
        $ch['parent_title'] = $hp['title'];
        $header_menus[] = $ch;
    }
}

// Fetch footer menus sorted by order
$footer_menus = $db->query("SELECT * FROM menus WHERE location = 'footer' ORDER BY item_order ASC, id ASC")->fetchAll();
?>

<div class="row g-4">
    <!-- Add / Edit Menu Form -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border">
            <h5 class="fw-bold mb-3"><?= $edit_menu ? '<i class="bi bi-pencil me-1"></i> Edit Menu Link' : '<i class="bi bi-plus-circle me-1"></i> Add Menu Link' ?></h5>
            <?php if ($msg): ?>
                <div class="alert alert-info py-2 small alert-dismissible fade show">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="menus.php" method="POST">
                <?php if ($edit_menu): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_menu['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Menu Placement *</label>
                    <select name="location" id="location_select" class="form-select" onchange="toggleParentBox()">
                        <option value="header" <?= ($edit_menu && $edit_menu['location'] === 'header') ? 'selected' : '' ?>>Header Navigation Menu</option>
                        <option value="footer" <?= ($edit_menu && $edit_menu['location'] === 'footer') ? 'selected' : '' ?>>Footer Menu</option>
                    </select>
                </div>

                <!-- Parent Dropdown for Submenus -->
                <div class="mb-3" id="parent_box">
                    <label class="form-label fw-semibold">Parent Menu (Optional for Dropdowns)</label>
                    <select name="parent_id" class="form-select">
                        <option value="0">None (Top Level Item)</option>
                        <?php foreach ($header_parents as $hp): ?>
                            <?php if (!$edit_menu || $edit_menu['id'] != $hp['id']): ?>
                                <option value="<?= $hp['id'] ?>" <?= ($edit_menu && $edit_menu['parent_id'] == $hp['id']) ? 'selected' : '' ?>>
                                    Submenu under: <?= htmlspecialchars($hp['title']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Select a top-level menu item to create a dropdown child link.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Type *</label>
                    <select id="link_type" name="link_type" class="form-select" onchange="toggleLinkInputs()">
                        <option value="category">Category Link</option>
                        <option value="page">Custom Page Link</option>
                        <option value="custom" <?= ($edit_menu && strpos($edit_menu['url'], 'http') === 0) ? 'selected' : '' ?>>Custom URL / External Link</option>
                    </select>
                </div>

                <!-- Select Category -->
                <div class="mb-3" id="box_category">
                    <label class="form-label fw-semibold">Select Category</label>
                    <select name="cat_slug" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Select Custom Page -->
                <div class="mb-3" id="box_page" style="display: none;">
                    <label class="form-label fw-semibold">Select Custom Page</label>
                    <select name="page_slug" class="form-select">
                        <?php foreach ($pages as $pg): ?>
                            <option value="<?= htmlspecialchars($pg['slug']) ?>"><?= htmlspecialchars($pg['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Custom URL input -->
                <div class="mb-3" id="box_custom" style="display: none;">
                    <label class="form-label fw-semibold">Custom Target URL</label>
                    <input type="text" name="url" class="form-control" placeholder="https://example.com or page.php" value="<?= htmlspecialchars($edit_menu['url'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Menu Label / Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. National, Politics, Contact" value="<?= htmlspecialchars($edit_menu['title'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Open In</label>
                    <select name="target" class="form-select">
                        <option value="_self" <?= ($edit_menu && $edit_menu['target'] === '_self') ? 'selected' : '' ?>>Same Window (_self)</option>
                        <option value="_blank" <?= ($edit_menu && $edit_menu['target'] === '_blank') ? 'selected' : '' ?>>New Tab (_blank)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Display Sequence Order</label>
                    <input type="number" name="item_order" class="form-control" value="<?= htmlspecialchars($edit_menu['item_order'] ?? '0') ?>">
                    <small class="text-muted">Smaller numbers display first (0, 1, 2...)</small>
                </div>

                <button type="submit" class="btn btn-danger w-100 fw-bold py-2">
                    <?= $edit_menu ? 'Update Menu Item' : 'Add to Navigation' ?>
                </button>

                <?php if ($edit_menu): ?>
                    <a href="menus.php" class="btn btn-outline-secondary w-100 mt-2">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Menus List & Order Manager -->
    <div class="col-lg-8">
        <form action="menus.php" method="POST">
            <input type="hidden" name="update_order" value="1">
            
            <div class="card shadow-sm border mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-menu-button-wide me-2"></i> Header Navigation Menu Sequence</h5>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="bi bi-arrow-down-up me-1"></i> Save Display Order</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="headerMenuTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th style="width: 90px;">Order</th>
                                <th>Menu Title</th>
                                <th>Target URL</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="headerMenuSortable">
                            <?php if (!empty($header_menus)): foreach ($header_menus as $m): ?>
                                <tr class="<?= !empty($m['is_child']) ? 'table-light' : '' ?> sortable-row" draggable="true" data-id="<?= $m['id'] ?>">
                                    <td class="text-center"><i class="bi bi-grip-vertical text-muted fs-5 cursor-grab me-1"></i></td>
                                    <td>
                                        <input type="number" name="orders[<?= $m['id'] ?>]" class="form-control form-control-sm text-center fw-bold order-input" value="<?= $m['item_order'] ?>">
                                    </td>
                                    <td>
                                        <?php if (!empty($m['is_child'])): ?>
                                            <span class="ms-3 text-muted me-1">&bull; &mdash;</span>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($m['title']) ?></span>
                                            <small class="badge bg-secondary ms-1">Child</small>
                                        <?php else: ?>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['title']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($m['url']) ?></code></td>
                                    <td class="text-end">
                                        <a href="menus.php?action=edit&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="menus.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete this menu link and its submenus?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No custom header navigation items added yet. Active categories show automatically by default.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Menu List -->
            <div class="card shadow-sm border">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-layout-footer me-2"></i> Footer Menu Links</h5>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="bi bi-arrow-down-up me-1"></i> Save Display Order</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="footerMenuTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th style="width: 90px;">Order</th>
                                <th>Menu Title</th>
                                <th>Target URL</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="footerMenuSortable">
                            <?php if (!empty($footer_menus)): foreach ($footer_menus as $fm): ?>
                                <tr class="sortable-row" draggable="true" data-id="<?= $fm['id'] ?>">
                                    <td class="text-center"><i class="bi bi-grip-vertical text-muted fs-5 cursor-grab me-1"></i></td>
                                    <td>
                                        <input type="number" name="orders[<?= $fm['id'] ?>]" class="form-control form-control-sm text-center fw-bold order-input" value="<?= $fm['item_order'] ?>">
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($fm['title']) ?></td>
                                    <td><code><?= htmlspecialchars($fm['url']) ?></code></td>
                                    <td class="text-end">
                                        <a href="menus.php?action=edit&id=<?= $fm['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="menus.php?action=delete&id=<?= $fm['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete this menu link?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No footer menu links added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleLinkInputs() {
    var type = document.getElementById('link_type').value;
    document.getElementById('box_category').style.display = (type === 'category') ? 'block' : 'none';
    document.getElementById('box_page').style.display = (type === 'page') ? 'block' : 'none';
    document.getElementById('box_custom').style.display = (type === 'custom') ? 'block' : 'none';
}

function toggleParentBox() {
    var loc = document.getElementById('location_select').value;
    document.getElementById('parent_box').style.display = (loc === 'header') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    toggleLinkInputs();
    toggleParentBox();

    // Drag and Drop Reordering Implementation
    let dragSrcEl = null;

    function handleDragStart(e) {
        dragSrcEl = this;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
        this.classList.add('bg-warning-subtle');
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDragEnter() {
        this.classList.add('table-info');
    }

    function handleDragLeave() {
        this.classList.remove('table-info');
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        if (dragSrcEl !== this && dragSrcEl.parentNode === this.parentNode) {
            let tbody = this.parentNode;
            let rows = Array.from(tbody.querySelectorAll('.sortable-row'));
            let srcIndex = rows.indexOf(dragSrcEl);
            let targetIndex = rows.indexOf(this);

            if (srcIndex < targetIndex) {
                tbody.insertBefore(dragSrcEl, this.nextSibling);
            } else {
                tbody.insertBefore(dragSrcEl, this);
            }

            // Auto update order inputs & AJAX save
            let newRows = tbody.querySelectorAll('.sortable-row');
            let idsInOrder = [];
            newRows.forEach((row, index) => {
                let orderInput = row.querySelector('.order-input');
                if (orderInput) {
                    orderInput.value = index + 1;
                }
                if (row.dataset.id) {
                    idsInOrder.push(row.dataset.id);
                }
            });

            // Save via AJAX
            fetch('menus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_reorder=1&orders_json=' + encodeURIComponent(JSON.stringify(idsInOrder))
            });
        }
        return false;
    }

    function handleDragEnd() {
        let rows = document.querySelectorAll('.sortable-row');
        rows.forEach(row => {
            row.classList.remove('bg-warning-subtle', 'table-info');
        });
    }

    let rows = document.querySelectorAll('.sortable-row');
    rows.forEach(row => {
        row.addEventListener('dragstart', handleDragStart, false);
        row.addEventListener('dragover', handleDragOver, false);
        row.addEventListener('dragenter', handleDragEnter, false);
        row.addEventListener('dragleave', handleDragLeave, false);
        row.addEventListener('drop', handleDrop, false);
        row.addEventListener('dragend', handleDragEnd, false);
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
