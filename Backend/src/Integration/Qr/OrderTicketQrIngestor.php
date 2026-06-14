<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr;

use Psr\Log\LoggerInterface;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Shared\Integration\Rabbit\EventEnvelope;
use Tickets\History\Domain\ActorType;
use Tickets\Integration\Qr\Assembler\AssembledQrOrder;
use Tickets\Integration\Qr\Assembler\QrOrderAssembler;
use Tickets\Integration\Qr\Assembler\QrOrderType;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Application\ChangeStatus\ChangeStatus;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Dto\AccountDto;

/**
 * Боевой приём заказа от витрины qr (Ф3): {@see AssembledQrOrder} → создание заказа org
 * через существующие Application-сервисы (CONTRACT_RFC_v0.md §7).
 *
 * Переиспользует штатный pipeline: createAndSave (NEW) → ChangeStatus(PAID, ActorType::QR)
 * запускает ProcessCreateTicket → билеты → PDF/QR → письма → история (как авто-оплата).
 *
 * Цена — ДЕКЛАРИРОВАННАЯ qr (Р2): сборку строк и снимок цены/опций делает {@see QrGuestRowBuilder}.
 *
 * Ф3.1: поддержан тип `regular`. friendly/live/list — Ф3.2 (открытые вопросы §6.2/§6.4/списки).
 */
final class OrderTicketQrIngestor implements QrOrderIngestorInterface
{
    public function __construct(
        private readonly QrOrderAssembler $assembler,
        private readonly QrGuestRowBuilder $rowBuilder,
        private readonly AccountApplication $accountApplication,
        private readonly CreateOrder $createOrder,
        private readonly ChangeStatus $changeStatus,
        private readonly AddComment $addComment,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function ingest(EventEnvelope $envelope): void
    {
        $order = $this->assembler->assemble($envelope);

        // Ф3.1 — только regular. Остальные типы требуют решений §6.2/§6.4 и list-семантики.
        if ($order->type->value !== QrOrderType::REGULAR) {
            throw new QrOrderRejectedException(sprintf(
                'Тип заказа "%s" пока не поддержан приёмом qr→org (Ф3.2)',
                $order->type->value,
            ));
        }

        $this->ingestRegular($order, $envelope);
    }

    private function ingestRegular(AssembledQrOrder $order, EventEnvelope $envelope): void
    {
        $userId = new Uuid(
            $this->accountApplication->creatingOrGetAccountId(AccountDto::fromState([
                'email' => $order->recipientEmail,
                'name' => $order->recipientName ?? '',
                'phone' => $order->recipientPhone ?? '',
                'city' => $order->recipientCity ?? '',
            ]))->value()
        );

        $data = [
            'festival_id' => $order->festivalId,
            'email' => $order->recipientEmail,
            'phone' => $order->recipientPhone ?? '',
            'types_of_payment_id' => $order->typesOfPaymentId,
            'guests' => $this->rowBuilder->build($order),
        ];

        $orderTicketDto = OrderTicketDto::fromState($data, $userId);

        $this->createOrder->createAndSave($orderTicketDto);

        if ($order->comment !== null) {
            $this->addComment->send($orderTicketDto->getId(), $userId, $order->comment);
        }

        // Деньги уже приняты в qr → сразу PAID. Запускает билеты/PDF/письма + историю (actor=qr).
        $this->changeStatus->change(
            $orderTicketDto->getId(),
            new Status(Status::PAID),
            $userId,
            null,           // comment — добавляется отдельно через AddComment
            false,          // now
            0,              // delay
            [],             // liveList
            ActorType::QR,  // actorType
        );

        $this->logger->info('[qr-ingest] Заказ regular создан и оплачен', [
            'trace_id' => $envelope->traceId,
            'qr_order_id' => $order->qrOrderId,
            'order_id' => $orderTicketDto->getId()->value(),
            'guests' => count($order->guests),
        ]);
    }
}
