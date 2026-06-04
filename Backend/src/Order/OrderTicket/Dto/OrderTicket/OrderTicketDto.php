<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Domain\ValueObject\MoneySnapshot;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;

/**
 * OrderTicketDto — DTO заказа в формате v2.6.0 (BREAKING change).
 *
 * Каждый гость — это {@see OrderGuestLine} со своим `ticket_type_id`, `promo_code`,
 * набором опций и снимком цены {@see MoneySnapshot}. На уровне заказа остаются только
 * `festival_id`, `user_id`, `types_of_payment_id`, `status`
 * (см. `.claude/specs/order-format-architecture.md` §1.2, §2.2).
 *
 * Удалены относительно старого формата:
 * - `ticket_type_id` — переехал на уровень гостя
 * - `promo_code` — переехал на уровень гостя
 * - `PriceDto $priceDto` — заменён агрегацией {@see self::totalPrice()} по строкам
 *
 * Источник: Чистая архитектура (Р. Мартин) — DTO переносит state между слоями,
 * расчёт цены делает Application-сервис `OrderPriceCalculator`, не DTO.
 */
class OrderTicketDto
{
    protected Uuid $id;

    /**
     * @param OrderGuestLine[] $guests
     */
    private function __construct(
        protected Uuid     $festival_id,
        protected Uuid     $user_id,
        protected string   $email,
        protected ?string  $phone,
        protected ?Uuid    $types_of_payment_id,
        protected array    $guests,
        protected string   $id_buy,
        protected string   $datePay,
        protected ?Status  $status,
        ?Uuid              $id = null,
        protected ?Uuid    $inviteLink = null,
        protected ?Uuid    $friendly_id = null,
        protected ?Uuid    $location_id = null,
        protected ?Uuid    $curator_id = null,
        protected ?string  $project = null,
    )
    {
        $this->id = $id ?? Uuid::random();
    }

    /**
     * Фабрика обычного/Friendly заказа.
     *
     * `$data['guests']` ожидается в формате v2.6.0 — каждый элемент содержит
     * `price_snapshot` (см. {@see OrderGuestLine::fromState()}). На пути создания заказа
     * контроллер заранее сериализует результат `OrderPriceCalculator::calculateLines()`
     * через `OrderGuestLine::toArray()`; на пути чтения из БД данные уже в этом формате
     * (после миграции v2.6.0).
     *
     * Флаг live-билета **деривируется из строк** (`guests[0]->isLive()`) — единый источник
     * правды (правило «единый формат данных», CONVENTIONS §8). Отдельного поля
     * `is_live_ticket` на заказе больше нет.
     *
     * @throws JsonException
     */
    public static function fromState(
        array $data,
        Uuid  $userId,
        ?Uuid $pusherId = null,
    ): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;

        $guests = self::parseGuests($data);
        $isLive = isset($guests[0]) && $guests[0]->isLive();

        $status = $data['status'] ?? (!$isLive ? Status::NEW : Status::PAID_FOR_LIVE);

        return new self(
            new Uuid($data['festival_id']),
            $userId,
            $data['email'],
            $data['phone'] ?? null,
            new Uuid($data['types_of_payment_id']),
            $guests,
            $data['id_buy'] ?? '',
            $data['date'] ?? '',
            new Status($status),
            $id,
            friendly_id: $pusherId,
            location_id: empty($data['location_id']) ? null : new Uuid($data['location_id']),
            curator_id:  empty($data['curator_id'])  ? null : new Uuid($data['curator_id']),
            project:     $data['project'] ?? null,
        );
    }

    /**
     * Фабричный метод для заказа-списка (создаётся куратором).
     *
     * Отличия от обычного fromState:
     * - НЕ требует ticket_type_id, types_of_payment_id, цены
     * - Обязательные: festival_id, location_id, curator_id, email получателя, гости
     * - Статус по умолчанию NEW_LIST
     * - Каждая строка создаётся с `ticket_type_id = null` и `price_snapshot = MoneySnapshot::zero()`
     *   (списки — билеты на локацию, без типа и без цены — см. `BUSINESS_RULES.md §12`)
     *
     * Поддерживает оба входных формата строк:
     * - сырые данные от куратора (без `price_snapshot`) — строятся с нулевым снимком;
     * - данные из БД (с `price_snapshot`, после миграции) — читаются через {@see OrderGuestLine::fromState()}.
     *
     * @throws JsonException
     */
    public static function fromStateForList(
        array   $data,
        Uuid    $userId,      // получатель билетов
        Uuid    $curatorId,   // куратор-создатель
        Uuid    $locationId,
        ?string $project = null,
    ): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;
        $status = $data['status'] ?? Status::NEW_LIST;

        $guests = self::parseListGuests($data);

        return new self(
            festival_id:           new Uuid($data['festival_id']),
            user_id:               $userId,
            email:                 $data['email'],
            phone:                 $data['phone'] ?? null,
            types_of_payment_id:   null,
            guests:                $guests,
            id_buy:                $data['id_buy'] ?? '',
            datePay:               $data['date'] ?? '',
            status:                new Status($status),
            id:                    $id,
            inviteLink:            null,
            friendly_id:           null,
            location_id:           $locationId,
            curator_id:            $curatorId,
            project:               $project ?? ($data['project'] ?? null),
        );
    }

    /**
     * Разбор `guests[]` для обычного заказа — строки уже в формате v2.6.0 (с `price_snapshot`).
     *
     * @return OrderGuestLine[]
     * @throws JsonException
     */
    private static function parseGuests(array $data): array
    {
        $guestsRaw = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);

        $guests = [];
        foreach ($guestsRaw as $guest) {
            $guests[] = OrderGuestLine::fromState($guest);
        }

        return $guests;
    }

    /**
     * Разбор `guests[]` для заказа-списка — поддержка сырых данных (без снимка цены)
     * и данных из БД (со снимком).
     *
     * @return OrderGuestLine[]
     * @throws JsonException
     */
    private static function parseListGuests(array $data): array
    {
        $guestsRaw = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);

        $guests = [];
        foreach ($guestsRaw as $guest) {
            $guests[] = array_key_exists('price_snapshot', $guest)
                ? OrderGuestLine::fromState($guest)
                : new OrderGuestLine(
                    id: isset($guest['id']) && !empty($guest['id']) ? new Uuid($guest['id']) : Uuid::random(),
                    value: $guest['value'],
                    email: $guest['email'] ?? null,
                    number: isset($guest['number']) ? (int) $guest['number'] : null,
                    festivalId: new Uuid($guest['festival_id'] ?? $data['festival_id']),
                    ticketTypeId: null,
                    options: [],
                    promoCode: null,
                    price: MoneySnapshot::zero(),
                    isLiveTicket: false,
                );
        }

        return $guests;
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        $jsonGuests = Json::encode(array_map(
            static fn (OrderGuestLine $guest): array => $guest->toArray(),
            $this->guests,
        ));

        return [
            'id' => $this->id,
            'festival_id' => $this->festival_id,
            'user_id' => $this->user_id,
            // ticket_type_id и promo_code теперь per-guest (в JSON guests[]),
            // на уровне заказа — null (источник правды переехал, спека §1.2).
            // Фильтры админки по этим колонкам переводятся на per-guest в Сессии 3.
            'ticket_type_id' => null,
            'promo_code' => null,
            'types_of_payment_id' => $this->types_of_payment_id?->value(),
            'guests' => $jsonGuests,
            'phone' => $this->phone,
            // Денормализованные итоги по заказу — для быстрых выборок в админке (спека §5.3).
            'price' => $this->totalPrice()->asFloat(),
            'discount' => $this->discountTotal()->asFloat(),
            'status' => (string)$this->status,
            'date' => (string)$this->datePay,
            'id_buy' => $this->id_buy,
            'friendly_id' => $this->friendly_id?->value(),
            'location_id' => $this->location_id?->value(),
            'curator_id'  => $this->curator_id?->value(),
            'project'     => $this->project,
        ];
    }

    /**
     * @return OrderGuestLine[]
     */
    public function getGuests(): array
    {
        return $this->guests;
    }

    /**
     * Итог по всему заказу — сумма {@see OrderGuestLine::total()} по строкам.
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
     * Суммарная скидка по заказу — сумма `discount` снимков всех строк.
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
     * Представительный `ticket_type_id` заказа — первый непустой по строкам.
     *
     * Нужен для совместимости с событиями/историей, которые исторически принимали один
     * `ticket_type_id` на заказ (письма, выбор PDF-шаблона). Для однотипных заказов
     * (а это все legacy и большинство новых) поведение идентично прежнему.
     * Для заказов-списков — null.
     */
    public function firstTicketTypeId(): ?Uuid
    {
        foreach ($this->guests as $guest) {
            if ($guest->ticketTypeId !== null) {
                return $guest->ticketTypeId;
            }
        }

        return null;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }

    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    public function getTypesOfPaymentId(): ?Uuid
    {
        return $this->types_of_payment_id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Live-билет деривируется из строк (единый источник правды) — флаг проставляется
     * `OrderPriceCalculator` из `ticket_type.is_live_ticket` и хранится в `guests[]`.
     */
    public function isIsLiveTicket(): bool
    {
        return isset($this->guests[0]) && $this->guests[0]->isLive();
    }

    public function isBilling(): bool
    {
        return $this->types_of_payment_id !== null
            && $this->types_of_payment_id->equals(new Uuid('3fcded69-4aef-4c4a-a041-52c91e5afd91'));
    }

    public function getInviteLink(): ?Uuid
    {
        return $this->inviteLink;
    }

    public function setInviteLink(?Uuid $inviteLink): void
    {
        $this->inviteLink = $inviteLink;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFriendlyId(): ?Uuid
    {
        return $this->friendly_id;
    }

    public function getLocationId(): ?Uuid
    {
        return $this->location_id;
    }

    public function getCuratorId(): ?Uuid
    {
        return $this->curator_id;
    }

    public function isList(): bool
    {
        return $this->curator_id !== null;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }
}
