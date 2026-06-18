<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetItem;

use DomainException;
use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;

class FestivalGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository
    ) {
    }

    /**
     * Возвращает null, если фестиваль не найден. Не бросаем исключение через
     * шину (Symfony Messenger обернул бы DomainException в HandlerFailedException,
     * и контроллер не смог бы отличить «не найден» от реальной ошибки).
     */
    public function __invoke(FestivalGetItemQuery $query): ?FestivalDto
    {
        try {
            return $this->repository->get($query->getId());
        } catch (DomainException) {
            return null;
        }
    }
}
