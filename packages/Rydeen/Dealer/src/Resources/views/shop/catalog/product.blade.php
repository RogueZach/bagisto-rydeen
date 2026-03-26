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
            {{-- Product Image --}}
            <div>
                @php
                    $imageUrl = $product->images->first()?->url ?? $product->base_image_url ?? null;
                @endphp
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}"
                         alt="{{ $product->name }}"
                         class="w-full max-h-96 object-contain rounded bg-gray-50">
                @else
                    <div class="w-full h-96 bg-gray-100 flex items-center justify-center text-gray-400">
                        @lang('rydeen-dealer::app.shop.catalog.no-image')
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">SKU: {{ $product->sku }}</p>

                {{-- Price --}}
                @if ($price)
                    <div class="mt-4 space-y-1">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm text-gray-500 uppercase font-medium">Your Price</span>
                            <span class="text-3xl font-bold text-green-700">${{ number_format($price['price'], 2) }}</span>
                        </div>
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

                {{-- Add to Order --}}
                <div class="mt-6 flex items-center gap-4">
                    <label for="quantity" class="text-sm font-medium text-gray-700">
                        @lang('rydeen-dealer::app.shop.catalog.qty')
                    </label>
                    <input type="number"
                           id="quantity"
                           value="1"
                           min="1"
                           class="w-20 border border-gray-300 rounded px-3 py-2 text-sm text-center">
                    <button type="button"
                            id="add-to-order-btn"
                            onclick="addToCart({{ $product->id }}, document.getElementById('quantity').value, this)"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 text-sm font-medium">
                        @lang('rydeen-dealer::app.shop.catalog.add-to-order')
                    </button>
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
