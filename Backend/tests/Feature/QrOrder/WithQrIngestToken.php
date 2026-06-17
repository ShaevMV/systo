<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

/**
 * Хелпер для тестов S2S-канала приёма заказов qr→org: настраивает валидный сервисный ключ
 * и формирует заголовок X-QR-Token (middleware qr.ingest, см. App\Http\Middleware\QrIngestAuth).
 */
trait WithQrIngestToken
{
    protected string $qrIngestToken = 'test-qr-ingest-token';

    /** Положить валидный ключ в конфиг канала (вызывать в setUp). */
    protected function configureQrIngestToken(): void
    {
        config(['services.qr_ingest.tokens' => [$this->qrIngestToken]]);
    }

    /**
     * Заголовки с валидным сервисным ключом qr.
     *
     * @return array<string, string>
     */
    protected function qrIngestHeaders(): array
    {
        return ['X-QR-Token' => $this->qrIngestToken];
    }
}
