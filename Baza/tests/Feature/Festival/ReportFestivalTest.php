<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\ChangesModel;
use App\Models\FestivalModel;
use App\Models\User;
use Baza\Changes\Applications\Report\ReportForChanges;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-5 (TD-48): отчёт смен скоупится по фестивалю (де-хардкод). Сидер создаёт смену на
 * дефолтном фестивале (count_el=5); добавляем смену на F_A (count_el=3) и проверяем,
 * что отчёт дефолта не видит F_A и наоборот. + 500-чек Blade /report. БД baza_test.
 */
class ReportFestivalTest extends TestCase
{
    use RefreshDatabase;

    private const F_A = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

    private string $fDefault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1 + смена на дефолте, count_el=5
        $this->seed(BazaRolePermissionsSeeder::class);

        $this->fDefault = (string) config('baza.default_festival_id');
        FestivalModel::create(['id' => self::F_A, 'name' => 'Осень', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);
        FestivalModel::create(['id' => $this->fDefault, 'name' => 'Дефолт', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);

        // Смена на F_A с count_el=3 (для проверки изоляции отчёта).
        ChangesModel::factory()->forUsers([1])->create([
            'festival_id' => self::F_A,
            'count_el_tickets' => 3,
            'count_live_tickets' => 0,
        ]);
    }

    public function test_report_scoped_to_default_festival(): void
    {
        $report = app(ReportForChanges::class)->getReport(); // null → дефолтный фестиваль

        self::assertSame(5, $report->getReportTotalDto()->toArray()['el'], 'отчёт дефолта = 5 (не включает F_A=3)');
    }

    public function test_report_scoped_to_explicit_festival(): void
    {
        $report = app(ReportForChanges::class)->getReport(self::F_A);

        self::assertSame(3, $report->getReportTotalDto()->toArray()['el'], 'отчёт F_A = 3 (не включает дефолт=5)');
    }

    public function test_report_page_renders_with_festival_selector(): void
    {
        $this->actingAs(User::find(1))
            ->get('/report')
            ->assertOk()
            ->assertSee('Фестиваль отчёта');
    }
}
