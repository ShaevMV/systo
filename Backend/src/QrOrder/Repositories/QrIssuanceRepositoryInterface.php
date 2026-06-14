<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use Shared\Domain\ValueObject\Uuid;

/**
 * Чтения, нужные для АВТОНОМНОЙ выдачи билетов qr-заказа (без order_tickets):
 * номер билета (kilter, auto-increment) и шаблоны PDF/email по паре фестиваль+тип билета.
 */
interface QrIssuanceRepositoryInterface
{
    /** Номер билета (kilter) после вставки строки в tickets. */
    public function getKilter(Uuid $ticketId): ?int;

    /**
     * Шаблоны (pdf, email) из ticket_type_festival по (festival_id, ticket_type_id).
     * В штатном flow это резолвится через JOIN order_tickets — у qr заказа его нет,
     * поэтому резолвим напрямую по типу билета гостя.
     *
     * @return array{pdf: ?string, email: ?string}|null
     */
    public function findTemplate(Uuid $festivalId, Uuid $ticketTypeId): ?array;
}
