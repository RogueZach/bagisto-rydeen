<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;
use Rydeen\Dealer\Mail\CompanyInvitationMail;
use Rydeen\Dealer\Mail\DealerApprovedMail;
use Rydeen\Dealer\Http\Traits\ScopesForRep;

class DealerApprovalController extends Controller
{
    use ScopesForRep;

    /**
     * List all dealers (pending and active).
     */
    public function index()
    {
        $query = Customer::orderByDesc('created_at');

        if ($repId = $this->repId()) {
            $query->where('assigned_rep_id', $repId);
        }

        $dealers = $query->paginate(25);

        return view('rydeen-dealer::admin.dealers.index', compact('dealers'));
    }

    /**
     * Show dealer detail.
     */
    public function view(int $id)
    {
        $dealer = Customer::findOrFail($id);

        if ($repId = $this->repId()) {
            abort_unless((int) $dealer->assigned_rep_id === $repId, 403);
        }

        $admins = Admin::orderBy('name')->get();

        return view('rydeen-dealer::admin.dealers.view', compact('dealer', 'admins'));
    }

    /**
     * Approve a dealer — set is_verified, approved_at, status.
     */
    public function approve(int $id)
    {
        $dealer = Customer::findOrFail($id);

        $dealer->is_verified = 1;
        $dealer->status = 1;
        $dealer->approved_at = now();
        $dealer->save();

        // Send approval notification
        $loginUrl = route('dealer.login');

        try {
            Mail::to($dealer->email)->send(new DealerApprovedMail($dealer, $loginUrl));
        } catch (\Exception $e) {
            // Log but don't block approval
            report($e);
        }

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.dealer-approved'));
    }

    /**
     * Reject/suspend a dealer.
     */
    public function reject(int $id)
    {
        $dealer = Customer::findOrFail($id);

        $dealer->is_suspended = 1;
        $dealer->is_verified = 0;
        $dealer->status = 0;
        $dealer->approved_at = null;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.dealer-rejected'));
    }

    /**
     * Unsuspend a dealer — clear suspension and restore pending state.
     */
    public function unsuspend(int $id)
    {
        $dealer = Customer::findOrFail($id);

        $dealer->is_suspended = 0;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.dealer-unsuspended'));
    }

    /**
     * Assign a sales rep to a dealer.
     */
    public function assignRep(Request $request, int $id)
    {
        $request->validate([
            'assigned_rep_id' => 'nullable|exists:admins,id',
        ]);

        $dealer = Customer::findOrFail($id);
        $dealer->assigned_rep_id = $request->assigned_rep_id;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.rep-assigned'));
    }

    /**
     * Update forecast level for a dealer.
     */
    public function updateForecastLevel(Request $request, int $id)
    {
        $request->validate([
            'forecast_level' => 'nullable|string|max:255',
        ]);

        $dealer = Customer::findOrFail($id);
        $dealer->forecast_level = $request->forecast_level;
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.forecast-updated'));
    }

    /**
     * Resend invitation email with a password-set link.
     */
    public function resendInvitation(int $id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->type !== 'company') {
            return redirect()->back()->with('error', trans('rydeen-dealer::app.admin.invitation-not-company'));
        }

        $loginUrl = route('dealer.login');

        try {
            Mail::to($customer->email)->send(new CompanyInvitationMail($customer, $loginUrl));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', trans('rydeen-dealer::app.admin.invitation-send-failed'));
        }

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.invitation-sent'));
    }

    /**
     * Delete a dealer and associated records.
     */
    public function destroy(int $id)
    {
        $dealer = Customer::findOrFail($id);

        // Remove associated Rydeen records
        \Rydeen\Dealer\Models\DealerAddress::where('customer_id', $dealer->id)->delete();
        \Rydeen\Dealer\Models\DealerContact::where('customer_id', $dealer->id)->delete();

        $dealer->delete();

        return redirect()->route('admin.rydeen.dealers.index')
            ->with('success', trans('rydeen-dealer::app.admin.dealer-deleted'));
    }

    /**
     * Approve a dealer address.
     */
    public function approveAddress(int $dealerId, int $addressId)
    {
        $dealer = Customer::findOrFail($dealerId);
        \Illuminate\Support\Facades\DB::table('rydeen_dealer_addresses')
            ->where('id', $addressId)
            ->where('customer_id', $dealer->id)
            ->update(['is_approved' => true, 'updated_at' => now()]);
        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.address-approved'));
    }
}
