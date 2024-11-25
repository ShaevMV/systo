<?php

namespace Tests\Unit;

use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;

use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Applications\Report\ReportForChanges;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;

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

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_get_changes_id(): void
    {
        $id = $this->getCurrentChanges->getId(1);

        self::assertEquals(1, $id);
    }


    public function test_get_report(): void
    {
        $report = $this->report->getReport();
    }
}
