<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;

class SettingsController extends Controller
{
    public function index()
    {
        $admins = Admin::orderBy('name')->get();

        $selectedIds = [];
        $configValue = DB::table('core_config')
            ->where('code', 'rydeen.order_notification_admin_ids')
            ->value('value');

        if ($configValue) {
            $selectedIds = json_decode($configValue, true) ?? [];
        }

        return view('rydeen-dealer::admin.settings.index', compact('admins', 'selectedIds'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'admin_ids'   => 'nullable|array',
            'admin_ids.*' => 'integer|exists:admins,id',
        ]);

        $adminIds = $validated['admin_ids'] ?? [];
        $value = json_encode(array_map('intval', $adminIds));

        DB::table('core_config')->updateOrInsert(
            ['code' => 'rydeen.order_notification_admin_ids'],
            [
                'value'      => $value,
                'channel_code' => 'default',
                'locale_code'  => 'en',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Notification settings saved.');
    }
}
