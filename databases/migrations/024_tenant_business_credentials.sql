-- 024_tenant_business_credentials.sql
-- Extra business fields for Settings (stored but not printed on receipts by default).

ALTER TABLE tenants
    ADD COLUMN email    VARCHAR(255) NULL AFTER phone,
    ADD COLUMN website  VARCHAR(255) NULL AFTER email,
    ADD COLUMN location VARCHAR(255) NULL AFTER address,
    ADD COLUMN kra_pin  VARCHAR(30)  NULL AFTER location;
