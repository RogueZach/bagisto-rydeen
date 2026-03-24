<?php

return [
    'device_trust_days'    => env('DEALER_DEVICE_TRUST_DAYS', 30),
    'code_expiry_minutes'  => 10,
    'code_resend_cooldown' => 60,
    'code_max_per_hour'    => 5,
    'admin_order_email'    => env('ADMIN_MAIL_ADDRESS', 'orders@rydeenmobile.com'),
];
