<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Domain;

use DomainException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\History\Domain\Event\OrderTicketDataChangedEvent;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationNewOrderTicket;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderCancel;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderDifficultiesArose;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaid;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderTicketChanged;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Guest\Pipeline\GuestOrderPipeline;
use Tickets\Orders\Guest\Policy\GuestOrderAccessPolicy;
use Tickets\Orders\Shared\Contract\OrderAccessPolicyInterface;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;
use Tickets\Orders\Shared\Specification\CompositeOrderSpecification;
use Tickets\PromoCode\Response\ExternalPromoCodeDto;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessGuestNotificationQuestionnaire;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramByQuestionnaireSend;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;

/**
 * Гостевой заказ — стандартная покупка билета через форму сайта.
 *
 * Особенности:
 * - Привязан к аккаунту пользователя, виден в его личном кабинете
 * - Имеет способ оплаты и цену (в отличие от BaseOrder)
 * - При оплате генерирует PDF-билеты и рассылает анкеты гостям
 * - Поддерживает смену данных гостей (ФИО/email) без смены статуса
 *
 * Переходы статусов определяются GuestOrderPipeline.
 */
final class GuestOrder extends BaseOrder
{
    public function __construct(
        Uuid                    $id,
        Uuid                    $festivalId,
        Uuid                    $userId,
        Status                  $status,
        array                   $tickets,
        private readonly Uuid     $typesOfPaymentId,
        private readonly PriceDto $price,
        private readonly Uuid     $ticketTypeId,
        private readonly string   $phone,
        private readonly ?string  $promoCode = null,
    ) {
        $this->id         = $id;
        $this->festivalId = $festivalId;
        $this->userId     = $userId;
        $this->status     = $status;
        $this->tickets    = $tickets;
    }

    // ----------------------------------------------------------------
    // Фабричный метод создания заказа
    // ----------------------------------------------------------------

    /**
     * Создаёт новый гостевой заказ со статусом NEW.
     * Записывает Domain Event для email-уведомления о создании.
     */
    public static function create(GuestOrderDto $dto, int $kilter): self
    {
        $order = new self(
            id:               $dto->getId(),
            festivalId:       $dto->getFestivalId(),
            userId:           $dto->getUserId(),
            status:           new Status(Status::NEW),
            tickets:          $dto->getTickets(),
            typesOfPaymentId: $dto->getTypesOfPaymentId(),
            price:            $dto->getPriceDto(),
            ticketTypeId:     $dto->getTicketTypeId(),
            phone:            $dto->getPhone(),
            promoCode:        $dto->getPromoCode(),
        );

        $order->record(new ProcessUserNotificationNewOrderTicket(
            $dto->getEmail(),
            $kilter,
            $dto->getTicketTypeId(),
            $dto->getFestivalId(),
        ));

        $order->recordHistory(new OrderCreatedEvent(
            ticketType: $dto->getTicketTypeId()->value(),
            price:      $dto->getPriceDto()->getTotalPrice(),
            kilter:     $kilter,
        ));

        return $order;
    }

    // ----------------------------------------------------------------
    // Семантические методы смены статуса
    // ----------------------------------------------------------------

    /**
     * Подтверждение оплаты.
     *
     * Создаёт билеты (PDF + QR), отправляет email покупателю,
     * рассылает ссылки на анкеты всем гостям.
     */
    public function confirmPayment(
        string               $email,
        ?string              $comment             = null,
        ?ExternalPromoCodeDto $externalPromoCode  = null,
    ): void {
        $this->changeStatus(new Status(Status::PAID), [
            'email'             => $email,
            'comment'           => $comment,
            'externalPromoCode' => $externalPromoCode,
        ]);
    }

    /**
     * Отмена заказа.
     *
     * Аннулирует билеты, отправляет email об отмене.
     */
    public function cancelOrder(string $email): void
    {
        $this->updateTicketIds();
        $this->changeStatus(new Status(Status::CANCEL), ['email' => $email]);
    }

    /**
     * Перевод в статус «Возникли трудности».
     *
     * Требует обязательный комментарий. Аннулирует билеты.
     */
    public function markDifficultiesArose(string $email, string $comment): void
    {
        $this->updateTicketIds();
        $this->changeStatus(new Status(Status::DIFFICULTIES_AROSE), [
            'email'   => $email,
            'comment' => $comment,
        ]);
    }

    /**
     * Изменение данных (ФИО/email) гостей в заказе без смены статуса.
     *
     * Алгоритм:
     * 1. Применяем изменения к нужным гостям
     * 2. Обновляем UUID всех гостей (старые билеты удаляются, создаются новые)
     * 3. Отменяем старые билеты → создаём новые с обновлёнными данными
     * 4. Рассылаем уведомления и ссылки на анкеты изменённым гостям
     *
     * @param array $valueMap  [ticketId => newName]  ФИО для изменения
     * @param array $emailMap  [ticketId => newEmail] Email для изменения
     */
    public function updateGuestData(string $email, array $valueMap, array $emailMap): void
    {
        if (empty($valueMap) && empty($emailMap)) {
            throw new DomainException('Не переданы данные для изменения билета');
        }

        $changes        = [];
        $changedIndexes = [];

        foreach ($this->tickets as $index => $guest) {
            $ticketId = $guest->getId()->value();

            if (isset($valueMap[$ticketId]) || isset($emailMap[$ticketId])) {
                $changes[] = [
                    'oldName' => $guest->getValue(),
                    'newName' => $valueMap[$ticketId] ?? $guest->getValue(),
                ];
                $changedIndexes[] = $index;

                if (isset($valueMap[$ticketId])) {
                    $guest->updateValue($valueMap[$ticketId]);
                }
                if (isset($emailMap[$ticketId])) {
                    $guest->updateEmail($emailMap[$ticketId]);
                }
            }
        }

        $this->updateTicketIds();

        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessCreateTicket($this->id, $this->tickets));

        if (!empty($changes)) {
            $this->record(new ProcessUserNotificationOrderTicketChanged(
                $email,
                $changes,
                $this->ticketTypeId,
            ));
        }

        if (!$this->isChildTicket()) {
            foreach ($changedIndexes as $index) {
                $guest = $this->tickets[$index];
                $this->record(new ProcessGuestNotificationQuestionnaire(
                    $guest->getEmail() ?? $email,
                    $this->id->value(),
                    $guest->getId()->value(),
                ));
            }
        }

        if (!empty($changes)) {
            $this->recordHistory(new OrderTicketDataChangedEvent($changes));
        }
    }

    // ----------------------------------------------------------------
    // Реализация BaseOrder
    // ----------------------------------------------------------------

    public function getOrderType(): OrderType
    {
        return OrderType::fromString(OrderType::GUEST);
    }

    protected function createPipeline(): OrderPipelineInterface
    {
        return new GuestOrderPipeline();
    }

    protected function createSpecification(): OrderSpecificationInterface
    {
        return new CompositeOrderSpecification([]);
    }

    protected function createAccessPolicy(): OrderAccessPolicyInterface
    {
        return new GuestOrderAccessPolicy();
    }

    protected function onStatusChanged(Status $from, Status $to, array $params): void
    {
        match ((string)$to) {
            Status::PAID               => $this->firePaidEvents($params),
            Status::CANCEL             => $this->fireCancelEvents($params),
            Status::DIFFICULTIES_AROSE => $this->fireDifficultiesAroseEvents($params),
            default                    => null,
        };
    }

    // ----------------------------------------------------------------
    // Приватные методы — Domain Events при переходах
    // ----------------------------------------------------------------

    private function firePaidEvents(array $params): void
    {
        $email            = $params['email'];
        $comment          = $params['comment'] ?? null;
        $externalPromoCode = $params['externalPromoCode'] ?? null;

        $this->record(new ProcessCreateTicket($this->id, $this->tickets));

        $this->record(new ProcessUserNotificationOrderPaid(
            $email,
            $this->tickets,
            $this->ticketTypeId,
            $comment,
            $externalPromoCode?->getPromocode(),
        ));

        if (!$this->isChildTicket()) {
            foreach ($this->tickets as $ticket) {
                $guestEmail = $ticket->getEmail() ?? $email;
                $this->record(new ProcessGuestNotificationQuestionnaire(
                    $guestEmail,
                    $this->id->value(),
                    $ticket->getId()->value(),
                ));
                $this->record(new ProcessTelegramByQuestionnaireSend($guestEmail));
            }
        }
    }

    private function fireCancelEvents(array $params): void
    {
        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessUserNotificationOrderCancel(
            $params['email'],
            $this->ticketTypeId,
        ));
    }

    private function fireDifficultiesAroseEvents(array $params): void
    {
        if (empty($params['comment'])) {
            throw new DomainException('Комментарий обязателен для статуса «Возникли трудности»');
        }

        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessUserNotificationOrderDifficultiesArose(
            $this->id,
            $params['email'],
            $params['comment'],
            $this->ticketTypeId,
        ));
    }

    /** Обновляет UUID всех гостей перед отменой/пересозданием билетов. */
    private function updateTicketIds(): void
    {
        foreach ($this->tickets as $guest) {
            $guest->updateId();
        }
    }

    /** Детский билет — не рассылает анкеты гостям. */
    private function isChildTicket(): bool
    {
        return $this->ticketTypeId->value() === BaseOrder::CHILD_TICKET_TYPE_ID;
    }

    // ----------------------------------------------------------------
    // Геттеры специфичные для GuestOrder
    // ----------------------------------------------------------------

    public function getTypesOfPaymentId(): Uuid  { return $this->typesOfPaymentId; }
    public function getPrice(): PriceDto         { return $this->price; }
    public function getTicketTypeId(): Uuid      { return $this->ticketTypeId; }
    public function getPhone(): string           { return $this->phone; }
    public function getPromoCode(): ?string      { return $this->promoCode; }
}
