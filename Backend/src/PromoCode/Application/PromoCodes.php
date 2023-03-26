<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application;

use Throwable;
use Tickets\PromoCode\Application\CreatePromoCode\CreateOrUpdatePromoCodeCommand;
use Tickets\PromoCode\Application\CreatePromoCode\CreateOrUpdatePromoCodeCommandHandler;
use Tickets\PromoCode\Application\GetPromoCodes\GetPromoCodeItemQuery;
use Tickets\PromoCode\Application\GetPromoCodes\GetPromoCodeItemQueryHandler;
use Tickets\PromoCode\Application\GetPromoCodes\GetPromoCodeListQuery;
use Tickets\PromoCode\Application\GetPromoCodes\GetPromoCodeListQueryHandler;
use Tickets\PromoCode\Response\PromoCodeDto;
use Tickets\PromoCode\Response\PromoCodeListDto;
use Tickets\Shared\Domain\Bus\Command\CommandBus;
use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class PromoCodes
{
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    public function __construct(
        private GetPromoCodeListQueryHandler $getPromoCodeListQueryHandler,
        private GetPromoCodeItemQueryHandler $getPromoCodeItemQueryHandler,
        private CreateOrUpdatePromoCodeCommandHandler $createOrUpdatePromoCodeCommandHandler,
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPromoCodeListQuery::class => $this->getPromoCodeListQueryHandler,
            GetPromoCodeItemQuery::class => $this->getPromoCodeItemQueryHandler,
        ]);

        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateOrUpdatePromoCodeCommand::class => $this->createOrUpdatePromoCodeCommandHandler
        ]);
    }

    public function getList(): PromoCodeListDto
    {
        /** @var PromoCodeListDto $promoCodeListDto */
        $promoCodeListDto = $this->queryBus->ask(new GetPromoCodeListQuery());

        return $promoCodeListDto;
    }

    public function getItem(Uuid $id): ?PromoCodeDto
    {
        /** @var PromoCodeDto|null $promoCodeDto */
        $promoCodeDto = $this->queryBus->ask(new GetPromoCodeItemQuery($id));

        return $promoCodeDto;
    }

    /**
     * @throws Throwable
     */
    public function createOrUpdatePromoCode(array $request): Uuid
    {
        $command = CreateOrUpdatePromoCodeCommand::fromState($request);
        $this->commandBus->dispatch($command);

        return $command->getId();
    }
}
