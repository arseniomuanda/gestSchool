<?php

namespace App\Services\Notifications;

/**
 * Resolver simples para credenciais de notificações.
 *
 * Email → lê directamente de `config/mail.php` (que por sua vez lê do `.env`).
 * SMS   → lê de `config/notifications.php` (que lê das OMBALA_* do `.env`).
 *
 * Em testes, qualquer chave pode ser sobreposta com `config()->set(...)`.
 */
class Settings
{
    /** Mapa de chave lógica → caminho em `config()`. */
    protected const CONFIG_MAP = [
        'email.from_address' => 'mail.from.address',
        'email.from_name'    => 'mail.from.name',
        'email.smtp_host'    => 'mail.mailers.smtp.host',
        'email.smtp_port'    => 'mail.mailers.smtp.port',

        'sms.enabled'        => 'notifications.sms.enabled',
        'sms.api_url'        => 'notifications.sms.api_url',
        'sms.api_key'        => 'notifications.sms.api_key',
        'sms.sender_id'      => 'notifications.sms.sender_id',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $path = self::CONFIG_MAP[$key] ?? null;
        if ($path === null) {
            return $default;
        }
        return config($path, $default);
    }
}
