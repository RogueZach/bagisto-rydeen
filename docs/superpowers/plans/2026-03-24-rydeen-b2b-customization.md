# Rydeen Dealer Portal — B2B Suite Customization Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Customize the Bagisto B2B Suite into a Rydeen-branded dealer portal with passwordless auth, tier pricing, simplified order flow, dashboard KPIs, and Railway deployment.

**Architecture:** We build on top of Bagisto v2.3.16 + B2B Suite rather than replacing it. Custom packages in `packages/Rydeen/` extend B2B Suite functionality via event listeners, view overrides, and middleware. We do NOT modify `vendor/` or `packages/Webkul/` directly — all changes are additive. The B2B Suite provides company registration, quote/RFQ, purchase orders, requisition lists, quick orders, and role-based permissions. We leverage these where they align with the PRD and hide/override where they don't.

**Tech Stack:** PHP 8.2, Laravel 11, Bagisto v2.3.16, B2B Suite (dev-master), Tailwind CSS, Alpine.js, Pest (testing), Resend (email), Railway (hosting)

**B2B Suite Integration Strategy:**
- **Keep:** Company registration (customized), company user management, role-based permissions
- **Adapt:** B2B Suite's purchase order flow → simplified dealer cart-to-order
- **Hide:** Quotes/RFQ system, requisition lists, quick orders (not needed for MVP, can re-enable later)
- **Add:** Passwordless auth, promo pricing engine, dashboard KPIs, Rydeen theme, FAQ/resources

---

## File Structure

### Package: `packages/Rydeen/Core/`
Core package providing shared config, helpers, the Rydeen theme, and B2B Suite configuration.

```
packages/Rydeen/Core/
├── src/
│   ├── Config/
│   │   └── rydeen.php                    # Rydeen-wide config (device trust, pricing, etc.)
│   ├── Providers/
│   │   └── CoreServiceProvider.php       # Registers config, views, theme, B2B activation, menu overrides
│   ├── Resources/
│   │   ├── lang/en/
│   │   │   └── app.php                  # Translations
│   │   └── views/
│   │       └── shop/
│   │           ├── layouts/
│   │           │   └── master.blade.php  # Rydeen-branded master layout
│   │           └── components/
│   │               ├── header.blade.php
│   │               └── footer.blade.php
│   └── Database/
│       └── Seeders/
│           └── RydeenSeeder.php         # Seeds customer groups, B2B activation, demo data
├── tests/
│   └── Unit/
│       └── CoreConfigTest.php
└── composer.json
```

### Package: `packages/Rydeen/Auth/`
Passwordless authentication with 6-digit email codes and device trust.

```
packages/Rydeen/Auth/
├── src/
│   ├── Providers/
│   │   └── AuthServiceProvider.php       # Routes, middleware, views, migrations
│   ├── Services/
│   │   └── AuthService.php              # generateCode, verifyCode, deviceTrust (codes hashed)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── LoginController.php      # Email entry, code verify, logout
│   │   └── Middleware/
│   │       ├── DeviceVerification.php   # Trusted device = skip code; untrusted = require code
│   │       └── RedirectStandardAuth.php # Redirect /customer/login → /dealer/login
│   ├── Models/
│   │   ├── VerificationCode.php         # Hashed 6-digit codes with expiry
│   │   └── TrustedDevice.php           # UUID cookie → customer mapping
│   ├── Mail/
│   │   └── VerificationCodeMail.php
│   ├── Database/
│   │   └── Migrations/
│   │       ├── 2026_03_24_000001_create_verification_codes_table.php
│   │       └── 2026_03_24_000002_create_trusted_devices_table.php
│   └── Resources/
│       ├── lang/en/app.php
│       └── views/
│           ├── login.blade.php
│           ├── verify.blade.php
│           └── emails/
│               └── verification-code.blade.php
├── tests/
│   ├── Unit/
│   │   └── AuthServiceTest.php
│   └── Feature/
│       └── LoginFlowTest.php
└── composer.json
```

### Package: `packages/Rydeen/Pricing/`
Promotional pricing engine layered on top of Bagisto's customer group prices.

```
packages/Rydeen/Pricing/
├── src/
│   ├── Providers/
│   │   └── PricingServiceProvider.php
│   ├── Services/
│   │   └── PriceResolver.php            # Resolve best price: group price → promo overlay
│   ├── Models/
│   │   ├── Promotion.php
│   │   └── PromotionItem.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── Admin/
│   │           └── PromotionController.php
│   ├── Database/
│   │   └── Migrations/
│   │       ├── 2026_03_24_000003_create_rydeen_promotions_table.php
│   │       └── 2026_03_24_000004_create_rydeen_promotion_items_table.php
│   └── Resources/
│       ├── lang/en/app.php
│       └── views/admin/promotions/
│           ├── index.blade.php
│           └── create.blade.php
├── tests/
│   └── Unit/
│       └── PriceResolverTest.php
└── composer.json
```

### Package: `packages/Rydeen/Dealer/`
Dealer-specific features: approval workflow, dashboard KPIs, catalog, order flow, resources, exports.

```
packages/Rydeen/Dealer/
├── src/
│   ├── Providers/
│   │   ├── DealerServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Services/
│   │   └── DashboardStatsService.php    # KPIs via OrderRepository queries
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Shop/
│   │       │   ├── DashboardController.php
│   │       │   ├── CatalogController.php
│   │       │   ├── ProductController.php
│   │       │   ├── OrderController.php
│   │       │   └── ResourcesController.php
│   │       └── Admin/
│   │           ├── DealerApprovalController.php
│   │           └── ExportController.php
│   ├── Listeners/
│   │   └── OrderListener.php
│   ├── Mail/
│   │   ├── OrderSubmittedMail.php
│   │   ├── OrderConfirmationMail.php
│   │   └── DealerApprovedMail.php
│   ├── Payment/
│   │   └── DealerOrder.php             # No-payment method (extends Webkul\Payment\Payment)
│   ├── Shipping/
│   │   └── DealerShipping.php          # Free shipping method
│   ├── Models/
│   │   └── ResourceItem.php
│   ├── Database/
│   │   ├── Migrations/
│   │   │   ├── 2026_03_24_000005_add_dealer_fields_to_customers_table.php
│   │   │   └── 2026_03_24_000006_create_resource_items_table.php
│   │   └── Seeders/
│   │       └── DealerDemoSeeder.php    # Sample products, dealers, customer groups for testing
│   └── Resources/
│       ├── lang/en/app.php
│       └── views/
│           ├── shop/
│           │   ├── dashboard/index.blade.php
│           │   ├── catalog/index.blade.php
│           │   ├── catalog/product.blade.php
│           │   ├── orders/index.blade.php
│           │   ├── orders/view.blade.php
│           │   ├── orders/print.blade.php
│           │   ├── resources/index.blade.php
│           │   └── emails/
│           │       ├── order-submitted.blade.php
│           │       └── order-confirmation.blade.php
│           └── admin/
│               ├── dealers/index.blade.php
│               ├── dealers/view.blade.php
│               └── exports/index.blade.php
├── tests/
│   ├── Unit/
│   │   └── DashboardStatsServiceTest.php
│   └── Feature/
│       ├── DealerApprovalTest.php
│       ├── CatalogTest.php
│       └── OrderFlowTest.php
└── composer.json
```

### Root-level files

```
deploy.sh                    # Railway deploy script
railway.json                 # Railway config
nixpacks.toml               # Nixpacks build config
CLAUDE.md                    # Project guidance
```

---

## Task 1: Project Scaffolding, Core Package & Theme

Combines scaffolding + theme (theme must exist before other packages create views).

**Files:**
- Create: `CLAUDE.md`
- Create: `packages/Rydeen/Core/` (all files)
- Modify: `composer.json` (root — add autoload for all 4 packages)
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Create CLAUDE.md**

Include: project overview (Bagisto v2.3.16 + B2B Suite), architecture notes, commands, and the rule to NOT modify vendor/Webkul.

- [ ] **Step 2: Create Core package structure**

Create `packages/Rydeen/Core/composer.json`, `src/Config/rydeen.php`, `src/Resources/lang/en/app.php`.

Config (`rydeen.php`):
```php
return [
    'device_trust_days'    => env('DEALER_DEVICE_TRUST_DAYS', 30),
    'code_expiry_minutes'  => 10,
    'code_resend_cooldown' => 60,
    'code_max_per_hour'    => 5,
    'admin_order_email'    => env('ADMIN_MAIL_ADDRESS', 'orders@rydeenmobile.com'),
];
```

- [ ] **Step 3: Create Rydeen master layout + header + footer**

`master.blade.php`: Tailwind CSS (CDN), Alpine.js, RYDEEN branding, nav (Dashboard, Catalog, Orders, Resources, Profile), cart badge, mobile hamburger menu, footer with contact info and business hours.

`header.blade.php`: RYDEEN logo, nav links with active state, cart icon with count, logout button.

`footer.blade.php`: Contact info, Mon-Fri 9:30am-4:30pm PT hours.

- [ ] **Step 4: Create CoreServiceProvider**

```php
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/rydeen.php', 'rydeen');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'rydeen');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'rydeen');

        // Activate B2B Suite if not already active
        if (Schema::hasTable('core_config')) {
            $active = \Webkul\Core\Models\CoreConfig::where('code', 'b2b_suite.general.settings.active')->first();
            if (! $active || $active->value !== '1') {
                \Webkul\Core\Models\CoreConfig::updateOrCreate(
                    ['code' => 'b2b_suite.general.settings.active'],
                    ['value' => '1', 'channel_code' => 'default', 'locale_code' => 'en']
                );
            }
        }

        // Override B2B shop menu to hide quotes/requisitions/quick-orders for MVP
        // Keep: purchase_orders, users, roles
        // Hide: quotes, requisitions, quick_orders
    }
}
```

- [ ] **Step 5: Create RydeenSeeder**

Seeds 4 customer groups (MESA Dealers, New Dealers, Dealers, International Dealers) and activates B2B Suite config flag.

- [ ] **Step 6: Register in root composer.json and bootstrap/providers.php**

Add all 4 package namespaces to autoload + autoload-dev. Add CoreServiceProvider after B2BSuiteServiceProvider.

Run: `composer dump-autoload`

- [ ] **Step 7: Verify everything boots**

Run: `php artisan tinker --execute="echo config('rydeen.device_trust_days');"`
Expected: `30`

- [ ] **Step 8: Commit**

```bash
git add packages/Rydeen/Core/ composer.json bootstrap/providers.php CLAUDE.md
git commit -m "feat: add Rydeen Core package with theme, config, and B2B activation"
```

---

## Task 2: Passwordless Auth Package

**Files:**
- Create: All files in `packages/Rydeen/Auth/`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Write AuthService unit tests**

Test service contract (not stored format — codes are hashed):
- `generateCode` for valid email returns success
- `generateCode` for unknown email returns failure
- 60-second resend cooldown enforced
- `verifyCode` with correct code returns success + customer
- `verifyCode` with wrong code returns failure
- `verifyCode` with expired code returns failure
- `createDeviceTrust` returns UUID string
- `isDeviceTrusted` validates UUID correctly
- `isDeviceTrusted` rejects fake/expired UUID

- [ ] **Step 2: Run tests — expect failure (classes don't exist)**

- [ ] **Step 3: Create migrations**

`rydeen_verification_codes`: id, email (indexed), code_hash (string 64), expires_at, used (bool default false), timestamps.

`rydeen_trusted_devices`: id, customer_id (unsigned int, FK), uuid (string 64 unique), expires_at, timestamps.

- [ ] **Step 4: Create models (VerificationCode, TrustedDevice)**

- [ ] **Step 5: Create AuthService**

Key difference from v1: store `Hash::make($code)` instead of plaintext. Verify with `Hash::check($input, $stored_hash)`. This means tests verify behavior (generateCode returns success, verifyCode with correct code works) not stored format.

Also checks that customer `is_verified == 1` and `is_suspended == 0` before allowing login.

- [ ] **Step 6: Create VerificationCodeMail**

Uses `rydeen-auth::emails.verification-code` view. Implements `ShouldQueue`.

- [ ] **Step 7: Create LoginController**

Routes under `dealer/` prefix:
- `GET /dealer/login` → email form
- `POST /dealer/login` → validate email, generate code, redirect to verify
- `GET /dealer/verify` → code entry form (email from session)
- `POST /dealer/verify` → verify code, login via `auth('customer')->login($customer)`, set device trust cookie, redirect to `/dealer/dashboard`
- `POST /dealer/logout` → logout, redirect to login

- [ ] **Step 8: Create DeviceVerification middleware**

Flow: If customer is already authenticated AND `rydeen_device` cookie matches a valid TrustedDevice record → allow through (no code needed). If not authenticated → redirect to `/dealer/login`. Applied to all `/dealer/*` routes except login/verify.

- [ ] **Step 9: Create RedirectStandardAuth middleware**

Redirects `/customer/login`, `/customer/register`, `/companies/register` to `/dealer/login`. Register in AuthServiceProvider and apply to the standard Bagisto auth routes.

- [ ] **Step 10: Create views**

`login.blade.php`: Extends `rydeen::shop.layouts.master`. Email input + submit button.
`verify.blade.php`: 6-digit code input + verify button + resend link.
`emails/verification-code.blade.php`: Clean email with code.

- [ ] **Step 11: Create AuthServiceProvider**

Register routes, migrations, views (namespace `rydeen-auth`), middleware aliases (`device.verify`, `redirect.standard.auth`), translations.

- [ ] **Step 12: Register provider, run migrations, run tests**

Run: `php artisan migrate && php artisan test packages/Rydeen/Auth/`
Expected: All PASS

- [ ] **Step 13: Write feature tests**

Test: GET /dealer/login → 200, POST with valid email → redirect to verify, POST verify with correct code → redirect to dashboard + auth cookie set, GET /dealer/dashboard without auth → redirect to login, GET /customer/login → redirect to /dealer/login.

- [ ] **Step 14: Commit**

```bash
git add packages/Rydeen/Auth/ bootstrap/providers.php
git commit -m "feat: add passwordless auth with hashed 6-digit codes and device trust"
```

---

## Task 3: Pricing — Customer Group Tiers + Promo Engine

**Files:**
- Create: All files in `packages/Rydeen/Pricing/`
- Modify: `bootstrap/providers.php`

**Prerequisite:** Task 1 must have seeded the 4 customer groups. Bagisto's built-in `product_customer_group_prices` table handles base tier pricing per group. This package adds a promotional pricing layer on top.

- [ ] **Step 1: Write PriceResolver tests**

11 test cases covering: group price fallback, percentage discount, threshold qty-based, timing/date-range, SKU-level override, best-price-wins, inactive promos ignored, scope filtering (customer_group, category, sku).

- [ ] **Step 2: Run tests — expect failure**

- [ ] **Step 3: Create migrations**

`rydeen_promotions`: id, name, type (enum), value (decimal), min_qty, starts_at, ends_at, scope (enum), scope_id, active, timestamps.

`rydeen_promotion_items`: id, promotion_id (FK), product_id (FK), override_price, timestamps.

- [ ] **Step 4: Create Promotion and PromotionItem models**

- [ ] **Step 5: Implement PriceResolver**

```php
public function resolve(int $productId, float $groupPrice, int $customerGroupId, int $qty = 1, array $categoryIds = []): array
```

Returns `['price' => float, 'promo_name' => string|null]`. Queries active promotions matching scope. Computes candidate prices per type. Returns lowest. If none beat group price, returns group price with null promo_name.

**Important:** PriceResolver is called from both catalog display AND cart/order flow to ensure price consistency.

- [ ] **Step 6: Run tests — expect pass**

- [ ] **Step 7: Create admin PromotionController + views**

CRUD routes under `admin/rydeen/promotions`. Index (datagrid), create/edit (form with type/scope/value fields), store, update, destroy.

- [ ] **Step 8: Create PricingServiceProvider**

Register routes, migrations, views (`rydeen-pricing`), translations.

- [ ] **Step 9: Register provider, run migrations, verify**

- [ ] **Step 10: Commit**

```bash
git add packages/Rydeen/Pricing/ bootstrap/providers.php
git commit -m "feat: add promotional pricing engine with 4 discount types"
```

---

## Task 4: Dealer Package — Approval, Dashboard, Catalog, Orders, Resources

**Files:**
- Create: All files in `packages/Rydeen/Dealer/`
- Modify: `bootstrap/providers.php`

### Sub-task 4a: Dealer fields + approval workflow + payment/shipping methods

- [ ] **Step 1: Create migration adding dealer fields to customers**

Columns: `forecast_level` (string nullable), `approved_at` (timestamp nullable), `assigned_rep_id` (unsigned int nullable FK to admins).

- [ ] **Step 2: Create DealerOrder payment method**

Extends `Webkul\Payment\Payment`. Always available. No payment processing. Returns `$0` total. Registered in `config/paymentmethods.php` via service provider config merge. Title: "Dealer Order (No Payment Required)".

- [ ] **Step 3: Create DealerShipping shipping method**

Free shipping method. Extends `Webkul\Shipping\Carriers\AbstractShipping`. Rate = $0. Title: "Standard Dealer Shipping". Registered via config merge.

- [ ] **Step 4: Create DealerApprovalController**

Admin routes:
- GET `admin/rydeen/dealers` — list pending (unverified) and active dealers
- GET `admin/rydeen/dealers/{id}` — view dealer detail
- POST `admin/rydeen/dealers/{id}/approve` — set `is_verified=1`, `approved_at=now()`, assign CompanyRole
- POST `admin/rydeen/dealers/{id}/reject` — set `is_suspended=1`
- PUT `admin/rydeen/dealers/{id}/assign-rep` — set `assigned_rep_id`
- PUT `admin/rydeen/dealers/{id}/forecast-level` — set `forecast_level`

On approval: assign B2B Suite `CompanyRole` with appropriate permissions for the dealer. Use `CompanyRole::firstOrCreate(['name' => 'Dealer'], ['permission_type' => 'all'])`.

- [ ] **Step 5: Write approval tests**

- [ ] **Step 6: Commit**

```bash
git commit -m "feat: add dealer approval, no-payment method, and free shipping"
```

### Sub-task 4b: Dashboard KPIs

- [ ] **Step 7: Write DashboardStatsService tests**

- [ ] **Step 8: Implement DashboardStatsService**

Uses `Webkul\Sales\Repositories\OrderRepository` (not direct Eloquent) for Bagisto convention compliance. Queries: total_orders_ytd, this_month_total, pending_orders_count, forecast_level.

- [ ] **Step 9: Create DashboardController + view**

GET `/dealer/dashboard`. View extends `rydeen::shop.layouts.master`. KPI cards per PRD mockup.

- [ ] **Step 10: Commit**

```bash
git commit -m "feat: add dealer dashboard with KPI widgets"
```

### Sub-task 4c: Catalog with status flags + tier pricing

- [ ] **Step 11: Create CatalogController + ProductController**

`/dealer/catalog` — grid with category filter, search, pagination (12/page), status badges, PriceResolver pricing.

`/dealer/catalog/{slug}` — detail with images, dealer price, promo badge, add-to-cart button, out-of-stock handling.

- [ ] **Step 12: Create catalog views**

Extend `rydeen::shop.layouts.master`. Product grid (Tailwind, 4-col desktop, 2-col mobile). Product detail matching PRD mockup.

- [ ] **Step 13: Write catalog tests**

- [ ] **Step 14: Commit**

```bash
git commit -m "feat: add dealer catalog with status flags and tier pricing"
```

### Sub-task 4d: Order flow + email notifications

- [ ] **Step 15: Create OrderController**

Routes:
- GET `/dealer/orders` — history with search/filter/pagination
- GET `/dealer/orders/{id}` — detail
- GET `/dealer/orders/{id}/print` — printer-friendly
- POST `/dealer/orders/{id}/reorder` — copy items to cart
- GET `/dealer/order-review` — review cart before submitting
- POST `/dealer/order-review/place` — place order using DealerOrder payment + DealerShipping

The place-order flow: validate cart not empty → create order via Bagisto's order creation pipeline with pre-selected DealerOrder payment and DealerShipping → redirect to confirmation page.

- [ ] **Step 16: Create OrderListener (EventServiceProvider)**

Listen to `checkout.order.save.after`:
- Send `OrderSubmittedMail` to `config('rydeen.admin_order_email')` (orders@rydeenmobile.com)
- Send `OrderConfirmationMail` to the dealer's email

Both mailables implement `ShouldQueue`.

**Note:** B2B Suite also listens to `checkout.order.save.after` to link quotes to orders. Our listener coexists — it only sends emails, no state mutations.

- [ ] **Step 17: Create mail classes + email views**

- [ ] **Step 18: Write order flow tests**

- [ ] **Step 19: Commit**

```bash
git commit -m "feat: add dealer order flow with email notifications"
```

### Sub-task 4e: Resources/FAQ page

- [ ] **Step 20: Create ResourceItem model + migration**

Fields: id, title, category (string), content (text), file_path (nullable), sort_order (int), active (bool), timestamps.

- [ ] **Step 21: Create ResourcesController + view + admin CRUD**

GET `/dealer/resources` — grouped by category, searchable, FAQ accordion, file downloads.

- [ ] **Step 22: Commit**

```bash
git commit -m "feat: add dealer resources/FAQ page"
```

### Sub-task 4f: CSV/PDF export

- [ ] **Step 23: Create ExportController + install dompdf**

Run: `composer require barryvdh/laravel-dompdf`

Routes:
- GET `/dealer/orders/export/csv`
- GET `/dealer/orders/export/pdf`

- [ ] **Step 24: Commit**

```bash
git commit -m "feat: add CSV/PDF order export"
```

### Sub-task 4g: Register DealerServiceProvider

- [ ] **Step 25: Create DealerServiceProvider + EventServiceProvider**

Wire up all routes, views, listeners, migrations, mail, translations, payment/shipping config merges. Register `customer_bouncer` middleware on dealer routes where B2B ACL applies.

- [ ] **Step 26: Create DealerDemoSeeder**

Seeds: sample products (5-10) with customer group prices, one dealer per pricing tier, sample orders for testing. Used for local dev and smoke testing.

- [ ] **Step 27: Register provider, run full test suite**

Run: `php artisan test packages/Rydeen/`
Expected: All PASS

- [ ] **Step 28: Commit**

```bash
git commit -m "feat: complete Rydeen Dealer package with demo seeder"
```

---

## Task 5: Email Configuration (Resend)

**Files:**
- Modify: `.env.example`
- Modify: `composer.json`

- [ ] **Step 1: Install Resend driver**

Run: `composer require resend/resend-laravel`

- [ ] **Step 2: Update .env.example**

```env
MAIL_MAILER=resend
RESEND_API_KEY=
MAIL_FROM_ADDRESS=noreply@rydeenmobile.com
MAIL_FROM_NAME="Rydeen Mobile"
ADMIN_MAIL_ADDRESS=orders@rydeenmobile.com
```

- [ ] **Step 3: Commit**

```bash
git commit -m "feat: configure Resend email driver"
```

---

## Task 6: Railway Deployment

**Files:**
- Create: `deploy.sh`, `railway.json`, `nixpacks.toml`
- Modify: `.env.example`

- [ ] **Step 1: Create nixpacks.toml**

```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.pdo_mysql", "php82Extensions.redis", "php82Packages.composer", "nodejs_18"]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader", "npm install"]

[phases.build]
cmds = ["npm run build"]
```

- [ ] **Step 2: Create deploy.sh**

```bash
#!/bin/bash
set -e
echo "=== Railway Deploy ==="

# Clear build-phase cache (direct file removal, no PHP boot needed)
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/events.php

# Check if this is a first deploy by attempting a safe artisan command
# If it fails, we need to run migrations from scratch
php artisan migrate:status > /dev/null 2>&1
NEEDS_SEED=$?

if [ $NEEDS_SEED -ne 0 ]; then
    echo "First deploy — running migrations and seeding..."
    php artisan migrate --force
    php artisan db:seed --force
    php artisan b2b-suite:install
else
    echo "Running migrations..."
    php artisan migrate --force
fi

php artisan storage:link --force
touch storage/installed
php artisan optimize

echo "Starting server on port ${PORT:-8080}"
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
```

Note: `php artisan serve` is used for MVP deployment. Document this limitation — for production traffic, switch to FrankenPHP or Laravel Octane.

- [ ] **Step 3: Create railway.json**

```json
{
    "$schema": "https://railway.com/railway.schema.json",
    "build": { "builder": "NIXPACKS" },
    "deploy": {
        "startCommand": "bash deploy.sh",
        "healthcheckPath": "/dealer/login",
        "healthcheckTimeout": 120
    }
}
```

- [ ] **Step 4: Handle Bagisto fresh-deploy boot crash**

Bagisto's `Core::getCurrentChannelCode()` crashes if channels table is empty. Instead of modifying `packages/Webkul/Core/src/Core.php` directly, create a boot-safety service provider in `packages/Rydeen/Core/` that wraps the `core()` singleton with try-catch behavior during console commands. Alternatively, use a Concord model override for the Core class. If neither approach works cleanly, the deploy.sh already clears cached config and seeds before any artisan command that would trigger the channel lookup.

- [ ] **Step 5: Update .env.example for Railway**

Add Railway-specific vars: `FILESYSTEM_DISK=public`, `QUEUE_CONNECTION=redis`, `DEALER_DEVICE_TRUST_DAYS=30`.

- [ ] **Step 6: Commit**

```bash
git add deploy.sh railway.json nixpacks.toml .env.example
git commit -m "feat: add Railway deployment configuration"
```

---

## Task 7: Integration Testing & Smoke Test

- [ ] **Step 1: Run full Rydeen test suite**

Run: `php artisan test packages/Rydeen/`
Expected: All tests PASS

- [ ] **Step 2: Run demo seeder for local smoke test**

Run: `php artisan db:seed --class=Rydeen\\Dealer\\Database\\Seeders\\DealerDemoSeeder`

- [ ] **Step 3: Manual smoke test locally**

Start server, verify:
1. `/dealer/login` → Rydeen-branded login page
2. Submit email → code sent (check `storage/logs/laravel.log` if no Resend key)
3. Enter code → redirected to dashboard with KPIs
4. `/dealer/catalog` → products with tier pricing and status badges
5. Add to cart → side cart appears
6. `/dealer/order-review` → review page, place order
7. Order confirmation → email in log
8. `/dealer/orders` → history with reorder/print
9. `/dealer/resources` → FAQ page
10. `/admin` → manage dealers, promotions
11. `/customer/login` → redirects to `/dealer/login`

- [ ] **Step 4: Deploy to Railway**

```bash
railway up
```

Verify healthcheck passes, test `/dealer/login` on public URL.

- [ ] **Step 5: Final commit and push**

```bash
git add -A
git commit -m "feat: complete Rydeen dealer portal on Bagisto B2B Suite"
git push origin main
```
