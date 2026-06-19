<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\BazaDelivery\Application\GetList\BazaDeliveryGetListQuery;
use Tickets\BazaDelivery\Application\GetList\BazaDeliveryGetListQueryHandler;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Domain\BazaDeliveryLifecycleEvent;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\BazaDelivery\Responses\BazaDeliveryGetListResponse;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Тонкий слой админ-чтения и управления доставкой билетов в Baza. Чтение списка — через QueryBus
 * (как Location/QrOrder/EmailDelivery); БД — только в репозитории. Постановку в очередь выполняет
 * BazaDeliveryDispatcher/DeliverTicketToBazaJob.
 */
final class BazaDeliveryApplication
{
    private readonly QueryBus $queryBus;

    public function __construct(
        private readonly BazaDeliveryRepositoryInterface $repository,
        private readonly HistoryRepositoryInterface $history,
        BazaDeliveryGetListQueryHandler $getListQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            BazaDeliveryGetListQuery::class => $getListQueryHandler,
        ]);
    }

    public function getList(BazaDeliveryGetListQuery $query): BazaDeliveryGetListResponse
    {
        return $this->queryBus->ask($query);
    }

    public function getItem(Uuid $id): ?BazaDeliveryDto
    {
        return $this->repository->findById($id);
    }

    /** Доставки билетов заказа (для экрана qr — «весь путь» заказа). */
    public function getByOrderId(Uuid $orderId): Collection
    {
        return $this->repository->getByOrderId($orderId);
    }

    /** Число застрявших доставок (failed) — для дашборд-виджета «застрявшие билеты». */
    public function countStuck(?Uuid $festivalId): int
    {
        return $this->repository->countStuck($festivalId);
    }

    /**
     * Повторная доставка из админки: возвращает запись в очередь и ставит DeliverTicketToBazaJob.
     * Счётчик попыток НЕ сбрасывается (§6.4) — если кап 10 уже достигнут, job сразу фиксирует
     * терминальный failed без новой записи в Baza. false → запись не найдена.
     */
    public function resend(Uuid $id, ?string $actorId): bool
    {
        $delivery = $this->repository->findById($id);
        if ($delivery === null) {
            return false;
        }

        $this->repository->requeue($id);
        $this->history->save(new SaveHistoryDto(
            $id->value(),
            new BazaDeliveryLifecycleEvent(BazaDeliveryStatus::QUEUED, ['action' => 'resend', 'target' => $delivery->getTarget()]),
            $actorId,
            ActorType::USER,
        ));

        DeliverTicketToBazaJob::dispatch($id->value());

        return true;
    }
}
