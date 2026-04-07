<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Throwable;
use Tickets\Questionnaire\Application\Questionnaire\Approve\QuestionnaireApproveCommand;
use Tickets\Questionnaire\Application\Questionnaire\Approve\QuestionnaireApproveCommandHandler;
use Tickets\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommand;
use Tickets\Questionnaire\Application\Questionnaire\Create\QuestionnaireCreateCommandHandler;
use Tickets\Questionnaire\Application\Questionnaire\ExistsByEmail\QuestionnaireExistsByEmailQuery;
use Tickets\Questionnaire\Application\Questionnaire\ExistsByEmail\QuestionnaireExistsByEmailQueryHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetByOrderTicket\QuestionnaireGetByOrderTicketQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetByOrderTicket\QuestionnaireGetByOrderTicketQueryHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetItem\QuestionnaireGetItemQueryHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetList\QuestionnaireGetListQueryHandler;
use Tickets\Questionnaire\Application\Questionnaire\GetQuestionnaireTypeByOrderTicket\GetQuestionnaireTypeByOrderTicketQuery;
use Tickets\Questionnaire\Application\Questionnaire\GetQuestionnaireTypeByOrderTicket\GetQuestionnaireTypeByOrderTicketQueryHandler;
use Tickets\Questionnaire\Application\Questionnaire\SendTelegram\SendTelegramCommand;
use Tickets\Questionnaire\Application\Questionnaire\SendTelegram\SendTelegramCommandHandler;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

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
        QuestionnaireGetByOrderTicketQueryHandler $questionnaireGetByOrderTicketQueryHandler,
        GetQuestionnaireTypeByOrderTicketQueryHandler $getQuestionnaireTypeByOrderTicketQueryHandler,
        QuestionnaireExistsByEmailQueryHandler $questionnaireExistsByEmailQueryHandler,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            QuestionnaireApproveCommand::class => $questionnaireApproveCommandHandler,
            QuestionnaireCreateCommand::class => $questionnaireCommandHandler,
            SendTelegramCommand::class => $sendTelegramCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            QuestionnaireGetItemQuery::class => $questionnaireGetItemQueryHandler,
            QuestionnaireGetListQuery::class => $questionnaireGetListQueryHandler,
            QuestionnaireGetByOrderTicketQuery::class => $questionnaireGetByOrderTicketQueryHandler,
            GetQuestionnaireTypeByOrderTicketQuery::class => $getQuestionnaireTypeByOrderTicketQueryHandler,
            QuestionnaireExistsByEmailQuery::class => $questionnaireExistsByEmailQueryHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function create(QuestionnaireTicketDto $questionnaireTicketDto): void
    {
        $this->commandBus->dispatch(new QuestionnaireCreateCommand($questionnaireTicketDto));
    }

    public function getItemId(int $id): ?QuestionnaireTicketDto
    {
        /** @var QuestionnaireTicketDto|null $result */
        $result = $this->queryBus->ask(new QuestionnaireGetItemQuery($id));

        return $result;
    }

    public function getList(QuestionnaireGetListQuery $query): ?QuestionnaireGetListQueryResponse
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

    public function getByOrderTicket(Uuid $orderId, Uuid $ticketId): ?QuestionnaireTicketDto
    {
        /** @var QuestionnaireTicketDto|null $result */
        $result = $this->queryBus->ask(new QuestionnaireGetByOrderTicketQuery($orderId, $ticketId));

        return $result;
    }

    public function getQuestionnaireTypeByOrderTicket(Uuid $orderId, Uuid $ticketId): ?QuestionnaireTypeModel
    {
        /** @var QuestionnaireTypeModel|null $result */
        $result = $this->queryBus->ask(new GetQuestionnaireTypeByOrderTicketQuery($orderId, $ticketId));

        return $result;
    }
}
