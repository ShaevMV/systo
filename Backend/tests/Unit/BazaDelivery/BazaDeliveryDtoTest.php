<?php

declare(strict_types=1);

namespace Tests\Unit\BazaDelivery;

use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\History\Domain\ActorType;

/**
 * Сборка BazaDeliveryDto: фабрика queued() из контекста и fromState() из строки БД +
 * проекция toArrayForCreate() (поля для записи).
 */
class BazaDeliveryDtoTest extends TestCase
{
    public function test_queued_builds_row_from_context(): void
    {
        $id = Uuid::random();
        $ticketId = Uuid::random();
        $ctx = new BazaDeliveryContext(
            orderId: '11111111-1111-1111-1111-111111111111',
            festivalId: '22222222-2222-2222-2222-222222222222',
            name: 'Иван Гость',
            email: 'guest@example.com',
            number: 777,
            source: 'qr_pipeline',
            actorType: ActorType::QR,
        );

        $dto = BazaDeliveryDto::queued($id, $ticketId, 'el_tickets', $ctx);

        $this->assertTrue($dto->getId()->equals($id));
        $this->assertTrue($dto->getTicketId()->equals($ticketId));
        $this->assertSame('el_tickets', $dto->getTarget());
        $this->assertSame(BazaDeliveryStatus::QUEUED, $dto->getStatus());
        $this->assertSame(0, $dto->getAttempts());
        $this->assertSame('11111111-1111-1111-1111-111111111111', $dto->getOrderId());
        $this->assertSame(777, $dto->getNumber());
        $this->assertSame('qr_pipeline', $dto->getSource());
    }

    public function test_to_array_for_create_has_all_columns(): void
    {
        $ctx = new BazaDeliveryContext(name: 'Иван', email: 'i@e.ru', number: 1, source: 'org_event');
        $dto = BazaDeliveryDto::queued(Uuid::random(), Uuid::random(), 'spisok_tickets', $ctx);

        $row = $dto->toArrayForCreate();

        $this->assertSame(
            ['id', 'ticket_id', 'order_id', 'target', 'status', 'attempts', 'error', 'name', 'email', 'number', 'festival_id', 'source'],
            array_keys($row),
        );
        $this->assertSame('spisok_tickets', $row['target']);
        $this->assertSame(BazaDeliveryStatus::QUEUED, $row['status']);
        $this->assertSame(0, $row['attempts']);
        $this->assertNull($row['error']);
    }

    public function test_from_state_parses_db_row(): void
    {
        $dto = BazaDeliveryDto::fromState([
            'id' => '33333333-3333-3333-3333-333333333333',
            'ticket_id' => '44444444-4444-4444-4444-444444444444',
            'order_id' => '55555555-5555-5555-5555-555555555555',
            'target' => 'live_tickets',
            'status' => BazaDeliveryStatus::FAILED,
            'attempts' => 3,
            'error' => 'Не найден билет в Базе входа',
            'name' => 'Пётр',
            'email' => 'p@e.ru',
            'number' => 1024,
            'festival_id' => '66666666-6666-6666-6666-666666666666',
            'source' => 'org_event',
            'delivered_at' => null,
            'created_at' => '2026-06-19 10:00:00',
        ]);

        $this->assertSame('live_tickets', $dto->getTarget());
        $this->assertSame(BazaDeliveryStatus::FAILED, $dto->getStatus());
        $this->assertSame(3, $dto->getAttempts());
        $this->assertSame(1024, $dto->getNumber());
        $this->assertSame('55555555-5555-5555-5555-555555555555', $dto->getOrderId());
    }
}
