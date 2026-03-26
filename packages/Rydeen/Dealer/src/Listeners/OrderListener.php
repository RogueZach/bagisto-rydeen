<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\Mail;
use Rydeen\Dealer\Mail\OrderConfirmationMail;
use Rydeen\Dealer\Mail\OrderSubmittedMail;

class OrderListener
{
    /**
     * Handle the event after an order is created.
     */
    public function afterOrderCreated($order): void
    {
        // Send notification to admin
        try {
            $adminEmail = config('rydeen.admin_order_email', 'orders@test.reform9.com');
            Mail::to($adminEmail)->send(new OrderSubmittedMail($order));
        } catch (\Exception $e) {
            report($e);
        }

        // Send confirmation to dealer
        try {
            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
