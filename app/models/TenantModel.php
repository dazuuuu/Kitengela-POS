<?php
// app/models/TenantModel.php
namespace Models;

/**
 * The tenant record itself. NOT tenant-scoped (it predates/defines the scope),
 * so $tenantScoped is false and queries are addressed by tenant id explicitly.
 */
class TenantModel extends Model
{
    protected string $table = 'tenants';
    protected bool $tenantScoped = false;

    public function create(string $name, string $slug): int
    {
        return $this->insert(['name' => $name, 'slug' => $slug, 'status' => 'active']);
    }

    public function setOwner(int $tenantId, int $userId): bool
    {
        return $this->update($tenantId, ['owner_user_id' => $userId]);
    }

    /** Whitelisted business-settings update. Caller passes their own tenant id. */
    public function updateSettings(int $tenantId, array $data): bool
    {
        self::ensureSettingsSchema($this->db);

        $allowed = ['name', 'logo_path', 'currency', 'phone', 'email', 'website', 'address', 'location', 'kra_pin', 'receipt_footer'];
        $clean = array_intersect_key($data, array_flip($allowed));
        $columns = self::columnNames($this->db);
        $clean = array_intersect_key($clean, array_flip($columns));
        if (!$clean) {
            return false;
        }
        return $this->update($tenantId, $clean);
    }

    /** Add Settings columns when migration 024 was not run yet (safe to call repeatedly). */
    public static function ensureSettingsSchema(\PDO $db): void
    {
        $existing = self::columnNames($db);
        $needed = [
            'email'    => 'VARCHAR(255) NULL',
            'website'  => 'VARCHAR(255) NULL',
            'location' => 'VARCHAR(255) NULL',
            'kra_pin'  => 'VARCHAR(30) NULL',
        ];
        foreach ($needed as $col => $def) {
            if (in_array($col, $existing, true)) {
                continue;
            }
            try {
                $db->exec("ALTER TABLE tenants ADD COLUMN {$col} {$def}");
                $existing[] = $col;
            } catch (\PDOException $e) {
                // Column may have been added concurrently; ignore duplicate-column errors.
                if ($e->getCode() !== '42S21' && !str_contains($e->getMessage(), 'Duplicate column')) {
                    throw $e;
                }
                self::$columnCache = null;
                $existing = self::columnNames($db);
            }
        }
        self::$columnCache = $existing;
    }

    /** @var string[]|null */
    private static ?array $columnCache = null;

    /** @return string[] */
    public static function columnNames(\PDO $db): array
    {
        if (self::$columnCache !== null) {
            return self::$columnCache;
        }
        $stmt = $db->query('SHOW COLUMNS FROM tenants');
        self::$columnCache = $stmt ? array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Field') : [];
        return self::$columnCache;
    }

    /** Unique slug from a business name. */
    public function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
        if ($base === '') {
            $base = 'shop';
        }
        $slug = $base;
        $i = 1;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM tenants WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (bool) $stmt->fetchColumn();
    }
}