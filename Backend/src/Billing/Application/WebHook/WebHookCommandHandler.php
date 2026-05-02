<?php

declare(strict_types=1);

namespace Tickets\Billing\Application\WebHook;

use Shared\Domain\Bus\Command\CommandHandler;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\ActorType;
use Tickets\Order\OrderTicket\Application\ChangeStatus\ChangeStatus;
use Tickets\Orders\Guest\Repository\GuestOrderRepositoryInterface;
use Tickets\Orders\Shared\Facade\OrderFacade;

class WebHookCommandHandler implements CommandHandler
{
    /** UUID системного актора (биллинг-сервис). */
    private const BILLING_ACTOR_ID = 'b9df62af-252a-4890-afd7-73c2a356c259';

    public function __construct(
        private readonly ChangeStatus                  $chanceStatus,
        private readonly GuestOrderRepositoryInterface $guestRepository,
        private readonly OrderFacade                   $orderFacade,
    ) {}

    /** @throws \Throwable */
    public function __invoke(WebHookCommand $command): void
    {
        // Новая система: GuestOrder обрабатывается через OrderFacade.
        // Старая система (order_tickets): обрабатывается через ChangeStatus (v1).
        if ($this->guestRepository->findById($command->getOrderId()) !== null) {
            $this->handleGuestOrder($command);
        } else {
            $this->handleLegacyOrder($command);
        }
    }

    /** @throws \Throwable */
    private function handleGuestOrder(WebHookCommand $command): void
    {
        $actorId = new Uuid(self::BILLING_ACTOR_ID);

        if ($command->getStatus()->isPaymentCompleted()) {
            $this->orderFacade->changeGuestStatus(
                orderId:   $command->getOrderId(),
                newStatus: new Status(Status::PAID),
                params:    [
                    'email'   => '',
                    'comment' => $this->buildReceiptComment($command->getLinkToReceipt()),
                ],
                actorId:   $actorId,
                actorType: ActorType::SYSTEM,
            );
            return;
        }

        if ($command->getStatus()->isPaymentRefund()) {
            $this->orderFacade->changeGuestStatus(
                orderId:   $command->getOrderId(),
                newStatus: new Status(Status::CANCEL),
                params:    ['email' => ''],
                actorId:   $actorId,
                actorType: ActorType::SYSTEM,
            );
            return;
        }

        $this->orderFacade->changeGuestStatus(
            orderId:   $command->getOrderId(),
            newStatus: new Status(Status::DIFFICULTIES_AROSE),
            params:    [
                'email'   => '',
                'comment' => 'Статус платежа не обработан: ' . $command->getStatus()->getStatus(),
            ],
            actorId:   $actorId,
            actorType: ActorType::SYSTEM,
        );
    }

    /** @throws \Throwable */
    private function handleLegacyOrder(WebHookCommand $command): void
    {
        if (!$command->getStatus()->isPaymentCompleted()) {
            return;
        }

        $this->chanceStatus->change(
            orderId:   $command->getOrderId(),
            status:    new Status(Status::PAID),
            userId:    new Uuid(self::BILLING_ACTOR_ID),
            comment:   $this->buildReceiptComment($command->getLinkToReceipt()),
            actorType: ActorType::SYSTEM,
        );
    }

    private function buildReceiptComment(?string $link): string
    {
        if ($link === null) {
            return '';
        }

        return '<br/> <a href="' . $link . '"> Ссылка на чек </a>';
    }
}
