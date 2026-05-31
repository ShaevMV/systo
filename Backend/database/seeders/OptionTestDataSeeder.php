<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Option\OptionModel;
use App\Models\Option\OptionPriceModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

/**
 * Тестовые опции для билетов (v2.6.0).
 *
 * Создаёт 2 опции:
 *  - «Саженец» (500₽) — привязана к Оргвзносу и Оргвзносу для регионов
 *  - «Печатный билет» (200₽) — привязана к Оргвзносу
 *
 * Используется в Feature-тестах и в локальной/staging среде для проверки
 * админ-UI без ручного создания через tinker. Идемпотентен: при повторном
 * запуске использует фиксированные UUID и затирает старые записи через `updateOrCreate`.
 *
 * См. `.claude/specs/ticket-options.md`.
 */
class OptionTestDataSeeder extends Seeder
{
    public const ID_OPTION_SAPLING = 'a1111111-1111-1111-1111-111111111111';
    public const ID_OPTION_PRINTED_TICKET = 'a2222222-2222-2222-2222-222222222222';

    public const ID_PRICE_SAPLING = 'b1111111-1111-1111-1111-111111111111';
    public const ID_PRICE_PRINTED_TICKET = 'b2222222-2222-2222-2222-222222222222';

    public const PRICE_SAPLING = 500;
    public const PRICE_PRINTED_TICKET = 200;

    public function run(): void
    {
        $this->seedSapling();
        $this->seedPrintedTicket();
    }

    private function seedSapling(): void
    {
        /** @var OptionModel $option */
        $option = OptionModel::updateOrCreate(
            ['id' => self::ID_OPTION_SAPLING],
            [
                'name' => 'Саженец',
                'active' => true,
                'festival_id' => FestivalHelper::UUID_FESTIVAL,
            ]
        );

        OptionPriceModel::updateOrCreate(
            ['id' => self::ID_PRICE_SAPLING],
            [
                'option_id' => self::ID_OPTION_SAPLING,
                'price' => self::PRICE_SAPLING,
                'before_date' => Carbon::now()->addMonths(6),
            ]
        );

        $option->ticketTypes()->sync([
            TypeTicketsSeeder::ID_FOR_FIRST_WAVE => [
                'description' => 'Один саженец вашего региона в подарок при покупке Оргвзноса',
            ],
            TypeTicketsSeeder::ID_FOR_REGIONS => [
                'description' => 'Саженец местного питомника (для удалённых участников)',
            ],
        ]);
    }

    private function seedPrintedTicket(): void
    {
        /** @var OptionModel $option */
        $option = OptionModel::updateOrCreate(
            ['id' => self::ID_OPTION_PRINTED_TICKET],
            [
                'name' => 'Печатный билет',
                'active' => true,
                'festival_id' => FestivalHelper::UUID_FESTIVAL,
            ]
        );

        OptionPriceModel::updateOrCreate(
            ['id' => self::ID_PRICE_PRINTED_TICKET],
            [
                'option_id' => self::ID_OPTION_PRINTED_TICKET,
                'price' => self::PRICE_PRINTED_TICKET,
                'before_date' => Carbon::now()->addMonths(6),
            ]
        );

        $option->ticketTypes()->sync([
            TypeTicketsSeeder::ID_FOR_FIRST_WAVE => [
                'description' => 'Бумажная распечатка билета — выдаётся на регистрации',
            ],
        ]);
    }
}
