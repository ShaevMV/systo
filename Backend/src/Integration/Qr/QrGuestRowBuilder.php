<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Integration\Qr\Assembler\AssembledQrGuest;
use Tickets\Integration\Qr\Assembler\AssembledQrOrder;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Repositories\OptionRepositoryInterface;

/**
 * Строит массив гостей в формате OrderGuestLine::toArray() (его ждёт OrderTicketDto::fromState)
 * из {@see AssembledQrOrder}. Выделено из {@see OrderTicketQrIngestor} ради тестируемости:
 * единственная зависимость — каталог опций (интерфейс), а не final Application-сервисы.
 *
 * Цена — ДЕКЛАРИРОВАННАЯ qr (Р2, CONTRACT_RFC_v0.md §5.2): per-guest price_snapshot берётся
 * из payload как есть, org его не пересчитывает. Имя/цена опции — из каталога org (снимок),
 * qty разворачивается в N снимков; неизвестная/неактивная опция → reject (анти-фрод, §3).
 */
final class QrGuestRowBuilder
{
    public function __construct(
        private readonly OptionRepositoryInterface $optionRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(AssembledQrOrder $order): array
    {
        $isLive = $order->type->isLive();

        return array_map(
            fn (AssembledQrGuest $guest): array => $this->buildRow($guest, $order->festivalId, $isLive),
            $order->guests,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(AssembledQrGuest $guest, string $festivalId, bool $isLive): array
    {
        return [
            'id' => Uuid::random()->value(),
            'value' => $guest->value,
            'email' => $guest->email,
            'number' => $guest->liveNumber !== null ? (int) $guest->liveNumber : null,
            'festival_id' => $festivalId,
            'ticket_type_id' => $guest->ticketTypeId,
            'options' => $this->buildOptionRows($guest),
            'promo_code' => $guest->promoCode,
            'price_snapshot' => [
                'base_price' => $guest->declaredBasePrice,
                'options_sum' => $guest->declaredOptionsSum,
                'discount' => $guest->declaredDiscount,
            ],
            'is_live_ticket' => $isLive,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildOptionRows(AssembledQrGuest $guest): array
    {
        if ($guest->options === [] || $guest->ticketTypeId === null) {
            return [];
        }

        $catalog = $this->catalogFor($this->toUuid($guest->ticketTypeId, 'ticket_type_id'));

        $rows = [];
        foreach ($guest->options as $option) {
            $optionId = $option->optionId->value();
            if (! isset($catalog[$optionId])) {
                throw new QrOrderRejectedException(sprintf(
                    'Опция %s не активна для типа билета %s (каталог org)',
                    $optionId,
                    $guest->ticketTypeId,
                ));
            }
            $view = $catalog[$optionId];
            for ($i = 0; $i < $option->qty; $i++) {
                $rows[] = [
                    'option_id' => $optionId,
                    'name' => $view->getName(),
                    'price' => $view->getPrice(),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, OptionForTicketTypeView>
     */
    private function catalogFor(Uuid $ticketTypeId): array
    {
        $map = [];
        foreach ($this->optionRepository->getActiveOptionsForTicketType($ticketTypeId) as $view) {
            $map[$view->getId()->value()] = $view;
        }

        return $map;
    }

    private function toUuid(string $value, string $field): Uuid
    {
        try {
            return new Uuid($value);
        } catch (InvalidArgumentException $e) {
            throw new QrOrderRejectedException(sprintf('Поле "%s" не является валидным UUID: %s', $field, $value), 0, $e);
        }
    }
}
