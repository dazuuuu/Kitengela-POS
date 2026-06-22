<?php
// app/helpers/PageGuard.php
// Per-request gate for protected pages. Enforces: fully authenticated (password
// AND OTP), correct role, and a valid subscription. Redirects otherwise.

class PageGuard
{
    const LOGIN_URL = '/Modern/public/auth/login.php';
    const STAFF_RESET_URL = '/Modern/public/staff/reset-password.php';

    /** Require a fully-authenticated tenant OWNER with an active subscription. */
    public static function tenant(): void
    {
        self::requireFullAuth();
        if (TenantContext::role() !== 'tenant_owner') {
            self::deny();
        }
        self::requireActiveSubscription();
    }

    /** Require a fully-authenticated STAFF member with an active subscription. */
    public static function staff(): void
    {
        self::requireFullAuth();
        if (TenantContext::role() !== 'staff') {
            self::deny();
        }
        self::requireActiveSubscription();
        if (!empty($_SESSION['must_reset'])) {
            header('Location: ' . self::STAFF_RESET_URL);
            exit;
        }
    }

    /** Require a fully-authenticated user (owner or staff) who holds a capability. */
    public static function capability(string $cap): void
    {
        self::requireFullAuth();
        self::requireActiveSubscription();
        if (!TenantContext::can($cap)) {
            self::deny();
        }
        if (TenantContext::role() === 'staff' && !empty($_SESSION['must_reset'])) {
            header('Location: ' . self::STAFF_RESET_URL);
            exit;
        }
    }

    private static function requireFullAuth(): void
    {
        $authed = !empty($_SESSION['logged_in']) && !empty($_SESSION['otp_verified']) && TenantContext::check();
        if (!$authed) {
            header('Location: ' . self::LOGIN_URL);
            exit;
        }
    }

    private static function requireActiveSubscription(): void
    {
        $tenantId = TenantContext::tenantId();
        if ($tenantId === null) {
            return; // platform users aren't subscription-gated
        }
        $sub = (new Models\SubscriptionModel(Database::pdo()))->forTenant($tenantId);
        $user = ['is_active' => 1, 'email_verified' => 1, 'tenant_id' => $tenantId];
        $verdict = AccountGuard::evaluate($user, $sub);
        if (!$verdict['ok']) {
            $_SESSION['flash']['error'] = AccountGuard::message($verdict['reason']);
            header('Location: ' . self::LOGIN_URL . '?locked=1');
            exit;
        }
    }

    private static function deny(): void
    {
        header('Location: ' . self::LOGIN_URL . '?denied=1');
        exit;
    }
}