# PRD Compliance Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all 15 PRD compliance gaps identified in the smoke test, bringing the dealer portal to full spec.

**Architecture:** All changes stay within the existing `packages/Rydeen/` packages. View changes use Blade + Tailwind + Alpine.js (already in use). New backend features follow the existing controller/service/route pattern. No vendor modifications.

**Tech Stack:** PHP 8.2, Laravel 11, Bagisto v2.3.16, Tailwind CSS, Alpine.js

---

## File Map

### Modified Files
- `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php` — MSRP, category tabs, +/- qty, color badges, Details link
- `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php` — MSRP, Need Help, image thumbnails
- `packages/Rydeen/Dealer/src/Resources/views/shop/dashboard/index.blade.php` — Recent Orders section
- `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php` — +/- qty, remove items, admin review msg, business hours
- `packages/Rydeen/Dealer/src/Resources/views/shop/order-confirmation/index.blade.php` — Admin review msg
- `packages/Rydeen/Dealer/src/Resources/lang/en/app.php` — All new translation strings
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/DashboardController.php` — Pass recent orders
- `packages/Rydeen/Dealer/src/Services/DashboardStatsService.php` — Fetch recent orders
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/CatalogController.php` — Pass MSRP to views
- `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php` — Cart item remove/update
- `packages/Rydeen/Dealer/src/Routes/shop.php` — Cart redirect, new routes
- `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php` — Fix cart link, add badge
- `packages/Rydeen/Dealer/src/Routes/admin.php` — Admin order routes
- `packages/Rydeen/Auth/src/Routes/web.php` — Registration route
- `packages/Rydeen/Auth/src/Http/Controllers/LoginController.php` — Registration methods
- `packages/Rydeen/Auth/src/Resources/views/login.blade.php` — "Apply for account" link

### New Files
- `packages/Rydeen/Auth/src/Resources/views/register.blade.php` — Dealer registration form
- `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php` — Admin order management
- `packages/Rydeen/Dealer/src/Resources/views/admin/orders/index.blade.php` — Admin order list
- `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php` — Admin order detail

---

## Task 1: MSRP Display on Catalog Cards and Product Detail

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/CatalogController.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php`

- [ ] **Step 1: Pass MSRP data alongside resolved prices in CatalogController**

In `CatalogController.php`, the `index()` method already builds a `$prices` array keyed by product ID. The product's base price (`$product->price`) is the MSRP. We need to include it in the `$prices` array.

In `packages/Rydeen/Dealer/src/Http/Controllers/Shop/CatalogController.php`, find the block inside the `foreach ($products as $product)` loop (~line 47-57) where `$prices[$product->id]` is set. Change it so MSRP is always included:

Replace the existing price-building block (approximately lines 44-60):
```php
        $prices = [];
        foreach ($products as $product) {
            $groupPrice = $this->getGroupPrice($product, $customer->customer_group_id);
            $basePrice  = $groupPrice ?? $product->price;

            $resolved = app(\Rydeen\Pricing\Services\PriceResolver::class)->resolve(
                $product,
                $customer,
                $basePrice,
                1
            );

            $prices[$product->id] = $resolved;
        }
```

With:
```php
        $prices = [];
        foreach ($products as $product) {
            $groupPrice = $this->getGroupPrice($product, $customer->customer_group_id);
            $basePrice  = $groupPrice ?? $product->price;

            $resolved = app(\Rydeen\Pricing\Services\PriceResolver::class)->resolve(
                $product,
                $customer,
                $basePrice,
                1
            );

            $resolved['msrp'] = (float) $product->price;
            $prices[$product->id] = $resolved;
        }
```

Do the same in the `show()` method (~line 88-93). After `$price = ...resolve(...)`, add:
```php
            $price['msrp'] = (float) $product->price;
```

- [ ] **Step 2: Update catalog index view to show MSRP with strikethrough**

In `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`, replace the price display block (lines 82-95):

```php
                                {{-- Price --}}
                                @if (isset($prices[$product->id]))
                                    <p class="mt-2 text-lg font-bold text-green-700">
                                        ${{ number_format($prices[$product->id]['price'], 2) }}
                                    </p>
                                    @if ($prices[$product->id]['promo_name'])
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-orange-100 text-orange-700 text-xs rounded font-medium">
                                            {{ $prices[$product->id]['promo_name'] }}
                                        </span>
                                    @endif
                                @else
                                    <p class="mt-2 text-sm text-gray-400 italic">
                                        @lang('rydeen-dealer::app.shop.catalog.price-unavailable')
                                    </p>
                                @endif
```

With:
```php
                                {{-- Price --}}
                                @if (isset($prices[$product->id]))
                                    <div class="mt-2 flex items-baseline gap-2">
                                        <span class="text-sm text-gray-500 uppercase">Your Price</span>
                                        <span class="text-lg font-bold text-green-700">${{ number_format($prices[$product->id]['price'], 2) }}</span>
                                    </div>
                                    @if ($prices[$product->id]['msrp'] > $prices[$product->id]['price'])
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-xs text-gray-400 uppercase">MSRP</span>
                                            <span class="text-sm text-gray-400 line-through">${{ number_format($prices[$product->id]['msrp'], 2) }}</span>
                                        </div>
                                    @endif
                                    @if ($prices[$product->id]['promo_name'])
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-orange-100 text-orange-700 text-xs rounded font-medium">
                                            {{ $prices[$product->id]['promo_name'] }}
                                        </span>
                                    @endif
                                @else
                                    <p class="mt-2 text-sm text-gray-400 italic">
                                        @lang('rydeen-dealer::app.shop.catalog.price-unavailable')
                                    </p>
                                @endif
```

- [ ] **Step 3: Update product detail view to show MSRP**

In `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php`, replace the price block (lines 36-51):

```php
                {{-- Price --}}
                @if ($price)
                    <div class="mt-4">
                        <p class="text-3xl font-bold text-green-700">
                            ${{ number_format($price['price'], 2) }}
                        </p>
                        @if ($price['promo_name'])
                            <span class="inline-block mt-2 px-3 py-1 bg-orange-100 text-orange-700 text-sm rounded font-medium">
                                {{ $price['promo_name'] }}
                            </span>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-gray-400 italic">
                        @lang('rydeen-dealer::app.shop.catalog.price-unavailable')
                    </p>
                @endif
```

With:
```php
                {{-- Price --}}
                @if ($price)
                    <div class="mt-4">
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl font-bold text-green-700">${{ number_format($price['price'], 2) }}</span>
                            <span class="text-sm text-gray-500 italic">(based on Forecast Lvl.)</span>
                        </div>
                        @if ($price['msrp'] > $price['price'])
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-sm text-gray-400 uppercase">MSRP</span>
                                <span class="text-lg text-gray-400 line-through">${{ number_format($price['msrp'], 2) }}</span>
                            </div>
                        @endif
                        @if ($price['promo_name'])
                            <span class="inline-block mt-2 px-3 py-1 bg-orange-100 text-orange-700 text-sm rounded font-medium">
                                {{ $price['promo_name'] }}
                            </span>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-gray-400 italic">
                        @lang('rydeen-dealer::app.shop.catalog.price-unavailable')
                    </p>
                @endif
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Shop/CatalogController.php \
       packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php \
       packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php
git commit -m "feat: display MSRP alongside dealer price on catalog and product detail"
```

---

## Task 2: Horizontal Category Tabs with Color Badges

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`

- [ ] **Step 1: Replace the sidebar category filter with horizontal tabs**

In `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`, replace the entire `<div class="flex gap-6">` wrapper (lines 25-113) with a new layout that has horizontal tabs at the top and a full-width grid below.

Replace lines 25-47 (the `<div class="flex gap-6">` opening through the end of `</aside>`) with:

```html
    {{-- Category Tabs --}}
    @php
        $categoryColors = [
            'Digital Mirrors' => 'bg-blue-600',
            'Blind Spot Detection' => 'bg-red-600',
            'Cameras' => 'bg-green-600',
            'Monitors' => 'bg-purple-600',
        ];
    @endphp
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <a href="{{ route('dealer.catalog', array_filter(['search' => request('search')])) }}"
           class="px-4 py-2 rounded-full text-sm font-medium transition {{ ! request('category') ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            ALL
        </a>
        @if ($categories)
            @foreach ($categories as $category)
                @php $color = $categoryColors[$category->name] ?? 'bg-gray-600'; @endphp
                <a href="{{ route('dealer.catalog', array_filter(['category' => $category->id, 'search' => request('search')])) }}"
                   class="px-4 py-2 rounded-full text-sm font-medium transition {{ request('category') == $category->id ? $color . ' text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        @endif
    </div>
```

Also remove the `<div class="flex gap-6">` wrapper and the `<div class="flex-1">` wrapper so the product grid is full-width. The grid section (starting with `@if ($products->isEmpty())`) should be at the top level inside `@section('content')`, no longer nested in `<div class="flex-1">`.

- [ ] **Step 2: Add color-coded category badge on each product card**

In the same file, inside each product card `<div class="p-4">` block, add a category badge above the product name. Find the product name link block and add before it:

```html
                            <div class="p-4">
                                @php
                                    $catName = $product->categories->first()?->name ?? 'Uncategorized';
                                    $badgeColor = $categoryColors[$catName] ?? 'bg-gray-600';
                                @endphp
                                <span class="inline-block px-2 py-0.5 {{ $badgeColor }} text-white text-xs rounded font-medium mb-2">
                                    {{ $catName }}
                                </span>
```

- [ ] **Step 3: Add "Details" link on each product card**

After the Add to Cart button inside each product card, add a Details link:

```html
                                <a href="{{ route('dealer.catalog.product', $product->url_key ?? $product->id) }}"
                                   class="mt-2 block text-center text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    Details
                                </a>
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php
git commit -m "feat: horizontal category tabs with color badges and Details links"
```

---

## Task 3: Quantity +/- Controls on Catalog Cards

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`

- [ ] **Step 1: Replace the "Add to Cart" button with an Alpine.js quantity component**

In the catalog index view, replace each product card's Add to Cart button (lines 98-102) with an Alpine.js component that toggles between "Add to Order" and a quantity selector:

```html
                                {{-- Add to Cart --}}
                                <div x-data="{ qty: 0, adding: false }" class="mt-3">
                                    <template x-if="qty === 0">
                                        <button type="button"
                                                @click="qty = 1; adding = true; addToCart({{ $product->id }}, 1, () => { adding = false })"
                                                :disabled="adding"
                                                class="w-full bg-yellow-400 text-gray-900 text-sm font-semibold py-2 rounded hover:bg-yellow-500 transition">
                                            <span x-show="!adding">+ @lang('rydeen-dealer::app.shop.catalog.add-to-order')</span>
                                            <span x-show="adding">@lang('rydeen-dealer::app.shop.catalog.adding')</span>
                                        </button>
                                    </template>
                                    <template x-if="qty > 0">
                                        <div class="flex items-center justify-center border border-gray-300 rounded">
                                            <button type="button"
                                                    @click="qty = Math.max(0, qty - 1); if (qty === 0) { removeFromCart({{ $product->id }}) } else { updateCart({{ $product->id }}, qty) }"
                                                    class="px-3 py-2 text-gray-600 hover:bg-gray-100 text-lg font-bold">
                                                &minus;
                                            </button>
                                            <span class="px-4 py-2 text-sm font-semibold min-w-[2rem] text-center" x-text="qty"></span>
                                            <button type="button"
                                                    @click="qty++; updateCart({{ $product->id }}, qty)"
                                                    class="px-3 py-2 text-gray-600 hover:bg-gray-100 text-lg font-bold">
                                                +
                                            </button>
                                        </div>
                                    </template>
                                </div>
```

- [ ] **Step 2: Update the addToCart JS function and add updateCart/removeFromCart**

Replace the entire `@push('scripts')` block at the bottom of the file with:

```html
@push('scripts')
<script>
    function addToCart(productId, quantity, callback) {
        fetch('{{ route('shop.api.checkout.cart.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ product_id: productId, quantity: quantity }),
        })
        .then(r => r.json())
        .then(data => { if (callback) callback(data); updateCartBadge(); })
        .catch(() => { if (callback) callback(null); });
    }

    function updateCart(productId, quantity) {
        fetch('{{ route('shop.api.checkout.cart.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ product_id: productId, quantity: quantity, update: true }),
        })
        .then(() => updateCartBadge())
        .catch(() => {});
    }

    function removeFromCart(productId) {
        // Handled by Bagisto API when quantity reaches 0 via update
        updateCart(productId, 0);
    }

    function updateCartBadge() {
        fetch('{{ route('shop.api.checkout.cart.index') }}', {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('cart-badge');
            const count = data.data?.items?.length ?? 0;
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        })
        .catch(() => {});
    }
</script>
@endpush
```

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php
git commit -m "feat: add quantity +/- controls on catalog product cards"
```

---

## Task 4: Fix Cart Link + Add Cart Badge in Header

**Files:**
- Modify: `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/shop.php`

- [ ] **Step 1: Add cart redirect route**

In `packages/Rydeen/Dealer/src/Routes/shop.php`, add a redirect route for `/dealer/cart` inside the route group, after the order-review routes (after line 28):

```php
    // Cart (redirect to order review)
    Route::get('cart', function () {
        return redirect()->route('dealer.order-review');
    })->name('dealer.cart');
```

- [ ] **Step 2: Update header cart icon to use named route and add badge**

In `packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php`, replace the cart icon link (lines 29-34):

```html
                    {{-- Cart Icon --}}
                    <a href="/dealer/cart" class="relative text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                    </a>
```

With:

```html
                    {{-- Cart Icon --}}
                    <a href="{{ route('dealer.cart') }}" class="relative text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                        <span id="cart-badge"
                              class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full w-5 h-5 items-center justify-center font-bold"
                              style="display: none;">
                            0
                        </span>
                    </a>
```

Also update the mobile cart link (line 81-83) from `/dealer/cart` to `{{ route('dealer.cart') }}`.

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Routes/shop.php \
       packages/Rydeen/Core/src/Resources/views/shop/components/header.blade.php
git commit -m "fix: wire cart link to order-review route and add cart count badge"
```

---

## Task 5: Order Review — Quantity Controls, Remove Items, Admin Review Message

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/shop.php`
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`

- [ ] **Step 1: Add cart update and remove routes**

In `packages/Rydeen/Dealer/src/Routes/shop.php`, add after the existing order-review routes:

```php
    // Cart item management
    Route::post('order-review/update-item', [OrderController::class, 'updateItem'])->name('dealer.order-review.update-item');
    Route::post('order-review/remove-item', [OrderController::class, 'removeItem'])->name('dealer.order-review.remove-item');
```

- [ ] **Step 2: Add updateItem and removeItem methods to OrderController**

In `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php`, add these methods before the `ensureCartAddresses()` method:

```php
    /**
     * Update a cart item's quantity.
     */
    public function updateItem(Request $request)
    {
        $cart = \Webkul\Checkout\Facades\Cart::getCart();
        if (! $cart) {
            return redirect()->route('dealer.order-review');
        }

        $item = $cart->items->firstWhere('id', $request->item_id);
        if ($item) {
            \Webkul\Checkout\Facades\Cart::update(['qty' => [$request->item_id => max(1, (int) $request->quantity)]]);
        }

        return redirect()->route('dealer.order-review');
    }

    /**
     * Remove a cart item.
     */
    public function removeItem(Request $request)
    {
        \Webkul\Checkout\Facades\Cart::removeItem($request->item_id);

        return redirect()->route('dealer.order-review');
    }
```

- [ ] **Step 3: Rewrite the order review view with quantity controls, remove buttons, and admin messaging**

Replace the entire content of `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php`:

```blade
@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.orders.review-title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">@lang('rydeen-dealer::app.shop.orders.review-title')</h1>
        <a href="{{ route('dealer.catalog') }}" class="text-sm text-blue-600 hover:text-blue-800">
            &larr; @lang('rydeen-dealer::app.shop.orders.back-to-catalog')
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    @if ($cart && $cart->items->count())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Order Items --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="font-semibold text-gray-900">@lang('rydeen-dealer::app.shop.orders.order-items')</h2>
                    </div>
                    @foreach ($cart->items as $item)
                        <div class="px-6 py-4 border-b flex items-center gap-4">
                            @php $img = $item->product?->images?->first()?->url ?? $item->product?->base_image_url ?? null; @endphp
                            @if ($img)
                                <img src="{{ $img }}" alt="{{ $item->name }}" class="w-16 h-16 object-contain rounded bg-gray-50 flex-shrink-0">
                            @else
                                <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center text-gray-400 text-xs flex-shrink-0">No img</div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->sku }}</p>
                                <p class="text-sm text-gray-500">${{ number_format($item->price, 2) }} ea</p>
                            </div>

                            {{-- Quantity +/- --}}
                            <form method="POST" action="{{ route('dealer.order-review.update-item') }}" class="flex items-center border border-gray-300 rounded">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="{{ max(1, (int) $item->quantity - 1) }}">
                                <button type="submit" class="px-3 py-1 text-gray-600 hover:bg-gray-100 text-lg font-bold">&minus;</button>
                            </form>
                            <span class="text-sm font-semibold w-8 text-center">{{ (int) $item->quantity }}</span>
                            <form method="POST" action="{{ route('dealer.order-review.update-item') }}" class="flex items-center border border-gray-300 rounded">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="{{ (int) $item->quantity + 1 }}">
                                <button type="submit" class="px-3 py-1 text-gray-600 hover:bg-gray-100 text-lg font-bold">+</button>
                            </form>

                            {{-- Line Total --}}
                            <p class="text-sm font-bold text-gray-900 w-24 text-right">${{ number_format($item->total, 2) }}</p>

                            {{-- Remove --}}
                            <form method="POST" action="{{ route('dealer.order-review.remove-item') }}">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button type="submit" class="text-gray-400 hover:text-red-600 ml-2" title="Remove">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                {{-- Order Notes --}}
                <form id="place-order-form" action="{{ route('dealer.order-review.place') }}" method="POST" class="bg-white rounded-lg shadow p-6 mt-4">
                    @csrf
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        @lang('rydeen-dealer::app.shop.orders.notes')
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                              class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                              placeholder="{{ trans('rydeen-dealer::app.shop.orders.notes-placeholder') }}"></textarea>
                    <p class="text-xs text-gray-400 mt-1">@lang('rydeen-dealer::app.shop.orders.notes-read-by')</p>
                </form>
            </div>

            {{-- Order Summary Sidebar --}}
            <div>
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h2 class="font-semibold text-gray-900 mb-4">@lang('rydeen-dealer::app.shop.orders.order-summary')</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">@lang('rydeen-dealer::app.shop.orders.subtotal')</span>
                            <span class="font-medium">${{ number_format($cart->sub_total, 2) }}</span>
                        </div>
                        @if ($cart->tax_total > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">@lang('rydeen-dealer::app.shop.orders.tax')</span>
                                <span>${{ number_format($cart->tax_total, 2) }}</span>
                            </div>
                        @endif
                        <div class="border-t pt-2 flex justify-between text-lg font-bold">
                            <span>@lang('rydeen-dealer::app.shop.orders.total')</span>
                            <span>${{ number_format($cart->grand_total, 2) }}</span>
                        </div>
                    </div>

                    <button type="submit" form="place-order-form"
                            class="mt-4 w-full bg-yellow-400 text-gray-900 py-3 rounded text-sm font-bold hover:bg-yellow-500 transition">
                        @lang('rydeen-dealer::app.shop.orders.place-order')
                    </button>

                    <p class="text-xs text-gray-500 text-center mt-2">
                        @lang('rydeen-dealer::app.shop.orders.admin-review-required')
                    </p>

                    {{-- Business Hours --}}
                    <div class="mt-4 p-3 bg-blue-50 rounded text-xs text-blue-800">
                        <p class="font-semibold mb-1">@lang('rydeen-dealer::app.shop.orders.processing-hours-title')</p>
                        <p>@lang('rydeen-dealer::app.shop.orders.processing-hours')</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <p class="text-gray-500 mb-4">@lang('rydeen-dealer::app.shop.orders.cart-empty')</p>
            <a href="{{ route('dealer.catalog') }}"
               class="inline-block bg-yellow-400 text-gray-900 px-6 py-2 rounded font-semibold hover:bg-yellow-500">
                @lang('rydeen-dealer::app.shop.dashboard.browse-catalog')
            </a>
        </div>
    @endif
@endsection
```

- [ ] **Step 4: Add new translation strings**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, add these keys inside the `'orders'` array:

```php
            'back-to-catalog'          => 'Back to Catalog',
            'order-items'              => 'Order Items',
            'order-summary'            => 'Order Summary',
            'admin-review-required'    => 'Orders require admin review before processing.',
            'processing-hours-title'   => 'Order Processing Hours',
            'processing-hours'         => 'Mon-Fri, 9:30 AM - 4:30 PM PT. Orders received after hours or on weekends will be processed the next business day.',
            'notes-read-by'            => 'Notes will be read by a Rydeen Specialist.',
```

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php \
       packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php \
       packages/Rydeen/Dealer/src/Routes/shop.php \
       packages/Rydeen/Dealer/src/Resources/lang/en/app.php
git commit -m "feat: add qty controls, remove items, admin review msg on order review"
```

---

## Task 6: Order Confirmation — Admin Review Message

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/order-confirmation/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`

- [ ] **Step 1: Add admin review message to confirmation page**

In `packages/Rydeen/Dealer/src/Resources/views/shop/order-confirmation/index.blade.php`, replace the confirmation message paragraph (lines 18-20):

```html
            <p class="text-gray-600 mb-4">
                @lang('rydeen-dealer::app.shop.orders.confirmation-message', ['id' => $order->increment_id ?? $order->id])
            </p>
```

With:

```html
            <p class="text-gray-600 mb-2">
                @lang('rydeen-dealer::app.shop.orders.confirmation-message', ['id' => $order->increment_id ?? $order->id])
            </p>
            <p class="text-sm text-amber-700 bg-amber-50 rounded px-3 py-2 mb-4">
                @lang('rydeen-dealer::app.shop.orders.admin-review-required')
            </p>
```

- [ ] **Step 2: Update the confirmation message translation**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, change the `confirmation-message` value:

From:
```php
            'confirmation-message' => 'Your order #:id has been placed and is being processed.',
```

To:
```php
            'confirmation-message' => 'Your order #:id has been submitted for review.',
```

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/order-confirmation/index.blade.php \
       packages/Rydeen/Dealer/src/Resources/lang/en/app.php
git commit -m "feat: add admin review required message on order confirmation"
```

---

## Task 7: Product Detail — Need Help, Image Thumbnails

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php`

- [ ] **Step 1: Add image thumbnails and "Need help?" section**

Replace the entire content of `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php`:

```blade
@extends('rydeen::shop.layouts.master')

@section('title', $product->name . ' — ' . trans('rydeen-dealer::app.shop.catalog.title'))

@section('content')
    <div class="mb-4">
        <a href="{{ route('dealer.catalog') }}" class="text-sm text-blue-600 hover:text-blue-800">
            &larr; @lang('rydeen-dealer::app.shop.catalog.back-to-catalog')
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Product Images --}}
            <div x-data="{ activeImage: '{{ $product->images->first()?->url ?? $product->base_image_url ?? '' }}' }">
                {{-- Main Image --}}
                <div class="mb-4">
                    <template x-if="activeImage">
                        <img :src="activeImage"
                             alt="{{ $product->name }}"
                             class="w-full max-h-96 object-contain rounded bg-gray-50">
                    </template>
                    <template x-if="!activeImage">
                        <div class="w-full h-96 bg-gray-100 flex items-center justify-center text-gray-400">
                            @lang('rydeen-dealer::app.shop.catalog.no-image')
                        </div>
                    </template>
                </div>

                {{-- Thumbnails --}}
                @if ($product->images->count() > 1)
                    <div class="flex gap-2 overflow-x-auto pb-2">
                        @foreach ($product->images as $image)
                            <button @click="activeImage = '{{ $image->url }}'"
                                    :class="activeImage === '{{ $image->url }}' ? 'ring-2 ring-blue-500' : 'ring-1 ring-gray-200'"
                                    class="flex-shrink-0 w-16 h-16 rounded overflow-hidden bg-gray-50">
                                <img src="{{ $image->url }}" alt="" class="w-full h-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">SKU: {{ $product->sku }}</p>

                {{-- Price --}}
                @if ($price)
                    <div class="mt-4">
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl font-bold text-green-700">${{ number_format($price['price'], 2) }}</span>
                            <span class="text-sm text-gray-500 italic">(based on Forecast Lvl.)</span>
                        </div>
                        @if ($price['msrp'] > $price['price'])
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-sm text-gray-400 uppercase">MSRP</span>
                                <span class="text-lg text-gray-400 line-through">${{ number_format($price['msrp'], 2) }}</span>
                            </div>
                        @endif
                        @if ($price['promo_name'])
                            <span class="inline-block mt-2 px-3 py-1 bg-orange-100 text-orange-700 text-sm rounded font-medium">
                                {{ $price['promo_name'] }}
                            </span>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-gray-400 italic">
                        @lang('rydeen-dealer::app.shop.catalog.price-unavailable')
                    </p>
                @endif

                {{-- Description --}}
                @if ($product->description)
                    <div class="mt-6 text-sm text-gray-700 leading-relaxed prose max-w-none">
                        {!! $product->description !!}
                    </div>
                @endif

                <hr class="my-6">

                {{-- SKU + Need Help --}}
                <div class="flex items-start justify-between gap-4">
                    <p class="text-sm text-gray-500">SKU: <strong>{{ $product->sku }}</strong></p>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">Need help?</p>
                        <p class="text-lg font-bold text-gray-900">1-310-787-7880</p>
                    </div>
                </div>

                <hr class="my-6">

                {{-- Add to Order --}}
                <div class="flex items-center gap-4">
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" onclick="document.getElementById('quantity').stepDown()" class="px-3 py-2 text-gray-600 hover:bg-gray-100 text-lg font-bold">&minus;</button>
                        <input type="number" id="quantity" value="1" min="1"
                               class="w-16 border-x border-gray-300 px-3 py-2 text-sm text-center [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button type="button" onclick="document.getElementById('quantity').stepUp()" class="px-3 py-2 text-gray-600 hover:bg-gray-100 text-lg font-bold">+</button>
                    </div>
                    <button type="button"
                            id="add-to-order-btn"
                            onclick="addToCart({{ $product->id }}, document.getElementById('quantity').value, this)"
                            class="flex-1 bg-yellow-400 text-gray-900 px-6 py-3 rounded font-bold hover:bg-yellow-500 text-sm transition">
                        + @lang('rydeen-dealer::app.shop.catalog.add-to-order')
                    </button>
                </div>

                <div class="mt-4 flex gap-4 text-sm">
                    <a href="{{ route('dealer.catalog') }}" class="text-blue-600 hover:text-blue-800">Back to Browse</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function addToCart(productId, quantity, btn) {
        btn.disabled = true;
        var originalText = btn.textContent;
        btn.textContent = '{{ trans('rydeen-dealer::app.shop.catalog.adding') }}';

        fetch('{{ route('shop.api.checkout.cart.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ product_id: productId, quantity: parseInt(quantity) }),
        })
        .then(r => r.json())
        .then(data => {
            btn.textContent = '{{ trans('rydeen-dealer::app.shop.catalog.added') }}';
            const badge = document.getElementById('cart-badge');
            if (badge) {
                const count = parseInt(badge.textContent || '0') + 1;
                badge.textContent = count;
                badge.style.display = 'flex';
            }
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            }, 1500);
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }
</script>
@endpush
```

- [ ] **Step 2: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/catalog/product.blade.php
git commit -m "feat: add image thumbnails and Need Help section to product detail"
```

---

## Task 8: Dashboard — Recent Orders Section

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Services/DashboardStatsService.php`
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/DashboardController.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/dashboard/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`

- [ ] **Step 1: Add recent orders query to DashboardStatsService**

In `packages/Rydeen/Dealer/src/Services/DashboardStatsService.php`, add a new method after `getStats()`:

```php
    /**
     * Get the 5 most recent orders for the customer.
     */
    public function getRecentOrders($customerId): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('orders')
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'increment_id', 'status', 'grand_total', 'total_item_count', 'created_at']);
    }
```

- [ ] **Step 2: Pass recent orders from DashboardController**

In `packages/Rydeen/Dealer/src/Http/Controllers/Shop/DashboardController.php`, update the `index()` method to pass recent orders. The current method looks like:

```php
    public function index()
    {
        $customer = auth('customer')->user();
        $stats = app(DashboardStatsService::class)->getStats($customer);

        return view('rydeen-dealer::shop.dashboard.index', compact('customer', 'stats'));
    }
```

Change it to:

```php
    public function index()
    {
        $customer = auth('customer')->user();
        $statsService = app(DashboardStatsService::class);
        $stats = $statsService->getStats($customer);
        $recentOrders = $statsService->getRecentOrders($customer->id);

        return view('rydeen-dealer::shop.dashboard.index', compact('customer', 'stats', 'recentOrders'));
    }
```

- [ ] **Step 3: Add Recent Orders section to dashboard view**

In `packages/Rydeen/Dealer/src/Resources/views/shop/dashboard/index.blade.php`, add the following block after the KPI cards section (after line 54, before the Quick Links section):

```html
    {{-- Recent Orders --}}
    <div class="bg-white rounded-lg shadow mt-6">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">@lang('rydeen-dealer::app.shop.dashboard.recent-orders')</h2>
            <a href="{{ route('dealer.orders') }}" class="text-sm text-blue-600 hover:text-blue-800">
                @lang('rydeen-dealer::app.shop.dashboard.view-all') &rarr;
            </a>
        </div>
        @if ($recentOrders->isEmpty())
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-500 font-medium mb-1">@lang('rydeen-dealer::app.shop.dashboard.no-recent-orders')</p>
                <p class="text-gray-400 text-sm mb-4">@lang('rydeen-dealer::app.shop.dashboard.start-browsing')</p>
                <a href="{{ route('dealer.catalog') }}"
                   class="inline-block bg-yellow-400 text-gray-900 px-6 py-2 rounded font-semibold hover:bg-yellow-500">
                    @lang('rydeen-dealer::app.shop.dashboard.browse-catalog')
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                        <tr>
                            <th class="px-6 py-3">@lang('rydeen-dealer::app.shop.orders.order-number')</th>
                            <th class="px-6 py-3">@lang('rydeen-dealer::app.shop.orders.date')</th>
                            <th class="px-6 py-3">@lang('rydeen-dealer::app.shop.orders.items')</th>
                            <th class="px-6 py-3">@lang('rydeen-dealer::app.shop.orders.total')</th>
                            <th class="px-6 py-3">@lang('rydeen-dealer::app.shop.orders.status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <a href="{{ route('dealer.orders.view', $order->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        #{{ $order->increment_id ?? $order->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-gray-600">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                                <td class="px-6 py-3">{{ $order->total_item_count }}</td>
                                <td class="px-6 py-3 font-medium">${{ number_format($order->grand_total, 2) }}</td>
                                <td class="px-6 py-3">
                                    @php
                                        $statusColor = match(true) {
                                            in_array($order->status, ['completed', 'closed']) => 'bg-green-100 text-green-800',
                                            in_array($order->status, ['canceled', 'fraud']) => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded text-xs font-medium {{ $statusColor }}">{{ ucfirst($order->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
```

- [ ] **Step 4: Add translation strings**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, add to the `'dashboard'` array:

```php
            'recent-orders'    => 'Recent Orders',
            'view-all'         => 'View all',
            'no-recent-orders' => 'No orders yet',
            'start-browsing'   => 'Start by browsing our product catalog.',
```

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Services/DashboardStatsService.php \
       packages/Rydeen/Dealer/src/Http/Controllers/Shop/DashboardController.php \
       packages/Rydeen/Dealer/src/Resources/views/shop/dashboard/index.blade.php \
       packages/Rydeen/Dealer/src/Resources/lang/en/app.php
git commit -m "feat: add Recent Orders section to dealer dashboard"
```

---

## Task 9: Dealer Self-Registration

**Files:**
- Modify: `packages/Rydeen/Auth/src/Http/Controllers/LoginController.php`
- Modify: `packages/Rydeen/Auth/src/Routes/web.php`
- Modify: `packages/Rydeen/Auth/src/Resources/views/login.blade.php`
- Create: `packages/Rydeen/Auth/src/Resources/views/register.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`

- [ ] **Step 1: Add registration routes**

In `packages/Rydeen/Auth/src/Routes/web.php`, add inside the route group, after the existing login/verify routes:

```php
    Route::get('register', [LoginController::class, 'showRegister'])->name('dealer.register');
    Route::post('register', [LoginController::class, 'register'])->name('dealer.register.submit');
```

- [ ] **Step 2: Add showRegister and register methods to LoginController**

In `packages/Rydeen/Auth/src/Http/Controllers/LoginController.php`, add these methods before the `logout()` method:

```php
    /**
     * Show dealer registration form.
     */
    public function showRegister()
    {
        if (auth('customer')->check()) {
            return redirect()->route('dealer.dashboard');
        }

        return view('rydeen-auth::register');
    }

    /**
     * Process dealer registration. Creates a pending (unverified) customer.
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:customers,email',
            'business_name' => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
        ]);

        // Default to "New Dealers" group
        $newDealerGroup = \Webkul\Customer\Models\CustomerGroup::where('code', 'new-dealers')->first();

        \Webkul\Customer\Models\Customer::create([
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email,
            'phone'             => $request->phone,
            'password'          => bcrypt(\Illuminate\Support\Str::random(32)), // Random password; OTP login only
            'customer_group_id' => $newDealerGroup?->id,
            'channel_id'        => core()->getCurrentChannel()->id,
            'is_verified'       => 0,  // Pending admin approval
            'status'            => 0,
        ]);

        return redirect()->route('dealer.login')
            ->with('success', trans('rydeen-dealer::app.shop.auth.registration-pending'));
    }
```

- [ ] **Step 3: Create registration view**

Create `packages/Rydeen/Auth/src/Resources/views/register.blade.php`:

```blade
@extends('rydeen::shop.layouts.master')

@section('title', 'Apply for a Dealer Account')

@section('content')
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white rounded-lg shadow p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">RYDEEN</h1>
                <p class="text-sm text-gray-500 mt-1">Apply for a Dealer Account</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('dealer.register.submit') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                    <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           placeholder="you@dealership.com"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>

                <button type="submit"
                        class="w-full bg-yellow-400 text-gray-900 py-3 rounded font-bold hover:bg-yellow-500 transition">
                    Submit Application
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-500">
                Your application will be reviewed by Rydeen. You'll receive an email once approved.
            </p>

            <p class="mt-4 text-center text-sm text-gray-600">
                Already a dealer? <a href="{{ route('dealer.login') }}" class="text-blue-600 hover:text-blue-800 font-medium">Sign in</a>
            </p>
        </div>
    </div>
@endsection
```

- [ ] **Step 4: Add "Apply for an account" link to login page**

In `packages/Rydeen/Auth/src/Resources/views/login.blade.php`, add before the closing `</div>` of the form card (before the final `</div>` inside the centered container):

```html
            <p class="mt-4 text-center text-sm text-gray-600">
                Not a dealer yet? <a href="{{ route('dealer.register') }}" class="text-blue-600 hover:text-blue-800 font-medium">Apply for an account</a>
            </p>
```

- [ ] **Step 5: Add translation strings**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, add a new `'auth'` section inside the `'shop'` array:

```php
        'auth' => [
            'registration-pending' => 'Your application has been submitted. You will receive an email once your account is approved.',
        ],
```

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Auth/src/Http/Controllers/LoginController.php \
       packages/Rydeen/Auth/src/Routes/web.php \
       packages/Rydeen/Auth/src/Resources/views/login.blade.php \
       packages/Rydeen/Auth/src/Resources/views/register.blade.php \
       packages/Rydeen/Dealer/src/Resources/lang/en/app.php
git commit -m "feat: add dealer self-registration with pending admin approval"
```

---

## Task 10: Admin Order Approval Workflow

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/admin/orders/index.blade.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/admin.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`

- [ ] **Step 1: Create OrderApprovalController**

Create `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php`:

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OrderApprovalController extends Controller
{
    /**
     * List all dealer orders for admin review.
     */
    public function index(Request $request)
    {
        $query = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select('orders.*', 'customers.first_name', 'customers.last_name', 'customers.email as customer_email');

        if ($request->status && $request->status !== 'all') {
            $query->where('orders.status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('orders.increment_id', 'like', "%{$request->search}%")
                  ->orWhere('customers.first_name', 'like', "%{$request->search}%")
                  ->orWhere('customers.last_name', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->orderByDesc('orders.created_at')->paginate(25);

        return view('rydeen-dealer::admin.orders.index', compact('orders'));
    }

    /**
     * View a single dealer order.
     */
    public function view($id)
    {
        $order = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->where('orders.id', $id)
            ->select('orders.*', 'customers.first_name', 'customers.last_name', 'customers.email as customer_email', 'customers.phone')
            ->first();

        abort_unless($order, 404);

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->get();

        return view('rydeen-dealer::admin.orders.view', compact('order', 'items'));
    }

    /**
     * Approve an order — set status to processing.
     */
    public function approve($id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'processing',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.orders.order-approved'));
    }

    /**
     * Hold/flag an order.
     */
    public function hold($id, Request $request)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'pending',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.orders.order-held'));
    }

    /**
     * Cancel an order.
     */
    public function cancel($id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'canceled',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.orders.order-canceled'));
    }
}
```

- [ ] **Step 2: Add admin order routes**

In `packages/Rydeen/Dealer/src/Routes/admin.php`, add inside the route group, after the existing dealer routes:

```php
    // Admin Order Management
    Route::get('orders', [\Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController::class, 'index'])->name('admin.rydeen.orders.index');
    Route::get('orders/{id}', [\Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController::class, 'view'])->name('admin.rydeen.orders.view');
    Route::post('orders/{id}/approve', [\Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController::class, 'approve'])->name('admin.rydeen.orders.approve');
    Route::post('orders/{id}/hold', [\Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController::class, 'hold'])->name('admin.rydeen.orders.hold');
    Route::post('orders/{id}/cancel', [\Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController::class, 'cancel'])->name('admin.rydeen.orders.cancel');
```

- [ ] **Step 3: Create admin order list view**

Create `packages/Rydeen/Dealer/src/Resources/views/admin/orders/index.blade.php`:

```blade
@extends('admin::layouts.master')

@section('page_title')
    @lang('rydeen-dealer::app.admin.orders.title')
@endsection

@section('content-wrapper')
    <div class="content full-page">
        <div class="page-header">
            <div class="page-title">
                <h1>@lang('rydeen-dealer::app.admin.orders.title')</h1>
            </div>
        </div>

        <div class="page-content">
            {{-- Filters --}}
            <form method="GET" class="mb-4 flex gap-4 items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by order # or dealer name..."
                       class="border rounded px-3 py-2 text-sm w-64">
                <select name="status" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Canceled</option>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            </form>

            @if (session('success'))
                <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
            @endif

            <div class="table">
                <table>
                    <thead>
                        <tr>
                            <th>@lang('rydeen-dealer::app.shop.orders.order-number')</th>
                            <th>Dealer</th>
                            <th>@lang('rydeen-dealer::app.shop.orders.date')</th>
                            <th>@lang('rydeen-dealer::app.shop.orders.items')</th>
                            <th>@lang('rydeen-dealer::app.shop.orders.total')</th>
                            <th>@lang('rydeen-dealer::app.shop.orders.status')</th>
                            <th>@lang('rydeen-dealer::app.admin.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>#{{ $order->increment_id ?? $order->id }}</td>
                                <td>{{ $order->first_name }} {{ $order->last_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y g:i A') }}</td>
                                <td>{{ $order->total_item_count }}</td>
                                <td>${{ number_format($order->grand_total, 2) }}</td>
                                <td>
                                    @php
                                        $color = match($order->status) {
                                            'completed', 'closed' => 'color: green;',
                                            'canceled', 'fraud' => 'color: red;',
                                            'processing' => 'color: blue;',
                                            default => 'color: orange;',
                                        };
                                    @endphp
                                    <span style="{{ $color }} font-weight: bold;">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.rydeen.orders.view', $order->id) }}" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">@lang('rydeen-dealer::app.admin.orders.no-orders')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
```

- [ ] **Step 4: Create admin order detail view**

Create `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php`:

```blade
@extends('admin::layouts.master')

@section('page_title')
    Order #{{ $order->increment_id ?? $order->id }}
@endsection

@section('content-wrapper')
    <div class="content full-page">
        <div class="page-header">
            <div class="page-title">
                <h1>Order #{{ $order->increment_id ?? $order->id }}</h1>
            </div>
            <div class="page-action">
                <a href="{{ route('admin.rydeen.orders.index') }}" class="btn btn-sm">Back to Orders</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
        @endif

        <div class="page-content">
            {{-- Order Info --}}
            <div class="sale-container" style="margin-bottom: 20px;">
                <div class="sale-section">
                    <div class="secton-title"><span>Order Information</span></div>
                    <div class="section-content">
                        <div class="row">
                            <span class="title">Status:</span>
                            <span class="value"><strong>{{ ucfirst($order->status) }}</strong></span>
                        </div>
                        <div class="row">
                            <span class="title">Date:</span>
                            <span class="value">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y g:i A') }}</span>
                        </div>
                        <div class="row">
                            <span class="title">Dealer:</span>
                            <span class="value">{{ $order->first_name }} {{ $order->last_name }} ({{ $order->customer_email }})</span>
                        </div>
                        @if ($order->phone)
                            <div class="row">
                                <span class="title">Phone:</span>
                                <span class="value">{{ $order->phone }}</span>
                            </div>
                        @endif
                        <div class="row">
                            <span class="title">Grand Total:</span>
                            <span class="value"><strong>${{ number_format($order->grand_total, 2) }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="table" style="margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ (int) $item->qty_ordered }}</td>
                                <td>${{ number_format($item->price, 2) }}</td>
                                <td>${{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Actions --}}
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                @if ($order->status === 'pending' || $order->status === 'pending_payment')
                    <form method="POST" action="{{ route('admin.rydeen.orders.approve', $order->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-lg btn-primary" style="background: green; color: white;">
                            Approve Order
                        </button>
                    </form>
                @endif

                @if ($order->status !== 'canceled')
                    <form method="POST" action="{{ route('admin.rydeen.orders.hold', $order->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-lg btn-warning">
                            Hold Order
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.rydeen.orders.cancel', $order->id) }}"
                          onsubmit="return confirm('Are you sure you want to cancel this order?')">
                        @csrf
                        <button type="submit" class="btn btn-lg btn-danger" style="background: red; color: white;">
                            Cancel Order
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
```

- [ ] **Step 5: Add admin order translation strings**

In `packages/Rydeen/Dealer/src/Resources/lang/en/app.php`, add a new `'orders'` section inside the `'admin'` array:

```php
        'orders' => [
            'title'          => 'Dealer Orders',
            'no-orders'      => 'No orders found.',
            'order-approved' => 'Order has been approved and is now processing.',
            'order-held'     => 'Order has been put on hold.',
            'order-canceled' => 'Order has been canceled.',
        ],
```

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php \
       packages/Rydeen/Dealer/src/Resources/views/admin/orders/index.blade.php \
       packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php \
       packages/Rydeen/Dealer/src/Routes/admin.php \
       packages/Rydeen/Dealer/src/Resources/lang/en/app.php
git commit -m "feat: add admin order approval workflow with approve/hold/cancel"
```

---

## Task 11: Product Status Flags (NEW, UPDATED, SALE, REDUCED)

**Files:**
- Create: `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_25_000001_add_product_flags_to_products_table.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`

- [ ] **Step 1: Create migration to add flag columns**

Create `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_25_000001_add_product_flags_to_products_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('rydeen_flag_new')->default(false)->after('status');
            $table->boolean('rydeen_flag_updated')->default(false)->after('rydeen_flag_new');
            $table->boolean('rydeen_flag_sale')->default(false)->after('rydeen_flag_updated');
            $table->boolean('rydeen_flag_reduced')->default(false)->after('rydeen_flag_sale');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rydeen_flag_new', 'rydeen_flag_updated', 'rydeen_flag_sale', 'rydeen_flag_reduced']);
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Add status flag badges in catalog product cards**

In `packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php`, inside each product card, right after the image `<a>` tag and before `<div class="p-4">`, add:

```html
                            {{-- Status Flags --}}
                            <div class="flex flex-wrap gap-1 px-4 pt-2">
                                @if ($product->rydeen_flag_new)
                                    <span class="px-2 py-0.5 bg-green-500 text-white text-xs rounded font-bold uppercase">New</span>
                                @endif
                                @if ($product->rydeen_flag_updated)
                                    <span class="px-2 py-0.5 bg-blue-500 text-white text-xs rounded font-bold uppercase">Updated</span>
                                @endif
                                @if ($product->rydeen_flag_sale)
                                    <span class="px-2 py-0.5 bg-red-500 text-white text-xs rounded font-bold uppercase">Sale</span>
                                @endif
                                @if ($product->rydeen_flag_reduced)
                                    <span class="px-2 py-0.5 bg-amber-500 text-white text-xs rounded font-bold uppercase">Reduced</span>
                                @endif
                            </div>
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Database/Migrations/2026_03_25_000001_add_product_flags_to_products_table.php \
       packages/Rydeen/Dealer/src/Resources/views/shop/catalog/index.blade.php
git commit -m "feat: add NEW/UPDATED/SALE/REDUCED product status flags"
```

---

## Summary of All Translation String Additions

For reference, here is the complete set of all new keys that must be added to `packages/Rydeen/Dealer/src/Resources/lang/en/app.php` across all tasks. Each task adds its own subset — this is the consolidated list to verify nothing is missed.

**In `'admin'` array:**
```php
'orders' => [
    'title'          => 'Dealer Orders',
    'no-orders'      => 'No orders found.',
    'order-approved' => 'Order has been approved and is now processing.',
    'order-held'     => 'Order has been put on hold.',
    'order-canceled' => 'Order has been canceled.',
],
```

**In `'shop' > 'dashboard'` array:**
```php
'recent-orders'    => 'Recent Orders',
'view-all'         => 'View all',
'no-recent-orders' => 'No orders yet',
'start-browsing'   => 'Start by browsing our product catalog.',
```

**In `'shop' > 'orders'` array:**
```php
'back-to-catalog'          => 'Back to Catalog',
'order-items'              => 'Order Items',
'order-summary'            => 'Order Summary',
'admin-review-required'    => 'Orders require admin review before processing.',
'processing-hours-title'   => 'Order Processing Hours',
'processing-hours'         => 'Mon-Fri, 9:30 AM - 4:30 PM PT. Orders received after hours or on weekends will be processed the next business day.',
'notes-read-by'            => 'Notes will be read by a Rydeen Specialist.',
```

**In `'shop'` array (new section):**
```php
'auth' => [
    'registration-pending' => 'Your application has been submitted. You will receive an email once your account is approved.',
],
```

**Update existing:**
```php
'confirmation-message' => 'Your order #:id has been submitted for review.',
```
