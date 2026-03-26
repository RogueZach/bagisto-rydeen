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

it('admin can resend invitation for a company', function () {
    Mail::fake();

    $admin = getTestAdmin();
    $channelId = DB::table('channels')->value('id') ?? 1;
    $groupId = DB::table('customer_groups')->value('id') ?? 1;

    $customerId = DB::table('customers')->insertGetId([
        'first_name'        => 'Resend',
        'last_name'         => 'Test',
        'email'             => 'resend-' . uniqid() . '@example.com',
        'password'          => bcrypt('password'),
        'type'              => 'company',
        'customer_group_id' => $groupId,
        'channel_id'        => $channelId,
        'is_verified'       => 1,
        'status'            => 1,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.resend-invitation', $customerId));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Mail::assertQueued(CompanyInvitationMail::class, function ($mail) use ($customerId) {
        $customer = Customer::find($customerId);
        return $mail->hasTo($customer->email);
    });

    // Cleanup
    $email = DB::table('customers')->where('id', $customerId)->value('email');
    DB::table('customer_password_resets')->where('email', $email)->delete();
    DB::table('customers')->where('id', $customerId)->delete();
});

it('resend invitation rejects non-company customers', function () {
    Mail::fake();

    $admin = getTestAdmin();
    $customerId = createPendingDealer(); // type is not 'company'

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.rydeen.dealers.resend-invitation', $customerId));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    Mail::assertNothingQueued();

    // Cleanup
    DB::table('customers')->where('id', $customerId)->delete();
});

if (! function_exists('getTestAdmin')) {
    function getTestAdmin(): \Webkul\User\Models\Admin
    {
        $admin = \Webkul\User\Models\Admin::where('email', 'rydeen-test-admin@example.com')->first();

        if (! $admin) {
            $roleId = DB::table('roles')->value('id') ?? 1;
            $id = DB::table('admins')->insertGetId([
                'name'       => 'Test Admin',
                'email'      => 'rydeen-test-admin@example.com',
                'password'   => bcrypt('password'),
                'status'     => 1,
                'role_id'    => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $admin = \Webkul\User\Models\Admin::find($id);
        }

        return $admin;
    }
}

if (! function_exists('createPendingDealer')) {
    function createPendingDealer(): int
    {
        $channelId = DB::table('channels')->value('id') ?? 1;
        $groupId = DB::table('customer_groups')->value('id') ?? 1;

        return DB::table('customers')->insertGetId([
            'first_name'        => 'Pending',
            'last_name'         => 'Dealer',
            'email'             => 'pending-' . uniqid() . '@example.com',
            'password'          => bcrypt('password'),
            'type'              => 'user',
            'customer_group_id' => $groupId,
            'channel_id'        => $channelId,
            'is_verified'       => 0,
            'status'            => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
