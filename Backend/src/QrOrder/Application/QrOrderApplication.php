<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application;

use App\Mail\OrderToCreate;
use Carbon\Carbon;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\QrOrder\Application\GetList\QrOrderGetListQuery;
use Tickets\QrOrder\Application\GetList\QrOrderGetListQueryHandler;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;
use Tickets\QrOrder\Application\Stats\QrOrderStatsQuery;
use Tickets\QrOrder\Application\Stats\QrOrderStatsQueryHandler;
use Tickets\QrOrder\Domain\QrOrderHistoryEvent;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;
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

    /** Статусы контракта qr, означающие «создан/новый» → шлём письмо «заказ создан». */
    private const CREATED_STATUSES = ['создан', 'new', 'created'];

    /** Чтение списка для админки идёт через QueryBus (как Location); запись — тонким слоем. */
    private readonly QueryBus $queryBus;

    public function __construct(
        private readonly QrOrderRepositoryInterface $repository,
        private readonly HistoryRepositoryInterface $history,
        private readonly MailDispatcher $mailDispatcher,
        QrOrderGetListQueryHandler $getListQueryHandler,
        QrOrderStatsQueryHandler $statsQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            QrOrderGetListQuery::class => $getListQueryHandler,
            QrOrderStatsQuery::class => $statsQueryHandler,
        ]);
    }

    /**
     * Принять заказ от витрины qr. Заказ может прийти в двух режимах:
     *  - «создан» (двухшаговый жизненный цикл) → шлём письмо «заказ создан» (orderToCreate),
     *    билеты НЕ выпускаем; выпуск произойдёт позже при changeStatus → «оплачен»;
     *  - «оплачен» (одношаговый приём) → сразу выпускаем билеты (PDF/письма/Telegram).
     *
     * Идемпотентно: повторный приём заказа с тем же id (== id заказа qr/org) не создаёт дубль,
     * не шлёт письмо «создан» повторно и не выпускает билеты повторно — возвращает true.
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

            if ($this->isPaid($dto->getStatus())) {
                // Заказ пришёл уже оплаченным → сразу запускаем выдачу билетов.
                // Помечаем issued_at ДО постановки задачи (защита от повторной выдачи при
                // qr-ретраях) — повторный приём отсечёт existsById выше. Выдача — асинхронно,
                // вне HTTP-запроса qr; событие issued в историю пишет сам IssueOrderJob.
                $this->repository->markIssued($dto->getId(), Carbon::now());
                IssueOrderJob::dispatch($dto->getId());
            } elseif ($this->isCreated($dto->getStatus()) && $this->isRegular($dto->getTypeOrder())) {
                // Заказ принят в статусе «создан» → письмо «заказ создан» (как в классическом
                // org-флоу). Билеты появятся позже при переводе в «оплачен» (changeStatus).
                // Только обычный заказ (regular): списки при создании писем не шлют, живые
                // билеты стартуют сразу оплаченными — для них письма «создан» нет (как в org).
                $this->sendCreatedEmail($dto);
            }
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

    private function isCreated(string $status): bool
    {
        return in_array(mb_strtolower(trim($status)), self::CREATED_STATUSES, true);
    }

    /** Обычный заказ (regular) — включая пустой/неизвестный type_order (fallback на regular). */
    private function isRegular(?string $typeOrder): bool
    {
        $type = TypeOrder::normalize($typeOrder);

        return $type === '' || $type === TypeOrder::REGULAR;
    }

    /**
     * Письмо «заказ создан» получателю qr-заказа (orderToCreate). Данные берём из payload
     * (БД не трогаем): имя фестиваля — order_data.festival.title, номер заказа — external_order_no
     * (как у qr-витрины), число позиций — длина guests[]. Отправка трекается (source = qr_pipeline).
     */
    private function sendCreatedEmail(QrOrderDto $dto): void
    {
        $payload = $dto->getPayload();

        $festivalName = (string) (data_get($payload, 'order_data.festival.title') ?? '');
        $guestsCount = is_array($payload['guests'] ?? null) ? count($payload['guests']) : 0;
        $orderNo = (int) ($dto->getExternalOrderNo() ?? 0);
        $kilter = $orderNo > 0 ? $orderNo : max(1, $guestsCount);

        $this->mailDispatcher->send(
            EmailEvent::ORDER_CREATED,
            new EmailContext(
                recipient: $dto->getEmail(),
                festivalId: $dto->getFestivalId()?->value(),
                orderType: $dto->getTypeOrder(),
                source: 'qr_pipeline',
                actorType: ActorType::QR,
                aggregateType: 'qr_order',
                aggregateId: $dto->getId()->value(),
            ),
            new OrderToCreate($kilter, $festivalName),
        );
    }
}
