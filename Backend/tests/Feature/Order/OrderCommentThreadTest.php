<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\User;
use Database\Seeders\OrderSeeder;
use Database\Seeders\UserSeeder;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\ValueObject\CommentSource;
use Tests\TestCase;

/**
 * C1: ядро треда комментариев к заказу.
 *
 * Покрывает:
 *  - admin/manager добавляют комментарий → строка в треде + событие истории `comment_added`;
 *  - тред возвращается в хронологическом порядке с автором/источником;
 *  - старый difficulties-флоу по-прежнему создаёт комментарий (не сломан);
 *  - права: guest/seller не могут добавить (403).
 */
class OrderCommentThreadTest extends TestCase
{
    private const ORDER_ID = OrderSeeder::ID_FOR_FIRST_ORDER;

    /**
     * Берём засеянного админа (id из БД — строка, как в проде), а не factory
     * (у factory-юзера id остаётся VO в памяти до перезагрузки). Имя задаём детерминированно.
     */
    private function admin(): User
    {
        /** @var User $admin */
        $admin = User::query()->findOrFail(UserSeeder::ID_FOR_ADMIN_UUID);
        $admin->update(['name' => 'Админ Тест']);

        return $admin;
    }

    private function addUrl(string $orderId = self::ORDER_ID): string
    {
        return '/api/v1/order/' . $orderId . '/comment';
    }

    private function listUrl(string $orderId = self::ORDER_ID): string
    {
        return '/api/v1/order/' . $orderId . '/comments';
    }

    public function test_admin_adds_comment_creates_thread_row_and_history(): void
    {
        $this->actingAs($this->admin(), 'api');

        $this->postJson($this->addUrl(), ['comment' => 'VIP, проводить до сцены'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('comment.comment', 'VIP, проводить до сцены')
            ->assertJsonPath('comment.author_source', CommentSource::ORG_USER)
            ->assertJsonPath('comment.author_name', 'Админ Тест');

        // Строка появилась в треде заказа.
        $this->assertDatabaseHas('comment', [
            'order_tickets_id' => self::ORDER_ID,
            'comment'          => 'VIP, проводить до сцены',
            'author_source'    => CommentSource::ORG_USER,
        ]);

        // Событие истории `comment_added` без текста комментария (только источник/флаги).
        $history = app(HistoryRepositoryInterface::class)->getByAggregateId(self::ORDER_ID);
        $events  = array_map(static fn ($h) => $h->eventName, $history);
        self::assertContains('comment_added', $events);

        $commentEvent = null;
        foreach ($history as $h) {
            if ($h->eventName === 'comment_added') {
                $commentEvent = $h;
                break;
            }
        }
        self::assertNotNull($commentEvent);
        self::assertSame('order', $commentEvent->aggregateType);
        self::assertSame(CommentSource::ORG_USER, $commentEvent->payload['source']);
        // Текст комментария НЕ попадает в payload истории (ПДн).
        self::assertArrayNotHasKey('comment', $commentEvent->payload);
        self::assertArrayNotHasKey('text', $commentEvent->payload);
    }

    public function test_manager_can_add_comment(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager', 'name' => 'Менеджер']), 'api');

        $this->postJson($this->addUrl(), ['comment' => 'Заметка менеджера'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('comment', [
            'order_tickets_id' => self::ORDER_ID,
            'comment'          => 'Заметка менеджера',
        ]);
    }

    public function test_thread_returns_comments_in_chronological_order_with_author(): void
    {
        // Чистый заказ без засеянных комментариев (CommentSeeder наполняет только FIRST order).
        $orderId = OrderSeeder::ID_FOR_SECOND_ORDER;

        $this->actingAs($this->admin(), 'api');

        // Колонка created_at — секундной точности; разносим вставки во времени,
        // чтобы сортировка `created_at ASC` была детерминированной.
        \Illuminate\Support\Carbon::setTestNow('2026-06-21 10:00:01');
        $this->postJson($this->addUrl($orderId), ['comment' => 'Первый'])->assertStatus(200);
        \Illuminate\Support\Carbon::setTestNow('2026-06-21 10:00:02');
        $this->postJson($this->addUrl($orderId), ['comment' => 'Второй'])->assertStatus(200);
        \Illuminate\Support\Carbon::setTestNow('2026-06-21 10:00:03');
        $this->postJson($this->addUrl($orderId), ['comment' => 'Третий'])->assertStatus(200);
        \Illuminate\Support\Carbon::setTestNow();

        $response = $this->getJson($this->listUrl($orderId))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $comments = $response->json('comments');
        self::assertCount(3, $comments);

        // Весь тред — в порядке добавления (по времени).
        $texts = array_map(static fn ($c) => $c['comment'], $comments);
        self::assertSame(['Первый', 'Второй', 'Третий'], $texts);

        // У каждой записи есть автор/источник/время.
        foreach ($comments as $c) {
            self::assertArrayHasKey('id', $c);
            self::assertArrayHasKey('author_name', $c);
            self::assertArrayHasKey('author_source', $c);
            self::assertArrayHasKey('created_at', $c);
        }

        // Запись org-юзера несёт имя автора и источник org_user.
        $last = end($comments);
        self::assertSame(CommentSource::ORG_USER, $last['author_source']);
        self::assertSame('Админ Тест', $last['author_name']);
    }

    public function test_difficulties_flow_still_creates_comment(): void
    {
        $before = CommentOrderTicketModel::query()
            ->whereOrderTicketsId(self::ORDER_ID)
            ->count();

        $this->actingAs($this->admin(), 'api');

        // difficulties_arose требует комментарий — старый флоу должен создать строку треда.
        $this->postJson('/api/v1/order/toChangeStatus/' . self::ORDER_ID, [
            'status'  => 'difficulties_arose',
            'comment' => 'Возникли трудности с оплатой',
        ])->assertStatus(200);

        $after = CommentOrderTicketModel::query()
            ->whereOrderTicketsId(self::ORDER_ID)
            ->count();

        self::assertSame($before + 1, $after, 'difficulties-флоу должен добавить комментарий в тред');

        $this->assertDatabaseHas('comment', [
            'order_tickets_id' => self::ORDER_ID,
            'comment'          => 'Возникли трудности с оплатой',
            'author_source'    => CommentSource::ORG_USER,
        ]);
    }

    public function test_guest_cannot_add_comment(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');

        $this->postJson($this->addUrl(), ['comment' => 'нельзя'])
            ->assertStatus(403);
    }

    public function test_seller_cannot_add_comment(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');

        $this->postJson($this->addUrl(), ['comment' => 'нельзя'])
            ->assertStatus(403);
    }

    public function test_empty_comment_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'api');

        $this->postJson($this->addUrl(), ['comment' => '   '])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
