# PRD Gap Closure Design

**Date:** 2026-03-26
**Scope:** Close remaining gaps between the Rydeen Dealer Portal codebase and the PRD (received 2.21.2026).

## Deferred Items

The following PRD gaps are intentionally deferred and NOT part of this spec:

- **Login page UI** — PRD mockup shows passwordless "Continue with Email" entry; current page has email+password fields. Deferred to client handoff.
- **Side Cart Drawer** — PRD mockup shows slide-out cart panel overlaying catalog. Visual enhancement deferred.
- **SMS Verification** — PRD item 7 says "email or SMS code." Email-only at launch; SMS deferred until provider account is set up.

## Feature 1: Rep Role — Filtered Admin View

### Problem

The PRD requires 3 roles (Admin, Rep, Dealer). Reps currently exist only as an `assigned_rep_id` field on the customers table. There is no authorization restricting reps to their assigned dealers, no filtered views, and no distinct permissions. Any admin can see and manage all dealers.

### Design

Reps are admin users assigned a "Sales Rep" role via Bagisto's built-in role system. When logged in, all dealer and order queries are scoped to dealers where `assigned_rep_id` matches the rep's admin ID.

#### 1.1 Seeder

Add a "Sales Rep" role to `RydeenSeeder` with permissions limited to:

- `rydeen.dealers.view` — read-only dealer list and detail
- `rydeen.orders.view` — view orders for assigned dealers
- `rydeen.orders.approve` — approve orders
- `rydeen.orders.hold` — hold orders

Excluded from rep permissions: dealer approve/reject, rep assignment, impersonation, order cancel.

#### 1.2 ScopesForRep Trait

A reusable trait added to admin controllers:

```php
trait ScopesForRep
{
    protected function isRep(): bool
    {
        $admin = auth('admin')->user();
        return $admin && $admin->role && $admin->role->name === 'Sales Rep';
    }

    protected function repId(): ?int
    {
        return $this->isRep() ? auth('admin')->id() : null;
    }
}
```

#### 1.3 Controller Changes

**DealerApprovalController:**

- `index()` — When rep: `Customer::where('assigned_rep_id', $this->repId())->paginate(25)`. When full admin: unchanged.
- `view()` — When rep: verify `$dealer->assigned_rep_id === $this->repId()`, abort 403 otherwise.

**OrderApprovalController:**

- `index()` — When rep: add `->where('customers.assigned_rep_id', $this->repId())` to the join query.
- `view()` — When rep: verify the order's customer has `assigned_rep_id` matching the rep, abort 403 otherwise.

#### 1.4 View Changes

On admin dealer view (`admin/dealers/view.blade.php`), hide these buttons when the logged-in admin is a rep:

- Approve Dealer
- Reject Dealer
- Assign Rep
- Login as Dealer (Impersonate)

All other UI elements remain visible. Order approve/hold buttons remain available for reps.

#### 1.5 ACL Registration

Register `rydeen.dealers` and `rydeen.orders` permission keys in the Dealer package's ACL config so Bagisto's bouncer respects the permissions when checking admin access.

#### 1.6 Files Changed

- `packages/Rydeen/Core/src/Database/Seeders/RydeenSeeder.php` — add Sales Rep role
- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerApprovalController.php` — use ScopesForRep trait, scope queries
- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php` — use ScopesForRep trait, scope queries
- `packages/Rydeen/Dealer/src/Http/Traits/ScopesForRep.php` — new trait
- `packages/Rydeen/Dealer/src/Config/acl.php` — new ACL config file
- `packages/Rydeen/Dealer/src/Providers/DealerServiceProvider.php` — register ACL config
- `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php` — conditional button visibility

No new routes. No new pages. No migrations.

---

## Feature 2: Inventory Confirmation on Order Approval

### Problem

PRD section 4C states orders are "INVENTORY CONFIRMED and processed." The admin approve action currently sets order status to `processing` without checking stock levels.

### Design

When admin clicks "Approve," the system checks stock for every line item. Insufficient stock shows a warning; admin can override and approve anyway.

#### 2.1 Stock Check in OrderApprovalController::approve()

Before updating status:

1. Query `product_inventories` for each order item using `order_items.product_id` to join.
2. Compare inventory `qty` vs `qty_ordered` on each order item.
3. If all items have sufficient stock: approve immediately (status = `processing`).
4. If any item is short AND `confirm_override` is not set: redirect back with stock warnings in session flash data. Do not change order status.
5. If `confirm_override = 1` is present: skip stock check, approve the order.

#### 2.2 Override Mechanism

Two-step flow using Blade conditionals, no JavaScript required:

- First click (no override): runs stock check, flashes warnings, stays on page.
- When warnings are flashed, the "Approve" button changes text to "Approve Anyway" and the form includes `<input type="hidden" name="confirm_override" value="1">`.
- Second click (with override): controller sees `confirm_override`, approves without re-checking.

#### 2.3 Admin Order View Changes

When stock warnings are flashed, render a yellow warning banner in `admin/orders/view.blade.php`:

```
Stock Warning:
- {name} (SKU: {sku}): {available} available, {ordered} ordered
- ...
```

#### 2.4 Files Changed

- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php` — add stock check logic to `approve()`
- `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php` — stock warning banner and override button

No new migrations. Reads from Bagisto's existing `product_inventories` table.

---

## Feature 3: Ship-to Address Management

### Problem

PRD page 2 notes: "Additional ship-to addresses added within dealer profile or communicated to Rydeen associate." No address management exists in the dealer portal.

### Design

Dealers maintain an address book. New addresses require admin approval before use on orders. Admins approve addresses from the dealer detail view.

#### 3.1 Migration — `rydeen_dealer_addresses`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| customer_id | unsignedBigInteger | FK to customers.id |
| label | string(100) | e.g. "Warehouse", "Showroom" |
| first_name | string |  |
| last_name | string |  |
| company_name | string, nullable |  |
| address1 | string |  |
| address2 | string, nullable |  |
| city | string |  |
| state | string |  |
| postcode | string |  |
| country | string, default "US" |  |
| phone | string, nullable |  |
| is_approved | boolean, default false |  |
| is_default | boolean, default false |  |
| timestamps |  |  |

Index: `(customer_id, is_approved)`.

Additional migration: add nullable `dealer_address_id` (FK to `rydeen_dealer_addresses.id`, `SET NULL` on delete) to `orders` table.

#### 3.2 Model — DealerAddress

- BelongsTo Customer (as `dealer`)
- Scopes: `approved()`, `forDealer($id)`, `pending()`
- New addresses created with `is_approved = false`

#### 3.3 Dealer Portal — Address Book

New routes:

- `GET /dealer/addresses` — list all addresses with status badges (Approved / Pending Approval)
- `POST /dealer/addresses` — create new address (sets `is_approved = false`)
- `DELETE /dealer/addresses/{id}` — delete own address

No edit action. Dealers delete and re-create to avoid re-approval complexity.

New view: `shop/addresses/index.blade.php` — form + list, same styling as existing portal pages.

New nav link: "Addresses" added to header between "Orders" and "Resources".

#### 3.4 Order Review Integration

Add an address picker dropdown to `shop/order-review/index.blade.php`, above the existing Customer Contact widget:

- Dropdown shows only `is_approved = true` addresses, formatted as `{label}: {address1}, {city}, {state} {postcode}`
- If no approved addresses exist, show: "No approved shipping addresses. Add one in your Address Book." with a link.
- Selected address stored as hidden input `dealer_address_id`
- Address is NOT required to place an order — the picker is optional. Some dealers communicate addresses to reps directly per PRD.

`OrderController::placeOrder()` saves the selected `dealer_address_id` on the order record.

#### 3.5 Admin — Address Approval

On existing `admin/dealers/view.blade.php`, add a "Shipping Addresses" section below dealer info:

- Lists all addresses for that dealer
- Pending addresses get an "Approve" button
- Approved addresses show a green badge
- No reject action — admin leaves pending, dealer can delete and re-submit

New admin route: `POST /admin/rydeen/dealers/{id}/approve-address/{addressId}`

#### 3.6 Files Changed

- `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_000003_create_rydeen_dealer_addresses_table.php` — new table
- `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_000004_add_dealer_address_id_to_orders_table.php` — FK on orders
- `packages/Rydeen/Dealer/src/Models/DealerAddress.php` — new model
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/AddressController.php` — new dealer controller
- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerApprovalController.php` — add `approveAddress()` method
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php` — save `dealer_address_id` on order
- `packages/Rydeen/Dealer/src/Routes/shop.php` — address routes
- `packages/Rydeen/Dealer/src/Routes/admin.php` — approve-address route
- `packages/Rydeen/Dealer/src/Resources/views/shop/addresses/index.blade.php` — new address book page
- `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php` — address picker dropdown
- `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php` — addresses section
- `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php` — "Addresses" nav link
- `packages/Rydeen/Dealer/src/Resources/lang/en/app.php` — address-related translations

---

## Feature 4: Email Config Fix

### Problem

The default admin email is `orders@test.reform9.com`. PRD requires `orders@rydeenmobile.com`.

### Design

Change the default value in `packages/Rydeen/Core/src/Config/rydeen.php`:

```php
// Before
'admin_order_email' => env('ADMIN_MAIL_ADDRESS', 'orders@test.reform9.com'),

// After
'admin_order_email' => env('ADMIN_MAIL_ADDRESS', 'orders@rydeenmobile.com'),
```

Production `ADMIN_MAIL_ADDRESS` env var still takes precedence if set.

### Files Changed

- `packages/Rydeen/Core/src/Config/rydeen.php` — update default email

---

## Testing Strategy

All features should have Pest tests following existing patterns in `packages/Rydeen/*/tests/`:

- **Rep Role:** Feature test verifying rep sees only assigned dealers and orders; full admin sees all; rep cannot access approve/reject/impersonate actions.
- **Inventory Check:** Unit test for stock comparison logic. Feature test verifying warning is shown on insufficient stock, override works, and sufficient stock approves immediately.
- **Ship-to Addresses:** Feature test for CRUD lifecycle (create pending, admin approve, select on order). Test that unapproved addresses don't appear in picker.
- **Email Config:** No test needed — config value change.
