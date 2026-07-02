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
:root{
  --dash-navy:#0f172a;--dash-teal:#0f766e;--dash-surface:#fff;
  --dash-bg:#f4f6f8;--dash-border:#e2e8f0;--dash-muted:#64748b;
}
.dash-hero{background:var(--dash-navy);color:#fff;border-radius:16px;padding:24px 28px;margin-bottom:24px;
  box-shadow:0 4px 6px rgba(15,23,42,.06),0 20px 40px rgba(15,23,42,.15);position:relative;overflow:hidden;}
.dash-hero::after{content:'';position:absolute;right:-20px;top:-20px;width:120px;height:120px;
  border:16px solid rgba(255,255,255,.06);border-radius:16px;transform:rotate(8deg);}
.dash-hero h2{font-size:1.5rem;font-weight:800;margin:0 0 4px;position:relative;z-index:1;}
.dash-hero p{color:#94a3b8;margin:0;font-size:.9rem;position:relative;z-index:1;}
.dash-hero .dash-date{color:#64748b;font-size:.8rem;margin-top:10px;position:relative;z-index:1;}

.dash-stat{display:flex;align-items:center;gap:16px;padding:20px 22px;border-radius:14px;
  background:var(--dash-surface);border:1px solid var(--dash-border);height:100%;
  box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.06);
  transition:transform .2s ease,box-shadow .2s ease;}
.dash-stat:hover{transform:translateY(-4px);box-shadow:0 4px 8px rgba(15,23,42,.06),0 16px 32px rgba(15,23,42,.1);}
.dash-stat .ico{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;
  justify-content:center;color:#fff;font-size:1.25rem;flex-shrink:0;
  box-shadow:0 4px 10px rgba(15,23,42,.15);}
.dash-stat .ico.teal{background:var(--dash-teal);}
.dash-stat .ico.navy{background:var(--dash-navy);}
.dash-stat .ico.blue{background:#1e40af;}
.dash-stat .ico.slate{background:#334155;}
.dash-stat .num{font-size:1.55rem;font-weight:800;line-height:1.1;color:var(--dash-navy);}
.dash-stat .lbl{font-size:.75rem;color:var(--dash-muted);text-transform:uppercase;letter-spacing:.04em;margin-top:3px;font-weight:600;}

.donut-card,.chart-card,.dash-tile{background:var(--dash-surface);border:1px solid var(--dash-border);
  border-radius:14px;padding:22px 18px;height:100%;
  box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.06);
  transition:transform .2s ease,box-shadow .2s ease;}
.donut-card:hover,.chart-card:hover,.dash-tile:hover{
  transform:translateY(-3px);box-shadow:0 4px 8px rgba(15,23,42,.06),0 14px 28px rgba(15,23,42,.09);}
.donut-card h3,.chart-card h3{font-size:.88rem;font-weight:700;color:var(--dash-muted);
  text-transform:uppercase;letter-spacing:.04em;margin:0 0 16px;}
.donut{width:110px;height:110px;border-radius:50%;margin:0 auto 10px;position:relative;}
.donut-ring{position:absolute;inset:0;border-radius:50%;}
.donut-inner{position:absolute;inset:14px;background:#fff;border-radius:50%;display:flex;
  align-items:center;justify-content:center;box-shadow:inset 0 0 0 1px var(--dash-border);}
.donut-pct{font-size:1.2rem;font-weight:800;color:var(--dash-navy);line-height:1;}
.donut-sub{font-size:.72rem;color:#94a3b8;margin-top:4px;}
.dash-tile .tile-lbl{font-size:.78rem;color:var(--dash-muted);text-transform:uppercase;letter-spacing:.03em;font-weight:600;}
.dash-tile .tile-val{font-size:1.35rem;font-weight:800;color:var(--dash-navy);margin-top:4px;}
</style>

<div class="dash-hero">
  <h2>Dashboard</h2>
  <p>Overview for <?php echo htmlspecialchars($shop); ?></p>
  <div class="dash-date"><?php echo date('l, j F Y'); ?></div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico navy"><i class="fas fa-box"></i></div>
      <div><div class="num"><?php echo number_format($stats['products']); ?></div><div class="lbl">Products</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico teal"><i class="fas fa-shopping-cart"></i></div>
      <div><div class="num"><?php echo number_format($stats['sales_today']); ?></div><div class="lbl">Sales today</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico blue"><i class="fas fa-coins"></i></div>
      <div><div class="num">KES <?php echo number_format($stats['profit_today'], 0); ?></div><div class="lbl">Profit today</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="dash-stat">
      <div class="ico slate"><i class="fas fa-users"></i></div>
      <div><div class="num"><?php echo number_format($stats['customers']); ?></div><div class="lbl">Customers</div></div>
    </div>
  </div>
</div>

<!-- Donut row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Profit margin</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(var(--dash-teal) <?php echo $pctProfit; ?>%, #e2e8f0 0);"></div>
        <div class="donut-inner"><span class="donut-pct"><?php echo $pctProfit; ?>%</span></div>
      </div>
      <div class="donut-sub">KES <?php echo number_format($stats['profit_all'], 0); ?> profit</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Retail sales</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(#1e40af <?php echo $pctRetail; ?>%, #e2e8f0 0);"></div>
        <div class="donut-inner"><span class="donut-pct"><?php echo $pctRetail; ?>%</span></div>
      </div>
      <div class="donut-sub"><?php echo number_format($stats['retail_sales']); ?> of <?php echo number_format($totalSales); ?> sales</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Customers</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(var(--dash-navy) <?php echo $pctCustomers; ?>%, #e2e8f0 0);"></div>
        <div class="donut-inner"><span class="donut-pct"><?php echo $pctCustomers; ?>%</span></div>
      </div>
      <div class="donut-sub"><?php echo number_format($stats['customers']); ?> unique · <?php echo number_format($stats['sales_with_customer'] ?? 0); ?> tagged sales</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="donut-card">
      <h3>Wholesale</h3>
      <div class="donut">
        <div class="donut-ring" style="background:conic-gradient(#334155 <?php echo $pctWholesale; ?>%, #e2e8f0 0);"></div>
        <div class="donut-inner"><span class="donut-pct"><?php echo $pctWholesale; ?>%</span></div>
      </div>
      <div class="donut-sub"><?php echo number_format($stats['wholesale_sales']); ?> of <?php echo number_format($totalSales); ?> sales</div>
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
    <div class="dash-tile">
      <div class="tile-lbl">Revenue today</div>
      <div class="tile-val">KES <?php echo number_format($stats['revenue_today'], 0); ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="dash-tile">
      <div class="tile-lbl">Total revenue</div>
      <div class="tile-val">KES <?php echo number_format($stats['revenue_all'], 0); ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="dash-tile">
      <div class="tile-lbl">Stock value (cost)</div>
      <div class="tile-val">KES <?php echo number_format($stats['stock_value'], 0); ?></div>
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
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15,118,110,.08)',
        tension: .35,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#0f766e'
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
        backgroundColor: ['#0f766e', '#0f172a', '#64748b'],
        borderRadius: 6
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
