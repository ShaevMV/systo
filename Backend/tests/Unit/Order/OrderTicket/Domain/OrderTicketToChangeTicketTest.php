<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Domain;

use DomainException;
use Tests\TestCase;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderTicketChanged;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessGuestNotificationQuestionnaire;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;

/**
 * Unit-тесты OrderTicket::toChangeTicket().
 *
 * Регрессионные сценарии:
 * - Меняем НЕ последнего гостя в заказе из ≥2 гостей: данные других гостей
 *   НЕ должны затираться (был баг с висящей &$guest после foreach).
 * - Старые UUID идут в ProcessCancelTicket, новые — в ProcessCreateTicket.
 * - Анкета-уведомление шлётся именно изменённым гостям (с их новыми email).
 */
class OrderTicketToChangeTicketTest extends TestCase
{
    private const FESTIVAL_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    private const USER_ID     = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
    private const PAYMENT_ID  = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
    private const TICKET_TYPE = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
    private const ORDER_ID    = 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee';

    private const GUEST_A_ID = '11111111-1111-1111-1111-111111111111';
    private const GUEST_B_ID = '22222222-2222-2222-2222-222222222222';
    private const GUEST_C_ID = '33333333-3333-3333-3333-333333333333';

    private function makeOrder(array $guestRows): OrderTicketDto
    {
        $data = [
            'festival_id'           => self::FESTIVAL_ID,
            'user_id'               => self::USER_ID,
            'email'                 => 'buyer@example.com',
            'phone'                 => '+79991234567',
            'types_of_payment_id'   => self::PAYMENT_ID,
            'ticket_type_id'        => self::TICKET_TYPE,
            'guests'                => $guestRows,
            'id_buy'                => 'test-buy-id',
            'date'                  => date('Y-m-d H:i:s'),
            'status'                => Status::PAID,
            'promo_code'            => null,
            'questionnaire_type_id' => self::FESTIVAL_ID,
            'id'                    => self::ORDER_ID,
        ];

        return OrderTicketDto::fromState(
            $data,
            new Uuid(self::USER_ID),
            new PriceDto(3800, count($guestRows), 0),
            false,
            null,
        );
    }

    private function guestRow(string $id, string $name, ?string $email): array
    {
        return [
            'value'       => $name,
            'email'       => $email,
            'number'      => null,
            'id'          => $id,
            'festival_id' => self::FESTIVAL_ID,
        ];
    }

    /**
     * @test
     * Регрессия на висящую ссылку &$guest: заказ из 2 гостей, меняем ТОЛЬКО
     * первого. Данные второго (B) должны остаться нетронутыми, и в массиве
     * не должно быть дублей первого гостя.
     */
    public function changing_only_first_guest_does_not_overwrite_other_guests(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
            $this->guestRow(self::GUEST_B_ID, 'Борис', 'boris@example.com'),
        ]);

        $valueMap = [self::GUEST_A_ID => 'Анна Новая'];
        $emailMap = [self::GUEST_A_ID => 'anna-new@example.com'];

        $orderTicket = OrderTicket::toChangeTicket($dto, $valueMap, $emailMap);
        $guests = $orderTicket->getTicket();

        $this->assertCount(2, $guests, 'После изменения должно остаться 2 гостя');

        // Гость A — изменён (имя+email новые, UUID новый)
        $this->assertSame('Анна Новая', $guests[0]->getValue());
        $this->assertSame('anna-new@example.com', $guests[0]->getEmail());
        $this->assertNotSame(self::GUEST_A_ID, $guests[0]->getId()->value(),
            'У изменённого гостя должен быть новый UUID');

        // Гость B — НЕ тронут
        $this->assertSame('Борис', $guests[1]->getValue(),
            'Имя неизменённого гостя не должно затираться (регрессия &$guest)');
        $this->assertSame('boris@example.com', $guests[1]->getEmail(),
            'Email неизменённого гостя не должен затираться (регрессия &$guest)');
        $this->assertSame(self::GUEST_B_ID, $guests[1]->getId()->value(),
            'UUID неизменённого гостя должен остаться прежним');
    }

    /**
     * @test
     * Регрессия: заказ из 3 гостей, меняем ТОЛЬКО среднего (B).
     * Раньше из-за &$guest данные B затирались данными последнего C
     * (или наоборот) после второго foreach.
     */
    public function changing_only_middle_guest_keeps_first_and_last_intact(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
            $this->guestRow(self::GUEST_B_ID, 'Борис', 'boris@example.com'),
            $this->guestRow(self::GUEST_C_ID, 'Виктор', 'viktor@example.com'),
        ]);

        $valueMap = [self::GUEST_B_ID => 'Борис Новый'];
        $emailMap = [self::GUEST_B_ID => 'boris-new@example.com'];

        $orderTicket = OrderTicket::toChangeTicket($dto, $valueMap, $emailMap);
        $guests = $orderTicket->getTicket();

        $this->assertCount(3, $guests);

        // A — без изменений
        $this->assertSame('Анна', $guests[0]->getValue());
        $this->assertSame('anna@example.com', $guests[0]->getEmail());
        $this->assertSame(self::GUEST_A_ID, $guests[0]->getId()->value());

        // B — изменён
        $this->assertSame('Борис Новый', $guests[1]->getValue());
        $this->assertSame('boris-new@example.com', $guests[1]->getEmail());
        $this->assertNotSame(self::GUEST_B_ID, $guests[1]->getId()->value());

        // C — без изменений (тут раньше всё ломалось из-за висящей ссылки)
        $this->assertSame('Виктор', $guests[2]->getValue(),
            'Имя последнего гостя не должно затираться при изменении среднего');
        $this->assertSame('viktor@example.com', $guests[2]->getEmail(),
            'Email последнего гостя не должен затираться при изменении среднего');
        $this->assertSame(self::GUEST_C_ID, $guests[2]->getId()->value(),
            'UUID последнего гостя должен остаться прежним');
    }

    /**
     * @test
     * Все UUID в массиве гостей должны быть уникальны — даже если изменён
     * НЕ последний гость. Дубликаты UUID = баг с &$guest.
     */
    public function guest_uuids_remain_unique_after_partial_change(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
            $this->guestRow(self::GUEST_B_ID, 'Борис', 'boris@example.com'),
            $this->guestRow(self::GUEST_C_ID, 'Виктор', 'viktor@example.com'),
        ]);

        $orderTicket = OrderTicket::toChangeTicket(
            $dto,
            [self::GUEST_A_ID => 'Анна Новая'],
            [self::GUEST_A_ID => 'anna-new@example.com'],
        );

        $ids = array_map(
            static fn ($g) => $g->getId()->value(),
            $orderTicket->getTicket()
        );

        $this->assertSame(
            count($ids),
            count(array_unique($ids)),
            'UUID гостей должны быть уникальны (нет дублей из-за висящей ссылки)'
        );
    }

    /**
     * @test
     * ProcessCancelTicket должен получить СТАРЫЕ UUID изменённых гостей
     * (для удаления старых билетов из таблицы tickets).
     */
    public function records_process_cancel_ticket_with_old_uuids(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
            $this->guestRow(self::GUEST_B_ID, 'Борис', 'boris@example.com'),
        ]);

        $orderTicket = OrderTicket::toChangeTicket(
            $dto,
            [self::GUEST_A_ID => 'Анна Новая'],
            [self::GUEST_A_ID => 'anna-new@example.com'],
        );

        $cancelEvent = $this->findEvent($orderTicket->pullDomainEvents(), ProcessCancelTicket::class);
        $this->assertNotNull($cancelEvent, 'Должен быть записан ProcessCancelTicket');

        $ticketIds = $this->readPrivateArray($cancelEvent, 'ticketIds');
        $this->assertCount(1, $ticketIds);
        $this->assertSame(self::GUEST_A_ID, $ticketIds[0]->value(),
            'В ProcessCancelTicket должен идти СТАРЫЙ UUID гостя для удаления старого билета');
    }

    /**
     * @test
     * ProcessGuestNotificationQuestionnaire шлётся только изменённым гостям
     * и с их НОВЫМ email и НОВЫМ ticketId.
     */
    public function notifies_only_changed_guest_with_new_email_and_new_ticket_id(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
            $this->guestRow(self::GUEST_B_ID, 'Борис', 'boris@example.com'),
        ]);

        $orderTicket = OrderTicket::toChangeTicket(
            $dto,
            [self::GUEST_B_ID => 'Борис Новый'],
            [self::GUEST_B_ID => 'boris-new@example.com'],
        );

        $events = $orderTicket->pullDomainEvents();
        $notifications = array_values(array_filter(
            $events,
            static fn ($e) => $e instanceof ProcessGuestNotificationQuestionnaire,
        ));

        $this->assertCount(1, $notifications,
            'Анкета должна уйти только одному (изменённому) гостю');

        $email = $this->readPrivateValue($notifications[0], 'email');
        $orderId = $this->readPrivateValue($notifications[0], 'orderId');
        $ticketId = $this->readPrivateValue($notifications[0], 'ticketId');

        $this->assertSame('boris-new@example.com', $email,
            'Анкета должна уйти на новый email гостя');
        $this->assertSame(self::ORDER_ID, $orderId);
        $this->assertNotSame(self::GUEST_B_ID, $ticketId,
            'ticketId в уведомлении — это уже НОВЫЙ id (билет пересоздаётся)');

        // И этот новый ticketId должен совпадать с реальным новым id гостя B
        $this->assertSame(
            $orderTicket->getTicket()[1]->getId()->value(),
            $ticketId,
            'ticketId в уведомлении должен совпадать с актуальным UUID изменённого гостя',
        );
    }

    /**
     * @test
     * Если меняем ТОЛЬКО email (без value), запись истории «изменение ФИО»
     * с oldName === newName всё равно создаётся — это поведение текущего кода,
     * фиксируем его.
     */
    public function records_change_event_only_when_anything_changed(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
        ]);

        $orderTicket = OrderTicket::toChangeTicket(
            $dto,
            [],
            [self::GUEST_A_ID => 'anna-new@example.com'],
        );

        $changedEvent = $this->findEvent(
            $orderTicket->pullDomainEvents(),
            ProcessUserNotificationOrderTicketChanged::class,
        );

        $this->assertNotNull($changedEvent,
            'При смене хоть одного поля должно отправляться уведомление об изменении');
    }

    /**
     * @test
     */
    public function throws_when_no_changes_provided(): void
    {
        $this->expectException(DomainException::class);

        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
        ]);

        OrderTicket::toChangeTicket($dto, [], []);
    }

    /**
     * @test
     * ProcessCreateTicket должен получить именно изменённых гостей
     * с их НОВЫМИ UUID.
     */
    public function records_process_create_ticket_with_new_changed_guests(): void
    {
        $dto = $this->makeOrder([
            $this->guestRow(self::GUEST_A_ID, 'Анна', 'anna@example.com'),
            $this->guestRow(self::GUEST_B_ID, 'Борис', 'boris@example.com'),
        ]);

        $orderTicket = OrderTicket::toChangeTicket(
            $dto,
            [self::GUEST_A_ID => 'Анна Новая'],
            [self::GUEST_A_ID => 'anna-new@example.com'],
        );

        $createEvent = $this->findEvent($orderTicket->pullDomainEvents(), ProcessCreateTicket::class);
        $this->assertNotNull($createEvent);

        $quests = $this->readPrivateArray($createEvent, 'quests');
        $this->assertCount(1, $quests, 'В создание билетов должен попасть только изменённый гость');

        $this->assertSame('Анна Новая', $quests[0]->getValue());
        $this->assertSame('anna-new@example.com', $quests[0]->getEmail());
        $this->assertNotSame(self::GUEST_A_ID, $quests[0]->getId()->value(),
            'У создаваемого билета должен быть НОВЫЙ UUID');
    }

    private function findEvent(array $events, string $class): ?object
    {
        foreach ($events as $event) {
            if ($event instanceof $class) {
                return $event;
            }
        }
        return null;
    }

    /**
     * Доступ к private-полю объекта-события (нужно, потому что Process*-события
     * хранят payload в private-полях без геттеров).
     */
    private function readPrivateValue(object $object, string $property): mixed
    {
        $ref = new \ReflectionObject($object);
        // ищем свойство в иерархии классов
        while ($ref !== false) {
            if ($ref->hasProperty($property)) {
                $prop = $ref->getProperty($property);
                $prop->setAccessible(true);
                return $prop->getValue($object);
            }
            $ref = $ref->getParentClass();
        }
        $this->fail("Свойство {$property} не найдено в " . get_class($object));
    }

    private function readPrivateArray(object $object, string $property): array
    {
        $value = $this->readPrivateValue($object, $property);
        $this->assertIsArray($value);
        return $value;
    }
}
