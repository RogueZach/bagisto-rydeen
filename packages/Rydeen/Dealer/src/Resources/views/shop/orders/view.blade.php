@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.orders.order-detail', ['id' => $order->increment_id ?? $order->id]))

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('dealer.orders') }}" class="text-sm text-gray-900 hover:text-gray-700">
                &larr; @lang('rydeen-dealer::app.shop.orders.back-to-orders')
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">
                @lang('rydeen-dealer::app.shop.orders.order-detail', ['id' => $order->increment_id ?? $order->id])
            </h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dealer.orders.print', $order->id) }}"
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300" target="_blank">
                @lang('rydeen-dealer::app.shop.orders.print')
            </a>
            <form action="{{ route('dealer.orders.reorder', $order->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-black">
                    @lang('rydeen-dealer::app.shop.orders.reorder')
                </button>
            </form>
        </div>
    </div>

    {{-- Order Summary --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-500">@lang('rydeen-dealer::app.shop.orders.date')</p>
                <p class="font-medium">{{ $order->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-500">@lang('rydeen-dealer::app.shop.orders.status')</p>
                <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
            </div>
            <div>
                <p class="text-gray-500">@lang('rydeen-dealer::app.shop.orders.items')</p>
                <p class="font-medium">{{ $order->total_item_count }}</p>
            </div>
            <div>
                <p class="text-gray-500">@lang('rydeen-dealer::app.shop.orders.total')</p>
                <p class="font-medium text-lg">${{ number_format($order->grand_total, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Order Items --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                <tr>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.product')</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.catalog.qty')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.unit-price')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.shop.orders.subtotal')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->sku }}</td>
                        <td class="px-4 py-3">{{ (int) $item->qty_ordered }}</td>
                        <td class="px-4 py-3">${{ number_format($item->price, 2) }}</td>
                        <td class="px-4 py-3 font-medium">${{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right font-semibold">@lang('rydeen-dealer::app.shop.orders.subtotal')</td>
                    <td class="px-4 py-3 font-semibold">${{ number_format($order->sub_total, 2) }}</td>
                </tr>
                @if ($order->tax_amount > 0)
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right">@lang('rydeen-dealer::app.shop.orders.tax')</td>
                        <td class="px-4 py-3">${{ number_format($order->tax_amount, 2) }}</td>
                    </tr>
                @endif
                @if ($order->shipping_amount > 0)
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right">@lang('rydeen-dealer::app.shop.orders.shipping')</td>
                        <td class="px-4 py-3">${{ number_format($order->shipping_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="text-lg">
                    <td colspan="4" class="px-4 py-3 text-right font-bold">@lang('rydeen-dealer::app.shop.orders.total')</td>
                    <td class="px-4 py-3 font-bold">${{ number_format($order->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
