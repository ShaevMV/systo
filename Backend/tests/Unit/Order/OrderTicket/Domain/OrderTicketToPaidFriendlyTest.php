<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Domain;

use Tests\TestCase;
use Shared\Domain\ValueObject\Uuid;
use Shared\Domain\ValueObject\Status;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaidFriendly;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;

/**
 * Unit тест для OrderTicket::toPaidFriendly() и ProcessUserNotificationOrderPaidFriendly.
 *
 * Проверяет что:
 * - toPaidFriendly() генерирует ProcessUserNotificationOrderPaidFriendly (не ProcessUserNotificationOrderPaid)
 * - toPaid() генерирует ProcessUserNotificationOrderPaid (регрессия)
 * - Оба метода генерируют ProcessCreateTicket
 */
class OrderTicketToPaidFriendlyTest extends TestCase
{
    private function createOrderTicketDto(bool $isFriendly = false): OrderTicketDto
    {
        $data = [
            'festival_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'user_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'email' => 'guest@example.com',
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
            'status' => Status::PAID,
            'promo_code' => null,
            'questionnaire_type_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'id' => 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee',
        ];

        $userId = new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');
        $priceDto = new PriceDto(3800, 1, 0, 3800, 3800);
        $pusherId = $isFriendly ? new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb') : null;

        return OrderTicketDto::fromState($data, $userId, $priceDto, false, $pusherId);
    }

    /** @test */
    public function toPaidFriendly_generates_ProcessUserNotificationOrderPaidFriendly(): void
    {
        $dto = $this->createOrderTicketDto(isFriendly: true);

        $orderTicket = OrderTicket::toPaidFriendly($dto);

        $events = $orderTicket->pullDomainEvents();

        // Проверяем что есть ProcessUserNotificationOrderPaidFriendly
        $friendlyEventFound = false;
        foreach ($events as $event) {
            if ($event instanceof ProcessUserNotificationOrderPaidFriendly) {
                $friendlyEventFound = true;
                break;
            }
        }

        $this->assertTrue($friendlyEventFound, 
            'toPaidFriendly() должен генерировать ProcessUserNotificationOrderPaidFriendly');
    }

    /** @test */
    public function toPaidFriendly_does_not_generate_ProcessUserNotificationOrderPaid(): void
    {
        $dto = $this->createOrderTicketDto(isFriendly: true);

        $orderTicket = OrderTicket::toPaidFriendly($dto);

        $events = $orderTicket->pullDomainEvents();

        // Проверяем что НЕТ ProcessUserNotificationOrderPaid
        $paidEventFound = false;
        foreach ($events as $event) {
            if ($event instanceof ProcessUserNotificationOrderPaid) {
                $paidEventFound = true;
                break;
            }
        }

        $this->assertFalse($paidEventFound,
            'toPaidFriendly() НЕ должен генерировать ProcessUserNotificationOrderPaid');
    }

    /** @test */
    public function toPaid_generates_ProcessUserNotificationOrderPaid(): void
    {
        // Регрессионный тест — убеждаемся что toPaid() не сломался
        $dto = $this->createOrderTicketDto(isFriendly: false);

        $orderTicket = OrderTicket::toPaid($dto);

        $events = $orderTicket->pullDomainEvents();

        $paidEventFound = false;
        foreach ($events as $event) {
            if ($event instanceof ProcessUserNotificationOrderPaid) {
                $paidEventFound = true;
                break;
            }
        }

        $this->assertTrue($paidEventFound,
            'toPaid() должен генерировать ProcessUserNotificationOrderPaid');
    }

    /** @test */
    public function toPaid_does_not_generate_ProcessUserNotificationOrderPaidFriendly(): void
    {
        $dto = $this->createOrderTicketDto(isFriendly: false);

        $orderTicket = OrderTicket::toPaid($dto);

        $events = $orderTicket->pullDomainEvents();

        $friendlyEventFound = false;
        foreach ($events as $event) {
            if ($event instanceof ProcessUserNotificationOrderPaidFriendly) {
                $friendlyEventFound = true;
                break;
            }
        }

        $this->assertFalse($friendlyEventFound,
            'toPaid() НЕ должен генерировать ProcessUserNotificationOrderPaidFriendly');
    }

    /** @test */
    public function both_methods_generate_ProcessCreateTicket(): void
    {
        $dtoFriendly = $this->createOrderTicketDto(isFriendly: true);
        $dtoRegular = $this->createOrderTicketDto(isFriendly: false);

        $orderTicketFriendly = OrderTicket::toPaidFriendly($dtoFriendly);
        $orderTicketRegular = OrderTicket::toPaid($dtoRegular);

        $eventsFriendly = $orderTicketFriendly->pullDomainEvents();
        $eventsRegular = $orderTicketRegular->pullDomainEvents();

        $createTicketFoundFriendly = false;
        $createTicketFoundRegular = false;

        foreach ($eventsFriendly as $event) {
            if ($event instanceof \Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket) {
                $createTicketFoundFriendly = true;
                break;
            }
        }

        foreach ($eventsRegular as $event) {
            if ($event instanceof \Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket) {
                $createTicketFoundRegular = true;
                break;
            }
        }

        $this->assertTrue($createTicketFoundFriendly,
            'toPaidFriendly() должен генерировать ProcessCreateTicket');
        $this->assertTrue($createTicketFoundRegular,
            'toPaid() должен генерировать ProcessCreateTicket');
    }
}
