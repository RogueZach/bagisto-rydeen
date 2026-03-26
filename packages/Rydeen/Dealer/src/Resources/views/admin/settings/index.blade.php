<x-admin::layouts>
    <x-slot:title>
        Rydeen Settings
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Rydeen Settings
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Order Notification Recipients</h2>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Select which admin users should receive email notifications when a dealer places an order.
            If none are selected, notifications will be sent to <code>{{ config('rydeen.admin_order_email') }}</code>.
        </p>

        <form action="{{ route('admin.rydeen.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded p-3">
                @foreach ($admins as $admin)
                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-1 rounded">
                        <input type="checkbox"
                               name="admin_ids[]"
                               value="{{ $admin->id }}"
                               {{ in_array($admin->id, $selectedIds) ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <span class="text-gray-700 dark:text-gray-300">{{ $admin->name }}</span>
                        <span class="text-gray-400 text-xs">({{ $admin->email }})</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
