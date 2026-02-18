<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire;

use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Shared\Questionnaire\Application\Questionnaire\Approve\QuestionnaireApproveCommand;
use Shared\Questionnaire\Application\Questionnaire\Approve\QuestionnaireApproveCommandHandler;
use Shared\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommand;
use Shared\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommandHandler;
use Shared\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQuery;
use Shared\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQueryHandler;
use Shared\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Shared\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQueryHandler;
use Shared\Questionnaire\Application\Questionnaire\SendTelegram\SendTelegramCommand;
use Shared\Questionnaire\Application\Questionnaire\SendTelegram\SendTelegramCommandHandler;
use Shared\Questionnaire\Dto\QuestionnaireTicketDto;
use Shared\Questionnaire\Responses\QuestionnaireGetListQueryResponse;
use Throwable;

class QuestionnaireApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        QuestionnaireCreateCommandHandler $questionnaireCommandHandler,
        QuestionnaireApproveCommandHandler $questionnaireApproveCommandHandler,
        SendTelegramCommandHandler $sendTelegramCommandHandler,

        QuestionnaireGetItemQueryHandler $questionnaireGetItemQueryHandler,
        QuestionnaireGetListQueryHandler $questionnaireGetListQueryHandler,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            QuestionnaireApproveCommand::class => $questionnaireApproveCommandHandler,
            QuestionnaireCreateCommand::class => $questionnaireCommandHandler,
            SendTelegramCommand::class => $sendTelegramCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            QuestionnaireGetItemQuery::class => $questionnaireGetItemQueryHandler,
            QuestionnaireGetListQuery::class => $questionnaireGetListQueryHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function create(QuestionnaireTicketDto $questionnaireTicketDto): void
    {
        $this->commandBus->dispatch(new QuestionnaireCreateCommand($questionnaireTicketDto));
    }

    public function getItemId(int $id): ?QuestionnaireGetListQueryResponse
    {
        /** @var  QuestionnaireGetListQueryResponse|null $result */
        $result = $this->queryBus->ask(new QuestionnaireGetItemQuery($id));

        return $result;
    }

    public function getList(QuestionnaireGetListQuery $query):?QuestionnaireGetListQueryResponse
    {
        /** @var QuestionnaireGetListQueryResponse|null $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    /**
     * @throws Throwable
     */
    public function approve(int $id): void
    {
        $this->commandBus->dispatch(new QuestionnaireApproveCommand($id));
    }

    /**
     * @throws Throwable
     */
    public function sendTelegram(int $id): void
    {
        $this->commandBus->dispatch(new SendTelegramCommand($id));
    }
}
