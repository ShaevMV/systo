<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application;

use Tests\TestCase;
use Mockery;
use DomainException;
use Shared\Domain\ValueObject\Uuid;
use Shared\Domain\ValueObject\Status;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\Application\ChangeOrderPrice\ChangeOrderPriceCommand;
use Tickets\Order\OrderTicket\Application\ChangeOrderPrice\ChangeOrderPriceCommandHandler;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

/**
 * Integration тест для проверки изменения цены заказа.
 *
 * Использует Tests\TestCase (Laravel) потому что handler оборачивает
 * операции в DB::transaction — фасаду нужен Laravel container.
 *
 * Репозитории заинжекчены через Mockery — реальная БД не дёргается
 * через них, но DB::transaction открывает реальную транзакцию (которая
 * сворачивается RefreshDatabase).
 */
class ChangeOrderPriceTest extends TestCase
{
    private function createOrderTicketDto(float $price = 3800, bool $isFriendly = true): OrderTicketDto
    {
        $data = [
            'festival_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'user_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'email' => 'friendly@example.com',
            'phone' => '+79991234567',
            'types_of_payment_id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'ticket_type_id' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'guests' => [
                [
                    'value' => 'Тестовый Гость',
                    'email' => 'guest@example.com',
                    'number' => null,
                    'id' => 'dddddddd-dddd-dddd-dddd-dddddddddddd',
                    'festival_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
                ],
            ],
            'id_buy' => 'test-buy-id',
            'date' => date('Y-m-d H:i:s'),
            'status' => Status::NEW,
            'promo_code' => null,
            'questionnaire_type_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'id' => 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee',
        ];

        $userId = new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');
        $priceDto = new PriceDto((int)$price, 1, 0);
        $pusherId = $isFriendly ? new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb') : null;

        return OrderTicketDto::fromState($data, $userId, $priceDto, false, $pusherId);
    }

    /** @test */
    public function change_order_price_command_accepts_all_fields(): void
    {
        $orderId = new Uuid('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee');
        $adminId = new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
        $newPrice = 5000.0;

        $command = new ChangeOrderPriceCommand($orderId, $newPrice, $adminId);

        $this->assertTrue($command->getOrderId()->equals($orderId));
        $this->assertEquals($newPrice, $command->getPrice());
        $this->assertTrue($command->getAdminId()->equals($adminId));
    }

    /** @test */
    public function handler_calls_repository_change_price(): void
    {
        $orderId = new Uuid('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee');
        $adminId = new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
        $newPrice = 5000.0;

        $repositoryMock = Mockery::mock(OrderTicketRepositoryInterface::class);
        $historyMock = Mockery::mock(HistoryRepositoryInterface::class);

        $orderTicketDto = $this->createOrderTicketDto(price: 3800, isFriendly: true);

        $repositoryMock->shouldReceive('findOrder')
            ->once()
            ->with($orderId)
            ->andReturn($orderTicketDto);

        $repositoryMock->shouldReceive('changePrice')
            ->once()
            ->with($orderId, $newPrice)
            ->andReturn(true);

        // История пишется ровно один раз внутри транзакции (см. doc-комментарий handler-а).
        $historyMock->shouldReceive('save')->once();

        $handler = new ChangeOrderPriceCommandHandler($repositoryMock, $historyMock);

        $command = new ChangeOrderPriceCommand($orderId, $newPrice, $adminId);

        $handler($command);

        // Если дошли сюда — значит handler отработал без исключений
        $this->assertTrue(true);
    }

    /** @test */
    public function handler_throws_exception_if_order_not_found(): void
    {
        $orderId = new Uuid('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee');
        $adminId = new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $repositoryMock = Mockery::mock(OrderTicketRepositoryInterface::class);
        $historyMock = Mockery::mock(HistoryRepositoryInterface::class);

        $repositoryMock->shouldReceive('findOrder')
            ->once()
            ->with($orderId)
            ->andReturn(null);

        $handler = new ChangeOrderPriceCommandHandler($repositoryMock, $historyMock);

        $command = new ChangeOrderPriceCommand($orderId, 5000.0, $adminId);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Заказ не найден: ' . $orderId);

        $handler($command);
    }

    /** @test */
    public function handler_throws_exception_if_price_is_zero(): void
    {
        $orderId = new Uuid('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee');
        $adminId = new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $repositoryMock = Mockery::mock(OrderTicketRepositoryInterface::class);
        $historyMock = Mockery::mock(HistoryRepositoryInterface::class);

        $orderTicketDto = $this->createOrderTicketDto(price: 3800, isFriendly: true);

        $repositoryMock->shouldReceive('findOrder')
            ->once()
            ->with($orderId)
            ->andReturn($orderTicketDto);

        // changePrice НЕ должен вызываться
        $repositoryMock->shouldReceive('changePrice')->never();
        $historyMock->shouldReceive('save')->never();

        $handler = new ChangeOrderPriceCommandHandler($repositoryMock, $historyMock);

        $command = new ChangeOrderPriceCommand($orderId, 0, $adminId);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Цена должна быть больше нуля');

        $handler($command);
    }

    /** @test */
    public function handler_throws_exception_if_price_is_negative(): void
    {
        $orderId = new Uuid('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee');
        $adminId = new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $repositoryMock = Mockery::mock(OrderTicketRepositoryInterface::class);
        $historyMock = Mockery::mock(HistoryRepositoryInterface::class);

        $orderTicketDto = $this->createOrderTicketDto(price: 3800, isFriendly: true);

        $repositoryMock->shouldReceive('findOrder')
            ->once()
            ->with($orderId)
            ->andReturn($orderTicketDto);

        $repositoryMock->shouldReceive('changePrice')->never();
        $historyMock->shouldReceive('save')->never();

        $handler = new ChangeOrderPriceCommandHandler($repositoryMock, $historyMock);

        $command = new ChangeOrderPriceCommand($orderId, -100, $adminId);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Цена должна быть больше нуля');

        $handler($command);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
