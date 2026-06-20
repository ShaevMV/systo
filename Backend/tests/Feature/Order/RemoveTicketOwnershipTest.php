<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\Ordering\OrderTicketModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\TestCase;

/**
 * IDOR-регресс (TD-28): `DELETE /api/v1/order/removeTicket/{orderId}/{ticketId}`
 * нельзя применить к ЧУЖОМУ заказу. Доступ — только admin или куратор-создатель
 * (curator_id == auth_id). До фикса любой залогиненный куратор удалял билет из
 * чужого заказа по UUID.
 */
class RemoveTicketOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Минимальный заказ-список с заданным владельцем-куратором.
     * FK отключаем на вставку: тест про гейт владения, а не про связи (festival/user/...).
     */
    private function makeListOrder(string $curatorId): string
    {
        $id = (string) RamseyUuid::uuid4();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // forceFill — festival_id/др. вне $fillable; FK отключены (тест про гейт владения).
        (new OrderTicketModel())->forceFill([
            'id' => $id,
            'user_id' => (string) RamseyUuid::uuid4(),
            'festival_id' => (string) RamseyUuid::uuid4(),
            'status' => 'new_list',
            'curator_id' => $curatorId,
            'guests' => '[]',
            'date' => now()->toDateTimeString(),
        ])->save();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $id;
    }

    private function url(string $orderId): string
    {
        return '/api/v1/order/removeTicket/' . $orderId . '/' . RamseyUuid::uuid4();
    }

    public function test_stranger_curator_cannot_remove_from_others_order(): void
    {
        $owner = User::factory()->create(['role' => 'curator']);
        $stranger = User::factory()->create(['role' => 'curator']);
        $orderId = $this->makeListOrder($owner->id->value());

        $this->actingAs($stranger, 'api');

        $this->deleteJson($this->url($orderId))
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_owner_curator_passes_ownership_gate(): void
    {
        $owner = User::factory()->create(['role' => 'curator']);
        $orderId = $this->makeListOrder($owner->id->value());

        $this->actingAs($owner, 'api');

        // Владелец проходит проверку владения — гейт его НЕ блокирует (не 403).
        $this->assertNotSame(403, $this->deleteJson($this->url($orderId))->status());
    }

    public function test_admin_passes_ownership_gate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $orderId = $this->makeListOrder((string) RamseyUuid::uuid4());

        $this->actingAs($admin, 'api');

        // Admin — суперроль, проходит без проверки владельца (не 403).
        $this->assertNotSame(403, $this->deleteJson($this->url($orderId))->status());
    }
}
