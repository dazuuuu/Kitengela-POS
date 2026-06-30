# Modern-POS / Kitale POS

A multi-tenant Point-of-Sale web app (PHP 8.3, MariaDB, server-rendered, no build step).
The active/primary product is the **POS** (owner + staff dashboards under `public/super/`
and `public/staff/`). A legacy public catalogue/website also lives in the same `public/`
tree but is secondary.

## Cursor Cloud specific instructions

The dependency-refresh layer (composer) runs automatically via the startup update script.
The notes below are the durable, non-obvious things needed to run and develop the app.

### Stack & where commands live
- PHP 8.3 (CLI + `php -S` dev server), Composer, MariaDB 10.11. No Node, no build/lint/test
  tooling exists in this repo (no Makefile, no PHPUnit, no linter config).
- Single Composer dependency: `phpmailer/phpmailer` (already committed under `vendor/`).
  `composer install` exits non-zero because of a pre-existing `composer.json` (`^6.9`) vs
  `composer.lock` (`v7.1.1`) mismatch — this is harmless since `vendor/` is committed.

### Database (MariaDB) — must be started each session
- Start it: `sudo service mariadb start` (it is NOT auto-started on boot, and starting
  services is intentionally not in the update script).
- Connection: DB `modern_db`, user `root`, password `mysql` (see `app/config/database.php`).
  The `root` user was configured for password auth (`mysql_native_password`).
- Socket caveat (already fixed persistently in `/etc/php/8.3/cli/conf.d/99-mysql-socket.ini`):
  MariaDB's socket is `/run/mysqld/mysqld.sock` but PHP defaulted to
  `/var/run/mysqld/mysqld.sock` (these are NOT symlinked here), which made
  `PDO host=localhost` fail with `[2002] No such file or directory`. The ini override points
  PHP's `pdo_mysql.default_socket` at the real path; keep it.

### Recreating the database schema (non-obvious migration gotchas)
Migrations are raw SQL in `databases/migrations/` with **no runner** and are NOT cleanly
idempotent on a fresh DB. Apply them like this:
```bash
mysql -u root -pmysql -e "CREATE DATABASE IF NOT EXISTS modern_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
for f in databases/migrations/*.sql; do mysql -u root -pmysql modern_db < "$f"; done
```
Then fix these known issues (the loop above will report errors for them):
- `003_*` and `004_*` hardcode `USE ismano_db;` (a different DB). Apply them into `modern_db`
  with the USE line stripped: `grep -v '^USE ' 00X_*.sql | mysql -u root -pmysql modern_db`.
- `008_*` ALTERs `store_cart`, which is only created in `009_*`. Re-run `008_*` after `009_*`.
- **Products table collision (important for POS):** both `007_*` (legacy catalogue) and
  `020_create_inventory.sql` (POS) do `CREATE TABLE IF NOT EXISTS products` with different
  schemas. `007` wins by ordering, leaving the POS `ProductModel` broken (it needs
  `tenant_id`/`subcategory_id`). Force the POS schema:
  ```bash
  mysql -u root -pmysql modern_db -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS products; SET FOREIGN_KEY_CHECKS=1;"
  mysql -u root -pmysql modern_db < databases/migrations/020_create_inventory.sql
  mysql -u root -pmysql modern_db < databases/migrations/021_product_category_optional.sql
  ```

### Running the app (dev server)
URLs are hardcoded to the base path `/Kitale/public/...`, so the docroot must expose the
repo as `Kitale`. A `/srv/www/Kitale -> /workspace` symlink is used; recreate if missing:
```bash
sudo mkdir -p /srv/www && sudo ln -sfn /workspace /srv/www/Kitale
php -S 0.0.0.0:8000 -t /srv/www      # then open http://localhost:8000/Kitale/public/
```

### Accounts / login (non-obvious)
- Login is single-step (no 2FA). It only requires the user row to have `is_active=1` AND
  `email_verified=1` (`app/helpers/AccountGuard.php`). Owner role is `tenant_owner` (role_id 5).
- The dev tool `public/devs/register-tenant.php?key=kitale-dev` is **out of date** (it INSERTs
  `name`/`password`/`role_name` columns that no longer exist). Create accounts directly in SQL
  against the real `users` schema (`username`, `email`, `password_hash`, `role_id`,
  `tenant_id`, `is_active`, `email_verified`). A bcrypt hash: `php -r 'echo password_hash("...", PASSWORD_BCRYPT);'`.
- Email (OTP, invites, reports) needs SMTP in `app/config/mail.php` (gitignored). Set
  `'otp_debug' => true` in `app/config/app.php` to surface OTPs on-screen without real email.
  M-Pesa billing (`app/config/mpesa.php`) needs Daraja creds + a public callback URL.
