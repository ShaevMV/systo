<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use DomainException;
use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessGuestNotificationQuestionnaire;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramByQuestionnaireSend;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\PromoCode\Response\ExternalPromoCodeDto;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelLiveTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessPushLiveTicket;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderTicketChanged;
use Tickets\History\Trait\HasHistory;
use Tickets\History\Domain\Event\OrderStatusChangedEvent;
use Tickets\History\Domain\Event\OrderTicketDataChangedEvent;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\History\Domain\Event\OrderListCreatedEvent;

final class OrderTicket extends AggregateRoot
{
    use HasHistory;
    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    /**
     * @param GuestsDto[] $ticket
     */
    public function __construct(
        protected Uuid     $festival_id,
        protected Uuid     $user_id,
        protected ?Uuid    $types_of_payment_id,
        protected PriceDto $price,
        protected Status   $status,
        protected array    $ticket,
        protected Uuid     $id,
        protected ?string  $promo_code = null,
        protected ?Uuid    $location_id = null,
        protected ?Uuid    $curator_id = null,
    )
    {
    }

    private static function fromOrderTicketDto(OrderTicketDto $orderTicketDto): self
    {
        return new self(
            $orderTicketDto->getFestivalId(),
            $orderTicketDto->getUserId(),
            $orderTicketDto->getTypesOfPaymentId(),
            $orderTicketDto->getPriceDto(),
            $orderTicketDto->getStatus(),
            $orderTicketDto->getTicket(),
            $orderTicketDto->getId(),
            $orderTicketDto->getPromoCode(),
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
                $orderTicketDto->getTicketTypeId(),
                $result->festival_id,
            )
        );

        $result->recordHistory(new OrderCreatedEvent(
            ticketType: $orderTicketDto->getTicketTypeId()->value(),
            price:      $orderTicketDto->getPriceDto()->getTotalPrice(),
            kilter:     $kilter,
        ));

        return $result;
    }

    public static function toPaidInLiveTicket(OrderTicketDto $orderTicketDto, int $kilter): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->getTicket(),
        ));


        $result->record(new ProcessUserNotificationOrderPaidLiveTicket(
                $orderTicketDto->getEmail(),
                $orderTicketDto->getTicketTypeId(),
                $orderTicketDto->getTypesOfPaymentId(),
                $kilter,
            )
        );

        if (!self::isChildTicket($orderTicketDto->getTicketTypeId())) {
            foreach ($orderTicketDto->getTicket() as $item) {
                $result->record(new ProcessGuestNotificationQuestionnaire(
                        $item->getEmail(),
                        $orderTicketDto->getId()->value(),
                        $item->getId()->value(),
                    )
                );
            }
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::PAID_FOR_LIVE,
        ));

        return $result;
    }

    public static function toProcessGuestNotificationQuestionnaire(OrderTicketDto $orderTicketDto): self
    {
        if (self::isChildTicket($orderTicketDto->getTicketTypeId())) {
            return self::fromOrderTicketDto($orderTicketDto);
        }

        $result = self::fromOrderTicketDto($orderTicketDto);

        foreach ($orderTicketDto->getTicket() as $item) {
            $result->record(new ProcessGuestNotificationQuestionnaire(
                    $item->getEmail(),
                    $orderTicketDto->getId()->value(),
                    $item->getId()->value(),
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
            $result->getTicket(),
        ));

        $result->record(new ProcessUserNotificationOrderPaid(
                $orderTicketDto->getEmail(),
                $result->getTicket(),
                $orderTicketDto->getTicketTypeId(),
                $comment,
                $externalPromoCodeDto?->getPromocode(),
            )
        );

        if (!self::isChildTicket($orderTicketDto->getTicketTypeId())) {
            $orderId = $orderTicketDto->getId();

            foreach ($orderTicketDto->getTicket() as $item) {
                $result->record(new ProcessGuestNotificationQuestionnaire(
                        $item->getEmail() ?? $orderTicketDto->getEmail(),
                        $orderId->value(),
                        $item->getId()->value(),
                    )
                );
                $result->record(new ProcessTelegramByQuestionnaireSend(
                        $item->getEmail() ?? $orderTicketDto->getEmail()
                    )
                );
            }
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::PAID,
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
            $result->getTicket(),
        ));

        $result->record(new ProcessUserNotificationOrderPaidFriendly(
                $orderTicketDto->getEmail(),
                $result->getTicket(),
                $orderTicketDto->getTicketTypeId(),
                $comment,
                $externalPromoCodeDto?->getPromocode(),
            )
        );

        if (!self::isChildTicket($orderTicketDto->getTicketTypeId())) {
            $orderId = $orderTicketDto->getId();

            foreach ($orderTicketDto->getTicket() as $item) {
                $result->record(new ProcessGuestNotificationQuestionnaire(
                        $item->getEmail() ?? $orderTicketDto->getEmail(),
                        $orderId->value(),
                        $item->getId()->value(),
                    )
                );
                $result->record(new ProcessTelegramByQuestionnaireSend(
                        $item->getEmail() ?? $orderTicketDto->getEmail()
                    )
                );
            }
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::PAID,
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
                $orderTicketDto->getTicketTypeId(),
            )
        );

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::CANCEL,
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
            $orderTicketDto->getTicket()
        ));

        $result->updateIdTicket();

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::CANCEL_FOR_LIVE,
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
            $result->getTicket(),
        ));

        if (!self::isChildTicket($orderTicketDto->getTicketTypeId())) {
            foreach ($orderTicketDto->getTicket() as $item) {
                $result->record(new ProcessGuestNotificationQuestionnaire(
                        $item->getEmail(),
                        $orderTicketDto->getId()->value(),
                        $item->getId()->value(),
                    )
                );
            }
        }

        foreach ($liveNumber as $key => $item) {
            $result->record(new ProcessPushLiveTicket(
                (int)$item,
                new Uuid($key),
            ));
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::LIVE_TICKET_ISSUED,
        ));

        return $result;
    }

    /**
     * Смена данных (ФИО/email) одного или нескольких гостей в заказе.
     *
     * Алгоритм аналогичен toDifficultiesArose:
     * 1. Применяем изменения к нужным гостям
     * 2. Обновляем UUID у всех гостей (старые билеты будут удалены, новые созданы)
     * 3. Отменяем все старые билеты
     * 4. Создаём новые билеты с обновлёнными данными
     * 5. Отправляем письмо и ссылки на анкеты изменённым гостям
     *
     * @param array $valueMap [ticketId => newValue] — ФИО для изменения
     * @param array $emailMap [ticketId => newEmail] — email для изменения
     */
    public static function toChangeTicket(
        OrderTicketDto $orderTicketDto,
        array          $valueMap,
        array          $emailMap,
    ): self {
        if (empty($valueMap) && empty($emailMap)) {
            throw new DomainException('Не переданы данные для изменения билета');
        }

        $result = self::fromOrderTicketDto($orderTicketDto);

        $changes = [];
        $changedIndexes = [];
        $changedUuid = [];
        $changedTickets = [];
        foreach ($result->ticket as $index => &$guest) {
            $ticketId = $guest->getId()->value();

            if (isset($valueMap[$ticketId]) || isset($emailMap[$ticketId])) {
                $changes[] = [
                    'oldName' => $guest->getValue(),
                    'newName' => $valueMap[$ticketId] ?? $guest->getValue(),
                ];
                $changedUuid[] = new Uuid($ticketId);
                $changedIndexes[] = $index;

                if (isset($valueMap[$ticketId])) {
                    $guest->updateValue($valueMap[$ticketId]);
                }
                if (isset($emailMap[$ticketId])) {
                    $guest->updateEmail($emailMap[$ticketId]);
                }
                $guest->updateId();
                $changedTickets[] = $guest;
            }
        }

        $result->record(new ProcessCancelTicket($result->id, $changedUuid));

        $result->record(new ProcessCreateTicket(
            $result->id,
            $changedTickets,
        ));

        if (!empty($changes)) {
            $result->record(new ProcessUserNotificationOrderTicketChanged(
                $orderTicketDto->getEmail(),
                $changes,
                $orderTicketDto->getTicketTypeId(),
                $orderTicketDto->getFestivalId(),
            ));
        }

        if (!self::isChildTicket($orderTicketDto->getTicketTypeId())) {
            foreach ($changedIndexes as $index) {
                $guest = $result->ticket[$index];
                $result->record(new ProcessGuestNotificationQuestionnaire(
                    $guest->getEmail() ?? $orderTicketDto->getEmail(),
                    $orderTicketDto->getId()->value(),
                    $guest->getId()->value(),
                ));
            }
        }

        if (!empty($changes)) {
            $result->recordHistory(new OrderTicketDataChangedEvent($changes));
        }

        return $result;
    }

    private function updateIdTicket(): void
    {
        foreach ($this->ticket as &$guestsDto) {
            $guestsDto->updateId();
        }
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
                $orderTicketDto->getTicketTypeId(),
            )
        );

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$orderTicketDto->getStatus(),
            toStatus:   Status::DIFFICULTIES_AROSE,
            comment:    $comment,
        ));

        return $result;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return GuestsDto[]
     */
    public function getTicket(): array
    {
        return $this->ticket;
    }

    private static function isChildTicket(?Uuid $ticketTypeId): bool
    {
        return $ticketTypeId !== null
            && $ticketTypeId->value() === self::CHILD_TICKET_TYPE_ID;
    }

    /**
     * Создать заказ-список (статус NEW_LIST). Куратор писем не получает,
     * пользователь-получатель тоже не получает на этом этапе — письмо придёт при APPROVE_LIST.
     */
    public static function createList(
        OrderTicketDto $orderTicketDto,
        int            $kilter,
        ?string        $locationName = null,
    ): self {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->recordHistory(new OrderListCreatedEvent(
            locationId:   $orderTicketDto->getLocationId()?->value() ?? '',
            locationName: $locationName,
            project:      $orderTicketDto->getProject(),
            kilter:       $kilter,
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
            $result->getTicket(),
        ));

        $result->record(new ProcessUserNotificationListApproved(
            $orderTicketDto->getEmail(),
            $result->getTicket(),
            $orderTicketDto->getFestivalId(),
            $orderTicketDto->getLocationId(),
        ));

        $orderId = $orderTicketDto->getId();
        foreach ($orderTicketDto->getTicket() as $item) {
            $result->record(new ProcessGuestNotificationQuestionnaire(
                $item->getEmail() ?? $orderTicketDto->getEmail(),
                $orderId->value(),
                $item->getId()->value(),
            ));
        }

        $result->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string) $orderTicketDto->getStatus(),
            toStatus:   Status::APPROVE_LIST,
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
            fromStatus: (string) $orderTicketDto->getStatus(),
            toStatus:   Status::CANCEL_LIST,
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
            fromStatus: (string) $orderTicketDto->getStatus(),
            toStatus:   Status::DIFFICULTIES_AROSE_LIST,
            comment:    $comment,
        ));

        return $result;
    }
}
