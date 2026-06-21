<?php
// public/super/categories/index.php
require_once __DIR__ . '/../../../app/app.php';
PageGuard::tenant();

$pdo = Database::pdo();
$C = new Models\CategoryModel($pdo);
$error = '';
$old = '';

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = $editId > 0 ? $C->find($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $old = trim($_POST['name'] ?? '');
        $res = $C->create($old);
        if ($res['ok']) {
            $_SESSION['flash']['success'] = 'Category "' . $old . '" added.';
            header('Location: /Modern/public/super/categories/'); exit;
        }
        $error = $res['error'];
    } elseif ($action === 'rename') {
        $id = (int) ($_POST['id'] ?? 0);
        $old = trim($_POST['name'] ?? '');
        $res = $C->rename($id, $old);
        if ($res['ok']) {
            $_SESSION['flash']['success'] = 'Category updated.';
            header('Location: /Modern/public/super/categories/'); exit;
        }
        $error = $res['error']; $editRow = $C->find($id);
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $C->find($id);
        if ($row) { $C->setStatus($id, $row['status'] === 'active' ? 'draft' : 'active'); }
        $_SESSION['flash']['success'] = 'Category status updated.';
        header('Location: /Modern/public/super/categories/'); exit;
    } elseif ($action === 'delete') {
        $res = $C->deleteSafe((int) ($_POST['id'] ?? 0));
        $_SESSION['flash'][$res['ok'] ? 'success' : 'error'] = $res['ok'] ? 'Category deleted.' : $res['error'];
        header('Location: /Modern/public/super/categories/'); exit;
    }
}

$categories = $C->listWithCounts();
$page_title = 'Categories';
ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-1"><?php echo $editRow ? 'Rename category' : 'Add a category'; ?></h2>
        <p class="text-muted small mb-3">Group your products. You can add subcategories inside each one.</p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" novalidate>
          <input type="hidden" name="action" value="<?php echo $editRow ? 'rename' : 'create'; ?>">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>"><?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Category name</label>
            <input name="name" class="form-control" placeholder="e.g. Drinks"
                   value="<?php echo htmlspecialchars($editRow['name'] ?? $old); ?>" required autofocus>
          </div>
          <button class="btn btn-primary"><?php echo $editRow ? 'Save' : 'Add category'; ?></button>
          <?php if ($editRow): ?><a class="btn btn-link" href="/Modern/public/super/categories/">Cancel</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">Your categories <span class="badge bg-light text-dark"><?php echo count($categories); ?></span></h2>
        <?php if (!$categories): ?>
          <div class="text-muted">No categories yet. Add your first one on the left.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr class="text-muted small text-uppercase"><th>Name</th><th>Status</th><th class="text-center">Subcats</th><th class="text-center">Products</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                  <td class="fw-semibold"><?php echo htmlspecialchars($c['name']); ?></td>
                  <td>
                    <?php if ($c['status'] === 'active'): ?><span class="badge bg-success">Active</span>
                    <?php else: ?><span class="badge bg-secondary">Draft</span><?php endif; ?>
                  </td>
                  <td class="text-center">
                    <a href="/Modern/public/super/subcategories/?category_id=<?php echo (int)$c['id']; ?>" class="badge bg-light text-dark text-decoration-none"><?php echo (int)$c['subcategory_count']; ?> manage</a>
                  </td>
                  <td class="text-center"><span class="badge bg-light text-dark"><?php echo (int)$c['product_count']; ?></span></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary" href="/Modern/public/super/categories/?edit=<?php echo (int)$c['id']; ?>">Edit</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                      <button class="btn btn-sm btn-outline-secondary"><?php echo $c['status'] === 'active' ? 'Draft' : 'Activate'; ?></button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                      <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                      <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/tenants/layout.php';