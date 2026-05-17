<?php

namespace App\Services\Notifications\Channels;

use App\Models\User;
use App\Services\Notifications\OmbalaClient;
use App\Services\Notifications\Settings;
use RuntimeException;

/**
 * Canal SMS via Ombala (https://api.useombala.ao).
 * Lança RuntimeException quando desactivado, sem chave, ou em erro HTTP.
 * O NotificationSender apanha e regista como `failed` no histórico.
 */
class SmsChannel implements Channel
{
    public function __construct(protected OmbalaClient $client)
    {
    }

    public function send(string $recipientAddress, string $subject, string $body): array
    {
        if (! filter_var(Settings::get('sms.enabled'), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Canal SMS desactivado (OMBALA_ENABLED=false).');
        }
        if (! $this->client->isConfigured()) {
            throw new RuntimeException('OMBALA_API_KEY não definida no .env.');
        }

        $from = (string) Settings::get('sms.sender_id') ?: 'gestSchool';
        $this->client->sendSms(to: $recipientAddress, message: $body, from: $from);

        return ['address' => $recipientAddress, 'error' => null];
    }

    public function addressFromUser(User $user): ?string
    {
        return $user->phone ?: null;
    }
}
