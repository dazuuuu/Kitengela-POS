<?php
// app/helpers/AccountGuard.php
// Decides whether an account may log in / use the app, based on activation state
// and subscription validity. Login and per-request middleware both consult this.

class AccountGuard
{
    /** Days after period end the tenant can still log in (read-only handled in UI). */
    const GRACE_DAYS = 0;

    /**
     * @param array      $user  the users row (needs is_active, email_verified)
     * @param array|null $sub   the subscriptions row (or null)
     * @return array ['ok'=>bool, 'reason'=>?string]
     */
    public static function evaluate(array $user, ?array $sub): array
    {
        if (empty($user['is_active']) || empty($user['email_verified'])) {
            return ['ok' => false, 'reason' => 'not_activated'];
        }

        // Platform admins (no tenant) aren't subscription-gated.
        if (($user['tenant_id'] ?? null) === null) {
            return ['ok' => true, 'reason' => null];
        }

        if (!$sub) {
            return ['ok' => false, 'reason' => 'no_subscription'];
        }
        if (!in_array($sub['status'], ['trialing', 'active'], true)) {
            return ['ok' => false, 'reason' => 'subscription_' . $sub['status']];
        }
        if (!empty($sub['current_period_end'])) {
            $deadline = strtotime($sub['current_period_end']) + (self::GRACE_DAYS * 86400);
            if (time() > $deadline) {
                return ['ok' => false, 'reason' => 'subscription_expired'];
            }
        }
        return ['ok' => true, 'reason' => null];
    }

    public static function message(string $reason): string
    {
        return [
            'not_activated'        => 'Please activate your account using the link we emailed you.',
            'no_subscription'      => 'No active subscription found for this account.',
            'subscription_expired' => 'Your subscription has expired. Please renew to continue.',
        ][$reason] ?? 'Your account cannot be accessed right now. Please renew or contact support.';
    }
}