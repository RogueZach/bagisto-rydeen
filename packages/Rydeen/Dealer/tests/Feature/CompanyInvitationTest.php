<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Rydeen\Dealer\Mail\CompanyInvitationMail;
use Rydeen\Dealer\Listeners\CompanyInvitationListener;
use Webkul\Customer\Models\Customer;

it('sends invitation email when a company-type customer is created', function () {
    Mail::fake();

    $channelId = DB::table('channels')->value('id') ?? 1;
    $groupId = DB::table('customer_groups')->value('id') ?? 1;

    $customerId = DB::table('customers')->insertGetId([
        'first_name'        => 'Company',
        'last_name'         => 'Admin',
        'email'             => 'company-' . uniqid() . '@example.com',
        'password'          => bcrypt('password'),
        'type'              => 'company',
        'customer_group_id' => $groupId,
        'channel_id'        => $channelId,
        'is_verified'       => 1,
        'status'            => 1,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $customer = Customer::find($customerId);

    $listener = new CompanyInvitationListener();
    $listener->afterCreated($customer);

    Mail::assertQueued(CompanyInvitationMail::class, function ($mail) use ($customer) {
        return $mail->hasTo($customer->email);
    });

    // Cleanup
    DB::table('customer_password_resets')->where('email', $customer->email)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('does not send invitation for non-company customers', function () {
    Mail::fake();

    $channelId = DB::table('channels')->value('id') ?? 1;
    $groupId = DB::table('customer_groups')->value('id') ?? 1;

    $customerId = DB::table('customers')->insertGetId([
        'first_name'        => 'Regular',
        'last_name'         => 'Customer',
        'email'             => 'regular-' . uniqid() . '@example.com',
        'password'          => bcrypt('password'),
        'type'              => 'person',
        'customer_group_id' => $groupId,
        'channel_id'        => $channelId,
        'is_verified'       => 0,
        'status'            => 0,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $customer = Customer::find($customerId);

    $listener = new CompanyInvitationListener();
    $listener->afterCreated($customer);

    Mail::assertNothingQueued();

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});
