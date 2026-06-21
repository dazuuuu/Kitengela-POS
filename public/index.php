<?php
// public/index.php — Modern POS · landing page
// A minimal, phone-first landing page for the SaaS POS. No e-commerce, no
// engineering-services content. Drives visitors to registration.

$page_title       = 'Modern POS — run your shop from your phone';
$page_description = 'A modern point-of-sale that lives on your phone. Record sales, track stock, send receipts and manage your team — no hardware, no installs.';
$use_home_navbar  = true;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$REGISTER = '/Modern/public/auth/register.php';
$LOGIN    = '/Modern/public/auth/login.php';

/* Pricing pulled live from subscription_plans when available; falls back to the
   seeded plans so the page never depends on the database being up. */
$plans = [];
try {
    $appBoot = __DIR__ . '/../app/app.php';
    if (is_file($appBoot)) {
        require_once $appBoot;
        $plans = Database::pdo()
            ->query("SELECT * FROM subscription_plans WHERE is_active = 1 AND is_public = 1 ORDER BY id")
            ->fetchAll();
    }
} catch (Throwable $e) {
    $plans = [];
}
if (!$plans) {
    $plans = [
        ['name' => 'Standard', 'description' => 'Everything you need to run your shop',
         'price_weekly' => 250, 'price_biweekly' => 500, 'price_monthly' => 1000,
         'max_staff' => 3, 'max_products' => 200],
    ];
}

$features = [
    ['icon' => 'fa-bolt',            'title' => 'Record sales in seconds', 'desc' => 'Ring up a sale, pick the items, done. Your stock updates itself.'],
    ['icon' => 'fa-boxes-stacked',   'title' => 'Know your stock',         'desc' => 'See what\'s running low before it runs out, with reorder alerts.'],
    ['icon' => 'fa-receipt',         'title' => 'Receipts, instantly',     'desc' => 'Print or email a receipt the moment a sale is finished.'],
    ['icon' => 'fa-user-group',      'title' => 'Keep customers close',    'desc' => 'Save customer details and tell them the moment you restock.'],
    ['icon' => 'fa-user-shield',     'title' => 'Your team, your rules',   'desc' => 'Add staff and choose exactly what each person is allowed to do.'],
    ['icon' => 'fa-chart-line',      'title' => 'See how you\'re doing',   'desc' => 'Daily sales and stock reports, always in your pocket.'],
];

$steps = [
    ['n' => '1', 'title' => 'Create your shop',      'desc' => 'Register and choose a plan that fits how you sell.'],
    ['n' => '2', 'title' => 'Verify it\'s you',       'desc' => 'Confirm with the code we email you — your account stays secure.'],
    ['n' => '3', 'title' => 'Set up &amp; start selling', 'desc' => 'Add your logo and products, then record your first sale.'],
];

function lp_money($v): string { return $v === null ? '—' : 'KES ' . number_format((float)$v); }

ob_start();
?>
<style>
/* ============================================================
   Modern POS landing — scoped (.lp-*). Harmonised with theme.css
   teal/emerald tokens so the kept navbar/footer don't clash.
   ============================================================ */
.lp{ --ink:#0a1413; --teal:var(--brand-primary,#0D9488); --mint:var(--brand-secondary,#2DD4BF);
     --amber:var(--brand-accent,#E8902C); --line:#e2eae8; --muted:#64748b;
     color:var(--ink); overflow-x:clip; }
.lp .lp-wrap{ max-width:1180px; margin:0 auto; padding:0 22px; }
.lp section{ position:relative; }

/* ---------- HERO ---------- */
.lp-hero{ position:relative; color:#fff; overflow:hidden;
  background:radial-gradient(120% 120% at 80% 0%, #0c5a52 0%, #06342f 45%, #041f1c 100%);
  padding:calc(var(--navbar-height,90px) + 64px) 0 90px; }
.lp-hero::before{ content:''; position:absolute; inset:0; opacity:.5; pointer-events:none;
  background:
    radial-gradient(40% 40% at 12% 18%, rgba(45,212,191,.22), transparent 60%),
    radial-gradient(36% 36% at 88% 72%, rgba(232,144,44,.16), transparent 60%); }
.lp-hero-grid{ position:relative; display:grid; grid-template-columns:1.05fr .95fr; gap:54px; align-items:center; }
.lp-eyebrow{ display:inline-flex; align-items:center; gap:9px; padding:7px 15px; border-radius:999px;
  background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16);
  font-size:.72rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--mint); }
.lp-eyebrow .pulse{ width:8px; height:8px; border-radius:50%; background:var(--mint); box-shadow:0 0 0 0 rgba(45,212,191,.6); animation:lpPulse 2.2s infinite; }
@keyframes lpPulse{ 0%{box-shadow:0 0 0 0 rgba(45,212,191,.55);} 70%{box-shadow:0 0 0 9px rgba(45,212,191,0);} 100%{box-shadow:0 0 0 0 rgba(45,212,191,0);} }
.lp-hero h1{ font-family:var(--font-display,'Montserrat',sans-serif); font-weight:900;
  font-size:clamp(2.5rem,5.4vw,4.1rem); line-height:1.03; letter-spacing:-.01em; margin:18px 0 16px; }
.lp-hero h1 .hl{ color:var(--mint); }
.lp-hero p.lead{ color:rgba(255,255,255,.82); font-size:1.08rem; max-width:480px; margin-bottom:28px; }
.lp-cta{ display:flex; flex-wrap:wrap; gap:14px; align-items:center; }
.lp-btn{ display:inline-flex; align-items:center; gap:10px; padding:14px 24px; border-radius:12px;
  font-weight:700; font-size:.96rem; text-decoration:none; transition:transform .18s ease, box-shadow .18s ease, background .18s ease; }
.lp-btn-primary{ background:var(--mint); color:#04201d; box-shadow:0 12px 30px rgba(45,212,191,.32); }
.lp-btn-primary:hover{ transform:translateY(-2px); box-shadow:0 18px 40px rgba(45,212,191,.42); color:#04201d; }
.lp-btn-ghost{ color:#fff; border:1px solid rgba(255,255,255,.28); }
.lp-btn-ghost:hover{ background:rgba(255,255,255,.1); color:#fff; transform:translateY(-2px); }
.lp-trust{ display:flex; flex-wrap:wrap; gap:18px; margin-top:24px; color:rgba(255,255,255,.7); font-size:.82rem; }
.lp-trust span{ display:inline-flex; align-items:center; gap:7px; }
.lp-trust i{ color:var(--mint); }

/* ---------- SIGNATURE: 3D phone ---------- */
.lp-stage{ perspective:1100px; display:flex; justify-content:center; }
.lp-phone-scene{ position:relative; transform-style:preserve-3d; transition:transform .25s cubic-bezier(.2,.7,.2,1); will-change:transform; }
.lp-phone{ position:relative; width:288px; height:592px; border-radius:42px; transform-style:preserve-3d;
  background:linear-gradient(160deg,#1b2a28,#0a1413); padding:13px;
  box-shadow:0 50px 90px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.18), 0 0 0 2px rgba(255,255,255,.05);
  animation:lpFloat 7s ease-in-out infinite; }
@keyframes lpFloat{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-14px); } }
.lp-screen{ position:relative; height:100%; border-radius:30px; overflow:hidden; background:#f1f6f5; display:flex; flex-direction:column; }
.lp-notch{ position:absolute; top:10px; left:50%; transform:translateX(-50%); width:120px; height:24px; background:#0a1413; border-radius:0 0 16px 16px; z-index:5; }
.lp-sbar{ display:flex; justify-content:space-between; align-items:center; padding:12px 18px 6px; font-size:.66rem; font-weight:700; color:#0a1413; }
.lp-sbar i{ margin-left:5px; }
.lp-appbar{ display:flex; align-items:center; gap:10px; padding:8px 16px 12px; }
.lp-appbar .lp-av{ width:32px; height:32px; border-radius:9px; background:linear-gradient(135deg,var(--teal),var(--mint)); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:.8rem; }
.lp-appbar b{ font-size:.92rem; } .lp-appbar small{ display:block; color:#64748b; font-size:.62rem; }
.lp-sale{ margin:4px 14px; background:#fff; border:1px solid #e6efed; border-radius:16px; padding:14px; box-shadow:0 8px 20px rgba(6,52,47,.06); }
.lp-sale h5{ font-size:.64rem; letter-spacing:.12em; text-transform:uppercase; color:#94a3b8; margin:0 0 10px; font-weight:800; }
.lp-row{ display:flex; justify-content:space-between; align-items:center; font-size:.8rem; padding:7px 0; border-bottom:1px dashed #eef3f2; }
.lp-row:last-of-type{ border-bottom:0; }
.lp-row .q{ color:#64748b; font-size:.72rem; }
.lp-total{ display:flex; justify-content:space-between; align-items:baseline; margin-top:10px; padding-top:10px; border-top:2px solid #0a1413; }
.lp-total b{ font-size:1.15rem; } .lp-total span{ font-size:.66rem; color:#64748b; text-transform:uppercase; letter-spacing:.1em; }
.lp-record{ margin:14px; padding:13px; border-radius:13px; text-align:center; font-weight:800; font-size:.85rem; color:#04201d;
  background:linear-gradient(135deg,var(--teal),var(--mint)); box-shadow:0 10px 22px rgba(13,148,136,.3); }
.lp-toast{ margin:0 14px 14px; display:flex; align-items:center; gap:9px; background:#e8f9f3; color:#0a766b; border-radius:11px; padding:10px 12px; font-size:.74rem; font-weight:700; }

/* floating depth chips */
.lp-chip{ position:absolute; display:flex; align-items:center; gap:9px; padding:11px 14px; border-radius:14px;
  background:rgba(255,255,255,.96); color:#0a1413; font-size:.76rem; font-weight:700;
  box-shadow:0 18px 40px rgba(0,0,0,.28); }
.lp-chip i{ width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.7rem; }
.lp-chip small{ display:block; font-weight:500; color:#64748b; font-size:.66rem; }
.lp-chip-1{ top:64px; left:-46px; transform:translateZ(60px); animation:lpFloat 6s ease-in-out infinite; }
.lp-chip-2{ top:248px; right:-58px; transform:translateZ(90px); animation:lpFloat 7.5s ease-in-out .4s infinite; }
.lp-chip-3{ bottom:70px; left:-40px; transform:translateZ(40px); animation:lpFloat 6.8s ease-in-out .8s infinite; }

/* ---------- SECTION SHELL ---------- */
.lp-sec{ padding:84px 0; }
.lp-head{ max-width:620px; margin:0 auto 48px; text-align:center; }
.lp-head .lp-kicker{ font-size:.72rem; font-weight:800; letter-spacing:.18em; text-transform:uppercase; color:var(--teal); }
.lp-head h2{ font-family:var(--font-display,'Montserrat',sans-serif); font-weight:800; font-size:clamp(1.8rem,3.6vw,2.6rem); margin:12px 0 10px; letter-spacing:-.01em; }
.lp-head p{ color:var(--muted); font-size:1.02rem; }

/* ---------- FEATURES ---------- */
.lp-feat-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
.lp-card{ background:#fff; border:1px solid var(--line); border-radius:18px; padding:26px 24px;
  transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease; will-change:transform; }
.lp-card:hover{ transform:translateY(-7px); box-shadow:0 26px 50px rgba(6,52,47,.12); border-color:transparent; }
.lp-card .lp-ic{ width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center;
  background:rgba(13,148,136,.1); color:var(--teal); font-size:1.05rem; margin-bottom:16px; }
.lp-card h3{ font-size:1.06rem; font-weight:800; margin:0 0 7px; }
.lp-card p{ color:var(--muted); font-size:.92rem; margin:0; line-height:1.6; }

/* ---------- HOW IT WORKS (real sequence -> numbered) ---------- */
.lp-steps{ background:var(--color-surface-alt,#f1f6f5); }
.lp-steps-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:20px; counter-reset:step; }
.lp-step{ position:relative; background:#fff; border:1px solid var(--line); border-radius:18px; padding:30px 26px; }
.lp-step .num{ font-family:var(--font-display,'Montserrat',sans-serif); font-weight:900; font-size:2.4rem;
  line-height:1; color:transparent; -webkit-text-stroke:2px var(--mint); margin-bottom:12px; }
.lp-step h3{ font-size:1.08rem; font-weight:800; margin:0 0 7px; }
.lp-step p{ color:var(--muted); font-size:.92rem; margin:0; line-height:1.6; }

/* ---------- PRICING ---------- */
.lp-toggle{ display:inline-flex; background:#eef3f2; border-radius:999px; padding:5px; gap:4px; margin:0 auto 36px; }
.lp-toggle button{ border:0; background:transparent; padding:9px 18px; border-radius:999px; font-weight:700; font-size:.84rem; color:#64748b; cursor:pointer; transition:all .2s ease; }
.lp-toggle button.on{ background:#fff; color:var(--ink); box-shadow:0 4px 12px rgba(6,52,47,.1); }
.lp-price-grid{ display:grid; grid-template-columns:repeat(2,minmax(0,360px)); gap:22px; justify-content:center; }
.lp-price-3{ grid-template-columns:repeat(3,minmax(0,300px)); }
.lp-plan{ position:relative; background:#fff; border:1px solid var(--line); border-radius:22px; padding:32px 30px; display:flex; flex-direction:column; }
.lp-plan.featured{ border-color:var(--teal); box-shadow:0 24px 60px rgba(13,148,136,.16); }
.lp-plan .tag{ position:absolute; top:-12px; right:24px; background:var(--amber); color:#fff; font-size:.64rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; padding:5px 12px; border-radius:999px; }
.lp-plan h3{ font-size:1.2rem; font-weight:800; margin:0 0 4px; }
.lp-plan .sub{ color:var(--muted); font-size:.86rem; margin-bottom:18px; }
.lp-plan .price{ font-family:var(--font-display,'Montserrat',sans-serif); font-weight:900; font-size:2.1rem; line-height:1; }
.lp-plan .price small{ font-size:.8rem; font-weight:600; color:var(--muted); }
.lp-plan ul{ list-style:none; padding:0; margin:20px 0 24px; display:flex; flex-direction:column; gap:11px; }
.lp-plan li{ display:flex; align-items:center; gap:10px; font-size:.9rem; color:#44524f; }
.lp-plan li i{ color:var(--teal); font-size:.78rem; }
.lp-plan .lp-btn{ justify-content:center; margin-top:auto; }
.lp-plan .lp-btn-dark{ background:var(--ink); color:#fff; }
.lp-plan .lp-btn-dark:hover{ background:#06342f; color:#fff; transform:translateY(-2px); }

/* ---------- FINAL CTA ---------- */
.lp-final{ position:relative; overflow:hidden; color:#fff; text-align:center;
  background:radial-gradient(120% 120% at 50% 0%, #0c5a52, #06342f 60%, #041f1c); padding:88px 0; }
.lp-final h2{ font-family:var(--font-display,'Montserrat',sans-serif); font-weight:900; font-size:clamp(1.9rem,4vw,3rem); margin:0 0 14px; }
.lp-final p{ color:rgba(255,255,255,.8); max-width:520px; margin:0 auto 28px; }

/* ---------- reveal ---------- */
.lp-reveal{ opacity:0; transform:translateY(24px); transition:opacity .7s ease, transform .7s ease; }
.lp-reveal.in{ opacity:1; transform:none; }

/* ---------- RESPONSIVE ---------- */
@media (max-width:980px){
  .lp-hero-grid{ grid-template-columns:1fr; gap:48px; text-align:center; }
  .lp-cta, .lp-trust{ justify-content:center; }
  .lp-hero p.lead{ margin-left:auto; margin-right:auto; }
  .lp-feat-grid{ grid-template-columns:repeat(2,1fr); }
  .lp-steps-grid{ grid-template-columns:1fr; }
  .lp-price-3{ grid-template-columns:1fr; max-width:380px; margin:0 auto; }
}
@media (max-width:600px){
  .lp-feat-grid{ grid-template-columns:1fr; }
  .lp-price-grid{ grid-template-columns:1fr; }
  .lp-chip-1{ left:-12px; } .lp-chip-2{ right:-12px; } .lp-chip-3{ left:-8px; }
  .lp-phone{ width:256px; height:526px; }
  .lp-sec{ padding:60px 0; }
}
@media (prefers-reduced-motion: reduce){
  .lp-phone, .lp-chip-1, .lp-chip-2, .lp-chip-3{ animation:none; }
  .lp-reveal{ transition:none; opacity:1; transform:none; }
  .lp-phone-scene{ transition:none; }
}
:focus-visible{ outline:3px solid var(--mint); outline-offset:3px; border-radius:6px; }
</style>

<div class="lp">

  <!-- ===== HERO ===== -->
  <section class="lp-hero">
    <div class="lp-wrap lp-hero-grid">
      <div class="lp-hero-copy">
        <span class="lp-eyebrow"><span class="pulse"></span> Point of sale, in your pocket</span>
        <h1>Run your whole shop <span class="hl">from your phone.</span></h1>
        <p class="lead">Record sales, track stock, send receipts and manage your team — no till, no hardware, no installs. Just your phone.</p>
        <div class="lp-cta">
          <a class="lp-btn lp-btn-primary" href="<?php echo $REGISTER; ?>">Create your shop <i class="fa-solid fa-arrow-right"></i></a>
          <a class="lp-btn lp-btn-ghost" href="<?php echo $LOGIN; ?>">Log in</a>
        </div>
        <div class="lp-trust">
          <span><i class="fa-solid fa-mobile-screen"></i> Works on any phone</span>
          <span><i class="fa-solid fa-shield-halved"></i> Email-verified logins</span>
          <span><i class="fa-solid fa-money-bill-wave"></i> Pay with M-Pesa</span>
        </div>
      </div>

      <!-- signature: tilting 3D phone running the POS -->
      <div class="lp-stage">
        <div class="lp-phone-scene" id="lpScene">
          <div class="lp-phone">
            <div class="lp-notch"></div>
            <div class="lp-screen">
              <div class="lp-sbar"><span>9:41</span><span><i class="fa-solid fa-signal"></i><i class="fa-solid fa-wifi"></i><i class="fa-solid fa-battery-three-quarters"></i></span></div>
              <div class="lp-appbar">
                <span class="lp-av">A</span>
                <div><b>Acme Beddings</b><small>New sale</small></div>
              </div>
              <div class="lp-sale">
                <h5>Items</h5>
                <div class="lp-row"><span>Duvet, King <span class="q">×1</span></span><span>4,500</span></div>
                <div class="lp-row"><span>Bedsheet, Queen <span class="q">×2</span></span><span>4,400</span></div>
                <div class="lp-row"><span>Towel set <span class="q">×1</span></span><span>800</span></div>
                <div class="lp-total"><span>Total</span><b>KES 9,700</b></div>
              </div>
              <div class="lp-record"><i class="fa-solid fa-check"></i> Record sale</div>
              <div class="lp-toast"><i class="fa-solid fa-paper-plane"></i> Receipt emailed to customer</div>
            </div>
          </div>
          <div class="lp-chip lp-chip-1"><i style="background:var(--amber);" class="fa-solid fa-triangle-exclamation"></i><div>Low stock<small>Towels · 4 left</small></div></div>
          <div class="lp-chip lp-chip-2"><i style="background:var(--teal);" class="fa-solid fa-check"></i><div>Sale recorded<small>Stock updated</small></div></div>
          <div class="lp-chip lp-chip-3"><i style="background:#16a34a;" class="fa-solid fa-money-bill-wave"></i><div>M-Pesa<small>Subscription active</small></div></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== FEATURES ===== -->
  <section class="lp-sec">
    <div class="lp-wrap">
      <div class="lp-head lp-reveal">
        <span class="lp-kicker">Everything your counter needs</span>
        <h2>One app for the whole shop</h2>
        <p>The tools you'd expect from a full point-of-sale, sized to fit in your hand.</p>
      </div>
      <div class="lp-feat-grid">
        <?php foreach ($features as $f): ?>
          <article class="lp-card lp-reveal">
            <div class="lp-ic"><i class="fa-solid <?php echo $f['icon']; ?>"></i></div>
            <h3><?php echo $f['title']; ?></h3>
            <p><?php echo $f['desc']; ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== HOW IT WORKS ===== -->
  <section class="lp-sec lp-steps">
    <div class="lp-wrap">
      <div class="lp-head lp-reveal">
        <span class="lp-kicker">Up and running today</span>
        <h2>Three steps to your first sale</h2>
      </div>
      <div class="lp-steps-grid">
        <?php foreach ($steps as $s): ?>
          <div class="lp-step lp-reveal">
            <div class="num"><?php echo $s['n']; ?></div>
            <h3><?php echo $s['title']; ?></h3>
            <p><?php echo $s['desc']; ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== PRICING ===== -->
  <section class="lp-sec">
    <div class="lp-wrap">
      <div class="lp-head lp-reveal">
        <span class="lp-kicker">Simple pricing</span>
        <h2>Pay weekly, every two weeks, or monthly</h2>
        <p>Switch or cancel anytime. Billed by M-Pesa.</p>
      </div>
      <?php
        $pp = $plans[0] ?? null;                 // the single public plan
        $ppId = (int) ($pp['id'] ?? 0);
        $cards = [
          ['key' => 'weekly',   'label' => 'Weekly',        'per' => '/week',     'price' => $pp['price_weekly']   ?? null, 'best' => false],
          ['key' => 'biweekly', 'label' => 'Every 2 weeks', 'per' => '/2 weeks',  'price' => $pp['price_biweekly'] ?? null, 'best' => false],
          ['key' => 'monthly',  'label' => 'Monthly',       'per' => '/month',    'price' => $pp['price_monthly']  ?? null, 'best' => true],
        ];
      ?>
      <div class="lp-price-grid lp-price-3">
        <?php foreach ($cards as $c): if ($c['price'] === null) continue;
          $href = $REGISTER . '?plan_id=' . $ppId . '&interval=' . $c['key']; ?>
          <div class="lp-plan lp-reveal <?php echo $c['best'] ? 'featured' : ''; ?>">
            <?php if ($c['best']): ?><span class="tag">Best value</span><?php endif; ?>
            <h3><?php echo htmlspecialchars($c['label']); ?></h3>
            <div class="sub"><?php echo htmlspecialchars($pp['name'] ?? 'Standard'); ?> plan</div>
            <div class="price"><?php echo lp_money($c['price']); ?><small class="lp-per"><?php echo $c['per']; ?></small></div>
            <ul>
              <li><i class="fa-solid fa-check"></i> <?php echo ($pp['max_staff'] ?? null) === null ? 'Unlimited staff' : ((int)$pp['max_staff'] . ' staff accounts'); ?></li>
              <li><i class="fa-solid fa-check"></i> <?php echo ($pp['max_products'] ?? null) === null ? 'Unlimited products' : ('Up to ' . (int)$pp['max_products'] . ' products'); ?></li>
              <li><i class="fa-solid fa-check"></i> Sales, receipts &amp; reports</li>
              <li><i class="fa-solid fa-check"></i> Low-stock alerts</li>
              <li><i class="fa-solid fa-check"></i> Customer restock notices</li>
            </ul>
            <a class="lp-btn <?php echo $c['best'] ? 'lp-btn-primary' : 'lp-btn-dark'; ?>" href="<?php echo $href; ?>">Start <?php echo htmlspecialchars(strtolower($c['label'])); ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== FINAL CTA ===== -->
  <section class="lp-final">
    <div class="lp-wrap lp-reveal">
      <h2>Your shop, in your pocket.</h2>
      <p>Create your account in a minute and record your first sale today.</p>
      <a class="lp-btn lp-btn-primary" href="<?php echo $REGISTER; ?>">Create your shop <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </section>

</div>

<script>
(function(){
  'use strict';
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Pointer-tilt the phone scene (desktop, fine pointers only) — the signature moment. */
  var scene = document.getElementById('lpScene');
  var finePointer = window.matchMedia && window.matchMedia('(pointer:fine)').matches;
  if (scene && finePointer && !reduce){
    var hero = scene.closest('.lp-hero'); var raf = null, tx = 0, ty = 0;
    hero.addEventListener('mousemove', function(e){
      var r = hero.getBoundingClientRect();
      var px = (e.clientX - r.left) / r.width - 0.5;
      var py = (e.clientY - r.top) / r.height - 0.5;
      tx = px * 16; ty = py * -16;
      if (!raf) raf = requestAnimationFrame(apply);
    });
    hero.addEventListener('mouseleave', function(){ tx = 0; ty = 0; if(!raf) raf = requestAnimationFrame(apply); });
    function apply(){ raf = null; scene.style.transform = 'rotateY(' + tx + 'deg) rotateX(' + ty + 'deg)'; }
  }

  /* Scroll reveal */
  if ('IntersectionObserver' in window){
    var io = new IntersectionObserver(function(es){
      es.forEach(function(en){ if(en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold:0.14 });
    document.querySelectorAll('.lp-reveal').forEach(function(el){ io.observe(el); });
  } else {
    document.querySelectorAll('.lp-reveal').forEach(function(el){ el.classList.add('in'); });
  }
})();
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/templates/public/layout.php';