<?php

declare(strict_types=1);

namespace Tests\Feature\TicketType;

use Tests\TestCase;

/**
 * Регресс: ticketType/getList не должен падать 500 при отсутствии orderBy/filter в запросе.
 * Экран «Привязки шаблонов» грузил типы билетов запросом {filter:{}} (без orderBy) → был
 * "Undefined array key orderBy" (500). Контроллер теперь читает их через ?? [] (TD-14).
 */
class TicketTypeGetListTest extends TestCase
{
    public function test_getlist_without_orderby_returns_200(): void
    {
        $this->postJson('/api/v1/ticketType/getList', ['filter' => []])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_getlist_without_any_body_returns_200(): void
    {
        $this->postJson('/api/v1/ticketType/getList', [])
            ->assertStatus(200);
    }
}
