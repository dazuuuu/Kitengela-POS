<?php
// app/helpers/Branding.php
// Logo paths for login, sidebars, receipts. Tenant-uploaded logo when available.

class Branding
{
    public const PUBLIC_URL  = '/Rongai/public';
    public const DEFAULT_LOGO = self::PUBLIC_URL . '/assets/images/logo/logo.png';

    /** Normalize stored logo paths (legacy /public/... or full web path). */
    public static function resolveLogoPath(?string $path): string
    {
        if ($path === null || $path === '') {
            return self::DEFAULT_LOGO;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, self::PUBLIC_URL)) {
            return $path;
        }
        if (str_starts_with($path, '/public/')) {
            return '/Rongai' . $path;
        }
        return self::PUBLIC_URL . '/' . ltrim($path, '/');
    }

    /** Login / register screens — first active tenant logo, else default. */
    public static function authLogo(?PDO $db = null): string
    {
        if ($db) {
            try {
                $stmt = $db->query(
                    "SELECT logo_path FROM tenants
                      WHERE status = 'active' AND logo_path IS NOT NULL AND logo_path != ''
                   ORDER BY id ASC LIMIT 1"
                );
                $path = $stmt->fetchColumn();
                if ($path) {
                    return self::resolveLogoPath((string) $path);
                }
            } catch (\Throwable $e) {
                // tenants table may not exist during setup
            }
        }
        return self::DEFAULT_LOGO;
    }

    /** Tenant pages, staff dashboard, receipts. */
    public static function tenantLogo(?array $tenant): string
    {
        if ($tenant && !empty($tenant['logo_path'])) {
            return self::resolveLogoPath($tenant['logo_path']);
        }
        return self::authLogo(null);
    }
}
