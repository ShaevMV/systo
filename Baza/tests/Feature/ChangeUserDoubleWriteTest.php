<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nette\Utils\Json;
use Tests\TestCase;

/**
 * Тесты двойной записи состава смены в change_user (Baza, Ф2 PR-2).
 *
 * Сохранение смены (SaveChange) пишет состав И в changes.user_id (JSON, старый
 * путь — вход/отчёты не ломаются), И в change_user (новая таблица с ролями).
 * Роль производная (ShiftRole::fromUser по is_admin) — явное назначение в PR-6.
 *
 * БД baza_test (phpunit.xml). UsersTableSeeder: id=1 admin (is_admin=true),
 * id=3 обычный (is_admin=false).
 */
class ChangeUserDoubleWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UsersTableSeeder::class);
    }

    public function test_save_double_writes_change_user_with_derived_roles(): void
    {
        app(SaveChange::class)->save([1, 3], Carbon::now());

        $change = ChangesModel::query()->latest('id')->first();
        self::assertNotNull($change);

        // старый путь цел: changes.user_id JSON содержит обоих
        $ids = array_map('intval', (array) Json::decode($change->user_id));
        self::assertEqualsCanonicalizing([1, 3], $ids);

        // двойная запись: change_user строки с производными ролями
        $rows = ChangeUserModel::where('change_id', $change->id)->get()->keyBy('user_id');
        self::assertCount(2, $rows);
        self::assertSame(ShiftRole::ADMINISTRATOR, $rows[1]->role, 'is_admin=true → administrator');
        self::assertSame(ShiftRole::TICKETER, $rows[3]->role, 'is_admin=false → ticketer');
    }

    public function test_remove_deletes_change_user_rows(): void
    {
        app(SaveChange::class)->save([1, 3], Carbon::now());
        $change = ChangesModel::query()->latest('id')->first();
        self::assertSame(2, ChangeUserModel::where('change_id', $change->id)->count());

        app(ChangesRepositoryInterface::class)->remove($change->id);

        self::assertSame(0, ChangeUserModel::where('change_id', $change->id)->count(), 'нет осиротевших change_user');
        self::assertNull(ChangesModel::find($change->id));
    }

    public function test_get_change_id_still_resolves_active_shift(): void
    {
        app(SaveChange::class)->save([1], Carbon::now());
        $change = ChangesModel::query()->latest('id')->first();

        self::assertSame($change->id, app(ChangesRepositoryInterface::class)->getChangeId(1));
    }

    public function test_resave_replaces_change_user_rows(): void
    {
        app(SaveChange::class)->save([1, 3], Carbon::now());
        $change = ChangesModel::query()->latest('id')->first();

        // пересохранить ту же смену с другим составом (по id)
        app(SaveChange::class)->save([3], Carbon::now(), $change->id);

        $rows = ChangeUserModel::where('change_id', $change->id)->get();
        self::assertCount(1, $rows, 'старый состав заменён, без дублей');
        self::assertSame(3, (int) $rows->first()->user_id);
    }

    public function test_duplicate_user_id_in_compound_is_deduped_not_fatal(): void
    {
        // Кривой POST compound[] с дублем не должен ронять сохранение (UNIQUE):
        // состав дедуплицируется одинаково в JSON и в change_user.
        app(SaveChange::class)->save([1, 1, 3], Carbon::now());

        $change = ChangesModel::query()->latest('id')->first();
        self::assertNotNull($change);

        self::assertSame(1, ChangeUserModel::where('change_id', $change->id)->where('user_id', 1)->count());
        self::assertSame(2, ChangeUserModel::where('change_id', $change->id)->count(), 'дубль схлопнут');

        $ids = array_map('intval', (array) Json::decode($change->user_id));
        self::assertEqualsCanonicalizing([1, 3], $ids, 'JSON тоже дедуплицирован');
    }
}
