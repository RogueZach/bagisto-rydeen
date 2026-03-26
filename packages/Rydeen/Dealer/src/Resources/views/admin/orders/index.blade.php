<x-admin::layouts>
    <x-slot:title>
        Dealer Orders
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Dealer Orders
        </p>
    </div>

    {{-- Filters --}}
    <div class="mt-7 mb-4 flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.rydeen.orders.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search order #, name..."
                   class="border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:text-white w-64">

            <select name="status"
                    class="border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:text-white">
                <option value="">All Statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="canceled" @selected(request('status') === 'canceled')>Canceled</option>
            </select>

            <button type="submit" class="primary-button">
                Filter
            </button>

            @if (request('search') || request('status'))
                <a href="{{ route('admin.rydeen.orders.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-600 bg-gray-50 dark:bg-gray-900 dark:text-gray-300 border-b">
                <tr>
                    <th class="px-4 py-3">Order #</th>
                    <th class="px-4 py-3">Dealer Name</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-3 font-medium">{{ $order->increment_id }}</td>
                        <td class="px-4 py-3">{{ $order->first_name }} {{ $order->last_name }}</td>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                        <td class="px-4 py-3">{{ (int) $order->total_qty_ordered }}</td>
                        <td class="px-4 py-3">${{ number_format($order->grand_total, 2) }}</td>
                        <td class="px-4 py-3">
                            @switch($order->status)
                                @case('pending')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                    @break
                                @case('processing')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">
                                        Processing
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                    @break
                                @case('canceled')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">
                                        Canceled
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                        {{ ucfirst($order->status) }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.rydeen.orders.view', $order->id) }}"
                               class="text-gray-900 hover:text-gray-700 dark:text-yellow-400">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</x-admin::layouts>
