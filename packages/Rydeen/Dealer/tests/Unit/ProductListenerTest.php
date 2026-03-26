<?php

use Illuminate\Support\Facades\DB;
use Rydeen\Dealer\Listeners\ProductListener;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->listener = new ProductListener();
    $this->channelCode = DB::table('channels')->value('code') ?? 'default';
    $this->localeCode = DB::table('locales')->value('code') ?? 'en';
    $this->urlKeyAttributeId = DB::table('attributes')->where('code', 'url_key')->value('id');
    $this->nameAttributeId = DB::table('attributes')->where('code', 'name')->value('id');
});

afterEach(function () {
    if (isset($this->product)) {
        DB::table('product_attribute_values')->where('product_id', $this->product->id)->delete();
        DB::table('product_flat')->where('product_id', $this->product->id)->delete();
        DB::table('product_categories')->where('product_id', $this->product->id)->delete();
        DB::table('product_channels')->where('product_id', $this->product->id)->delete();
        DB::table('products')->where('id', $this->product->id)->delete();
    }
});

it('generates url_key from sku and name when url_key is empty', function () {
    $this->product = createTestProduct('TEST-001', 'Rydeen Backup Camera');

    $this->listener->afterSave($this->product);

    $urlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $this->urlKeyAttributeId)
        ->value('text_value');

    expect($urlKey)->toBe('test-001-rydeen-backup-camera');
});

it('falls back to sku only when name is empty', function () {
    $this->product = createTestProduct('TEST-002');

    $this->listener->afterSave($this->product);

    $urlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $this->urlKeyAttributeId)
        ->value('text_value');

    expect($urlKey)->toBe('test-002');
});

it('does not overwrite existing url_key', function () {
    $this->product = createTestProduct('TEST-003', 'Some Product');

    $channelCode = DB::table('channels')->value('code') ?? 'default';
    $localeCode = DB::table('locales')->value('code') ?? 'en';
    $urlKeyAttributeId = DB::table('attributes')->where('code', 'url_key')->value('id');

    // Manually set a url_key before calling the listener
    DB::table('product_attribute_values')->insert([
        'product_id'   => $this->product->id,
        'attribute_id' => $urlKeyAttributeId,
        'text_value'   => 'my-custom-slug',
        'channel'      => $channelCode,
        'locale'       => $localeCode,
        'unique_id'    => implode('|', [$channelCode, $localeCode, $this->product->id, $urlKeyAttributeId]),
    ]);

    $this->listener->afterSave($this->product);

    $urlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $urlKeyAttributeId)
        ->value('text_value');

    expect($urlKey)->toBe('my-custom-slug');
});

it('appends suffix when slug already exists for another product', function () {
    // Create first product — SKU "TEST-004" slugifies to "test-004"
    $this->product = createTestProduct('TEST-004', 'Duplicate Name');
    $this->listener->afterSave($this->product);

    // Create second product — SKU "TEST--004" also slugifies to "test-004" (duplicate hyphens collapse),
    // so both products produce the same base slug "test-004-duplicate-name".
    $secondProduct = createTestProduct('TEST--004', 'Duplicate Name');
    $this->listener->afterSave($secondProduct);

    $urlKeyAttributeId = DB::table('attributes')->where('code', 'url_key')->value('id');

    $firstUrlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $urlKeyAttributeId)
        ->value('text_value');

    $secondUrlKey = DB::table('product_attribute_values')
        ->where('product_id', $secondProduct->id)
        ->where('attribute_id', $urlKeyAttributeId)
        ->value('text_value');

    expect($firstUrlKey)->toBe('test-004-duplicate-name');
    expect($secondUrlKey)->toBe('test-004-duplicate-name-1');

    // Cleanup second product
    DB::table('product_attribute_values')->where('product_id', $secondProduct->id)->delete();
    DB::table('product_flat')->where('product_id', $secondProduct->id)->delete();
    DB::table('product_categories')->where('product_id', $secondProduct->id)->delete();
    DB::table('product_channels')->where('product_id', $secondProduct->id)->delete();
    DB::table('products')->where('id', $secondProduct->id)->delete();
});

it('handles unicode characters and diacriticals in names', function () {
    $this->product = createTestProduct('TEST-005', 'Cámara Retrovisora Electrónica');

    $this->listener->afterSave($this->product);

    $urlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $this->urlKeyAttributeId)
        ->value('text_value');

    expect($urlKey)->toBe('test-005-camara-retrovisora-electronica');
});

/**
 * Create a simple product with a name attribute value but no url_key.
 */
function createTestProduct(string $sku, ?string $name = null): Product
{
    $familyId = DB::table('attribute_families')->value('id') ?? 1;
    $channelId = DB::table('channels')->value('id') ?? 1;
    $channelCode = DB::table('channels')->value('code') ?? 'default';
    $localeCode = DB::table('locales')->value('code') ?? 'en';

    $productId = DB::table('products')->insertGetId([
        'type'                => 'simple',
        'sku'                 => $sku,
        'attribute_family_id' => $familyId,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    DB::table('product_channels')->insert([
        'product_id' => $productId,
        'channel_id' => $channelId,
    ]);

    if ($name !== null) {
        $nameAttributeId = DB::table('attributes')->where('code', 'name')->value('id');

        DB::table('product_attribute_values')->insert([
            'product_id'   => $productId,
            'attribute_id' => $nameAttributeId,
            'text_value'   => $name,
            'channel'      => $channelCode,
            'locale'       => $localeCode,
            'unique_id'    => implode('|', [$channelCode, $localeCode, $productId, $nameAttributeId]),
        ]);
    }

    return Product::find($productId);
}
