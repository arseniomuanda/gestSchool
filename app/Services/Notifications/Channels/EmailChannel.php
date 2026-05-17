<?php

namespace App\Services\Notifications\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements Channel
{
    public function send(string $recipientAddress, string $subject, string $body): array
    {
        // Credenciais SMTP vêm directamente de config/mail.php (.env)
        Mail::raw($body, function ($message) use ($recipientAddress, $subject) {
            $message->to($recipientAddress)->subject($subject);
        });

        return ['address' => $recipientAddress, 'error' => null];
    }

    public function addressFromUser(User $user): ?string
    {
        return $user->email ?: null;
    }
}
