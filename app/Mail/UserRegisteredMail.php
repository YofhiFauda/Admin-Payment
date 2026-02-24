<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class UserRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifyUrl;
    public string $plainPassword;

    public function __construct(
        public User $user,
        string $plainPassword
    ) {
        $this->plainPassword = $plainPassword;
        $this->verifyUrl = URL::temporarySignedRoute(
            'users.verify',
            now()->addHours(72),
            ['id' => $user->id]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Akun FinanceOps Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-user',
        );
    }
}
