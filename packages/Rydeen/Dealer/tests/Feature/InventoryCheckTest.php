<?php

use Illuminate\Support\Facades\DB;

it('approves order immediately when stock is sufficient', function () {
    $admin      = getTestAdmin();
    $customerId = createDealerForInventoryTest();
    $orderId    = createOrderWithProduct($customerId, 2);
    setProductStock($orderId, 10);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.orders.approve', $orderId));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $response->assertSessionMissing('stock_warnings');

    $status = DB::table('orders')->where('id', $orderId)->value('status');
    expect($status)->toBe('processing');

    cleanupInventoryTest($orderId, $customerId);
});

it('shows stock warnings when inventory is insufficient', function () {
    $admin      = getTestAdmin();
    $customerId = createDealerForInventoryTest();
    $orderId    = createOrderWithProduct($customerId, 5);
    setProductStock($orderId, 2);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.orders.approve', $orderId));

    $response->assertRedirect();
    $response->assertSessionHas('stock_warnings');

    $status = DB::table('orders')->where('id', $orderId)->value('status');
    expect($status)->toBe('pending');

    cleanupInventoryTest($orderId, $customerId);
});

it('approves order with override despite insufficient stock', function () {
    $admin      = getTestAdmin();
    $customerId = createDealerForInventoryTest();
    $orderId    = createOrderWithProduct($customerId, 5);
    setProductStock($orderId, 2);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.orders.approve', $orderId), [
            'confirm_override' => '1',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $status = DB::table('orders')->where('id', $orderId)->value('status');
    expect($status)->toBe('processing');

    cleanupInventoryTest($orderId, $customerId);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

if (! function_exists('getTestAdmin')) {
    function getTestAdmin(): \Webkul\User\Models\Admin
    {
        $admin = \Webkul\User\Models\Admin::where('email', 'rydeen-test-admin@example.com')->first();
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
            $admin = \Webkul\User\Models\Admin::find($id);
        }
        return $admin;
    }
}

if (! function_exists('createDealerForInventoryTest')) {
    function createDealerForInventoryTest(): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId   = DB::table('customer_groups')->value('id') ?? 1;
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
        $productId = DB::table('products')->value('id');
        if (! $productId) {
            $productId = DB::table('products')->insertGetId([
                'type'                 => 'simple',
                'sku'                  => 'TEST-INV-' . uniqid(),
                'attribute_family_id'  => DB::table('attribute_families')->value('id') ?? 1,
                'created_at'           => now(),
                'updated_at'           => now(),
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
            'order_id'   => $orderId,
            'product_id' => $productId,
            'sku'        => 'TEST-INV-SKU',
            'name'       => 'Test Inventory Product',
            'type'       => 'simple',
            'qty_ordered' => $qty,
            'price'      => 20.00,
            'base_price' => 20.00,
            'total'      => 20.00 * $qty,
            'base_total' => 20.00 * $qty,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $orderId;
    }
}

if (! function_exists('setProductStock')) {
    function setProductStock(int $orderId, int $qty): void
    {
        $item              = DB::table('order_items')->where('order_id', $orderId)->first();
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
