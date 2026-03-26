<x-admin::layouts>
    <x-slot:title>
        Contact: {{ $contact->full_name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Contact: {{ $contact->full_name }}
        </p>

        <a href="{{ route('admin.rydeen.contacts.index') }}"
           class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            Back to Contacts
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Contact Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Contact Information</h2>

        <form action="{{ route('admin.rydeen.contacts.update', $contact->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $contact->first_name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                    @error('first_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $contact->last_name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                    @error('last_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $contact->email) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">{{ old('notes', $contact->notes) }}</textarea>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $contact->is_active ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <span class="text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Dealer Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Dealer</h2>
        <div class="text-sm">
            <p><strong>{{ $contact->dealer->first_name }} {{ $contact->dealer->last_name }}</strong></p>
            <p class="text-gray-500">{{ $contact->dealer->email }}</p>
        </div>
    </div>

    {{-- Associated Orders --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Associated Orders</h2>

        @if ($contact->orders->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 bg-gray-50 dark:bg-gray-900 dark:text-gray-300 border-b">
                        <tr>
                            <th class="px-4 py-3">Order #</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contact->orders as $order)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">{{ $order->increment_id ?? $order->id }}</td>
                                <td class="px-4 py-3">{{ ucfirst($order->status) }}</td>
                                <td class="px-4 py-3">${{ number_format($order->grand_total, 2) }}</td>
                                <td class="px-4 py-3">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.rydeen.orders.view', $order->id) }}" class="text-blue-600 hover:underline text-xs">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No orders associated with this contact yet.</p>
        @endif
    </div>
</x-admin::layouts>
