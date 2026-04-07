<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetQuestionnaireTypeByOrderTicket;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class GetQuestionnaireTypeByOrderTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
    ) {
    }

    public function __invoke(GetQuestionnaireTypeByOrderTicketQuery $query): ?QuestionnaireTypeModel
    {
        $orderId = $query->getOrderId();

        // Получаем тип анкеты из заказа
        $orderTicket = $this->orderTicketRepository->findOrder($orderId);
        $questionnaireTypeId = $orderTicket?->getQuestionnaireTypeId()?->value();

        if ($questionnaireTypeId !== null) {
            $questionnaireType = QuestionnaireTypeModel::find($questionnaireTypeId);
            if ($questionnaireType !== null) {
                return $questionnaireType;
            }
        }

        // Fallback: гостевая анкета
        return QuestionnaireTypeModel::where('code', 'guest')
            ->where('active', true)
            ->first();
    }
}
