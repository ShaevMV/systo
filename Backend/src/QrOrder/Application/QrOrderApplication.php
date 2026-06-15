<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\QrOrder\Application\GetList\QrOrderGetListQuery;
use Tickets\QrOrder\Application\GetList\QrOrderGetListQueryHandler;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;
use Tickets\QrOrder\Application\Stats\QrOrderStatsQuery;
use Tickets\QrOrder\Application\Stats\QrOrderStatsQueryHandler;
use Tickets\QrOrder\Domain\QrOrderHistoryEvent;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;
use Tickets\QrOrder\Responses\QrOrderGetListResponse;
use Tickets\QrOrder\Responses\QrOrderStatsResponse;

/**
 * Приём заказов от витрины qr (API №1) + смена статуса с выдачей билетов (API №2).
 * Тонкий слой над репозиторием (БД — только в репозитории, правило №1).
 */
final class QrOrderApplication
{
    /** Статусы контракта qr, означающие «оплачено» → запускают выдачу билетов. */
    private const PAID_STATUSES = ['оплачен', 'paid'];

    /** Чтение списка для админки идёт через QueryBus (как Location); запись — тонким слоем. */
    private readonly QueryBus $queryBus;

    public function __construct(
        private readonly QrOrderRepositoryInterface $repository,
        private readonly HistoryRepositoryInterface $history,
        QrOrderGetListQueryHandler $getListQueryHandler,
        QrOrderStatsQueryHandler $statsQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            QrOrderGetListQuery::class => $getListQueryHandler,
            QrOrderStatsQuery::class => $statsQueryHandler,
        ]);
    }

    /**
     * Принять заказ. Идемпотентно: повторный приём заказа с тем же id (== id заказа qr/org)
     * не создаёт дубль — возвращает true без повторной записи.
     */
    public function create(QrOrderDto $dto): bool
    {
        if ($this->repository->existsById($dto->getId())) {
            return true;
        }

        $created = $this->repository->create($dto);

        if ($created) {
            $this->history->save(new SaveHistoryDto(
                $dto->getId()->value(),
                new QrOrderHistoryEvent('created', [
                    'status' => $dto->getStatus(),
                    'type_order' => $dto->getTypeOrder(),
                ]),
                null,
                ActorType::QR,
            ));
        }

        return $created;
    }

    public function getItem(Uuid $id): ?QrOrderDto
    {
        return $this->repository->findById($id);
    }

    /** Список qr-заказов для админки (read-only): фильтры + пагинация + total. */
    public function getList(QrOrderGetListQuery $query): QrOrderGetListResponse
    {
        /** @var QrOrderGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    /** Сводные метрики qr-заказов для дашборда (read-only): заказы + выручка в разрезах. */
    public function getStats(QrOrderStatsQuery $query): QrOrderStatsResponse
    {
        /** @var QrOrderStatsResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    /**
     * Сменить статус принятого заказа (API №2). Возвращает false, если заказа нет.
     *
     * При переходе в «оплачен» запускается выдача билетов (PDF/письма) — один раз
     * (защита по issued_at: повторный «оплачен» не выдаёт билеты снова).
     */
    public function changeStatus(Uuid $id, string $status): bool
    {
        $order = $this->repository->findById($id);
        if ($order === null) {
            return false;
        }

        $this->repository->changeStatus($id, $status);

        $this->history->save(new SaveHistoryDto(
            $id->value(),
            new QrOrderHistoryEvent('status_changed', [
                'from' => $order->getStatus(),
                'to' => $status,
            ]),
            null,
            ActorType::QR,
        ));

        if ($this->isPaid($status) && $order->getIssuedAt() === null) {
            // Помечаем выданным ДО постановки задачи — защита от повторного запуска при
            // повторном «оплачен» (qr-ретраи). Сама выдача — асинхронно, вне HTTP-запроса qr.
            $this->repository->markIssued($id, Carbon::now());
            IssueOrderJob::dispatch($id);
        }

        return true;
    }

    private function isPaid(string $status): bool
    {
        return in_array(mb_strtolower(trim($status)), self::PAID_STATUSES, true);
    }
}
