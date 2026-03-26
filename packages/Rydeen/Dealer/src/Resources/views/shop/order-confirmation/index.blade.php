@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.orders.confirmation-title'))

@section('content')
    <div class="max-w-xl mx-auto text-center py-12">
        <div class="bg-white rounded-lg shadow p-8">
            <div class="text-green-600 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                @lang('rydeen-dealer::app.shop.orders.order-placed')
            </h1>

            <p class="text-gray-600 mb-4">
                @lang('rydeen-dealer::app.shop.orders.confirmation-message', ['id' => $order->increment_id ?? $order->id])
            </p>

            <p class="text-sm text-amber-700 bg-amber-50 rounded px-3 py-2 mb-4">
                Orders require admin review before processing.
            </p>

            <div class="bg-gray-50 rounded p-4 mb-6 text-sm">
                <p><strong>@lang('rydeen-dealer::app.shop.orders.order-number'):</strong> #{{ $order->increment_id ?? $order->id }}</p>
                <p><strong>@lang('rydeen-dealer::app.shop.orders.items'):</strong> {{ $order->total_item_count }}</p>
                <p><strong>@lang('rydeen-dealer::app.shop.orders.total'):</strong> ${{ number_format($order->grand_total, 2) }}</p>
            </div>

            <div class="flex gap-4 justify-center">
                <a href="{{ route('dealer.orders.view', $order->id) }}"
                   class="bg-gray-900 text-white px-6 py-2 rounded hover:bg-black text-sm">
                    @lang('rydeen-dealer::app.shop.orders.view-order')
                </a>
                <a href="{{ route('dealer.catalog') }}"
                   class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 text-sm">
                    @lang('rydeen-dealer::app.shop.orders.continue-shopping')
                </a>
            </div>
        </div>
    </div>
@endsection
