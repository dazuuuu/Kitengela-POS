<?php
// public/super/products/index.php
require_once __DIR__ . '/../../../app/app.php';
PageGuard::capability(Capabilities::INVENTORY_EDIT);

$pdo = Database::pdo();
$C = new Models\CategoryModel($pdo);
$S = new Models\SubcategoryModel($pdo);
$P = new Models\ProductModel($pdo);

$base = '/Rongai/public/super/products/';

$categories = $C->all([], 'name ASC');
$allSubs    = $S->all([], 'name ASC');

$editId  = (int) ($_GET['edit'] ?? $_POST['id'] ?? 0);
$editRow = $editId > 0 ? $P->find($editId) : null;
if (!$editRow) { $editId = 0; }

$errors = [];
$old = [];

/** Validate + store an uploaded product image. Returns ['ok','path'|'error','skip']. */
function product_handle_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        return ['ok' => true, 'path' => null, 'skip' => true];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Image upload failed. Try a smaller file.'];
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Image must be under 3 MB.'];
    }
    $info = @getimagesize($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Use a JPG, PNG, WEBP or GIF image.'];
    }
    $dir = ROOT_PATH . '/public/assets/uploads/products';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $name = 'prod_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'error' => 'Could not save the image. Check folder permissions.'];
    }
    return ['ok' => true, 'path' => '/Rongai/public/assets/uploads/products/' . $name];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);
    if ($postId > 0) {
        $editId = $postId;
        $editRow = $P->find($editId) ?: null;
    }

    if ($action === 'toggle') {
        $row = $P->find((int) ($_POST['id'] ?? 0));
        if ($row) { $P->setStatus((int) $row['id'], $row['status'] === 'active' ? 'draft' : 'active'); }
        $_SESSION['flash']['success'] = 'Product status updated.';
        header('Location: ' . $base); exit;
    }
    if ($action === 'delete') {
        $P->deleteSafe((int) ($_POST['id'] ?? 0));
        $_SESSION['flash']['success'] = 'Product deleted.';
        header('Location: ' . $base); exit;
    }

    // create / save (active) or draft
    $in = [
        'name'                => trim($_POST['name'] ?? ''),
        'category_id'         => (int) ($_POST['category_id'] ?? 0),
        'subcategory_id'      => (int) ($_POST['subcategory_id'] ?? 0),
        'description'         => trim($_POST['description'] ?? ''),
        'quantity'            => $_POST['quantity'] ?? '',
        'unit'                => $_POST['unit'] ?? 'piece',
        'buying_price'        => $_POST['buying_price'] ?? '',
        'wholesale_price'     => $_POST['wholesale_price'] ?? '',
        'retail_price'        => $_POST['retail_price'] ?? '',
        'low_stock_threshold' => (int) ($_POST['low_stock_threshold'] ?? 10),
        'colors'              => array_filter(array_map('trim', explode(',', $_POST['colors'] ?? ''))),
        'sizes'               => array_filter(array_map('trim', explode(',', $_POST['sizes'] ?? ''))),
        'status'              => $action === 'draft' ? 'draft' : 'active',
    ];
    $old = $in;

    // Image: keep existing on edit unless a new one is uploaded.
    $img = product_handle_image($_FILES['image'] ?? []);
    if (!$img['ok']) {
        $errors['image'] = $img['error'];
    } else {
        $in['image_path'] = empty($img['skip']) ? $img['path'] : ($editRow['image_path'] ?? null);
    }

    if (!$errors) {
        $res = $editRow ? $P->edit($editId, $in) : $P->create($in);
        if ($res['ok']) {
            $_SESSION['flash']['success'] = $editRow ? 'Product updated.' : 'Product "' . $in['name'] . '" added.';
            header('Location: ' . $base); exit;
        }
        $errors = $res['errors'];
    }
    // keep edit context on validation failure
    if ($editRow) { $old['image_path'] = $editRow['image_path'] ?? null; }
}

// Prefill values (edit row, or repopulated $old after a failed submit)
$val = function (string $k, $default = '') use ($editRow, $old) {
    if (!empty($old)) { return $old[$k] ?? $default; }
    return $editRow[$k] ?? $default;
};
$csv = function (?string $json): string {
    $a = $json ? json_decode($json, true) : [];
    return is_array($a) ? implode(', ', $a) : '';
};
$colorsVal = !empty($old) ? implode(', ', (array) ($old['colors'] ?? [])) : $csv($editRow['colors'] ?? null);
$sizesVal  = !empty($old) ? implode(', ', (array) ($old['sizes'] ?? []))  : $csv($editRow['sizes'] ?? null);
$curImage  = $editRow['image_path'] ?? ($old['image_path'] ?? null);

$products = $P->listWithMeta();
$page_title = 'Products';

// subcategories grouped by category for the dependent dropdown
$subsByCat = [];
foreach ($allSubs as $s) { $subsByCat[(int) $s['category_id']][] = ['id' => (int) $s['id'], 'name' => $s['name']]; }

ob_start();
$unitLabels = ['piece' => 'Piece(s)', 'g' => 'Grams (g)', 'kg' => 'Kilograms (kg)', 'tonne' => 'Tonnes', 'ml' => 'Millilitres (ml)', 'litre' => 'Litres'];
?>
<div class="row g-4">
  <!-- form -->
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-1"><?php echo $editRow ? 'Edit product' : 'Add a product'; ?></h2>
        <p class="text-muted small mb-3">Set buying, wholesale and retail prices. Stock value = buying price &times; quantity.</p>

        <?php if (!empty($errors['_'])): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($errors['_']); ?></div><?php endif; ?>
        <?php if (!empty($errors['image'])): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($errors['image']); ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="action" value="save">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>"><?php endif; ?>

          <div class="row g-2">
            <div class="col-7 mb-3">
              <label class="form-label">Category <span class="text-muted">(optional)</span></label>
              <select name="category_id" id="catSel" class="form-select">
                <option value="">Uncategorized</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>" <?php echo ((string)$val('category_id') === (string)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['category_id'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['category_id']); ?></small><?php endif; ?>
            </div>
            <div class="col-5 mb-3">
              <label class="form-label">Subcategory <span class="text-muted">(optional)</span></label>
              <select name="subcategory_id" id="subSel" class="form-select"><option value="">—</option></select>
              <?php if (!empty($errors['subcategory_id'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['subcategory_id']); ?></small><?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Product name</label>
            <input name="name" class="form-control" value="<?php echo htmlspecialchars($val('name')); ?>" placeholder="e.g. Coca-Cola 500ml" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['name']); ?></small><?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(optional)</span></label>
            <textarea name="description" class="form-control" rows="2" placeholder="Short note about the product"><?php echo htmlspecialchars($val('description')); ?></textarea>
          </div>

          <div class="row g-2">
            <div class="col-6 mb-3">
              <label class="form-label">Quantity in stock</label>
              <input name="quantity" id="qtyP" type="number" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($val('quantity')); ?>" placeholder="0">
              <?php if (!empty($errors['quantity'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['quantity']); ?></small><?php endif; ?>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Unit</label>
              <select name="unit" class="form-select">
                <?php foreach ($unitLabels as $u => $lbl): ?>
                  <option value="<?php echo $u; ?>" <?php echo ((string)$val('unit', 'piece') === $u) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-4 mb-3">
              <label class="form-label">Buying price (KES)</label>
              <input name="buying_price" id="buyP" type="number" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($val('buying_price')); ?>" placeholder="0">
              <?php if (!empty($errors['buying_price'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['buying_price']); ?></small><?php endif; ?>
            </div>
            <div class="col-4 mb-3">
              <label class="form-label">Wholesale (KES)</label>
              <input name="wholesale_price" id="wholeP" type="number" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($val('wholesale_price', $val('selling_price'))); ?>" placeholder="0">
              <?php if (!empty($errors['wholesale_price'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['wholesale_price']); ?></small><?php endif; ?>
            </div>
            <div class="col-4 mb-3">
              <label class="form-label">Retail (KES)</label>
              <input name="retail_price" id="retailP" type="number" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($val('retail_price', $val('selling_price'))); ?>" placeholder="0">
              <?php if (!empty($errors['retail_price'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['retail_price']); ?></small><?php endif; ?>
            </div>
          </div>

          <div id="stockValueBox" class="alert alert-light border py-2 small mb-2" style="display:none;"></div>
          <div id="profitBox" class="alert alert-secondary py-2 small mb-3" style="display:none;"></div>

          <div class="row g-2">
            <div class="col-6 mb-3">
              <label class="form-label">Colours <span class="text-muted">(optional)</span></label>
              <input name="colors" class="form-control" value="<?php echo htmlspecialchars($colorsVal); ?>" placeholder="Blue, Red">
              <small class="text-muted">Comma-separated</small>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Sizes <span class="text-muted">(optional)</span></label>
              <input name="sizes" class="form-control" value="<?php echo htmlspecialchars($sizesVal); ?>" placeholder="S, M, L">
              <small class="text-muted">Comma-separated</small>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Restock alert when stock reaches</label>
            <input name="low_stock_threshold" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($val('low_stock_threshold', 10)); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Product image <span class="text-muted">(optional)</span></label>
            <?php if ($curImage): ?>
              <div class="mb-2"><img src="<?php echo htmlspecialchars($curImage); ?>" alt="" style="height:54px;border-radius:8px;border:1px solid #e2e8f0;"></div>
            <?php endif; ?>
            <input name="image" type="file" accept="image/*" class="form-control">
            <small class="text-muted">JPG, PNG, WEBP or GIF, under 3 MB.<?php echo $curImage ? ' Leave empty to keep the current image.' : ''; ?></small>
          </div>

          <button class="btn btn-primary" name="action" value="save"><?php echo $editRow ? 'Save product' : 'Add product'; ?></button>
          <button class="btn btn-outline-secondary" name="action" value="draft">Save as draft</button>
          <?php if ($editRow): ?><a class="btn btn-link" href="<?php echo $base; ?>">Cancel</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <!-- list -->
  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">Your products <span class="badge bg-light text-dark"><?php echo count($products); ?></span></h2>
        <?php if (!$products): ?>
          <div class="text-muted">No products yet. Add your first one on the left.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr class="text-muted small text-uppercase"><th></th><th>Product</th><th class="text-end">Stock</th><th class="text-end">Retail</th><th class="text-end">Wholesale profit</th><th class="text-end">Retail profit</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($products as $p):
                    $retail = (float)($p['retail_price'] ?? $p['selling_price']);
                    $wholesale = (float)($p['wholesale_price'] ?? $p['selling_price']);
                    $buy = (float)$p['buying_price'];
                    $qty = (float)$p['quantity'];
                    $pfW = Models\ProductModel::profit($buy, $wholesale);
                    $pfR = Models\ProductModel::profit($buy, $retail);
                    $stockVal = Models\ProductModel::stockValue($buy, $qty);
                    $low = $qty <= (int)$p['low_stock_threshold'];
                ?>
                <tr>
                  <td style="width:46px;">
                    <?php if (!empty($p['image_path'])): ?>
                      <img src="<?php echo htmlspecialchars($p['image_path']); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                    <?php else: ?>
                      <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width:40px;height:40px;border-radius:8px;background:#f1f5f9;"><i class="fas fa-box"></i></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="text-muted small"><?php echo $p['category_name'] ? htmlspecialchars($p['category_name']) : 'Uncategorized'; ?><?php echo $p['subcategory_name'] ? ' · ' . htmlspecialchars($p['subcategory_name']) : ''; ?></div>
                  </td>
                  <td class="text-end <?php echo $low ? 'text-danger fw-semibold' : ''; ?>">
                    <?php echo rtrim(rtrim(number_format($qty, 2), '0'), '.'); ?> <span class="text-muted small"><?php echo htmlspecialchars($p['unit']); ?></span>
                    <div class="text-muted small">Cost KES <?php echo number_format($stockVal, 0); ?></div>
                    <?php if ($low): ?><i class="fas fa-triangle-exclamation ms-1" title="Low stock"></i><?php endif; ?>
                  </td>
                  <td class="text-end">KES <?php echo number_format($retail, 0); ?></td>
                  <td class="text-end">KES <?php echo number_format($pfW['unit_profit'], 0); ?><div class="text-muted small"><?php echo $pfW['margin_pct'] !== null ? $pfW['margin_pct'] . '%' : '—'; ?></div></td>
                  <td class="text-end">KES <?php echo number_format($pfR['unit_profit'], 0); ?><div class="text-muted small"><?php echo $pfR['margin_pct'] !== null ? $pfR['margin_pct'] . '%' : '—'; ?></div></td>
                  <td><?php echo $p['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Draft</span>'; ?></td>
                  <td class="text-end" style="white-space:nowrap;">
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>?edit=<?php echo (int)$p['id']; ?>">Edit</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                      <button class="btn btn-sm btn-outline-secondary"><?php echo $p['status'] === 'active' ? 'Draft' : 'Activate'; ?></button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?');">
                      <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
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

<script>
  // Dependent subcategory dropdown
  var SUBS = <?php echo json_encode($subsByCat); ?>;
  var SELECTED_SUB = <?php echo json_encode((string)$val('subcategory_id')); ?>;
  var catSel = document.getElementById('catSel'), subSel = document.getElementById('subSel');
  function fillSubs() {
    var list = SUBS[catSel.value] || [];
    subSel.innerHTML = '<option value="">—</option>';
    list.forEach(function (s) {
      var o = document.createElement('option');
      o.value = s.id; o.textContent = s.name;
      if (String(s.id) === String(SELECTED_SUB)) o.selected = true;
      subSel.appendChild(o);
    });
  }
  if (catSel) { catSel.addEventListener('change', function () { SELECTED_SUB = ''; fillSubs(); }); fillSubs(); }

  // Live stock value + profit readout
  var buyP = document.getElementById('buyP'), wholeP = document.getElementById('wholeP'),
      retailP = document.getElementById('retailP'), qtyP = document.getElementById('qtyP'),
      box = document.getElementById('profitBox'), stockBox = document.getElementById('stockValueBox');
  function calcProfit() {
    var b = parseFloat(buyP.value), w = parseFloat(wholeP.value), r = parseFloat(retailP.value), q = parseFloat(qtyP.value) || 0;
    if (isNaN(b)) { box.style.display = 'none'; stockBox.style.display = 'none'; return; }
    if (!isNaN(q) && q >= 0) {
      stockBox.style.display = 'block';
      stockBox.innerHTML = 'Stock value at cost: <strong>KES ' + (b * q).toFixed(0) + '</strong> (buying &times; quantity)';
    } else { stockBox.style.display = 'none'; }
    if (isNaN(w) && isNaN(r)) { box.style.display = 'none'; return; }
    var wProfit = isNaN(w) ? null : w - b;
    var rProfit = isNaN(r) ? null : r - b;
    box.style.display = 'block';
    var html = '';
    if (wProfit !== null) {
      var wMargin = w > 0 ? (wProfit / w * 100) : 0;
      html += '<div><strong>Wholesale</strong> profit/unit: KES ' + wProfit.toFixed(0) + ' &middot; margin ' + wMargin.toFixed(1) + '%';
      if (q > 0) html += ' &middot; total profit if all sold: KES ' + (wProfit * q).toFixed(0);
      html += '</div>';
    }
    if (rProfit !== null) {
      var rMargin = r > 0 ? (rProfit / r * 100) : 0;
      html += '<div class="mt-1"><strong>Retail</strong> profit/unit: KES ' + rProfit.toFixed(0) + ' &middot; margin ' + rMargin.toFixed(1) + '%';
      if (q > 0) html += ' &middot; total profit if all sold: KES ' + (rProfit * q).toFixed(0);
      html += '</div>';
    }
    box.className = 'alert py-2 small mb-3 ' + ((wProfit !== null && wProfit < 0) || (rProfit !== null && rProfit < 0) ? 'alert-danger' : 'alert-success');
    box.innerHTML = html;
  }
  [buyP, wholeP, retailP, qtyP].forEach(function(el){ if(el) el.addEventListener('input', calcProfit); });
  calcProfit();
</script>
<?php
$content = ob_get_clean();
$__layout = TenantContext::role() === 'staff' ? 'staff' : 'tenants';
include __DIR__ . '/../../templates/' . $__layout . '/layout.php';