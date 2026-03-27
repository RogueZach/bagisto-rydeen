<?php

use Illuminate\Support\Facades\DB;
use Rydeen\Dealer\Models\DealerAddress;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;

it('dealer can create an address that defaults to unapproved', function () {
    $customerId = createVerifiedCompany();
    $customer   = Customer::find($customerId);

    $authService = app(\Rydeen\Auth\Services\AuthService::class);
    $uuid        = $authService->createDeviceTrust($customer);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->post(route('dealer.addresses.store'), [
            'label'      => 'Test Warehouse',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'address1'   => '123 Main St',
            'city'       => 'Los Angeles',
            'state'      => 'CA',
            'postcode'   => '90001',
        ]);

    $response->assertRedirect(route('dealer.addresses'));

    $address = DealerAddress::where('customer_id', $customerId)
        ->where('label', 'Test Warehouse')
        ->first();

    expect($address)->not->toBeNull();
    expect($address->is_approved)->toBeFalse();

    // Cleanup
    DB::table('rydeen_dealer_addresses')->where('customer_id', $customerId)->delete();
    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('admin can approve a dealer address', function () {
    $admin      = getTestAdmin();
    $customerId = createVerifiedCompany();

    $addressId = DB::table('rydeen_dealer_addresses')->insertGetId([
        'customer_id' => $customerId,
        'label'       => 'Approval Test Address',
        'first_name'  => 'Jane',
        'last_name'   => 'Smith',
        'address1'    => '456 Oak Ave',
        'city'        => 'San Diego',
        'state'       => 'CA',
        'postcode'    => '92101',
        'country'     => 'US',
        'is_approved' => false,
        'is_default'  => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.approve-address', [
            'id'        => $customerId,
            'addressId' => $addressId,
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $address = DealerAddress::find($addressId);
    expect($address->is_approved)->toBeTrue();

    // Cleanup
    DB::table('rydeen_dealer_addresses')->where('id', $addressId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('unapproved addresses do not appear in order review picker', function () {
    $customerId = createVerifiedCompany();
    $customer   = Customer::find($customerId);

    $authService = app(\Rydeen\Auth\Services\AuthService::class);
    $uuid        = $authService->createDeviceTrust($customer);

    $addressId = DB::table('rydeen_dealer_addresses')->insertGetId([
        'customer_id' => $customerId,
        'label'       => 'Hidden Warehouse',
        'first_name'  => 'Bob',
        'last_name'   => 'Jones',
        'address1'    => '789 Pine Rd',
        'city'        => 'Sacramento',
        'state'       => 'CA',
        'postcode'    => '94203',
        'country'     => 'US',
        'is_approved' => false,
        'is_default'  => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // Seed a minimal product and active cart with one item so the address picker section renders
    $familyId = DB::table('attribute_families')->value('id') ?? 1;
    $channelId = DB::table('channels')->value('id') ?? 1;

    $productId = DB::table('products')->insertGetId([
        'sku'                 => 'test-addr-sku-' . uniqid(),
        'type'                => 'simple',
        'attribute_family_id' => $familyId,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    $cartId = DB::table('cart')->insertGetId([
        'customer_id'           => $customerId,
        'channel_id'            => $channelId,
        'is_active'             => 1,
        'is_guest'              => 0,
        'items_count'           => 1,
        'items_qty'             => 1,
        'grand_total'           => 10.00,
        'base_grand_total'      => 10.00,
        'sub_total'             => 10.00,
        'base_sub_total'        => 10.00,
        'global_currency_code'  => 'USD',
        'base_currency_code'    => 'USD',
        'cart_currency_code'    => 'USD',
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);

    DB::table('cart_items')->insert([
        'cart_id'    => $cartId,
        'product_id' => $productId,
        'sku'        => 'test-addr-sku',
        'name'       => 'Test Product',
        'type'       => 'simple',
        'quantity'   => 1,
        'price'      => 10.00,
        'base_price' => 10.00,
        'total'      => 10.00,
        'base_total' => 10.00,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->get(route('dealer.order-review'));

    $response->assertStatus(200);
    $response->assertDontSee('Hidden Warehouse');
    $response->assertSee('No approved shipping addresses');

    // Cleanup
    DB::table('cart_items')->where('cart_id', $cartId)->delete();
    DB::table('cart')->where('id', $cartId)->delete();
    DB::table('products')->where('id', $productId)->delete();
    DB::table('rydeen_dealer_addresses')->where('id', $addressId)->delete();
    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('dealer can delete their own address', function () {
    $customerId = createVerifiedCompany();
    $customer   = Customer::find($customerId);

    $authService = app(\Rydeen\Auth\Services\AuthService::class);
    $uuid        = $authService->createDeviceTrust($customer);

    $addressId = DB::table('rydeen_dealer_addresses')->insertGetId([
        'customer_id' => $customerId,
        'label'       => 'Deletable Address',
        'first_name'  => 'Alice',
        'last_name'   => 'Walker',
        'address1'    => '321 Elm St',
        'city'        => 'Fresno',
        'state'       => 'CA',
        'postcode'    => '93650',
        'country'     => 'US',
        'is_approved' => false,
        'is_default'  => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->withCookie('rydeen_device', $uuid)
        ->delete(route('dealer.addresses.destroy', $addressId));

    $response->assertRedirect(route('dealer.addresses'));

    expect(DealerAddress::find($addressId))->toBeNull();

    // Cleanup
    DB::table('rydeen_trusted_devices')->where('customer_id', $customerId)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

if (! function_exists('createVerifiedCompany')) {
    function createVerifiedCompany(): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId   = DB::table('customer_groups')->value('id') ?? 1;

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
            $id     = DB::table('admins')->insertGetId([
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
