<?php
// public/auth/register.php
// Card-driven, pay-to-activate registration.
//   - plan_id + interval arrive from the landing pricing card (pre-filled, not re-chosen)
//   - owner enters business name, email, password, phone
//   - on submit: create the account, push an M-Pesa prompt to their phone
//   - the waiting screen polls billing/status.php; payment activates + logs in -> dashboard
require_once __DIR__ . '/../../app/app.php';

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['otp_verified'])) {
    header('Location: /Modern/public/super/dashboard/'); exit;
}

$pdo  = Database::pdo();
$mpesaCfg = is_file(ROOT_PATH . '/app/config/mpesa.php') ? require ROOT_PATH . '/app/config/mpesa.php' : [];
$bill = new BillingService($pdo, new MpesaService($mpesaCfg));

$intervalLabels = ['weekly' => 'Weekly', 'biweekly' => 'Every 2 weeks', 'monthly' => 'Monthly'];

$action   = $_POST['action'] ?? '';
$planId   = (int) ($_POST['plan_id'] ?? $_GET['plan_id'] ?? 0);
$interval = $_POST['interval'] ?? $_GET['interval'] ?? '';

/* Resolve the chosen plan + price. */
$plan = null; $amount = null;
if ($planId) {
    $s = $pdo->prepare('SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1');
    $s->execute([$planId]);
    $plan = $s->fetch() ?: null;
}
if ($plan && Billing::isValidInterval($interval)) {
    $amount = Billing::planAmount($plan, $interval);
}

$view = 'form';            // form | waiting | error
$errors = [];
$checkout = '';
$payError = '';
$old = ['business_name' => '', 'email' => '', 'phone' => ''];

/* ---- retry payment for an already-created account ---- */
if ($action === 'retry' && !empty($_SESSION['reg_pending'])) {
    $rp = $_SESSION['reg_pending'];
    $init = $bill->initiate($rp);
    if ($init['ok']) { $view = 'waiting'; $checkout = $init['checkout_request_id']; $old['phone'] = $rp['phone']; $amount = $rp['amount']; }
    else { $view = 'error'; $payError = $init['error']; }
}

/* ---- new registration ---- */
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $old = [
        'business_name' => trim($_POST['business_name'] ?? ''),
        'email'         => trim($_POST['email'] ?? ''),
        'phone'         => trim($_POST['phone'] ?? ''),
    ];
    if (!$plan || $amount === null) {
        $errors['_'] = 'Please choose a plan first.';
    } elseif (empty($old['phone']) || (new MpesaService($mpesaCfg))->normalizePhone($old['phone']) === null) {
        $errors['phone'] = 'Enter the M-Pesa phone number to bill (e.g. 0712 345 678).';
    }

    if (!$errors) {
        $reg = new RegistrationService($pdo);
        $result = $reg->register([
            'business_name' => $old['business_name'],
            'email'         => $old['email'],
            'password'      => $_POST['password'] ?? '',
            'phone'         => $old['phone'],
            'plan_id'       => $planId,
            'interval'      => $interval,
        ], function () {}); // no activation email — payment activates the account

        if (!$result['ok']) {
            $errors = $result['errors'];
        } else {
            $ctx = [
                'tenant_id'       => $result['tenant_id'],
                'user_id'         => $result['user_id'],
                'subscription_id' => $result['subscription_id'],
                'plan_id'         => $planId,
                'interval'        => $interval,
                'amount'          => $amount,
                'phone'           => $old['phone'],
                'account_ref'     => 'SUB' . $result['tenant_id'],
                'desc'            => 'Modern POS ' . ($plan['name'] ?? '') . ' ' . $interval,
            ];
            $_SESSION['reg_pending'] = $ctx;
            $init = $bill->initiate($ctx);
            if ($init['ok']) { $view = 'waiting'; $checkout = $init['checkout_request_id']; }
            else { $view = 'error'; $payError = $init['error']; }
        }
    }
}

$page_title = 'Create your shop';
$intLabel = $intervalLabels[$interval] ?? '';

ob_start();

if ($view === 'waiting'):
    $digits = preg_replace('/\D/', '', $old['phone'] ?: ($_SESSION['reg_pending']['phone'] ?? ''));
    $maskPhone = strlen($digits) >= 6 ? substr($digits, 0, 4) . str_repeat('*', max(0, strlen($digits) - 6)) . substr($digits, -2) : $digits;
?>
    <div class="auth-title">Check your phone</div>
    <div class="auth-sub">We sent an M-Pesa request to <strong><?php echo htmlspecialchars($maskPhone); ?></strong>.
        Enter your PIN to pay <strong>KES <?php echo number_format((float)($amount ?? 0), 0); ?></strong> and activate your shop.</div>
    <div id="payWait" style="text-align:center;padding:14px 0;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:1.6rem;color:#2563eb;"></i>
        <div class="text-muted small mt-2" id="payMsg">Waiting for payment…</div>
    </div>
    <form method="post" id="retryForm" style="display:none;">
        <input type="hidden" name="action" value="retry">
        <button class="btn-auth">Resend the prompt</button>
    </form>
    <script>
    (function(){
        var checkout = <?php echo json_encode($checkout); ?>;
        var msg = document.getElementById('payMsg'), retry = document.getElementById('retryForm');
        var tries = 0, max = 40;
        var t = setInterval(function(){
            tries++;
            fetch('/Modern/public/api/billing/status.php?checkout=' + encodeURIComponent(checkout), {headers:{'Accept':'application/json'}})
              .then(function(r){ return r.json(); })
              .then(function(d){
                if (d.status === 'success'){ clearInterval(t); msg.textContent = 'Payment received! Taking you in…'; window.location = d.redirect; }
                else if (d.status === 'failed' || d.status === 'cancelled'){ clearInterval(t); msg.textContent = 'Payment was not completed.'; retry.style.display='block'; }
                else if (tries >= max){ clearInterval(t); msg.textContent = 'Still waiting — you can resend the prompt.'; retry.style.display='block'; }
              }).catch(function(){});
        }, 3000);
    })();
    </script>

<?php elseif ($view === 'error'): ?>
    <div class="auth-title">Payment couldn't start</div>
    <div class="auth-alert err"><?php echo htmlspecialchars($payError ?: 'Something went wrong.'); ?></div>
    <form method="post">
        <input type="hidden" name="action" value="retry">
        <button class="btn-auth">Try again</button>
    </form>
    <div class="auth-foot"><a href="/Modern/public/index.php#pricing">Back to plans</a></div>

<?php elseif (!$plan || $amount === null): ?>
    <div class="auth-title">Choose a plan first</div>
    <div class="auth-sub">Pick how you'd like to be billed, then we'll set up your shop.</div>
    <a class="btn-auth d-block text-center text-decoration-none" href="/Modern/public/index.php#pricing">See plans</a>

<?php else: ?>
    <div class="auth-title">Create your shop</div>
    <div class="auth-sub">You're signing up for the <strong><?php echo htmlspecialchars($plan['name']); ?></strong> plan.</div>

    <div style="background:#f1f6f5;border:1px solid #e2eae8;border-radius:12px;padding:14px 16px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;">
        <div><div style="font-weight:700;"><?php echo htmlspecialchars($intLabel); ?></div>
             <div class="text-muted small"><?php echo htmlspecialchars($plan['name']); ?> plan</div></div>
        <div style="font-weight:800;font-size:1.1rem;">KES <?php echo number_format((float)$amount, 0); ?></div>
    </div>

    <?php if (!empty($errors['_'])): ?><div class="auth-alert err"><?php echo htmlspecialchars($errors['_']); ?></div><?php endif; ?>

    <form method="post" novalidate>
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="plan_id" value="<?php echo (int)$planId; ?>">
        <input type="hidden" name="interval" value="<?php echo htmlspecialchars($interval); ?>">
        <div class="mb-3">
            <label class="form-label">Business name</label>
            <input name="business_name" class="form-control" value="<?php echo htmlspecialchars($old['business_name']); ?>">
            <?php if (!empty($errors['business_name'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['business_name']); ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($old['email']); ?>">
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['email']); ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">M-Pesa phone</label>
            <input name="phone" class="form-control" placeholder="0712 345 678" value="<?php echo htmlspecialchars($old['phone']); ?>">
            <?php if (!empty($errors['phone'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['phone']); ?></small><?php endif; ?>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" placeholder="At least 8 characters">
            <?php if (!empty($errors['password'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['password']); ?></small><?php endif; ?>
        </div>
        <button class="btn-auth">Pay KES <?php echo number_format((float)$amount, 0); ?> &amp; activate</button>
    </form>
    <div class="auth-foot">Already have an account? <a href="/Modern/public/auth/login.php">Log in</a></div>
<?php endif;
$content = ob_get_clean();
include ROOT_PATH . '/public/templates/auth/layout.php';