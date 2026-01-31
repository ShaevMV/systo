<?php

declare(strict_types=1);

namespace Tickets\Billing\Application\CreatingLinkForPay;

use Illuminate\Http\Client\RequestException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Billing\DTO\PaymentResponseDTO;
use Tickets\Billing\Services\BillingService;

class CreatingLinkForPayCommandHandler implements CommandHandler
{
    public function __construct(
        private BillingService $billingService
    )
    {
    }

    /**
     * @throws RequestException
     */
    public function __invoke(CreatingLinkForPayCommand $command): PaymentResponseDTO
    {
        return $this->billingService->createPayments(
            $command->getRequestDTO(),
            $command->getDeviceValueObject(),
        );
    }
}
