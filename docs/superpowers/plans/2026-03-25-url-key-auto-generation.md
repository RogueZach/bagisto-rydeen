# URL Key Auto-Generation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auto-generate product URL keys (format: `{sku}-{slugified-name}`) server-side so the dealer catalog always has working product links, even when products are created without manually entering a URL key.

**Architecture:** An event listener in the Rydeen Dealer package hooks into Bagisto's `catalog.product.create.after` and `catalog.product.update.after` events. When a product is saved without a `url_key`, the listener generates one from the SKU and product name, ensures uniqueness, and persists it to `product_attribute_values`. Existing URL keys are never overwritten.

**Tech Stack:** PHP 8.2, Laravel (Bagisto 2.3.16), Pest testing framework

**Spec:** `docs/superpowers/specs/2026-03-25-url-key-auto-generation-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `packages/Rydeen/Dealer/src/Listeners/ProductListener.php` | Create | Event listener: generates and persists URL key when empty |
| `packages/Rydeen/Dealer/src/Providers/EventServiceProvider.php` | Modify | Register product event bindings |
| `packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php` | Create | Unit tests for URL key generation logic |

---

### Task 1: Scaffold Test File and Write First Failing Test

**Files:**
- Create: `packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php`

- [ ] **Step 1: Write the failing test — generates url_key from SKU and name**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php --filter="generates url_key from sku and name" -v`

Expected: FAIL — class `Rydeen\Dealer\Listeners\ProductListener` not found.

- [ ] **Step 3: Commit test scaffold**

```bash
git add packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php
git commit -m "test: add failing test for url_key auto-generation from sku + name"
```

---

### Task 2: Implement ProductListener — Basic Generation

**Files:**
- Create: `packages/Rydeen/Dealer/src/Listeners/ProductListener.php`

- [ ] **Step 1: Write minimal implementation to pass the first test**

```php
<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

class ProductListener
{
    /**
     * Handle product create/update events.
     * Generates a url_key from SKU + name when the product has no url_key set.
     */
    public function afterSave(Product $product): void
    {
        $urlKeyAttribute = DB::table('attributes')->where('code', 'url_key')->first();

        if (! $urlKeyAttribute) {
            return;
        }

        $existingUrlKey = DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->where('attribute_id', $urlKeyAttribute->id)
            ->value('text_value');

        if (! empty($existingUrlKey)) {
            return;
        }

        $slug = $this->generateSlug($product);
        $uniqueSlug = $this->ensureUnique($slug, $product->id, $urlKeyAttribute->id);

        $this->persistUrlKey($product, $urlKeyAttribute, $uniqueSlug);
    }

    /**
     * Build slug from "{sku}-{slugified-name}" format.
     * Falls back to SKU only if name is empty.
     */
    protected function generateSlug(Product $product): string
    {
        $nameAttribute = DB::table('attributes')->where('code', 'name')->first();

        $name = $nameAttribute
            ? DB::table('product_attribute_values')
                ->where('product_id', $product->id)
                ->where('attribute_id', $nameAttribute->id)
                ->value('text_value')
            : null;

        $base = $product->sku;

        if (! empty($name)) {
            $base .= ' ' . $name;
        }

        return $this->slugify($base);
    }

    /**
     * Slugify a string using Bagisto's algorithm:
     * lowercase, NFKD normalize, strip diacriticals, keep letters/numbers/spaces/hyphens,
     * spaces to hyphens, collapse duplicates, trim edges.
     */
    protected function slugify(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        if (class_exists('Normalizer')) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_KD);
        }

        // Strip diacritical marks (combining characters)
        $value = preg_replace('/[\x{0300}-\x{036f}]/u', '', $value);

        // Keep only letters, numbers, spaces, and hyphens
        $value = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $value);

        // Spaces to hyphens
        $value = preg_replace('/\s+/', '-', $value);

        // Collapse duplicate hyphens
        $value = preg_replace('/-+/', '-', $value);

        // Trim leading/trailing hyphens
        return trim($value, '-');
    }

    /**
     * Append -1, -2, etc. if the slug already exists for a different product.
     */
    protected function ensureUnique(string $slug, int $productId, int $urlKeyAttributeId): string
    {
        $candidate = $slug;
        $suffix = 0;

        while ($this->slugExists($candidate, $productId, $urlKeyAttributeId)) {
            $suffix++;
            $candidate = $slug . '-' . $suffix;
        }

        return $candidate;
    }

    /**
     * Check if a url_key slug exists for any product other than the given one.
     */
    protected function slugExists(string $slug, int $productId, int $urlKeyAttributeId): bool
    {
        return DB::table('product_attribute_values')
            ->where('attribute_id', $urlKeyAttributeId)
            ->where('text_value', $slug)
            ->where('product_id', '!=', $productId)
            ->exists();
    }

    /**
     * Save the url_key attribute value and refresh the product flat index.
     */
    protected function persistUrlKey(Product $product, object $urlKeyAttribute, string $slug): void
    {
        $channels = $product->channels->pluck('code')->toArray();

        if (empty($channels)) {
            $channels = [DB::table('channels')->value('code') ?? 'default'];
        }

        $locales = DB::table('channel_locales')
            ->join('channels', 'channels.id', '=', 'channel_locales.channel_id')
            ->whereIn('channels.code', $channels)
            ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
            ->pluck('locales.code')
            ->unique()
            ->toArray();

        if (empty($locales)) {
            $locales = [DB::table('locales')->value('code') ?? 'en'];
        }

        foreach ($channels as $channelCode) {
            foreach ($locales as $localeCode) {
                $uniqueId = implode('|', [$channelCode, $localeCode, $product->id, $urlKeyAttribute->id]);

                DB::table('product_attribute_values')->updateOrInsert(
                    ['unique_id' => $uniqueId],
                    [
                        'product_id'   => $product->id,
                        'attribute_id' => $urlKeyAttribute->id,
                        'text_value'   => $slug,
                        'channel'      => $channelCode,
                        'locale'       => $localeCode,
                    ]
                );
            }
        }

        // Refresh product flat index so url_key is available for queries
        try {
            $indexer = app(\Webkul\Product\Helpers\Indexers\Flat::class);
            $indexer->refresh($product);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php --filter="generates url_key from sku and name" -v`

Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Listeners/ProductListener.php
git commit -m "feat: add ProductListener with url_key generation from sku + name"
```

---

### Task 3: Test and Implement Edge Cases

**Files:**
- Modify: `packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php`

- [ ] **Step 1: Add test — falls back to SKU only when name is empty**

Add after the existing test in `ProductListenerTest.php`:

```php
it('falls back to sku only when name is empty', function () {
    $this->product = createTestProduct('TEST-002');

    $this->listener->afterSave($this->product);

    $urlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $this->urlKeyAttributeId)
        ->value('text_value');

    expect($urlKey)->toBe('test-002');
});
```

- [ ] **Step 2: Run test to verify it passes** (implementation already handles this)

Run: `php artisan test packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php --filter="falls back to sku only" -v`

Expected: PASS

- [ ] **Step 3: Add test — skips generation when url_key already exists**

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php --filter="does not overwrite" -v`

Expected: PASS

- [ ] **Step 5: Add test — handles uniqueness collisions**

```php
it('appends suffix when slug already exists for another product', function () {
    // Create first product with a url_key
    $this->product = createTestProduct('TEST-004', 'Duplicate Name');
    $this->listener->afterSave($this->product);

    // Create second product with same SKU pattern and name
    $secondProduct = createTestProduct('TEST-004', 'Duplicate Name');
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
```

- [ ] **Step 6: Run all tests to verify they pass**

Run: `php artisan test packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php -v`

Expected: 4 tests, all PASS

- [ ] **Step 7: Add test — handles unicode/diacriticals in product names**

```php
it('handles unicode characters and diacriticals in names', function () {
    $this->product = createTestProduct('TEST-005', 'Camara Retrovisora Electonica');

    $this->listener->afterSave($this->product);

    $urlKey = DB::table('product_attribute_values')
        ->where('product_id', $this->product->id)
        ->where('attribute_id', $this->urlKeyAttributeId)
        ->value('text_value');

    expect($urlKey)->toBe('test-005-camara-retrovisora-electonica');
});
```

- [ ] **Step 8: Run all tests**

Run: `php artisan test packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php -v`

Expected: 5 tests, all PASS

- [ ] **Step 9: Commit**

```bash
git add packages/Rydeen/Dealer/tests/Unit/ProductListenerTest.php
git commit -m "test: add edge case tests for url_key generation (empty name, existing key, collisions, unicode)"
```

---

### Task 4: Wire Up EventServiceProvider

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Providers/EventServiceProvider.php`

- [ ] **Step 1: Add the event bindings**

Update `EventServiceProvider.php` to:

```php
<?php

namespace Rydeen\Dealer\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Rydeen\Dealer\Listeners\OrderListener;
use Rydeen\Dealer\Listeners\ProductListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'checkout.order.save.after' => [
            [OrderListener::class, 'afterOrderCreated'],
        ],

        'catalog.product.create.after' => [
            [ProductListener::class, 'afterSave'],
        ],

        'catalog.product.update.after' => [
            [ProductListener::class, 'afterSave'],
        ],
    ];
}
```

- [ ] **Step 2: Run the full test suite to verify nothing broke**

Run: `php artisan test packages/Rydeen/Dealer/tests/ -v`

Expected: All existing tests PASS, plus the 5 new ProductListener tests.

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Providers/EventServiceProvider.php
git commit -m "feat: register product url_key auto-generation events in EventServiceProvider"
```

---

### Task 5: Manual Verification

- [ ] **Step 1: Clear caches**

Run: `php artisan optimize:clear`

- [ ] **Step 2: Run full Rydeen test suite**

Run: `php artisan test packages/Rydeen/ -v`

Expected: All tests pass across Auth, Pricing, and Dealer packages.

- [ ] **Step 3: Verify via admin UI (if local server is available)**

1. Start server: `php artisan serve`
2. Go to `/admin/catalog/products/edit/1`
3. Clear the URL Key field, fill in Name, and save
4. Verify the URL Key is auto-populated after save
5. Verify the product is accessible at `/dealer/catalog/{generated-slug}`

- [ ] **Step 4: Final commit with all changes**

Run: `git log --oneline -5` to verify commit history looks clean.
