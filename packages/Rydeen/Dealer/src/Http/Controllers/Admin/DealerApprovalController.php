<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;
use Rydeen\Dealer\Mail\CompanyInvitationMail;
use Rydeen\Dealer\Mail\DealerApprovedMail;

class DealerApprovalController extends Controller
{
    /**
     * List all dealers (pending and active).
     */
    public function index()
    {
        $dealers = Customer::orderByDesc('created_at')->paginate(25);

        return view('rydeen-dealer::admin.dealers.index', compact('dealers'));
    }

    /**
     * Show dealer detail.
     */
    public function view(int $id)
    {
        $dealer = Customer::findOrFail($id);
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
        try {
            Mail::to($dealer->email)->send(new DealerApprovedMail($dealer));
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
        $dealer->save();

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.dealer-rejected'));
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

        $token = Password::broker('customers')->createToken($customer);
        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($customer->email));

        try {
            Mail::to($customer->email)->send(new CompanyInvitationMail($customer, $resetUrl));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', trans('rydeen-dealer::app.admin.invitation-send-failed'));
        }

        return redirect()->back()->with('success', trans('rydeen-dealer::app.admin.invitation-sent'));
    }
}
