<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notifications\Channels\Channel;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\SmsChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orquestra o envio de uma notificação a uma lista de destinatários,
 * por um ou mais canais, renderizando templates com placeholders.
 *
 * Uso típico:
 *   app(NotificationSender::class)->dispatch(
 *       eventKey: 'comunicado_publicado',
 *       recipients: $encarregadoUsers,
 *       channels: ['email'],
 *       payload: ['titulo' => $com->titulo, 'mensagem' => $com->mensagem],
 *   );
 */
class NotificationSender
{
    public function dispatch(
        string $eventKey,
        iterable $recipients,
        array $channels = ['email'],
        array $payload = [],
        string $locale = 'pt',
    ): array {
        $results = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($channels as $channelKey) {
            $channel = $this->resolveChannel($channelKey);
            $template = NotificationTemplate::query()
                ->where('event_key', $eventKey)
                ->where('channel', $channelKey)
                ->where('locale', $locale)
                ->active()
                ->first();

            if (! $template) {
                $results['skipped']++;
                continue;
            }

            foreach ($recipients as $user) {
                if (! $user instanceof User) {
                    continue;
                }
                $address = $channel->addressFromUser($user);
                if (! $address) {
                    $results['skipped']++;
                    continue;
                }

                $subject = $this->render($template->subject ?? '', $payload, $user);
                $body = $this->render($template->body, $payload, $user);

                $log = Notification::create([
                    'recipient_user_id' => $user->id,
                    'recipient_address' => $address,
                    'channel' => $channelKey,
                    'event_key' => $eventKey,
                    'status' => 'queued',
                    'subject' => $subject,
                    'body_preview' => Str::limit($body, 200),
                    'payload' => $payload,
                ]);

                try {
                    $channel->send($address, $subject, $body);
                    $log->update(['status' => 'sent', 'sent_at' => now()]);
                    $results['sent']++;
                } catch (\Throwable $e) {
                    $log->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 1000)]);
                    Log::warning("[notifications] {$eventKey}/{$channelKey} falhou para user #{$user->id}: ".$e->getMessage());
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Substitui {{placeholder}} no template pelos valores do payload + dados do user.
     */
    protected function render(string $tpl, array $payload, User $user): string
    {
        $vars = array_merge(
            [
                'nome_destinatario' => $user->name ?? '',
                'email_destinatario' => $user->email ?? '',
            ],
            $payload,
        );
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            fn ($m) => (string) ($vars[$m[1]] ?? ''),
            $tpl,
        ) ?? $tpl;
    }

    protected function resolveChannel(string $key): Channel
    {
        return match ($key) {
            'email' => app(EmailChannel::class),
            'sms' => app(SmsChannel::class),
            default => throw new \InvalidArgumentException("Canal de notificação desconhecido: {$key}"),
        };
    }
}
