<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TicketSearchModel;
use Illuminate\Database\Seeder;

/**
 * Демо-данные поискового индекса ticket_search (для стенда) — чтобы ручной поиск на КПП
 * «без QR» было видно вживую: по ФИО, телефону, telegram, госномеру, имени ребёнка.
 *
 * Идемпотентно: updateOrCreate по ticket_uuid (повторный прогон не плодит дубли).
 * НЕ для прода — боевые строки наполняются из org→Baza ingest. festival_id совпадает
 * с фестивалём поиска (ChangesTestDataSeeder::FESTIVAL_ID), иначе строки не найдутся.
 */
class TicketSearchTestDataSeeder extends Seeder
{
    private const FESTIVAL_ID = ChangesTestDataSeeder::FESTIVAL_ID;

    public function run(): void
    {
        foreach ($this->rows() as $row) {
            TicketSearchModel::query()->updateOrCreate(
                ['ticket_uuid' => $row['ticket_uuid']],
                $row + ['festival_id' => self::FESTIVAL_ID],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'ticket_uuid' => 'aaa10001-0000-4000-8000-000000000001',
                'type' => 'electron', 'kilter' => 900001,
                'fio' => 'Иван Петров', 'phone' => '+79991234567', 'telegram' => 'ivan',
                'email' => 'ivan@example.com', 'city' => 'Казань',
                'type_ticket' => 'Оргвзнос', 'external_order_no' => '123',
                'payload' => ['role' => 'org_fee', 'is_buyer' => true],
            ],
            [
                'ticket_uuid' => 'aaa10002-0000-4000-8000-000000000002',
                'type' => 'electron', 'kilter' => 900002,
                'fio' => 'Пётр Смирнов', 'phone' => '+79990001122', 'telegram' => null,
                'email' => 'petr@example.com', 'city' => 'Москва',
                'type_ticket' => 'Оргвзнос', 'external_order_no' => '123',
                'payload' => ['role' => 'org_fee', 'is_buyer' => false],
            ],
            [
                'ticket_uuid' => 'aaa10003-0000-4000-8000-000000000003',
                'type' => 'electron', 'kilter' => 900003,
                'fio' => 'Маша Петрова (7 лет)', 'child_name' => 'Маша Петрова',
                'parent_phone' => '+79991234567', 'email' => 'ivan@example.com',
                'type_ticket' => 'Детский билет', 'external_order_no' => '123',
                'payload' => ['role' => 'kid', 'child' => ['age' => 7, 'allergies' => 'пыльца']],
            ],
            [
                'ticket_uuid' => 'aaa10004-0000-4000-8000-000000000004',
                'type' => 'auto', 'kilter' => null,
                'fio' => 'Иван Петров', 'car_number' => 'А123БВ77',
                'type_ticket' => 'Парковка', 'external_order_no' => '123',
                'payload' => ['role' => 'eco_car', 'car' => ['number' => 'А123БВ77']],
            ],
            [
                'ticket_uuid' => 'aaa10005-0000-4000-8000-000000000005',
                'type' => 'spisok', 'kilter' => 900005,
                'fio' => 'Гость Списка', 'phone' => '+79993334455',
                'email' => 'guest@example.com', 'city' => 'Самара',
                'type_ticket' => 'Список', 'external_order_no' => '777',
                'payload' => ['role' => 'list'],
            ],
        ];
    }
}
