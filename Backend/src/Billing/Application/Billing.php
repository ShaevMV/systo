<?php

declare(strict_types=1);


namespace Tickets\Billing\Application;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Billing\Application\CreatingLinkForPay\CreatingLinkForPayCommand;
use Tickets\Billing\Application\CreatingLinkForPay\CreatingLinkForPayCommandHandler;
use Tickets\Billing\Application\WebHook\WebHookCommand;
use Tickets\Billing\Application\WebHook\WebHookCommandHandler;
use Tickets\Billing\DTO\PaymentRequestDTO;
use Tickets\Billing\DTO\PaymentResponseDTO;
use Tickets\Billing\ValueObject\DeviceValueObject;
use Tickets\Billing\ValueObject\StatusForBillingValueObject;

class Billing
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        WebHookCommandHandler $commandHandler,
        CreatingLinkForPayCommandHandler $creatingLinkForPayCommandHandler,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            WebHookCommand::class => $commandHandler,
            CreatingLinkForPayCommand::class => $creatingLinkForPayCommandHandler,
        ]);
    }


    /**
     * @throws \Throwable
     */
    public function webHook(
        string $orderId,
        string $status,
        ?string $linkToReceipt = null,
    ): void
    {
        $this->commandBus->dispatch(new WebHookCommand(
            new Uuid($orderId),
            new StatusForBillingValueObject($status),
            $linkToReceipt
        ));
    }

    /**
     * @throws \Throwable
     */
    public function creatingLink(
        PaymentRequestDTO $requestDTO,
        DeviceValueObject $deviceValueObject,
    ): PaymentResponseDTO
    {
        /** @var PaymentResponseDTO|null $result */
        $result = $this->commandBus->dispatch(new CreatingLinkForPayCommand(
            $requestDTO,
            $deviceValueObject,
        ));

        if($result === null || !empty($result->getError())) {
            throw new \DomainException($result->getError() ?? "Пустой ответ!!!");
        }

        return $result;
    }
}
