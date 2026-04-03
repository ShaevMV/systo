<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\SendTelegram;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Questionnaire\Domain\Questionnaire;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class SendTelegramCommandHandler implements CommandHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $repository,
        private \Bus $bus,
    ){}

    public function __invoke(SendTelegramCommand $command): void
    {
        $dto  = $this->repository->get($command->getId());

        $questionnaire = Questionnaire::toSendTelegram($dto);

        $this->bus::chain($questionnaire->pullDomainEvents())->dispatch();
    }
}