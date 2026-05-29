<?php

namespace Tests\Unit;

use Baza\Tickets\Applications\Search\SearchService;
use Baza\Tickets\Responses\SearchResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke-тест SearchService: вызов через DI должен возвращать SearchResponse
 * и не падать при пустой БД (все 5 репозиториев возвращают пустые коллекции).
 *
 * Сложные сценарии с тестовыми билетами (el_tickets / friendly / spisok / live /
 * auto) — TODO после создания соответствующих сидеров (TD-2 follow-up).
 */
class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_search_returns_search_response_on_empty_db(): void
    {
        /** @var SearchService $searchService */
        $searchService = $this->app->get(SearchService::class);

        $result = $searchService->find('100');

        self::assertInstanceOf(SearchResponse::class, $result);
    }
}
