<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Access-лог коннектов от витрины qr к каналу приёма заказов (qrOrder/create).
 *
 * Пишет КАЖДЫЙ коннект — принятый и отклонённый (401) — в отдельный канал qr_access
 * (структурированный JSON, см. config/logging.php). Каждая запись помечена actor=qr /
 * sub=qr-service: так действия витрины qr отделяются от действий администраторов в логах.
 *
 * Безопасность: НЕ логируем сам ключ X-QR-Token и не парсим тело отклонённого (недоверенного)
 * запроса. order_id читаем только у принятого коннекта (ключ уже проверен). IP/UA нужны для
 * аудита источника. ПДн (email/гости) сюда не попадают — для них канал qr_pipeline с маскировкой.
 */
final class QrAccessLog
{
    public const CHANNEL = 'qr_access';

    private const ACTOR = 'qr';

    private const SUBJECT = 'qr-service';

    /** Коннект прошёл аутентификацию канала (ключ верный). */
    public static function accepted(Request $request): void
    {
        self::write('info', 'connect.accepted', self::context($request, [
            'result' => 'accepted',
            'order_id' => self::orderId($request),
        ]));
    }

    /**
     * Коннект отклонён (401). $reason: no_token (заголовок пуст) | bad_token (ключ не совпал).
     * Тело запроса НЕ читаем — он не аутентифицирован.
     */
    public static function rejected(Request $request, string $reason): void
    {
        self::write('warning', 'connect.rejected', self::context($request, [
            'result' => 'rejected',
            'reason' => $reason,
        ]));
    }

    /**
     * Мягкая запись в канал: сбой логирования (диск/права) НЕ должен ронять приём заказа.
     * Падение глушим в дефолтный лог через report() — приём продолжается.
     *
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        try {
            Log::channel(self::CHANNEL)->{$level}($message, $context);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Общий контекст записи. Без заголовков (чтобы не утёк ключ) и без ПДн.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function context(Request $request, array $extra): array
    {
        return array_merge([
            'actor' => self::ACTOR,
            'sub' => self::SUBJECT,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ua' => mb_substr((string) $request->userAgent(), 0, 200),
        ], $extra);
    }

    /** order_id из тела принятого запроса (uuid, не ПДн) — для корреляции с qr_pipeline. */
    private static function orderId(Request $request): ?string
    {
        $id = $request->input('order_id');

        return is_string($id) && $id !== '' ? $id : null;
    }
}
