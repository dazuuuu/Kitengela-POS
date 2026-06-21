<?php
// public/super/dashboard/index.php
require_once __DIR__ . '/../../../app/app.php';
PageGuard::tenant();

$pdo = Database::pdo();
$__tenant = (new Models\TenantModel($pdo))->find(TenantContext::tenantId());
$sub = (new Models\SubscriptionModel($pdo))->forTenant(TenantContext::tenantId());

$firstLogin = !empty($_SESSION['just_activated']) || !empty($_SESSION['first_login']);
unset($_SESSION['first_login']);

$page_title = 'Dashboard';
$shop = $__tenant['name'] ?? 'your shop';
$renews = !empty($sub['current_period_end']) ? date('j M Y', strtotime($sub['current_period_end'])) : '—';

ob_start();
?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
  <div class="card-body p-4">
    <h2 class="h4 mb-1">Welcome to <?php echo htmlspecialchars($shop); ?> 👋</h2>
    <p class="text-muted mb-0">
      Your account is active. This is your control centre — from here you'll manage stock,
      record sales, register customers and your staff, and view reports. We're setting those
      modules up next; for now your subscription is live and your business profile is ready to fill in.
    </p>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
      <div class="card-body">
        <div class="text-muted small text-uppercase">Subscription</div>
        <div class="h5 mb-0 mt-1"><?php echo htmlspecialchars(ucfirst($sub['status'] ?? 'none')); ?></div>
        <div class="text-muted small mt-1">Renews: <?php echo htmlspecialchars($renews); ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
      <div class="card-body">
        <div class="text-muted small text-uppercase">Next step</div>
        <div class="h6 mb-2 mt-1">Set up your business</div>
        <a class="btn btn-sm btn-primary" href="/Modern/public/super/profile/">Open Business Profile</a>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
      <div class="card-body">
        <div class="text-muted small text-uppercase">Coming soon</div>
        <div class="text-muted small mt-1">Inventory, Sales, Customers, Staff & Reports.</div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/tenants/layout.php';