<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChangesModel;
use App\Models\User;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Демо-данные стенда для Ф2 (TD-41) — чтобы роли смены и RBAC было видно вживую.
 *
 * Назначает РАЗНЫЕ роли смены нескольким фикстура-пользователям (UsersTableSeeder),
 * открывает одну демо-смену из этого состава (роли попадут в change_user) и сеет
 * дефолтную матрицу прав. Идемпотентно: роли — update по email; смена открывается
 * только если открытой ещё нет; матрица — updateOrCreate.
 *
 * НЕ для прода: боевые роли назначает админ через UI «Права доступа» / форму смены.
 */
class StagingDemoSeeder extends Seeder
{
    /** email фикстуры => роль смены (ShiftRole). admin (id=1) остаётся суперролью по is_admin. */
    private const DEMO_ROLES = [
        'YulyaRahlina@spaceofjoy.ru' => ShiftRole::SHIFT_CHIEF,    // id=3
        'KostyaIhti@spaceofjoy.ru'   => ShiftRole::TICKETER,       // id=4
        'Infocentr1@spaceofjoy.ru'   => ShiftRole::KPP_COMMANDANT, // id=9
        'Ohrana1@spaceofjoy.ru'      => ShiftRole::GUARD,          // id=33
    ];

    public function run(): void
    {
        // role вне $fillable → прямой query-builder update (идемпотентно)
        foreach (self::DEMO_ROLES as $email => $role) {
            User::where('email', $email)->update(['role' => $role]);
        }

        // дефолтная матрица прав (идемпотентно)
        $this->call(BazaRolePermissionsSeeder::class);

        // демо-смена из этого состава — только если открытой смены ещё нет
        // (SaveChange всегда создаёт новую, поэтому защищаемся от дублей).
        if (! ChangesModel::query()->whereNull('end')->exists()) {
            app(SaveChange::class)->save([1, 3, 4, 9, 33], Carbon::now());
        }
    }
}
