<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\ChangesModel;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Database\Seeders\FestivalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-2 (TD-48): legacy Blade-форма смены (/change/edit, /change/save) с фестивалём.
 * 500-проверка рендера и сохранения через Blade-флоу. БД baza_test.
 */
class ChangeBladeFestivalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1
        $this->seed(BazaRolePermissionsSeeder::class);
        $this->seed(FestivalSeeder::class);            // 1 активный фестиваль (дефолт)
    }

    public function test_edit_form_renders_with_festival_selector(): void
    {
        $this->actingAs(User::find(1))
            ->get('/change/edit')
            ->assertOk()
            ->assertSee('Фестиваль смены');
    }

    public function test_save_creates_shift_with_default_festival(): void
    {
        $this->actingAs(User::find(1))
            ->post('/change/save', [
                'compound' => [1],
                'chief' => 1,
                'start' => Carbon::now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('changes.report'));

        $change = ChangesModel::whereNull('end')->whereJsonContains('user_id', 1)->first();
        self::assertNotNull($change);
        self::assertSame((string) config('baza.default_festival_id'), (string) $change->festival_id);
    }
}
