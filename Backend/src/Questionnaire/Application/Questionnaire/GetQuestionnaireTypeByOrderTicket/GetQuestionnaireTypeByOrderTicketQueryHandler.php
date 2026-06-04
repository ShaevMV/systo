<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetQuestionnaireTypeByOrderTicket;

use DomainException;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Application\GetTicketType\GetTicketType;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

/**
 * Определяет тип анкеты для конкретного гостя заказа (формат v2.6.0).
 *
 * В новом формате тип анкеты определяется **по строке гостя**: у каждого гостя свой
 * `ticket_type_id`, а у типа билета — свой `questionnaire_type_id`. Поэтому ищем строку
 * по `ticketId`, берём её `ticket_type` и его `questionnaire_type_id`.
 *
 * Fallback на гостевую анкету (`guest`), если:
 * - заказ/строка не найдены;
 * - у строки нет `ticket_type_id` (заказ-список);
 * - у типа билета нет `questionnaire_type_id`;
 * - привязанный тип анкеты отсутствует.
 *
 * Источник: Чистая архитектура — резолв через репозитории/Application-сервис, без прямого
 * обращения к БД из хендлера.
 */
class GetQuestionnaireTypeByOrderTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private QuestionnaireTypeRepositoryInterface $questionnaireTypeRepository,
        private GetTicketType $getTicketType,
    ) {
    }

    public function __invoke(GetQuestionnaireTypeByOrderTicketQuery $query): ?object
    {
        $orderTicket = $this->orderTicketRepository->findOrder($query->getOrderId());

        $questionnaireTypeId = $this->resolveQuestionnaireTypeId($orderTicket, $query->getTicketId());

        if ($questionnaireTypeId !== null) {
            try {
                return $this->questionnaireTypeRepository->getItem($questionnaireTypeId);
            } catch (DomainException) {
                // Тип не найден — пробуем fallback
            }
        }

        // Fallback: гостевая анкета
        try {
            return $this->questionnaireTypeRepository->getByCode('guest');
        } catch (DomainException) {
            return null;
        }
    }

    /**
     * Тип анкеты строки гостя = questionnaire_type_id её типа билета.
     */
    private function resolveQuestionnaireTypeId(?OrderTicketDto $orderTicket, Uuid $ticketId): ?Uuid
    {
        if ($orderTicket === null) {
            return null;
        }

        foreach ($orderTicket->getGuests() as $guest) {
            if (! $guest->id->equals($ticketId)) {
                continue;
            }

            if ($guest->ticketTypeId === null) {
                return null;
            }

            try {
                return $this->getTicketType
                    ->getTicketsTypeByUuid($guest->ticketTypeId)
                    ->getQuestionnaireTypeId();
            } catch (DomainException) {
                return null;
            }
        }

        return null;
    }
}
