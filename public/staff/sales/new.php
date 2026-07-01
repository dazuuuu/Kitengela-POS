<?php
// public/staff/sales/new.php  — point-of-sale: record a sale
require_once __DIR__ . '/../../../app/app.php';
PageGuard::auth(Capabilities::SALES_RECORD);

$pdo = Database::pdo();

$stmt = $pdo->prepare('SELECT u.branch_id, b.title FROM users u LEFT JOIN branches b ON b.id = u.branch_id WHERE u.id = ?');
$stmt->execute([TenantContext::userId()]);
$me = $stmt->fetch() ?: [];
$branchId   = !empty($me['branch_id']) ? (int) $me['branch_id'] : null;
$branchName = $me['title'] ?? '';

$__tenant   = (new Models\TenantModel($pdo))->find(TenantContext::tenantId());
$tenantSlug = $__tenant['slug'] ?? '';
$shopName   = $__tenant['name'] ?? 'Our Shop';
$catalogueUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
              . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
              . '/Kitale/public/catalogue.php?shop=' . urlencode($tenantSlug);

$P = new Models\ProductModel($pdo);
$products = $P->sellable();

$error   = '';
$cartJson = '[]';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    $cartJson = $_POST['cart'] ?? '[]';
    if (!is_array($cart)) { $cart = []; }
    $items = [];
    foreach ($cart as $c) {
        $items[] = ['product_id' => (int) ($c['product_id'] ?? 0), 'quantity' => (float) ($c['quantity'] ?? 0)];
    }
    $res = (new Models\SaleModel($pdo))->record([
        'sale_type'       => $_POST['sale_type'] ?? 'retail',
        'payment_method'  => $_POST['payment_method'] ?? '',
        'cash_amount'     => $_POST['cash_amount'] ?? 0,
        'mpesa_amount'    => $_POST['mpesa_amount'] ?? 0,
        'amount_given'    => $_POST['amount_given'] ?? 0,
        'discount_amount' => $_POST['discount_amount'] ?? 0,
        'staff_id'        => TenantContext::userId(),
        'branch_id'       => $branchId,
        'customer_name'   => $_POST['customer_name'] ?? '',
        'customer_phone'  => $_POST['customer_phone'] ?? '',
        'customer_email'  => $_POST['customer_email'] ?? '',
        'items'           => $items,
    ]);
    if ($res['ok']) {
        $_SESSION['flash']['success'] = 'Sale recorded — ' . $res['receipt_number'] . '.';
        header('Location: /Kitale/public/staff/sales/receipt.php?id=' . $res['sale_id']);
        exit;
    }
    $error = $res['errors']['_'] ?? ($res['errors']['payment_method'] ?? ($res['errors']['amount_given'] ?? ($res['errors']['discount_amount'] ?? 'Could not record the sale.')));
}

$page_title = 'Make a sale';
ob_start();
?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if (!$products): ?>
  <div class="alert alert-warning">No products in stock to sell. Ask the owner to add stock.</div>
<?php else: ?>
<form method="post" id="saleForm">
<input type="hidden" name="cart" id="cartInput" value="">
<div class="row g-4">

  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;">
      <div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:18px 20px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="h6 mb-0 text-white fw-bold"><i class="fas fa-box me-2" style="color:#60a5fa;"></i>Products</h2>
            <?php if ($branchName): ?><span class="badge mt-1" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.85);font-size:.7rem;"><?php echo htmlspecialchars($branchName); ?></span><?php endif; ?>
          </div>
          <button type="button" class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#shareCatalogueModal"
                  style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:9px;font-size:.8rem;">
            <i class="fas fa-share-nodes me-1" style="color:#a5b4fc;"></i>Share Catalogue
          </button>
        </div>
        <div class="btn-group w-100 mb-3" role="group">
          <input type="radio" class="btn-check" name="sale_type" id="typeRetail" value="retail" checked>
          <label class="btn btn-sm btn-outline-light" for="typeRetail">Retail prices</label>
          <input type="radio" class="btn-check" name="sale_type" id="typeWholesale" value="wholesale">
          <label class="btn btn-sm btn-outline-light" for="typeWholesale">Wholesale prices</label>
        </div>
        <div class="position-relative">
          <i class="fas fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);font-size:.85rem;"></i>
          <input type="text" id="search" class="form-control" placeholder="Search products…" autocomplete="off"
                 style="padding-left:36px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:.9rem;">
        </div>
        <div id="searchHint" class="mt-2" style="font-size:.75rem;color:rgba(255,255,255,.5);">
          Showing first 3 products — type to search all <?php echo count($products); ?>
        </div>
      </div>
      <div class="card-body p-3" style="background:#fff;">
        <div id="productList" style="max-height:420px;overflow-y:auto;">
          <?php foreach ($products as $idx => $p):
              $retail = (float)($p['retail_price'] ?? $p['selling_price']);
              $wholesale = (float)($p['wholesale_price'] ?? $p['selling_price']);
          ?>
            <button type="button" class="prod btn w-100 text-start border rounded mb-2 p-0 overflow-hidden"
                    data-id="<?php echo (int)$p['id']; ?>"
                    data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>"
                    data-retail="<?php echo $retail; ?>"
                    data-wholesale="<?php echo $wholesale; ?>"
                    data-stock="<?php echo (float)$p['quantity']; ?>"
                    data-unit="<?php echo htmlspecialchars($p['unit'], ENT_QUOTES); ?>"
                    data-idx="<?php echo $idx; ?>"
                    style="transition:all .15s;border-color:#e2e8f0!important;">
              <div class="d-flex align-items-center gap-0">
                <div class="flex-shrink-0" style="width:60px;height:60px;background:#f1f5f9;overflow:hidden;">
                  <?php if (!empty($p['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($p['image_path']); ?>" alt="" style="width:60px;height:60px;object-fit:cover;">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100" style="color:#94a3b8;"><i class="fas fa-box" style="font-size:1.3rem;"></i></div>
                  <?php endif; ?>
                </div>
                <div class="px-3 flex-grow-1 text-start">
                  <div class="fw-semibold" style="font-size:.88rem;color:#0f172a;"><?php echo htmlspecialchars($p['name']); ?></div>
                  <small class="text-muted"><?php echo rtrim(rtrim(number_format((float)$p['quantity'],2),'0'),'.'); ?> <?php echo htmlspecialchars($p['unit']); ?> in stock</small>
                </div>
                <div class="px-3 text-end">
                  <div class="prod-price fw-bold text-nowrap" style="font-size:.88rem;color:#1d4ed8;">KES <?php echo number_format($retail,0); ?></div>
                  <small class="text-muted prod-alt" style="font-size:.7rem;">W: <?php echo number_format($wholesale,0); ?></small>
                </div>
              </div>
            </button>
          <?php endforeach; ?>
          <div id="noMatch" class="text-muted small text-center py-3" style="display:none;"><i class="fas fa-search me-1"></i>No products match.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
      <div class="card-body p-3 p-md-4">
        <h2 class="h6 mb-3">This sale <span id="saleTypeBadge" class="badge bg-primary ms-1">Retail</span></h2>
        <div id="cartEmpty" class="text-muted small mb-2">Tap a product to add it.</div>
        <table class="table table-sm align-middle mb-2" id="cartTable" style="display:none;"><tbody id="cartRows"></tbody></table>
        <div class="d-flex justify-content-between small text-muted"><span>Subtotal</span><span>KES <span id="subtotalOut">0</span></span></div>
        <div class="d-flex justify-content-between small text-danger mb-1" id="discountRow" style="display:none!important;"><span>Discount</span><span>− KES <span id="discountOut">0</span></span></div>
        <div class="d-flex justify-content-between border-top pt-2 mt-1">
          <span class="fw-semibold">Total</span>
          <span class="fw-bold fs-5">KES <span id="totalOut">0</span></span>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
      <div class="card-body p-3 p-md-4">
        <h2 class="h6 mb-3">Discount <span class="text-muted fw-normal small">(negotiated)</span></h2>
        <div class="input-group">
          <span class="input-group-text">KES</span>
          <input type="number" step="0.01" min="0" name="discount_amount" id="discountAmt" class="form-control" placeholder="0" value="0">
        </div>
        <small class="text-muted">Subtract from subtotal — e.g. 200 total with 20 discount = 180 due.</small>
      </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;">
      <div class="card-body p-3 p-md-4">
        <h2 class="h6 mb-3">Payment</h2>
        <div class="btn-group w-100 mb-3" role="group">
          <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked>
          <label class="btn btn-outline-primary" for="payCash"><i class="fas fa-money-bill-wave me-1"></i>Cash</label>
          <input type="radio" class="btn-check" name="payment_method" id="payMpesa" value="mpesa">
          <label class="btn btn-outline-success" for="payMpesa"><i class="fas fa-mobile-screen me-1"></i>M-Pesa</label>
          <input type="radio" class="btn-check" name="payment_method" id="paySplit" value="split">
          <label class="btn btn-outline-secondary" for="paySplit"><i class="fas fa-divide me-1"></i>Split</label>
        </div>

        <div id="cashOnlyBox">
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small">Cash given</label>
              <input type="number" step="0.01" min="0" name="amount_given" id="amountGiven" class="form-control" placeholder="0">
            </div>
            <div class="col-6">
              <label class="form-label small">Change</label>
              <div class="form-control bg-light" id="changeOut">KES 0</div>
            </div>
          </div>
        </div>

        <div id="splitBox" style="display:none;">
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small">Cash portion</label>
              <input type="number" step="0.01" min="0" name="cash_amount" id="cashPortion" class="form-control" placeholder="0">
            </div>
            <div class="col-6">
              <label class="form-label small">M-Pesa portion</label>
              <input type="number" step="0.01" min="0" name="mpesa_amount" id="mpesaPortion" class="form-control" placeholder="0">
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small">Cash tendered</label>
              <input type="number" step="0.01" min="0" id="splitCashGiven" class="form-control" placeholder="For change">
            </div>
            <div class="col-6">
              <label class="form-label small">Change</label>
              <div class="form-control bg-light" id="splitChangeOut">KES 0</div>
            </div>
          </div>
          <small class="text-muted d-block mb-2" id="splitHint">Cash + M-Pesa must equal the total.</small>
        </div>

        <hr>
        <p class="small text-muted mb-2">Customer (optional)</p>
        <div class="mb-2"><input name="customer_name" class="form-control" placeholder="Customer name"></div>
        <div class="row g-2 mb-3">
          <div class="col-6"><input name="customer_phone" class="form-control" placeholder="Phone (2547…)"></div>
          <div class="col-6"><input name="customer_email" type="email" class="form-control" placeholder="Email"></div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg" id="completeBtn" disabled>Complete sale</button>
      </div>
    </div>
  </div>
</div>
</form>

<style>
.prod:hover { background:#f0f7ff!important; border-color:#bfdbfe!important; }
#search::placeholder { color:rgba(255,255,255,.45)!important; }
</style>

<script>
var PRODUCTS = {}, IDLE_LIMIT = 3, totalProducts = <?php echo count($products); ?>;
var saleType = 'retail';

document.querySelectorAll('.prod').forEach(function (b) {
    PRODUCTS[b.dataset.id] = {
        id: b.dataset.id, name: b.dataset.name,
        retail: parseFloat(b.dataset.retail), wholesale: parseFloat(b.dataset.wholesale),
        stock: parseFloat(b.dataset.stock), unit: b.dataset.unit, el: b
    };
});

var cart = {};
try { (JSON.parse(<?php echo json_encode($cartJson); ?>) || []).forEach(function (c) { cart[c.product_id] = c.quantity; }); } catch (e) {}

function activePrice(p) { return saleType === 'wholesale' ? p.wholesale : p.retail; }
function money(n) { return n.toLocaleString('en-KE', {maximumFractionDigits:0}); }
function subtotal() { var t=0; for (var id in cart){ t += activePrice(PRODUCTS[id]) * cart[id]; } return t; }
function discount() { return Math.max(0, parseFloat(document.getElementById('discountAmt').value) || 0); }
function grandTotal() { return Math.max(0, subtotal() - discount()); }

function updateProductPrices() {
    document.querySelectorAll('.prod').forEach(function(b) {
        var p = PRODUCTS[b.dataset.id];
        var price = activePrice(p);
        b.querySelector('.prod-price').textContent = 'KES ' + money(price);
        b.querySelector('.prod-alt').textContent = saleType === 'retail' ? ('W: ' + money(p.wholesale)) : ('R: ' + money(p.retail));
    });
    document.getElementById('saleTypeBadge').textContent = saleType === 'wholesale' ? 'Wholesale' : 'Retail';
    document.getElementById('saleTypeBadge').className = 'badge ms-1 ' + (saleType === 'wholesale' ? 'bg-info text-dark' : 'bg-primary');
    render();
}

function render() {
    var rows = document.getElementById('cartRows'), ids = Object.keys(cart);
    rows.innerHTML = '';
    document.getElementById('cartTable').style.display = ids.length ? 'table' : 'none';
    document.getElementById('cartEmpty').style.display = ids.length ? 'none' : 'block';
    ids.forEach(function (id) {
        var p = PRODUCTS[id], qty = cart[id], price = activePrice(p);
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><div class="fw-semibold">'+p.name+'</div><small class="text-muted">KES '+money(price)+' × '+qty+'</small></td>'
            + '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" data-dec="'+id+'">−</button>'
            + '<span class="mx-2">'+qty+'</span><button type="button" class="btn btn-sm btn-outline-secondary" data-inc="'+id+'">+</button>'
            + '<button type="button" class="btn btn-sm btn-link text-danger" data-del="'+id+'">remove</button></td>'
            + '<td class="text-end fw-semibold">KES '+money(price*qty)+'</td>';
        rows.appendChild(tr);
    });
    var sub = subtotal(), disc = discount(), tot = grandTotal();
    document.getElementById('subtotalOut').textContent = money(sub);
    document.getElementById('discountOut').textContent = money(disc);
    document.getElementById('discountRow').style.display = disc > 0 ? 'flex' : 'none';
    document.getElementById('totalOut').textContent = money(tot);
    document.getElementById('completeBtn').disabled = ids.length === 0;
    updateChange();
    document.getElementById('cartInput').value = JSON.stringify(ids.map(function(id){ return { product_id: parseInt(id,10), quantity: cart[id] }; }));
}

function add(id) {
    var p = PRODUCTS[id], cur = cart[id] || 0;
    if (cur+1 > p.stock) { alert('Only '+p.stock+' '+p.unit+' of '+p.name+' in stock.'); return; }
    cart[id] = cur+1; render();
}
function dec(id) { if(!cart[id]) return; cart[id]--; if(cart[id]<=0) delete cart[id]; render(); }

document.querySelectorAll('.prod').forEach(function(b){ b.addEventListener('click', function(){ add(b.dataset.id); }); });
document.getElementById('cartRows').addEventListener('click', function(e){
    var t=e.target;
    if (t.dataset.inc) add(t.dataset.inc);
    else if (t.dataset.dec) dec(t.dataset.dec);
    else if (t.dataset.del){ delete cart[t.dataset.del]; render(); }
});

document.querySelectorAll('input[name=sale_type]').forEach(function(r){
    r.addEventListener('change', function(){ saleType = this.value; updateProductPrices(); });
});

// Search filter
var searchInput = document.getElementById('search'), searchHint = document.getElementById('searchHint');
function applyFilter() {
    var q = searchInput.value.toLowerCase().trim(), any = false, shown = 0;
    document.querySelectorAll('.prod').forEach(function(b) {
        var show = q === '' ? parseInt(b.dataset.idx,10) < IDLE_LIMIT : b.dataset.name.toLowerCase().indexOf(q) !== -1;
        b.style.display = show ? '' : 'none';
        if (show) { any = true; shown++; }
    });
    document.getElementById('noMatch').style.display = (q !== '' && !any) ? 'block' : 'none';
    if (searchHint) searchHint.textContent = q === '' ? 'Showing '+Math.min(IDLE_LIMIT,totalProducts)+' products — type to search all '+totalProducts : shown+' result'+(shown!==1?'s':'');
}
searchInput.addEventListener('input', applyFilter); applyFilter();

// Payment UI
var cashOnlyBox = document.getElementById('cashOnlyBox'), splitBox = document.getElementById('splitBox');
function payMethod() { return document.querySelector('input[name=payment_method]:checked').value; }
function updateChange() {
    var tot = grandTotal(), method = payMethod();
    if (method === 'cash') {
        var given = parseFloat(document.getElementById('amountGiven').value) || 0;
        document.getElementById('changeOut').textContent = given >= tot ? ('KES '+money(given-tot)) : 'short';
    } else if (method === 'split') {
        var cashP = parseFloat(document.getElementById('cashPortion').value) || 0;
        var given = parseFloat(document.getElementById('splitCashGiven').value) || 0;
        document.getElementById('splitChangeOut').textContent = cashP > 0 && given >= cashP ? ('KES '+money(given-cashP)) : (cashP > 0 ? 'short' : '—');
        var mpesaP = parseFloat(document.getElementById('mpesaPortion').value) || 0;
        var rem = tot - cashP - mpesaP;
        document.getElementById('splitHint').textContent = Math.abs(rem) < 0.01 ? 'Split matches total.' : ('Remaining: KES '+money(Math.max(0,rem)));
    }
}
function syncPaymentBoxes() {
    var m = payMethod();
    cashOnlyBox.style.display = m === 'cash' ? 'block' : 'none';
    splitBox.style.display = m === 'split' ? 'block' : 'none';
    if (m === 'split') {
        document.getElementById('amountGiven').value = document.getElementById('splitCashGiven').value;
    }
    updateChange();
}
document.querySelectorAll('input[name=payment_method]').forEach(function(r){ r.addEventListener('change', syncPaymentBoxes); });
['amountGiven','cashPortion','mpesaPortion','splitCashGiven','discountAmt'].forEach(function(id){
    var el = document.getElementById(id); if(el) el.addEventListener('input', function(){ render(); if(id==='splitCashGiven') document.getElementById('amountGiven').value = this.value; });
});

document.getElementById('saleForm').addEventListener('submit', function(e){
    if (Object.keys(cart).length === 0){ e.preventDefault(); alert('Add at least one product.'); return; }
    var tot = grandTotal(), m = payMethod();
    if (m === 'cash') {
        var given = parseFloat(document.getElementById('amountGiven').value) || 0;
        if (given < tot) { e.preventDefault(); alert('Cash given is less than the total.'); return; }
    } else if (m === 'split') {
        var cashP = parseFloat(document.getElementById('cashPortion').value) || 0;
        var mpesaP = parseFloat(document.getElementById('mpesaPortion').value) || 0;
        if (Math.abs(cashP + mpesaP - tot) > 0.01) { e.preventDefault(); alert('Cash and M-Pesa portions must add up to KES '+money(tot)); return; }
        if (cashP > 0) {
            document.getElementById('amountGiven').value = document.getElementById('splitCashGiven').value || cashP;
            var given = parseFloat(document.getElementById('amountGiven').value) || 0;
            if (given < cashP) { e.preventDefault(); alert('Cash tendered is less than the cash portion.'); return; }
        }
    }
});

syncPaymentBoxes(); updateProductPrices();
</script>

<?php include __DIR__ . '/../../components/tenants/share_modal.php'; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/staff/layout.php';
