<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Rydeen\Auth\Mail\VerificationCodeMail;

class SendTestEmail extends Command
{
    protected $signature = 'rydeen:test-email {email}';
    protected $description = 'Send a test verification code email via Resend';

    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info("Sending test verification email to {$email}...");

        try {
            Mail::to($email)->send(new VerificationCodeMail('123456'));
            $this->info('Sent successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
