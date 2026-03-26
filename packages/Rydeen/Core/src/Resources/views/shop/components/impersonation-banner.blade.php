@if (session('impersonating_admin_id'))
    <div class="bg-amber-400 text-amber-900 px-4 py-2 text-center text-sm font-medium flex items-center justify-center gap-3">
        <span>
            @lang('rydeen-dealer::app.admin.impersonation-banner')
            <strong>{{ auth('customer')->user()?->first_name }} {{ auth('customer')->user()?->last_name }}</strong>
        </span>
        <form action="{{ route('dealer.impersonate.stop') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="underline font-bold hover:text-amber-800">
                @lang('rydeen-dealer::app.admin.impersonation-return')
            </button>
        </form>
    </div>
@endif
