<?php

declare(strict_types=1);

namespace Shared\Integration\Rabbit;

use InvalidArgumentException;

/**
 * EventSigner — подпись и проверка межсервисных сообщений (HMAC-SHA256 + anti-replay).
 *
 * Прототип (см. `.claude/specs/qr-integration/CONTRACT_RFC_v0.md` §6).
 * Подписывается строка `timestamp . "." . body` — timestamp входит в подпись, поэтому
 * атакующий не может подменить его для обхода anti-replay (паттерн Stripe webhooks).
 *
 * Зачем: канал между независимыми системами (qr/org/BAZA) несёт команды на выпуск
 * билета и ПДн. Без подписи компрометация/спуфинг = бесплатный билет + проход.
 * Сравнение через {@see hash_equals} (защита от timing-attack).
 */
final class EventSigner
{
    public function __construct(
        private readonly string $secret,
        private readonly int $maxSkewSeconds = 300,
    ) {
        if ($this->secret === '') {
            throw new InvalidArgumentException('EventSigner: секрет подписи пуст (задайте RABBITMQ_SIGNING_SECRET)');
        }
    }

    /**
     * Вычислить подпись тела с привязкой к timestamp.
     */
    public function sign(string $body, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $body, $this->secret);
    }

    /**
     * Проверить подпись и свежесть сообщения.
     *
     * @param int $now текущее время (UNIX); параметр для тестируемости
     * @return bool true — подпись верна и сообщение не устарело/не из будущего
     */
    public function verify(string $body, int $timestamp, string $signature, int $now): bool
    {
        // Anti-replay: окно ±maxSkew. Слишком старое (replay) или из будущего — отклоняем.
        if (abs($now - $timestamp) > $this->maxSkewSeconds) {
            return false;
        }

        $expected = $this->sign($body, $timestamp);

        return hash_equals($expected, $signature);
    }
}
