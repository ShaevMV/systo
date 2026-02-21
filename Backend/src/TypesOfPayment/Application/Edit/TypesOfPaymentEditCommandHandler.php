<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class TypesOfPaymentEditCommandHandler implements CommandHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TypesOfPaymentEditCommand $command): void
    {
        $this->repository->editItem(
            $command->getId(),
            $command->getPaymentDto(),
        );
    }
}
