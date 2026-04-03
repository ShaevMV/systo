<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\Approve;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Questionnaire\Domain\Questionnaire;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class QuestionnaireApproveCommandHandler implements CommandHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository,
        private \Bus $bus,
    )
    {
    }

    public function __invoke(QuestionnaireApproveCommand $command): void
    {
        $questionnaire = Questionnaire::toApprove($this->repository->get($command->getId()));
        $this->bus::chain($questionnaire->pullDomainEvents())->dispatch();

        $this->repository->cacheStatus($command->getId(),QuestionnaireStatus::APPROVE);
    }
}
