<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\Create;
use Shared\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;

class QuestionnaireCreateCommandHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository,
        private UserRepositoriesInterface $userRepositories,
    )
    {
    }

    public function __invoke(QuestionnaireCreateCommand $command): void
    {
        $command->getQuestionnaireTicketDto()->setUserId(
            $command->getQuestionnaireTicketDto()->getEmail() ?
                $this->userRepositories->findAccountByEmail(
                    $command->getQuestionnaireTicketDto()->getEmail()
                )?->getId() : null
        );

        $this->repository->create($command->getQuestionnaireTicketDto());
    }
}
