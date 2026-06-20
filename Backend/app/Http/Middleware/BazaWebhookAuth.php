<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Аутентификация S2S-канала приёма вебхука «билет прошёл» от Baza (Ф4).
 *
 * Baza предъявляет ключ в заголовке "X-Baza-Token"; org сверяет со списком валидных ключей
 * (services.baza_webhook.tokens ← env BAZA_WEBHOOK_TOKENS). Зеркало QrIngestAuth.
 *
 * ВАЖНО: это ВХОДЯЩИЙ канал (Baza→org), отдельный от исходящего services.baza_ingest
 * (org→Baza, url+token Ф3) — не путать. Пустой список → канал закрыт (безопасный дефолт).
 */
class BazaWebhookAuth
{
    public const HEADER = 'X-Baza-Token';

    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->headers->get(self::HEADER, '');

        if ($provided !== '' && $this->matchesKnownToken($provided)) {
            return $next($request);
        }

        Log::warning('baza.webhook.rejected', [
            'reason' => $provided === '' ? 'no_token' : 'bad_token',
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Доступ запрещён: неверный или отсутствующий ключ канала baza-webhook',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function matchesKnownToken(string $provided): bool
    {
        /** @var list<string> $tokens */
        $tokens = config('services.baza_webhook.tokens', []);

        foreach ($tokens as $token) {
            if ($token !== '' && hash_equals((string) $token, $provided)) {
                return true;
            }
        }

        return false;
    }
}
