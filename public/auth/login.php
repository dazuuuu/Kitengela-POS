<?php
// public/auth/login.php
// Step 1 of login: verify credentials + account/subscription state, then issue an
// email OTP and hand off to 2FA. A full session is NOT created here — only a
// "pending 2FA" marker — so nothing protected is reachable until the code is verified.
require_once __DIR__ . '/../../app/app.php';
require_once ROOT_PATH . '/app/services/emails/otp_email.php';

// Already fully logged in with a recognised role? Go to the right dashboard.
// A session that claims to be logged in but carries no valid role is stale or
// half-built (e.g. left over from an earlier build / dev shortcut). Don't trust
// it — discard the auth state and show the login form rather than bouncing the
// user into a guard that will only deny them.
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['otp_verified'])) {
    $sessionRole = $_SESSION['role'] ?? null;
    if ($sessionRole === 'staff') {
        header('Location: /Modern/public/staff/dashboard/');
        exit;
    }
    if ($sessionRole === 'tenant_owner') {
        header('Location: /Modern/public/super/dashboard/');
        exit;
    }
    // Unknown/empty role — broken session. Clear it and fall through to login.
    unset(
        $_SESSION['logged_in'], $_SESSION['otp_verified'], $_SESSION['user_id'],
        $_SESSION['tenant_id'], $_SESSION['role'], $_SESSION['capabilities'],
        $_SESSION['username'], $_SESSION['must_reset']
    );
    TenantContext::reset();
}

$pdo = Database::pdo();
$auth = new AuthService($pdo);
$error = '';
$notice = '';
if (($_GET['reset'] ?? '') === '1') {
    $notice = 'Your password has been set. Please sign in with your new password.';
} elseif (($_GET['denied'] ?? '') === '1') {
    $error = 'You don\'t have access to that page. Please sign in with the right account.';
} elseif (($_GET['locked'] ?? '') === '1') {
    $error = 'Your subscription needs attention before you can sign in.';
}
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $user = $auth->findByEmail($email);
    if (!$user || !$auth->verifyPassword($user, $password)) {
        $auth->logAttempt($email, $ip);
        $error = 'Invalid email or password.';
    } else {
        $sub = $auth->subscriptionFor($user['tenant_id'] !== null ? (int) $user['tenant_id'] : null);
        $verdict = AccountGuard::evaluate($user, $sub);
        if (!$verdict['ok']) {
            $error = AccountGuard::message($verdict['reason']);
        } else {
            // Credentials good -> start 2FA. Half-authenticated session only.
            $otp = new OtpService($pdo);
            $issue = $otp->issue((int) $user['id'], $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null, 'login_2fa', $ip);
            if ($issue['ok']) {
                $shop = '';
                if ($user['tenant_id'] !== null) {
                    $t = (new Models\TenantModel($pdo))->find((int) $user['tenant_id']);
                    $shop = $t['name'] ?? '';
                }
                $msg = build_otp_email($issue['code'], $shop !== '' ? $shop : 'your login');
                $sent = (new MailService())->send($user['email'], $msg['subject'], $msg['html']);

                // Local testing aid: surface the code on the next screen when SMTP
                // isn't configured yet. OFF unless app/config/app.php sets
                // ['otp_debug' => true]. Remove/disable before going live.
                $appCfg = is_file(ROOT_PATH . '/app/config/app.php') ? require ROOT_PATH . '/app/config/app.php' : [];
                if (!empty($appCfg['otp_debug'])) {
                    $_SESSION['dev_otp'] = $issue['code'];
                    $_SESSION['dev_mail_error'] = $sent ? '' : MailService::lastError();
                    if (!$sent) { error_log('OTP not emailed (otp_debug on); code surfaced on screen.'); }
                } else {
                    unset($_SESSION['dev_otp'], $_SESSION['dev_mail_error']);
                }
            }
            // Stash pending identity (NOT logged_in/otp_verified yet).
            session_regenerate_id(true);
            $_SESSION['pending_user_id']   = (int) $user['id'];
            $_SESSION['pending_email']     = $user['email'];
            $_SESSION['pending_tenant_id'] = $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null;
            header('Location: /Modern/public/verification/otp-verify.php');
            exit;
        }
    }
}

$page_title = 'Log in';
ob_start();
?>
<div class="auth-title">Welcome back</div>
<div class="auth-sub">Log in to your shop dashboard.</div>
<?php if ($error): ?><div class="auth-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($notice): ?><div class="auth-alert ok"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
<form method="post" novalidate>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" autofocus>
    </div>
    <div class="mb-4">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control">
    </div>
    <button class="btn-auth">Continue</button>
</form>
<div class="auth-foot">No account yet? <a href="/Modern/public/auth/register.php">Create one</a></div>
<?php
$content = ob_get_clean();
include ROOT_PATH . '/public/templates/auth/layout.php';