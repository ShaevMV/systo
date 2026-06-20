<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Аутентификация S2S-канала приёма билетов от org (Ф3).
 *
 * org предъявляет сервисный ключ в заголовке "X-Baza-Token"; Baza сверяет его со списком
 * валидных ключей из конфига (services.baza_ingest.tokens, источник — env BAZA_INGEST_TOKENS).
 * Зеркало org-овского QrIngestAuth (X-QR-Token).
 *
 * Список ключей (через запятую) даёт ротацию без простоя. Безопасный дефолт: пустой список
 * (канал не сконфигурирован) → доступ закрыт. Сравнение через hash_equals — защита от timing-атаки.
 */
class BazaIngestAuth
{
    /** Заголовок, в котором org передаёт сервисный ключ. */
    public const HEADER = 'X-Baza-Token';

    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->headers->get(self::HEADER, '');

        if ($provided !== '' && $this->matchesKnownToken($provided)) {
            return $next($request);
        }

        Log::channel(config('logging.default'))->warning('baza.ingest.rejected', [
            'reason' => $provided === '' ? 'no_token' : 'bad_token',
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Доступ запрещён: неверный или отсутствующий ключ канала baza-ingest',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Совпадает ли предъявленный ключ хотя бы с одним из настроенных (constant-time сравнение).
     */
    private function matchesKnownToken(string $provided): bool
    {
        /** @var list<string> $tokens */
        $tokens = config('services.baza_ingest.tokens', []);

        foreach ($tokens as $token) {
            if ($token !== '' && hash_equals((string) $token, $provided)) {
                return true;
            }
        }

        return false;
    }
}
