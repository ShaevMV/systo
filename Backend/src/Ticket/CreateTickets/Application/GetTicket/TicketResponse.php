<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetTicket;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected string $name,
        protected int    $kilter,
        protected Uuid   $uuid,
        protected string $status,
        protected string $email,
        protected string $phone,
        protected string $city,
        protected ?string $comment,
        protected Carbon $date_order,
        protected ?string $festivalView = null,
        protected ?string $emailView = null,
        protected ?Uuid $festival_id = null,
        protected bool $is_need_seedling = false,
        protected ?Uuid $type_ticket_id = null,
        protected ?string $type_ticket = null,
        protected ?Uuid $order_id = null,
        protected ?Uuid $curator_id = null,
        protected ?string $curator_email = null,
        protected ?string $curator_name = null,
        protected ?string $project = null,
        protected ?Uuid $location_id = null,
        protected bool $isDeleted = false,
    )
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }

    public function getId(): Uuid
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getFestivalView(): ?string
    {
        return $this->festivalView ?? null;
    }

    /**
     * Структура для записи билета во внешнюю БД (таблица `el_tickets`).
     * White-list — указываем только реальные колонки таблицы, чтобы любые
     * новые поля DTO (например, для заказов-списков) не утекали в insert/update.
     */
    public function toArrayForBaza(): array
    {
        return [
            'kilter'           => $this->kilter,
            'uuid'             => $this->uuid->value(),
            'city'             => $this->city,
            'name'             => $this->name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'date_order'       => $this->date_order->toDateTimeString(),
            // Soft-deleted билет (пересоздан при смене ФИО) → статус cancel в el_tickets
            'status'           => $this->isDeleted ? 'cancel' : $this->status,
            'comment'          => $this->comment,
            'is_need_seedling' => $this->is_need_seedling,
            'type_ticket_id'   => $this->type_ticket_id?->value(),
            'type_ticket'      => $this->type_ticket,
            'festival_id'      => $this->festival_id?->value(),
        ];
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festival_id;
    }

    public function getEmailView(): ?string
    {
        return $this->emailView;
    }

    public function isIsNeedSeedling(): bool
    {
        return $this->is_need_seedling;
    }

    public function getTypeTicketId(): ?Uuid
    {
        return $this->type_ticket_id;
    }

    public function getTypeTicket(): ?string
    {
        return $this->type_ticket;
    }

    public function getOrderId(): ?Uuid
    {
        return $this->order_id;
    }

    public function getCuratorId(): ?Uuid
    {
        return $this->curator_id;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function getLocationId(): ?Uuid
    {
        return $this->location_id;
    }

    public function isList(): bool
    {
        return $this->curator_id !== null;
    }

    /**
     * Композитное представление куратора в формате "email | ФИО"
     * (как принято в админских журналах для совместимости с UI).
     */
    public function getCuratorComposite(): ?string
    {
        if ($this->curator_id === null) {
            return null;
        }

        $email = $this->curator_email ?? '';
        $name  = $this->curator_name ?? '';

        if ($email === '' && $name === '') {
            return $this->curator_id->value();
        }

        return trim($email . ' | ' . $name);
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * Структура для записи билета-списка во внешнюю БД (таблица spisok_tickets).
     * Поле id таблицы — auto_increment; идентификация билета идёт по kilter.
     * Поля project/comment/curator/email/name в Baza объявлены NOT NULL —
     * пустые значения подменяем на '', чтобы не падал INSERT.
     *
     * Если билет soft-deleted (удалён при пересоздании или отмене) — ставим
     * статус 'cancelled', чтобы старая запись в Baza не висела как действующая.
     */
    public function toArrayForSpisok(): array
    {
        return [
            'kilter'      => $this->kilter,
            'project'     => (string) ($this->project ?? ''),
            'curator'     => (string) ($this->getCuratorComposite() ?? ''),
            'email'       => $this->email,
            'name'        => $this->name,
            'date_order'  => $this->date_order->toDateTimeString(),
            'comment'     => (string) ($this->comment ?? ''),
            // Soft-deleted билет → cancel, чтобы старая запись в Baza не висела активной
            'status'      => $this->isDeleted ? 'cancel' : $this->status,
            'ticket_uuid' => $this->uuid->value(),
            'festival_id' => $this->festival_id?->value(),
        ];
    }
}
