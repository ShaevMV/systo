<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\BazaDelivery\Application\BazaDeliveryApplication;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\BazaDelivery\Application\GetList\BazaDeliveryGetListQuery;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Админ-слой доставки в Baza: список с whitelist-фильтром и пагинацией, повторная доставка (resend),
 * счётчик застрявших для дашборда.
 */
class BazaDeliveryApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function seedDelivery(string $target, string $status, ?Uuid $festivalId = null): Uuid
    {
        $id = Uuid::random();
        $repo = app(BazaDeliveryRepositoryInterface::class);
        $repo->create(BazaDeliveryDto::queued(
            $id,
            Uuid::random(),
            $target,
            new BazaDeliveryContext(source: 'org_event', festivalId: $festivalId?->value()),
        ));
        if ($status === BazaDeliveryStatus::FAILED) {
            $repo->markFailed($id, 'Baza недоступна');
        } elseif ($status === BazaDeliveryStatus::DELIVERED) {
            $repo->markDelivered($id);
        }

        return $id;
    }

    public function test_get_list_applies_status_whitelist_filter(): void
    {
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::FAILED);
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::FAILED);
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::DELIVERED);

        $response = app(BazaDeliveryApplication::class)->getList(
            new BazaDeliveryGetListQuery(['status' => BazaDeliveryStatus::FAILED], Order::none(), 1, 20),
        );

        $this->assertSame(2, $response->getTotalCount());
        $this->assertCount(2, $response->getCollection());
    }

    public function test_get_list_paginates(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::QUEUED);
        }

        $response = app(BazaDeliveryApplication::class)->getList(
            new BazaDeliveryGetListQuery([], Order::none(), 1, 2),
        );

        $this->assertSame(5, $response->getTotalCount());
        $this->assertCount(2, $response->getCollection());
    }

    public function test_count_stuck_counts_failed(): void
    {
        $festivalId = Uuid::random();
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::FAILED, $festivalId);
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::FAILED, $festivalId);
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::DELIVERED, $festivalId);
        $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::FAILED, Uuid::random());

        $app = app(BazaDeliveryApplication::class);
        $this->assertSame(3, $app->countStuck(null));
        $this->assertSame(2, $app->countStuck($festivalId));
    }

    public function test_resend_requeues_failed_record(): void
    {
        Queue::fake();
        $id = $this->seedDelivery(BazaDeliveryDispatcher::TARGET_EL, BazaDeliveryStatus::FAILED);

        $ok = app(BazaDeliveryApplication::class)->resend($id, null);

        $this->assertTrue($ok);
        $this->assertSame(BazaDeliveryStatus::QUEUED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());

        $events = array_map(fn ($h) => $h->eventName, app(HistoryRepositoryInterface::class)->getByAggregateId($id->value()));
        $this->assertContains('baza_queued', $events);
        Queue::assertPushed(DeliverTicketToBazaJob::class);
    }

    public function test_resend_returns_false_when_not_found(): void
    {
        Queue::fake();
        $this->assertFalse(app(BazaDeliveryApplication::class)->resend(Uuid::random(), null));
    }
}
