<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Rydeen\Dealer\Mail\CompanyInvitationMail;

class CompanyInvitationListener
{
    public function afterCreated($customer): void
    {
        if ($customer->type !== 'company') {
            return;
        }

        $token = Password::broker('customers')->createToken($customer);

        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($customer->email));

        try {
            Mail::to($customer->email)->send(new CompanyInvitationMail($customer, $resetUrl));

            session()->flash('info', "Onboarding email sent to {$customer->email}");
        } catch (\Exception $e) {
            report($e);

            session()->flash('warning', 'Company created but onboarding email failed — use Resend Invitation from the list.');
        }
    }
}
