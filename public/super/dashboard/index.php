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

$totalSales = $stats['retail_sales'] + $stats['wholesale_sales'];
$pctRetail = $totalSales > 0 ? round($stats['retail_sales'] / $totalSales * 100) : 0;
$pctWholesale = $totalSales > 0 ? 100 - $pctRetail : 0;
$rev = $stats['revenue_all'];
$pctProfit = $rev > 0 ? min(100, max(0, round($stats['profit_all'] / $rev * 100))) : 0;
$pctCustomers = (int) ($stats['customer_rate'] ?? 0);

$payCash = $stats['payment_split']['cash'] ?? 0;
$payMpesa = $stats['payment_split']['mpesa'] ?? 0;
$paySplit = $stats['payment_split']['split'] ?? 0;

ob_start();
?>
<style>
/* Admin-style dashboard — matches reference card layout (dashboard content only) */
.dash-crumb{font-size:.8rem;color:#999;margin-bottom:6px;}
.dash-crumb a{color:#999;text-decoration:none;}
.dash-crumb a:hover{color:#666;}
.dash-title{font-size:1.6rem;font-weight:600;color:#333;margin:0 0 22px;}

/* Row 1 — split icon + data cards */
.mini-stat{display:flex;background:#fff;border-radius:4px;overflow:hidden;height:100%;
  box-shadow:0 1px 3px rgba(0,0,0,.08);}
.mini-stat-ico{width:88px;min-height:96px;display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:1.75rem;flex-shrink:0;}
.mini-stat-ico.red{background:#e74c3c;}
.mini-stat-ico.orange{background:#f39c12;}
.mini-stat-ico.blue{background:#3498db;}
.mini-stat-ico.green{background:#2ecc71;}
.mini-stat-body{flex:1;padding:20px 18px;display:flex;flex-direction:column;justify-content:center;}
.mini-stat-num{font-size:1.75rem;font-weight:700;color:#333;line-height:1.1;}
.mini-stat-lbl{font-size:.85rem;color:#999;margin-top:4px;}

/* Row 2 — donut progress cards */
.donut-card{background:#fff;border-radius:4px;padding:24px 16px 28px;text-align:center;height:100%;
  box-shadow:0 1px 3px rgba(0,0,0,.08);}
.donut-card h3{font-size:.95rem;font-weight:600;color:#555;margin:0 0 18px;}
.donut{width:110px;height:110px;border-radius:50%;margin:0 auto;position:relative;}
.donut-ring{position:absolute;inset:0;border-radius:50%;}
.donut-inner{position:absolute;inset:14px;background:#fff;border-radius:50%;
  display:flex;align-items:center;justify-content:center;}
.donut-pct{font-size:1.25rem;font-weight:700;line-height:1;}
.donut-pct.blue{color:#3498db;}
.donut-pct.red{color:#e74c3c;}
.donut-pct.green{color:#1abc9c;}
.donut-pct.orange{color:#f39c12;}

/* Row 3 — chart cards */
.chart-card{background:#fff;border-radius:4px;padding:20px;height:100%;
  box-shadow:0 1px 3px rgba(0,0,0,.08);}
.chart-card h3{font-size:.95rem;font-weight:600;color:#555;margin:0 0 16px;}
</style>

<div class="dash-crumb"><a href="/Rongai/public/super/dashboard/">Home</a> / Dashboard / Overview</div>
<h2 class="dash-title">Dashboard</h2>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="mini-stat">
      <div class="mini-stat-ico red"><i class="fas fa-box"></i></div>
      <div class="mini-stat-body">
        <div class="mini-stat-num"><?php echo number_format($stats['products']); ?></div>
        <div class="mini-stat-lbl">Products</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="mini-stat">
      <div class="mini-stat-ico orange"><i class="fas fa-shopping-cart"></i></div>
      <div class="mini-stat-body">
        <div class="mini-stat-num"><?php echo number_format($stats['sales_today']); ?></div>
        <div class="mini-stat-lbl">Sales today</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="mini-stat">
      <div class="mini-stat-ico blue"><i class="fas fa-coins"></i></div>
      <div class="mini-stat-body">
        <div class="mini-stat-num">KES <?php echo number_format($stats['profit_today'], 0); ?></div>
        <div class="mini-stat-lbl">Profit today</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="mini-stat">
      <div class="mini-stat-ico green"><i class="fas fa-users"></i></div>
      <div class="mini-stat-body">
        <div class="mini-stat-num"><?php echo number_format($stats['customers']); ?></div>
        <div class="mini-stat-lbl">Customers</div>
      </div>
    </div>
  </div>
</div>

<!-- Donut row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Profit margin</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(#3498db <?php echo $pctProfit; ?>%, #eee 0);"></div>
        <div class="donut-inner"><span class="donut-pct blue"><?php echo $pctProfit; ?>%</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Retail sales</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(#e74c3c <?php echo $pctRetail; ?>%, #eee 0);"></div>
        <div class="donut-inner"><span class="donut-pct red"><?php echo $pctRetail; ?>%</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Customers</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(#1abc9c <?php echo $pctCustomers; ?>%, #eee 0);"></div>
        <div class="donut-inner"><span class="donut-pct green"><?php echo $pctCustomers; ?>%</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Wholesale</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(#f39c12 <?php echo $pctWholesale; ?>%, #eee 0);"></div>
        <div class="donut-inner"><span class="donut-pct orange"><?php echo $pctWholesale; ?>%</span></div>
      </div>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="row g-3">
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
        backgroundColor: 'rgba(52,152,219,.12)',
        tension: .35,
        fill: true,
        pointRadius: 5,
        pointBackgroundColor: '#3498db',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
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
        backgroundColor: ['#bdc3c7', '#2ecc71', '#bdc3c7'],
        borderRadius: 2
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
