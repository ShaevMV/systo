<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use DomainException;
use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\History\Domain\Event\OrderListCreatedEvent;
use Tickets\History\Domain\Event\OrderStatusChangedEvent;
use Tickets\History\Domain\Event\OrderTicketDataChangedEvent;
use Tickets\History\Domain\Event\OrderTicketDataRemoveEvent;
use Tickets\History\Trait\HasHistory;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;
use Tickets\PromoCode\Response\ExternalPromoCodeDto;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessGuestNotificationQuestionnaire;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramByQuestionnaireSend;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelLiveTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessPushLiveTicket;

/**
 * OrderTicket — агрегат заказа в формате v2.6.0 (BREAKING change).
 *
 * Заказ — это контейнер строк {@see OrderGuestLine}. Каждая строка несёт свой
 * `ticket_type_id`, `promo_code`, опции и снимок цены. Цена заказа — функция строк
 * ({@see self::totalPrice()}), а не отдельное поле (см. `.claude/specs/order-format-architecture.md` §1).
 *
 * На уровне заказа остаются: `festival_id`, `user_id`, `types_of_payment_id`, `status`,
 * `location_id`/`curator_id` (для заказов-списков).
 *
 * Проверка «детского билета» теперь **per-guest** (`OrderGuestLine::isChild()`) — в одном
 * заказе могут смешиваться типы (оргвзнос + детский + парковка), и анкета каждого гостя
 * определяется его строкой.
 *
 * Источник: Чистая архитектура (Р. Мартин) — агрегат самодостаточен, цену собирает сам
 * (Tell, don't ask); Domain не знает про репозитории — готовые `MoneySnapshot` приходят
 * из Application-сервиса `OrderPriceCalculator`.
 */
final class OrderTicket extends AggregateRoot
{
    use HasHistory;

    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    /**
     * @param OrderGuestLine[] $guests
     */
    public function __construct(
        protected Uuid   $festival_id,
        protected Uuid   $user_id,
        protected ?Uuid  $types_of_payment_id,
        protected Status $status,
        protected array  $guests,
        protected Uuid   $id,
        protected ?Uuid  $location_id = null,
        protected ?Uuid  $curator_id = null,
    )
    {
    }

    private static function fromOrderTicketDto(OrderTicketDto $orderTicketDto): self
    {
        return new self(
            $orderTicketDto->getFestivalId(),
            $orderTicketDto->getUserId(),
            $orderTicketDto->getTypesOfPaymentId(),
            $orderTicketDto->getStatus(),
            $orderTicketDto->getGuests(),
            $orderTicketDto->getId(),
            $orderTicketDto->getLocationId(),
            $orderTicketDto->getCuratorId(),
        );
    }

    public static function create(
        OrderTicketDto $orderTicketDto,
        int            $kilter
    ): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->record(new ProcessUserNotificationNewOrderTicket(
                $orderTicketDto->getEmail(),
                $kilter,
                $orderTicketDto->firstTicketTypeId(),
                $result->festival_id,
            )
        );

        $result->recordHistory(new OrderCreatedEvent(
            ticketType: $orderTicketDto->firstTicketTypeId()?->value() ?? '',
            price: $result->totalPrice()->asFloat(),
            kilter: $kilter,
        ));

        return $result;
    }

    public static function toPaidInLiveTicket(OrderTicketDto $orderTicketDto, int $kilter): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->guests(),
        ));

        $result->record(new ProcessUserNotificationOrderPaidLiveTicket(
                $orderTicketDto->getEmail(),
                $orderTicketDto->firstTicketTypeId(),
                $orderTicketDto->getTypesOfPaymentId(),
                $kilter,
            )
        );

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getGuests() as $guest) {
            if ($guest->isChild()) {
                continue;
            }
            $result->record(new ProcessGuestNotificationQuestionnaire(
                    $guest->email ?? $orderTicketDto->getEmail(),
                    $orderId->value(),
                    $guest->id->value(),
                )
            );
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::PAID_FOR_LIVE,
        ));

        return $result;
    }

    public static function toProcessGuestNotificationQuestionnaire(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getGuests() as $guest) {
            if ($guest->isChild()) {
                continue;
            }
            $result->record(new ProcessGuestNotificationQuestionnaire(
                    $guest->email ?? $orderTicketDto->getEmail(),
                    $orderId->value(),
                    $guest->id->value(),
                )
            );
        }

        return $result;
    }


    public static function toPaid(
        OrderTicketDto        $orderTicketDto,
        ?string               $comment = null,
        ?ExternalPromoCodeDto $externalPromoCodeDto = null
    ): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->guests(),
        ));

        $result->record(new ProcessUserNotificationOrderPaid(
                $orderTicketDto->getEmail(),
                $result->guests(),
                $orderTicketDto->firstTicketTypeId(),
                $comment,
                $externalPromoCodeDto?->getPromocode(),
            )
        );

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getGuests() as $guest) {
            if ($guest->isChild()) {
                continue;
            }
            $email = $guest->email ?? $orderTicketDto->getEmail();
            $result->record(new ProcessGuestNotificationQuestionnaire(
                    $email,
                    $orderId->value(),
                    $guest->id->value(),
                )
            );
            $result->record(new ProcessTelegramByQuestionnaireSend($email));
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::PAID,
        ));

        return $result;
    }

    /**
     * Оплата Friendly-заказа (созданного пушером).
     *
     * Отличается от toPaid() тем что:
     * - Использует ProcessUserNotificationOrderPaidFriendly (без ссылки на /myOrders)
     * - У гостей friendly-заказов нет личного кабинета
     *
     * Источник: Роберт Мартин — «Чистая архитектура», глава «Зависимости» (Dependency Rule)
     * Domain Events остаются чистыми — каждый описывает один конкретный случай.
     */
    public static function toPaidFriendly(
        OrderTicketDto        $orderTicketDto,
        ?string               $comment = null,
        ?ExternalPromoCodeDto $externalPromoCodeDto = null
    ): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->guests(),
        ));

        $result->record(new ProcessUserNotificationOrderPaidFriendly(
                $orderTicketDto->getEmail(),
                $result->guests(),
                $orderTicketDto->firstTicketTypeId(),
                $comment,
                $externalPromoCodeDto?->getPromocode(),
            )
        );

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getGuests() as $guest) {
            if ($guest->isChild()) {
                continue;
            }
            $email = $guest->email ?? $orderTicketDto->getEmail();
            $result->record(new ProcessGuestNotificationQuestionnaire(
                    $email,
                    $orderId->value(),
                    $guest->id->value(),
                )
            );
            $result->record(new ProcessTelegramByQuestionnaireSend($email));
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::PAID,
        ));

        return $result;
    }

    public static function toCancel(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();
        $result->record(new ProcessCancelTicket(
            $result->id,
        ));

        $result->record(new ProcessUserNotificationOrderCancel(
                $orderTicketDto->getEmail(),
                $orderTicketDto->firstTicketTypeId(),
            )
        );

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::CANCEL,
        ));

        return $result;
    }

    public static function toCancelLive(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();
        $result->record(new ProcessCancelTicket(
            $result->id,
        ));
        $result->record(new ProcessCancelLiveTicket(
            $result->id,
            $orderTicketDto->getGuests()
        ));

        $result->updateIdTicket();

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::CANCEL_FOR_LIVE,
        ));

        return $result;
    }

    public static function toLiveIssued(
        OrderTicketDto $orderTicketDto,
        array          $liveNumber = [],
    ): self
    {
        if (count($liveNumber) === 0) {
            throw new DomainException('Забыли ввести номера билетов');
        }

        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->guests(),
        ));

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getGuests() as $guest) {
            if ($guest->isChild()) {
                continue;
            }
            $result->record(new ProcessGuestNotificationQuestionnaire(
                    $guest->email ?? $orderTicketDto->getEmail(),
                    $orderId->value(),
                    $guest->id->value(),
                )
            );
        }

        foreach ($liveNumber as $key => $item) {
            $result->record(new ProcessPushLiveTicket(
                (int)$item,
                new Uuid($key),
            ));
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::LIVE_TICKET_ISSUED,
        ));

        return $result;
    }

    /**
     * Удаление одного гостя (билета) из заказа.
     */
    public static function toRemoveTicket(
        OrderTicketDto $orderTicketDto,
        Uuid           $ticketId
    ): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $changes = [];

        foreach ($result->guests as $guest) {
            if ($ticketId->equals($guest->id)) {
                $changes = [
                    'oldName' => $guest->value,
                    'newUuid' => null,
                ];
                $result->removeGuest($ticketId);
            }
        }

        $result->record(new ProcessCancelTicket($result->id, [$ticketId]));

        if (!empty($changes)) {
            $result->recordHistory(new OrderTicketDataRemoveEvent($changes));
        }

        return $result;
    }

    /**
     * Убрать гостя из заказа по id строки (VO иммутабелен — пересобираем массив).
     */
    private function removeGuest(Uuid $ticketId): void
    {
        $this->guests = array_values(array_filter(
            $this->guests,
            static fn (OrderGuestLine $guest): bool => !$guest->id->equals($ticketId),
        ));
    }

    /**
     * Смена данных (ФИО/email) одного или нескольких гостей в заказе.
     *
     * Алгоритм аналогичен toDifficultiesArose:
     * 1. Применяем изменения к нужным гостям (новый VO через withValue/withEmail)
     * 2. Регенерируем UUID у изменённых строк (старые билеты будут удалены, новые созданы)
     * 3. Отменяем старые билеты (по старым id)
     * 4. Создаём новые билеты с обновлёнными данными
     * 5. Отправляем письмо и ссылки на анкеты изменённым (не-детским) гостям
     *
     * @param array $valueMap [ticketId => newValue] — ФИО для изменения
     * @param array $emailMap [ticketId => newEmail] — email для изменения
     */
    public static function toChangeTicket(
        OrderTicketDto $orderTicketDto,
        array          $valueMap,
        array          $emailMap,
    ): self
    {
        if (empty($valueMap) && empty($emailMap)) {
            throw new DomainException('Не переданы данные для изменения билета');
        }

        $result = self::fromOrderTicketDto($orderTicketDto);

        $changes = [];
        $changedUuid = [];      // старые id строк — для отмены билетов
        $changedTickets = [];   // новые строки — для создания билетов
        $rebuilt = [];

        foreach ($result->guests as $guest) {
            $ticketId = $guest->id->value();

            if (isset($valueMap[$ticketId]) || isset($emailMap[$ticketId])) {
                $changes[] = [
                    'oldName' => $guest->value,
                    'newName' => $valueMap[$ticketId] ?? $guest->value,
                ];
                $changedUuid[] = $guest->id;

                $updated = $guest;
                if (isset($valueMap[$ticketId])) {
                    $updated = $updated->withValue($valueMap[$ticketId]);
                }
                if (isset($emailMap[$ticketId])) {
                    $updated = $updated->withEmail($emailMap[$ticketId]);
                }
                $updated = $updated->withRegeneratedId();

                $changedTickets[] = $updated;
                $rebuilt[] = $updated;
            } else {
                $rebuilt[] = $guest;
            }
        }

        $result->guests = $rebuilt;

        $result->record(new ProcessCancelTicket($result->id, $changedUuid));

        $result->record(new ProcessCreateTicket(
            $result->id,
            $changedTickets,
        ));

        if (!empty($changes)) {
            $result->record(new ProcessUserNotificationOrderTicketChanged(
                $orderTicketDto->getEmail(),
                $changes,
                $orderTicketDto->firstTicketTypeId(),
                $orderTicketDto->getFestivalId(),
            ));
        }

        $orderId = $orderTicketDto->getId();
        foreach ($changedTickets as $changedGuest) {
            if ($changedGuest->isChild()) {
                continue;
            }
            $result->record(new ProcessGuestNotificationQuestionnaire(
                $changedGuest->email ?? $orderTicketDto->getEmail(),
                $orderId->value(),
                $changedGuest->id->value(),
            ));
        }

        if (!empty($changes)) {
            $result->recordHistory(new OrderTicketDataChangedEvent($changes));
        }

        return $result;
    }

    /**
     * Регенерация id всех строк (VO иммутабелен — пересобираем массив новыми объектами).
     */
    private function updateIdTicket(): void
    {
        $this->guests = array_map(
            static fn (OrderGuestLine $guest): OrderGuestLine => $guest->withRegeneratedId(),
            $this->guests,
        );
    }

    public static function toDifficultiesArose(OrderTicketDto $orderTicketDto, ?string $comment): self
    {
        if (is_null($comment)) {
            throw new DomainException('Комментарий обязательный для смены статус "Возникли трудности"');
        }

        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();
        $result->record(new ProcessCancelTicket(
            $result->id,
        ));

        $result->record(new ProcessUserNotificationOrderDifficultiesArose(
                $orderTicketDto->getId(),
                $orderTicketDto->getEmail(),
                $comment,
                $orderTicketDto->firstTicketTypeId(),
            )
        );

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::DIFFICULTIES_AROSE,
            comment: $comment,
        ));

        return $result;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return OrderGuestLine[]
     */
    public function guests(): array
    {
        return $this->guests;
    }

    /**
     * Итог по заказу — сумма {@see OrderGuestLine::total()} по всем строкам (Tell, don't ask).
     */
    public function totalPrice(): Money
    {
        return array_reduce(
            $this->guests,
            static fn (Money $acc, OrderGuestLine $guest): Money => $acc->add($guest->total()),
            Money::zero(),
        );
    }

    /**
     * Суммарная скидка по заказу.
     */
    public function discountTotal(): Money
    {
        return array_reduce(
            $this->guests,
            static fn (Money $acc, OrderGuestLine $guest): Money => $acc->add($guest->price->discount),
            Money::zero(),
        );
    }

    /**
     * Live-заказ определяется по строкам (по инварианту все строки одного типа —
     * live + non-live в одном заказе запрещены, см. OrderPriceCalculator).
     */
    public function isLive(): bool
    {
        return isset($this->guests[0]) && $this->guests[0]->isLive();
    }

    /**
     * Уникальные `ticket_type_id` по строкам заказа — для совместимости со старыми
     * событиями/фильтрами, которым нужен перечень типов.
     *
     * @return Uuid[]
     */
    public function uniqueTicketTypeIds(): array
    {
        $seen = [];
        $result = [];
        foreach ($this->guests as $guest) {
            if ($guest->ticketTypeId === null) {
                continue;
            }
            $key = $guest->ticketTypeId->value();
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $guest->ticketTypeId;
            }
        }

        return $result;
    }

    /**
     * Создать заказ-список (статус NEW_LIST). Куратор писем не получает,
     * пользователь-получатель тоже не получает на этом этапе — письмо придёт при APPROVE_LIST.
     */
    public static function createList(
        OrderTicketDto $orderTicketDto,
        int            $kilter,
        ?string        $locationName = null,
    ): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->recordHistory(new OrderListCreatedEvent(
            locationId: $orderTicketDto->getLocationId()?->value() ?? '',
            locationName: $locationName,
            project: $orderTicketDto->getProject(),
            kilter: $kilter,
        ));

        return $result;
    }

    /**
     * Перевод заказа-списка в статус APPROVE_LIST.
     * Создаёт билеты, рассылает гостям ссылки на анкеты, отправляет получателю PDF-билеты.
     */
    public static function toApproveList(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->guests(),
        ));

        $result->record(new ProcessUserNotificationListApproved(
            $orderTicketDto->getEmail(),
            $result->guests(),
            $orderTicketDto->getFestivalId(),
            $orderTicketDto->getLocationId(),
        ));

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getGuests() as $guest) {
            $result->record(new ProcessGuestNotificationQuestionnaire(
                $guest->email ?? $orderTicketDto->getEmail(),
                $orderId->value(),
                $guest->id->value(),
            ));
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::APPROVE_LIST,
        ));

        return $result;
    }

    /**
     * Перевод заказа-списка в статус CANCEL_LIST.
     * Отменяет ранее созданные билеты (если были на approve_list) и шлёт письмо получателю.
     */
    public static function toCancelList(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();

        $result->record(new ProcessCancelTicket($result->id));

        $result->record(new ProcessUserNotificationListCancel(
            $orderTicketDto->getEmail(),
            $orderTicketDto->getFestivalId(),
        ));

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::CANCEL_LIST,
        ));

        return $result;
    }

    /**
     * Перевод заказа-списка в статус DIFFICULTIES_AROSE_LIST.
     * Отменяет ранее созданные билеты и шлёт письмо получателю с комментарием.
     */
    public static function toDifficultiesAroseList(OrderTicketDto $orderTicketDto, ?string $comment): self
    {
        if (is_null($comment)) {
            throw new DomainException('Комментарий обязательный для смены статуса "Возникли трудности (список)"');
        }

        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();

        $result->record(new ProcessCancelTicket($result->id));

        $result->record(new ProcessUserNotificationListDifficultiesArose(
            $orderTicketDto->getEmail(),
            $comment,
            $orderTicketDto->getFestivalId(),
        ));

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus: Status::DIFFICULTIES_AROSE_LIST,
            comment: $comment,
        ));

        return $result;
    }

}
