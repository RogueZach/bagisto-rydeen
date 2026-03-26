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

it('impersonating admin bypasses device verification', function () {
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

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can stop impersonating and return to admin', function () {
    $admin = getTestAdmin();
    $customerId = createVerifiedCompany();
    $customer = Customer::find($customerId);

    $this->actingAs($admin, 'admin');

    // Start impersonation via the route
    $this->post(route('admin.rydeen.dealers.impersonate', $customerId));

    // Now stop impersonation
    $response = $this->post(route('dealer.impersonate.stop'));

    $response->assertRedirect(route('admin.rydeen.dealers.view', $customerId));
    $response->assertSessionMissing('impersonating_admin_id');
    $response->assertSessionMissing('impersonating_dealer_id');

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

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

    $authService = app(\Rydeen\Auth\Services\AuthService::class);
    $uuid = $authService->createDeviceTrust($customer);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->get(route('dealer.dashboard'));

    $response->assertStatus(200);
    $response->assertDontSee('You are viewing as');

    // Cleanup
    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

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
