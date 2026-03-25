<?php

use Illuminate\Support\Carbon;
use Rydeen\Pricing\Models\Promotion;
use Rydeen\Pricing\Models\PromotionItem;
use Rydeen\Pricing\Services\PriceResolver;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->resolver = app(PriceResolver::class);
});

it('returns group price when no promotions exist', function () {
    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('applies percentage discount', function () {
    Promotion::create([
        'name'   => '10% Off Everything',
        'type'   => 'percentage',
        'value'  => 10,
        'scope'  => 'all',
        'active' => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(90.00);
    expect($result['promo_name'])->toBe('10% Off Everything');
});

it('applies threshold discount when quantity meets minimum', function () {
    Promotion::create([
        'name'    => 'Bulk Discount',
        'type'    => 'threshold',
        'value'   => 15,
        'min_qty' => 10,
        'scope'   => 'all',
        'active'  => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
        qty: 10,
    );

    expect($result['price'])->toBe(85.00);
    expect($result['promo_name'])->toBe('Bulk Discount');
});

it('does not apply threshold discount when quantity is below minimum', function () {
    Promotion::create([
        'name'    => 'Bulk Discount',
        'type'    => 'threshold',
        'value'   => 15,
        'min_qty' => 10,
        'scope'   => 'all',
        'active'  => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
        qty: 5,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('applies timing discount when within date range', function () {
    Promotion::create([
        'name'      => 'Holiday Sale',
        'type'      => 'timing',
        'value'     => 20,
        'starts_at' => now()->subDay(),
        'ends_at'   => now()->addDay(),
        'scope'     => 'all',
        'active'    => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(80.00);
    expect($result['promo_name'])->toBe('Holiday Sale');
});

it('does not apply timing discount when before start date', function () {
    Promotion::create([
        'name'      => 'Future Sale',
        'type'      => 'timing',
        'value'     => 20,
        'starts_at' => now()->addWeek(),
        'ends_at'   => now()->addWeeks(2),
        'scope'     => 'all',
        'active'    => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('does not apply timing discount when after end date', function () {
    Promotion::create([
        'name'      => 'Expired Sale',
        'type'      => 'timing',
        'value'     => 20,
        'starts_at' => now()->subWeeks(2),
        'ends_at'   => now()->subWeek(),
        'scope'     => 'all',
        'active'    => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('applies sku level override price', function () {
    $productId = 9901;

    $promo = Promotion::create([
        'name'   => 'SKU Override',
        'type'   => 'sku_level',
        'value'  => 0,
        'scope'  => 'all',
        'active' => true,
    ]);

    PromotionItem::create([
        'promotion_id'  => $promo->id,
        'product_id'    => $productId,
        'override_price' => 59.99,
    ]);

    $result = $this->resolver->resolve(
        productId: $productId,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(59.99);
    expect($result['promo_name'])->toBe('SKU Override');
});

it('does not apply sku level override for different product', function () {
    $productId = 9902;
    $otherProductId = 9903;

    $promo = Promotion::create([
        'name'   => 'SKU Override',
        'type'   => 'sku_level',
        'value'  => 0,
        'scope'  => 'all',
        'active' => true,
    ]);

    PromotionItem::create([
        'promotion_id'  => $promo->id,
        'product_id'    => $productId,
        'override_price' => 59.99,
    ]);

    $result = $this->resolver->resolve(
        productId: $otherProductId,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('selects the best price when multiple promos apply', function () {
    Promotion::create([
        'name'   => '10% Off',
        'type'   => 'percentage',
        'value'  => 10,
        'scope'  => 'all',
        'active' => true,
    ]);

    Promotion::create([
        'name'   => '25% Off',
        'type'   => 'percentage',
        'value'  => 25,
        'scope'  => 'all',
        'active' => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(75.00);
    expect($result['promo_name'])->toBe('25% Off');
});

it('ignores inactive promotions', function () {
    Promotion::create([
        'name'   => 'Inactive Promo',
        'type'   => 'percentage',
        'value'  => 50,
        'scope'  => 'all',
        'active' => false,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('ignores promotions scoped to a different customer group', function () {
    Promotion::create([
        'name'     => 'Group 5 Only',
        'type'     => 'percentage',
        'value'    => 30,
        'scope'    => 'customer_group',
        'scope_id' => 5,
        'active'   => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});

it('applies promotions scoped to matching customer group', function () {
    Promotion::create([
        'name'     => 'MESA Dealers Special',
        'type'     => 'percentage',
        'value'    => 20,
        'scope'    => 'customer_group',
        'scope_id' => 3,
        'active'   => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 3,
    );

    expect($result['price'])->toBe(80.00);
    expect($result['promo_name'])->toBe('MESA Dealers Special');
});

it('applies promotions scoped to matching category', function () {
    Promotion::create([
        'name'     => 'Category Sale',
        'type'     => 'percentage',
        'value'    => 15,
        'scope'    => 'category',
        'scope_id' => 7,
        'active'   => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
        categoryIds: [7, 12],
    );

    expect($result['price'])->toBe(85.00);
    expect($result['promo_name'])->toBe('Category Sale');
});

it('ignores promotions scoped to non-matching category', function () {
    Promotion::create([
        'name'     => 'Category Sale',
        'type'     => 'percentage',
        'value'    => 15,
        'scope'    => 'category',
        'scope_id' => 7,
        'active'   => true,
    ]);

    $result = $this->resolver->resolve(
        productId: 1,
        groupPrice: 100.00,
        customerGroupId: 1,
        categoryIds: [3, 12],
    );

    expect($result['price'])->toBe(100.00);
    expect($result['promo_name'])->toBeNull();
});
