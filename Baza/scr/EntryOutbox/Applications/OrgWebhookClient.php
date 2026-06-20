<?php

declare(strict_types=1);

namespace Baza\EntryOutbox\Applications;

use Baza\EntryOutbox\Dto\EntryOutboxDto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Исходящий S2S-клиент вебхука «билет прошёл» Baza→org (Ф4).
 *
 * POST {url}/api/v1/baza/ticketEntered, заголовок X-Baza-Token (зеркало ingest Ф3).
 * Включается только при заданных url+token (env ORG_WEBHOOK_URL / ORG_WEBHOOK_TOKEN);
 * иначе isEnabled()=false → дренаж ничего не шлёт (вход и так работает, буфер копится).
 */
final class OrgWebhookClient
{
    private const PATH = '/api/v1/baza/ticketEntered';

    private const TIMEOUT = 5;

    public function isEnabled(): bool
    {
        return $this->baseUrl() !== '' && $this->token() !== '';
    }

    /**
     * @return bool true — org принял; false — org вернул успех=false; null — выключено/ошибка транспорта.
     */
    public function send(EntryOutboxDto $row): ?bool
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withHeaders(['X-Baza-Token' => $this->token()])
                ->acceptJson()
                ->timeout(self::TIMEOUT)
                ->post($this->baseUrl().self::PATH, $row->toWebhookPayload());

            if (! $response->successful()) {
                Log::warning('baza.webhook.http_error', ['id' => $row->id, 'status' => $response->status()]);

                return null;
            }

            return (bool) $response->json('success', false);
        } catch (Throwable $e) {
            Log::warning('baza.webhook.transport_error', ['id' => $row->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.org_webhook.url', ''), '/');
    }

    private function token(): string
    {
        return (string) config('services.org_webhook.token', '');
    }
}
