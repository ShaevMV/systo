<?php

namespace Tests\Unit;

use Baza\Tickets\Applications\Search\SearchService;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    private SearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var SearchService $service */
        $service = $this->app->get(SearchService::class);
        $this->service = $service;
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_in_correct_find(): void
    {
        $result = $this->service->find('shaevmv');
        $d = 4;
    }
}
