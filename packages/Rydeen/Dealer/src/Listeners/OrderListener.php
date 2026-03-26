<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Rydeen\Dealer\Mail\OrderConfirmationMail;
use Rydeen\Dealer\Mail\OrderCustomerNotificationMail;
use Rydeen\Dealer\Mail\OrderRepNotificationMail;
use Rydeen\Dealer\Mail\OrderSubmittedMail;
use Rydeen\Dealer\Models\DealerContact;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;

class OrderListener
{
    public function afterOrderCreated($order): void
    {
        $contact = $this->getContact($order);
        $dealer = Customer::find($order->customer_id);

        // 1. Send to admin(s)
        $this->sendToAdmins($order, $contact);

        // 2. Send to assigned rep
        $this->sendToRep($order, $contact, $dealer);

        // 3. Send confirmation to dealer (synchronous)
        $this->sendToDealer($order, $contact);

        // 4. Send notification to customer
        $this->sendToCustomer($order, $contact, $dealer);
    }

    protected function getContact($order): ?DealerContact
    {
        $contactId = DB::table('orders')
            ->where('id', $order->id)
            ->value('dealer_contact_id');

        return $contactId ? DealerContact::find($contactId) : null;
    }

    protected function sendToAdmins($order, ?DealerContact $contact): void
    {
        try {
            $recipients = $this->getAdminRecipients();

            foreach ($recipients as $email) {
                Mail::to($email)->queue(new OrderSubmittedMail($order, $contact));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function sendToRep($order, ?DealerContact $contact, ?Customer $dealer): void
    {
        if (! $dealer?->assigned_rep_id) {
            return;
        }

        try {
            $rep = Admin::find($dealer->assigned_rep_id);

            if ($rep?->email) {
                Mail::to($rep->email)->queue(
                    new OrderRepNotificationMail($order, $contact, $rep->name)
                );
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function sendToDealer($order, ?DealerContact $contact): void
    {
        try {
            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(
                    new OrderConfirmationMail($order, $contact)
                );
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function sendToCustomer($order, ?DealerContact $contact, ?Customer $dealer): void
    {
        if (! $contact?->email) {
            return;
        }

        try {
            $dealerName = trim(($dealer?->first_name ?? '') . ' ' . ($dealer?->last_name ?? ''));

            Mail::to($contact->email)->queue(
                new OrderCustomerNotificationMail($order, $contact, $dealerName ?: 'Your Dealer')
            );
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function getAdminRecipients(): array
    {
        // Check for configured admin recipients in core_config
        $configValue = DB::table('core_config')
            ->where('code', 'rydeen.order_notification_admin_ids')
            ->value('value');

        if ($configValue) {
            $adminIds = json_decode($configValue, true);

            if (is_array($adminIds) && count($adminIds) > 0) {
                return Admin::whereIn('id', $adminIds)
                    ->pluck('email')
                    ->toArray();
            }
        }

        // Fallback to env var
        return [config('rydeen.admin_order_email', 'orders@test.reform9.com')];
    }
}
