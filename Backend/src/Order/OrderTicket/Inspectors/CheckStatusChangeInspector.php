<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Inspectors;

use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Shared\Domain\ValueObject\Uuid;

class CheckStatusChangeInspector
{
    public function checkIsCreate(
        OrderTicketItemForListResponse $response,
    ): string
    {
        $isNotCorrect = [];

        foreach ($response->getGuests() as $guestsDto) {
            if (!$this->checkIsFilePresence($guestsDto->getId())) {
                $isNotCorrect[$guestsDto->getId()->value()] = $guestsDto->getValue();
            }
        }
        $result = '';
        if (count($isNotCorrect) > 0) {
            $result.= " В заказе {$response->getId()->value()} ID {$response->getKilter()}. Данные билеты не были созданы: ". implode(',',array_keys($isNotCorrect));
        }

        return $result;
    }

    private function checkIsFilePresence(Uuid $id): bool
    {
        $filename = storage_path("app/public/tickets/{$id->value()}.pdf");

        return file_exists($filename);
    }
}
