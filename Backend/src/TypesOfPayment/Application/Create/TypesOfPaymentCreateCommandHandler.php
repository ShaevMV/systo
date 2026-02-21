<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class TypesOfPaymentCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TypesOfPaymentCreateCommand $command): void
    {
        $this->repository->editItem(
            $command->getId(),
            $command->getPaymentDto(),
        );
    }
}
