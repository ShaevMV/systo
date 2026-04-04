<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

class QuestionnaireTypeCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private QuestionnaireTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(QuestionnaireTypeCreateCommand $command): void
    {
        $this->repository->create($command->getData());
    }
}
