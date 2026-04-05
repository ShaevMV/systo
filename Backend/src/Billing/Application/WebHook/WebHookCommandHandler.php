<?php

declare(strict_types=1);

namespace Tickets\Billing\Application\WebHook;

use Shared\Domain\Bus\Command\CommandHandler;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Application\ChangeStatus\ChangeStatus;
use Shared\Domain\ValueObject\Status;

class WebHookCommandHandler implements CommandHandler
{
    public function __construct(
        private ChangeStatus $chanceStatus,
    )
    {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(WebHookCommand $command): void
    {
        $comment = match (true) {
            $command->getStatus()->isPaymentRefund() => 'Возврат платежа',
            $command->getStatus()->isPaymentCompleted() => $this->insertLink($command->getLinkToReceipt()),
            default => 'Статус платежа не обработан ' . $command->getStatus()->getStatus()
        };

        $status = match (true) {
            $command->getStatus()->isPaymentRefund() => new Status(Status::CANCEL),
            $command->getStatus()->isPaymentCompleted() => new Status(Status::PAID),
            default => new Status(Status::DIFFICULTIES_AROSE),
        };

        if($command->getStatus()->isPaymentCompleted()) {
            $this->chanceStatus->change(
                $command->getOrderId(),
                $status,
                new Uuid('b9df62af-252a-4890-afd7-73c2a356c259'),
                $comment
            );
        }

    }

    private function insertLink(?string $link): string
    {
        if(null === $link) {
            return '';
        }

        return '<br/> <a href="$link"> Ссылка на чек </a>';
    }
}
