<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Festival\TicketTypesModel;
use Illuminate\Database\Seeder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

/**
 * Доп. тип билета «Оргвзнос» (3500₽) — для случая разных типов билетов
 * между фестивалями. Используется при тестировании фильтра «по фестивалю».
 *
 * NB: не идемпотентен (insert + attach при существующем ID → конфликт).
 * См. TD-27 — план переделки всех сидеров на updateOrCreate.
 */
class TypeTicketsSecondFestivalSeeder extends Seeder
{
    public const TYPE_TICKET_FOR_SECOND_FESTIVAL = '222abc0c-fc8e-4a1d-a4b0-d345cafa0923';
    public const DEFAULT_PRICE = 3500;

    public function run(): void
    {
        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::TYPE_TICKET_FOR_SECOND_FESTIVAL;
        $ticketTypes->name = 'Оргвзнос';
        $ticketTypes->sort = 6;
        $ticketTypes->price = self::DEFAULT_PRICE;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();
    }
}
