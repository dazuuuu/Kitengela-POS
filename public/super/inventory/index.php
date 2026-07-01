<?php
// public/super/inventory/index.php — category-grouped inventory overview
require_once __DIR__ . '/../../../app/app.php';
PageGuard::capability(Capabilities::INVENTORY_VIEW);

$pdo = Database::pdo();
$P = new Models\ProductModel($pdo);
$grouped = $P->listGroupedByCategory();
$productsBase = '/Rongai/public/super/products/';

$totals = ['products' => 0, 'stock_value' => 0.0, 'retail_value' => 0.0];
foreach ($grouped as $items) {
    foreach ($items as $p) {
        $totals['products']++;
        $qty = (float)$p['quantity'];
        $totals['stock_value'] += Models\ProductModel::stockValue((float)$p['buying_price'], $qty);
        $totals['retail_value'] += $qty * (float)($p['retail_price'] ?? $p['selling_price']);
    }
}

$page_title = 'Inventory';
ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="h5 fw-bold mb-1">Inventory by category</h1>
    <p class="text-muted small mb-0">Professional stock overview — click Edit to update any product.</p>
  </div>
  <a href="<?php echo $productsBase; ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add product</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-3">
        <div class="text-muted small text-uppercase fw-semibold">Products</div>
        <div class="h4 mb-0 fw-bold"><?php echo $totals['products']; ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-3">
        <div class="text-muted small text-uppercase fw-semibold">Stock value (cost)</div>
        <div class="h5 mb-0 fw-bold">KES <?php echo number_format($totals['stock_value'], 0); ?></div>
        <div class="text-muted small">Buying price &times; qty</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-3">
        <div class="text-muted small text-uppercase fw-semibold">Retail value</div>
        <div class="h5 mb-0 fw-bold text-primary">KES <?php echo number_format($totals['retail_value'], 0); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-3">
        <div class="text-muted small text-uppercase fw-semibold">Categories</div>
        <div class="h4 mb-0 fw-bold"><?php echo count($grouped); ?></div>
      </div>
    </div>
  </div>
</div>

<?php if (!$grouped): ?>
  <div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-body p-5 text-center text-muted">
      <i class="fas fa-warehouse fa-2x mb-3 d-block" style="opacity:.25;"></i>
      No products yet. <a href="<?php echo $productsBase; ?>">Add your first product</a>.
    </div>
  </div>
<?php else: ?>
  <?php foreach ($grouped as $catName => $items): ?>
  <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden;">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;">
      <div>
        <h2 class="h6 fw-bold mb-0"><i class="fas fa-folder-open me-2 text-primary"></i><?php echo htmlspecialchars($catName); ?></h2>
        <span class="text-muted small"><?php echo count($items); ?> product<?php echo count($items) !== 1 ? 's' : ''; ?></span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr class="text-muted small text-uppercase">
            <th style="width:48px;"></th>
            <th>Product</th>
            <th class="text-end">Stock</th>
            <th class="text-end">Buying</th>
            <th class="text-end">Stock value</th>
            <th class="text-end">Wholesale</th>
            <th class="text-end">Retail</th>
            <th class="text-end">W. profit</th>
            <th class="text-end">R. profit</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $p):
              $buy = (float)$p['buying_price'];
              $qty = (float)$p['quantity'];
              $wholesale = (float)($p['wholesale_price'] ?? $p['selling_price']);
              $retail = (float)($p['retail_price'] ?? $p['selling_price']);
              $pfW = Models\ProductModel::profit($buy, $wholesale);
              $pfR = Models\ProductModel::profit($buy, $retail);
              $stockVal = Models\ProductModel::stockValue($buy, $qty);
              $low = $qty <= (int)$p['low_stock_threshold'];
          ?>
          <tr>
            <td>
              <?php if (!empty($p['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($p['image_path']); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
              <?php else: ?>
                <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width:40px;height:40px;border-radius:8px;background:#f1f5f9;"><i class="fas fa-box"></i></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
              <?php if ($p['subcategory_name']): ?><div class="text-muted small"><?php echo htmlspecialchars($p['subcategory_name']); ?></div><?php endif; ?>
            </td>
            <td class="text-end <?php echo $low ? 'text-danger fw-semibold' : ''; ?>">
              <?php echo rtrim(rtrim(number_format($qty, 2), '0'), '.'); ?>
              <span class="text-muted small"><?php echo htmlspecialchars($p['unit']); ?></span>
            </td>
            <td class="text-end text-muted">KES <?php echo number_format($buy, 0); ?></td>
            <td class="text-end fw-semibold">KES <?php echo number_format($stockVal, 0); ?></td>
            <td class="text-end">KES <?php echo number_format($wholesale, 0); ?></td>
            <td class="text-end">KES <?php echo number_format($retail, 0); ?></td>
            <td class="text-end"><span class="<?php echo $pfW['unit_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">KES <?php echo number_format($pfW['unit_profit'], 0); ?></span></td>
            <td class="text-end"><span class="<?php echo $pfR['unit_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">KES <?php echo number_format($pfR['unit_profit'], 0); ?></span></td>
            <td><?php echo $p['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Draft</span>'; ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?php echo $productsBase; ?>?edit=<?php echo (int)$p['id']; ?>">Edit</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/tenants/layout.php';
