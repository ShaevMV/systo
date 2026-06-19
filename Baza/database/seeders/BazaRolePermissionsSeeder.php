<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BazaRolePermissionModel;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Illuminate\Database\Seeder;

/**
 * Дефолтная матрица прав «роль × действие» (Ф2).
 *
 * Идемпотентно (updateOrCreate) → безопасно гонять на каждом деплое. ПОДКЛЮЧЁН
 * к DatabaseSeeder: матрица — это конфиг (без ПДн), а её наличие защищает от
 * «заперли всех» (если появится не-админ, но матрица пуста). administrator —
 * суперроль (короткозамкнута в коде), в таблицу НЕ пишется.
 *
 * Перевыпуск/правка прав — из UI (PR-6). Здесь только разумный дефолт.
 */
class BazaRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            ShiftRole::SHIFT_CHIEF => [
                ShiftPermission::TICKET_SCAN,
                ShiftPermission::TICKET_SEARCH,
                ShiftPermission::TICKET_ENTER,
                ShiftPermission::REPORT_VIEW,
                ShiftPermission::SHIFT_COMPOSE,
                ShiftPermission::SHIFT_CLOSE,
                ShiftPermission::FINANCE_VIEW,
            ],
            ShiftRole::TICKETER => [
                ShiftPermission::TICKET_SCAN,
                ShiftPermission::TICKET_SEARCH,
                ShiftPermission::TICKET_ENTER,
            ],
            ShiftRole::KPP_COMMANDANT => [
                ShiftPermission::TICKET_SCAN,
                ShiftPermission::TICKET_SEARCH,
                ShiftPermission::TICKET_ENTER,
                ShiftPermission::FINANCE_VIEW,
            ],
            ShiftRole::GUARD => [
                ShiftPermission::TICKET_SCAN,
                ShiftPermission::TICKET_SEARCH,
                ShiftPermission::TICKET_ENTER,
            ],
        ];

        foreach ($matrix as $role => $actions) {
            foreach ($actions as $action) {
                BazaRolePermissionModel::updateOrCreate(
                    ['role' => $role, 'action' => $action],
                    []
                );
            }
        }
    }
}
