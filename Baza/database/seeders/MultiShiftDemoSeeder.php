<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChangeUserModel;
use App\Models\User;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Демо стенда: ДВЕ открытые смены с РАЗНЫМИ начальниками и составами — чтобы владелец
 * увидел RBAC (роль×действие) и изоляцию смен вживую (TD-41 / Ф2).
 *
 * Богаче чем StagingDemoSeeder (тот открывает одну смену): здесь два начальника
 * разных смен (проверка изоляции), полный набор 5 ролей + дубль билетёра/охранника
 * во вторую смену. Роли назначаются users.role (вне $fillable → прямой update, как в
 * StagingDemoSeeder), смены создаются ТЕМ ЖЕ боевым путём SaveChange::save (CQRS +
 * syncChangeUsers + инвариант «есть начальник»), а не прямым insert — не дублируем логику.
 *
 * Идемпотентно:
 *  - роли — update по email;
 *  - матрица прав — updateOrCreate (BazaRolePermissionsSeeder);
 *  - смена конкретного начальника открывается только если он ещё не ведёт открытую смену
 *    (getChangeId по составу JSON + проверка роли shift_chief в change_user).
 *
 * НЕ для прода: боевые роли/смены заводит админ через UI «Права доступа» / форму смены.
 */
class MultiShiftDemoSeeder extends Seeder
{
    public const FESTIVAL_ID = ChangesTestDataSeeder::FESTIVAL_ID;

    /**
     * Глобальная роль смены на email фикстуры (UsersTableSeeder).
     * id=1 (admin) — суперроль administrator по is_admin, проставляем явно для наглядности.
     *
     * @var array<string, string>
     */
    private const DEMO_ROLES = [
        'admin@admin.ru'             => ShiftRole::ADMINISTRATOR,  // id=1  — суперроль
        'YulyaRahlina@spaceofjoy.ru' => ShiftRole::SHIFT_CHIEF,    // id=3  — начальник смены №1
        'Lera@spaceofjoy.ru'         => ShiftRole::SHIFT_CHIEF,    // id=8  — начальник смены №2 (изоляция)
        'KostyaIhti@spaceofjoy.ru'   => ShiftRole::TICKETER,       // id=4  — билетёр (смена №1)
        'Infocentr1@spaceofjoy.ru'   => ShiftRole::KPP_COMMANDANT, // id=9  — комендант КПП (смена №1)
        'Ohrana1@spaceofjoy.ru'      => ShiftRole::GUARD,          // id=33 — охранник (смена №1)
        'Archi@spaceofjoy.ru'        => ShiftRole::TICKETER,       // id=10 — билетёр (смена №2)
        'Ohrana2@spaceofjoy.ru'      => ShiftRole::GUARD,          // id=34 — охранник (смена №2)
    ];

    /**
     * Состав двух демо-смен: [chiefId, [members...]]. Начальник входит в состав
     * (инвариант Ф2). Составы не пересекаются — наглядная изоляция смен.
     *
     * @var array<int, array{0:int, 1:int[]}>
     */
    private const SHIFTS = [
        // Смена №1: начальник 3 (Юля), билетёр 4, комендант 9, охранник 33
        [3, [3, 4, 9, 33]],
        // Смена №2: начальник 8 (Лера), билетёр 10, охранник 34
        [8, [8, 10, 34]],
    ];

    public function run(): void
    {
        // 1) Роли (role вне $fillable → прямой query-builder update, идемпотентно).
        foreach (self::DEMO_ROLES as $email => $role) {
            User::where('email', $email)->update(['role' => $role]);
        }

        // 2) Дефолтная матрица прав (идемпотентно).
        $this->call(BazaRolePermissionsSeeder::class);

        // TODO(Ф7/PII): когда появится ShiftPermission::TICKET_PII (полная карточка билета),
        // добавить право `ticket.pii` в дефолт-матрицу (например, shift_chief/kpp_commandant).
        // Сейчас константы нет → НЕ добавляем, чтобы не сеять невалидное действие.

        // 3) Две открытые смены с разными начальниками — боевым путём SaveChange::save.
        //    ВАЖНО: SaveChange/репозиторий держат один экземпляр ChangesModel и при
        //    id=null делают $model->save() по нему же → повторный вызов на ТОМ ЖЕ
        //    инстансе обновил бы первую строку вместо вставки второй. Поэтому на каждую
        //    смену резолвим СВЕЖИЙ SaveChange (bind, не singleton → новый ChangesModel).
        foreach (self::SHIFTS as [$chiefId, $members]) {
            // Идемпотентность: открываем смену, только если этот начальник ещё не ведёт открытую.
            if ($this->chiefHasOpenShift(app(ChangesRepositoryInterface::class), $chiefId)) {
                continue;
            }

            app(SaveChange::class)->save($members, Carbon::now(), null, $chiefId);
        }
    }

    /**
     * Ведёт ли пользователь $chiefId уже открытую смену в роли начальника.
     * getChangeId ищет открытую смену по составу (JSON) → затем проверяем,
     * что в change_user он именно shift_chief (защита от ложного срабатывания,
     * если он лишь участник чужой смены).
     */
    private function chiefHasOpenShift(ChangesRepositoryInterface $repository, int $chiefId): bool
    {
        $changeId = $repository->getChangeId($chiefId);

        if ($changeId === null) {
            return false;
        }

        return ChangeUserModel::where('change_id', $changeId)
            ->where('user_id', $chiefId)
            ->where('role', ShiftRole::SHIFT_CHIEF)
            ->exists();
    }
}
