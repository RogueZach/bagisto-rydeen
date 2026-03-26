<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; color: #1a1a1a; font-size: 24px; margin-bottom: 8px;">RYDEEN</h1>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 32px;">Order Confirmation</p>

        <p style="color: #333; font-size: 16px;">Hi {{ $order->customer_first_name }},</p>

        <p style="color: #333; font-size: 16px;">Thank you for your order. We have received it and it is being processed.</p>

        <p style="color: #333; font-size: 14px;">
            <strong>Order #:</strong> {{ $order->increment_id ?? $order->id }}<br>
            <strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <thead>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Product</th>
                    <th style="padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">SKU</th>
                    <th style="padding: 8px 12px; text-align: center; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Qty</th>
                    <th style="padding: 8px 12px; text-align: right; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333;">{{ $item->name }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #666;">{{ $item->sku }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: center;">{{ (int) $item->qty_ordered }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: right;">${{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-weight: bold; font-size: 16px; color: #1a1a1a; text-align: right; margin-top: 16px;">
            Grand Total: ${{ number_format($order->grand_total, 2) }}
        </p>

        <p style="color: #666; font-size: 14px; margin-top: 24px;">
            If you have any questions, please contact us at {{ config('rydeen.admin_order_email') }}.
        </p>

        <p style="color: #999; font-size: 12px; text-align: center; margin-top: 32px;">
            &mdash; Rydeen
        </p>
    </div>
</body>
</html>
