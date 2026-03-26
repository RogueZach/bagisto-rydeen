@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.orders.review-title'))

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">@lang('rydeen-dealer::app.shop.orders.review-title')</h1>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    @if ($cart && $cart->items->count())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Order Items --}}
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>

                @foreach ($cart->items as $item)
                    <div class="bg-white rounded-lg shadow p-4 flex items-start gap-4">
                        {{-- Product Thumbnail --}}
                        @php
                            $product = $item->product;
                            $imageUrl = $product?->images?->first()?->url ?? $product?->base_image_url ?? null;
                        @endphp
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="w-16 h-16 object-cover rounded flex-shrink-0">
                        @else
                            <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Item Details --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">SKU: {{ $item->sku }}</p>
                            <p class="text-sm text-gray-700 mt-1">${{ number_format($item->price, 2) }} each</p>
                        </div>

                        {{-- Quantity Controls --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <form action="{{ route('dealer.order-review.update-item') }}" method="POST" class="flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="{{ max(1, (int) $item->quantity - 1) }}">
                                <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm font-medium"
                                        {{ (int) $item->quantity <= 1 ? 'disabled' : '' }}>
                                    &minus;
                                </button>
                            </form>

                            <span class="w-10 text-center text-sm font-medium text-gray-900">{{ (int) $item->quantity }}</span>

                            <form action="{{ route('dealer.order-review.update-item') }}" method="POST" class="flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="{{ (int) $item->quantity + 1 }}">
                                <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm font-medium">
                                    +
                                </button>
                            </form>
                        </div>

                        {{-- Line Total --}}
                        <div class="text-right flex-shrink-0 w-24">
                            <p class="text-sm font-semibold text-gray-900">${{ number_format($item->total, 2) }}</p>
                        </div>

                        {{-- Remove Button --}}
                        <form action="{{ route('dealer.order-review.remove-item') }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                            <button type="submit"
                                    class="text-gray-400 hover:text-red-500 transition"
                                    title="Remove item">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach

                {{-- Order Notes --}}
                <div class="bg-white rounded-lg shadow p-4 mt-4">
                    <form id="place-order-form" action="{{ route('dealer.order-review.place') }}" method="POST">
                        @csrf
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Order Notes
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                  placeholder="Add any special instructions for your order..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Notes will be read by a Rydeen Specialist.</p>
                    </form>
                </div>
            </div>

            {{-- Right Column: Order Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-900">${{ number_format($cart->sub_total, 2) }}</span>
                        </div>

                        @if ($cart->tax_total > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium text-gray-900">${{ number_format($cart->tax_total, 2) }}</span>
                            </div>
                        @endif

                        <hr class="my-3">

                        <div class="flex justify-between text-base font-bold">
                            <span class="text-gray-900">Total</span>
                            <span class="text-gray-900">${{ number_format($cart->grand_total, 2) }}</span>
                        </div>
                    </div>

                    <button type="submit"
                            form="place-order-form"
                            class="mt-6 w-full bg-yellow-400 text-gray-900 font-semibold py-3 px-4 rounded hover:bg-yellow-500 transition text-sm">
                        Place Order
                    </button>

                    <p class="text-xs text-gray-500 text-center mt-3">
                        Orders require admin review before processing.
                    </p>

                    <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-xs text-yellow-700">
                            <strong>Order Processing Hours:</strong> Mon-Fri, 9:30 AM - 4:30 PM PT. Orders received after hours or on weekends will be processed the next business day.
                        </p>
                    </div>

                    <a href="{{ route('dealer.catalog') }}" class="block text-center text-sm text-gray-900 hover:text-gray-700 mt-4">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            <p class="text-gray-500 mb-4">Your cart is empty</p>
            <a href="{{ route('dealer.catalog') }}"
               class="inline-block bg-gray-900 text-white px-6 py-2 rounded hover:bg-black text-sm">
                Browse Catalog
            </a>
        </div>
    @endif
@endsection
