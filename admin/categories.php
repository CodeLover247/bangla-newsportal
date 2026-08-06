<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$msg = '';

// Add / Edit Category Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $raw_slug = trim($_POST['slug'] ?? '');
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    $slug = get_unique_slug('categories', !empty($raw_slug) ? $raw_slug : $name, $edit_id);
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $order = (int)($_POST['cat_order'] ?? 0);

    if (!empty($name)) {
        if ($edit_id > 0) {
            $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, parent_id=?, description=?, cat_order=? WHERE id=?");
            $stmt->execute([$name, $slug, $parent_id, $desc, $order, $edit_id]);
            $msg = "Category updated successfully!";
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, parent_id, description, cat_order, status) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $slug, $parent_id, $desc, $order]);
            $msg = "Category created successfully!";
        }
    }
}

// Delete Handler
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    if ($del_id > 0) {
        try {
            // Reassign child categories & posts so no orphan records crash the site
            $db->prepare("UPDATE categories SET parent_id = 0 WHERE parent_id = ?")->execute([$del_id]);
            $db->prepare("UPDATE posts SET category_id = 0 WHERE category_id = ?")->execute([$del_id]);
            $db->prepare("UPDATE posts SET subcategory_id = 0 WHERE subcategory_id = ?")->execute([$del_id]);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$del_id]);
            header('Location: categories.php?msg=deleted');
            exit;
        } catch (Throwable $e) {
            $msg = "Error deleting category: " . $e->getMessage();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$all_categories = get_categories(0, false);

if (!empty($search)) {
    $filtered_categories = [];
    foreach ($all_categories as $c) {
        if (stripos($c['name'], $search) !== false || stripos($c['slug'], $search) !== false) {
            $filtered_categories[] = $c;
        }
    }
    $categories = $filtered_categories;
} else {
    $categories = $all_categories;
}

$edit_cat = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_cat = get_category((int)$_GET['id']);
}
?>

<div class="row g-4">
    <!-- Category Form -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border border-0">
            <h5 class="fw-bold mb-3"><?= $edit_cat ? 'Edit Category' : 'Add New Category' ?></h5>
            <?php if ($msg): ?><div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

            <form action="categories.php" method="POST">
                <?php if ($edit_cat): ?><input type="hidden" name="edit_id" value="<?= $edit_cat['id'] ?>"><?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_cat['name'] ?? '') ?>" placeholder="e.g. জাতীয়, খেলাধুলা">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Slug (URL)</label>
                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($edit_cat['slug'] ?? '') ?>" placeholder="e.g. national, sports (leave blank for auto)">
                    <div class="form-text small">Unique URL slug for this category.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Parent Category</label>
                    <select name="parent_id" class="form-select">
                        <option value="0">None (Main Category)</option>
                        <?php foreach ($all_categories as $c): if ($edit_cat && $edit_cat['id'] == $c['id']) continue; ?>
                            <option value="<?= $c['id'] ?>" <?= ($edit_cat && $edit_cat['parent_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_cat['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Display Order</label>
                    <input type="number" name="cat_order" class="form-control" value="<?= htmlspecialchars($edit_cat['cat_order'] ?? '0') ?>">
                </div>

                <button type="submit" class="btn btn-danger w-100 fw-bold"><?= $edit_cat ? 'Update Category' : 'Save Category' ?></button>
                <?php if ($edit_cat): ?>
                    <a href="categories.php" class="btn btn-link text-secondary w-100 mt-2">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Category List Table -->
    <div class="col-lg-8">
        <!-- Search Filter -->
        <div class="card border-0 shadow-sm p-3 mb-3">
            <form method="GET" action="categories.php">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search category by name or slug..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="categories.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Category Name</th>
                            <th>Slug</th>
                            <th>Parent</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                            <tr class="fw-bold">
                                <td><?= $cat['cat_order'] ?></td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                <td><span class="badge bg-primary">Main Category</span></td>
                                <td class="text-end">
                                    <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete this category?');" title="Delete"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php $subs = get_categories($cat['id'], false); foreach ($subs as $sub): ?>
                                <tr>
                                    <td>&mdash; <?= $sub['cat_order'] ?></td>
                                    <td class="ps-4">&rdsh; <?= htmlspecialchars($sub['name']) ?></td>
                                    <td><code><?= htmlspecialchars($sub['slug']) ?></code></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($cat['name']) ?></span></td>
                                    <td class="text-end">
                                        <a href="categories.php?action=edit&id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="categories.php?action=delete&id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" onclick="return confirm('Delete subcategory?');" title="Delete"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No categories found matching filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
