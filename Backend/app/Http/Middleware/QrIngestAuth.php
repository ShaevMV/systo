<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tickets\QrOrder\Application\Support\QrAccessLog;

/**
 * Аутентификация S2S-канала приёма заказов от витрины qr.spaceofjoy.ru.
 *
 * Витрина qr предъявляет сервисный ключ в заголовке "X-QR-Token"; org сверяет его со списком
 * валидных ключей из конфига (services.qr_ingest.tokens, источник — env QR_INGEST_TOKENS).
 *
 * Список ключей (через запятую) даёт ротацию без простоя: на время смены ключа держим
 * старый + новый одновременно, qr переключается на новый, затем старый убираем из .env.
 *
 * Безопасный дефолт: пустой список (канал не сконфигурирован) → доступ закрыт.
 * Сравнение через hash_equals — защита от timing-атаки (как в auto_payment).
 */
class QrIngestAuth
{
    /** Заголовок, в котором qr передаёт сервисный ключ. */
    public const HEADER = 'X-QR-Token';

    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->headers->get(self::HEADER, '');

        if ($provided !== '' && $this->matchesKnownToken($provided)) {
            QrAccessLog::accepted($request);

            return $next($request);
        }

        // Отклонённый коннект логируем с причиной: нет заголовка vs неверный ключ.
        QrAccessLog::rejected($request, $provided === '' ? 'no_token' : 'bad_token');

        return response()->json([
            'success' => false,
            'message' => 'Доступ запрещён: неверный или отсутствующий ключ канала qr',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Совпадает ли предъявленный ключ хотя бы с одним из настроенных (constant-time сравнение).
     */
    private function matchesKnownToken(string $provided): bool
    {
        /** @var list<string> $tokens */
        $tokens = config('services.qr_ingest.tokens', []);

        foreach ($tokens as $token) {
            if ($token !== '' && hash_equals((string) $token, $provided)) {
                return true;
            }
        }

        return false;
    }
}
