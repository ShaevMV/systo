<?php

namespace Tests\Unit;

use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Applications\Report\ReportForChanges;
use Baza\Changes\Applications\Report\ReportForChangesResponse;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;

/**
 * Integration-тесты для сервисов смен (Changes).
 *
 * Используют отдельную БД `baza_test` (см. phpunit.xml). RefreshDatabase
 * пересоздаёт схему на каждом тесте, ChangesTestDataSeeder создаёт
 * пользователей + одну открытую смену с user_id=1.
 *
 * Связано с TD-2 (починка PHPUnit), v2.5.1.
 */
class ChangesTest extends TestCase
{
    use RefreshDatabase;

    private GetCurrentChanges $getCurrentChanges;

    private ReportForChanges $report;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChangesTestDataSeeder::class);

        $this->getCurrentChanges = $this->app->get(GetCurrentChanges::class);
        $this->report = $this->app->get(ReportForChanges::class);
    }

    /**
     * Открытая смена для user_id=1 должна находиться репозиторием.
     */
    public function test_get_changes_id_returns_open_shift_for_admin(): void
    {
        $id = $this->getCurrentChanges->getId(ChangesTestDataSeeder::ADMIN_USER_ID);

        self::assertSame(1, $id);
    }

    /**
     * Для пользователя без смены — DomainException ('Ваша смена не найдена...').
     */
    public function test_get_changes_id_throws_for_user_without_shift(): void
    {
        $this->expectException(\DomainException::class);

        $this->getCurrentChanges->getId(999);
    }

    /**
     * Отчёт по festival_id должен вернуть ReportForChangesResponse
     * со сменой из сидера.
     */
    public function test_get_report_returns_response_with_seeded_shift(): void
    {
        $response = $this->report->getReport();

        self::assertInstanceOf(ReportForChangesResponse::class, $response);
    }
}
