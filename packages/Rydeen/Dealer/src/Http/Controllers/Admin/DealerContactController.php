<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rydeen\Dealer\DataGrids\DealerContactDataGrid;
use Rydeen\Dealer\Models\DealerContact;

class DealerContactController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return app(DealerContactDataGrid::class)->toJson();
        }

        return view('rydeen-dealer::admin.contacts.index');
    }

    public function view(int $id)
    {
        $contact = DealerContact::with(['dealer', 'orders'])->findOrFail($id);

        return view('rydeen-dealer::admin.contacts.view', compact('contact'));
    }

    public function update(Request $request, int $id)
    {
        $contact = DealerContact::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'notes'      => 'nullable|string|max:1000',
            'is_active'  => 'boolean',
        ]);

        $contact->update($validated);

        return redirect()->back()->with('success', 'Contact updated successfully.');
    }
}
