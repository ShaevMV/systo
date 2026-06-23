<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Consumer;

use Psr\Log\LoggerInterface;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\QrEmailIntake;
use Tickets\QrOrder\Application\QrOrderApplication;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Чистое ядро консьюмера qr→org: по типу сообщения (routing key) направляет тело
 * в ТЕ ЖЕ точки приёма, что и HTTP (QrOrderApplication / QrEmailIntake), НЕ дублируя
 * бизнес-логику. Идемпотентность — внутри Application (existsById / issued_at /
 * existsByExternalId): повторная доставка (at-least-once) безопасна.
 *
 * Не знает про AMQP → юнит-тестируется. Транспорт (ack/nack/dlq) — в QrConsumeCommand.
 */
final class QrInboundMessageHandler
{
    public function __construct(
        private readonly QrOrderApplication $orders,
        private readonly QrEmailIntake $emails,
        private readonly LoggerInterface $log,
    ) {
    }

    /**
     * @param  string  $type  routing key сообщения (qr.order.create / qr.order.status / qr.email.send)
     * @param  array<string, mixed>  $body  десериализованное тело сообщения
     */
    public function handle(string $type, array $body): QrHandleOutcome
    {
        try {
            return match ($type) {
                'qr.order.create' => $this->handleOrderCreate($body),
                'qr.order.status' => $this->handleOrderStatus($body),
                'qr.email.send' => $this->handleEmail($body),
                default => $this->dlq('неизвестный тип сообщения: '.$type),
            };
        } catch (\InvalidArgumentException $e) {
            // Битый контракт (нет order_id/email, кривой uuid, >1000 гостей) = 422-эквивалент.
            // Повтор того же тела опять упадёт → в DLQ, не ретраим.
            return $this->dlq('невалидный контракт: '.$e->getMessage());
        } catch (\Throwable $e) {
            // Транзиентный сбой (БД недоступна и т.п.) → повторить.
            $this->log->warning('qr-consume: транзиентная ошибка', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return QrHandleOutcome::Retry;
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function handleOrderCreate(array $body): QrHandleOutcome
    {
        // fromQrContract — единственная точка валидации контракта (бросает InvalidArgumentException
        // при отсутствии order_id/email, >1000 гостей).
        $dto = QrOrderDto::fromQrContract($body);
        // Идемпотентность внутри: existsById($dto->getId()) → повтор не создаёт дубль и не выдаёт
        // билеты дважды (id qr == id org).
        $this->orders->create($dto);

        return QrHandleOutcome::Ack;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function handleOrderStatus(array $body): QrHandleOutcome
    {
        $id = trim((string) ($body['order_id'] ?? ''));
        $status = trim((string) ($body['status'] ?? ''));
        if ($id === '' || $status === '') {
            return $this->dlq('changeStatus: нужны order_id и status');
        }

        // new Uuid бросит InvalidArgumentException на кривом id → перехват выше → DLQ.
        $ok = $this->orders->changeStatus(new Uuid($id), $status);

        // false = заказа ещё нет (сообщение «оплачен» могло обогнать «создан»): повторим до предела,
        // дальше уйдёт в DLQ. Идемпотентность выдачи защищена issued_at внутри changeStatus.
        return $ok ? QrHandleOutcome::Ack : QrHandleOutcome::Retry;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function handleEmail(array $body): QrHandleOutcome
    {
        $result = $this->emails->ingest($body);

        // 'invalid' = битый контракт письма → DLQ; 'accepted'/'duplicate' → успех (идемпотентно).
        return ($result['status'] ?? '') === 'invalid'
            ? $this->dlq('email: '.($result['message'] ?? 'невалидное письмо'))
            : QrHandleOutcome::Ack;
    }

    private function dlq(string $reason): QrHandleOutcome
    {
        $this->log->warning('qr-consume: сообщение в DLQ', ['reason' => $reason]);

        return QrHandleOutcome::Dlq;
    }
}
