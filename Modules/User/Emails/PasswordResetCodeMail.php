<?php

namespace Modules\User\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The code that lets someone set a new password from their e-mail address.
 *
 * Deliberately short-lived and free of links: the app asks for the four
 * digits, so there is nothing in the message to click by mistake.
 */
class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $minutes,
        public ?string $name = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Password reset code'));
    }

    public function content(): Content
    {
        return new Content(view: 'user::emails.password-reset-code');
    }
}
