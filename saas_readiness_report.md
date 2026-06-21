# SaaS / Multi-Tenant Readiness Report

_Generated: 2026-06-19 18:53:35_  
_Root: `C:/Program Files/Ampps/www/Modern`_

## Readiness score: 25/100
> Heuristic. Higher = less retrofit work. Breakdown below.

## 1. Code at a glance
- PHP files (excl. vendor): **183**
- Lines of PHP (your code): **31212**
- SQL files: **13**
- Total files scanned: 210

## 2. Real dependencies (from composer.json)
- `php`: >=7.4
- `phpmailer/phpmailer`: ^6.9

> Reality check: ignore any earlier claim of Laravel/Symfony/etc. unless it appears above.

## 3. Database — tenant readiness
Tables total: **48**

### Already tenant/store-scoped (2)
- `subscription_stk` (via `tenant_id`)
- `subscriptions` (via `tenant_id`)

### Business tables MISSING a tenant column (38)
These need a `tenant_id` added + backfill + an enforced query scope:
- blog_categories
- blog_faqs
- blog_sections
- blog_tag_relations
- blog_tags
- blogs
- cart_items
- cart_sessions
- enquiries
- enquiry_replies
- gallery
- gallery_categories
- hero_slides
- page_headers
- product_categories
- products
- project_categories
- project_gallery
- project_tag_relations
- project_tags
- project_videos
- projects
- saved_for_later
- service_benefits
- service_faqs
- service_gallery
- service_sections
- services
- site_settings
- store_cart
- store_categories
- store_order_items
- store_orders
- store_products
- store_saved_for_later
- testimonials
- user_profiles
- users

### Platform-global tables (expected unscoped)
- login_attempts
- password_resets
- roles
- store_order_items_backup
- store_orders_backup
- subscription_plans
- tenant_otp
- tenants

## 4. Schema drift & duplication
### Tables in a dump with NO migration that creates them
- password_resets
- store_order_items_backup
- store_orders_backup
- subscription_plans
- subscription_stk
- subscriptions
- tenant_otp
- tenants
### Competing/duplicate subsystems
- **cart**: `cart_items`, `cart_sessions`, `store_cart`, `store_saved_for_later`, `saved_for_later`
- **order**: `store_orders`, `store_order_items`
- **product**: `products`, `store_products`
- **category**: `product_categories`, `store_categories`

> Decide on ONE before scoping — don't tenant-ize both copies.

## 5. Query & security patterns
- `->prepare()` calls (prepared statements): **333**
- `->query()` with variable concatenation (injection smell): **5**
- mysqli references: **2**
- `$_SESSION` references: **293**
- Base/abstract Model class: **no — this raises retrofit cost**
- Files with raw $_GET/$_POST on the same line as SQL (review these):
  - `public/admin/enquiries/view.php` (1)
  - `public/admin/gallery/index.php` (3)
  - `public/admin/store/products/index.php` (2)
  - `public/api/blog/update.php` (1)
  - `public/api/store/cart/update.php` (1)

## 6. Feature inventory (reusable building blocks)
- **Auth & users** — 10 file(s)
- **Products (legacy)** — 3 file(s)
- **Store / e-commerce** — 11 file(s)
- **Cart** — 4 file(s)
- **Orders** — 5 file(s)
- **M-Pesa / payments** — 3 file(s)
- **Blog** — 2 file(s)
- **Gallery** — 2 file(s)
- **Projects** — 2 file(s)
- **Services** — 2 file(s)
- **Enquiries** — 2 file(s)
- **Testimonials** — 2 file(s)
- **Mail** — 3 file(s)
- **Settings** — 4 file(s)

## 7. Share these next
To design the tenant foundation, paste me these (real code, not this report):
1. `databases/ismano_db.sql` (source-of-truth schema)
2. `app/init.php`, `app/bootstrap.php`, `app/config/database.php` (boot + DB connection — where scoping is injected)
3. `app/controllers/AuthController.php`, `app/models/UserModel.php`, `app/models/Session.php`, `app/helpers/middleware.php`
4. One model+controller pair, e.g. `app/models/StoreProductModel.php` + `app/controllers/StoreController.php` (to see the query pattern)
5. `app/services/MpesaService.php` + `app/config/mpesa.php` (for billing)
