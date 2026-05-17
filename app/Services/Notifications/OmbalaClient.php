<?php

namespace App\Services\Notifications;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente HTTP para a API SMS da Ombala (https://api.useombala.ao).
 *
 * Autenticação: header `Authorization: Token <uuid>` (chave do `.env`).
 * Credenciais e flags vivem todas no `.env` (ver Settings::ENV_MAP).
 *
 * Endpoints suportados:
 *   - POST /v1/messages       → enviar SMS
 *   - GET  /v1/credits        → consultar saldo
 *   - GET  /v1/senders/approved → listar remetentes aprovados
 *
 * Não invocar directamente para enviar — usar SmsChannel via NotificationSender.
 */
class OmbalaClient
{
    public const DEFAULT_BASE_URL = 'https://api.useombala.ao';
    public const CREDITS_CACHE_KEY = 'ombala.credits';
    public const CREDITS_CACHE_TTL = 60;       // segundos
    public const SENDERS_CACHE_KEY = 'ombala.senders.approved';
    public const SENDERS_CACHE_TTL = 300;

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
    ) {
        $this->apiKey ??= (string) Settings::get('sms.api_key');
        $this->baseUrl = rtrim($this->baseUrl ?? (string) Settings::get('sms.api_url') ?: self::DEFAULT_BASE_URL, '/');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Envia uma SMS. Retorna a resposta HTTP em sucesso (status 2xx),
     * lança RuntimeException com mensagem clara em falha.
     *
     * @param string $to       Número do destinatário (normalizado para formato AO)
     * @param string $message  Texto da SMS
     * @param string $from     Sender aprovado no portal Ombala
     * @param string|null $scheduleAt  Ex: '20251015182000' (yyyyMMddHHmmss)
     */
    public function sendSms(string $to, string $message, string $from, ?string $scheduleAt = null): array
    {
        $this->assertConfigured();

        $payload = [
            'message' => $message,
            'from' => $from,
            'to' => $this->normalizePhone($to),
        ];
        if ($scheduleAt) {
            $payload['schedule'] = $scheduleAt;
        }

        $response = $this->http()->post('/v1/messages', $payload);

        // Cache do saldo fica obsoleto após cada envio
        Cache::forget(self::CREDITS_CACHE_KEY);

        if (! $response->successful()) {
            throw $this->errorFromResponse($response, 'Envio de SMS falhou');
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    /**
     * Saldo de SMS no portal Ombala. Cacheado 60s.
     * Devolve null se não conseguir obter (ex: API down) — sem lançar.
     */
    public function credits(bool $force = false): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }
        if ($force) {
            Cache::forget(self::CREDITS_CACHE_KEY);
        }
        return Cache::remember(self::CREDITS_CACHE_KEY, self::CREDITS_CACHE_TTL, function () {
            try {
                $r = $this->http()->get('/v1/credits');
                if (! $r->successful()) return null;
                $body = $r->json();
                // O spec não define schema; tentar várias chaves comuns
                foreach (['credits', 'balance', 'saldo', 'available'] as $k) {
                    if (isset($body[$k]) && is_numeric($body[$k])) return (int) $body[$k];
                }
                // Top-level integer
                if (is_int($body)) return $body;
                if (is_numeric($body)) return (int) $body;
                return null;
            } catch (\Throwable $e) {
                Log::warning('[ombala] credits failed: '.$e->getMessage());
                return null;
            }
        });
    }

    /**
     * Lista de senders aprovados. Cacheada 5min.
     * @return array<int,string>  nomes dos senders aprovados
     */
    public function approvedSenders(bool $force = false): array
    {
        if (! $this->isConfigured()) return [];
        if ($force) Cache::forget(self::SENDERS_CACHE_KEY);

        return Cache::remember(self::SENDERS_CACHE_KEY, self::SENDERS_CACHE_TTL, function () {
            try {
                $r = $this->http()->get('/v1/senders/approved');
                if (! $r->successful()) return [];
                $body = $r->json();
                // O spec não define schema; tentar várias formas
                if (is_array($body)) {
                    if (isset($body['data']) && is_array($body['data'])) {
                        return array_values(array_filter(array_map(
                            fn ($s) => is_array($s) ? ($s['name'] ?? null) : (is_string($s) ? $s : null),
                            $body['data']
                        )));
                    }
                    return array_values(array_filter(array_map(
                        fn ($s) => is_array($s) ? ($s['name'] ?? null) : (is_string($s) ? $s : null),
                        $body
                    )));
                }
                return [];
            } catch (\Throwable $e) {
                Log::warning('[ombala] senders/approved failed: '.$e->getMessage());
                return [];
            }
        });
    }

    /**
     * Normaliza um número de telefone para o formato esperado pela Ombala
     * (9 dígitos sem prefixo internacional — ex: 921939411).
     *
     * Remove espaços, hifens, parêntesis, `+` e prefixos `00244`/`244`.
     */
    public function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($digits, '00244')) $digits = substr($digits, 5);
        elseif (str_starts_with($digits, '244')) $digits = substr($digits, 3);
        return $digits;
    }

    protected function http()
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['Authorization' => 'Token '.$this->apiKey])
            ->timeout(15)
            ->connectTimeout(5);
    }

    protected function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Ombala não configurada — definir OMBALA_API_KEY no .env.');
        }
    }

    protected function errorFromResponse(Response $r, string $prefix): RuntimeException
    {
        $msg = $r->json('message') ?? $r->json('error') ?? $r->body();
        if (is_array($msg)) $msg = json_encode($msg);
        return new RuntimeException(sprintf('%s (HTTP %d): %s', $prefix, $r->status(), \Illuminate\Support\Str::limit((string) $msg, 200)));
    }
}
