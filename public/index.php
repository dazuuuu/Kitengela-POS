<?php
// public/index.php — Rongai POS · login portal (installable PWA)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../app/helpers/Branding.php';

$LOGIN    = '/Rongai/public/auth/login.php';
$appLogo  = Branding::DEFAULT_LOGO;
$loggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['otp_verified']);
$role     = $_SESSION['role'] ?? '';
$dashUrl  = $role === 'staff' ? '/Rongai/public/staff/dashboard/' : '/Rongai/public/super/dashboard/';
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Rongai POS — Sign in</title>
<meta name="description" content="Rongai POS — record sales, track stock, print receipts.">
<?php include __DIR__ . '/components/pwa_head.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/Rongai/public/assets/css/pos-portal.css">
</head>
<body class="pos-portal">
  <div class="pos-portal-bg" aria-hidden="true"></div>

  <div class="pos-portal-shell">
    <aside class="pos-brand-panel">
      <div class="pos-brand-logo">
        <img src="<?php echo $h($appLogo); ?>" alt="Rongai POS" width="64" height="64"
             onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'fa-solid fa-cash-register\' style=\'font-size:1.6rem;color:#5eead4;line-height:60px;text-align:center;width:100%\'></i>'">
      </div>
      <h1 class="pos-brand-title">Rongai POS</h1>
      <p class="pos-brand-tag">Commercial point of sale for retail &amp; wholesale</p>
      <ul class="pos-feature-list">
        <li><i class="fa-solid fa-cash-register"></i> Fast checkout &amp; receipt printing</li>
        <li><i class="fa-solid fa-boxes-stacked"></i> Real-time stock &amp; inventory</li>
        <li><i class="fa-solid fa-chart-line"></i> Sales reports &amp; profit tracking</li>
        <li><i class="fa-solid fa-mobile-screen"></i> Works on phone, tablet &amp; desktop</li>
      </ul>
    </aside>

    <div class="pos-stage" id="stage">
      <div class="pos-card-wrap" id="cardWrap">
        <div class="pos-card-shadow"></div>
        <div class="pos-card">
          <div class="pos-card-top"></div>
          <div class="pos-card-body">
            <div class="pos-mobile-brand">
              <div class="pos-brand-logo">
                <img src="<?php echo $h($appLogo); ?>" alt="Rongai POS" width="64" height="64"
                     onerror="this.style.display='none'">
              </div>
              <h1>Rongai POS</h1>
              <p>Run your shop from your phone</p>
            </div>

            <?php if ($loggedIn): ?>
              <div class="pos-lede">You're signed in</div>
              <a class="pos-portal-btn owner" href="<?php echo $h($dashUrl); ?>">
                <span class="ic"><i class="fa-solid fa-gauge-high"></i></span>
                <span class="tx"><b>Open the POS</b><span>Continue to your dashboard</span></span>
                <span class="go"><i class="fa-solid fa-arrow-right"></i></span>
              </a>
              <a class="pos-portal-btn staff" href="/Rongai/public/auth/logout.php">
                <span class="ic"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                <span class="tx"><b>Switch account</b><span>Log out and sign in as someone else</span></span>
                <span class="go"><i class="fa-solid fa-arrow-right"></i></span>
              </a>
            <?php else: ?>
              <div class="pos-lede">Sign in to continue</div>
              <a class="pos-portal-btn owner" href="<?php echo $h($LOGIN); ?>?as=owner">
                <span class="ic"><i class="fa-solid fa-user-shield"></i></span>
                <span class="tx"><b>Owner / Manager</b><span>Sales, stock, staff &amp; reports</span></span>
                <span class="go"><i class="fa-solid fa-arrow-right"></i></span>
              </a>
              <a class="pos-portal-btn staff" href="<?php echo $h($LOGIN); ?>?as=staff">
                <span class="ic"><i class="fa-solid fa-cash-register"></i></span>
                <span class="tx"><b>Staff / Cashier</b><span>Make sales &amp; print receipts</span></span>
                <span class="go"><i class="fa-solid fa-arrow-right"></i></span>
              </a>
            <?php endif; ?>

            <button class="pos-install-btn" id="installBtn" type="button">
              <i class="fa-solid fa-circle-down"></i> Install app
            </button>
            <p class="pos-foot">Rongai POS &middot; secure commercial POS</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <p class="pos-hint" id="iosHint" style="display:none;position:relative;z-index:2;padding:0 24px 24px;">
    To install: tap <b>Share</b> <i class="fa-solid fa-arrow-up-from-bracket"></i> then <b>Add to Home Screen</b>.
  </p>

<script>
(function(){
  var wrap = document.getElementById('cardWrap');
  if (wrap) {
    document.addEventListener('mousemove', function(e) {
      var r = wrap.getBoundingClientRect();
      var dx = (e.clientX - (r.left + r.width / 2)) / r.width;
      var dy = (e.clientY - (r.top + r.height / 2)) / r.height;
      wrap.style.transform = 'rotateX(' + (dy * -8) + 'deg) rotateY(' + (dx * 8) + 'deg)';
    });
    document.addEventListener('mouseleave', function() {
      wrap.style.transform = '';
    });
  }

  var deferred = null, btn = document.getElementById('installBtn');
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault(); deferred = e;
    if (btn && !standalone) btn.style.display = 'flex';
  });
  if (btn) btn.addEventListener('click', function() {
    if (!deferred) return;
    deferred.prompt();
    deferred.userChoice.finally(function() { deferred = null; btn.style.display = 'none'; });
  });
  window.addEventListener('appinstalled', function() { if (btn) btn.style.display = 'none'; });

  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
  var isSafari = /^((?!chrome|crios|fxios).)*safari/i.test(navigator.userAgent);
  if (isIOS && isSafari && !standalone) {
    var h = document.getElementById('iosHint');
    if (h) h.style.display = 'block';
  }
})();
</script>
</body>
</html>
