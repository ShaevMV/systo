<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Guest;

use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Policy\GuestOrderAccessPolicy;

/**
 * Тесты политики доступа GuestOrderAccessPolicy.
 *
 * Проверяет правила доступа по ролям:
 * - create: публичный (любая роль)
 * - viewList: только admin/seller
 * - viewItem: admin/seller или владелец заказа
 * - changeStatus: только admin/seller
 */
class GuestOrderAccessPolicyTest extends TestCase
{
    private GuestOrderAccessPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new GuestOrderAccessPolicy();
    }

    // ----------------------------------------------------------------
    // canCreate
    // ----------------------------------------------------------------

    /** @test */
    public function any_role_can_create_guest_order(): void
    {
        foreach (['guest', 'admin', 'seller', 'pusher', 'manager', 'unknown'] as $role) {
            $this->assertTrue($this->policy->canCreate($role), "Роль '$role' должна иметь доступ к созданию");
        }
    }

    // ----------------------------------------------------------------
    // canViewList
    // ----------------------------------------------------------------

    /** @test */
    public function admin_can_view_list(): void
    {
        $this->assertTrue($this->policy->canViewList('admin'));
    }

    /** @test */
    public function seller_can_view_list(): void
    {
        $this->assertTrue($this->policy->canViewList('seller'));
    }

    /** @test */
    public function guest_cannot_view_list(): void
    {
        $this->assertFalse($this->policy->canViewList('guest'));
    }

    /** @test */
    public function pusher_cannot_view_guest_order_list(): void
    {
        $this->assertFalse($this->policy->canViewList('pusher'));
    }

    // ----------------------------------------------------------------
    // canViewItem
    // ----------------------------------------------------------------

    /** @test */
    public function admin_can_view_any_item(): void
    {
        $order = $this->orderWithUserId(Uuid::random());
        $this->assertTrue($this->policy->canViewItem('admin', $order, Uuid::random()));
    }

    /** @test */
    public function seller_can_view_any_item(): void
    {
        $order = $this->orderWithUserId(Uuid::random());
        $this->assertTrue($this->policy->canViewItem('seller', $order, Uuid::random()));
    }

    /** @test */
    public function owner_can_view_own_item(): void
    {
        $userId = Uuid::random();
        $order  = $this->orderWithUserId($userId);

        $this->assertTrue($this->policy->canViewItem('guest', $order, $userId));
    }

    /** @test */
    public function non_owner_guest_cannot_view_item(): void
    {
        $ownerUuid = Uuid::random();
        $otherUuid = Uuid::random();
        $order     = $this->orderWithUserId($ownerUuid);

        $this->assertFalse($this->policy->canViewItem('guest', $order, $otherUuid));
    }

    /** @test */
    public function unauthenticated_cannot_view_item(): void
    {
        $order = $this->orderWithUserId(Uuid::random());
        $this->assertFalse($this->policy->canViewItem('guest', $order, null));
    }

    // ----------------------------------------------------------------
    // canChangeStatus
    // ----------------------------------------------------------------

    /** @test */
    public function admin_can_change_status(): void
    {
        $this->assertTrue($this->policy->canChangeStatus('admin', new Status(Status::PAID)));
    }

    /** @test */
    public function seller_can_change_status(): void
    {
        $this->assertTrue($this->policy->canChangeStatus('seller', new Status(Status::CANCEL)));
    }

    /** @test */
    public function pusher_cannot_change_guest_order_status(): void
    {
        $this->assertFalse($this->policy->canChangeStatus('pusher', new Status(Status::PAID)));
    }

    /** @test */
    public function guest_cannot_change_status(): void
    {
        $this->assertFalse($this->policy->canChangeStatus('guest', new Status(Status::CANCEL)));
    }

    // ----------------------------------------------------------------

    private function orderWithUserId(Uuid $userId): GuestOrder
    {
        return new GuestOrder(
            id:               Uuid::random(),
            festivalId:       Uuid::random(),
            userId:           $userId,
            status:           new Status(Status::NEW),
            tickets:          [],
            typesOfPaymentId: Uuid::random(),
            price:            new PriceDto(3800, 1, 0),
            ticketTypeId:     Uuid::random(),
            phone:            '+79991234567',
        );
    }
}
