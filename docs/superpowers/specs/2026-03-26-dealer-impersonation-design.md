# Dealer Impersonation ("Login as Dealer")

**Date:** 2026-03-26
**Scope:** Admin/Rep can impersonate a dealer to view and act in the dealer portal on their behalf

## Problem

Reps assigned to dealers have no way to see the dealer portal from the dealer's perspective. When a dealer calls in with a phone order or needs support, the rep can't browse the catalog at the dealer's pricing tier, place orders on their behalf, or troubleshoot what the dealer sees.

## Solution

Session-based impersonation. An admin clicks "Login as Dealer" on the dealer detail view, gets logged into the dealer portal as that dealer, and can perform any action the dealer can. A persistent banner identifies the impersonation session. Orders placed during impersonation are tagged with an audit note. Clicking "Return to Admin" ends the impersonation and drops the admin back into the admin panel.

## Architecture

### 1. Starting Impersonation

- **Trigger:** "Login as Dealer" button on `/admin/rydeen/dealers/{id}` view
- **Visibility:** Only for verified, non-suspended dealers
- **Route:** `POST /admin/rydeen/dealers/{id}/impersonate`
- **Controller:** `ImpersonationController::start()`
  - Stores `impersonating_admin_id` (admin's ID) and `impersonating_dealer_id` (dealer's ID) in the web session
  - Logs admin into customer guard: `auth('customer')->login($dealer)`
  - Redirects to `/dealer/dashboard`
- The admin's `admin` guard session is unaffected — Laravel guards are independent

### 2. Impersonation Banner

- **Component:** `impersonation-banner.blade.php` included in master layout
- **Condition:** Renders only when `session('impersonating_admin_id')` is set
- **Content:** "You are viewing as **[Dealer Name]** — [Return to Admin]"
- **Styling:** Fixed-top amber/yellow bar that pushes page content down
- **Return link:** POST to `/dealer/impersonate/stop`

### 3. Audit Trail on Orders

- **Hook:** Existing `OrderListener::afterOrderCreated()` (fires on `checkout.order.save.after`)
- **Logic:** If `session('impersonating_admin_id')` is set, add a note to the order: "Order placed by [Admin Name] on behalf of [Dealer Name]"
- **Storage:** Uses the existing order notes field — no new DB columns

### 4. DeviceVerification Bypass

- **Middleware:** `DeviceVerification` currently checks `rydeen_device` cookie
- **Change:** If `session('impersonating_admin_id')` is set, skip device verification and allow the request through
- **Reason:** Admin won't have the dealer's trusted device cookie

### 5. Stopping Impersonation

- **Route:** `POST /dealer/impersonate/stop`
- **Controller:** `ImpersonationController::stop()`
  - Reads `impersonating_dealer_id` from session
  - `auth('customer')->logout()`
  - Clears both impersonation session keys
  - Redirects to `/admin/rydeen/dealers/{dealer_id}`
- Admin remains logged into `admin` guard — no re-authentication needed

## File Changes

All within `packages/Rydeen/`:

| File | Action |
|------|--------|
| `Dealer/src/Http/Controllers/Admin/ImpersonationController.php` | **New** — `start()` and `stop()` methods |
| `Dealer/src/Routes/admin.php` | **Update** — add `POST {id}/impersonate` route |
| `Dealer/src/Routes/shop.php` | **Update** — add `POST dealer/impersonate/stop` route |
| `Dealer/src/Resources/views/admin/dealers/view.blade.php` | **Update** — add "Login as Dealer" button |
| `Core/src/Resources/views/shop/components/impersonation-banner.blade.php` | **New** — amber banner component |
| `Core/src/Resources/views/shop/layouts/master.blade.php` | **Update** — include banner component |
| `Auth/src/Http/Middleware/DeviceVerification.php` | **Update** — skip check when impersonating |
| `Dealer/src/Listeners/OrderListener.php` | **Update** — add audit note when impersonating |
| `Dealer/src/Resources/lang/en/app.php` | **Update** — add translation strings |

## Constraints

- Zero new DB tables or columns — purely session-based
- No changes to existing dealer controllers — `auth('customer')->user()` returns the impersonated dealer naturally
- Admin guard session preserved independently
- Impersonation state is ephemeral — session expiry cleans up automatically
