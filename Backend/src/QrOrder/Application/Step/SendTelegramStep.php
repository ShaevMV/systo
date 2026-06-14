<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramSend;

/**
 * Шаг 4: уведомление гостей в Telegram-бот по username из контракта (guests[].telegram).
 *
 * Для qr анкеты в момент выдачи ещё нет, поэтому telegram приходит прямо в контракте (а не
 * ищется по анкете). На каждого гостя с непустым telegram ставит готовую задачу ProcessTelegramSend
 * (она шлёт в бот и имеет свой ретрай/обработку). Telegram-бот — сторонний чёрный ящик, поэтому
 * шаг изолирован: только ставит задачи и сам не падает.
 *
 * Мягкая валидация (по договорённости с владельцем — он связующее звено): telegram обязателен
 * на стороне qr, но org его НЕ требует — пустой/отсутствующий просто пропускается.
 */
final class SendTelegramStep implements PipelineStepInterface
{
    public function name(): string
    {
        return 'send_telegram';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        $payload = $order->getPayload();
        $guests = is_array($payload['guests'] ?? null) ? $payload['guests'] : [];
        $log = PipelineLog::logger();
        $queued = 0;

        foreach ($guests as $index => $guest) {
            if (! is_array($guest)) {
                continue;
            }

            // Срезаем ведущий '@' и пробелы — бот ждёт чистый username.
            $telegram = ltrim(trim((string) ($guest['telegram'] ?? '')), '@');

            if ($telegram === '') {
                $log->info('send_telegram.skip_empty', [
                    'order_id' => $order->getId()->value(),
                    'guest_index' => $index,
                ]);

                continue;
            }

            ProcessTelegramSend::dispatch($telegram);
            $queued++;
        }

        $log->info('send_telegram.queued', [
            'order_id' => $order->getId()->value(),
            'count' => $queued,
        ]);

        return $carry;
    }
}
