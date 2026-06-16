<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Регресс (v2.6.1): getList-эндпоинты не должны падать 500 при отсутствии filter/orderBy в теле.
 *
 * Причина (найдено при проверке v2.6.0 на staging): контроллеры читали
 * $request->toArray()['filter'] / ['orderBy'] напрямую → "Undefined array key" (HTTP 500)
 * на запросе с пустым телом {}. Теперь — ?? [] + try/catch → Order::none() (TD-14).
 *
 * Затронуты: ticketType/getList, typesOfPayment/getList (публичные — падали 500 на стенде),
 * account/getList (admin — тот же небезопасный паттерн).
 */
class GetListWithoutFilterTest extends TestCase
{
    public function test_ticket_type_getlist_without_filter_orderby_returns_200(): void
    {
        $this->postJson('/api/v1/ticketType/getList', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/ticketType/getList', ['filter' => []])
            ->assertStatus(200);
    }

    public function test_types_of_payment_getlist_without_filter_orderby_returns_200(): void
    {
        $this->postJson('/api/v1/typesOfPayment/getList', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/typesOfPayment/getList', ['filter' => []])
            ->assertStatus(200);
    }

    public function test_account_getlist_without_filter_orderby_returns_200(): void
    {
        $admin = User::where('email', 'admin@spaceofjoy.ru')->firstOrFail();
        $token = auth('api')->login($admin);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/account/getList', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
