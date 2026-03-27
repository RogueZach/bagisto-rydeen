<?php

use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;

it('rep only sees assigned dealers in index', function () {
    $rep = createAdminWithRole(getOrCreateRepRole()->id, 'rep1');
    $assignedDealerId = createDealerAssignedTo($rep->id);
    $unassignedDealerId = createDealerAssignedTo(null);

    $assignedDealer = DB::table('customers')->find($assignedDealerId);
    $unassignedDealer = DB::table('customers')->find($unassignedDealerId);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.dealers.index'));

    $response->assertStatus(200);
    $response->assertSee($assignedDealer->first_name);
    $response->assertDontSee($unassignedDealer->first_name);

    // Cleanup
    DB::table('customers')->whereIn('id', [$assignedDealerId, $unassignedDealerId])->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('rep cannot view dealer not assigned to them', function () {
    $rep = createAdminWithRole(getOrCreateRepRole()->id, 'rep2');
    $unassignedDealerId = createDealerAssignedTo(null);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.dealers.view', $unassignedDealerId));

    $response->assertStatus(403);

    // Cleanup
    DB::table('customers')->where('id', $unassignedDealerId)->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('full admin sees all dealers in index', function () {
    $admin = getTestAdmin();
    // Create a real rep so the FK constraint is satisfied for the assigned dealer
    $rep = createAdminWithRole(getOrCreateRepRole()->id, 'rep-fa');
    $dealerA = createDealerAssignedTo($rep->id);
    $dealerB = createDealerAssignedTo(null);

    $dealerARecord = DB::table('customers')->find($dealerA);
    $dealerBRecord = DB::table('customers')->find($dealerB);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.rydeen.dealers.index'));

    $response->assertStatus(200);
    $response->assertSee($dealerARecord->first_name);
    $response->assertSee($dealerBRecord->first_name);

    // Cleanup
    DB::table('customers')->whereIn('id', [$dealerA, $dealerB])->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('rep only sees orders for assigned dealers', function () {
    $rep = createAdminWithRole(getOrCreateRepRole()->id, 'rep3');
    $assignedDealerId = createDealerAssignedTo($rep->id);
    $unassignedDealerId = createDealerAssignedTo(null);

    $assignedOrderId = createOrderForCustomer($assignedDealerId);
    $unassignedOrderId = createOrderForCustomer($unassignedDealerId);

    $assignedOrder = DB::table('orders')->find($assignedOrderId);
    $unassignedOrder = DB::table('orders')->find($unassignedOrderId);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.orders.index'));

    $response->assertStatus(200);
    $response->assertSee($assignedOrder->increment_id);
    $response->assertDontSee($unassignedOrder->increment_id);

    // Cleanup
    DB::table('orders')->whereIn('id', [$assignedOrderId, $unassignedOrderId])->delete();
    DB::table('customers')->whereIn('id', [$assignedDealerId, $unassignedDealerId])->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

it('rep cannot view order for unassigned dealer', function () {
    $rep = createAdminWithRole(getOrCreateRepRole()->id, 'rep4');
    $unassignedDealerId = createDealerAssignedTo(null);
    $orderId = createOrderForCustomer($unassignedDealerId);

    $response = $this->actingAs($rep, 'admin')
        ->get(route('admin.rydeen.orders.view', $orderId));

    $response->assertStatus(403);

    // Cleanup
    DB::table('orders')->where('id', $orderId)->delete();
    DB::table('customers')->where('id', $unassignedDealerId)->delete();
    DB::table('admins')->where('id', $rep->id)->delete();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

if (! function_exists('getOrCreateRepRole')) {
    function getOrCreateRepRole(): \Webkul\User\Models\Role
    {
        return \Webkul\User\Models\Role::firstOrCreate(
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
    function createAdminWithRole(int $roleId, string $slug): \Webkul\User\Models\Admin
    {
        $email = "rydeen-{$slug}-" . uniqid() . '@example.com';
        $id = DB::table('admins')->insertGetId([
            'name' => "Test {$slug}", 'email' => $email, 'password' => bcrypt('password'),
            'status' => 1, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return \Webkul\User\Models\Admin::find($id);
    }
}

if (! function_exists('createDealerAssignedTo')) {
    function createDealerAssignedTo(?int $repId): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId = DB::table('customer_groups')->value('id') ?? 1;
        $unique = uniqid();
        return DB::table('customers')->insertGetId([
            'first_name' => 'Dealer' . $unique, 'last_name' => 'Test',
            'email' => "dealer-{$unique}@example.com", 'password' => bcrypt('password'),
            'customer_group_id' => $groupId, 'channel_id' => $channelId,
            'is_verified' => 1, 'status' => 1, 'assigned_rep_id' => $repId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

if (! function_exists('createOrderForCustomer')) {
    function createOrderForCustomer(int $customerId): int
    {
        return DB::table('orders')->insertGetId([
            'increment_id' => 'TEST-' . uniqid(), 'status' => 'pending',
            'customer_id' => $customerId, 'is_guest' => 0,
            'customer_email' => 'test@example.com', 'grand_total' => 100.00,
            'base_grand_total' => 100.00, 'sub_total' => 100.00, 'base_sub_total' => 100.00,
            'total_qty_ordered' => 1, 'channel_name' => 'Default',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

if (! function_exists('getTestAdmin')) {
    function getTestAdmin(): \Webkul\User\Models\Admin
    {
        $admin = \Webkul\User\Models\Admin::where('email', 'rydeen-test-admin@example.com')->first();
        if (! $admin) {
            $roleId = DB::table('roles')->value('id') ?? 1;
            $id = DB::table('admins')->insertGetId([
                'name' => 'Test Admin', 'email' => 'rydeen-test-admin@example.com',
                'password' => bcrypt('password'), 'status' => 1, 'role_id' => $roleId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $admin = \Webkul\User\Models\Admin::find($id);
        }
        return $admin;
    }
}
