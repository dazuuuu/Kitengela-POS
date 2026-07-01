<?php
// public/super/dashboard/index.php — owner overview (summary cards + charts)
require_once __DIR__ . '/../../../app/app.php';
PageGuard::auth();

$pdo = Database::pdo();
$tid = (int) TenantContext::tenantId();
$__tenant = (new Models\TenantModel($pdo))->find($tid);
$stats = (new DashboardService($pdo))->overview($tid);

unset($_SESSION['first_login']);
$page_title = 'Dashboard';
$shop = $__tenant['name'] ?? 'your shop';

// Fill 7-day trend labels (missing days = 0)
$trendLabels = [];
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $trendLabels[] = date('D j', strtotime($d));
    $found = 0;
    foreach ($stats['trend'] as $row) {
        if ($row['d'] === $d) { $found = (float) $row['revenue']; break; }
    }
    $trendData[] = $found;
}

$totalSales = max(1, $stats['retail_sales'] + $stats['wholesale_sales']);
$pctRetail = round($stats['retail_sales'] / $totalSales * 100);
$pctWholesale = 100 - $pctRetail;
$rev = max(1, $stats['revenue_all']);
$pctProfit = min(100, max(0, round($stats['profit_all'] / $rev * 100)));
$pctCustomers = min(100, $stats['customers'] > 0 ? min(100, $stats['customers'] * 5) : 0);
$pctProducts = min(100, $stats['products'] > 0 ? min(100, $stats['products'] * 2) : 0);

$payCash = $stats['payment_split']['cash'] ?? 0;
$payMpesa = $stats['payment_split']['mpesa'] ?? 0;
$paySplit = $stats['payment_split']['split'] ?? 0;

ob_start();
?>
<style>
.dash-stat{display:flex;align-items:center;gap:16px;padding:20px;border-radius:10px;background:#fff;border:1px solid #e8e8e8;box-shadow:0 1px 3px rgba(0,0,0,.04);height:100%;}
.dash-stat .ico{width:56px;height:56px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;flex-shrink:0;}
.dash-stat .num{font-size:1.65rem;font-weight:700;line-height:1.1;color:#333;}
.dash-stat .lbl{font-size:.8rem;color:#888;text-transform:uppercase;letter-spacing:.03em;margin-top:2px;}
.donut-card{background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:24px 16px;text-align:center;height:100%;box-shadow:0 1px 3px rgba(0,0,0,.04);}
.donut-card h3{font-size:.95rem;font-weight:600;color:#555;margin:0 0 16px;}
.donut{width:100px;height:100px;border-radius:50%;margin:0 auto 8px;position:relative;display:flex;align-items:center;justify-content:center;}
.donut::after{content:attr(data-pct);font-size:1.1rem;font-weight:700;color:#333;}
.donut-inner{position:absolute;inset:18px;background:#fff;border-radius:50%;}
.chart-card{background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.04);height:100%;}
.chart-card h3{font-size:.95rem;font-weight:600;color:#555;margin:0 0 16px;}
.breadcrumb-dash{font-size:.8rem;color:#999;margin-bottom:4px;}
.breadcrumb-dash a{color:#999;text-decoration:none;}
.page-head{margin-bottom:22px;}
.page-head h2{font-size:1.5rem;font-weight:600;color:#333;margin:0;}
</style>

<div class="page-head">
  <div class="breadcrumb-dash"><a href="#">Home</a> / Dashboard / Overview</div>
  <h2>Dashboard</h2>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico" style="background:#e74c3c;"><i class="fas fa-box"></i></div>
      <div><div class="num"><?php echo number_format($stats['products']); ?></div><div class="lbl">Products</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico" style="background:#e67e22;"><i class="fas fa-shopping-cart"></i></div>
      <div><div class="num"><?php echo number_format($stats['sales_today']); ?></div><div class="lbl">Sales today</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico" style="background:#3498db;"><i class="fas fa-coins"></i></div>
      <div><div class="num">KES <?php echo number_format($stats['profit_today'], 0); ?></div><div class="lbl">Profit today</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico" style="background:#27ae60;"><i class="fas fa-users"></i></div>
      <div><div class="num"><?php echo number_format($stats['customers']); ?></div><div class="lbl">Customers</div></div>
    </div>
  </div>
</div>

<!-- Donut row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Profit margin</h3>
      <div class="donut" data-pct="<?php echo $pctProfit; ?>%" style="background:conic-gradient(#3498db <?php echo $pctProfit; ?>%, #eee 0);"><div class="donut-inner"></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Retail sales</h3>
      <div class="donut" data-pct="<?php echo $pctRetail; ?>%" style="background:conic-gradient(#e74c3c <?php echo $pctRetail; ?>%, #eee 0);"><div class="donut-inner"></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Customers</h3>
      <div class="donut" data-pct="<?php echo $pctCustomers; ?>%" style="background:conic-gradient(#1abc9c <?php echo $pctCustomers; ?>%, #eee 0);"><div class="donut-inner"></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Wholesale</h3>
      <div class="donut" data-pct="<?php echo $pctWholesale; ?>%" style="background:conic-gradient(#f1c40f <?php echo $pctWholesale; ?>%, #eee 0);"><div class="donut-inner"></div></div>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-7">
    <div class="chart-card">
      <h3>Revenue — last 7 days</h3>
      <canvas id="lineChart" height="120"></canvas>
    </div>
  </div>
  <div class="col-12 col-lg-5">
    <div class="chart-card">
      <h3>Payment methods (all time)</h3>
      <canvas id="barChart" height="120"></canvas>
    </div>
  </div>
</div>

<!-- Secondary stats -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
      <div class="card-body p-3">
        <div class="text-muted small">Revenue today</div>
        <div class="h5 fw-bold mb-0">KES <?php echo number_format($stats['revenue_today'], 0); ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
      <div class="card-body p-3">
        <div class="text-muted small">Total revenue</div>
        <div class="h5 fw-bold mb-0">KES <?php echo number_format($stats['revenue_all'], 0); ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
      <div class="card-body p-3">
        <div class="text-muted small">Stock value (cost)</div>
        <div class="h5 fw-bold mb-0">KES <?php echo number_format($stats['stock_value'], 0); ?></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  var labels = <?php echo json_encode($trendLabels); ?>;
  var lineData = <?php echo json_encode($trendData); ?>;
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Revenue (KES)',
        data: lineData,
        borderColor: '#3498db',
        backgroundColor: 'rgba(52,152,219,.1)',
        tension: .35,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#3498db'
      }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: ['Cash', 'M-Pesa', 'Split'],
      datasets: [{
        data: [<?php echo $payCash; ?>, <?php echo $payMpesa; ?>, <?php echo $paySplit; ?>],
        backgroundColor: ['#a8e6cf', '#d5d5d5', '#ffd3b6'],
        borderRadius: 4
      }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
})();
</script>
<?php
$content = ob_get_clean();
$extra_css = '';
include __DIR__ . '/../../templates/tenants/layout.php';
