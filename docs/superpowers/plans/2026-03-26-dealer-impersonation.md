# Dealer Impersonation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow admins/reps to impersonate dealers — view the dealer portal as them and place orders on their behalf with an audit trail.

**Architecture:** Session-based impersonation using Laravel's multi-guard auth. Admin logs dealer into the `customer` guard while preserving their own `admin` guard session. A persistent banner shows during impersonation. Orders placed are tagged with an audit note via the existing OrderListener.

**Tech Stack:** Laravel Auth Guards, Session, Blade Components, Pest Tests

---

### Task 1: ImpersonationController (start & stop)

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Controllers/Admin/ImpersonationController.php`

- [ ] **Step 1: Write the test**

Create `packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;

it('admin can start impersonating a verified dealer', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.impersonate', $customerId));

    $response->assertRedirect(route('dealer.dashboard'));
    $response->assertSessionHas('impersonating_admin_id', $admin->id);
    $response->assertSessionHas('impersonating_dealer_id', $customerId);

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin cannot impersonate an unverified dealer', function () {
    $admin = getTestAdmin();
    $customerId = createPendingDealer();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.impersonate', $customerId));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $response->assertSessionMissing('impersonating_admin_id');

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can stop impersonating and return to admin', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);

    // Start impersonation
    $this->actingAs($admin, 'admin')
        ->withSession([
            'impersonating_admin_id' => $admin->id,
            'impersonating_dealer_id' => $customerId,
        ])
        ->actingAs($customer, 'customer');

    $response = $this->post(route('dealer.impersonate.stop'));

    $response->assertRedirect(route('admin.rydeen.dealers.view', $customerId));
    $response->assertSessionMissing('impersonating_admin_id');
    $response->assertSessionMissing('impersonating_dealer_id');

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

/**
 * Create a verified company customer for testing.
 */
if (! function_exists('createVerifiedCompany')) {
    function createVerifiedCompany(): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId = DB::table('customer_groups')->value('id') ?? 1;

        return DB::table('customers')->insertGetId([
            'first_name'        => 'Verified',
            'last_name'         => 'Company',
            'email'             => 'verified-' . uniqid() . '@example.com',
            'password'          => bcrypt('password'),
            'type'              => 'company',
            'customer_group_id' => $groupId,
            'channel_id'        => $channelId,
            'is_verified'       => 1,
            'status'            => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}

if (! function_exists('getTestAdmin')) {
    function getTestAdmin(): Admin
    {
        $admin = Admin::where('email', 'rydeen-test-admin@example.com')->first();

        if (! $admin) {
            $roleId = DB::table('roles')->value('id') ?? 1;
            $id = DB::table('admins')->insertGetId([
                'name'       => 'Test Admin',
                'email'      => 'rydeen-test-admin@example.com',
                'password'   => bcrypt('password'),
                'status'     => 1,
                'role_id'    => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $admin = Admin::find($id);
        }

        return $admin;
    }
}

if (! function_exists('createPendingDealer')) {
    function createPendingDealer(): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId = DB::table('customer_groups')->value('id') ?? 1;

        return DB::table('customers')->insertGetId([
            'first_name'        => 'Pending',
            'last_name'         => 'Dealer',
            'email'             => 'pending-' . uniqid() . '@example.com',
            'password'          => bcrypt('password'),
            'type'              => 'user',
            'customer_group_id' => $groupId,
            'channel_id'        => $channelId,
            'is_verified'       => 0,
            'status'            => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`
Expected: FAIL — route `admin.rydeen.dealers.impersonate` not defined

- [ ] **Step 3: Create the controller**

Create `packages/Rydeen/Dealer/src/Http/Controllers/Admin/ImpersonationController.php`:

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\Customer\Models\Customer;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a dealer.
     */
    public function start(int $id)
    {
        $dealer = Customer::findOrFail($id);

        if (! $dealer->is_verified || $dealer->is_suspended) {
            return redirect()->back()->with('error', trans('rydeen-dealer::app.admin.impersonate-not-allowed'));
        }

        $admin = auth('admin')->user();

        session([
            'impersonating_admin_id'  => $admin->id,
            'impersonating_dealer_id' => $dealer->id,
        ]);

        auth('customer')->login($dealer);

        return redirect()->route('dealer.dashboard');
    }

    /**
     * Stop impersonating and return to admin.
     */
    public function stop()
    {
        $dealerId = session('impersonating_dealer_id');

        auth('customer')->logout();

        session()->forget(['impersonating_admin_id', 'impersonating_dealer_id']);

        return redirect()->route('admin.rydeen.dealers.view', $dealerId);
    }
}
```

- [ ] **Step 4: Add the routes**

In `packages/Rydeen/Dealer/src/Routes/admin.php`, add inside the dealers route group (after the `resend-invitation` route on line 14), and add the import:

```php
use Rydeen\Dealer\Http\Controllers\Admin\ImpersonationController;
```

Add route inside the dealers group:

```php
Route::post('{id}/impersonate', [ImpersonationController::class, 'start'])->name('admin.rydeen.dealers.impersonate');
```

In `packages/Rydeen/Dealer/src/Routes/shop.php`, add inside the dealer route group (after the resources route on line 41):

```php
use Rydeen\Dealer\Http\Controllers\Admin\ImpersonationController;
```

Add route:

```php
// Impersonation stop
Route::post('impersonate/stop', [ImpersonationController::class, 'stop'])->name('dealer.impersonate.stop');
```

- [ ] **Step 5: Add translation strings**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, add after `'resend-invitation'` (line 42):

```php
'impersonate'              => 'Login as Dealer',
'impersonate-not-allowed'  => 'Cannot impersonate this dealer. They must be verified and not suspended.',
'impersonation-banner'     => 'You are viewing as',
'impersonation-return'     => 'Return to Admin',
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`
Expected: PASS (all 3 tests)

- [ ] **Step 7: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Admin/ImpersonationController.php \
       packages/Rydeen/Dealer/src/Routes/admin.php \
       packages/Rydeen/Dealer/src/Routes/shop.php \
       packages/Rydeen/Dealer/src/Resources/lang/en/app.php \
       packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php
git commit -m "feat: add dealer impersonation controller with start/stop routes"
```

---

### Task 2: DeviceVerification Bypass for Impersonation

**Files:**
- Modify: `packages/Rydeen/Auth/src/Http/Middleware/DeviceVerification.php`

- [ ] **Step 1: Write the test**

Add to `packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`:

```php
it('impersonating admin bypasses device verification', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);

    // Simulate impersonation session without device cookie
    $response = $this->actingAs($customer, 'customer')
        ->withSession([
            'impersonating_admin_id'  => $admin->id,
            'impersonating_dealer_id' => $customerId,
        ])
        ->get(route('dealer.dashboard'));

    $response->assertStatus(200);

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="bypasses device verification"`
Expected: FAIL — redirects to login (no trusted device cookie)

- [ ] **Step 3: Update DeviceVerification middleware**

Replace `packages/Rydeen/Auth/src/Http/Middleware/DeviceVerification.php`:

```php
<?php

namespace Rydeen\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Rydeen\Auth\Services\AuthService;
use Symfony\Component\HttpFoundation\Response;

class DeviceVerification
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Handle an incoming request.
     *
     * Checks that the customer is authenticated and that the device cookie
     * maps to a valid (non-expired) trusted device record.
     * Skips device check when an admin is impersonating the dealer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return redirect()->route('dealer.login');
        }

        // Skip device verification during impersonation
        if (session('impersonating_admin_id')) {
            return $next($request);
        }

        $uuid = $request->cookie('rydeen_device');

        if ($this->authService->isDeviceTrusted($customer, $uuid)) {
            return $next($request);
        }

        return redirect()->route('dealer.login');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="bypasses device verification"`
Expected: PASS

- [ ] **Step 5: Run all auth tests to check for regressions**

Run: `php artisan test packages/Rydeen/Auth/`
Expected: All tests pass

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Auth/src/Http/Middleware/DeviceVerification.php \
       packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php
git commit -m "feat: bypass device verification during admin impersonation"
```

---

### Task 3: Impersonation Banner

**Files:**
- Create: `packages/Rydeen/Core/src/Resources/views/shop/components/impersonation-banner.blade.php`
- Modify: `packages/Rydeen/Core/src/Resources/views/shop/layouts/master.blade.php`

- [ ] **Step 1: Write the test**

Add to `packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`:

```php
it('shows impersonation banner when impersonating', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);

    $response = $this->actingAs($customer, 'customer')
        ->withSession([
            'impersonating_admin_id'  => $admin->id,
            'impersonating_dealer_id' => $customerId,
        ])
        ->get(route('dealer.dashboard'));

    $response->assertStatus(200);
    $response->assertSee('You are viewing as');
    $response->assertSee('Return to Admin');
    $response->assertSee($customer->first_name);

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('does not show impersonation banner for normal dealers', function () {
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);

    // Create a trusted device so the middleware lets us through
    $authService = app(\Rydeen\Auth\Services\AuthService::class);
    $uuid = $authService->createDeviceTrust($customer);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->get(route('dealer.dashboard'));

    $response->assertStatus(200);
    $response->assertDontSee('You are viewing as');

    // Cleanup
    DB::table('trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="shows impersonation banner"`
Expected: FAIL — "You are viewing as" not found

- [ ] **Step 3: Create the banner component**

Create `packages/Rydeen/Core/src/Resources/views/shop/components/impersonation-banner.blade.php`:

```blade
@if (session('impersonating_admin_id'))
    <div class="bg-amber-400 text-amber-900 px-4 py-2 text-center text-sm font-medium flex items-center justify-center gap-3">
        <span>
            @lang('rydeen-dealer::app.admin.impersonation-banner')
            <strong>{{ auth('customer')->user()?->first_name }} {{ auth('customer')->user()?->last_name }}</strong>
        </span>
        <form action="{{ route('dealer.impersonate.stop') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="underline font-bold hover:text-amber-800">
                @lang('rydeen-dealer::app.admin.impersonation-return')
            </button>
        </form>
    </div>
@endif
```

- [ ] **Step 4: Include the banner in the master layout**

In `packages/Rydeen/Core/src/Resources/views/shop/layouts/master.blade.php`, add after the opening `<body>` tag (line 17) and before the header include (line 19):

```blade
    @include('rydeen::shop.components.impersonation-banner')
```

The body section should now read:

```blade
<body class="min-h-screen flex flex-col bg-gray-50 text-gray-900 antialiased">

    @include('rydeen::shop.components.impersonation-banner')

    @include('rydeen::shop.components.header')
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="banner"`
Expected: PASS (both banner tests)

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Core/src/Resources/views/shop/components/impersonation-banner.blade.php \
       packages/Rydeen/Core/src/Resources/views/shop/layouts/master.blade.php \
       packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php
git commit -m "feat: add impersonation banner to dealer portal"
```

---

### Task 4: "Login as Dealer" Button in Admin View

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php`

- [ ] **Step 1: Write the test**

Add to `packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`:

```php
it('admin dealer view shows impersonate button for verified dealers', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.rydeen.dealers.view', $customerId));

    $response->assertStatus(200);
    $response->assertSee('Login as Dealer');

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin dealer view hides impersonate button for unverified dealers', function () {
    $admin = getTestAdmin();
    $customerId = createPendingDealer();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.rydeen.dealers.view', $customerId));

    $response->assertStatus(200);
    $response->assertDontSee('Login as Dealer');

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="impersonate button"`
Expected: FAIL — "Login as Dealer" not found

- [ ] **Step 3: Add the button to the dealer view**

In `packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php`, add after the resend invitation form `@endif` (line 113) and before the closing `</div>` of the flex gap-3 container:

```blade
                @if ($dealer->is_verified && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.impersonate', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm">
                            @lang('rydeen-dealer::app.admin.impersonate')
                        </button>
                    </form>
                @endif
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="impersonate button"`
Expected: PASS (both tests)

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/admin/dealers/view.blade.php \
       packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php
git commit -m "feat: add Login as Dealer button to admin dealer view"
```

---

### Task 5: Order Audit Trail During Impersonation

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Listeners/OrderListener.php`

- [ ] **Step 1: Write the test**

Add to `packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`:

```php
use Rydeen\Dealer\Listeners\OrderListener;

it('adds audit note to order when placed during impersonation', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();

    // Create a minimal mock order
    $order = (object) [
        'id'    => 999,
        'notes' => null,
    ];

    // Simulate impersonation session
    session([
        'impersonating_admin_id'  => $admin->id,
        'impersonating_dealer_id' => $customerId,
    ]);

    $listener = new OrderListener();

    // We need to mock Mail to prevent actual sends
    \Illuminate\Support\Facades\Mail::fake();

    $listener->afterOrderCreated($order);

    // Verify the note was set
    expect($order->notes)->toContain('Order placed by');
    expect($order->notes)->toContain($admin->name);

    // Cleanup
    session()->forget(['impersonating_admin_id', 'impersonating_dealer_id']);
    DB::table('customers')->where('id', $customerId)->delete();
});

it('does not add audit note for normal orders', function () {
    $order = (object) [
        'id'    => 999,
        'notes' => null,
    ];

    // No impersonation session
    session()->forget('impersonating_admin_id');

    \Illuminate\Support\Facades\Mail::fake();

    $listener = new OrderListener();
    $listener->afterOrderCreated($order);

    expect($order->notes)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="audit note"`
Expected: FAIL — notes is still null

- [ ] **Step 3: Update OrderListener with audit trail**

Replace `packages/Rydeen/Dealer/src/Listeners/OrderListener.php`:

```php
<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\User\Models\Admin;
use Rydeen\Dealer\Mail\OrderConfirmationMail;
use Rydeen\Dealer\Mail\OrderSubmittedMail;

class OrderListener
{
    /**
     * Handle the event after an order is created.
     */
    public function afterOrderCreated($order): void
    {
        // Add audit note if order was placed during impersonation
        $this->addImpersonationAuditNote($order);

        // Send notification to admin
        try {
            $adminEmail = config('rydeen.admin_order_email', 'orders@test.reform9.com');
            Mail::to($adminEmail)->send(new OrderSubmittedMail($order));
        } catch (\Exception $e) {
            report($e);
        }

        // Send confirmation to dealer
        try {
            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Tag the order with an audit note when placed by an impersonating admin.
     */
    protected function addImpersonationAuditNote($order): void
    {
        $adminId = session('impersonating_admin_id');

        if (! $adminId) {
            return;
        }

        $admin = Admin::find($adminId);
        $dealer = auth('customer')->user();

        $auditNote = sprintf(
            'Order placed by %s on behalf of %s %s',
            $admin?->name ?? 'Admin #' . $adminId,
            $dealer?->first_name ?? '',
            $dealer?->last_name ?? ''
        );

        $existingNotes = $order->notes;
        $order->notes = $existingNotes
            ? $existingNotes . "\n\n" . $auditNote
            : $auditNote;

        if (method_exists($order, 'save')) {
            $order->save();
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php --filter="audit note"`
Expected: PASS (both tests)

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Listeners/OrderListener.php \
       packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php
git commit -m "feat: add audit trail for orders placed during impersonation"
```

---

### Task 6: Full Test Suite Verification

**Files:** None (verification only)

- [ ] **Step 1: Run all impersonation tests**

Run: `php artisan test packages/Rydeen/Dealer/tests/Feature/ImpersonationTest.php`
Expected: All 9 tests PASS

- [ ] **Step 2: Run all Rydeen tests**

Run: `php artisan test packages/Rydeen/`
Expected: All tests pass — no regressions in Auth, Dealer, or Pricing packages

- [ ] **Step 3: Clear caches**

Run: `php artisan optimize:clear`
Expected: All caches cleared

- [ ] **Step 4: Final commit if fixes needed**

Only if adjustments were required:

```bash
git add -A
git commit -m "fix: address test suite issues from impersonation feature"
```
