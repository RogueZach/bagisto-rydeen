@extends('rydeen::shop.layouts.master')

@section('title', $product->name . ' — ' . trans('rydeen-dealer::app.shop.catalog.title'))

@section('content')
    <div class="mb-4">
        <a href="{{ route('dealer.catalog') }}" class="text-sm text-gray-900 hover:text-gray-700">
            &larr; @lang('rydeen-dealer::app.shop.catalog.back-to-catalog')
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Product Image with Thumbnails --}}
            <div x-data="{
                activeImage: '{{ $product->images->first()?->url ?? $product->base_image_url ?? '' }}',
                images: {{ Js::from($product->images->pluck('url')->toArray()) }}
            }">
                {{-- Main Image --}}
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

                {{-- Thumbnails --}}
                <template x-if="images.length > 1">
                    <div class="flex gap-2 mt-4 overflow-x-auto">
                        <template x-for="(img, index) in images" :key="index">
                            <button @click="activeImage = img"
                                    class="flex-shrink-0 w-16 h-16 rounded border-2 overflow-hidden"
                                    :class="activeImage === img ? 'border-yellow-500' : 'border-gray-200 hover:border-gray-400'">
                                <img :src="img" :alt="'{{ $product->name }} image ' + (index + 1)" class="w-full h-full object-contain bg-gray-50">
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Product Info --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>

                {{-- Price --}}
                @if ($price)
                    <div class="mt-4 space-y-1">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm text-gray-500 uppercase font-medium">Your Price</span>
                            <span class="text-3xl font-bold text-green-700">${{ number_format($price['price'], 2) }}</span>
                        </div>
                        <p class="text-xs text-gray-400">(based on Forecast Lvl.)</p>
                        @if (isset($price['msrp']) && $price['msrp'] > $price['price'])
                            <div class="flex items-baseline gap-2">
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

                {{-- Need help? --}}
                <hr class="my-6">
                <div class="flex items-start justify-between gap-4">
                    <p class="text-sm text-gray-500">SKU: <strong>{{ $product->sku }}</strong></p>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">Need help?</p>
                        <p class="text-lg font-bold text-gray-900">1-310-787-7880</p>
                    </div>
                </div>
                <hr class="my-6">

                {{-- Add to Order --}}
                <div class="mt-6 flex items-center gap-4">
                    <label for="quantity" class="text-sm font-medium text-gray-700">
                        @lang('rydeen-dealer::app.shop.catalog.qty')
                    </label>
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" onclick="document.getElementById('quantity').stepDown()" class="px-3 py-2 text-gray-600 hover:bg-gray-100 text-lg font-bold">&minus;</button>
                        <input type="number" id="quantity" value="1" min="1" class="w-16 border-x border-gray-300 px-3 py-2 text-sm text-center [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button type="button" onclick="document.getElementById('quantity').stepUp()" class="px-3 py-2 text-gray-600 hover:bg-gray-100 text-lg font-bold">+</button>
                    </div>
                    <button type="button"
                            id="add-to-order-btn"
                            onclick="addToCart({{ $product->id }}, document.getElementById('quantity').value, this)"
                            class="bg-yellow-400 text-gray-900 font-bold px-6 py-2 rounded hover:bg-yellow-500 text-sm">
                        @lang('rydeen-dealer::app.shop.catalog.add-to-order')
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Back to Browse --}}
    <div class="mt-6">
        <a href="{{ route('dealer.catalog') }}" class="text-sm text-gray-900 hover:text-gray-700">
            &larr; Back to Browse
        </a>
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

            // Update cart badge
            var badge = document.getElementById('cart-badge');
            if (badge && data.data) {
                var itemCount = 0;
                if (data.data.items) {
                    data.data.items.forEach(function(item) {
                        itemCount += item.quantity;
                    });
                }
                if (itemCount > 0) {
                    badge.textContent = itemCount;
                    badge.style.display = 'flex';
                }
            }

            setTimeout(function() {
                btn.disabled = false;
                btn.textContent = originalText;
            }, 1500);
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }
</script>
@endpush
