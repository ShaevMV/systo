<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\Approve;

use Illuminate\Support\Facades\Auth;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Questionnaire\Domain\Questionnaire;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class QuestionnaireApproveCommandHandler implements CommandHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository,
        private HistoryRepositoryInterface $history,
        private \Bus $bus,
    )
    {
    }

    public function __invoke(QuestionnaireApproveCommand $command): void
    {
        $questionnaire = Questionnaire::toApprove($this->repository->get($command->getId()));
        $this->bus::chain($questionnaire->pullDomainEvents())->dispatch();

        // Факт одобрения — в историю (actor = админ, выполнивший approve; БД только в репозитории).
        foreach ($questionnaire->pullHistoryEvents() as $event) {
            $this->history->save(new SaveHistoryDto(
                (string) $command->getId(),
                $event,
                Auth::id(),
                ActorType::USER,
            ));
        }

        $this->repository->cacheStatus($command->getId(),QuestionnaireStatus::APPROVE);
    }
}
