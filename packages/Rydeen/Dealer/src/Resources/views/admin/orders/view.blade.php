<x-admin::layouts>
    <x-slot:title>
        Order #{{ $order->increment_id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Order #{{ $order->increment_id }}
        </p>

        <a href="{{ route('admin.rydeen.orders.index') }}"
           class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            Back to Orders
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Order Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Order Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Status:</span>
                <span class="ml-2 font-medium">
                    @switch($order->status)
                        @case('pending')
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">Pending</span>
                            @break
                        @case('processing')
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800">Processing</span>
                            @break
                        @case('completed')
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">Completed</span>
                            @break
                        @case('canceled')
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">Canceled</span>
                            @break
                        @default
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-800">{{ ucfirst($order->status) }}</span>
                    @endswitch
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Date:</span>
                <span class="ml-2 font-medium">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Dealer:</span>
                <span class="ml-2 font-medium">{{ $order->first_name }} {{ $order->last_name }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Email:</span>
                <span class="ml-2 font-medium">{{ $order->email ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                <span class="ml-2 font-medium">{{ $order->phone ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Grand Total:</span>
                <span class="ml-2 font-medium text-lg">${{ number_format($order->grand_total, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Subtotal:</span>
                <span class="ml-2 font-medium">${{ number_format($order->sub_total, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Shipping:</span>
                <span class="ml-2 font-medium">${{ number_format($order->shipping_amount, 2) }}</span>
            </div>
            @if ($order->tax_amount > 0)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Tax:</span>
                    <span class="ml-2 font-medium">${{ number_format($order->tax_amount, 2) }}</span>
                </div>
            @endif
            @if ($order->discount_amount > 0)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Discount:</span>
                    <span class="ml-2 font-medium text-red-600">-${{ number_format($order->discount_amount, 2) }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Order Items --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Order Items
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-600 bg-gray-50 dark:bg-gray-900 dark:text-gray-300 border-b">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Unit Price</th>
                        <th class="px-4 py-3">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b dark:border-gray-800">
                            <td class="px-4 py-3">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->sku }}</td>
                            <td class="px-4 py-3">{{ (int) $item->qty_ordered }}</td>
                            <td class="px-4 py-3">${{ number_format($item->price, 2) }}</td>
                            <td class="px-4 py-3">${{ number_format($item->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No items found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Actions
        </h2>

        <div class="flex flex-wrap gap-3">
            @if ($order->status === 'pending')
                <form action="{{ route('admin.rydeen.orders.approve', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium">
                        Approve Order
                    </button>
                </form>
            @endif

            @if ($order->status !== 'pending')
                <form action="{{ route('admin.rydeen.orders.hold', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm font-medium">
                        Hold Order
                    </button>
                </form>
            @endif

            @if ($order->status !== 'canceled')
                <form action="{{ route('admin.rydeen.orders.cancel', $order->id) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium">
                        Cancel Order
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-admin::layouts>
