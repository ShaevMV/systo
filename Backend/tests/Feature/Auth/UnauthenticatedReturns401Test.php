<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * TD-33: защищённый роут без/с протухшим токеном должен отдавать 401, а не 500.
 *
 * Раньше для НЕ-JSON запроса (без Accept: application/json) Authenticate редиректил
 * на route('login') — которого в API-only Backend нет → RouteNotFoundException → 500.
 * Теперь Handler::unauthenticated всегда отдаёт 401 JSON.
 */
class UnauthenticatedReturns401Test extends TestCase
{
    public function test_protected_post_without_accept_json_returns_401_not_500(): void
    {
        // post() без заголовка Accept: application/json — именно этот путь раньше давал 500.
        $this->post('/api/v1/qrOrder/getList')->assertStatus(401);
    }

    public function test_protected_post_with_accept_json_returns_401(): void
    {
        $this->postJson('/api/v1/qrOrder/getList')->assertStatus(401);
    }

    public function test_protected_get_without_token_returns_401(): void
    {
        $this->get('/api/v1/order/getUserList')->assertStatus(401);
    }
}
