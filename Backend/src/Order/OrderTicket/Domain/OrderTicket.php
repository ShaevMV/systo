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

final class OrderTicket extends AggregateRoot
{
    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    /**
     * @param GuestsDto[] $ticket
     */
    public function __construct(
        protected Uuid     $festival_id,
        protected Uuid     $user_id,
        protected Uuid     $types_of_payment_id,
        protected PriceDto $price,
        protected Status   $status,
        protected array    $ticket,
        protected Uuid     $id,
        protected ?string  $promo_code = null,
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

    private static function isChildTicket(Uuid $ticketTypeId): bool
    {
        return $ticketTypeId->value() === self::CHILD_TICKET_TYPE_ID;
    }

}
