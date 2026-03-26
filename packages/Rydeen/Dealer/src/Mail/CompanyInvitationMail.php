<?php

namespace Rydeen\Dealer\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public $dealer,
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Rydeen Dealer Portal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'rydeen-dealer::shop.emails.company-invitation',
        );
    }
}
