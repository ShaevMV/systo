<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetByOrderTicket;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class QuestionnaireGetByOrderTicketQueryHandler implements QueryHandler
{
    public function __construct(
        private QuestionnaireRepositoryInterface $questionnaireRepository,
        private OrderTicketRepositoryInterface $orderTicketRepository,
    ) {
    }

    public function __invoke(QuestionnaireGetByOrderTicketQuery $query): ?QuestionnaireTicketDto
    {
        $orderId = $query->getOrderId();
        $ticketId = $query->getTicketId();

        // 1. Ищем анкету по orderId + ticketId
        $questionnaire = $this->questionnaireRepository->findByOrderIdAndTicketId($orderId, $ticketId);
        if ($questionnaire !== null) {
            return $questionnaire;
        }

        // 2. Если не найдена — ищем new_user анкету по email из заказа
        $orderTicket = $this->orderTicketRepository->findOrder($orderId);
        if ($orderTicket === null) {
            return null;
        }

        $email = $orderTicket->getEmail();
        if (empty($email)) {
            return null;
        }

        $newUserQuestionnaire = $this->findNewUserByEmail($email);
        if ($newUserQuestionnaire !== null) {
            return $newUserQuestionnaire;
        }

        return null;
    }

    /**
     * Найти анкету типа new_user по email
     */
    private function findNewUserByEmail(string $email): ?QuestionnaireTicketDto
    {
        $newUserType = QuestionnaireTypeModel::where('code', 'new_user')
            ->where('active', true)
            ->first();

        if ($newUserType === null) {
            return null;
        }

        return $this->questionnaireRepository->findByEmailAndQuestionnaireType(
            $email,
            new Uuid($newUserType->id)
        );
    }
}
