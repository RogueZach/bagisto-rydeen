<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>@lang('rydeen-dealer::app.shop.orders.order-detail', ['id' => $order->increment_id ?? $order->id])</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #666; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-size: 12px; text-transform: uppercase; }
        .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #333; }
        .text-right { text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Print</button>
        <a href="{{ route('dealer.orders.view', $order->id) }}" style="margin-left: 10px;">Back to Order</a>
    </div>

    <h1>Rydeen — Dealer Order</h1>
    <p class="meta">
        Order #{{ $order->increment_id ?? $order->id }} |
        {{ $order->created_at->format('M d, Y H:i') }} |
        Status: {{ ucfirst(str_replace('_', ' ', $order->status)) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ (int) $item->qty_ordered }}</td>
                    <td class="text-right">${{ number_format($item->price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Subtotal</td>
                <td class="text-right">${{ number_format($order->sub_total, 2) }}</td>
            </tr>
            @if ($order->tax_amount > 0)
                <tr>
                    <td colspan="4" class="text-right">Tax</td>
                    <td class="text-right">${{ number_format($order->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="4" class="text-right">Grand Total</td>
                <td class="text-right">${{ number_format($order->grand_total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
