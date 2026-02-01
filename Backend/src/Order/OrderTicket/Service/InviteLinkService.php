<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Repositories\InviteLinkRepositoryInterface;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;

class InviteLinkService
{
    private const LINK_PATCH = 'https://org.spaceofjoy.ru/invite/';

    public function __construct(
        private InviteLinkRepositoryInterface $repository,
        private QuestionnaireRepositoryInterface $questionnaireRepository,
    )
    {
    }

    public function getLink(Uuid $userId): ?string
    {
        return $this->repository->isPaidOrderByUserId($userId) ? self::LINK_PATCH . $userId->value() : null;
    }

    public function isPaidOrderByUserId(Uuid $userId, string $email): bool
    {
        return $this->repository->isPaidOrderByUserId($userId) ||
            $this->questionnaireRepository->findByEmail($email)?->getStatus() === QuestionnaireStatus::APPROVE;
    }
}
