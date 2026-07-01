<?php
// public/components/tenants/sidebar.php
// Tenant (shop owner) sidebar. Capability-gated POS navigation with the tenant's
// own business name + logo. Expects $__tenant (array|null) and a booted TenantContext.
// NOTE: this is the TENANT sidebar; the staff version will be duplicated from it later.

$__tenant   = $__tenant ?? null;
$shopName   = $__tenant['name'] ?? 'My Shop';
$logo = '/Kitale/public/assets/images/logo/logo.png';
$username   = $_SESSION['username'] ?? 'User';
$roleLabel  = TenantContext::role() === 'tenant_owner' ? 'Owner' : 'Staff';
$uri        = $_SERVER['REQUEST_URI'] ?? '';
$dashUrl    = TenantContext::role() === 'staff' ? '/Kitale/public/staff/dashboard/' : '/Kitale/public/super/dashboard/';

$isOn = function (string $needle) use ($uri): string {
    return strpos($uri, $needle) !== false ? 'active' : '';
};
?>
<button class="t-sidebar-toggle" id="tSidebarToggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
<div class="t-sidebar-overlay" id="tSidebarOverlay"></div>

<aside class="t-sidebar" id="tSidebar">
    <div class="t-brand">
        <button class="t-close" id="tSidebarClose" aria-label="Close"><i class="fas fa-times"></i></button>
        <img class="t-logo" src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($shopName); ?>">
        <div class="t-shop"><?php echo htmlspecialchars($shopName); ?></div>
        <div class="t-user">
            <?php echo htmlspecialchars($username); ?>
            <span class="t-role"><?php echo htmlspecialchars($roleLabel); ?></span>
        </div>
    </div>

    <nav class="t-nav">
        <a class="t-link <?php echo $isOn('/dashboard'); ?>" href="<?php echo $dashUrl; ?>">
            <i class="fas fa-gauge-high"></i><span>Dashboard</span>
        </a>

        <?php if (TenantContext::role() === 'staff' && TenantContext::can(Capabilities::SALES_VIEW)): ?>
        <a class="t-link <?php echo $isOn('/staff/sales/'); ?>" href="/Kitale/public/staff/sales/"><i class="fas fa-receipt"></i><span>My sales</span></a>
        <?php endif; ?>
        <?php if (TenantContext::role() === 'tenant_owner'): ?>
        <a class="t-link <?php echo $isOn('/super/sales'); ?>" href="/Kitale/public/super/sales/"><i class="fas fa-receipt"></i><span>Sales</span></a>
        <?php endif; ?>

       

        <?php if (TenantContext::can(Capabilities::BRANCHES_MANAGE)): ?>
        <a class="t-link <?php echo $isOn('/super/branches'); ?>" href="/Kitale/public/super/branches/">
            <i class="fas fa-code-branch"></i><span>Branches</span>
        </a>
        <?php endif; ?>

        <?php if (TenantContext::can(Capabilities::STAFF_MANAGE)): ?>
        <a class="t-link <?php echo $isOn('/super/staff'); ?>" href="/Kitale/public/super/staff/">
            <i class="fas fa-user-gear"></i><span>Staff</span>
        </a>
        <?php endif; ?>

        <?php if (TenantContext::can(Capabilities::INVENTORY_EDIT)): ?>
        <a class="t-link <?php echo $isOn('/super/categories'); ?><?php echo $isOn('/super/subcategories'); ?>" href="/Kitale/public/super/categories/">
            <i class="fas fa-tags"></i><span>Categories</span>
        </a>
        <a class="t-link <?php echo $isOn('/super/inventory'); ?>" href="/Kitale/public/super/inventory/">
            <i class="fas fa-warehouse"></i><span>Inventory</span>
        </a>
        <a class="t-link <?php echo $isOn('/super/products'); ?>" href="/Kitale/public/super/products/">
            <i class="fas fa-box"></i><span>Products</span>
        </a>
        <?php elseif (TenantContext::can(Capabilities::INVENTORY_VIEW)): ?>
        <a class="t-link <?php echo $isOn('/super/inventory'); ?>" href="/Kitale/public/super/inventory/">
            <i class="fas fa-warehouse"></i><span>Inventory</span>
        </a>
        <?php endif; ?>

        <?php if (TenantContext::can(Capabilities::REPORTS_VIEW)): ?>
        <a class="t-link <?php echo $isOn('/super/reports'); ?>" href="/Kitale/public/super/reports/"><i class="fas fa-chart-line"></i><span>Reports</span></a>
        <?php endif; ?>


        <hr>

   
        <a class="t-link t-danger" href="/Kitale/public/auth/logout.php">
            <i class="fas fa-arrow-right-from-bracket"></i><span>Logout</span>
        </a>
    </nav>
</aside>

<style>
:root { --t-bg:#0f172a; --t-bg2:#1e293b; --t-line:#334155; --t-accent:#2563eb; --t-text:#cbd5e1; }
.t-sidebar { width:264px; background:var(--t-bg); color:var(--t-text); position:fixed; left:0; top:0; height:100vh; overflow-y:auto; z-index:1001; transition:transform .3s ease; }
.t-brand { padding:24px 20px; border-bottom:1px solid var(--t-line); text-align:center; position:relative; }
.t-logo { height:44px; max-width:160px; object-fit:contain; background:#fff; border-radius:8px; padding:6px; }
.t-shop { margin-top:12px; font-weight:700; color:#fff; font-size:1.05rem; }
.t-user { margin-top:6px; font-size:.8rem; color:#94a3b8; }
.t-role { display:inline-block; margin-left:6px; padding:1px 8px; border-radius:999px; background:var(--t-bg2); color:#cbd5e1; font-size:.7rem; }
.t-nav { padding:14px 12px; }
.t-link { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:8px; color:var(--t-text); text-decoration:none; font-size:.9rem; margin-bottom:3px; }
.t-link i { width:20px; color:#94a3b8; }
.t-link:hover, .t-link.active { background:var(--t-bg2); color:#fff; }
.t-link:hover i, .t-link.active i { color:#fff; }
.t-link.active { box-shadow:inset 3px 0 0 var(--t-accent); }
.t-soon { margin-left:auto; font-size:.62rem; font-style:normal; background:#334155; color:#94a3b8; padding:1px 7px; border-radius:999px; }
.t-danger { color:#f87171; }
.t-danger:hover { background:#7f1d1d; color:#fff; }
.t-nav hr { border:0; border-top:1px solid var(--t-line); margin:12px 0; }
.t-sidebar-toggle, .t-close { display:none; }
.t-sidebar-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; opacity:0; display:none; transition:opacity .3s; }
.t-sidebar-overlay.active { display:block; opacity:1; }
@media (max-width:992px){
  .t-sidebar { transform:translateX(-100%); }
  .t-sidebar.active { transform:translateX(0); }
  .t-sidebar-toggle { display:flex; align-items:center; justify-content:center; position:fixed; top:12px; left:12px; z-index:1002; width:44px; height:44px; border:0; border-radius:8px; background:var(--t-bg); color:#fff; font-size:20px; box-shadow:0 2px 10px rgba(0,0,0,.2); }
  .t-close { display:flex; align-items:center; justify-content:center; position:absolute; top:12px; right:12px; width:30px; height:30px; border:0; border-radius:6px; background:rgba(255,255,255,.1); color:#fff; }
}
</style>
<script>
(function(){
  var tg=document.getElementById('tSidebarToggle'),sb=document.getElementById('tSidebar'),
      ov=document.getElementById('tSidebarOverlay'),cl=document.getElementById('tSidebarClose');
  function open(){sb&&sb.classList.add('active');ov&&ov.classList.add('active');document.body.style.overflow='hidden';}
  function close(){sb&&sb.classList.remove('active');ov&&ov.classList.remove('active');document.body.style.overflow='';}
  tg&&tg.addEventListener('click',open); cl&&cl.addEventListener('click',close); ov&&ov.addEventListener('click',close);
  document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
  document.querySelectorAll('[data-soon]').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();});});
})();
</script>