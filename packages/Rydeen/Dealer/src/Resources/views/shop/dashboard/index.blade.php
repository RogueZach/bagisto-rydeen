@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.dashboard.title'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            @lang('rydeen-dealer::app.shop.dashboard.welcome', ['name' => $customer->first_name])
        </h1>
        <p class="text-gray-600 mt-1">@lang('rydeen-dealer::app.shop.dashboard.subtitle')</p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Orders YTD --}}
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                @lang('rydeen-dealer::app.shop.dashboard.total-orders-ytd')
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ number_format($stats['total_orders_ytd']) }}
            </p>
        </div>

        {{-- This Month Total --}}
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                @lang('rydeen-dealer::app.shop.dashboard.this-month')
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                ${{ number_format($stats['this_month_total'], 2) }}
            </p>
        </div>

        {{-- Pending Orders --}}
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                @lang('rydeen-dealer::app.shop.dashboard.pending-orders')
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ number_format($stats['pending_orders_count']) }}
            </p>
        </div>

        {{-- Forecast Level --}}
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                @lang('rydeen-dealer::app.shop.dashboard.forecast-level')
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ $stats['forecast_level'] }}
            </p>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="mt-8 bg-white rounded-lg shadow">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
            <a href="{{ route('dealer.orders') }}" class="text-sm text-gray-900 hover:text-gray-700">View all &rarr;</a>
        </div>

        @if ($recentOrders->isEmpty())
            <div class="text-center py-10 px-6">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-500 font-medium mb-1">No orders yet</p>
                <p class="text-sm text-gray-400 mb-4">Start by browsing our product catalog.</p>
                <a href="{{ route('dealer.catalog') }}"
                   class="inline-block bg-gray-900 text-white px-5 py-2 rounded text-sm hover:bg-black">
                    Browse Catalog
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                        <tr>
                            <th class="px-6 py-3">Order #</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Items</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <a href="{{ route('dealer.orders.view', $order->id) }}" class="text-gray-900 hover:text-gray-700 font-medium">
                                        #{{ $order->increment_id ?? $order->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $order->total_item_count }}</td>
                                <td class="px-6 py-3 font-medium">${{ number_format($order->grand_total, 2) }}</td>
                                <td class="px-6 py-3">
                                    @php
                                        $statusColors = match($order->status) {
                                            'completed', 'closed' => 'bg-green-100 text-green-800',
                                            'canceled', 'fraud' => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800',
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Quick Links --}}
    <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('dealer.catalog') }}"
           class="block bg-white rounded-lg shadow p-4 hover:shadow-md transition text-center">
            <p class="font-semibold text-gray-800">@lang('rydeen-dealer::app.shop.dashboard.browse-catalog')</p>
        </a>

        <a href="{{ route('dealer.orders') }}"
           class="block bg-white rounded-lg shadow p-4 hover:shadow-md transition text-center">
            <p class="font-semibold text-gray-800">@lang('rydeen-dealer::app.shop.dashboard.view-orders')</p>
        </a>

        <a href="{{ route('dealer.resources') }}"
           class="block bg-white rounded-lg shadow p-4 hover:shadow-md transition text-center">
            <p class="font-semibold text-gray-800">@lang('rydeen-dealer::app.shop.dashboard.resources')</p>
        </a>
    </div>
@endsection
