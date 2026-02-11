<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\Approve;

use Shared\Domain\Bus\Command\CommandHandler;
use Shared\Questionnaire\Domain\Questionnaire;
use Shared\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
use Shared\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

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
