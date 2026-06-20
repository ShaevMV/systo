<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application\Client;

use Illuminate\Support\Facades\Http;
use Throwable;
use Tickets\BazaDelivery\Application\Support\BazaDeliveryLog;

/**
 * Исходящий S2S-клиент записи билета в Baza через ingest-API (Ф3).
 *
 * Шлёт POST {url}/api/baza/ingest/ticket с заголовком X-Baza-Token (зеркало приёма qr→org).
 * Канал включается только если заданы и URL, и токен (env BAZA_INGEST_URL / BAZA_INGEST_TOKEN);
 * иначе isEnabled()=false и доставка идёт прежним путём (прямая запись в БД Baza) — поведение
 * org не меняется до явной настройки канала.
 */
final class BazaIngestClient
{
    private const PATH = '/api/baza/ingest/ticket';

    /** Таймаут запроса к Baza (сек) — короткий, чтобы при недоступности быстро падать в fallback. */
    private const TIMEOUT = 5;

    public function isEnabled(): bool
    {
        return $this->baseUrl() !== '' && $this->token() !== '';
    }

    /**
     * @param  array<string, mixed>  $ticket
     * @param  array<string, mixed>  $search  опц. богатые поля гостя для поискового индекса Baza (ticket_search)
     * @return bool|null true — Baza применила запись; false — Baza явно НЕ применила (200 success:false,
     *                   напр. live-номера ещё нет); null — канал выключен / HTTP-ошибка / транспорт упал.
     *                   В случае false/null вызывающий откатывается на прямую запись.
     */
    public function send(string $target, array $ticket, array $search = []): ?bool
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $body = ['target' => $target, 'ticket' => $ticket];
        if ($search !== []) {
            $body['search'] = $search;
        }

        try {
            $response = Http::withHeaders(['X-Baza-Token' => $this->token()])
                ->acceptJson()
                ->timeout(self::TIMEOUT)
                ->post($this->baseUrl().self::PATH, $body);

            if (! $response->successful()) {
                BazaDeliveryLog::logger()->warning('baza.ingest.http_error', [
                    'target' => $target,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return (bool) $response->json('success', false);
        } catch (Throwable $e) {
            BazaDeliveryLog::logger()->warning('baza.ingest.transport_error', [
                'target' => $target,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.baza_ingest.url', ''), '/');
    }

    private function token(): string
    {
        return (string) config('services.baza_ingest.token', '');
    }
}
