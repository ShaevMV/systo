<?php

namespace Tests\Unit;

use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;

use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{

    private AddTicketsInReport $addTicketsInReport;

    private GetCurrentChanges $getCurrentChanges;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var AddTicketsInReport $service */
        $addTicketsInReport = $this->app->get(AddTicketsInReport::class);
        $this->addTicketsInReport = $addTicketsInReport;


        /** @var GetCurrentChanges $service */
        $getCurrentChanges = $this->app->get(GetCurrentChanges::class);
        $this->getCurrentChanges = $getCurrentChanges;
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_get_changes_id(): void
    {
        $id = $this->getCurrentChanges->getId(1);

        self::assertEquals(2, $id);
    }
}
