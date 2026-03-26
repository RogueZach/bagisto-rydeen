<?php

use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;

it('admin can view dealer list', function () {
    $admin = getTestAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.rydeen.dealers.index'));

    $response->assertStatus(200);
});

it('admin can view dealer detail', function () {
    $admin = getTestAdmin();
    $customerId = createPendingDealer();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.rydeen.dealers.view', $customerId));

    $response->assertStatus(200);

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can approve dealer', function () {
    $admin = getTestAdmin();
    $customerId = createPendingDealer();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.approve', $customerId));

    $response->assertRedirect();

    $customer = DB::table('customers')->find($customerId);
    expect($customer->is_verified)->toBe(1);
    expect($customer->approved_at)->not->toBeNull();

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can reject dealer', function () {
    $admin = getTestAdmin();
    $customerId = createPendingDealer();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.reject', $customerId));

    $response->assertRedirect();

    $customer = DB::table('customers')->find($customerId);
    expect($customer->is_suspended)->toBe(1);

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can update forecast level', function () {
    $admin = getTestAdmin();
    $customerId = createPendingDealer();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.update-forecast', $customerId), [
            'forecast_level' => 'Gold',
        ]);

    $response->assertRedirect();

    $customer = DB::table('customers')->find($customerId);
    expect($customer->forecast_level)->toBe('Gold');

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

if (! function_exists('getTestAdmin')) {
    /**
     * Get or create an admin user for testing.
     */
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
    /**
     * Create a pending (unverified) dealer customer.
     */
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
