<?php

namespace Tests\Unit;

use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Applications\Report\ReportForChanges;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;

/**
 * Integration тесты для сервисов смен — требуют тестовые данные в БД
 * (созданную смену, billing-токены, etc). Сейчас сидеров для Baza нет.
 * Тесты помечены как skipped до создания сидеров (TD-2 follow-up).
 */
class ChangesTest extends TestCase
{
    private AddTicketsInReport $addTicketsInReport;

    private GetCurrentChanges $getCurrentChanges;
    private ReportForChanges $report;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var AddTicketsInReport $service */
        $addTicketsInReport = $this->app->get(AddTicketsInReport::class);
        $this->addTicketsInReport = $addTicketsInReport;


        /** @var GetCurrentChanges $service */
        $getCurrentChanges = $this->app->get(GetCurrentChanges::class);
        $this->getCurrentChanges = $getCurrentChanges;

        /** @var ReportForChanges $report */
        $report = $this->app->get(ReportForChanges::class);
        $this->report = $report;
    }

    public function test_get_changes_id(): void
    {
        $this->markTestSkipped('Требует сидера со сменой в БД — нет сидеров для Baza, TD-2 follow-up');

        $id = $this->getCurrentChanges->getId(1);

        self::assertEquals(1, $id);
    }


    public function test_get_report(): void
    {
        $this->markTestSkipped('Требует сидера с данными для отчёта + assertions — TD-2 follow-up');

        $report = $this->report->getReport();
    }
}
