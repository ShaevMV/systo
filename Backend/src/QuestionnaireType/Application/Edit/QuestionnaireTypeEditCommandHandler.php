<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

class QuestionnaireTypeEditCommandHandler implements CommandHandler
{
    public function __construct(
        private QuestionnaireTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireTypeEditCommand $command): void
    {
        $this->repository->editItem($command->getId(), $command->getData());
    }
}
