# PRD Gap Closure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the remaining PRD compliance gaps: rep role filtering, inventory confirmation on order approval, ship-to address management with admin approval, and email config fix.

**Architecture:** All changes stay inside `packages/Rydeen/`. Rep filtering uses a shared trait applied to existing admin controllers. Inventory check adds stock validation to the existing approve action. Ship-to addresses introduce a new model/migration/controller following the existing DealerContact pattern. No vendor modifications.

**Tech Stack:** PHP 8.2, Laravel, Bagisto v2.3.16, Pest, Tailwind CSS, Alpine.js

---

## File Map

### New Files
- `packages/Rydeen/Dealer/src/Http/Traits/ScopesForRep.php` — trait for rep detection and query scoping
- `packages/Rydeen/Dealer/src/Config/acl.php` — ACL permission registration
- `packages/Rydeen/Dealer/src/Models/DealerAddress.php` — address book model
- `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_100001_create_rydeen_dealer_addresses_table.php` — address table
- `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_100002_add_dealer_address_id_to_orders_table.php` — FK on orders
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/AddressController.php` — dealer address CRUD
- `packages/Rydeen/Dealer/src/Resources/views/shop/addresses/index.blade.php` — address book page
- `packages/Rydeen/Dealer/tests/Feature/RepScopeTest.php` — rep filtering tests
- `packages/Rydeen/Dealer/tests/Feature/InventoryCheckTest.php` — inventory confirmation tests
- `packages/Rydeen/Dealer/tests/Feature/DealerAddressTest.php` — address management tests

### Modified Files
- `packages/Rydeen/Core/src/Config/rydeen.php` — email default fix
- `packages/Rydeen/Core/src/Database/Seeders/RydeenSeeder.php` — add Sales Rep role
- `packages/Rydeen/Dealer/src/Providers/DealerServiceProvider.php` — register ACL config
- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerApprovalController.php` — rep scoping + approveAddress()
- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php` — rep scoping + stock check
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php` — save dealer_address_id
- `packages/Rydeen/Dealer/src/Routes/admin.php` — approve-address route
- `packages/Rydeen/Dealer/src/Routes/shop.php` — address routes
- `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php` — rep-conditional buttons + address section
- `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php` — stock warning + rep-conditional cancel
- `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php` — address picker
- `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php` — Addresses nav link
- `packages/Rydeen/Dealer/src/Resources/lang/en/app.php` — new translations

---

### Task 1: Email Config Fix

**Files:**
- Modify: `packages/Rydeen/Core/src/Config/rydeen.php:8`

- [ ] **Step 1: Update the default email**

In `packages/Rydeen/Core/src/Config/rydeen.php`, change line 8 from:

```php
    'admin_order_email'    => env('ADMIN_MAIL_ADDRESS', 'orders@test.reform9.com'),
```

to:

```php
    'admin_order_email'    => env('ADMIN_MAIL_ADDRESS', 'orders@rydeenmobile.com'),
```

- [ ] **Step 2: Commit**

```bash
git add packages/Rydeen/Core/src/Config/rydeen.php
git commit -m "fix: update default admin email to orders@rydeenmobile.com"
```

---

### Task 2: ScopesForRep Trait

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Traits/ScopesForRep.php`

- [ ] **Step 1: Create the trait**

```php
<?php

namespace Rydeen\Dealer\Http\Traits;

trait ScopesForRep
{
    /**
     * Check if the current admin user has the "Sales Rep" role.
     */
    protected function isRep(): bool
    {
        $admin = auth('admin')->user();

        return $admin && $admin->role && $admin->role->name === 'Sales Rep';
    }

    /**
     * Return the current admin's ID if they are a rep, null otherwise.
     */
    protected function repId(): ?int
    {
        return $this->isRep() ? auth('admin')->id() : null;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Traits/ScopesForRep.php
git commit -m "feat: add ScopesForRep trait for rep role filtering"
```

---

### Task 3: ACL Registration + Seeder

**Files:**
- Create: `packages/Rydeen/Dealer/src/Config/acl.php`
- Modify: `packages/Rydeen/Dealer/src/Providers/DealerServiceProvider.php:15`
- Modify: `packages/Rydeen/Core/src/Database/Seeders/RydeenSeeder.php`

- [ ] **Step 1: Create the ACL config**

Create `packages/Rydeen/Dealer/src/Config/acl.php`:

```php
<?php

return [
    [
        'key'   => 'rydeen',
        'name'  => 'Rydeen',
        'route' => 'admin.rydeen.dealers.index',
        'sort'  => 9,
    ],
    [
        'key'   => 'rydeen.dealers',
        'name'  => 'Dealers',
        'route' => 'admin.rydeen.dealers.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'rydeen.dealers.view',
        'name'  => 'View',
        'route' => 'admin.rydeen.dealers.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'rydeen.dealers.approve',
        'name'  => 'Approve / Reject',
        'route' => 'admin.rydeen.dealers.approve',
        'sort'  => 2,
    ],
    [
        'key'   => 'rydeen.dealers.impersonate',
        'name'  => 'Login as Dealer',
        'route' => 'admin.rydeen.dealers.impersonate',
        'sort'  => 3,
    ],
    [
        'key'   => 'rydeen.orders',
        'name'  => 'Dealer Orders',
        'route' => 'admin.rydeen.orders.index',
        'sort'  => 2,
    ],
    [
        'key'   => 'rydeen.orders.view',
        'name'  => 'View',
        'route' => 'admin.rydeen.orders.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'rydeen.orders.approve',
        'name'  => 'Approve',
        'route' => 'admin.rydeen.orders.approve',
        'sort'  => 2,
    ],
    [
        'key'   => 'rydeen.orders.hold',
        'name'  => 'Hold',
        'route' => 'admin.rydeen.orders.hold',
        'sort'  => 3,
    ],
    [
        'key'   => 'rydeen.orders.cancel',
        'name'  => 'Cancel',
        'route' => 'admin.rydeen.orders.cancel',
        'sort'  => 4,
    ],
];
```

- [ ] **Step 2: Register ACL in DealerServiceProvider**

In `packages/Rydeen/Dealer/src/Providers/DealerServiceProvider.php`, add this line inside the `register()` method, after the existing `mergeConfigFrom` calls (after line 17):

```php
        $this->mergeConfigFrom(__DIR__ . '/../Config/acl.php', 'acl');
```

- [ ] **Step 3: Add Sales Rep role to RydeenSeeder**

In `packages/Rydeen/Core/src/Database/Seeders/RydeenSeeder.php`, add a call in `run()` after `$this->seedAdminBranding();`:

```php
        $this->seedSalesRepRole();
```

Then add this method at the end of the class:

```php
    protected function seedSalesRepRole(): void
    {
        \Webkul\User\Models\Role::firstOrCreate(
            ['name' => 'Sales Rep'],
            [
                'description'     => 'Sales representative with access to assigned dealers and their orders only.',
                'permission_type' => 'custom',
                'permissions'     => [
                    'dashboard',
                    'rydeen',
                    'rydeen.dealers',
                    'rydeen.dealers.view',
                    'rydeen.orders',
                    'rydeen.orders.view',
                    'rydeen.orders.approve',
                    'rydeen.orders.hold',
                ],
            ]
        );
    }
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Config/acl.php packages/Rydeen/Dealer/src/Providers/DealerServiceProvider.php packages/Rydeen/Core/src/Database/Seeders/RydeenSeeder.php
git commit -m "feat: register Rydeen ACL permissions and seed Sales Rep role"
```

---

### Task 4: Rep Scoping in DealerApprovalController

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerApprovalController.php`
- Test: `packages/Rydeen/Dealer/tests/Feature/RepScopeTest.php`

- [ ] **Step 1: Write the failing tests**

Create `packages/Rydeen/Dealer/tests/Feature/RepScopeTest.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('rep only sees assigned dealers in index', function () {
    $repRole = getOrCreateRepRole();
    $rep = createAdminWithRole($repRole->id, 'rep-scope-test');

    $assignedDealerId = createDealerAssignedTo($rep->id);
    $unassignedDealerId = createDealerAssignedTo(null);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.dealers.index'));

    $response->assertStatus(200);
    $response->assertSee(Customer::find($assignedDealerId)->first_name);
    $response->assertDontSee(Customer::find($unassignedDealerId)->first_name);

    DB::table('customers')->whereIn('id', [$assignedDealerId, $unassignedDealerId])->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('rep cannot view dealer not assigned to them', function () {
    $repRole = getOrCreateRepRole();
    $rep = createAdminWithRole($repRole->id, 'rep-scope-view');

    $unassignedDealerId = createDealerAssignedTo(null);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.dealers.view', $unassignedDealerId));

    $response->assertStatus(403);

    DB::table('customers')->where('id', $unassignedDealerId)->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('full admin sees all dealers in index', function () {
    $admin = getTestAdmin();

    $dealer1 = createDealerAssignedTo(999);
    $dealer2 = createDealerAssignedTo(null);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.rydeen.dealers.index'));

    $response->assertStatus(200);
    $response->assertSee(Customer::find($dealer1)->first_name);
    $response->assertSee(Customer::find($dealer2)->first_name);

    DB::table('customers')->whereIn('id', [$dealer1, $dealer2])->delete();
});

it('rep only sees orders for assigned dealers', function () {
    $repRole = getOrCreateRepRole();
    $rep = createAdminWithRole($repRole->id, 'rep-order-scope');

    $assignedDealerId = createDealerAssignedTo($rep->id);
    $unassignedDealerId = createDealerAssignedTo(null);

    $assignedOrderId = createOrderForCustomer($assignedDealerId);
    $unassignedOrderId = createOrderForCustomer($unassignedDealerId);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.orders.index'));

    $response->assertStatus(200);
    $assignedOrder = DB::table('orders')->where('id', $assignedOrderId)->first();
    $unassignedOrder = DB::table('orders')->where('id', $unassignedOrderId)->first();
    $response->assertSee($assignedOrder->increment_id);
    $response->assertDontSee($unassignedOrder->increment_id);

    DB::table('order_items')->whereIn('order_id', [$assignedOrderId, $unassignedOrderId])->delete();
    DB::table('orders')->whereIn('id', [$assignedOrderId, $unassignedOrderId])->delete();
    DB::table('customers')->whereIn('id', [$assignedDealerId, $unassignedDealerId])->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('rep cannot view order for unassigned dealer', function () {
    $repRole = getOrCreateRepRole();
    $rep = createAdminWithRole($repRole->id, 'rep-order-view');

    $unassignedDealerId = createDealerAssignedTo(null);
    $orderId = createOrderForCustomer($unassignedDealerId);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.orders.view', $orderId));

    $response->assertStatus(403);

    DB::table('order_items')->where('order_id', $orderId)->delete();
    DB::table('orders')->where('id', $orderId)->delete();
    DB::table('customers')->where('id', $unassignedDealerId)->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

// --- Helpers ---

if (! function_exists('getOrCreateRepRole')) {
    function getOrCreateRepRole(): Role
    {
        return Role::firstOrCreate(
            ['name' => 'Sales Rep'],
            [
                'description'     => 'Sales representative role for testing',
                'permission_type' => 'custom',
                'permissions'     => ['dashboard', 'rydeen', 'rydeen.dealers', 'rydeen.dealers.view', 'rydeen.orders', 'rydeen.orders.view', 'rydeen.orders.approve', 'rydeen.orders.hold'],
            ]
        );
    }
}

if (! function_exists('createAdminWithRole')) {
    function createAdminWithRole(int $roleId, string $slug): Admin
    {
        $email = "rydeen-{$slug}-" . uniqid() . '@example.com';

        $id = DB::table('admins')->insertGetId([
            'name'       => "Test {$slug}",
            'email'      => $email,
            'password'   => bcrypt('password'),
            'status'     => 1,
            'role_id'    => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Admin::find($id);
    }
}

if (! function_exists('createDealerAssignedTo')) {
    function createDealerAssignedTo(?int $repId): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId = DB::table('customer_groups')->value('id') ?? 1;
        $unique = uniqid();

        return DB::table('customers')->insertGetId([
            'first_name'        => 'Dealer' . $unique,
            'last_name'         => 'Test',
            'email'             => "dealer-{$unique}@example.com",
            'password'          => bcrypt('password'),
            'customer_group_id' => $groupId,
            'channel_id'        => $channelId,
            'is_verified'       => 1,
            'status'            => 1,
            'assigned_rep_id'   => $repId,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}

if (! function_exists('createOrderForCustomer')) {
    function createOrderForCustomer(int $customerId): int
    {
        return DB::table('orders')->insertGetId([
            'increment_id'      => 'TEST-' . uniqid(),
            'status'            => 'pending',
            'customer_id'       => $customerId,
            'is_guest'          => 0,
            'customer_email'    => 'test@example.com',
            'grand_total'       => 100.00,
            'base_grand_total'  => 100.00,
            'sub_total'         => 100.00,
            'base_sub_total'    => 100.00,
            'total_qty_ordered' => 1,
            'channel_name'      => 'Default',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/RepScopeTest.php`

Expected: FAIL — controllers don't scope by rep yet.

- [ ] **Step 3: Add ScopesForRep to DealerApprovalController**

Replace the full contents of `packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerApprovalController.php`:

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Rydeen\Dealer\Http\Traits\ScopesForRep;
use Rydeen\Dealer\Mail\CompanyInvitationMail;
use Rydeen\Dealer\Mail\DealerApprovedMail;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;

class DealerApprovalController extends Controller
{
    use ScopesForRep;

    /**
     * List all dealers (scoped to assigned dealers for reps).
     */
    public function index()
    {
        $query = Customer::orderByDesc('created_at');

        if ($repId = $this->repId()) {
            $query->where('assigned_rep_id', $repId);
        }

        $dealers = $query->paginate(25);

        return view('rydeen-dealer::admin.dealers.index', compact('dealers'));
    }

    /**
     * Show dealer detail (rep can only view assigned dealers).
     */
    public function view(int $id)
    {
        $dealer = Customer::findOrFail($id);

        if ($repId = $this->repId()) {
            abort_unless($dealer->assigned_rep_id === $repId, 403);
        }

        $admins = Admin::orderBy('name')->get();

        return view('rydeen-dealer::admin.dealers.view', compact('dealer', 'admins'));
    }

    /**
     * Approve a dealer — set is_verified, approved_at, status.
     */
    public function approve(int $id)
    {
        $dealer = Customer::findOrFail($id);

        $dealer->is_verified = 1;
        $dealer->status = 1;
        $dealer->approved_at = now();
        $dealer->save();

        $loginUrl = route('dealer.login');

        try {
            Mail::to($dealer->email)->send(new DealerApprovedMail($dealer, $loginUrl));
        } catch (\Exception $e) {
            report($e);
        }

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.dealer-approved'));
    }

    /**
     * Reject/suspend a dealer.
     */
    public function reject(int $id)
    {
        $dealer = Customer::findOrFail($id);

        $dealer->is_suspended = 1;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.dealer-rejected'));
    }

    /**
     * Assign a sales rep to a dealer.
     */
    public function assignRep(Request $request, int $id)
    {
        $request->validate([
            'assigned_rep_id' => 'nullable|exists:admins,id',
        ]);

        $dealer = Customer::findOrFail($id);
        $dealer->assigned_rep_id = $request->assigned_rep_id;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.rep-assigned'));
    }

    /**
     * Update forecast level for a dealer.
     */
    public function updateForecastLevel(Request $request, int $id)
    {
        $request->validate([
            'forecast_level' => 'nullable|string|max:255',
        ]);

        $dealer = Customer::findOrFail($id);
        $dealer->forecast_level = $request->forecast_level;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.forecast-updated'));
    }

    /**
     * Resend invitation email with a password-set link.
     */
    public function resendInvitation(int $id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->type !== 'company') {
            return redirect()->back()->with('error', trans('rydeen-dealer::app.admin.invitation-not-company'));
        }

        $loginUrl = route('dealer.login');

        try {
            Mail::to($customer->email)->send(new CompanyInvitationMail($customer, $loginUrl));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', trans('rydeen-dealer::app.admin.invitation-send-failed'));
        }

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.invitation-sent'));
    }

    /**
     * Approve a dealer's shipping address.
     */
    public function approveAddress(int $dealerId, int $addressId)
    {
        $dealer = Customer::findOrFail($dealerId);

        \Illuminate\Support\Facades\DB::table('rydeen_dealer_addresses')
            ->where('id', $addressId)
            ->where('customer_id', $dealer->id)
            ->update(['is_approved' => true, 'updated_at' => now()]);

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.address-approved'));
    }
}
```

- [ ] **Step 4: Add ScopesForRep to OrderApprovalController**

Replace the full contents of `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php`:

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Rydeen\Dealer\Http\Traits\ScopesForRep;

class OrderApprovalController extends Controller
{
    use ScopesForRep;

    /**
     * List all orders (scoped to assigned dealers for reps).
     */
    public function index(Request $request)
    {
        $query = DB::table('orders')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'orders.id',
                'orders.increment_id',
                'orders.status',
                'orders.grand_total',
                'orders.total_qty_ordered',
                'orders.created_at',
                'customers.first_name',
                'customers.last_name',
                'customers.email'
            )
            ->orderByDesc('orders.created_at');

        if ($repId = $this->repId()) {
            $query->where('customers.assigned_rep_id', $repId);
        }

        if ($status = $request->get('status')) {
            $query->where('orders.status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('orders.increment_id', 'like', "%{$search}%")
                  ->orWhere('customers.first_name', 'like', "%{$search}%")
                  ->orWhere('customers.last_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        return view('rydeen-dealer::admin.orders.index', compact('orders'));
    }

    /**
     * Show a single order with items (rep can only view orders for assigned dealers).
     */
    public function view(int $id)
    {
        $order = DB::table('orders')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'orders.id',
                'orders.increment_id',
                'orders.status',
                'orders.grand_total',
                'orders.sub_total',
                'orders.shipping_amount',
                'orders.tax_amount',
                'orders.discount_amount',
                'orders.total_qty_ordered',
                'orders.created_at',
                'orders.customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.email',
                'customers.phone',
                'customers.assigned_rep_id',
                'orders.dealer_contact_id'
            )
            ->where('orders.id', $id)
            ->first();

        if (! $order) {
            abort(404);
        }

        if ($repId = $this->repId()) {
            abort_unless((int) $order->assigned_rep_id === $repId, 403);
        }

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->select('id', 'name', 'sku', 'qty_ordered', 'price', 'total', 'type', 'product_id')
            ->get();

        $contact = null;
        if ($order->dealer_contact_id) {
            $contact = DB::table('rydeen_dealer_contacts')->where('id', $order->dealer_contact_id)->first();
        }

        return view('rydeen-dealer::admin.orders.view', compact('order', 'items', 'contact'));
    }

    /**
     * Approve an order — check stock first, allow override.
     */
    public function approve(Request $request, int $id)
    {
        if (! $request->has('confirm_override')) {
            $items = DB::table('order_items')
                ->where('order_id', $id)
                ->select('name', 'sku', 'qty_ordered', 'product_id')
                ->get();

            $warnings = [];

            foreach ($items as $item) {
                $totalQty = (int) DB::table('product_inventories')
                    ->where('product_id', $item->product_id)
                    ->sum('qty');

                if ($totalQty < (int) $item->qty_ordered) {
                    $warnings[] = "{$item->name} (SKU: {$item->sku}): {$totalQty} available, {$item->qty_ordered} ordered";
                }
            }

            if (! empty($warnings)) {
                return redirect()->back()->with('stock_warnings', $warnings);
            }
        }

        DB::table('orders')->where('id', $id)->update([
            'status'     => 'processing',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order has been approved and set to processing.');
    }

    /**
     * Hold an order — set status to pending.
     */
    public function hold(int $id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'pending',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order has been placed on hold (pending).');
    }

    /**
     * Cancel an order.
     */
    public function cancel(int $id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'canceled',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order has been canceled.');
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/RepScopeTest.php`

Expected: All 5 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerApprovalController.php packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php packages/Rydeen/Dealer/tests/Feature/RepScopeTest.php
git commit -m "feat: scope admin dealer/order views by assigned rep"
```

---

### Task 5: Rep-Conditional View Buttons

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php:80-123`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php:164-201`

- [ ] **Step 1: Add rep-conditional wrappers on dealer view**

In `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php`, replace the Actions block (lines 79-124) — wrap the approve/reject/impersonate buttons and assign-rep section in `@unless` checks:

Replace:

```blade
    {{-- Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Approve / Reject --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                @lang('rydeen-dealer::app.admin.approval-actions')
            </h3>

            <div class="flex gap-3">
                @if (! $dealer->is_verified)
                    <form action="{{ route('admin.rydeen.dealers.approve', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="primary-button">
                            @lang('rydeen-dealer::app.admin.approve')
                        </button>
                    </form>
                @endif

                @if (! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.reject', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                            @lang('rydeen-dealer::app.admin.reject')
                        </button>
                    </form>
                @endif

                @if ($dealer->type === 'company' && $dealer->is_verified && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.resend-invitation', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded hover:bg-yellow-600 text-sm">
                            @lang('rydeen-dealer::app.admin.resend-invitation')
                        </button>
                    </form>
                @endif

                @if ($dealer->is_verified && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.impersonate', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm">
                            @lang('rydeen-dealer::app.admin.impersonate')
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Assign Rep --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
```

with:

```blade
    @php $isRep = auth('admin')->user()?->role?->name === 'Sales Rep'; @endphp

    {{-- Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @unless ($isRep)
        {{-- Approve / Reject --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                @lang('rydeen-dealer::app.admin.approval-actions')
            </h3>

            <div class="flex gap-3">
                @if (! $dealer->is_verified)
                    <form action="{{ route('admin.rydeen.dealers.approve', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="primary-button">
                            @lang('rydeen-dealer::app.admin.approve')
                        </button>
                    </form>
                @endif

                @if (! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.reject', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                            @lang('rydeen-dealer::app.admin.reject')
                        </button>
                    </form>
                @endif

                @if ($dealer->type === 'company' && $dealer->is_verified && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.resend-invitation', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded hover:bg-yellow-600 text-sm">
                            @lang('rydeen-dealer::app.admin.resend-invitation')
                        </button>
                    </form>
                @endif

                @if ($dealer->is_verified && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.impersonate', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm">
                            @lang('rydeen-dealer::app.admin.impersonate')
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endunless

        @unless ($isRep)
        {{-- Assign Rep --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
```

And add `@endunless` after the closing `</div>` of the Assign Rep section (after the existing line 152):

```blade
        </div>
        @endunless
```

- [ ] **Step 2: Hide cancel button for reps on order view**

In `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php`, add a rep check. Replace lines 164-202 (the Actions section):

```blade
    {{-- Action Buttons --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Actions
        </h2>

        @php $isRep = auth('admin')->user()?->role?->name === 'Sales Rep'; @endphp

        <div class="flex flex-wrap gap-3">
            @if ($order->status === 'pending')
                <form action="{{ route('admin.rydeen.orders.approve', $order->id) }}" method="POST">
                    @csrf
                    @if (session('stock_warnings'))
                        <input type="hidden" name="confirm_override" value="1">
                    @endif
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium">
                        {{ session('stock_warnings') ? 'Approve Anyway' : 'Approve Order' }}
                    </button>
                </form>
            @endif

            @if ($order->status !== 'pending')
                <form action="{{ route('admin.rydeen.orders.hold', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm font-medium">
                        Hold Order
                    </button>
                </form>
            @endif

            @unless ($isRep)
                @if ($order->status !== 'canceled')
                    <form action="{{ route('admin.rydeen.orders.cancel', $order->id) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium">
                            Cancel Order
                        </button>
                    </form>
                @endif
            @endunless
        </div>
    </div>
```

- [ ] **Step 3: Add stock warning banner to order view**

In the same file, add the stock warning banner right after the `@if (session('success'))` block (after line 21). Insert:

```blade
    @if (session('stock_warnings'))
        <div class="mb-4 p-4 rounded bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">
            <p class="font-semibold mb-2">Stock Warning — Insufficient inventory for the following items:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach (session('stock_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs">Click "Approve Anyway" to override and approve this order.</p>
        </div>
    @endif
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php
git commit -m "feat: hide admin-only actions for rep role, add stock warning UI"
```

---

### Task 6: Inventory Check Tests

**Files:**
- Create: `packages/Rydeen/Dealer/tests/Feature/InventoryCheckTest.php`

- [ ] **Step 1: Write the tests**

Create `packages/Rydeen/Dealer/tests/Feature/InventoryCheckTest.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;

it('approves order immediately when stock is sufficient', function () {
    $admin = getTestAdmin();
    $customerId = createDealerForInventoryTest();
    $orderId = createOrderWithProduct($customerId, 2);

    // Ensure product has enough stock
    setProductStock($orderId, 10);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.orders.approve', $orderId));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $response->assertSessionMissing('stock_warnings');

    $order = DB::table('orders')->where('id', $orderId)->first();
    expect($order->status)->toBe('processing');

    cleanupInventoryTest($orderId, $customerId);
});

it('shows stock warnings when inventory is insufficient', function () {
    $admin = getTestAdmin();
    $customerId = createDealerForInventoryTest();
    $orderId = createOrderWithProduct($customerId, 5);

    // Set stock lower than ordered
    setProductStock($orderId, 2);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.orders.approve', $orderId));

    $response->assertRedirect();
    $response->assertSessionHas('stock_warnings');

    // Order should NOT be approved
    $order = DB::table('orders')->where('id', $orderId)->first();
    expect($order->status)->toBe('pending');

    cleanupInventoryTest($orderId, $customerId);
});

it('approves order with override despite insufficient stock', function () {
    $admin = getTestAdmin();
    $customerId = createDealerForInventoryTest();
    $orderId = createOrderWithProduct($customerId, 5);

    setProductStock($orderId, 2);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.orders.approve', $orderId), [
            'confirm_override' => '1',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $order = DB::table('orders')->where('id', $orderId)->first();
    expect($order->status)->toBe('processing');

    cleanupInventoryTest($orderId, $customerId);
});

// --- Helpers ---

if (! function_exists('createDealerForInventoryTest')) {
    function createDealerForInventoryTest(): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId = DB::table('customer_groups')->value('id') ?? 1;

        return DB::table('customers')->insertGetId([
            'first_name'        => 'InvTest',
            'last_name'         => 'Dealer',
            'email'             => 'inv-' . uniqid() . '@example.com',
            'password'          => bcrypt('password'),
            'customer_group_id' => $groupId,
            'channel_id'        => $channelId,
            'is_verified'       => 1,
            'status'            => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}

if (! function_exists('createOrderWithProduct')) {
    function createOrderWithProduct(int $customerId, int $qty): int
    {
        // Find or create a product
        $productId = DB::table('products')->value('id');
        if (! $productId) {
            $productId = DB::table('products')->insertGetId([
                'type'           => 'simple',
                'sku'            => 'TEST-INV-' . uniqid(),
                'attribute_family_id' => DB::table('attribute_families')->value('id') ?? 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $orderId = DB::table('orders')->insertGetId([
            'increment_id'      => 'INV-' . uniqid(),
            'status'            => 'pending',
            'customer_id'       => $customerId,
            'is_guest'          => 0,
            'customer_email'    => 'test@example.com',
            'grand_total'       => 100.00,
            'base_grand_total'  => 100.00,
            'sub_total'         => 100.00,
            'base_sub_total'    => 100.00,
            'total_qty_ordered' => $qty,
            'channel_name'      => 'Default',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('order_items')->insert([
            'order_id'     => $orderId,
            'product_id'   => $productId,
            'sku'          => 'TEST-INV-SKU',
            'name'         => 'Test Inventory Product',
            'type'         => 'simple',
            'qty_ordered'  => $qty,
            'price'        => 20.00,
            'base_price'   => 20.00,
            'total'        => 20.00 * $qty,
            'base_total'   => 20.00 * $qty,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $orderId;
    }
}

if (! function_exists('setProductStock')) {
    function setProductStock(int $orderId, int $qty): void
    {
        $item = DB::table('order_items')->where('order_id', $orderId)->first();
        $inventorySourceId = DB::table('inventory_sources')->value('id') ?? 1;

        DB::table('product_inventories')->updateOrInsert(
            ['product_id' => $item->product_id, 'inventory_source_id' => $inventorySourceId, 'vendor_id' => 0],
            ['qty' => $qty]
        );
    }
}

if (! function_exists('cleanupInventoryTest')) {
    function cleanupInventoryTest(int $orderId, int $customerId): void
    {
        DB::table('order_items')->where('order_id', $orderId)->delete();
        DB::table('orders')->where('id', $orderId)->delete();
        DB::table('customers')->where('id', $customerId)->delete();
    }
}
```

- [ ] **Step 2: Run tests**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/InventoryCheckTest.php`

Expected: All 3 tests PASS (the controller logic was already added in Task 4).

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/tests/Feature/InventoryCheckTest.php
git commit -m "test: add inventory confirmation tests for order approval"
```

---

### Task 7: DealerAddress Migration + Model

**Files:**
- Create: `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_100001_create_rydeen_dealer_addresses_table.php`
- Create: `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_100002_add_dealer_address_id_to_orders_table.php`
- Create: `packages/Rydeen/Dealer/src/Models/DealerAddress.php`

- [ ] **Step 1: Create the addresses migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rydeen_dealer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('label', 100);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postcode');
            $table->string('country')->default('US');
            $table->string('phone')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_approved']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rydeen_dealer_addresses');
    }
};
```

- [ ] **Step 2: Create the orders FK migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('dealer_address_id')->nullable()->after('dealer_contact_id');
            $table->foreign('dealer_address_id')->references('id')->on('rydeen_dealer_addresses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dealer_address_id']);
            $table->dropColumn('dealer_address_id');
        });
    }
};
```

- [ ] **Step 3: Create the DealerAddress model**

```php
<?php

namespace Rydeen\Dealer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\Customer;

class DealerAddress extends Model
{
    protected $table = 'rydeen_dealer_addresses';

    protected $fillable = [
        'customer_id',
        'label',
        'first_name',
        'last_name',
        'company_name',
        'address1',
        'address2',
        'city',
        'state',
        'postcode',
        'country',
        'phone',
        'is_approved',
        'is_default',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_default'  => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForDealer($query, int $dealerId)
    {
        return $query->where('customer_id', $dealerId);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address1,
            $this->address2,
            $this->city,
            $this->state . ' ' . $this->postcode,
        ]);

        return implode(', ', $parts);
    }
}
```

- [ ] **Step 4: Run migration**

Run: `php artisan migrate`

Expected: Both migrations run successfully.

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_100001_create_rydeen_dealer_addresses_table.php packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_100002_add_dealer_address_id_to_orders_table.php packages/Rydeen/Dealer/src/Models/DealerAddress.php
git commit -m "feat: add DealerAddress model and migrations"
```

---

### Task 8: Address Controller + Routes + View

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/AddressController.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/shop/addresses/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/shop.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/admin.php`
- Modify: `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php`

- [ ] **Step 1: Create AddressController**

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rydeen\Dealer\Models\DealerAddress;

class AddressController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();

        $addresses = DealerAddress::forDealer($customer->id)
            ->orderByDesc('created_at')
            ->get();

        return view('rydeen-dealer::shop.addresses.index', compact('addresses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'      => 'required|string|max:100',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'address1'   => 'required|string|max:255',
            'address2'   => 'nullable|string|max:255',
            'city'       => 'required|string|max:255',
            'state'      => 'required|string|max:255',
            'postcode'   => 'required|string|max:20',
            'country'    => 'nullable|string|max:2',
            'phone'      => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
        ]);

        $customer = auth('customer')->user();

        DealerAddress::create([
            'customer_id'  => $customer->id,
            'label'        => $request->label,
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'company_name' => $request->company_name,
            'address1'     => $request->address1,
            'address2'     => $request->address2,
            'city'         => $request->city,
            'state'        => $request->state,
            'postcode'     => $request->postcode,
            'country'      => $request->country ?? 'US',
            'phone'        => $request->phone,
            'is_approved'  => false,
            'is_default'   => false,
        ]);

        return redirect()->route('dealer.addresses')
            ->with('success', trans('rydeen-dealer::app.shop.addresses.created'));
    }

    public function destroy(int $id)
    {
        $customer = auth('customer')->user();

        $address = DealerAddress::where('id', $id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $address->delete();

        return redirect()->route('dealer.addresses')
            ->with('success', trans('rydeen-dealer::app.shop.addresses.deleted'));
    }
}
```

- [ ] **Step 2: Create the address book view**

Create `packages/Rydeen/Dealer/src/Resources/views/shop/addresses/index.blade.php`:

```blade
@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.addresses.title'))

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">@lang('rydeen-dealer::app.shop.addresses.title')</h1>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Add Address Form --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">@lang('rydeen-dealer::app.shop.addresses.add-new')</h2>

        <form action="{{ route('dealer.addresses.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label *</label>
                    <input type="text" name="label" value="{{ old('label') }}" required
                           placeholder="e.g. Warehouse, Showroom"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                    <input type="text" name="address1" value="{{ old('address1') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                    <input type="text" name="address2" value="{{ old('address2') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                    <input type="text" name="city" value="{{ old('city') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                    <input type="text" name="state" value="{{ old('state') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Postcode *</label>
                    <input type="text" name="postcode" value="{{ old('postcode') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>

            @if ($errors->any())
                <div class="mt-3 p-3 rounded bg-red-50 text-red-700 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="mt-4">
                <button type="submit" class="bg-yellow-400 text-gray-900 px-6 py-2 rounded text-sm font-semibold hover:bg-yellow-500">
                    @lang('rydeen-dealer::app.shop.addresses.save')
                </button>
            </div>
        </form>
    </div>

    {{-- Address List --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">@lang('rydeen-dealer::app.shop.addresses.your-addresses')</h2>

        @if ($addresses->isEmpty())
            <p class="text-gray-500 text-sm">@lang('rydeen-dealer::app.shop.addresses.no-addresses')</p>
        @else
            <div class="space-y-4">
                @foreach ($addresses as $address)
                    <div class="border border-gray-200 rounded-lg p-4 flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-sm text-gray-900">{{ $address->label }}</span>
                                @if ($address->is_approved)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">Pending Approval</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-700">{{ $address->first_name }} {{ $address->last_name }}</p>
                            @if ($address->company_name)
                                <p class="text-sm text-gray-500">{{ $address->company_name }}</p>
                            @endif
                            <p class="text-sm text-gray-500">{{ $address->formatted_address }}</p>
                            @if ($address->phone)
                                <p class="text-sm text-gray-500">{{ $address->phone }}</p>
                            @endif
                        </div>
                        <form action="{{ route('dealer.addresses.destroy', $address->id) }}" method="POST"
                              onsubmit="return confirm('Delete this address?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
```

- [ ] **Step 3: Add dealer address routes**

In `packages/Rydeen/Dealer/src/Routes/shop.php`, add the import at the top (after the ContactController import):

```php
use Rydeen\Dealer\Http\Controllers\Shop\AddressController;
```

Then add these routes inside the middleware group, before the `// Impersonation stop` comment:

```php
    // Addresses
    Route::get('addresses', [AddressController::class, 'index'])->name('dealer.addresses');
    Route::post('addresses', [AddressController::class, 'store'])->name('dealer.addresses.store');
    Route::delete('addresses/{id}', [AddressController::class, 'destroy'])->name('dealer.addresses.destroy');
```

- [ ] **Step 4: Add admin approve-address route**

In `packages/Rydeen/Dealer/src/Routes/admin.php`, add this route inside the `admin/rydeen/dealers` group (after the impersonate route):

```php
    Route::post('{id}/approve-address/{addressId}', [DealerApprovalController::class, 'approveAddress'])->name('admin.rydeen.dealers.approve-address');
```

- [ ] **Step 5: Add Addresses nav link to header**

In `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php`, add a new nav link after the Orders link (after line 22):

```blade
                    <a href="/dealer/addresses"
                       class="text-sm font-medium {{ request()->is('dealer/addresses*') ? 'text-gray-900 border-b-2 border-yellow-400 pb-1' : 'text-gray-600 hover:text-gray-900' }}">
                        Addresses
                    </a>
```

And in the mobile nav section (after line 81):

```blade
                <a href="/dealer/addresses"
                   class="block px-3 py-2 rounded text-sm font-medium {{ request()->is('dealer/addresses*') ? 'bg-yellow-50 text-gray-900' : 'text-gray-600 hover:bg-gray-100' }}">
                    Addresses
                </a>
```

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Shop/AddressController.php packages/Rydeen/Dealer/src/Resources/views/shop/addresses/index.blade.php packages/Rydeen/Dealer/src/Routes/shop.php packages/Rydeen/Dealer/src/Routes/admin.php packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php
git commit -m "feat: add dealer address book with CRUD and admin approval route"
```

---

### Task 9: Address Picker on Order Review + Admin Address Section

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php:140-144,192-201`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php`

- [ ] **Step 1: Add address picker to order review**

In `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php`, add the address picker inside the form, before the Customer Contact section (before line 94 `{{-- Customer Contact --}}`). Insert:

```blade
                    {{-- Shipping Address --}}
                    <div class="bg-white rounded-lg shadow p-4 mt-4">
                        <h2 class="text-sm font-semibold text-gray-900 mb-3">Shipping Address</h2>

                        @php
                            $approvedAddresses = \Rydeen\Dealer\Models\DealerAddress::forDealer(auth('customer')->id())
                                ->approved()
                                ->get();
                        @endphp

                        @if ($approvedAddresses->isEmpty())
                            <p class="text-sm text-gray-500">
                                No approved shipping addresses.
                                <a href="{{ route('dealer.addresses') }}" class="text-gray-900 underline">Add one in your Address Book</a>.
                            </p>
                        @else
                            <select name="dealer_address_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— Select address (optional) —</option>
                                @foreach ($approvedAddresses as $addr)
                                    <option value="{{ $addr->id }}">
                                        {{ $addr->label }}: {{ $addr->formatted_address }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
```

- [ ] **Step 2: Update OrderController to save dealer_address_id**

In `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php`, update the `placeOrder` method's validation (line 142-144):

Replace:

```php
        $request->validate([
            'dealer_contact_id' => 'required|integer',
        ]);
```

with:

```php
        $request->validate([
            'dealer_contact_id'  => 'required|integer',
            'dealer_address_id'  => 'nullable|integer',
        ]);
```

Then after the line that ensures `dealer_contact_id` is set (after line 201), add:

```php
        // Save dealer address if selected
        if ($request->dealer_address_id) {
            \Illuminate\Support\Facades\DB::table('orders')
                ->where('id', $order->id)
                ->update(['dealer_address_id' => $request->dealer_address_id]);
        }
```

- [ ] **Step 3: Add address section to admin dealer view**

In `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php`, add a Shipping Addresses section before the closing `</x-admin::layouts>` tag:

```blade
    {{-- Shipping Addresses --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Shipping Addresses
        </h2>

        @php
            $addresses = \Rydeen\Dealer\Models\DealerAddress::forDealer($dealer->id)
                ->orderByDesc('created_at')
                ->get();
        @endphp

        @if ($addresses->isEmpty())
            <p class="text-sm text-gray-500">No shipping addresses on file.</p>
        @else
            <div class="space-y-3">
                @foreach ($addresses as $address)
                    <div class="flex items-start justify-between border border-gray-200 dark:border-gray-700 rounded p-3">
                        <div class="text-sm">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $address->label }}</span>
                                @if ($address->is_approved)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">Pending</span>
                                @endif
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $address->first_name }} {{ $address->last_name }}
                                @if ($address->company_name) &mdash; {{ $address->company_name }} @endif
                            </p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $address->formatted_address }}</p>
                        </div>
                        @if (! $address->is_approved)
                            <form action="{{ route('admin.rydeen.dealers.approve-address', [$dealer->id, $address->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                    Approve
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php
git commit -m "feat: add address picker to order review and admin address approval"
```

---

### Task 10: Translations + Address Tests

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`
- Create: `packages/Rydeen/Dealer/tests/Feature/DealerAddressTest.php`

- [ ] **Step 1: Add translations**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, add to the `'admin'` array (after `'impersonation-return'`):

```php
        'address-approved'         => 'Address has been approved.',
```

Add a new `'addresses'` key inside the `'shop'` array (after the `'resources'` block):

```php
        'addresses' => [
            'title'          => 'Shipping Addresses',
            'add-new'        => 'Add New Address',
            'save'           => 'Save Address',
            'your-addresses' => 'Your Addresses',
            'no-addresses'   => 'No shipping addresses yet.',
            'created'        => 'Address saved. It will be available for orders once approved by admin.',
            'deleted'        => 'Address has been deleted.',
        ],
```

- [ ] **Step 2: Write address tests**

Create `packages/Rydeen/Dealer/tests/Feature/DealerAddressTest.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Rydeen\Dealer\Models\DealerAddress;
use Webkul\Customer\Models\Customer;

it('dealer can create an address that defaults to unapproved', function () {
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);
    $uuid = app(\Rydeen\Auth\Services\AuthService::class)->createDeviceTrust($customer);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->post(route('dealer.addresses.store'), [
            'label'      => 'Warehouse',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'address1'   => '123 Main St',
            'city'       => 'Los Angeles',
            'state'      => 'CA',
            'postcode'   => '90001',
        ]);

    $response->assertRedirect(route('dealer.addresses'));

    $address = DealerAddress::where('customer_id', $customerId)->first();
    expect($address)->not->toBeNull();
    expect($address->is_approved)->toBeFalse();
    expect($address->label)->toBe('Warehouse');

    DealerAddress::where('customer_id', $customerId)->delete();
    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can approve a dealer address', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();

    $addressId = DB::table('rydeen_dealer_addresses')->insertGetId([
        'customer_id' => $customerId,
        'label'       => 'Test',
        'first_name'  => 'Jane',
        'last_name'   => 'Doe',
        'address1'    => '456 Oak Ave',
        'city'        => 'Torrance',
        'state'       => 'CA',
        'postcode'    => '90501',
        'country'     => 'US',
        'is_approved' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.approve-address', [$customerId, $addressId]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $address = DealerAddress::find($addressId);
    expect($address->is_approved)->toBeTrue();

    DB::table('rydeen_dealer_addresses')->where('id', $addressId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('unapproved addresses do not appear in order review picker', function () {
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);
    $uuid = app(\Rydeen\Auth\Services\AuthService::class)->createDeviceTrust($customer);

    DB::table('rydeen_dealer_addresses')->insert([
        'customer_id' => $customerId,
        'label'       => 'Pending Addr',
        'first_name'  => 'Test',
        'last_name'   => 'User',
        'address1'    => '789 Elm St',
        'city'        => 'Gardena',
        'state'       => 'CA',
        'postcode'    => '90247',
        'country'     => 'US',
        'is_approved' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->get(route('dealer.order-review'));

    $response->assertStatus(200);
    $response->assertDontSee('Pending Addr');
    $response->assertSee('No approved shipping addresses');

    DealerAddress::where('customer_id', $customerId)->delete();
    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('dealer can delete their own address', function () {
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);
    $uuid = app(\Rydeen\Auth\Services\AuthService::class)->createDeviceTrust($customer);

    $addressId = DB::table('rydeen_dealer_addresses')->insertGetId([
        'customer_id' => $customerId,
        'label'       => 'To Delete',
        'first_name'  => 'Del',
        'last_name'   => 'Test',
        'address1'    => '000 Gone Rd',
        'city'        => 'Nowhere',
        'state'       => 'CA',
        'postcode'    => '00000',
        'country'     => 'US',
        'is_approved' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->delete(route('dealer.addresses.destroy', $addressId));

    $response->assertRedirect(route('dealer.addresses'));
    expect(DealerAddress::find($addressId))->toBeNull();

    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});
```

- [ ] **Step 3: Run all tests**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/DealerAddressTest.php`

Expected: All 4 tests PASS.

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/lang/en/app.php packages/Rydeen/Dealer/tests/Feature/DealerAddressTest.php
git commit -m "feat: add address translations and tests"
```

---

### Task 11: Run Full Test Suite

- [ ] **Step 1: Run all Rydeen tests**

Run: `php artisan test packages/Rydeen/`

Expected: All tests pass. If any fail, fix them before proceeding.

- [ ] **Step 2: Run cache clear**

Run: `php artisan optimize:clear`

Expected: Caches cleared successfully.
