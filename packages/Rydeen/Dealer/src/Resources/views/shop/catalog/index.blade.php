@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.catalog.title'))

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">@lang('rydeen-dealer::app.shop.catalog.title')</h1>

        {{-- Search --}}
        <form action="{{ route('dealer.catalog') }}" method="GET" class="flex gap-2">
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ trans('rydeen-dealer::app.shop.catalog.search-placeholder') }}"
                   class="border border-gray-300 rounded px-3 py-2 text-sm w-64">
            <button type="submit" class="bg-yellow-400 text-gray-900 px-4 py-2 rounded text-sm font-semibold hover:bg-yellow-500">
                @lang('rydeen-dealer::app.shop.catalog.search')
            </button>
        </form>
    </div>

    {{-- Category color map (using hex values for inline styles to avoid Tailwind purge issues) --}}
    @php
        $categoryHex = [
            'Digital Mirrors'       => '#2563eb',
            'Blind Spot Detection'  => '#dc2626',
            'Cameras'               => '#16a34a',
            'Monitors'              => '#9333ea',
        ];
        $defaultHex = '#6b7280';

        $categoryHexMap = [];
        if ($categories) {
            foreach ($categories as $cat) {
                $categoryHexMap[$cat->id] = $categoryHex[$cat->name] ?? $defaultHex;
            }
        }
    @endphp

    {{-- Horizontal Category Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('dealer.catalog', request()->only('search')) }}"
           class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition
                  {{ ! request('category') ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            @lang('rydeen-dealer::app.shop.catalog.all-products')
        </a>
        @if ($categories)
            @foreach ($categories as $category)
                @php
                    $hex = $categoryHex[$category->name] ?? $defaultHex;
                    $isActive = request('category') == $category->id;
                @endphp
                <a href="{{ route('dealer.catalog', array_merge(request()->only('search'), ['category' => $category->id])) }}"
                   class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition {{ $isActive ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                   @if ($isActive) style="background-color: {{ $hex }}" @endif>
                    @if (! $isActive)
                        <span class="w-2.5 h-2.5 rounded-full mr-2 flex-shrink-0" style="background-color: {{ $hex }}"></span>
                    @endif
                    {{ $category->name }}
                </a>
            @endforeach
        @endif
    </div>

    {{-- Product Grid (full width, no sidebar) --}}
    @if ($products->isEmpty())
        <div class="text-center py-12 text-gray-500">
            @lang('rydeen-dealer::app.shop.catalog.no-products')
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)
                @php
                    $productCategories = $product->categories;
                    $firstCategory = $productCategories->first();
                    $catHex = $firstCategory ? ($categoryHexMap[$firstCategory->id] ?? $defaultHex) : $defaultHex;
                    $catName = $firstCategory?->name ?? 'Uncategorized';
                    $priceData = $prices[$product->id] ?? null;
                @endphp
                <div class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden flex flex-col">
                    {{-- Category Color Badge --}}
                    <div class="px-4 pt-3">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold text-white" style="background-color: {{ $catHex }}">
                            {{ $catName }}
                        </span>
                    </div>

                    {{-- Product Image --}}
                    <a href="{{ route('dealer.catalog.product', $product->url_key ?? $product->id) }}" class="block relative">
                        @php
                            $imageUrl = $product->images->first()?->url ?? $product->base_image_url ?? null;
                        @endphp
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-48 object-contain bg-gray-50">
                        @else
                            <div class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                                @lang('rydeen-dealer::app.shop.catalog.no-image')
                            </div>
                        @endif

                        {{-- Status Flags --}}
                        <div class="absolute top-2 right-2 flex flex-col gap-1">
                            @if ($product->rydeen_flag_new)
                                <span class="px-2 py-0.5 bg-emerald-500 text-white text-[10px] font-bold uppercase rounded shadow">NEW</span>
                            @endif
                            @if ($product->rydeen_flag_updated)
                                <span class="px-2 py-0.5 bg-blue-500 text-white text-[10px] font-bold uppercase rounded shadow">UPDATED</span>
                            @endif
                            @if ($product->rydeen_flag_sale)
                                <span class="px-2 py-0.5 bg-orange-500 text-white text-[10px] font-bold uppercase rounded shadow">SALE</span>
                            @endif
                            @if ($product->rydeen_flag_reduced)
                                <span class="px-2 py-0.5 bg-red-500 text-white text-[10px] font-bold uppercase rounded shadow">REDUCED</span>
                            @endif
                        </div>
                    </a>

                    {{-- Product Info --}}
                    <div class="p-4 flex flex-col flex-1">
                        <a href="{{ route('dealer.catalog.product', $product->url_key ?? $product->id) }}"
                           class="block text-sm font-semibold text-gray-800 hover:text-blue-600 truncate">
                            {{ $product->name }}
                        </a>
                        <p class="text-xs text-gray-500 mt-1">SKU: {{ $product->sku }}</p>

                        {{-- Pricing: Your Price + MSRP --}}
                        @if ($priceData)
                            <div class="mt-2 space-y-0.5">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-gray-500 uppercase">Your Price</span>
                                    <span class="text-lg font-bold text-green-700">${{ number_format($priceData['price'], 2) }}</span>
                                </div>
                                @if ($priceData['msrp'] > $priceData['price'])
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs text-gray-400 uppercase">MSRP</span>
                                        <span class="text-sm text-gray-400 line-through">${{ number_format($priceData['msrp'], 2) }}</span>
                                    </div>
                                @endif
                            </div>
                            @if ($priceData['promo_name'])
                                <span class="inline-block mt-1 px-2 py-0.5 bg-orange-100 text-orange-700 text-xs rounded font-medium">
                                    {{ $priceData['promo_name'] }}
                                </span>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-gray-400 italic">
                                @lang('rydeen-dealer::app.shop.catalog.price-unavailable')
                            </p>
                        @endif

                        {{-- Spacer to push controls to bottom --}}
                        <div class="flex-1"></div>

                        {{-- Quantity +/- Controls (Alpine.js) --}}
                        <div class="mt-3" x-data="qtyControl({{ $product->id }})">
                            {{-- Initial "Add to Order" button --}}
                            <template x-if="!inCart">
                                <button type="button"
                                        x-on:click="addFirst()"
                                        x-bind:disabled="loading"
                                        class="w-full bg-yellow-400 text-gray-900 text-sm font-semibold py-2 rounded hover:bg-yellow-500 disabled:opacity-50 transition">
                                    <span x-show="!loading">+ Add to Order</span>
                                    <span x-show="loading" x-cloak>Adding...</span>
                                </button>
                            </template>

                            {{-- Qty adjustment controls --}}
                            <template x-if="inCart">
                                <div class="flex items-center gap-1">
                                    <button type="button"
                                            x-on:click="decrement()"
                                            x-bind:disabled="loading"
                                            class="w-9 h-9 flex items-center justify-center rounded bg-gray-200 text-gray-700 font-bold hover:bg-gray-300 disabled:opacity-50 transition text-lg">
                                        &minus;
                                    </button>
                                    <span class="flex-1 text-center text-sm font-semibold text-gray-800"
                                          x-text="qty"></span>
                                    <button type="button"
                                            x-on:click="increment()"
                                            x-bind:disabled="loading"
                                            class="w-9 h-9 flex items-center justify-center rounded bg-gray-200 text-gray-700 font-bold hover:bg-gray-300 disabled:opacity-50 transition text-lg">
                                        +
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Details Link --}}
                        <a href="{{ route('dealer.catalog.product', $product->url_key ?? $product->id) }}"
                           class="mt-2 block text-center text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Details &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->appends(request()->query())->links() }}
        </div>
    @endif
@endsection

@push('scripts')
<script>
    /**
     * Cart state: maps product_id -> { cartItemId, qty }
     * Populated on page load from the cart API.
     */
    window.rydeenCart = {};

    /**
     * Fetch current cart and populate rydeenCart map.
     */
    async function loadCart() {
        try {
            const res = await fetch('{{ route('shop.api.checkout.cart.index') }}', {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            const cart = json.data;
            if (cart && cart.items) {
                window.rydeenCart = {};
                let totalQty = 0;
                cart.items.forEach(item => {
                    // Match cart items to products by product_url_key
                    window.rydeenCart[item.product_url_key] = {
                        cartItemId: item.id,
                        qty: item.quantity,
                    };
                    totalQty += item.quantity;
                });
                updateCartBadge(totalQty);
            }
        } catch (e) {
            console.error('Failed to load cart', e);
        }
    }

    /**
     * Add a product to the cart.
     * Returns the updated cart data.
     */
    async function addToCart(productId, quantity) {
        const res = await fetch('{{ route('shop.api.checkout.cart.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ product_id: productId, quantity: quantity }),
        });
        const json = await res.json();
        if (json.data) {
            syncCartState(json.data);
        }
        return json;
    }

    /**
     * Update quantity of an existing cart item.
     */
    async function updateCart(cartItemId, newQty) {
        const res = await fetch('{{ route('shop.api.checkout.cart.update') }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ qty: { [cartItemId]: newQty } }),
        });
        const json = await res.json();
        if (json.data) {
            syncCartState(json.data);
        }
        return json;
    }

    /**
     * Remove a cart item entirely.
     */
    async function removeFromCart(cartItemId) {
        const res = await fetch('{{ route('shop.api.checkout.cart.destroy') }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ cart_item_id: cartItemId }),
        });
        const json = await res.json();
        if (json.data) {
            syncCartState(json.data);
        }
        return json;
    }

    /**
     * Sync local rydeenCart state from the API cart response.
     */
    function syncCartState(cart) {
        window.rydeenCart = {};
        let totalQty = 0;
        if (cart.items) {
            cart.items.forEach(item => {
                window.rydeenCart[item.product_url_key] = {
                    cartItemId: item.id,
                    qty: item.quantity,
                };
                totalQty += item.quantity;
            });
        }
        updateCartBadge(totalQty);
    }

    /**
     * Update the cart badge count in the header.
     */
    function updateCartBadge(totalQty) {
        const badge = document.getElementById('cart-badge');
        if (badge) {
            badge.textContent = totalQty;
            badge.style.display = totalQty > 0 ? 'flex' : 'none';
        }
    }

    /**
     * Alpine.js component for per-card quantity control.
     * Looks up the product's url_key in the global rydeenCart map.
     */
    function qtyControl(productId) {
        // Build a url_key lookup from Blade data
        const urlKeyMap = {
            @foreach ($products as $p)
                {{ $p->id }}: '{{ $p->url_key }}',
            @endforeach
        };

        const urlKey = urlKeyMap[productId] || '';
        const existing = window.rydeenCart[urlKey];

        return {
            productId: productId,
            urlKey: urlKey,
            qty: existing ? existing.qty : 1,
            inCart: !!existing,
            loading: false,

            async addFirst() {
                this.loading = true;
                try {
                    await addToCart(this.productId, 1);
                    const entry = window.rydeenCart[this.urlKey];
                    if (entry) {
                        this.qty = entry.qty;
                        this.inCart = true;
                    }
                } catch (e) {
                    console.error('Add to cart failed', e);
                }
                this.loading = false;
            },

            async increment() {
                this.loading = true;
                const entry = window.rydeenCart[this.urlKey];
                if (entry) {
                    const newQty = entry.qty + 1;
                    await updateCart(entry.cartItemId, newQty);
                    const updated = window.rydeenCart[this.urlKey];
                    this.qty = updated ? updated.qty : newQty;
                }
                this.loading = false;
            },

            async decrement() {
                this.loading = true;
                const entry = window.rydeenCart[this.urlKey];
                if (entry) {
                    if (entry.qty <= 1) {
                        await removeFromCart(entry.cartItemId);
                        this.inCart = false;
                        this.qty = 1;
                    } else {
                        const newQty = entry.qty - 1;
                        await updateCart(entry.cartItemId, newQty);
                        const updated = window.rydeenCart[this.urlKey];
                        this.qty = updated ? updated.qty : newQty;
                    }
                }
                this.loading = false;
            },
        };
    }

    // Load cart state on page init
    document.addEventListener('DOMContentLoaded', loadCart);
</script>
@endpush
