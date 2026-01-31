<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire;

use Shared\Domain\Criteria\Filter;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommand;
use Tickets\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommandHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQueryHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQueryHandler;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

class QuestionnaireApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        QuestionnaireCreateCommandHandler $questionnaireCommandHandler,
        QuestionnaireGetItemQueryHandler $questionnaireGetItemQueryHandler,
        QuestionnaireGetListQueryHandler $questionnaireGetListQueryHandler,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            QuestionnaireCreateCommand::class => $questionnaireCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            QuestionnaireGetItemQuery::class => $questionnaireGetItemQueryHandler,
            QuestionnaireGetListQuery::class => $questionnaireGetListQueryHandler,
        ]);
    }

    /**
     * @throws \Throwable
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

    public function getList(QuestionnaireGetListQuery $query):QuestionnaireGetListQueryResponse
    {
        /** @var QuestionnaireGetListQueryResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

}
