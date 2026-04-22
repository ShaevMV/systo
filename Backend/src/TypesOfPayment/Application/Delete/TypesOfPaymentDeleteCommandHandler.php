<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class TypesOfPaymentDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TypesOfPaymentDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
