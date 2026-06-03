<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing;

use Carbon\Carbon;
use DomainException;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Application\GetTicketType\GetTicketType;
use Tickets\Festival\Response\TicketTypeDto;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Repositories\OptionRepositoryInterface;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestInput;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;
use Tickets\Order\OrderTicket\Domain\ValueObject\MoneySnapshot;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestOption;
use Tickets\PromoCode\Application\SearchPromoCode\IsCorrectPromoCode;

/**
 * OrderPriceCalculator — Application-сервис расчёта цены строк заказа.
 *
 * Ядро BREAKING-change v2.6.0 (см. `.claude/specs/order-format-architecture.md` §4.2).
 *
 * **Ответственности:**
 * 1. Принять «сырые» данные гостей от контроллера (см. {@see RawGuestInput}).
 * 2. Загрузить актуальные цены типов билетов и опций (волны).
 * 3. Применить промокод (процент — только от базы билета, не от базы+опций).
 * 4. Создать снимки {@see OrderGuestOption} с замороженной ценой/именем опции.
 * 5. Собрать {@see OrderGuestLine}[] с заполненным {@see MoneySnapshot}.
 *
 * **Расчёт по строке:**
 * ```
 *   base        = getTicketType->getPrice(ticket_type_id, now).price        // волна или ticket_type.price
 *   optionsSum  = sum(option.priceSnapshot)                                  // развёрнут qty в N снимков
 *   discount    = isCorrectPromoCode(promo_code, base, ticket_type_id, festival_id)
 *   total       = base + optionsSum - discount                               // clamp ≥ 0 в Money::subtract
 * ```
 *
 * **Инварианты (валидируется до расчёта):**
 * - В одном заказе нельзя смешивать live + non-live билеты (BUSINESS_RULES §1, встреча 2026-05-30).
 * - Каждая опция в payload должна быть активна для своего ticket_type — иначе {@see DomainException}.
 *
 * **Что НЕ делает Calculator:**
 * - Не сохраняет заказ — сохранение в Repository (см. `CreatingOrderCommandHandler`).
 * - Не публикует Domain Events — это в `OrderTicket` агрегате.
 * - Не валидирует доступность волны на дату в прошлом — фабрика {@see Carbon} даёт now() по умолчанию.
 *
 * Источник: Чистая архитектура — Application слой оркеструет Domain + Infrastructure;
 * Совершенный код, гл. «Высокоуровневые конструкции» — сервис делает одну вещь — расчёт.
 */
class OrderPriceCalculator
{
    /**
     * Защита от DoS + бизнес-правило: один заказ — не более 10 гостей.
     * Реальные сценарии: индивидуальный покупатель (1-2 билета) или малая группа (до 10).
     * Большие группы оформляются куратором через {@see \Tickets\Order\OrderTicket\Domain\OrderTicket::createList()}
     * (заказы-списки, без ограничения), не через обычный flow.
     */
    public const MAX_GUESTS_PER_ORDER = 10;

    public function __construct(
        private GetTicketType $getTicketType,
        private IsCorrectPromoCode $isCorrectPromoCode,
        private OptionRepositoryInterface $optionRepository,
    ) {
    }

    /**
     * @param  RawGuestInput[]  $rawGuests  непустой массив гостей
     * @return OrderGuestLine[] с теми же индексами что и `$rawGuests`
     *
     * @throws DomainException
     *   - если `$rawGuests` пустой или превышает {@see self::MAX_GUESTS_PER_ORDER}
     *   - если ticket_type гостя из другого фестиваля
     *   - если в заказе смешаны live + non-live билеты
     *   - если опция гостя не активна для его ticket_type
     */
    public function calculateLines(Uuid $festivalId, array $rawGuests, ?Carbon $now = null): array
    {
        if (empty($rawGuests)) {
            throw new DomainException('OrderPriceCalculator::calculateLines() requires at least one guest');
        }

        if (count($rawGuests) > self::MAX_GUESTS_PER_ORDER) {
            throw new DomainException(sprintf(
                'В одном заказе нельзя оформить более %d гостей — оформите несколько заказов',
                self::MAX_GUESTS_PER_ORDER,
            ));
        }

        $now ??= Carbon::now();

        // 1. Кешируем TicketTypeDto по `ticket_type_id` — один заказ часто использует 1-2 типа.
        //    Заодно валидируем что все ticket_type существуют (GetTicketType бросит DomainException)
        //    И что они принадлежат тому фестивалю что и заказ (защита от подмены ticket_type_id).
        $ticketTypeCache = $this->loadTicketTypeCache($rawGuests, $festivalId);

        // 2. Инвариант: live + non-live в одном заказе нельзя.
        $this->assertNotMixedLiveAndRegular($ticketTypeCache);

        // 3. Загружаем активные опции пакетно по уникальным ticket_type_id.
        //    Map: [ticket_type_id_string => [option_id_string => OptionForTicketTypeView]]
        $optionsByTicketType = $this->loadActiveOptionsByTicketType(array_keys($ticketTypeCache));

        // 4. Считаем каждую строку.
        $lines = [];
        foreach ($rawGuests as $idx => $rawGuest) {
            $lines[$idx] = $this->buildLine(
                $rawGuest,
                $festivalId,
                $ticketTypeCache[$rawGuest->ticketTypeId->value()],
                $optionsByTicketType[$rawGuest->ticketTypeId->value()] ?? [],
                $now,
            );
        }

        return $lines;
    }

    /**
     * @param  RawGuestInput[]  $rawGuests
     * @return array<string, TicketTypeDto>  ключ — `ticket_type_id` как string
     */
    private function loadTicketTypeCache(array $rawGuests, Uuid $festivalId): array
    {
        $cache = [];
        $festivalIdStr = $festivalId->value();

        foreach ($rawGuests as $rawGuest) {
            $key = $rawGuest->ticketTypeId->value();
            if (! isset($cache[$key])) {
                // getTicketsTypeByUuid бросит DomainException, если тип не найден — это OK.
                $ticketType = $this->getTicketType->getTicketsTypeByUuid($rawGuest->ticketTypeId);

                // Защита от подмены ticket_type_id из другого фестиваля. Без этой проверки
                // атакующий мог бы оформить билет фестиваля X в заказе на фестиваль Y —
                // и получить цену билета X (например, дешевле), приехав на Y.
                $festivalsOfTicketType = array_map(
                    static fn ($uuid) => $uuid->value(),
                    $ticketType->getFestivalListId(),
                );
                if (! in_array($festivalIdStr, $festivalsOfTicketType, true)) {
                    throw new DomainException(sprintf(
                        'Тип билета %s не принадлежит фестивалю %s — обновите страницу',
                        $key,
                        $festivalIdStr,
                    ));
                }

                $cache[$key] = $ticketType;
            }
        }

        return $cache;
    }

    /**
     * @param  array<string, TicketTypeDto>  $cache
     */
    private function assertNotMixedLiveAndRegular(array $cache): void
    {
        $hasLive = false;
        $hasRegular = false;
        foreach ($cache as $dto) {
            if ($dto->isLiveTicket()) {
                $hasLive = true;
            } else {
                $hasRegular = true;
            }
        }

        if ($hasLive && $hasRegular) {
            throw new DomainException(
                'В одном заказе нельзя смешивать живые и обычные билеты — оформите отдельным заказом'
            );
        }
    }

    /**
     * Грузим активные опции для всех уникальных ticket_type, по одному вызову на тип
     * (типов в одном заказе обычно 1-2 — N+1 риск ничтожен).
     *
     * @param  string[]  $ticketTypeIds  список строк-UUID
     * @return array<string, array<string, OptionForTicketTypeView>>
     */
    private function loadActiveOptionsByTicketType(array $ticketTypeIds): array
    {
        $byType = [];
        foreach ($ticketTypeIds as $ticketTypeId) {
            $views = $this->optionRepository->getActiveOptionsForTicketType(new Uuid($ticketTypeId));

            $map = [];
            foreach ($views as $view) {
                $map[$view->getId()->value()] = $view;
            }
            $byType[$ticketTypeId] = $map;
        }

        return $byType;
    }

    /**
     * @param  array<string, OptionForTicketTypeView>  $activeOptionsForType
     */
    private function buildLine(
        RawGuestInput $rawGuest,
        Uuid $festivalId,
        TicketTypeDto $ticketType,
        array $activeOptionsForType,
        Carbon $now,
    ): OrderGuestLine {
        $basePrice = $this->resolveBasePrice($rawGuest->ticketTypeId, $now);

        $optionSnapshots = $this->expandOptions($rawGuest->options, $activeOptionsForType, $ticketType);

        $optionsSum = Money::zero();
        foreach ($optionSnapshots as $snapshot) {
            $optionsSum = $optionsSum->add($snapshot->priceSnapshot);
        }

        $discount = $this->resolveDiscount(
            $rawGuest->promoCode,
            $basePrice,
            $rawGuest->ticketTypeId,
            $festivalId,
        );

        return new OrderGuestLine(
            id: Uuid::random(),
            value: $rawGuest->value,
            email: $rawGuest->email,
            number: null,
            festivalId: $festivalId,
            ticketTypeId: $rawGuest->ticketTypeId,
            options: $optionSnapshots,
            promoCode: $rawGuest->promoCode,
            price: new MoneySnapshot(
                basePrice: $basePrice,
                optionsSum: $optionsSum,
                discount: $discount,
            ),
            isLiveTicket: $ticketType->isLiveTicket(),
        );
    }

    private function resolveBasePrice(Uuid $ticketTypeId, Carbon $now): Money
    {
        // GetTicketType::getPrice() возвращает float через подзапрос к ticket_type_price
        // (или fallback на ticket_type.price). См. InMemoryTicketTypeRepository::buildBuilder().
        $priceResponse = $this->getTicketType->getPrice($ticketTypeId, $now);

        return Money::fromFloat($priceResponse->getPrice());
    }

    /**
     * Развернуть `RawGuestOptionInput[]` с `qty` в `OrderGuestOption[]` со снимками имени/цены.
     *
     * Каждая опция должна быть активна для своего ticket_type — иначе {@see DomainException}
     * (атакующий не сможет «подкинуть» неактивную опцию по UUID в обход формы).
     *
     * @param  RawGuestOptionInput[]  $rawOptions
     * @param  array<string, OptionForTicketTypeView>  $activeOptions
     * @return OrderGuestOption[]
     */
    private function expandOptions(array $rawOptions, array $activeOptions, TicketTypeDto $ticketType): array
    {
        $expanded = [];
        foreach ($rawOptions as $rawOption) {
            $optionIdStr = $rawOption->optionId->value();
            if (! isset($activeOptions[$optionIdStr])) {
                throw new DomainException(sprintf(
                    'Опция %s недоступна для типа билета %s — выберите другую опцию или обновите страницу',
                    $optionIdStr,
                    $ticketType->getId()->value(),
                ));
            }

            $view = $activeOptions[$optionIdStr];

            // Кратность qty → N снимков (Domain VO кратность не моделирует, см. RawGuestInput).
            for ($i = 0; $i < $rawOption->qty; $i++) {
                $expanded[] = new OrderGuestOption(
                    optionId: $rawOption->optionId,
                    nameSnapshot: $view->getName(),
                    priceSnapshot: new Money($view->getPrice()),
                );
            }
        }

        return $expanded;
    }

    /**
     * Зафиксированное на встрече 2026-05-30 правило: процентный промокод применяется
     * **только к базе билета**, не к сумме с опциями. Фиксированный — как есть.
     *
     * `IsCorrectPromoCode::findPromoCode()` возвращает уже посчитанную скидку (float)
     * через `setDiscount(price * discount / 100)` для процентных промокодов.
     */
    private function resolveDiscount(
        ?string $promoCode,
        Money $basePrice,
        Uuid $ticketTypeId,
        Uuid $festivalId,
    ): Money {
        if ($promoCode === null) {
            return Money::zero();
        }

        $promoCodeDto = $this->isCorrectPromoCode->findPromoCode(
            $promoCode,
            $basePrice->asFloat(),
            $ticketTypeId,
            $festivalId,
        );

        // `IsCorrectPromoCode::findPromoCode()` всегда возвращает не-null `PromoCodeDto`
        // (для невалидного промокода — пустой DTO с discount = 0.0), а `getDiscount(): float`
        // тоже не nullable. Поэтому `??` тут лишний — берём напрямую.
        return Money::fromFloat($promoCodeDto->getDiscount());
    }
}
