-- 024_tenant_business_credentials.sql
-- Extra business fields for Settings (stored but not printed on receipts by default).
-- Safe to re-run: skips columns that already exist.

ALTER TABLE tenants ADD COLUMN email    VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN website  VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN location VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN kra_pin  VARCHAR(30)  NULL;
