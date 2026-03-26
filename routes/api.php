<?php

use Illuminate\Support\Facades\Route;

// Temporary test route — remove after verifying Resend email works
Route::get('test-email/{email}', function (string $email) {
    \Illuminate\Support\Facades\Mail::to($email)->send(
        new \Rydeen\Auth\Mail\VerificationCodeMail('123456')
    );

    return response()->json(['status' => 'sent', 'to' => $email]);
});
