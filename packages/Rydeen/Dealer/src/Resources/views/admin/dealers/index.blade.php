<x-admin::layouts>
    <x-slot:title>
        @lang('rydeen-dealer::app.admin.dealers-title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('rydeen-dealer::app.admin.dealers-title')
        </p>
    </div>

    <div class="mt-7 overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-600 bg-gray-50 dark:bg-gray-900 dark:text-gray-300 border-b">
                <tr>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.id')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.name')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.email')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.status')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.approved-at')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.forecast-level')</th>
                    <th class="px-4 py-3">@lang('rydeen-dealer::app.admin.actions')</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($dealers as $dealer)
                    <tr class="border-b dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-3">{{ $dealer->id }}</td>
                        <td class="px-4 py-3">{{ $dealer->first_name }} {{ $dealer->last_name }}</td>
                        <td class="px-4 py-3">{{ $dealer->email }}</td>
                        <td class="px-4 py-3">
                            @if ($dealer->is_suspended)
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800">
                                    @lang('rydeen-dealer::app.admin.suspended')
                                </span>
                            @elseif ($dealer->is_verified)
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">
                                    @lang('rydeen-dealer::app.admin.approved')
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">
                                    @lang('rydeen-dealer::app.admin.pending')
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $dealer->approved_at ? \Carbon\Carbon::parse($dealer->approved_at)->format('M d, Y') : '—' }}</td>
                        <td class="px-4 py-3">{{ $dealer->forecast_level ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.rydeen.dealers.view', $dealer->id) }}"
                               class="text-gray-900 hover:text-gray-700 dark:text-yellow-400">
                                @lang('rydeen-dealer::app.admin.view')
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            @lang('rydeen-dealer::app.admin.no-dealers')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $dealers->links() }}
        </div>
    </div>
</x-admin::layouts>
