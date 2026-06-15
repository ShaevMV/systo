<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\History\Domain\Event\TemplateCreatedEvent;
use Tickets\Template\Domain\Template;

/**
 * Часть A (Template → агрегат с историей): UNIT-проверка доменного агрегата и событий —
 * без БД. Действия пишут recordHistory(...), pullHistoryEvents() отдаёт и очищает буфер.
 */
class TemplateAggregateTest extends TestCase
{
    public function test_created_records_single_event_and_clears_buffer(): void
    {
        $template = Template::created(Uuid::random(), 'orderToPaid', 'email', 'Оплата');

        $events = $template->pullHistoryEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TemplateCreatedEvent::class, $events[0]);
        $this->assertSame('template', $events[0]->getAggregateType());
        $this->assertSame('template_created', $events[0]->getEventName());
        $this->assertSame(['slug' => 'orderToPaid', 'kind' => 'email', 'title' => 'Оплата'], $events[0]->getPayload());

        // Буфер очищен после pull.
        $this->assertCount(0, $template->pullHistoryEvents());
    }

    public function test_edited_records_changed_fields_and_skips_when_empty(): void
    {
        $template = Template::existing(Uuid::random());
        $template->edited(['title', 'body']);

        $events = $template->pullHistoryEvents();
        $this->assertCount(1, $events);
        $this->assertSame('template_edited', $events[0]->getEventName());
        $this->assertSame(['changed' => ['title', 'body']], $events[0]->getPayload());

        // Пустой список изменений → события НЕ пишется.
        $noChange = Template::existing(Uuid::random());
        $noChange->edited([]);
        $this->assertCount(0, $noChange->pullHistoryEvents());
    }

    public function test_activated_event_payload(): void
    {
        $template = Template::existing(Uuid::random());
        $template->activated(false);

        $event = $template->pullHistoryEvents()[0];
        $this->assertSame('template_activated', $event->getEventName());
        $this->assertSame(['active' => false], $event->getPayload());
    }

    public function test_published_filters_null_comment(): void
    {
        $template = Template::existing(Uuid::random());

        $template->published('правки текста');
        $this->assertSame(['comment' => 'правки текста'], $template->pullHistoryEvents()[0]->getPayload());

        $template->published(null);
        $this->assertSame([], $template->pullHistoryEvents()[0]->getPayload());
    }

    public function test_rolled_back_event_payload(): void
    {
        $versionId = Uuid::random()->value();
        $template = Template::existing(Uuid::random());
        $template->rolledBack($versionId, '2026-06-15 10:00:00');

        $event = $template->pullHistoryEvents()[0];
        $this->assertSame('template', $event->getAggregateType());
        $this->assertSame('template_rolled_back', $event->getEventName());
        $this->assertSame(
            ['to_version_id' => $versionId, 'to_date' => '2026-06-15 10:00:00'],
            $event->getPayload(),
        );
    }
}
