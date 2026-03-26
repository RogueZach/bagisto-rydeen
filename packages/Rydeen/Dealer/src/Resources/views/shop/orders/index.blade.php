@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.orders.title'))

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">@lang('rydeen-dealer::app.shop.orders.title')</h1>

        <form action="{{ route('dealer.orders') }}" method="GET" class="flex gap-2">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ trans('rydeen-dealer::app.shop.orders.search-placeholder') }}"
                   class="border border-gray-300 rounded px-3 py-2 text-sm w-64">
            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-black">
                @lang('rydeen-dealer::app.shop.catalog.search')
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                <tr>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.order-number')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.date')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.items')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.total')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.status')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">#{{ $order->increment_id ?? $order->id }}</td>
                        <td class="px-4 py-3">{{ $order->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">{{ $order->total_item_count }}</td>
                        <td class="px-4 py-3">${{ number_format($order->grand_total, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 text-xs rounded font-medium
                                @if(in_array($order->status, ['completed', 'closed'])) bg-green-100 text-green-800
                                @elseif(in_array($order->status, ['canceled', 'fraud'])) bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('dealer.orders.view', $order->id) }}"
                                   class="text-gray-900 hover:text-gray-700 text-xs">
                                    @lang('rydeen-dealer::app.shop.orders.view')
                                </a>
                                <a href="{{ route('dealer.orders.print', $order->id) }}"
                                   class="text-gray-600 hover:text-gray-800 text-xs" target="_blank">
                                    @lang('rydeen-dealer::app.shop.orders.print')
                                </a>
                                <form action="{{ route('dealer.orders.reorder', $order->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-xs">
                                        @lang('rydeen-dealer::app.shop.orders.reorder')
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            @lang('rydeen-dealer::app.shop.orders.no-orders')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->appends(request()->query())->links() }}
    </div>
@endsection
