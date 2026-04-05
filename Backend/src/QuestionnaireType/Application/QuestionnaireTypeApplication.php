<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\QuestionnaireType\Application\Create\QuestionnaireTypeCreateCommand;
use Tickets\QuestionnaireType\Application\Create\QuestionnaireTypeCreateCommandHandler;
use Tickets\QuestionnaireType\Application\Delete\QuestionnaireTypeDeleteCommand;
use Tickets\QuestionnaireType\Application\Delete\QuestionnaireTypeDeleteCommandHandler;
use Tickets\QuestionnaireType\Application\Edit\QuestionnaireTypeEditCommand;
use Tickets\QuestionnaireType\Application\Edit\QuestionnaireTypeEditCommandHandler;
use Tickets\QuestionnaireType\Application\GetItem\QuestionnaireTypeGetItemQuery;
use Tickets\QuestionnaireType\Application\GetItem\QuestionnaireTypeGetItemQueryHandler;
use Tickets\QuestionnaireType\Application\GetByCode\QuestionnaireTypeGetByCodeQuery;
use Tickets\QuestionnaireType\Application\GetByCode\QuestionnaireTypeGetByCodeQueryHandler;
use Tickets\QuestionnaireType\Application\GetList\QuestionnaireTypeGetListQuery;
use Tickets\QuestionnaireType\Application\GetList\QuestionnaireTypeGetListQueryHandler;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;
use Tickets\QuestionnaireType\Response\QuestionnaireTypeGetListResponse;

class QuestionnaireTypeApplication
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(
        QuestionnaireTypeGetListQueryHandler $questionnaireTypeGetListQueryHandler,
        QuestionnaireTypeGetItemQueryHandler $questionnaireTypeGetItemQueryHandler,
        QuestionnaireTypeGetByCodeQueryHandler $questionnaireTypeGetByCodeQueryHandler,
        QuestionnaireTypeCreateCommandHandler $questionnaireTypeCreateCommandHandler,
        QuestionnaireTypeEditCommandHandler $questionnaireTypeEditCommandHandler,
        QuestionnaireTypeDeleteCommandHandler $questionnaireTypeDeleteCommandHandler,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            QuestionnaireTypeCreateCommand::class => $questionnaireTypeCreateCommandHandler,
            QuestionnaireTypeEditCommand::class => $questionnaireTypeEditCommandHandler,
            QuestionnaireTypeDeleteCommand::class => $questionnaireTypeDeleteCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            QuestionnaireTypeGetListQuery::class => $questionnaireTypeGetListQueryHandler,
            QuestionnaireTypeGetItemQuery::class => $questionnaireTypeGetItemQueryHandler,
            QuestionnaireTypeGetByCodeQuery::class => $questionnaireTypeGetByCodeQueryHandler,
        ]);
    }

    public function getList(QuestionnaireTypeGetListQuery $query): QuestionnaireTypeGetListResponse
    {
        /** @var QuestionnaireTypeGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $uuid): QuestionnaireTypeDto
    {
        /** @var QuestionnaireTypeDto $result */
        $result = $this->queryBus->ask(new QuestionnaireTypeGetItemQuery($uuid));

        return $result;
    }

    public function getByCode(string $code): QuestionnaireTypeDto
    {
        /** @var QuestionnaireTypeDto $result */
        $result = $this->queryBus->ask(new QuestionnaireTypeGetByCodeQuery($code));

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function edit(Uuid $id, QuestionnaireTypeDto $data): bool
    {
        $this->commandBus->dispatch(new QuestionnaireTypeEditCommand($id, $data));
        return true;
    }

    /**
     * @throws \Throwable
     */
    public function create(QuestionnaireTypeDto $data): bool
    {
        $this->commandBus->dispatch(new QuestionnaireTypeCreateCommand($data));

        return true;
    }

    /**
     * @throws \Throwable
     */
    public function delete(Uuid $id): bool
    {
        $this->commandBus->dispatch(new QuestionnaireTypeDeleteCommand($id));

        return true;
    }
}
