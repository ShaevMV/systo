<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Auto\Repositories\AutoRepositoryInterface;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Ф-rich (часть 2/2): org обогащает доставку в Baza богатыми полями гостя (search_blob) →
 * прикладывает их к ingest как блок `search` → Baza наполняет ticket_search (поиск без QR).
 */
class BazaSearchEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://baza.test';

    private function ticketResponse(): TicketResponse
    {
        return new TicketResponse(
            name: 'Иван Петров',
            kilter: 12345,
            uuid: Uuid::random(),
            status: 'paid',
            email: 'ivan@example.com',
            phone: '+79991234567',
            city: 'Казань',
            comment: null,
            date_order: Carbon::now(),
            festival_id: Uuid::random(),
            type_ticket_id: Uuid::random(),
        );
    }

    /** @return array<string,mixed> */
    private function search(): array
    {
        return [
            'fio' => 'Иван Петров', 'phone' => '+79991234567', 'telegram' => 'ivan',
            'car_number' => 'А123БВ77', 'child_name' => 'Маша Петрова', 'external_order_no' => '123',
        ];
    }

    public function test_dispatcher_stores_search_blob(): void
    {
        $id = app(BazaDeliveryDispatcher::class)->dispatch(
            $this->ticketResponse(),
            new BazaDeliveryContext(source: 'qr_pipeline'),
            $this->search(),
        );

        $blob = app(BazaDeliveryRepositoryInterface::class)->getSearchBlob($id);
        self::assertNotNull($blob);
        $decoded = json_decode(base64_decode($blob), true);
        self::assertSame('ivan', $decoded['telegram']);
        self::assertSame('А123БВ77', $decoded['car_number']);
    }

    public function test_deliver_includes_search_in_ingest_body(): void
    {
        config(['services.baza_ingest.url' => self::BASE, 'services.baza_ingest.token' => 'tok']);
        Http::fake([self::BASE.'/*' => Http::response(['success' => true], 200)]);

        $id = app(BazaDeliveryDispatcher::class)->dispatch(
            $this->ticketResponse(),
            new BazaDeliveryContext(source: 'qr_pipeline'),
            $this->search(),
        );

        $this->runJob($id);

        Http::assertSent(fn ($req) => $req->url() === self::BASE.'/api/baza/ingest/ticket'
            && isset($req['search'])
            && $req['search']['telegram'] === 'ivan'
            && $req['search']['child_name'] === 'Маша Петрова');
    }

    public function test_no_search_blob_omits_search_block(): void
    {
        config(['services.baza_ingest.url' => self::BASE, 'services.baza_ingest.token' => 'tok']);
        Http::fake([self::BASE.'/*' => Http::response(['success' => true], 200)]);

        // Доставка без search (классический путь) → в теле ingest нет блока search.
        $id = app(BazaDeliveryDispatcher::class)->dispatch(
            $this->ticketResponse(),
            new BazaDeliveryContext(source: 'qr_pipeline'),
        );

        $this->runJob($id);

        Http::assertSent(fn ($req) => $req->url() === self::BASE.'/api/baza/ingest/ticket' && ! isset($req['search']));
    }

    private function runJob(Uuid $id): void
    {
        (new DeliverTicketToBazaJob($id->value()))->handle(
            app(BazaDeliveryRepositoryInterface::class),
            app(TicketsRepositoryInterface::class),
            app(HistoryRepositoryInterface::class),
            app(AutoRepositoryInterface::class),
        );
    }
}
