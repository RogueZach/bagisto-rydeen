<x-admin::layouts>
    <x-slot:title>
        @lang('rydeen-dealer::app.admin.dealer-detail') — {{ $dealer->first_name }} {{ $dealer->last_name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('rydeen-dealer::app.admin.dealer-detail')
        </p>

        <a href="{{ route('admin.rydeen.dealers.index') }}"
           class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            @lang('rydeen-dealer::app.admin.back')
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Dealer Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            @lang('rydeen-dealer::app.admin.dealer-info')
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.name'):</span>
                <span class="ml-2 font-medium">{{ $dealer->first_name }} {{ $dealer->last_name }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.email'):</span>
                <span class="ml-2 font-medium">{{ $dealer->email }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.phone'):</span>
                <span class="ml-2 font-medium">{{ $dealer->phone ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.status'):</span>
                <span class="ml-2 font-medium">
                    @if ($dealer->is_suspended)
                        <span class="text-red-600">@lang('rydeen-dealer::app.admin.suspended')</span>
                    @elseif ($dealer->is_verified)
                        <span class="text-green-600">@lang('rydeen-dealer::app.admin.approved')</span>
                    @else
                        <span class="text-yellow-600">@lang('rydeen-dealer::app.admin.pending')</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.approved-at'):</span>
                <span class="ml-2 font-medium">{{ $dealer->approved_at ? \Carbon\Carbon::parse($dealer->approved_at)->format('M d, Y H:i') : '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.registered'):</span>
                <span class="ml-2 font-medium">{{ $dealer->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.forecast-level'):</span>
                <span class="ml-2 font-medium">{{ $dealer->forecast_level ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">@lang('rydeen-dealer::app.admin.assigned-rep'):</span>
                <span class="ml-2 font-medium">
                    @if ($dealer->assigned_rep_id)
                        {{ optional(\Webkul\User\Models\Admin::find($dealer->assigned_rep_id))->name ?? '—' }}
                    @else
                        —
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    @php $isRep = auth('admin')->user()?->role?->name === 'Sales Rep'; @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Approve / Reject --}}
        @unless ($isRep)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                @lang('rydeen-dealer::app.admin.approval-actions')
            </h3>

            <div class="flex gap-3">
                @if (! $dealer->is_verified)
                    <form action="{{ route('admin.rydeen.dealers.approve', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="primary-button">
                            @lang('rydeen-dealer::app.admin.approve')
                        </button>
                    </form>
                @endif

                @if (! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.reject', $dealer->id) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to suspend this dealer? They will lose access immediately.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                            @lang('rydeen-dealer::app.admin.reject')
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.rydeen.dealers.unsuspend', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                            @lang('rydeen-dealer::app.admin.unsuspend')
                        </button>
                    </form>
                @endif

                @if ($dealer->type === 'company' && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.resend-invitation', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded hover:bg-yellow-600 text-sm">
                            @lang('rydeen-dealer::app.admin.resend-invitation')
                        </button>
                    </form>
                @endif

                @if ($dealer->is_verified && ! $dealer->is_suspended)
                    <form action="{{ route('admin.rydeen.dealers.impersonate', $dealer->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded text-sm font-medium text-white"
                                style="background-color: #f59e0b;"
                                onmouseover="this.style.backgroundColor='#d97706'"
                                onmouseout="this.style.backgroundColor='#f59e0b'">
                            @lang('rydeen-dealer::app.admin.impersonate')
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endunless

        {{-- Assign Rep --}}
        @unless ($isRep)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                @lang('rydeen-dealer::app.admin.assign-rep')
            </h3>

            <form action="{{ route('admin.rydeen.dealers.assign-rep', $dealer->id) }}" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">
                        @lang('rydeen-dealer::app.admin.sales-rep')
                    </label>
                    <select name="assigned_rep_id"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:text-white">
                        <option value="">— @lang('rydeen-dealer::app.admin.none') —</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected($dealer->assigned_rep_id == $admin->id)>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="primary-button">
                    @lang('rydeen-dealer::app.admin.save')
                </button>
            </form>
        </div>
        @endunless

        {{-- Forecast Level --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                @lang('rydeen-dealer::app.admin.forecast-level')
            </h3>

            <form action="{{ route('admin.rydeen.dealers.update-forecast', $dealer->id) }}" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">
                        @lang('rydeen-dealer::app.admin.forecast-level')
                    </label>
                    <input type="text"
                           name="forecast_level"
                           value="{{ old('forecast_level', $dealer->forecast_level) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:text-white"
                           placeholder="e.g. Gold, Silver, Bronze">
                </div>
                <button type="submit" class="primary-button">
                    @lang('rydeen-dealer::app.admin.save')
                </button>
            </form>
        </div>
    </div>

    {{-- Delete Dealer --}}
    @unless ($isRep)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6 border border-red-200 dark:border-red-800">
        <h3 class="text-md font-semibold text-red-600 dark:text-red-400 mb-2">
            @lang('rydeen-dealer::app.admin.danger-zone')
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            @lang('rydeen-dealer::app.admin.delete-dealer-warning')
        </p>
        <form action="{{ route('admin.rydeen.dealers.destroy', $dealer->id) }}" method="POST"
              onsubmit="return confirm('@lang('rydeen-dealer::app.admin.delete-dealer-confirm')')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                @lang('rydeen-dealer::app.admin.delete-dealer')
            </button>
        </form>
    </div>
    @endunless

    {{-- Shipping Addresses --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Shipping Addresses
        </h2>

        @php
            $addresses = \Rydeen\Dealer\Models\DealerAddress::forDealer($dealer->id)
                ->orderByDesc('created_at')
                ->get();
        @endphp

        @if ($addresses->isEmpty())
            <p class="text-sm text-gray-500">No shipping addresses on file.</p>
        @else
            <div class="space-y-3">
                @foreach ($addresses as $address)
                    <div class="flex items-start justify-between border border-gray-200 dark:border-gray-700 rounded p-3">
                        <div class="text-sm">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $address->label }}</span>
                                @if ($address->is_approved)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800">Pending</span>
                                @endif
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $address->first_name }} {{ $address->last_name }}
                                @if ($address->company_name) &mdash; {{ $address->company_name }} @endif
                            </p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $address->formatted_address }}</p>
                        </div>
                        @if (! $address->is_approved)
                            <form action="{{ route('admin.rydeen.dealers.approve-address', [$dealer->id, $address->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                    Approve
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin::layouts>
