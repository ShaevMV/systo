<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\ExternalPromocode;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Throwable;
use Tickets\PromoCode\Response\ExternalPromoCodeDto;

class ExternalPromocode
{
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    public function __construct(
        private GetLastPromoCodeQueryHandler $getLastPromoCodeQueryHandler,
        private InsertOrderIdInExternalPromocodeCommandHandler $insertOrderIdInExternalPromocodeCommandHandler,

    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetLastPromoCodeQuery::class => $this->getLastPromoCodeQueryHandler,
        ]);

        $this->commandBus = new InMemorySymfonyCommandBus([
            InsertOrderIdInExternalPromocodeCommand::class => $this->insertOrderIdInExternalPromocodeCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function getPromocodeByOrderId(Uuid $orderId): ?ExternalPromoCodeDto
    {
        $this->commandBus->dispatch(new InsertOrderIdInExternalPromocodeCommand($orderId));

        /** @var ExternalPromoCodeDto|null $result */
        $result = $this->queryBus->ask(new GetLastPromoCodeQuery($orderId));
        return $result;
    }
}
