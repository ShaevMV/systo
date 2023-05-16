<?php

namespace Tests\Unit;

use Baza\Tickets\Applications\Search\SearchService;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    public function test_correct_search(): void
    {
        /** @var  SearchService $searchService */
        $searchService = $this->app->get(SearchService::class);
        $result = $searchService->find('100');

    }
}
