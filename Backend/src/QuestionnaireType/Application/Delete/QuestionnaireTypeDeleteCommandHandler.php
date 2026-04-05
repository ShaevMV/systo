<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

class QuestionnaireTypeDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private QuestionnaireTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireTypeDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
