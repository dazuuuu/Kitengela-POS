<?php
// public/staff/sales/receipt.php?id=N  — view / print / send a receipt
require_once __DIR__ . '/../../../app/app.php';
PageGuard::auth(Capabilities::SALES_VIEW);

$pdo = Database::pdo();
$SA  = new Models\SaleModel($pdo);

$id   = (int) ($_GET['id'] ?? 0);
$sale = $id > 0 ? $SA->find($id) : null;
if (!$sale) {
    http_response_code(404);
    echo 'Receipt not found.';
    exit;
}
$items = $SA->items($id);

$shop   = (new Models\TenantModel($pdo))->find(TenantContext::tenantId())['name'] ?? 'My Shop';
$branch = '';
if (!empty($sale['branch_id'])) {
    $b = $pdo->prepare('SELECT title FROM branches WHERE id = ? AND tenant_id = ?');
    $b->execute([$sale['branch_id'], TenantContext::tenantId()]);
    $branch = (string) ($b->fetchColumn() ?: '');
}
$st = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$st->execute([$sale['staff_id']]);
$staff = (string) ($st->fetchColumn() ?: 'Staff');

function money($n) { return 'KES ' . number_format((float) $n, 2); }

function receipt_inner(array $sale, array $items, string $shop, string $branch, string $staff): string
{
    $h = fn($s) => htmlspecialchars((string) $s);
    $rows = '';
    foreach ($items as $it) {
        $qty = rtrim(rtrim(number_format((float) $it['quantity'], 2), '0'), '.');
        $rows .= '<tr>'
            . '<td style="padding:4px 0;">' . $h($it['product_name']) . '<br><span style="color:#64748b;font-size:12px;">' . $qty . ' ' . $h($it['unit']) . ' &times; ' . money($it['unit_price']) . '</span></td>'
            . '<td style="padding:4px 0;text-align:right;white-space:nowrap;">' . money($it['line_total']) . '</td>'
            . '</tr>';
    }
    $payLine = $sale['payment_method'] === 'cash'
        ? '<tr><td style="color:#64748b;">Cash given</td><td style="text-align:right;">' . money($sale['amount_given']) . '</td></tr>'
          . '<tr><td style="color:#64748b;">Change</td><td style="text-align:right;">' . money($sale['change_given']) . '</td></tr>'
        : '<tr><td style="color:#64748b;">Paid by</td><td style="text-align:right;">M-Pesa</td></tr>';
    $cust = '';
    if (!empty($sale['customer_name']) || !empty($sale['customer_phone'])) {
        $cust = '<p style="margin:10px 0 0;font-size:12px;color:#64748b;">Customer: ' . $h($sale['customer_name'] ?: '—')
              . (!empty($sale['customer_phone']) ? ' · ' . $h($sale['customer_phone']) : '') . '</p>';
    }
    return '<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;max-width:360px;margin:0 auto;color:#0f172a;">'
        . '<div style="text-align:center;border-bottom:2px dashed #cbd5e1;padding-bottom:10px;margin-bottom:10px;">'
        . '<div style="font-size:18px;font-weight:700;">' . $h($shop) . '</div>'
        . ($branch ? '<div style="font-size:13px;color:#475569;">' . $h($branch) . '</div>' : '')
        . '<div style="font-size:12px;color:#64748b;margin-top:4px;">Receipt ' . $h($sale['receipt_number']) . '</div>'
        . '<div style="font-size:12px;color:#64748b;">' . $h(date('j M Y, g:i a', strtotime($sale['created_at']))) . '</div>'
        . '<div style="font-size:12px;color:#64748b;">Served by ' . $h($staff) . '</div>'
        . '</div>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px;">' . $rows . '</table>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px;border-top:2px dashed #cbd5e1;margin-top:8px;padding-top:8px;">'
        . '<tr><td style="font-weight:700;padding-top:8px;">Total</td><td style="text-align:right;font-weight:700;padding-top:8px;">' . money($sale['total']) . '</td></tr>'
        . $payLine . '</table>'
        . $cust
        . '<p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:14px;">Thank you for your business.</p>'
        . '</div>';
}

// --- email delivery ---
$flash = '';
$flashOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'email') {
    $to = trim($_POST['email'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $flash = 'Enter a valid email address.';
    } else {
        $html = '<div style="background:#f8fafc;padding:20px;">' . receipt_inner($sale, $items, $shop, $branch, $staff) . '</div>';
        $sent = (new MailService())->send($to, 'Receipt ' . $sale['receipt_number'] . ' — ' . $shop, $html, 'Receipt ' . $sale['receipt_number'] . ' from ' . $shop);
        if ($sent) { $flash = 'Receipt sent to ' . $to . '.'; $flashOk = true; }
        else { $flash = 'Could not send the email. Check the mail settings and try again.'; }
    }
}

// WhatsApp number (Kenya-friendly)
$waNum = '';
if (!empty($sale['customer_phone'])) {
    $d = preg_replace('/\D+/', '', $sale['customer_phone']);
    if ($d !== '') {
        if (strpos($d, '0') === 0) { $d = '254' . substr($d, 1); }
        elseif (strpos($d, '254') !== 0) { $d = '254' . $d; }
        $waNum = $d;
    }
}
$waText = rawurlencode("Receipt {$sale['receipt_number']} from {$shop}\nTotal: " . money($sale['total']) . "\nThank you!");
$waLink = 'https://wa.me/' . $waNum . '?text=' . $waText;

$defaultEmail = htmlspecialchars($sale['customer_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt <?php echo htmlspecialchars($sale['receipt_number']); ?> — <?php echo htmlspecialchars($shop); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  body{background:#f1f5f9;margin:0;padding:24px;font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;}
  .sheet{background:#fff;max-width:420px;margin:0 auto 18px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);padding:24px;}
  .actions{max-width:420px;margin:0 auto;}
  @media print { body{background:#fff;padding:0;} .actions,.noprint{display:none !important;} .sheet{box-shadow:none;border-radius:0;margin:0;} }
</style>
</head>
<body>
  <?php if ($flash): ?>
    <div class="actions"><div class="alert <?php echo $flashOk ? 'alert-success' : 'alert-danger'; ?> py-2"><?php echo htmlspecialchars($flash); ?></div></div>
  <?php endif; ?>

  <div class="sheet"><?php echo receipt_inner($sale, $items, $shop, $branch, $staff); ?></div>

  <div class="actions">
    <div class="d-flex gap-2 mb-2">
      <button onclick="window.print()" class="btn btn-primary flex-fill"><i class="fas fa-print me-1"></i> Print / Save PDF</button>
      <a href="<?php echo htmlspecialchars($waLink); ?>" target="_blank" rel="noopener" class="btn btn-success flex-fill"><i class="fab fa-whatsapp me-1"></i> WhatsApp</a>
    </div>
    <form method="post" class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-3">
        <label class="form-label small mb-1">Email the receipt</label>
        <div class="input-group">
          <input type="email" name="email" class="form-control" placeholder="customer@email.com" value="<?php echo $defaultEmail; ?>" required>
          <input type="hidden" name="action" value="email">
          <button class="btn btn-outline-primary"><i class="fas fa-paper-plane me-1"></i> Send</button>
        </div>
      </div>
    </form>
    <div class="d-flex gap-2 mt-3">
      <a href="/Kitale/public/staff/sales/new.php" class="btn btn-link flex-fill">New sale</a>
      <a href="/Kitale/public/staff/sales/" class="btn btn-link flex-fill">My sales</a>
    </div>
  </div>
</body>
</html>