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
