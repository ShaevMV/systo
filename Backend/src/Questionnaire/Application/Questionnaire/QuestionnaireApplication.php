<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommand;
use Tickets\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommandHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQueryHandler;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Responses\QuestionnaireGetItemQueryResponse;

class QuestionnaireApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        QuestionnaireCreateCommandHandler $questionnaireCommandHandler,
        QuestionnaireGetItemQueryHandler $questionnaireGetItemQueryHandler,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            QuestionnaireCreateCommand::class => $questionnaireCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            QuestionnaireGetItemQuery::class => $questionnaireGetItemQueryHandler
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function create(QuestionnaireTicketDto $questionnaireTicketDto): void
    {
        $this->commandBus->dispatch(new QuestionnaireCreateCommand($questionnaireTicketDto));
    }

    public function getItemByOrderId(Uuid $orderId): ?QuestionnaireGetItemQueryResponse
    {
        /** @var  QuestionnaireGetItemQueryResponse|null $result */
        $result = $this->queryBus->ask(new QuestionnaireGetItemQuery($orderId));

        return $result;
    }

}
