<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetQuestionnaireTypeByOrderTicket;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;

class GetQuestionnaireTypeByOrderTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private QuestionnaireTypeRepositoryInterface $questionnaireTypeRepository,
    ) {
    }

    public function __invoke(GetQuestionnaireTypeByOrderTicketQuery $query): ?object
    {
        $orderId = $query->getOrderId();

        // Получаем questionnaire_type_id из заказа
        $orderTicket = $this->orderTicketRepository->findOrder($orderId);
        $questionnaireTypeId = $orderTicket?->getQuestionnaireTypeId();

        if ($questionnaireTypeId !== null) {
            try {
                return $this->questionnaireTypeRepository->getItem($questionnaireTypeId);
            } catch (\DomainException) {
                // Тип не найден — пробуем fallback
            }
        }

        // Fallback: гостевая анкета
        try {
            return $this->questionnaireTypeRepository->getByCode('guest');
        } catch (\DomainException) {
            return null;
        }
    }
}
